<?php

namespace Database\Factories;

use App\Enums\SnapshotType;
use App\Enums\WorkflowStage;
use App\Models\Circuit;
use App\Models\CircuitSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CircuitSnapshot>
 */
class CircuitSnapshotFactory extends Factory
{
    protected $model = CircuitSnapshot::class;

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

        return [
            'circuit_id' => Circuit::factory(),
            'snapshot_type' => SnapshotType::Daily,
            'snapshot_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'miles_planned' => fake()->randomFloat(2, 2, 15),
            'percent_complete' => fake()->randomFloat(2, 0, 100),
            'api_status' => 'ACTIV',
            'workflow_stage' => WorkflowStage::Active,
            'total_units' => $totalUnits,
            'total_linear_ft' => fake()->randomFloat(2, 1000, 25000),
            'total_acres' => fake()->randomFloat(4, 0, 10),
            'total_trees' => fake()->numberBetween(0, 100),
            'units_approved' => $approved,
            'units_refused' => $refused,
            'units_pending' => $pending,
        ];
    }

    /**
     * Set snapshot type.
     */
    public function ofType(SnapshotType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_type' => $type,
        ]);
    }

    /**
     * Create as daily snapshot.
     */
    public function daily(): static
    {
        return $this->ofType(SnapshotType::Daily);
    }

    /**
     * Create as status change snapshot.
     */
    public function statusChange(): static
    {
        return $this->ofType(SnapshotType::StatusChange);
    }

    /**
     * Create as manual snapshot.
     */
    public function manual(): static
    {
        return $this->ofType(SnapshotType::Manual);
    }

    /**
     * Set specific date.
     */
    public function forDate($date): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_date' => $date,
        ]);
    }
}
