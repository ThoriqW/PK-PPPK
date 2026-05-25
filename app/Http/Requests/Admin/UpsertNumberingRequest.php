<?php

namespace App\Http\Requests\Admin;

use App\Models\NumberingConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertNumberingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'format'         => ['required', 'string', 'max:255', 'regex:/\{seq\}/'],
            'current_number' => ['required', 'integer', 'min:0'],
            'padding'        => ['required', 'integer', 'min:1', 'max:6'],
            'reset_policy'   => ['required', Rule::in([
                NumberingConfig::RESET_NEVER,
                NumberingConfig::RESET_YEARLY,
                NumberingConfig::RESET_MONTHLY,
            ])],
            'is_active'      => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'format.regex' => 'Format wajib mengandung placeholder {seq}.',
        ];
    }
}
