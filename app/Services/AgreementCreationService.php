<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Models\AgreementTemplateVersion;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\NumberingConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates the issuance of a brand-new agreement (kind = BARU).
 * - validates business rules (employee active, period fits retirement)
 * - reserves the next number (transactional)
 * - snapshots employee data and the template version used
 * - persists the agreement in DRAFT, generates QR token
 * - calls PdfRenderingService to produce the file, then flips status to AKTIF
 */
class AgreementCreationService
{
    public function __construct(
        private readonly RetirementCalculator $retirement,
        private readonly AgreementNumberGenerator $numberGenerator,
        private readonly PdfRenderingService $pdf,
        private readonly QrTokenService $qrTokens,
        private readonly AuditLogger $audit,
    ) {
    }

    public function create(
        Employee $employee,
        AgreementTemplate $template,
        NumberingConfig $numbering,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $signing = [],
    ): Agreement {
        $this->guardEmployeeActive($employee);
        $this->guardPeriodWithinRetirement($employee, $periodEnd);
        $this->guardPeriodValid($periodStart, $periodEnd);

        return DB::transaction(function () use ($employee, $template, $numbering, $periodStart, $periodEnd, $signing) {
            $version = $this->ensureLatestTemplateVersion($template);

            $issued = $this->numberGenerator->generate($numbering, now(), $employee->opd?->code);

            $agreement = new Agreement([
                'employee_id'         => $employee->id,
                'template_id'         => $template->id,
                'template_version_id' => $version->id,
                'numbering_config_id' => $numbering->id,
                'agreement_number'    => $issued['number'],
                'agreement_sequence'  => $issued['sequence'],
                'kind'                => Agreement::KIND_BARU,
                'parent_agreement_id' => null,
                'period_start'        => $periodStart->toDateString(),
                'period_end'          => $periodEnd->toDateString(),
                'status'              => Agreement::STATUS_DRAFT,
                'signed_at'           => $signing['signed_at']           ?? null,
                'signed_by_name'      => $signing['signed_by_name']      ?? null,
                'signed_by_position'  => $signing['signed_by_position']  ?? null,
                'qr_token'            => $this->qrTokens->generateUniqueToken(),
                'snapshot'            => $this->snapshotEmployee($employee, $periodStart, $periodEnd),
                'created_by'          => Auth::id(),
                'updated_by'          => Auth::id(),
            ]);
            $agreement->save();

            $rendered = $this->pdf->render($agreement);
            $agreement->pdf_path = $rendered['path'];
            $agreement->pdf_hash = $rendered['hash'];
            $agreement->status   = Agreement::STATUS_AKTIF;
            $agreement->save();

            $this->audit->log(
                AuditLog::ACTION_AGREEMENT_CREATE,
                "Perjanjian baru: {$agreement->agreement_number} untuk {$employee->full_name}",
                $agreement,
                [
                    'employee_id'      => $employee->id,
                    'period_start'     => $agreement->period_start->toDateString(),
                    'period_end'       => $agreement->period_end->toDateString(),
                    'numbering_config' => $numbering->id,
                    'template_version' => $version->id,
                ],
            );

            return $agreement->fresh(['employee', 'template', 'templateVersion', 'numberingConfig']);
        });
    }

    public function ensureLatestTemplateVersion(AgreementTemplate $template): AgreementTemplateVersion
    {
        if (empty(trim((string) $template->body_html))) {
            throw new BusinessException(
                'TEMPLATE_BODY_EMPTY',
                'Template perjanjian belum memiliki isi.',
                ['template_id' => $template->id],
            );
        }

        $latest = $template->latestVersion();
        if ($latest && $latest->body_html === $template->body_html && $latest->version === (int) $template->version) {
            return $latest;
        }

        return AgreementTemplateVersion::create([
            'template_id' => $template->id,
            'version'     => (int) $template->version,
            'body_html'   => (string) $template->body_html,
            'created_by'  => Auth::id(),
        ]);
    }

    private function guardEmployeeActive(Employee $employee): void
    {
        if ($employee->status !== Employee::STATUS_AKTIF) {
            throw new BusinessException(
                'EMPLOYEE_NOT_ACTIVE',
                "Pegawai {$employee->full_name} berstatus {$employee->status} dan tidak dapat dibuatkan perjanjian.",
                ['employee_id' => $employee->id, 'status' => $employee->status],
            );
        }
    }

    private function guardPeriodValid(Carbon $start, Carbon $end): void
    {
        if ($end->lt($start)) {
            throw new BusinessException(
                'PERIOD_INVALID',
                'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                ['period_start' => $start->toDateString(), 'period_end' => $end->toDateString()],
            );
        }
    }

    private function guardPeriodWithinRetirement(Employee $employee, Carbon $periodEnd): void
    {
        $cap = $this->retirement->maxAllowedPeriodEnd($employee);
        if ($cap === null) {
            throw new BusinessException(
                'KATEGORI_JABATAN_BELUM_DIISI',
                'Kategori jabatan pegawai belum diisi sehingga tanggal pensiun tidak dapat dihitung.',
                ['employee_id' => $employee->id],
            );
        }
        if ($periodEnd->gt($cap)) {
            throw new BusinessException(
                'RETIREMENT_CAP_EXCEEDED',
                'Tanggal selesai melebihi batas usia pensiun pegawai.',
                [
                    'employee_id'        => $employee->id,
                    'period_end'         => $periodEnd->toDateString(),
                    'retirement_cap'     => $cap->toDateString(),
                ],
            );
        }
    }

    /**
     * Frozen copy of every datum that affects what the rendered PDF says.
     * Re-rendering an agreement years later must always produce the same output,
     * regardless of subsequent edits to the employee record.
     */
    public function snapshotEmployee(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $employee->loadMissing(['opd', 'jabatanCategory']);

        return [
            'employee_id'           => $employee->id,
            'nip'                   => $employee->nip,
            'nik'                   => $employee->nik,
            'full_name'             => $employee->full_name,
            'place_of_birth'        => $employee->place_of_birth,
            'date_of_birth'         => optional($employee->date_of_birth)->toDateString(),
            'gender'                => $employee->gender,
            'education'             => $employee->education,
            'jabatan'               => $employee->jabatan,
            'jabatan_category_code' => $employee->jabatanCategory?->code,
            'jabatan_category_name' => $employee->jabatanCategory?->name,
            'golongan'              => $employee->golongan,
            'opd_code'              => $employee->opd?->code,
            'opd_name'              => $employee->opd?->name,
            'unit_kerja'            => $employee->unit_kerja,
            'appointment_year'      => $employee->appointment_year,
            'phone'                 => $employee->phone,
            'email'                 => $employee->email,
            'period_start'          => $periodStart->toDateString(),
            'period_end'            => $periodEnd->toDateString(),
            'retirement_date'       => optional($this->retirement->computeRetirementDate($employee))->toDateString(),
            'snapshotted_at'        => now()->toIso8601String(),
        ];
    }
}
