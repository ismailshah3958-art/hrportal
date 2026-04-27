<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(LeaveTypesSeeder::class);
        $this->call(DepartmentDesignationSeeder::class);
        $this->call(StandardShiftSeeder::class);

        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $user->assignRole('admin');

        $this->call(PortalEmployeeSeeder::class);
    }
}
