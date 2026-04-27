<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'hr.dashboard.view',
            'hr.employees.manage',
            'hr.departments.manage',
            'hr.designations.manage',
            'hr.shifts.manage',
            'hr.attendance.manage',
            'hr.leave.manage',
            'hr.payroll.manage',
            'hr.recruitment.manage',
            'hr.announcements.manage',
            'hr.documents.manage',
            'hr.performance.manage',
            'hr.assets.manage',
            'hr.training.manage',
            'hr.exit.manage',
            'hr.reports.view',
            'ess.profile.view',
            'ess.leave.apply',
            'ess.attendance.view',
            'ess.payslip.view',
            'ess.timesheet.submit',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $hr = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        $admin->syncPermissions(Permission::all());

        $hr->syncPermissions([
            'hr.dashboard.view',
            'hr.employees.manage',
            'hr.departments.manage',
            'hr.designations.manage',
            'hr.shifts.manage',
            'hr.attendance.manage',
            'hr.leave.manage',
            'hr.payroll.manage',
            'hr.recruitment.manage',
            'hr.announcements.manage',
            'hr.documents.manage',
            'hr.performance.manage',
            'hr.assets.manage',
            'hr.training.manage',
            'hr.exit.manage',
            'hr.reports.view',
        ]);

        $employee->syncPermissions([
            'ess.profile.view',
            'ess.leave.apply',
            'ess.attendance.view',
            'ess.payslip.view',
            'ess.timesheet.submit',
        ]);
    }
}
