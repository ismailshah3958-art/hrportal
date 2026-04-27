<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DummyAttendanceSeeder extends Seeder
{
    /**
     * Seeds attendance rows for every existing employee for the last several calendar days.
     * Safe to re-run: uses updateOrCreate on (employee_id, attendance_date).
     */
    public function run(): void
    {
        $employees = Employee::query()->orderBy('id')->get();

        if ($employees->isEmpty()) {
            $this->command?->warn('No employees found — add employees first, then run this seeder again.');

            return;
        }

        $daysBack = 7;

        foreach ($employees as $index => $employee) {
            for ($d = 0; $d < $daysBack; $d++) {
                $date = Carbon::now()->startOfDay()->subDays($d);
                $dateString = $date->toDateString();

                if ($date->isWeekend()) {
                    Attendance::query()->updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'attendance_date' => $dateString,
                        ],
                        [
                            'check_in_at' => null,
                            'check_out_at' => null,
                            'late_minutes' => 0,
                            'early_leave_minutes' => 0,
                            'work_minutes' => null,
                            'status' => 'holiday',
                            'source' => 'manual',
                            'notes' => 'Dummy seed — weekend',
                        ]
                    );

                    continue;
                }

                $startHour = 9;
                $startMinute = 0 + (($index + $d) % 25);
                $late = max(0, $startMinute - 10);

                $checkIn = (clone $date)->setTime($startHour, $startMinute, 0);
                $checkOut = (clone $date)->setTime(17, 30 + ($index % 20), 0);

                if ($checkOut->lt($checkIn)) {
                    $checkOut = (clone $checkIn)->addHours(8);
                }

                $workMinutes = (int) $checkIn->diffInMinutes($checkOut);

                $statusRoll = ($index + $d) % 11;
                $status = match (true) {
                    $statusRoll === 0 => 'remote',
                    $statusRoll === 1 => 'half_day',
                    $statusRoll === 2 => 'on_leave',
                    default => 'present',
                };

                if ($status === 'half_day') {
                    $checkOut = (clone $checkIn)->addHours(4);
                    $workMinutes = (int) $checkIn->diffInMinutes($checkOut);
                }

                if ($status === 'on_leave') {
                    $checkIn = null;
                    $checkOut = null;
                    $workMinutes = null;
                    $late = 0;
                }

                if ($status === 'remote') {
                    $late = min($late, 5);
                }

                Attendance::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'attendance_date' => $dateString,
                    ],
                    [
                        'check_in_at' => $checkIn,
                        'check_out_at' => $checkOut,
                        'late_minutes' => $late,
                        'early_leave_minutes' => ($index + $d) % 3 === 0 ? 15 : 0,
                        'work_minutes' => $workMinutes,
                        'status' => $status,
                        'source' => 'manual',
                        'notes' => $status === 'on_leave' ? 'Dummy seed — on leave' : 'Dummy seed',
                    ]
                );
            }
        }

        $this->command?->info('Dummy attendance seeded for '.$employees->count().' employee(s), last '.$daysBack.' days.');

        Artisan::call('attendance:recalculate-lates');
        $this->command?->info('Late rules applied (shift + 15m grace + payroll incident flags).');
    }
}
