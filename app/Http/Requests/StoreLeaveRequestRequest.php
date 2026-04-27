<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user?->can('hr.leave.manage')) {
            return true;
        }

        if ($user?->can('ess.leave.apply') && $user->employee) {
            return true;
        }

        return false;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user && ! $user->can('hr.leave.manage') && $user->can('ess.leave.apply')) {
            $eid = $user->employee?->id;
            if ($eid) {
                $this->merge(['employee_id' => $eid]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $hr = $user?->can('hr.leave.manage') ?? false;

        $employeeRules = ['required', 'integer', 'exists:employees,id'];
        if (! $hr && $user?->employee) {
            $employeeRules[] = Rule::in([(int) $user->employee->id]);
        }

        return [
            'employee_id' => $employeeRules,
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
