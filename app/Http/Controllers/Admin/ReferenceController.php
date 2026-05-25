<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Models\Employee;
use App\Models\JabatanCategory;
use App\Models\NumberingConfig;
use App\Models\Opd;

/**
 * Lookup endpoints used by the Vue UI to populate dropdowns and dashboard.
 */
class ReferenceController extends Controller
{
    public function opds()
    {
        return Opd::orderBy('name')->get(['id', 'code', 'name', 'is_active']);
    }

    public function jabatanCategories()
    {
        return JabatanCategory::orderBy('name')->get(['id', 'code', 'name', 'retirement_age', 'is_active']);
    }

    public function appointmentYears()
    {
        return Employee::query()
            ->select('appointment_year')->distinct()
            ->orderByDesc('appointment_year')
            ->pluck('appointment_year');
    }

    public function activeNumberings()
    {
        return NumberingConfig::orderBy('name')->get();
    }

    public function activeTemplates()
    {
        return AgreementTemplate::orderBy('name')->get(['id', 'name', 'description', 'is_active', 'version']);
    }

    public function dashboard()
    {
        $now = now();
        return response()->json([
            'employees_total'    => Employee::count(),
            'employees_active'   => Employee::where('status', Employee::STATUS_AKTIF)->count(),
            'agreements_active'  => Agreement::where('status', Agreement::STATUS_AKTIF)->count(),
            'agreements_archive' => Agreement::where('status', Agreement::STATUS_ARSIP)->count(),
            'expiring_90d'       => Agreement::where('status', Agreement::STATUS_AKTIF)
                ->whereBetween('period_end', [$now->toDateString(), $now->copy()->addDays(90)->toDateString()])
                ->count(),
        ]);
    }
}
