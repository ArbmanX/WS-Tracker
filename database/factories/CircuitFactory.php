<?php

namespace Database\Factories;

use App\Models\Circuit;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Circuit>
 */
class CircuitFactory extends Factory
{
    protected $model = Circuit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalMiles = fake()->randomFloat(2, 5, 25);
        $milesPlanned = fake()->randomFloat(2, 0, $totalMiles);
        $percentComplete = $totalMiles > 0 ? round(($milesPlanned / $totalMiles) * 100, 2) : 0;

        return [
            'job_guid' => '{'.strtoupper(Str::uuid()).'}',
            'work_order' => date('Y').'-'.fake()->numberBetween(1000, 9999),
            'extension' => '@',
            'region_id' => Region::factory(),
            'title' => strtoupper(fake()->city().' '.fake()->randomElement(['69/12', '138/12', '230/69']).' KV '.
                fake()->numberBetween(1, 30).'-'.fake()->numberBetween(1, 10).' LINE'),
            'contractor' => fake()->randomElement(['Asplundh', 'Penn Line', 'Lewis Tree']),
            'cycle_type' => fake()->randomElement(['4-Year', '5-Year', '6-Year']),
            'total_miles' => $totalMiles,
            'miles_planned' => $milesPlanned,
            'percent_complete' => $percentComplete,
            'total_acres' => fake()->randomFloat(2, 0, 50),
            'start_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'api_modified_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'api_status' => 'ACTIV',
            'api_data_json' => null,
            'last_synced_at' => now(),
            'planned_units_sync_enabled' => true,
            'is_excluded' => false,
            'exclusion_reason' => null,
            'excluded_by' => null,
            'excluded_at' => null,
        ];
    }

    /**
     * Indicate the circuit has a specific API status.
     */
    public function withApiStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'api_status' => $status,
        ]);
    }

    /**
     * Indicate the circuit is in QC status.
     */
    public function qc(): static
    {
        return $this->withApiStatus('QC');
    }

    /**
     * Indicate the circuit is in REWORK status.
     */
    public function rework(): static
    {
        return $this->withApiStatus('REWORK');
    }

    /**
     * Indicate the circuit is closed.
     */
    public function closed(): static
    {
        return $this->withApiStatus('CLOSE')
            ->state(fn (array $attributes) => [
                'percent_complete' => 100.00,
                'miles_planned' => $attributes['total_miles'],
            ]);
    }

    /**
     * Create as a split assessment.
     */
    public function split(string $extension = 'A'): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => $extension,
        ]);
    }

    /**
     * Mark as 100% complete.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'percent_complete' => 100.00,
            'miles_planned' => $attributes['total_miles'],
        ]);
    }

    /**
     * Create with specific region.
     */
    public function forRegion(Region $region): static
    {
        return $this->state(fn (array $attributes) => [
            'region_id' => $region->id,
        ]);
    }

    /**
     * Mark as excluded from reporting.
     */
    public function excluded(?string $reason = 'Test exclusion'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_excluded' => true,
            'exclusion_reason' => $reason,
            'excluded_at' => now(),
        ]);
    }

    /**
     * Create with a specific scope year in the work order.
     */
    public function forScopeYear(string $year): static
    {
        return $this->state(fn (array $attributes) => [
            'work_order' => $year.'-'.fake()->numberBetween(1000, 9999),
        ]);
    }
}
