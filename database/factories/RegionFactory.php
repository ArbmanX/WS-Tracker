<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 10)),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the region is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create as one of the PPL regions.
     */
    public function ppl(string $name): static
    {
        $regions = [
            'Central' => ['code' => 'CENTRAL', 'sort_order' => 1],
            'Lancaster' => ['code' => 'LANCASTER', 'sort_order' => 2],
            'Lehigh' => ['code' => 'LEHIGH', 'sort_order' => 3],
            'Harrisburg' => ['code' => 'HARRISBURG', 'sort_order' => 4],
        ];

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'code' => $regions[$name]['code'],
            'sort_order' => $regions[$name]['sort_order'],
        ]);
    }
}
