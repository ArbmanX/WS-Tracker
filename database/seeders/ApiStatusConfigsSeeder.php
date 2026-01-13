<?php

namespace Database\Seeders;

use App\Models\ApiStatusConfig;
use Illuminate\Database\Seeder;

class ApiStatusConfigsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the WorkStudio API status configurations.
     */
    public function run(): void
    {
        $configs = [
            [
                'api_status' => 'ACTIV',
                'display_name' => 'Active',
                'sync_frequency' => 'daily',
                'sync_planned_units' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'api_status' => 'QC',
                'display_name' => 'Quality Control',
                'sync_frequency' => 'weekly',
                'sync_planned_units' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'api_status' => 'REWORK',
                'display_name' => 'Rework',
                'sync_frequency' => 'weekly',
                'sync_planned_units' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'api_status' => 'CLOSE',
                'display_name' => 'Closed',
                'sync_frequency' => 'manual',
                'sync_planned_units' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($configs as $config) {
            ApiStatusConfig::updateOrCreate(
                ['api_status' => $config['api_status']],
                $config
            );
        }

        $this->command->info('API status configs seeded successfully: '.count($configs).' configs.');
    }
}
