<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public const ACTION_LOGIN              = 'LOGIN';
    public const ACTION_LOGOUT             = 'LOGOUT';
    public const ACTION_IMPORT             = 'IMPORT';
    public const ACTION_TEMPLATE_UPDATE    = 'TEMPLATE_UPDATE';
    public const ACTION_TEMPLATE_CREATE    = 'TEMPLATE_CREATE';
    public const ACTION_AGREEMENT_CREATE   = 'AGREEMENT_CREATE';
    public const ACTION_AGREEMENT_EXTEND   = 'AGREEMENT_EXTEND';
    public const ACTION_AGREEMENT_CANCEL   = 'AGREEMENT_CANCEL';
    public const ACTION_ARCHIVE            = 'ARCHIVE';
    public const ACTION_NUMBERING_CHANGE   = 'NUMBERING_CHANGE';
    public const ACTION_EMPLOYEE_UPDATE    = 'EMPLOYEE_UPDATE';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'description', 'meta', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta'       => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
