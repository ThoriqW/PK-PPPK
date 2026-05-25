<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * The single recommended way to write to audit_logs. Inserts only,
 * never updates. Tolerant of missing user (e.g., system events, queued jobs).
 */
class AuditLogger
{
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $meta = [],
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id'      => $userId ?? Auth::id(),
            'action'       => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'description'  => $description,
            'meta'         => $meta ?: null,
            'ip_address'   => Request::ip(),
            'user_agent'   => substr((string) Request::userAgent(), 0, 500),
            'created_at'   => now(),
        ]);
    }
}
