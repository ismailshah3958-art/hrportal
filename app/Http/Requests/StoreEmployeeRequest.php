<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.employees.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('zk_badge_user_id') && ($this->input('zk_badge_user_id') === '' || $this->input('zk_badge_user_id') === null)) {
            $this->merge(['zk_badge_user_id' => null]);
        }

        if ($this->has('create_portal_login')) {
            $raw = $this->input('create_portal_login');
            $on = in_array($raw, [true, 1, '1', 'true', 'on'], true);
            $this->merge(['create_portal_login' => $on]);
        } else {
            $this->merge(['create_portal_login' => false]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->boolean('create_portal_login') && $this->filled('user_id')) {
                $v->errors()->add('user_id', 'Remove linked user or turn off “Create portal login”.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')],
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
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:40'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', 'string', Rule::in(['active', 'on_leave', 'resigned', 'terminated'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')],
            'zk_badge_user_id' => ['nullable', 'integer', 'min:1', 'max:2147483647', Rule::unique('employees', 'zk_badge_user_id')],
            'create_portal_login' => ['sometimes', 'boolean'],
            'portal_email' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_login')),
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'portal_password' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_login')),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'portal_role' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_login')),
                'nullable',
                'string',
                Rule::in(['employee', 'hr', 'admin']),
            ],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
        ];
    }
}
