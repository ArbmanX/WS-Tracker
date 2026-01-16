<?php

namespace Database\Factories;

use App\Enums\SnapshotTrigger;
use App\Models\Circuit;
use App\Models\PlannedUnitsSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlannedUnitsSnapshot>
 */
class PlannedUnitsSnapshotFactory extends Factory
{
    protected $model = PlannedUnitsSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitCount = fake()->numberBetween(10, 200);
        $totalTrees = fake()->numberBetween(0, 50);
        $totalLinearFt = fake()->randomFloat(2, 100, 5000);
        $totalAcres = fake()->randomFloat(4, 0, 10);

        $rawJson = $this->generateSampleJson($unitCount, $totalTrees, $totalLinearFt, $totalAcres);

        return [
            'circuit_id' => Circuit::factory(),
            'work_order' => fn (array $attrs) => Circuit::find($attrs['circuit_id'])?->work_order
                ?? fake()->year().'-'.fake()->numberBetween(1000, 9999),
            'snapshot_trigger' => fake()->randomElement(SnapshotTrigger::cases()),
            'percent_complete' => fake()->randomFloat(2, 0, 100),
            'api_status' => fake()->randomElement(['ACTIV', 'QC', 'REWRK']),
            'content_hash' => hash('sha256', json_encode($rawJson)),
            'unit_count' => $unitCount,
            'total_trees' => $totalTrees,
            'total_linear_ft' => $totalLinearFt,
            'total_acres' => $totalAcres,
            'raw_json' => $rawJson,
            'created_by' => null,
        ];
    }

    /**
     * Generate sample JSON structure.
     */
    protected function generateSampleJson(int $unitCount, int $trees, float $linearFt, float $acres): array
    {
        $units = [];
        $stations = ['10', '20', '30', '110', '120'];
        $types = ['SPM', 'TPM', 'HCB', 'MCB', 'RMV'];
        $permissions = ['Approved', 'Pending', 'Denied', 'Not Required'];

        for ($i = 0; $i < min($unitCount, 10); $i++) {
            $units[] = [
                'id' => '{'.strtoupper(fake()->uuid()).'}',
                'station' => fake()->randomElement($stations),
                'type' => fake()->randomElement($types),
                'desc' => fake()->sentence(3),
                'removal_cat' => fake()->randomElement(['Maintained', 'Non-Maintained']),
                'permission' => fake()->randomElement($permissions),
                'trees' => fake()->numberBetween(0, 5),
                'linear_ft' => fake()->randomFloat(2, 0, 100),
                'acres' => fake()->randomFloat(4, 0, 1),
                'assessed' => fake()->date(),
                'lat' => fake()->latitude(39, 42),
                'lng' => fake()->longitude(-76, -74),
                'notes' => fake()->optional(0.3)->sentence(),
                'parcel_comments' => fake()->optional(0.2)->sentence(),
            ];
        }

        $byPermission = [];
        foreach ($permissions as $p) {
            $byPermission[$p] = fake()->numberBetween(0, $unitCount / 4);
        }

        $byUnitType = [];
        foreach ($types as $t) {
            $byUnitType[$t] = fake()->numberBetween(0, $unitCount / 5);
        }

        return [
            'meta' => [
                'region' => fake()->randomElement(['Lehigh', 'Susquehanna', 'Central', 'Harrisburg']),
                'cycle_type' => fake()->randomElement(['Cycle Maintenance - Trim', 'Hazard Tree']),
                'line_name' => strtoupper(fake()->city().' LINE'),
                'forester' => fake()->name(),
                'contractor' => fake()->randomElement(['Asplundh', 'Penn Line', 'Lewis Tree']),
            ],
            'summary' => [
                'total_units' => $unitCount,
                'total_trees' => $trees,
                'total_linear_ft' => $linearFt,
                'total_acres' => $acres,
                'by_permission' => $byPermission,
                'by_unit_type' => $byUnitType,
            ],
            'units' => $units,
        ];
    }

    /**
     * Create with specific trigger type.
     */
    public function withTrigger(SnapshotTrigger $trigger): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_trigger' => $trigger,
        ]);
    }

    /**
     * Create a manual snapshot.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_trigger' => SnapshotTrigger::Manual,
            'created_by' => User::factory(),
        ]);
    }

    /**
     * Create a 50% milestone snapshot.
     */
    public function milestone50(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_trigger' => SnapshotTrigger::Milestone50,
            'percent_complete' => fake()->randomFloat(2, 50, 60),
        ]);
    }

    /**
     * Create a 100% milestone snapshot.
     */
    public function milestone100(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_trigger' => SnapshotTrigger::Milestone100,
            'percent_complete' => 100.00,
        ]);
    }

    /**
     * Create a QC status change snapshot.
     */
    public function statusToQc(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_trigger' => SnapshotTrigger::StatusToQc,
            'api_status' => 'QC',
        ]);
    }

    /**
     * Create for a specific circuit.
     */
    public function forCircuit(Circuit $circuit): static
    {
        return $this->state(fn (array $attributes) => [
            'circuit_id' => $circuit->id,
            'work_order' => $circuit->work_order,
            'api_status' => $circuit->api_status,
            'percent_complete' => $circuit->percent_complete,
        ]);
    }
}
