<?php

namespace App\Http\Resources\Payroll;

use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PayrollItem */
class PayrollItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $breakdown = null;
        if ($this->breakdown_json) {
            $decoded = json_decode((string) $this->breakdown_json, true);
            if (is_array($decoded)) {
                $breakdown = $decoded;
            }
        }

        return [
            'id' => $this->id,
            'payroll_run_id' => $this->payroll_run_id,
            'employee_id' => $this->employee_id,
            'gross_amount' => (string) $this->gross_amount,
            'total_allowances' => (string) $this->total_allowances,
            'total_deductions' => (string) $this->total_deductions,
            'net_amount' => (string) $this->net_amount,
            'late_incidents' => (int) ($this->late_incidents ?? 0),
            'late_deduction_days' => (string) ($this->late_deduction_days ?? 0),
            'late_deduction_amount' => (string) ($this->late_deduction_amount ?? 0),
            'breakdown' => $breakdown,
            'payslip_path' => $this->payslip_path,
            'payslip_generated_at' => $this->payslip_generated_at?->toIso8601String(),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'full_name' => $this->employee->full_name,
                'employee_code' => $this->employee->employee_code,
            ]),
            'payroll_run' => $this->whenLoaded('payrollRun', fn () => [
                'id' => $this->payrollRun->id,
                'period_year' => (int) $this->payrollRun->period_year,
                'period_month' => (int) $this->payrollRun->period_month,
                'status' => $this->payrollRun->status,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
