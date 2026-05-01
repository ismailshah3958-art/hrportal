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
                'personal_email' => null,
                'phone' => '+923001234567',
                'whatsapp_phone' => '+923001234567',
                'cnic' => '35202-1234567-1',
                'date_of_birth' => '1990-01-15',
                'address_line1' => 'Demo address line',
                'emergency_contact_name' => 'Emergency Contact',
                'emergency_contact_phone' => '+923009876543',
                'emergency_contact_relation' => 'Spouse',
                'bank_name' => 'Demo Bank',
                'bank_branch' => 'Main Branch',
                'bank_account_title' => 'Portal Employee',
                'bank_account_number' => '0123456789012345',
                'bank_iban' => 'PK36SCBL0000001123456702',
                'status' => 'active',
                'employment_type' => 'permanent',
                'department_id' => $dept?->id,
                'created_by_user_id' => $admin?->id ?? $portalUser->id,
            ]);
        } elseif ($emp->bank_iban === null || $emp->bank_iban === '') {
            $emp->update([
                'work_email' => $emp->work_email ?: 'employee@example.com',
                'phone' => $emp->phone ?: '+923001234567',
                'whatsapp_phone' => $emp->whatsapp_phone ?: ($emp->phone ?: '+923001234567'),
                'cnic' => $emp->cnic ?: '35202-1234567-1',
                'date_of_birth' => $emp->date_of_birth ?: '1990-01-15',
                'address_line1' => $emp->address_line1 ?: 'Demo address line',
                'emergency_contact_name' => $emp->emergency_contact_name ?: 'Emergency Contact',
                'emergency_contact_phone' => $emp->emergency_contact_phone ?: '+923009876543',
                'emergency_contact_relation' => $emp->emergency_contact_relation ?: 'Spouse',
                'bank_name' => $emp->bank_name ?: 'Demo Bank',
                'bank_branch' => $emp->bank_branch ?: 'Main Branch',
                'bank_account_title' => $emp->bank_account_title ?: 'Portal Employee',
                'bank_account_number' => $emp->bank_account_number ?: '0123456789012345',
                'bank_iban' => 'PK36SCBL0000001123456702',
            ]);
        }

        Employee::query()->where('user_id', $portalUser->id)->where('id', '!=', $emp->id)->update(['user_id' => null]);
        $emp->update(['user_id' => $portalUser->id]);

        if ($this->command) {
            $this->command->info('Employee portal login: employee@example.com / password');
        }
    }
}
