<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_PARTIAL = 'PARTIAL';
    public const STATUS_FAILED  = 'FAILED';

    protected $fillable = [
        'filename', 'stored_path', 'uploaded_by',
        'total_rows', 'inserted_rows', 'skipped_rows', 'failed_rows',
        'status', 'started_at', 'finished_at', 'error_summary',
    ];

    protected function casts(): array
    {
        return [
            'started_at'    => 'datetime',
            'finished_at'   => 'datetime',
            'total_rows'    => 'integer',
            'inserted_rows' => 'integer',
            'skipped_rows'  => 'integer',
            'failed_rows'   => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class);
    }
}
