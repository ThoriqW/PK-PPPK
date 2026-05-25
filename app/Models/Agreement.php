<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agreement extends Model
{
    use HasFactory;

    public const KIND_BARU         = 'BARU';
    public const KIND_PERPANJANGAN = 'PERPANJANGAN';

    public const STATUS_DRAFT      = 'DRAFT';
    public const STATUS_AKTIF      = 'AKTIF';
    public const STATUS_ARSIP      = 'ARSIP';
    public const STATUS_DIBATALKAN = 'DIBATALKAN';

    protected $fillable = [
        'employee_id', 'template_id', 'template_version_id', 'numbering_config_id',
        'agreement_number', 'agreement_sequence',
        'kind', 'parent_agreement_id',
        'period_start', 'period_end',
        'status',
        'signed_at', 'signed_by_name', 'signed_by_position',
        'pdf_path', 'pdf_hash',
        'qr_token',
        'snapshot',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start'        => 'date',
            'period_end'          => 'date',
            'signed_at'           => 'date',
            'agreement_sequence'  => 'integer',
            'snapshot'            => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AgreementTemplate::class, 'template_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(AgreementTemplateVersion::class, 'template_version_id');
    }

    public function numberingConfig(): BelongsTo
    {
        return $this->belongsTo(NumberingConfig::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_agreement_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_agreement_id');
    }

    public function archives(): HasMany
    {
        return $this->hasMany(AgreementArchive::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARSIP;
    }

    /**
     * Public verification URL embedded in the QR.
     */
    public function publicVerifyUrl(): string
    {
        $base = rtrim((string) config('app.public_verify_base_url') ?: (string) config('app.url'), '/');
        return $base.'/verify/'.$this->qr_token;
    }
}
