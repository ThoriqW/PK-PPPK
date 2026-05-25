<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JabatanCategory extends Model
{
    use HasFactory;

    public const CODE_FUNGSIONAL_GURU         = 'FUNGSIONAL_GURU';
    public const CODE_FUNGSIONAL_AHLI_PERTAMA = 'FUNGSIONAL_AHLI_PERTAMA';
    public const CODE_FUNGSIONAL_AHLI_MUDA    = 'FUNGSIONAL_AHLI_MUDA';
    public const CODE_FUNGSIONAL_KETERAMPILAN = 'FUNGSIONAL_KETERAMPILAN';
    public const CODE_PELAKSANA               = 'PELAKSANA';

    protected $fillable = [
        'code', 'name', 'retirement_age', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'retirement_age' => 'integer',
            'is_active'      => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
