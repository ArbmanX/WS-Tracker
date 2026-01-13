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
        // Seed reference data first (order matters for dependencies)
        $this->call([
            // Foundation reference data
            RegionsSeeder::class,
            PermissionStatusesSeeder::class,
            ApiStatusConfigsSeeder::class,
            UnitTypesSeeder::class,

            // Roles and permissions (after Spatie tables exist)
            RolesAndPermissionsSeeder::class,
        ]);

        // Create test users in non-production
        if (app()->environment('local', 'testing')) {
            // Create admin user
            $admin = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
            ]);
            $admin->assignRole('admin');

            // Create test planner
            $planner = User::factory()->create([
                'name' => 'Test Planner',
                'email' => 'planner@example.com',
            ]);
            $planner->assignRole('planner');

            // Create sudo admin
            $sudo = User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'sudo@example.com',
            ]);
            $sudo->assignRole('sudo_admin');

            $this->command->info('Test users created: admin@example.com, planner@example.com, sudo@example.com');
        }
    }
}
