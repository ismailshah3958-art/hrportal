<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Casual Leave', 'code' => 'casual', 'default_days_per_year' => 10, 'requires_approval' => true, 'is_paid' => true, 'can_carry_forward' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sick Leave', 'code' => 'sick', 'default_days_per_year' => 8, 'requires_approval' => true, 'is_paid' => true, 'can_carry_forward' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Annual Leave', 'code' => 'annual', 'default_days_per_year' => 14, 'requires_approval' => true, 'is_paid' => true, 'can_carry_forward' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Unpaid Leave', 'code' => 'unpaid', 'default_days_per_year' => 0, 'requires_approval' => true, 'is_paid' => false, 'can_carry_forward' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($rows as $row) {
            DB::table('leave_types')->updateOrInsert(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
