<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgreementTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'body_html',
        'is_active', 'version',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'version'   => 'integer',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AgreementTemplateVersion::class, 'template_id');
    }

    public function latestVersion(): ?AgreementTemplateVersion
    {
        return $this->versions()->orderByDesc('version')->first();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
