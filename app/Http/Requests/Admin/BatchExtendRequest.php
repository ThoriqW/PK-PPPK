<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BatchExtendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'appointment_year'    => ['required', 'integer', 'min:1950', 'max:2100'],
            'template_id'         => ['required', 'integer', 'exists:agreement_templates,id'],
            'numbering_config_id' => ['required', 'integer', 'exists:numbering_configs,id'],
            'years'               => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
