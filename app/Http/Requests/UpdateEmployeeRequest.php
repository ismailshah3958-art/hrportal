<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.employees.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['manager_id', 'department_id', 'designation_id', 'zk_badge_user_id'] as $key) {
            if (! $this->has($key)) {
                continue;
            }
            if ($this->input($key) === '' || $this->input($key) === null) {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \App\Models\Employee $employee */
        $employee = $this->route('employee');

        return [
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employee->id)],
            'full_name' => ['required', 'string', 'max:255'],
            'work_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'cnic' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id', Rule::notIn([$employee->id])],
            'joining_date' => ['nullable', 'date'],
            'exit_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:40'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', 'string', Rule::in(['active', 'on_leave', 'resigned', 'terminated'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employee->id)],
            'zk_badge_user_id' => ['nullable', 'integer', 'min:1', 'max:2147483647', Rule::unique('employees', 'zk_badge_user_id')->ignore($employee->id)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
        ];
    }
}
