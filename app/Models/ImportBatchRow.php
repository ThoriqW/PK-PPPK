<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchRow extends Model
{
    use HasFactory;

    public const OUTCOME_INSERTED  = 'INSERTED';
    public const OUTCOME_DUPLICATE = 'DUPLICATE';
    public const OUTCOME_INVALID   = 'INVALID';
    public const OUTCOME_ERROR     = 'ERROR';

    public $timestamps = false;

    protected $fillable = [
        'import_batch_id', 'row_number', 'payload',
        'outcome', 'error_message', 'employee_id',
    ];

    protected function casts(): array
    {
        return [
            'payload'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
