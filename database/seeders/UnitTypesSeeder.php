<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitTypesSeeder extends Seeder
{
    /**
     * Seed the unit_types table with PPL vegetation management unit types.
     *
     * Categories:
     * - VLG: Vegetation Line/Length (linear feet)
     * - VAR: Variable Area (acres)
     * - VCT: Vegetation Count/Tree (tree count, sized by DBH)
     * - VNW: Vegetation No Work
     * - VSA: Vegetation Sensitive Area
     */
    public function run(): void
    {
        $units = [
            // VLG - Line Trimming Units (linear feet)
            ['code' => 'SPM', 'name' => 'Single Phase - Manual', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 1],
            ['code' => 'SPB', 'name' => 'Single Phase - Bucket', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 2],
            ['code' => 'SPE', 'name' => 'Single Phase - Enhanced', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 3],
            ['code' => 'MPM', 'name' => 'Multi-Phase - Manual', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 4],
            ['code' => 'MPB', 'name' => 'Multi-Phase - Bucket', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 5],
            ['code' => 'MPE', 'name' => 'Multi-Phase - Enhanced', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 6],
            ['code' => 'MPME', 'name' => 'Multi-Phase - Manual Enhanced', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 7],
            ['code' => 'MPBE', 'name' => 'Multi-Phase - Bucket Enhanced', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 8],
            ['code' => 'MST', 'name' => 'Mech Side Trim', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 9],
            ['code' => 'OHT', 'name' => 'Overhang Trim', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 10],
            ['code' => 'SIDET', 'name' => 'Side Trim', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 11],
            ['code' => 'SOHT', 'name' => 'Secondary OH Trim', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 12],
            ['code' => 'STB', 'name' => 'Side Trim - Bucket', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 13],
            ['code' => 'STM', 'name' => 'Side Trim - Manual', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 14],
            ['code' => 'TTS', 'name' => 'Trim To Spec', 'category' => 'VLG', 'measurement_type' => 'linear_ft', 'sort_order' => 15],

            // VAR - Brush/Herbicide Units (acres)
            ['code' => 'HCB', 'name' => 'Hand Cut Brush', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 20],
            ['code' => 'BRUSH', 'name' => 'Hand Cut Brush/Mowing', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 21],
            ['code' => 'BRUSHTRIM', 'name' => 'Hand Cut Brush w Trim', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 22],
            ['code' => 'MOW', 'name' => 'Mowing', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 23],
            ['code' => 'HERBNA', 'name' => 'Herbicide - Non Aquatic', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 24],
            ['code' => 'HERBA', 'name' => 'Herbicide - Aquatic', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 25],
            ['code' => 'HERBS', 'name' => 'Herbicide - Substation', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 26],
            ['code' => 'Herb-Sub', 'name' => 'Herbicide - Substation', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 27],
            ['code' => 'T&M', 'name' => 'Time & Material', 'category' => 'VAR', 'measurement_type' => 'acres', 'sort_order' => 28],

            // VCT - Tree Removal Units (tree count, by DBH)
            ['code' => 'REM612', 'name' => 'Removal 6-12"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 6, 'dbh_max' => 12, 'sort_order' => 30],
            ['code' => 'REM1218', 'name' => 'Removal 12.1-18"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 12.1, 'dbh_max' => 18, 'sort_order' => 31],
            ['code' => 'REM1824', 'name' => 'Removal 18.1-24"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 18.1, 'dbh_max' => 24, 'sort_order' => 32],
            ['code' => 'REM2430', 'name' => 'Removal 24.1-30"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 24.1, 'dbh_max' => 30, 'sort_order' => 33],
            ['code' => 'REM3036', 'name' => 'Removal 30.1-36"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 30.1, 'dbh_max' => 36, 'sort_order' => 34],
            ['code' => 'REM36', 'name' => 'Removal > 36.1"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 36.1, 'dbh_max' => null, 'sort_order' => 35],
            ['code' => 'REM>30', 'name' => 'Removal > 30.1" (new)', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 30.1, 'dbh_max' => null, 'sort_order' => 36],

            // VCT - Ash Tree Removal Units (EAB Program)
            ['code' => 'ASH612', 'name' => 'Ash 6-12"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 6, 'dbh_max' => 12, 'species' => 'ash', 'sort_order' => 40],
            ['code' => 'ASH1218', 'name' => 'Ash 12.1-18"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 12.1, 'dbh_max' => 18, 'species' => 'ash', 'sort_order' => 41],
            ['code' => 'ASH1824', 'name' => 'Ash 18.1-24"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 18.1, 'dbh_max' => 24, 'species' => 'ash', 'sort_order' => 42],
            ['code' => 'ASH2430', 'name' => 'Ash 24.1-30"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 24.1, 'dbh_max' => 30, 'species' => 'ash', 'sort_order' => 43],
            ['code' => 'ASH3036', 'name' => 'Ash 30.1-36"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 30.1, 'dbh_max' => 36, 'species' => 'ash', 'sort_order' => 44],
            ['code' => 'ASH36', 'name' => 'Ash > 36.1"', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'dbh_min' => 36.1, 'dbh_max' => null, 'species' => 'ash', 'sort_order' => 45],

            // VCT - Other Tree Units
            ['code' => 'BTR', 'name' => 'Bucket Trim', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'sort_order' => 50],
            ['code' => 'VPS', 'name' => 'Vine Pole/Structure', 'category' => 'VCT', 'measurement_type' => 'tree_count', 'sort_order' => 51],

            // VNW - No Work Units
            ['code' => 'NW', 'name' => 'No Work', 'category' => 'VNW', 'measurement_type' => 'none', 'sort_order' => 90],
            ['code' => 'NOT', 'name' => 'Notification', 'category' => 'VNW', 'measurement_type' => 'none', 'sort_order' => 91],

            // VSA - Sensitive Area
            ['code' => 'SENSI', 'name' => 'Sensitive Customer', 'category' => 'VSA', 'measurement_type' => 'none', 'sort_order' => 95],
        ];

        $now = now();

        foreach ($units as $unit) {
            DB::table('unit_types')->updateOrInsert(
                ['code' => $unit['code']],
                array_merge($unit, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('Seeded '.count($units).' unit types.');

        // Clear the cache so fresh data is loaded
        \App\Models\UnitType::clearCache();
    }
}
