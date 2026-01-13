<?php

namespace Database\Factories;

use App\Models\PlannerWeeklyAggregate;
use App\Models\Region;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlannerWeeklyAggregate>
 */
class PlannerWeeklyAggregateFactory extends Factory
{
    protected $model = PlannerWeeklyAggregate::class;

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

        $totalUnits = fake()->numberBetween(100, 500);
        $approved = fake()->numberBetween(0, (int) ($totalUnits * 0.7));
        $refused = fake()->numberBetween(0, (int) ($totalUnits * 0.1));
        $pending = $totalUnits - $approved - $refused;

        return [
            'user_id' => User::factory(),
            'region_id' => Region::factory(),
            'week_ending' => $weekEnding->format('Y-m-d'),
            'week_starting' => $weekStarting->format('Y-m-d'),
            'days_worked' => fake()->numberBetween(3, 6),
            'circuits_worked' => fake()->numberBetween(3, 15),
            'total_units_assessed' => $totalUnits,
            'total_linear_ft' => fake()->randomFloat(2, 2000, 40000),
            'total_acres' => fake()->randomFloat(4, 0, 20),
            'total_trees' => fake()->numberBetween(0, 150),
            'miles_planned' => fake()->randomFloat(2, 5, 50),
            'units_approved' => $approved,
            'units_refused' => $refused,
            'units_pending' => $pending,
            'unit_counts_by_type' => [
                'SPM' => fake()->numberBetween(20, 100),
                'HCB' => fake()->numberBetween(10, 50),
                'REM612' => fake()->numberBetween(5, 30),
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
