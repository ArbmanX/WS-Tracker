<?php

namespace Database\Seeders;

use App\Models\PermissionStatus;
use Illuminate\Database\Seeder;

class PermissionStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the permission statuses from WorkStudio VEGUNIT_PERMSTAT field.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Pending',
                'code' => '', // Empty string in API
                'color' => 'warning',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Approved',
                'code' => 'Approved',
                'color' => 'success',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Refused',
                'code' => 'Refused',
                'color' => 'error',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'No Contact',
                'code' => 'No Contact',
                'color' => 'info',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Deferred',
                'code' => 'Deferred',
                'color' => 'secondary',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'PPL Approved',
                'code' => 'PPL Approved',
                'color' => 'accent',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'N/A',
                'code' => 'N/A',
                'color' => 'neutral',
                'sort_order' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            PermissionStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }

        $this->command->info('Permission statuses seeded successfully: '.count($statuses).' statuses.');
    }
}
