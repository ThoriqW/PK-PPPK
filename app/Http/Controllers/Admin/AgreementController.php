<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BatchExtendRequest;
use App\Http\Requests\Admin\CreateAgreementRequest;
use App\Http\Requests\Admin\ExtendAgreementRequest;
use App\Http\Resources\AgreementResource;
use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Models\Employee;
use App\Models\NumberingConfig;
use App\Services\AgreementCreationService;
use App\Services\AgreementExtensionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgreementController extends Controller
{
    public function __construct(
        private readonly AgreementCreationService $creator,
        private readonly AgreementExtensionService $extender,
    ) {
    }

    public function index(Request $request)
    {
        $q = Agreement::query()
            ->with(['employee.opd', 'template'])
            ->when($request->filled('search'), function ($qb) use ($request) {
                $s = $request->string('search')->toString();
                $qb->where(function ($w) use ($s) {
                    $w->where('agreement_number', 'ilike', "%{$s}%")
                      ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'ilike', "%{$s}%")
                          ->orWhere('nip', 'ilike', "%{$s}%"));
                });
            })
            ->when($request->filled('status'), fn ($qb) => $qb->where('status', $request->string('status')))
            ->when($request->filled('kind'),   fn ($qb) => $qb->where('kind',   $request->string('kind')))
            ->when($request->filled('appointment_year'),
                fn ($qb) => $qb->whereHas('employee', fn ($e) => $e->where('appointment_year', $request->integer('appointment_year'))))
            ->when($request->filled('opd_id'),
                fn ($qb) => $qb->whereHas('employee', fn ($e) => $e->where('opd_id', $request->integer('opd_id'))))
            ->orderByDesc('id');

        return AgreementResource::collection($q->paginate($request->integer('per_page', 25)));
    }

    public function show(Agreement $agreement): AgreementResource
    {
        return new AgreementResource($agreement->load(['employee.opd', 'template', 'numberingConfig', 'parent', 'archives']));
    }

    public function store(CreateAgreementRequest $request): AgreementResource
    {
        $data      = $request->validated();
        $employee  = Employee::with(['opd', 'jabatanCategory'])->findOrFail($data['employee_id']);
        $template  = AgreementTemplate::findOrFail($data['template_id']);
        $numbering = NumberingConfig::findOrFail($data['numbering_config_id']);

        $agreement = $this->creator->create(
            $employee,
            $template,
            $numbering,
            Carbon::parse($data['period_start']),
            Carbon::parse($data['period_end']),
            [
                'signed_at'          => $data['signed_at']          ?? null,
                'signed_by_name'     => $data['signed_by_name']     ?? null,
                'signed_by_position' => $data['signed_by_position'] ?? null,
            ],
        );

        return new AgreementResource($agreement->load(['employee.opd', 'template']));
    }

    public function extend(ExtendAgreementRequest $request, Agreement $agreement): AgreementResource
    {
        $data      = $request->validated();
        $template  = AgreementTemplate::findOrFail($data['template_id']);
        $numbering = NumberingConfig::findOrFail($data['numbering_config_id']);

        $extension = $this->extender->extend(
            $agreement,
            $template,
            $numbering,
            (int) ($data['years'] ?? 5),
            isset($data['period_start']) ? Carbon::parse($data['period_start']) : null,
            [
                'signed_at'          => $data['signed_at']          ?? null,
                'signed_by_name'     => $data['signed_by_name']     ?? null,
                'signed_by_position' => $data['signed_by_position'] ?? null,
            ],
        );

        return new AgreementResource($extension->load(['employee.opd', 'template']));
    }

    public function batchExtend(BatchExtendRequest $request): JsonResponse
    {
        $data      = $request->validated();
        $template  = AgreementTemplate::findOrFail($data['template_id']);
        $numbering = NumberingConfig::findOrFail($data['numbering_config_id']);

        $outcomes = $this->extender->batchExtendByAppointmentYear(
            (int) $data['appointment_year'],
            $template,
            $numbering,
            (int) ($data['years'] ?? 5),
        );

        $extended = count(array_filter($outcomes, fn ($o) => $o['status'] === 'EXTENDED'));
        return response()->json([
            'total'     => count($outcomes),
            'extended'  => $extended,
            'failures'  => count($outcomes) - $extended,
            'outcomes'  => $outcomes,
        ]);
    }

    public function downloadPdf(Agreement $agreement): BinaryFileResponse|JsonResponse
    {
        if (! $agreement->pdf_path || ! Storage::disk('agreements')->exists($agreement->pdf_path)) {
            return response()->json([
                'error' => ['code' => 'PDF_NOT_FOUND', 'message' => 'PDF tidak ditemukan untuk perjanjian ini.'],
            ], 404);
        }

        $absolute = Storage::disk('agreements')->path($agreement->pdf_path);
        $filename = sprintf('Perjanjian-%s.pdf', str_replace('/', '-', (string) $agreement->agreement_number));
        return response()->download($absolute, $filename);
    }

    public function cancel(Request $request, Agreement $agreement): AgreementResource
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        if ($agreement->status !== Agreement::STATUS_AKTIF) {
            return new AgreementResource($agreement);
        }
        $agreement->update(['status' => Agreement::STATUS_DIBATALKAN]);
        $this->extender->archive($agreement, "Dibatalkan: ".$request->string('reason'));

        return new AgreementResource($agreement->fresh()->load(['employee.opd', 'template']));
    }
}
