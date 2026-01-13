<?php

use App\Models\Circuit;
use App\Models\CircuitAggregate;
use App\Services\WorkStudio\Aggregation\AggregateDiffService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AggregateDiffService;
    $this->seed(\Database\Seeders\RegionsSeeder::class);
});

it('detects initial sync when no existing aggregate', function () {
    $newData = [
        'total_units' => 100,
        'total_linear_ft' => 1500,
        'units_approved' => 75,
    ];

    $result = $this->service->compare($newData, null);

    expect($result['has_changes'])->toBeTrue()
        ->and($result['changes']['initial_sync'])->toBeTrue()
        ->and($result['delta']['total_units'])->toBe(100);
});

it('detects no changes when values are same', function () {
    $circuit = Circuit::factory()->create();
    $existing = CircuitAggregate::factory()->forCircuit($circuit)->create([
        'total_units' => 100,
        'total_linear_ft' => 1500.0,
        'total_acres' => 2.5,
        'total_trees' => 25,
        'units_approved' => 75,
        'units_refused' => 10,
        'units_pending' => 15,
        'unit_counts_by_type' => ['SPM' => 50],
    ]);

    $newData = [
        'total_units' => 100,
        'total_linear_ft' => 1500.0,
        'total_acres' => 2.5,
        'total_trees' => 25,
        'units_approved' => 75,
        'units_refused' => 10,
        'units_pending' => 15,
        'unit_counts_by_type' => ['SPM' => 50],
    ];

    $result = $this->service->compare($newData, $existing);

    expect($result['has_changes'])->toBeFalse()
        ->and($result['changes'])->toBeEmpty();
});

it('detects significant numeric changes', function () {
    $circuit = Circuit::factory()->create();
    $existing = CircuitAggregate::factory()->forCircuit($circuit)->create([
        'total_units' => 100,
        'total_linear_ft' => 1500.0,
    ]);

    $newData = [
        'total_units' => 110, // 10% increase
        'total_linear_ft' => 1600.0, // 6.7% increase
    ];

    $result = $this->service->compare($newData, $existing);

    expect($result['has_changes'])->toBeTrue()
        ->and($result['changes'])->toHaveKey('total_units')
        ->and($result['changes'])->toHaveKey('total_linear_ft')
        ->and($result['delta']['total_units'])->toBe(10)
        ->and($result['delta']['total_linear_ft'])->toBe(100.0);
});

it('ignores insignificant changes below threshold', function () {
    $circuit = Circuit::factory()->create();
    $existing = CircuitAggregate::factory()->forCircuit($circuit)->create([
        'total_units' => 100,
        'total_linear_ft' => 10000.0,
        'total_acres' => 2.5,
        'total_trees' => 25,
        'units_approved' => 75,
        'units_refused' => 10,
        'units_pending' => 15,
        'unit_counts_by_type' => ['SPM' => 50],
        'planner_distribution' => [],
    ]);

    $newData = [
        'total_units' => 100,
        'total_linear_ft' => 10050.0, // 0.5% change, below 1% threshold
        'total_acres' => 2.5,
        'total_trees' => 25,
        'units_approved' => 75,
        'units_refused' => 10,
        'units_pending' => 15,
        'unit_counts_by_type' => ['SPM' => 50],
        'planner_distribution' => [],
    ];

    $result = $this->service->compare($newData, $existing);

    expect($result['has_changes'])->toBeFalse();
});

it('detects changes from zero', function () {
    $circuit = Circuit::factory()->create();
    $existing = CircuitAggregate::factory()->forCircuit($circuit)->create([
        'total_trees' => 0,
    ]);

    $newData = [
        'total_trees' => 5,
    ];

    $result = $this->service->compare($newData, $existing);

    expect($result['has_changes'])->toBeTrue()
        ->and($result['changes'])->toHaveKey('total_trees');
});

it('detects changes to zero', function () {
    $circuit = Circuit::factory()->create();
    $existing = CircuitAggregate::factory()->forCircuit($circuit)->create([
        'total_trees' => 10,
    ]);

    $newData = [
        'total_trees' => 0,
    ];

    $result = $this->service->compare($newData, $existing);

    expect($result['has_changes'])->toBeTrue()
        ->and($result['delta']['total_trees'])->toBe(-10);
});

