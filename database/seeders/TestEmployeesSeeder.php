<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TestEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first()
            ?? User::query()->first();

        if (! $admin) {
            $this->command?->warn('No user found — run DatabaseSeeder first.');

            return;
        }

        $it = Department::query()->where('code', 'IT')->first();
        $hr = Department::query()->where('code', 'HR')->first();
        $fin = Department::query()->where('code', 'FIN')->first();

        $dev = Designation::query()->where('name', 'Software Engineer')->first();
        $srDev = Designation::query()->where('name', 'Senior Software Engineer')->first();
        $hrExec = Designation::query()->where('name', 'HR Executive')->first();
        $acc = Designation::query()->where('name', 'Accountant')->first();
        $intern = Designation::query()->where('name', 'Intern')->whereNull('department_id')->first();

        $rows = [
            [
                'employee_code' => 'TEST-EMP-001',
                'full_name' => 'Ahmed Khan',
                'work_email' => 'ahmed.khan@maestro.test',
                'phone' => '0300-1112233',
                'cnic' => '35202-1234567-1',
                'department_id' => $it?->id,
                'designation_id' => $dev?->id,
                'joining_date' => '2024-01-15',
                'employment_type' => 'permanent',
                'status' => 'active',
                'salary' => 185000.00,
                'city' => 'Karachi',
            ],
            [
                'employee_code' => 'TEST-EMP-002',
                'full_name' => 'Sara Malik',
                'work_email' => 'sara.malik@maestro.test',
                'phone' => '0300-2223344',
                'cnic' => '35202-2345678-2',
                'department_id' => $it?->id,
                'designation_id' => $srDev?->id,
                'joining_date' => '2023-06-01',
                'employment_type' => 'permanent',
                'status' => 'active',
                'salary' => 320000.00,
                'city' => 'Lahore',
            ],
            [
                'employee_code' => 'TEST-EMP-003',
                'full_name' => 'Bilal Hussain',
                'work_email' => 'bilal.hussain@maestro.test',
                'phone' => '0300-3334455',
                'cnic' => '35202-3456789-3',
                'department_id' => $hr?->id,
                'designation_id' => $hrExec?->id,
                'joining_date' => '2024-03-10',
                'employment_type' => 'permanent',
                'status' => 'active',
                'salary' => 145000.00,
                'city' => 'Islamabad',
            ],
            [
                'employee_code' => 'TEST-EMP-004',
                'full_name' => 'Fatima Noor',
                'work_email' => 'fatima.noor@maestro.test',
                'phone' => '0300-4445566',
                'cnic' => '35202-4567890-4',
                'department_id' => $fin?->id,
                'designation_id' => $acc?->id,
                'joining_date' => '2022-11-20',
                'employment_type' => 'permanent',
                'status' => 'on_leave',
                'salary' => 165000.00,
                'city' => 'Karachi',
            ],
            [
                'employee_code' => 'TEST-EMP-005',
                'full_name' => 'Hassan Ali',
                'work_email' => 'hassan.ali@maestro.test',
                'phone' => '0300-5556677',
                'cnic' => '35202-5678901-5',
                'department_id' => $it?->id,
                'designation_id' => $intern?->id,
                'joining_date' => '2025-02-01',
                'employment_type' => 'intern',
                'status' => 'active',
                'salary' => 45000.00,
                'city' => 'Remote',
            ],
        ];

        $managerId = null;
        foreach ($rows as $row) {
            $employee = Employee::query()->updateOrCreate(
                ['employee_code' => $row['employee_code']],
                array_merge($row, [
                    'created_by_user_id' => $admin->id,
                    'manager_id' => $managerId,
                ])
            );
            if ($managerId === null) {
                $managerId = $employee->id;
            }
        }

        $this->seedProfilePlaceholders(array_column($rows, 'employee_code'));
    }

    /**
     * Writes a local PNG per test employee under storage/app/public so list avatars resolve via APP_URL + /storage/.
     */
    private function seedProfilePlaceholders(array $codes): void
    {
        $palette = [
            [15, 118, 110],
            [59, 130, 246],
            [217, 119, 6],
            [239, 68, 68],
            [139, 92, 246],
        ];

        $i = 0;
        foreach ($codes as $code) {
            $employee = Employee::query()->where('employee_code', $code)->first();
            if (! $employee) {
                continue;
            }

            $path = 'employees/profile-photos/seed-'.$code.'.png';

            if (function_exists('imagecreatetruecolor') && function_exists('imagepng')) {
                $im = imagecreatetruecolor(128, 128);
                $rgb = $palette[$i % count($palette)];
                $fill = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
                imagefill($im, 0, 0, $fill);
                ob_start();
                imagepng($im);
                $binary = ob_get_clean();
                imagedestroy($im);
            } else {
                $binary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
            }

            Storage::disk('public')->put($path, $binary);
            $employee->update(['profile_photo_path' => $path]);
            $i++;
        }
    }
}
