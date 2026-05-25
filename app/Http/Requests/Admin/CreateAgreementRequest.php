<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'employee_id'         => ['required', 'integer', 'exists:employees,id'],
            'template_id'         => ['required', 'integer', 'exists:agreement_templates,id'],
            'numbering_config_id' => ['required', 'integer', 'exists:numbering_configs,id'],
            'period_start'        => ['required', 'date'],
            'period_end'          => ['required', 'date', 'after_or_equal:period_start'],
            'signed_at'           => ['nullable', 'date'],
            'signed_by_name'      => ['nullable', 'string', 'max:255'],
            'signed_by_position'  => ['nullable', 'string', 'max:255'],
        ];
    }
}
