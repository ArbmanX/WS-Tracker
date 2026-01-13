<?php

namespace Database\Factories;

use App\Models\Circuit;
use App\Models\CircuitAggregate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CircuitAggregate>
 */
class CircuitAggregateFactory extends Factory
{
    protected $model = CircuitAggregate::class;

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

        $totalMiles = fake()->randomFloat(2, 10, 100);
        $milesPlanned = fake()->randomFloat(2, 0, $totalMiles * 0.8);
        $milesRemaining = $totalMiles - $milesPlanned;

        return [
            'circuit_id' => Circuit::factory(),
            'aggregate_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'is_rollup' => false,
            'total_miles' => $totalMiles,
            'miles_planned' => $milesPlanned,
            'miles_remaining' => $milesRemaining,
            'total_units' => $totalUnits,
            'total_linear_ft' => fake()->randomFloat(2, 1000, 25000),
            'total_acres' => fake()->randomFloat(4, 0, 10),
            'total_trees' => fake()->numberBetween(0, 100),
            'units_approved' => $approved,
            'units_refused' => $refused,
            'units_pending' => $pending,
            'unit_counts_by_type' => [
                'SPM' => fake()->numberBetween(5, 50),
                'HCB' => fake()->numberBetween(2, 20),
                'REM612' => fake()->numberBetween(1, 15),
            ],
            'linear_ft_by_type' => [
                'SPM' => fake()->randomFloat(2, 500, 5000),
                'MPM' => fake()->randomFloat(2, 200, 2000),
            ],
            'acres_by_type' => [
                'HCB' => fake()->randomFloat(4, 0.1, 2),
                'BRUSH' => fake()->randomFloat(4, 0.1, 1),
            ],
        ];
    }

    /**
     * Mark as a rollup record.
     */
    public function rollup(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_rollup' => true,
        ]);
    }

    /**
     * Set specific date.
     */
    public function forDate($date): static
    {
        // Ensure date is formatted as Y-m-d string
        $dateString = $date instanceof \Carbon\Carbon || $date instanceof \DateTime
            ? $date->format('Y-m-d')
            : $date;

        return $this->state(fn (array $attributes) => [
            'aggregate_date' => $dateString,
        ]);
    }

    /**
     * Create for a specific circuit.
     */
    public function forCircuit(Circuit $circuit): static
    {
        return $this->state(fn (array $attributes) => [
            'circuit_id' => $circuit->id,
        ]);
    }
}
