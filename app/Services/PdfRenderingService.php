<?php

namespace App\Services;

use App\Models\Agreement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Renders an Agreement to PDF deterministically:
 *   - reads body_html from the snapshotted agreement_template_version (NOT the live template)
 *   - replaces placeholders with snapshot data + computed fields
 *   - injects QR code data URI
 *   - wraps the resulting HTML in a print-friendly Blade layout
 *   - writes to the 'agreements' disk and stores SHA-256 hash on the model
 */
class PdfRenderingService
{
    public function __construct(private readonly QrTokenService $qr)
    {
    }

    /**
     * @return array{path:string, hash:string, absolute_path:string}
     */
    public function render(Agreement $agreement): array
    {
        $agreement->loadMissing(['templateVersion', 'employee.opd', 'employee.jabatanCategory']);

        $verifyUrl = $agreement->publicVerifyUrl();
        // Auto-picks PNG (via GD) or SVG depending on what the PHP install
        // supports. Both render reliably in DomPDF.
        $qrInline = $this->qr->inlineHtml($verifyUrl, 160);

        $body = $this->fillPlaceholders(
            (string) $agreement->templateVersion->body_html,
            $agreement,
            $qrInline,
            $verifyUrl,
        );

        $html = View::make('agreements.pdf-layout', [
            'agreement' => $agreement,
            'body'      => $body,
            'qrInline'  => $qrInline,
            'verifyUrl' => $verifyUrl,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption(['isPhpEnabled' => false, 'isRemoteEnabled' => false]);

        $binary = $pdf->output();

        $relativePath = sprintf(
            '%d/agreement-%d-%s.pdf',
            (int) $agreement->created_at?->year ?: date('Y'),
            $agreement->id,
            Str::slug((string) $agreement->agreement_number, '_'),
        );

        Storage::disk('agreements')->put($relativePath, $binary);

        $hash = hash('sha256', $binary);

        return [
            'path'          => $relativePath,
            'hash'          => $hash,
            'absolute_path' => Storage::disk('agreements')->path($relativePath),
        ];
    }

    /**
     * Replace {{placeholder}} tokens inside the template body with snapshot
     * values + computed fields. Unknown tokens are left as-is so the admin can spot them.
     *
     * @param string $qrInline pre-rendered HTML snippet (img or inline svg)
     */
    public function fillPlaceholders(string $bodyHtml, Agreement $agreement, string $qrInline, string $verifyUrl): string
    {
        $snap = (array) $agreement->snapshot;

        $values = [
            'nomor_perjanjian'   => (string) $agreement->agreement_number,
            'tanggal_mulai'      => optional($agreement->period_start)->translatedFormat('d F Y'),
            'tanggal_selesai'    => optional($agreement->period_end)->translatedFormat('d F Y'),
            'tanggal_tanda_tangan' => optional($agreement->signed_at)->translatedFormat('d F Y'),
            'penanda_tangan_nama'   => (string) $agreement->signed_by_name,
            'penanda_tangan_jabatan'=> (string) $agreement->signed_by_position,

            'nama'             => (string) ($snap['full_name'] ?? ''),
            'nip'              => (string) ($snap['nip'] ?? ''),
            'nik'              => (string) ($snap['nik'] ?? ''),
            'tempat_lahir'     => (string) ($snap['place_of_birth'] ?? ''),
            'tanggal_lahir'    => isset($snap['date_of_birth'])
                ? \Carbon\Carbon::parse($snap['date_of_birth'])->translatedFormat('d F Y')
                : '',
            'jenis_kelamin'    => (string) ($snap['gender'] ?? ''),
            'pendidikan'       => (string) ($snap['education'] ?? ''),
            'jabatan'          => (string) ($snap['jabatan'] ?? ''),
            'kategori_jabatan' => (string) ($snap['jabatan_category_name'] ?? ''),
            'golongan'         => (string) ($snap['golongan'] ?? ''),
            'opd'              => (string) ($snap['opd_name'] ?? ''),
            'unit_kerja'       => (string) ($snap['unit_kerja'] ?? ''),
            'tahun_pengangkatan'=> (string) ($snap['appointment_year'] ?? ''),
            'tanggal_pensiun'  => isset($snap['retirement_date'])
                ? \Carbon\Carbon::parse($snap['retirement_date'])->translatedFormat('d F Y')
                : '',

            'qr_code'      => $qrInline,
            'qr_url'       => $verifyUrl,
        ];

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/u',
            fn ($m) => array_key_exists($m[1], $values) ? (string) $values[$m[1]] : $m[0],
            $bodyHtml,
        );
    }
}
