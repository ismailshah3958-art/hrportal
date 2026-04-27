<?php

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Carbon\Carbon;

/**
 * Builds late-attendance figures for a payroll line (attachment + deduction amounts).
 */
class PayrollAttendanceAttachment
{
    /**
     * @return array{
     *     late_incidents:int,
     *     late_deduction_days:float,
     *     late_deduction_amount:float,
     *     daily_rate_used:float,
     *     rule:string,
     *     period_from:string,
     *     period_to:string,
     *     incidents_cap_days:int
     * }
     */
    public function snapshot(Employee $employee, string $periodFrom, string $periodTo): array
    {
        $incidents = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$periodFrom, $periodTo])
            ->where('late_incident', true)
            ->count();

        $per = max(1, (int) config('hr.lates_per_deduction_day', 3));
        $deductionDays = intdiv($incidents, $per);

        $monthly = (float) ($employee->salary ?? 0);
        $divisor = max(1, (int) config('hr.salary_day_divisor', 30));
        $daily = $monthly > 0 ? round($monthly / $divisor, 4) : 0.0;
        $amount = round($deductionDays * $daily, 2);

        return [
            'late_incidents' => $incidents,
            'late_deduction_days' => (float) $deductionDays,
            'late_deduction_amount' => $amount,
            'daily_rate_used' => $daily,
            'rule' => sprintf('%d counted late day(s) = 1 day salary deduction (this period, floor).', $per),
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'incidents_cap_days' => $per,
        ];
    }

    /**
     * Writes late columns + JSON snapshot on a payroll item (for payroll build / finalize).
     */
    public function applyToPayrollItem(PayrollItem $item): void
    {
        $item->loadMissing('employee', 'payrollRun');
        $run = $item->payrollRun;
        $employee = $item->employee;
        if (! $run || ! $employee) {
            return;
        }

        [$from, $to] = $this->resolvePeriodBounds($run);
        $snap = $this->snapshot($employee, $from, $to);

        $item->late_incidents = $snap['late_incidents'];
        $item->late_deduction_days = $snap['late_deduction_days'];
        $item->late_deduction_amount = $snap['late_deduction_amount'];
        $item->attendance_attachment_json = json_encode([
            'source' => 'attendance',
            'snapshot' => $snap,
            'generated_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $item->saveQuietly();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolvePeriodBounds(PayrollRun $run): array
    {
        if ($run->period_start && $run->period_end) {
            return [
                Carbon::parse($run->period_start)->toDateString(),
                Carbon::parse($run->period_end)->toDateString(),
            ];
        }

        $start = Carbon::createFromDate((int) $run->period_year, (int) $run->period_month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }
}
