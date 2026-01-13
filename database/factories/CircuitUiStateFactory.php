<?php

namespace Database\Factories;

use App\Enums\WorkflowStage;
use App\Models\Circuit;
use App\Models\CircuitUiState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CircuitUiState>
 */
class CircuitUiStateFactory extends Factory
{
    protected $model = CircuitUiState::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'circuit_id' => Circuit::factory(),
            'workflow_stage' => WorkflowStage::Active,
            'stage_position' => fake()->numberBetween(0, 100),
            'stage_changed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'is_hidden' => false,
            'is_pinned' => false,
        ];
    }

    /**
     * Set a specific workflow stage.
     */
    public function inStage(WorkflowStage $stage): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_stage' => $stage,
        ]);
    }

    /**
     * Set as pending permissions stage.
     */
    public function pendingPermissions(): static
    {
        return $this->inStage(WorkflowStage::PendingPermissions);
    }

    /**
     * Set as QC stage.
     */
    public function qc(): static
    {
        return $this->inStage(WorkflowStage::Qc);
    }

    /**
     * Set as rework stage.
     */
    public function rework(): static
    {
        return $this->inStage(WorkflowStage::Rework);
    }

    /**
     * Set as closed stage.
     */
    public function closed(): static
    {
        return $this->inStage(WorkflowStage::Closed);
    }

    /**
     * Mark as hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }

    /**
     * Mark as pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }
}
