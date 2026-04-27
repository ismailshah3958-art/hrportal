<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PortalEmployeeSeeder extends Seeder
{
    /**
     * Demo employee login (ESS): linked employee record for leave / self-service.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();

        $portalUser = User::query()->firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Portal Employee',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $portalUser->syncRoles(['employee']);

        $emp = Employee::query()->where('employee_code', 'EMP-DEMO')->first();
        if (! $emp) {
            $dept = Department::query()->orderBy('id')->first();
            $emp = Employee::create([
                'employee_code' => 'EMP-DEMO',
                'full_name' => 'Portal Employee',
                'work_email' => 'employee@example.com',
                'status' => 'active',
                'employment_type' => 'permanent',
                'department_id' => $dept?->id,
                'created_by_user_id' => $admin?->id ?? $portalUser->id,
            ]);
        }

        Employee::query()->where('user_id', $portalUser->id)->where('id', '!=', $emp->id)->update(['user_id' => null]);
        $emp->update(['user_id' => $portalUser->id]);

        if ($this->command) {
            $this->command->info('Employee portal login: employee@example.com / password');
        }
    }
}
