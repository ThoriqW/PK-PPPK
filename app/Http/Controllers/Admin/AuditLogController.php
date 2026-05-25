<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('action'), fn ($qb) => $qb->where('action', $request->string('action')))
            ->when($request->filled('user_id'), fn ($qb) => $qb->where('user_id', $request->integer('user_id')))
            ->when($request->filled('from'), fn ($qb) => $qb->where('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'),   fn ($qb) => $qb->where('created_at', '<=', $request->date('to')))
            ->orderByDesc('id');

        return $q->paginate($request->integer('per_page', 50));
    }

    public function actions()
    {
        return response()->json([
            'actions' => [
                AuditLog::ACTION_LOGIN,
                AuditLog::ACTION_LOGOUT,
                AuditLog::ACTION_IMPORT,
                AuditLog::ACTION_TEMPLATE_CREATE,
                AuditLog::ACTION_TEMPLATE_UPDATE,
                AuditLog::ACTION_AGREEMENT_CREATE,
                AuditLog::ACTION_AGREEMENT_EXTEND,
                AuditLog::ACTION_AGREEMENT_CANCEL,
                AuditLog::ACTION_ARCHIVE,
                AuditLog::ACTION_NUMBERING_CHANGE,
                AuditLog::ACTION_EMPLOYEE_UPDATE,
            ],
        ]);
    }
}
