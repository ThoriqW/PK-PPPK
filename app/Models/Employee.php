<?php

namespace App\Models;

use App\Services\RetirementCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_AKTIF    = 'AKTIF';
    public const STATUS_PENSIUN  = 'PENSIUN';
    public const STATUS_NONAKTIF = 'NONAKTIF';

    protected $fillable = [
        'nip', 'nik', 'full_name', 'place_of_birth', 'date_of_birth', 'gender',
        'education', 'jabatan', 'jabatan_category_id', 'golongan',
        'opd_id', 'unit_kerja', 'appointment_year',
        'phone', 'email', 'status', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'    => 'date',
            'appointment_year' => 'integer',
            'meta'             => 'array',
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function jabatanCategory(): BelongsTo
    {
        return $this->belongsTo(JabatanCategory::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function activeAgreement(): ?Agreement
    {
        return $this->agreements()->where('status', Agreement::STATUS_AKTIF)->latest('id')->first();
    }

    /**
     * Date of mandatory retirement, computed via the rules table.
     * Returns null if jabatan_category is missing.
     */
    public function retirementDate(): ?Carbon
    {
        return app(RetirementCalculator::class)->computeRetirementDate($this);
    }
}
