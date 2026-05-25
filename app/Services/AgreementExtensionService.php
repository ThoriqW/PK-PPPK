<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Agreement;
use App\Models\AgreementArchive;
use App\Models\AgreementTemplate;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\NumberingConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles agreement extensions:
 *  - single: extend one specific agreement
 *  - batch:  extend every active agreement whose employee.appointment_year matches
 *
 * Extension never mutates the old agreement. The old row's status flips to ARSIP
 * and a new agreement row (kind = PERPANJANGAN) is created with parent_agreement_id
 * pointing at the previous one. Default duration: 5 years, capped at retirement.
 */
class AgreementExtensionService
{
    public function __construct(
        private readonly RetirementCalculator $retirement,
        private readonly AgreementNumberGenerator $numberGenerator,
        private readonly PdfRenderingService $pdf,
        private readonly QrTokenService $qrTokens,
        private readonly AuditLogger $audit,
        private readonly AgreementCreationService $creator,
    ) {
    }

    /**
     * Extend a single agreement. New period_start defaults to old period_end + 1 day.
     */
    public function extend(
        Agreement $agreement,
        AgreementTemplate $template,
        NumberingConfig $numbering,
        int $years = 5,
        ?Carbon $newStart = null,
        array $signing = [],
    ): Agreement {
        if ($agreement->status !== Agreement::STATUS_AKTIF) {
            throw new BusinessException(
                'AGREEMENT_NOT_ACTIVE',
                'Hanya perjanjian aktif yang dapat diperpanjang.',
                ['agreement_id' => $agreement->id, 'status' => $agreement->status],
            );
        }

        $employee = $agreement->employee;
        if (! $employee || $employee->status !== Employee::STATUS_AKTIF) {
            throw new BusinessException(
                'EMPLOYEE_NOT_ACTIVE',
                'Pegawai tidak aktif.',
                ['employee_id' => $employee?->id],
            );
        }

        $start = $newStart ?: $agreement->period_end->copy()->addDay();
        $suggestion = $this->retirement->suggestExtensionEnd($employee, $start, $years);

        if (! $suggestion['allowed']) {
            throw new BusinessException(
                $suggestion['reason'] ?: 'EXTENSION_NOT_ALLOWED',
                'Perpanjangan tidak dapat dilakukan.',
                ['employee_id' => $employee->id, 'period_start' => $start->toDateString()],
            );
        }

        $end = $suggestion['suggested_end'];

        return DB::transaction(function () use ($agreement, $employee, $template, $numbering, $start, $end, $signing, $suggestion) {
            // Archive the old agreement first.
            $this->archive($agreement, "Perpanjangan menjadi periode {$start->toDateString()} s/d {$end->toDateString()}");

            // Create the new (extension) agreement, then re-parent it.
            $version = $this->creator->ensureLatestTemplateVersion($template);
            $issued  = $this->numberGenerator->generate($numbering, now(), $employee->opd?->code);

            $extension = new Agreement([
                'employee_id'         => $employee->id,
                'template_id'         => $template->id,
                'template_version_id' => $version->id,
                'numbering_config_id' => $numbering->id,
                'agreement_number'    => $issued['number'],
                'agreement_sequence'  => $issued['sequence'],
                'kind'                => Agreement::KIND_PERPANJANGAN,
                'parent_agreement_id' => $agreement->id,
                'period_start'        => $start->toDateString(),
                'period_end'          => $end->toDateString(),
                'status'              => Agreement::STATUS_DRAFT,
                'signed_at'           => $signing['signed_at']          ?? null,
                'signed_by_name'      => $signing['signed_by_name']     ?? null,
                'signed_by_position'  => $signing['signed_by_position'] ?? null,
                'qr_token'            => $this->qrTokens->generateUniqueToken(),
                'snapshot'            => $this->creator->snapshotEmployee($employee, $start, $end),
                'created_by'          => Auth::id(),
                'updated_by'          => Auth::id(),
            ]);
            $extension->save();

            $rendered = $this->pdf->render($extension);
            $extension->pdf_path = $rendered['path'];
            $extension->pdf_hash = $rendered['hash'];
            $extension->status   = Agreement::STATUS_AKTIF;
            $extension->save();

            $this->audit->log(
                AuditLog::ACTION_AGREEMENT_EXTEND,
                "Perpanjangan: {$extension->agreement_number} dari {$agreement->agreement_number}",
                $extension,
                [
                    'parent_agreement_id' => $agreement->id,
                    'employee_id'         => $employee->id,
                    'period_start'        => $start->toDateString(),
                    'period_end'          => $end->toDateString(),
                    'capped_by_retirement'=> (bool) $suggestion['capped'],
                ],
            );

            return $extension->fresh();
        });
    }

    /**
     * Batch-extend all AKTIF agreements whose employee.appointment_year == $year.
     * Returns a per-employee outcome list so the UI can show what was processed,
     * skipped, and failed.
     *
     * @return array<int, array{employee_id:int, agreement_id:int, status:string, message:?string, new_agreement_id:?int}>
     */
    public function batchExtendByAppointmentYear(
        int $appointmentYear,
        AgreementTemplate $template,
        NumberingConfig $numbering,
        int $years = 5,
    ): array {
        $candidates = Agreement::query()
            ->where('status', Agreement::STATUS_AKTIF)
            ->whereHas('employee', fn ($q) => $q->where('appointment_year', $appointmentYear)
                ->where('status', Employee::STATUS_AKTIF))
            ->with(['employee.opd', 'employee.jabatanCategory'])
            ->get();

        $outcomes = [];
        foreach ($candidates as $agreement) {
            try {
                $new = $this->extend($agreement, $template, $numbering, $years);
                $outcomes[] = [
                    'employee_id'      => $agreement->employee_id,
                    'agreement_id'     => $agreement->id,
                    'status'           => 'EXTENDED',
                    'message'          => null,
                    'new_agreement_id' => $new->id,
                ];
            } catch (BusinessException $e) {
                $outcomes[] = [
                    'employee_id'      => $agreement->employee_id,
                    'agreement_id'     => $agreement->id,
                    'status'           => $e->errorCode(),
                    'message'          => $e->getMessage(),
                    'new_agreement_id' => null,
                ];
            }
        }
        return $outcomes;
    }

    public function archive(Agreement $agreement, string $reason): AgreementArchive
    {
        $agreement->update(['status' => Agreement::STATUS_ARSIP]);

        $archive = AgreementArchive::create([
            'agreement_id'    => $agreement->id,
            'archived_at'     => now(),
            'archived_reason' => $reason,
            'archived_by'     => Auth::id(),
        ]);

        $this->audit->log(
            AuditLog::ACTION_ARCHIVE,
            "Arsip: {$agreement->agreement_number}",
            $agreement,
            ['reason' => $reason],
        );

        return $archive;
    }
}
