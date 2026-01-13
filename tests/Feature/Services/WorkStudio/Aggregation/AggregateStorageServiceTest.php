<?php

use App\Models\Circuit;
use App\Models\CircuitAggregate;
use App\Models\PlannerDailyAggregate;
use App\Models\Region;
use App\Models\RegionalDailyAggregate;
use App\Models\User;
use App\Services\WorkStudio\Aggregation\AggregateStorageService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AggregateStorageService;
    $this->seed(\Database\Seeders\RegionsSeeder::class);
});

it('stores circuit aggregate', function () {
    $circuit = Circuit::factory()->create();

    $data = [
        'circuit_id' => $circuit->id,
        'aggregate_date' => now()->toDateString(),
        'is_rollup' => false,
        'total_units' => 100,
        'total_linear_ft' => 1500.5,
        'total_acres' => 2.5,
        'total_trees' => 25,
        'units_approved' => 75,
        'units_refused' => 10,
        'units_pending' => 15,
        'unit_counts_by_type' => ['SPM' => 50, 'HCB' => 30],
        'planner_distribution' => ['John Doe' => ['unit_count' => 60]],
    ];

    $aggregate = $this->service->storeCircuitAggregate($data);

    expect($aggregate)->toBeInstanceOf(CircuitAggregate::class)
        ->and($aggregate->circuit_id)->toBe($circuit->id)
        ->and($aggregate->total_units)->toEqual(100)
        ->and($aggregate->total_linear_ft)->toEqual(1500.5)
        ->and($aggregate->unit_counts_by_type)->toBe(['SPM' => 50, 'HCB' => 30]);
});

it('updates existing circuit aggregate for same date', function () {
    $circuit = Circuit::factory()->create();
    $date = now()->toDateString();

    // Store first version
    $this->service->storeCircuitAggregate([
        'circuit_id' => $circuit->id,
        'aggregate_date' => $date,
        'is_rollup' => false,
        'total_units' => 50,
    ]);

    // Update with new data
    $aggregate = $this->service->storeCircuitAggregate([
        'circuit_id' => $circuit->id,
        'aggregate_date' => $date,
        'is_rollup' => false,
        'total_units' => 100,
    ]);

    expect(CircuitAggregate::where('circuit_id', $circuit->id)->count())->toBe(1)
        ->and($aggregate->total_units)->toBe(100);
});

it('stores multiple circuit aggregates in transaction', function () {
    $circuits = Circuit::factory()->count(3)->create();

    $aggregates = collect($circuits)->map(fn ($circuit) => [
        'circuit_id' => $circuit->id,
        'aggregate_date' => now()->toDateString(),
        'is_rollup' => false,
        'total_units' => rand(10, 100),
    ]);

    $count = $this->service->storeCircuitAggregates($aggregates);

    expect($count)->toBe(3)
        ->and(CircuitAggregate::count())->toBe(3);
});

it('stores planner daily aggregate', function () {
    $user = User::factory()->create();
    $region = Region::first();

    $data = [
        'user_id' => $user->id,
        'region_id' => $region->id,
        'aggregate_date' => now()->toDateString(),
        'circuits_worked' => 5,
        'total_units_assessed' => 150,
        'total_linear_ft' => 2500.0,
        'total_acres' => 3.5,
        'units_approved' => 120,
        'units_refused' => 15,
        'units_pending' => 15,
        'unit_counts_by_type' => ['SPM' => 100],
        'circuits_list' => [1, 2, 3, 4, 5],
    ];

    $aggregate = $this->service->storePlannerAggregate($data);

    expect($aggregate)->toBeInstanceOf(PlannerDailyAggregate::class)
        ->and($aggregate->user_id)->toBe($user->id)
        ->and($aggregate->circuits_worked)->toBe(5)
        ->and($aggregate->total_units_assessed)->toBe(150);
});

it('stores regional daily aggregate', function () {
    $region = Region::first();

    $data = [
        'region_id' => $region->id,
        'aggregate_date' => now()->toDateString(),
        'total_circuits' => 50,
        'active_planners' => 10,
        'total_units' => 5000,
        'total_linear_ft' => 75000.0,
        'total_acres' => 150.0,
        'total_trees' => 500,
        'units_approved' => 4000,
        'units_refused' => 500,
        'units_pending' => 500,
        'unit_counts_by_type' => ['SPM' => 3000, 'HCB' => 1000],
        'permission_counts' => ['approved' => 4000, 'refused' => 500],
    ];

    $aggregate = $this->service->storeRegionalAggregate($data);

    expect($aggregate)->toBeInstanceOf(RegionalDailyAggregate::class)
        ->and($aggregate->region_id)->toBe($region->id)
        ->and($aggregate->total_circuits)->toBe(50)
        ->and($aggregate->total_units)->toBe(5000);
});

it('creates circuit rollup from date range', function () {
    $circuit = Circuit::factory()->create();

    // Create several daily aggregates
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(5))->create(['total_units' => 50]);
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(3))->create(['total_units' => 75]);
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now())->create(['total_units' => 100]);

    $rollup = $this->service->createCircuitRollup(
        $circuit->id,
        now()->subDays(5)->toDateString(),
        now()->toDateString()
    );

    expect($rollup->is_rollup)->toBeTrue()
        ->and($rollup->total_units)->toBe(100); // Uses latest values
});

it('prunes old aggregates', function () {
    $circuit = Circuit::factory()->create();
    $user = User::factory()->create();
    $region = Region::first();

    // Create old aggregates
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(400))->create();
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(100))->create();

    PlannerDailyAggregate::create([
        'user_id' => $user->id,
        'region_id' => $region->id,
        'aggregate_date' => now()->subDays(400)->toDateString(),
        'circuits_worked' => 1,
        'total_units_assessed' => 10,
    ]);

    RegionalDailyAggregate::create([
        'region_id' => $region->id,
        'aggregate_date' => now()->subDays(400)->toDateString(),
        'total_circuits' => 1,
        'active_planners' => 1,
    ]);

    $deleted = $this->service->pruneOldAggregates(365);

    expect($deleted['circuit_aggregates'])->toBe(1)
        ->and($deleted['planner_daily_aggregates'])->toBe(1)
        ->and($deleted['regional_daily_aggregates'])->toBe(1)
        ->and(CircuitAggregate::count())->toBe(1); // Only recent one remains
});

it('gets latest aggregate date for circuit', function () {
    $circuit = Circuit::factory()->create();

    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(5))->create();
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(2))->create();
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now())->create();

    $latestDate = $this->service->getLatestAggregateDate($circuit->id);

    expect($latestDate)->toBe(now()->toDateString());
});

it('checks if aggregate exists for date', function () {
    $circuit = Circuit::factory()->create();
    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();

    CircuitAggregate::factory()->forCircuit($circuit)->forDate($today)->create();

    expect($this->service->hasAggregateForDate($circuit->id, $today))->toBeTrue()
        ->and($this->service->hasAggregateForDate($circuit->id, $yesterday))->toBeFalse();
});

it('removes error markers from data before storing', function () {
    $circuit = Circuit::factory()->create();

    $data = [
        'circuit_id' => $circuit->id,
        'aggregate_date' => now()->toDateString(),
        'is_rollup' => false,
        'total_units' => 50,
        '_error' => 'Some error message',
    ];

    $aggregate = $this->service->storeCircuitAggregate($data);

    expect($aggregate->total_units)->toBe(50);
    // The _error key should not cause issues
});