it('detects jsonb field changes', function () {
    $circuit = Circuit::factory()->create();
    $existing = CircuitAggregate::factory()->forCircuit($circuit)->create([
        'unit_counts_by_type' => ['SPM' => 50, 'HCB' => 30],
    ]);

    $newData = [
        'total_units' => $existing->total_units,
        'unit_counts_by_type' => ['SPM' => 60, 'HCB' => 30], // SPM changed
    ];

    $result = $this->service->compare($newData, $existing);

    expect($result['has_changes'])->toBeTrue()
        ->and($result['changes']['jsonb_fields'])->toHaveKey('unit_counts_by_type');
});

it('calculates progress delta between dates', function () {
    $circuit = Circuit::factory()->create();

    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(10))->create([
        'total_units' => 50,
        'total_linear_ft' => 500,
    ]);

    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now())->create([
        'total_units' => 100,
        'total_linear_ft' => 1000,
    ]);

    $result = $this->service->calculateProgressDelta(
        $circuit->id,
        now()->subDays(10)->toDateString(),
        now()->toDateString()
    );

    expect($result['circuit_id'])->toBe($circuit->id)
        ->and($result['delta']['total_units']['from'])->toBe(50)
        ->and($result['delta']['total_units']['to'])->toBe(100)
        ->and($result['delta']['total_units']['change'])->toBe(50)
        ->and($result['delta']['total_units']['percent_change'])->toBe(100.0);
});

it('returns error when missing aggregate data for delta', function () {
    $circuit = Circuit::factory()->create();

    $result = $this->service->calculateProgressDelta(
        $circuit->id,
        now()->subDays(10)->toDateString(),
        now()->toDateString()
    );

    expect($result)->toHaveKey('error');
});

it('gets circuits with changes since date', function () {
    $circuit1 = Circuit::factory()->create();
    $circuit2 = Circuit::factory()->create();

    // Common JSONB values to ensure consistent comparison
    $jsonbData = [
        'unit_counts_by_type' => ['SPM' => 50],
        'planner_distribution' => [],
    ];

    // Circuit 1: Had changes (50 -> 100 units, which is > 1%)
    CircuitAggregate::factory()->forCircuit($circuit1)->forDate(now()->subDays(5))->create([
        'total_units' => 50,
        'total_linear_ft' => 1000.0,
        'total_acres' => 1.0,
        'total_trees' => 10,
        'units_approved' => 40,
        'units_refused' => 5,
        'units_pending' => 5,
        ...$jsonbData,
    ]);
    CircuitAggregate::factory()->forCircuit($circuit1)->forDate(now())->create([
        'total_units' => 100, // Significant change
        'total_linear_ft' => 1000.0,
        'total_acres' => 1.0,
        'total_trees' => 10,
        'units_approved' => 40,
        'units_refused' => 5,
        'units_pending' => 5,
        ...$jsonbData,
    ]);

    // Circuit 2: No changes (same values)
    CircuitAggregate::factory()->forCircuit($circuit2)->forDate(now()->subDays(5))->create([
        'total_units' => 75,
        'total_linear_ft' => 1500.0,
        'total_acres' => 2.0,
        'total_trees' => 20,
        'units_approved' => 60,
        'units_refused' => 8,
        'units_pending' => 7,
        ...$jsonbData,
    ]);
    CircuitAggregate::factory()->forCircuit($circuit2)->forDate(now())->create([
        'total_units' => 75, // Same as before
        'total_linear_ft' => 1500.0,
        'total_acres' => 2.0,
        'total_trees' => 20,
        'units_approved' => 60,
        'units_refused' => 8,
        'units_pending' => 7,
        ...$jsonbData,
    ]);

    $changedCircuits = $this->service->getCircuitsWithChanges(now()->subDays(5)->toDateString());

    expect($changedCircuits)->toHaveCount(1)
        ->and($changedCircuits->first()['circuit_id'])->toBe($circuit1->id)
        ->and($changedCircuits->first()['has_changes'])->toBeTrue();
});
