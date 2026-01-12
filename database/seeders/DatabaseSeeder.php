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
        // Seed reference data first
        $this->call([
            UnitTypesSeeder::class,
            // Future seeders:
            // RolesAndPermissionsSeeder::class,
            // RegionsSeeder::class,
            // ApiStatusConfigsSeeder::class,
            // PermissionStatusesSeeder::class,
        ]);

        // Create test user in non-production
        if (app()->environment('local', 'testing')) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
