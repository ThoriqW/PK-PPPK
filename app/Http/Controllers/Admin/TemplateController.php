<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertTemplateRequest;
use App\Http\Resources\AgreementTemplateResource;
use App\Models\AgreementTemplate;
use App\Models\AgreementTemplateVersion;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplateController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function index(Request $request)
    {
        $q = AgreementTemplate::query()
            ->when($request->filled('search'), fn ($qb) => $qb->where('name', 'ilike', '%'.$request->string('search').'%'))
            ->orderByDesc('updated_at');
        return AgreementTemplateResource::collection($q->paginate($request->integer('per_page', 20)));
    }

    public function show(AgreementTemplate $template): AgreementTemplateResource
    {
        return new AgreementTemplateResource($template);
    }

    public function store(UpsertTemplateRequest $request): AgreementTemplateResource
    {
        $template = DB::transaction(function () use ($request) {
            $tpl = AgreementTemplate::create(array_merge($request->validated(), [
                'version'    => 1,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]));

            AgreementTemplateVersion::create([
                'template_id' => $tpl->id,
                'version'     => 1,
                'body_html'   => $tpl->body_html,
                'created_by'  => $request->user()?->id,
            ]);

            return $tpl;
        });

        $this->audit->log(AuditLog::ACTION_TEMPLATE_CREATE, "Buat template: {$template->name}", $template);
        return new AgreementTemplateResource($template);
    }

    public function update(UpsertTemplateRequest $request, AgreementTemplate $template): AgreementTemplateResource
    {
        DB::transaction(function () use ($request, $template) {
            $data = $request->validated();
            $bodyChanged = $data['body_html'] !== $template->body_html;

            $template->fill($data);
            if ($bodyChanged) {
                $template->version = (int) $template->version + 1;
            }
            $template->updated_by = $request->user()?->id;
            $template->save();

            if ($bodyChanged) {
                AgreementTemplateVersion::create([
                    'template_id' => $template->id,
                    'version'     => (int) $template->version,
                    'body_html'   => $template->body_html,
                    'created_by'  => $request->user()?->id,
                ]);
            }
        });

        $this->audit->log(AuditLog::ACTION_TEMPLATE_UPDATE, "Ubah template: {$template->name} v{$template->version}", $template, [
            'version' => (int) $template->version,
        ]);

        return new AgreementTemplateResource($template->fresh());
    }

    public function destroy(AgreementTemplate $template): JsonResponse
    {
        // Soft prevention: a template referenced by agreements must not be deleted.
        if ($template->id && DB::table('agreements')->where('template_id', $template->id)->exists()) {
            return response()->json([
                'error' => [
                    'code'    => 'TEMPLATE_IN_USE',
                    'message' => 'Template sudah digunakan oleh perjanjian dan tidak dapat dihapus.',
                ],
            ], 422);
        }
        $template->delete();
        return response()->json(['ok' => true]);
    }

    public function placeholders(): JsonResponse
    {
        return response()->json([
            'placeholders' => [
                ['key' => 'nomor_perjanjian',     'label' => 'Nomor Perjanjian'],
                ['key' => 'tanggal_mulai',        'label' => 'Tanggal Mulai'],
                ['key' => 'tanggal_selesai',      'label' => 'Tanggal Selesai'],
                ['key' => 'tanggal_tanda_tangan', 'label' => 'Tanggal Tanda Tangan'],
                ['key' => 'penanda_tangan_nama', 'label' => 'Nama Penanda Tangan'],
                ['key' => 'penanda_tangan_jabatan','label' => 'Jabatan Penanda Tangan'],
                ['key' => 'nama',                 'label' => 'Nama Pegawai'],
                ['key' => 'nip',                  'label' => 'NIP'],
                ['key' => 'nik',                  'label' => 'NIK'],
                ['key' => 'tempat_lahir',         'label' => 'Tempat Lahir'],
                ['key' => 'tanggal_lahir',        'label' => 'Tanggal Lahir'],
                ['key' => 'jenis_kelamin',        'label' => 'Jenis Kelamin'],
                ['key' => 'pendidikan',           'label' => 'Pendidikan'],
                ['key' => 'jabatan',              'label' => 'Jabatan'],
                ['key' => 'kategori_jabatan',     'label' => 'Kategori Jabatan'],
                ['key' => 'golongan',             'label' => 'Golongan'],
                ['key' => 'opd',                  'label' => 'OPD'],
                ['key' => 'unit_kerja',           'label' => 'Unit Kerja'],
                ['key' => 'tahun_pengangkatan',   'label' => 'Tahun Pengangkatan'],
                ['key' => 'tanggal_pensiun',      'label' => 'Tanggal Pensiun'],
                ['key' => 'qr_code',              'label' => 'QR Code (gambar)'],
                ['key' => 'qr_url',               'label' => 'URL Verifikasi'],
            ],
        ]);
    }
}
