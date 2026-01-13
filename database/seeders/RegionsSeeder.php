<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the 4 PPL Electric Utilities regions.
     */
    public function run(): void
    {
        $regions = [
            [
                'name' => 'Central',
                'code' => 'CENTRAL',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Lancaster',
                'code' => 'LANCASTER',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Lehigh',
                'code' => 'LEHIGH',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Harrisburg',
                'code' => 'HARRISBURG',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['code' => $region['code']],
                $region
            );
        }

        $this->command->info('Regions seeded successfully: '.count($regions).' regions.');
    }
}
