<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DepartmentDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $it = Department::query()->updateOrCreate(
            ['code' => 'IT'],
            ['name' => 'IT', 'description' => 'Information Technology', 'is_active' => true]
        );
        $hr = Department::query()->updateOrCreate(
            ['code' => 'HR'],
            ['name' => 'HR', 'description' => 'Human Resources', 'is_active' => true]
        );
        $fin = Department::query()->updateOrCreate(
            ['code' => 'FIN'],
            ['name' => 'Finance', 'description' => 'Finance & Accounts', 'is_active' => true]
        );

        $defs = [
            ['department_id' => $it->id, 'name' => 'Software Engineer', 'level' => 30],
            ['department_id' => $it->id, 'name' => 'Senior Software Engineer', 'level' => 40],
            ['department_id' => $it->id, 'name' => 'Engineering Manager', 'level' => 50],
            ['department_id' => $hr->id, 'name' => 'HR Executive', 'level' => 30],
            ['department_id' => $hr->id, 'name' => 'HR Manager', 'level' => 50],
            ['department_id' => $fin->id, 'name' => 'Accountant', 'level' => 30],
            ['department_id' => null, 'name' => 'Intern', 'level' => 10],
        ];

        foreach ($defs as $row) {
            Designation::query()->updateOrCreate(
                [
                    'department_id' => $row['department_id'],
                    'name' => $row['name'],
                ],
                [
                    'level' => $row['level'],
                    'is_active' => true,
                ]
            );
        }
    }
}
