<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Single source of truth for retirement-age math.
 *
 * Convention: retirement effective date = last day of the month in which
 * the employee reaches the retirement age dictated by their jabatan_category.
 * If BKPSDMD prefers a different convention later (exact birthday, or first
 * day of next month), change it here and nowhere else.
 */
class RetirementCalculator
{
    public function computeRetirementDate(Employee $e): ?Carbon
    {
        $category = $e->jabatanCategory;
        if (! $category || ! $e->date_of_birth) {
            return null;
        }

        $dob = CarbonImmutable::parse($e->date_of_birth);
        $retire = $dob->addYears($category->retirement_age);
        // Last day of the month the employee turns retirement_age.
        return Carbon::parse($retire->endOfMonth()->toDateString());
    }

    /**
     * Latest legally allowed period_end for any new agreement
     * issued for this employee. Returns null if not computable.
     */
    public function maxAllowedPeriodEnd(Employee $e): ?Carbon
    {
        return $this->computeRetirementDate($e);
    }

    /**
     * Suggest an end date for an extension of $years years starting on $newStart.
     * If the candidate end exceeds the retirement cap, the result is capped.
     *
     * @return array{allowed: bool, suggested_end: \Carbon\Carbon|null, capped: bool, reason: string|null}
     */
    public function suggestExtensionEnd(Employee $e, Carbon $newStart, int $years = 5): array
    {
        $cap = $this->maxAllowedPeriodEnd($e);
        // -1 day so a 5-year extension covers exactly 5 years inclusive on both ends.
        $candidate = $newStart->copy()->addYears($years)->subDay();

        if ($cap === null) {
            return [
                'allowed'       => false,
                'suggested_end' => null,
                'capped'        => false,
                'reason'        => 'KATEGORI_JABATAN_BELUM_DIISI',
            ];
        }

        if ($newStart->gt($cap)) {
            return [
                'allowed'       => false,
                'suggested_end' => null,
                'capped'        => false,
                'reason'        => 'PEGAWAI_SUDAH_PENSIUN',
            ];
        }

        if ($candidate->gt($cap)) {
            return [
                'allowed'       => true,
                'suggested_end' => $cap,
                'capped'        => true,
                'reason'        => 'CAPPED_BY_RETIREMENT',
            ];
        }

        return [
            'allowed'       => true,
            'suggested_end' => $candidate,
            'capped'        => false,
            'reason'        => null,
        ];
    }
}
