<?php

namespace Database\Factories;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Models\SyncLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SyncLog>
 */
class SyncLogFactory extends Factory
{
    protected $model = SyncLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-7 days', 'now');
        $duration = fake()->numberBetween(30, 300);

        return [
            'sync_type' => fake()->randomElement(SyncType::cases()),
            'sync_status' => SyncStatus::Completed,
            'sync_trigger' => SyncTrigger::Scheduled,
            'api_status_filter' => fake()->randomElement(['ACTIV', 'QC', 'REWORK', null]),
            'started_at' => $startedAt,
            'completed_at' => (clone $startedAt)->modify("+{$duration} seconds"),
            'duration_seconds' => $duration,
            'circuits_processed' => fake()->numberBetween(10, 100),
            'circuits_created' => fake()->numberBetween(0, 5),
            'circuits_updated' => fake()->numberBetween(5, 50),
            'aggregates_created' => fake()->numberBetween(10, 100),
        ];
    }

    /**
     * Set sync type.
     */
    public function ofType(SyncType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => $type,
        ]);
    }

    /**
     * Create as a circuit list sync.
     */
    public function circuitList(): static
    {
        return $this->ofType(SyncType::CircuitList);
    }

    /**
     * Create as an aggregates sync.
     */
    public function aggregates(): static
    {
        return $this->ofType(SyncType::Aggregates);
    }

    /**
     * Create as a full sync.
     */
    public function full(): static
    {
        return $this->ofType(SyncType::Full);
    }

    /**
     * Mark as failed.
     */
    public function failed(string $message = 'Sync failed'): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => SyncStatus::Failed,
            'error_message' => $message,
        ]);
    }

    /**
     * Mark as manual trigger.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_trigger' => SyncTrigger::Manual,
        ]);
    }

    /**
     * Mark as in progress (started but not completed).
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => SyncStatus::Started,
            'completed_at' => null,
            'duration_seconds' => null,
        ]);
    }
}
