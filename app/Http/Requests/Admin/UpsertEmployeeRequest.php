<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('employee')?->id ?? $this->route('employee');

        return [
            'nip'                 => ['nullable', 'string', 'max:32', Rule::unique('employees', 'nip')->ignore($id)->whereNull('deleted_at')],
            'nik'                 => ['nullable', 'string', 'max:32', Rule::unique('employees', 'nik')->ignore($id)->whereNull('deleted_at')],
            'full_name'           => ['required', 'string', 'max:255'],
            'place_of_birth'      => ['nullable', 'string', 'max:255'],
            'date_of_birth'       => ['required', 'date'],
            'gender'              => ['nullable', Rule::in(['L', 'P'])],
            'education'           => ['nullable', 'string', 'max:255'],
            'jabatan'             => ['required', 'string', 'max:255'],
            'jabatan_category_id' => ['required', 'integer', 'exists:jabatan_categories,id'],
            'golongan'            => ['nullable', 'string', 'max:16'],
            'opd_id'              => ['required', 'integer', 'exists:opds,id'],
            'unit_kerja'          => ['nullable', 'string', 'max:255'],
            'appointment_year'    => ['required', 'integer', 'min:1950', 'max:2100'],
            'phone'               => ['nullable', 'string', 'max:32'],
            'email'               => ['nullable', 'email', 'max:255'],
            'status'              => ['nullable', Rule::in([Employee::STATUS_AKTIF, Employee::STATUS_PENSIUN, Employee::STATUS_NONAKTIF])],
        ];
    }
}
