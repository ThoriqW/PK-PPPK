<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function index(Request $request)
    {
        $q = Employee::query()
            ->with(['opd', 'jabatanCategory'])
            ->when($request->filled('search'), function ($qb) use ($request) {
                $s = $request->string('search')->toString();
                $qb->where(function ($w) use ($s) {
                    $w->where('full_name', 'ilike', "%{$s}%")
                      ->orWhere('nip', 'ilike', "%{$s}%")
                      ->orWhere('nik', 'ilike', "%{$s}%");
                });
            })
            ->when($request->filled('opd_id'), fn ($qb) => $qb->where('opd_id', $request->integer('opd_id')))
            ->when($request->filled('appointment_year'),
                fn ($qb) => $qb->where('appointment_year', $request->integer('appointment_year')))
            ->when($request->filled('status'), fn ($qb) => $qb->where('status', $request->string('status')))
            ->orderBy('full_name');

        return EmployeeResource::collection($q->paginate($request->integer('per_page', 25)));
    }

    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee->load(['opd', 'jabatanCategory']));
    }

    public function store(UpsertEmployeeRequest $request): EmployeeResource
    {
        $employee = Employee::create($request->validated());
        $this->audit->log(AuditLog::ACTION_EMPLOYEE_UPDATE, "Tambah pegawai manual: {$employee->full_name}", $employee);
        return new EmployeeResource($employee->load(['opd', 'jabatanCategory']));
    }

    public function update(UpsertEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee->update($request->validated());
        $this->audit->log(AuditLog::ACTION_EMPLOYEE_UPDATE, "Ubah pegawai: {$employee->full_name}", $employee);
        return new EmployeeResource($employee->load(['opd', 'jabatanCategory']));
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();
        $this->audit->log(AuditLog::ACTION_EMPLOYEE_UPDATE, "Hapus (soft) pegawai: {$employee->full_name}", $employee);
        return response()->json(['ok' => true]);
    }
}
