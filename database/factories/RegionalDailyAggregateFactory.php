<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\RegionalDailyAggregate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RegionalDailyAggregate>
 */
class RegionalDailyAggregateFactory extends Factory
{
    protected $model = RegionalDailyAggregate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalUnits = fake()->numberBetween(50, 300);
        $approved = fake()->numberBetween(0, (int) ($totalUnits * 0.7));
        $refused = fake()->numberBetween(0, (int) ($totalUnits * 0.1));
        $pending = $totalUnits - $approved - $refused;

        $activeCircuits = fake()->numberBetween(5, 20);
        $qcCircuits = fake()->numberBetween(1, 5);
        $closedCircuits = fake()->numberBetween(0, 3);
        $totalCircuits = $activeCircuits + $qcCircuits + $closedCircuits;

        return [
            'region_id' => Region::factory(),
            'aggregate_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'active_circuits' => $activeCircuits,
            'qc_circuits' => $qcCircuits,
            'closed_circuits' => $closedCircuits,
            'total_circuits' => $totalCircuits,
            'total_miles' => fake()->randomFloat(2, 100, 500),
            'miles_planned' => fake()->randomFloat(2, 50, 300),
            'avg_percent_complete' => fake()->randomFloat(2, 20, 80),
            'total_units' => $totalUnits,
            'total_linear_ft' => fake()->randomFloat(2, 5000, 50000),
            'total_acres' => fake()->randomFloat(4, 0, 50),
            'total_trees' => fake()->numberBetween(0, 200),
            'units_approved' => $approved,
            'units_refused' => $refused,
            'units_pending' => $pending,
            'active_planners' => fake()->numberBetween(2, 8),
            'unit_counts_by_type' => [
                'SPM' => fake()->numberBetween(10, 100),
                'HCB' => fake()->numberBetween(5, 50),
            ],
            'status_breakdown' => [
                'ACTIV' => $activeCircuits,
                'QC' => $qcCircuits,
                'CLOSE' => $closedCircuits,
            ],
        ];
    }

    /**
     * Set specific date.
     */
    public function forDate($date): static
    {
        $dateString = $date instanceof \Carbon\Carbon || $date instanceof \DateTime
            ? $date->format('Y-m-d')
            : $date;

        return $this->state(fn (array $attributes) => [
            'aggregate_date' => $dateString,
        ]);
    }

    /**
     * Create for a specific region.
     */
    public function forRegion(Region $region): static
    {
        return $this->state(fn (array $attributes) => [
            'region_id' => $region->id,
        ]);
    }
}
