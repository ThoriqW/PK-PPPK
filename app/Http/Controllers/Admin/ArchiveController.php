<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgreementResource;
use App\Models\Agreement;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $q = Agreement::query()
            ->with(['employee.opd', 'template'])
            ->where('status', Agreement::STATUS_ARSIP)
            ->when($request->filled('appointment_year'),
                fn ($qb) => $qb->whereHas('employee', fn ($e) => $e->where('appointment_year', $request->integer('appointment_year'))))
            ->when($request->filled('opd_id'),
                fn ($qb) => $qb->whereHas('employee', fn ($e) => $e->where('opd_id', $request->integer('opd_id'))))
            ->orderByDesc('updated_at');

        return AgreementResource::collection($q->paginate($request->integer('per_page', 25)));
    }
}
