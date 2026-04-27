<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.attendance.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'attendance_date' => ['required', 'date'],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date'],
            'late_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'early_leave_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'work_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'status' => ['required', 'string', Rule::in(['present', 'absent', 'half_day', 'on_leave', 'remote', 'holiday'])],
            'source' => ['nullable', 'string', Rule::in(['manual', 'biometric', 'import', 'api'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
