<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgreementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'agreement_number'    => $this->agreement_number,
            'kind'                => $this->kind,
            'status'              => $this->status,
            'period_start'        => optional($this->period_start)->toDateString(),
            'period_end'          => optional($this->period_end)->toDateString(),
            'signed_at'           => optional($this->signed_at)->toDateString(),
            'signed_by_name'      => $this->signed_by_name,
            'signed_by_position'  => $this->signed_by_position,
            'qr_url'              => $this->publicVerifyUrl(),
            'has_pdf'             => (bool) $this->pdf_path,
            'parent_agreement_id' => $this->parent_agreement_id,
            'employee'            => $this->whenLoaded('employee', fn () => [
                'id'        => $this->employee->id,
                'nip'       => $this->employee->nip,
                'full_name' => $this->employee->full_name,
                'jabatan'   => $this->employee->jabatan,
                'opd'       => $this->employee->opd?->name,
                'appointment_year' => $this->employee->appointment_year,
            ]),
            'template'            => $this->whenLoaded('template', fn () => [
                'id'   => $this->template->id,
                'name' => $this->template->name,
            ]),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
