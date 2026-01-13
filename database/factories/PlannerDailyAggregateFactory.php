<?php

namespace Database\Factories;

use App\Models\PlannerDailyAggregate;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlannerDailyAggregate>
 */
class PlannerDailyAggregateFactory extends Factory
{
    protected $model = PlannerDailyAggregate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalUnits = fake()->numberBetween(20, 150);
        $approved = fake()->numberBetween(0, (int) ($totalUnits * 0.7));
        $refused = fake()->numberBetween(0, (int) ($totalUnits * 0.1));
        $pending = $totalUnits - $approved - $refused;

        return [
            'user_id' => User::factory(),
            'region_id' => Region::factory(),
            'aggregate_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'circuits_worked' => fake()->numberBetween(1, 5),
            'total_units_assessed' => $totalUnits,
            'total_linear_ft' => fake()->randomFloat(2, 500, 10000),
            'total_acres' => fake()->randomFloat(4, 0, 5),
            'total_trees' => fake()->numberBetween(0, 50),
            'units_approved' => $approved,
            'units_refused' => $refused,
            'units_pending' => $pending,
            'unit_counts_by_type' => [
                'SPM' => fake()->numberBetween(5, 30),
                'HCB' => fake()->numberBetween(2, 15),
            ],
        ];
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
     * Create for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
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
