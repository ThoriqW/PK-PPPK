<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NumberingConfigResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'format'          => $this->format,
            'current_number'  => (int) $this->current_number,
            'padding'         => (int) $this->padding,
            'reset_policy'    => $this->reset_policy,
            'last_issued_year'=> $this->last_issued_year,
            'last_issued_month'=> $this->last_issued_month,
            'is_active'       => (bool) $this->is_active,
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
