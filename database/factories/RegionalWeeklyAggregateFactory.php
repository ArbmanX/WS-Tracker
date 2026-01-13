<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\RegionalWeeklyAggregate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RegionalWeeklyAggregate>
 */
class RegionalWeeklyAggregateFactory extends Factory
{
    protected $model = RegionalWeeklyAggregate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random Saturday for week ending
        $weekEnding = Carbon::parse(fake()->dateTimeBetween('-30 days', 'now'))
            ->next(Carbon::SATURDAY);
        $weekStarting = $weekEnding->copy()->subDays(6);

        $totalMiles = fake()->randomFloat(2, 500, 2000);
        $milesPlanned = fake()->randomFloat(2, 100, $totalMiles * 0.8);
        $milesRemaining = $totalMiles - $milesPlanned;

        $totalUnits = fake()->numberBetween(1000, 5000);
        $approved = fake()->numberBetween(0, (int) ($totalUnits * 0.7));
        $refused = fake()->numberBetween(0, (int) ($totalUnits * 0.1));
        $pending = $totalUnits - $approved - $refused;

        $activeCircuits = fake()->numberBetween(20, 80);
        $qcCircuits = fake()->numberBetween(5, 20);
        $closedCircuits = fake()->numberBetween(10, 50);
        $excludedCircuits = fake()->numberBetween(0, 10);

        return [
            'region_id' => Region::factory(),
            'week_ending' => $weekEnding->format('Y-m-d'),
            'week_starting' => $weekStarting->format('Y-m-d'),
            'active_circuits' => $activeCircuits,
            'qc_circuits' => $qcCircuits,
            'closed_circuits' => $closedCircuits,
            'total_circuits' => $activeCircuits + $qcCircuits + $closedCircuits,
            'excluded_circuits' => $excludedCircuits,
            'total_miles' => $totalMiles,
            'miles_planned' => $milesPlanned,
            'miles_remaining' => $milesRemaining,
            'avg_percent_complete' => round(($milesPlanned / $totalMiles) * 100, 2),
            'total_units' => $totalUnits,
            'total_linear_ft' => fake()->randomFloat(2, 50000, 200000),
            'total_acres' => fake()->randomFloat(4, 20, 100),
            'total_trees' => fake()->numberBetween(100, 800),
            'units_approved' => $approved,
            'units_refused' => $refused,
            'units_pending' => $pending,
            'active_planners' => fake()->numberBetween(5, 20),
            'total_planner_days' => fake()->numberBetween(25, 100),
            'unit_counts_by_type' => [
                'SPM' => fake()->numberBetween(200, 1000),
                'HCB' => fake()->numberBetween(100, 500),
                'REM612' => fake()->numberBetween(50, 200),
            ],
            'status_breakdown' => [
                'ACTIV' => $activeCircuits,
                'QC' => $qcCircuits,
                'CLOSE' => $closedCircuits,
            ],
            'daily_breakdown' => null,
        ];
    }

    /**
     * Set specific week ending date.
     */
    public function forWeekEnding(Carbon|string $date): static
    {
        $weekEnding = $date instanceof Carbon ? $date : Carbon::parse($date);
        // Ensure it's a Saturday
        if (! $weekEnding->isSaturday()) {
            $weekEnding = $weekEnding->next(Carbon::SATURDAY);
        }
        $weekStarting = $weekEnding->copy()->subDays(6);

        return $this->state(fn (array $attributes) => [
            'week_ending' => $weekEnding->format('Y-m-d'),
            'week_starting' => $weekStarting->format('Y-m-d'),
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
