<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Carbon\Carbon;

class AttendanceShiftRuleService
{
    /**
     * Applies shift start + grace rules to late_minutes and late_incident (payroll countable day).
     * Only present / remote / half_day with a check-in use shift timing; other statuses clear late.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyLateRules(array &$data, Employee $employee): void
    {
        $status = $data['status'] ?? 'present';

        if (! in_array($status, ['present', 'remote', 'half_day'], true)) {
            $data['late_minutes'] = 0;
            $data['late_incident'] = false;

            return;
        }

        if (empty($data['check_in_at'])) {
            $data['late_minutes'] = (int) ($data['late_minutes'] ?? 0);
            $data['late_incident'] = ($data['late_minutes'] ?? 0) > 0;

            return;
        }

        $shift = $this->resolveShift($employee, $data['attendance_date'] ?? null);
        if (! $shift) {
            $data['late_minutes'] = (int) ($data['late_minutes'] ?? 0);
            $data['late_incident'] = ($data['late_minutes'] ?? 0) > 0;

            return;
        }

        $dateStr = Carbon::parse($data['attendance_date'])->toDateString();
        $startTime = $this->normalizeTimeString($shift->start_time);
        $shiftStart = Carbon::parse($dateStr.' '.$startTime);
        $graceEnd = (clone $shiftStart)->addMinutes((int) $shift->grace_late_minutes);

        $checkIn = Carbon::parse($data['check_in_at']);

        if ($checkIn->lte($graceEnd)) {
            $data['late_minutes'] = 0;
            $data['late_incident'] = false;

            return;
        }

        $data['late_minutes'] = (int) $graceEnd->diffInMinutes($checkIn);
        $data['late_incident'] = $data['late_minutes'] > 0;
    }

    public function recalculateAttendance(Attendance $attendance): void
    {
        $attendance->loadMissing('employee');
        if (! $attendance->employee) {
            return;
        }

        $data = $attendance->only([
            'attendance_date',
            'check_in_at',
            'check_out_at',
            'status',
            'late_minutes',
            'early_leave_minutes',
            'work_minutes',
            'source',
            'notes',
        ]);

        $this->applyLateRules($data, $attendance->employee);

        $attendance->forceFill([
            'late_minutes' => $data['late_minutes'],
            'late_incident' => $data['late_incident'],
        ])->saveQuietly();
    }

    private function resolveShift(Employee $employee, mixed $attendanceDate): ?Shift
    {
        if (! $attendanceDate) {
            return $this->defaultShift();
        }

        $d = Carbon::parse($attendanceDate)->toDateString();

        $assignment = EmployeeShiftAssignment::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $d)
            ->where(function ($q) use ($d) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $d);
            })
            ->orderByDesc('effective_from')
            ->first();

        if ($assignment) {
            return Shift::query()->whereKey($assignment->shift_id)->first();
        }

        return $this->defaultShift();
    }

    private function defaultShift(): ?Shift
    {
        $id = config('hr.default_shift_id');
        if ($id) {
            $s = Shift::query()->whereKey($id)->where('is_active', true)->first();
            if ($s) {
                return $s;
            }
        }

        return Shift::query()->where('is_active', true)->orderBy('id')->first();
    }

    private function normalizeTimeString(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }

        $s = (string) $value;
        if (strlen($s) === 5) {
            return $s.':00';
        }

        return $s;
    }
}
