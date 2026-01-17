<?php

namespace Database\Factories;

use App\Models\UnlinkedPlanner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnlinkedPlanner>
 */
class UnlinkedPlannerFactory extends Factory
{
    protected $model = UnlinkedPlanner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'ws_user_guid' => Str::uuid()->toString(),
            'ws_username' => strtolower($firstName.'.'.$lastName),
            'display_name' => $firstName.' '.$lastName,
            'circuit_count' => fake()->numberBetween(5, 50),
            'first_seen_at' => fake()->dateTimeBetween('-30 days', '-7 days'),
            'last_seen_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'linked_to_user_id' => null,
            'linked_at' => null,
        ];
    }

    /**
     * Create a linked planner.
     */
    public function linked(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'linked_to_user_id' => $user?->id ?? User::factory(),
                'linked_at' => now(),
            ];
        });
    }

    /**
     * Create an unlinked planner.
     */
    public function unlinked(): static
    {
        return $this->state(fn (array $attributes) => [
            'linked_to_user_id' => null,
            'linked_at' => null,
        ]);
    }
}
