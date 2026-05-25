<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementTemplateVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'template_id', 'version', 'body_html', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version'    => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AgreementTemplate::class, 'template_id');
    }
}
