<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request): array
    {
        $retirement = $this->retirementDate();

        return [
            'id'                  => $this->id,
            'nip'                 => $this->nip,
            'nik'                 => $this->nik,
            'full_name'           => $this->full_name,
            'place_of_birth'      => $this->place_of_birth,
            'date_of_birth'       => optional($this->date_of_birth)->toDateString(),
            'gender'              => $this->gender,
            'education'           => $this->education,
            'jabatan'             => $this->jabatan,
            'jabatan_category'    => $this->whenLoaded('jabatanCategory', fn () => [
                'id'             => $this->jabatanCategory->id,
                'code'           => $this->jabatanCategory->code,
                'name'           => $this->jabatanCategory->name,
                'retirement_age' => $this->jabatanCategory->retirement_age,
            ]),
            'golongan'            => $this->golongan,
            'opd'                 => $this->whenLoaded('opd', fn () => [
                'id'   => $this->opd->id,
                'code' => $this->opd->code,
                'name' => $this->opd->name,
            ]),
            'unit_kerja'          => $this->unit_kerja,
            'appointment_year'    => $this->appointment_year,
            'phone'               => $this->phone,
            'email'               => $this->email,
            'status'              => $this->status,
            'retirement_date'     => $retirement?->toDateString(),
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
