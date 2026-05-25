<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExtendAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'template_id'         => ['required', 'integer', 'exists:agreement_templates,id'],
            'numbering_config_id' => ['required', 'integer', 'exists:numbering_configs,id'],
            'years'               => ['nullable', 'integer', 'min:1', 'max:10'],
            'period_start'        => ['nullable', 'date'],
            'signed_at'           => ['nullable', 'date'],
            'signed_by_name'      => ['nullable', 'string', 'max:255'],
            'signed_by_position'  => ['nullable', 'string', 'max:255'],
        ];
    }
}
