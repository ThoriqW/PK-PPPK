<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberingConfig extends Model
{
    use HasFactory;

    public const RESET_NEVER   = 'NEVER';
    public const RESET_YEARLY  = 'YEARLY';
    public const RESET_MONTHLY = 'MONTHLY';

    protected $fillable = [
        'name', 'format', 'current_number', 'padding',
        'reset_policy', 'last_issued_year', 'last_issued_month',
        'is_active', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'current_number'    => 'integer',
            'padding'           => 'integer',
            'is_active'         => 'boolean',
            'last_issued_year'  => 'integer',
            'last_issued_month' => 'integer',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
