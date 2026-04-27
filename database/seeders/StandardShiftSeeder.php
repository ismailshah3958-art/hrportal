<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class StandardShiftSeeder extends Seeder
{
    /**
     * Default 9-hour office shift (09:00–18:00) with 15 minutes late grace.
     * Assigns all employees from 2020-01-01 onward unless they already have that row.
     */
    public function run(): void
    {
        $shift = Shift::query()->updateOrCreate(
            ['name' => 'Standard 9h (15m grace)'],
            [
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'grace_late_minutes' => 15,
                'break_minutes' => 0,
                'is_night_shift' => false,
                'is_active' => true,
            ]
        );

        foreach (Employee::query()->cursor() as $employee) {
            EmployeeShiftAssignment::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'effective_from' => '2020-01-01',
                ],
                [
                    'shift_id' => $shift->id,
                    'effective_to' => null,
                ]
            );
        }

        $this->command?->info('Standard shift ID '.$shift->id.' — optional: HR_DEFAULT_SHIFT_ID='.$shift->id.' in .env');
    }
}
