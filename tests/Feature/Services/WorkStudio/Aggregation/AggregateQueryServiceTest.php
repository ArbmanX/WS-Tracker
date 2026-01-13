<?php

use App\Models\Circuit;
use App\Models\CircuitAggregate;
use App\Models\PlannerDailyAggregate;
use App\Models\Region;
use App\Models\RegionalDailyAggregate;
use App\Models\User;
use App\Services\WorkStudio\Aggregation\AggregateQueryService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AggregateQueryService;
    $this->seed(\Database\Seeders\RegionsSeeder::class);
});

it('gets global summary across all regions', function () {
    $regions = Region::all();

    foreach ($regions as $region) {
        RegionalDailyAggregate::create([
            'region_id' => $region->id,
            'aggregate_date' => now()->toDateString(),
            'total_circuits' => 10,
            'active_planners' => 3,
            'total_units' => 500,
            'total_linear_ft' => 5000.0,
            'total_acres' => 10.0,
            'total_trees' => 50,
            'units_approved' => 400,
            'units_refused' => 50,
            'units_pending' => 50,
            'unit_counts_by_type' => ['SPM' => 300, 'HCB' => 100],
        ]);
    }

    $summary = $this->service->getGlobalSummary();

    expect($summary['total_circuits'])->toBe(40) // 10 * 4 regions
        ->and($summary['active_planners'])->toBe(12) // 3 * 4 regions
        ->and($summary['total_units'])->toBe(2000) // 500 * 4
        ->and($summary['regions_count'])->toBe(4);
});

it('gets regional summary for specific region', function () {
    $region = Region::first();

    RegionalDailyAggregate::create([
        'region_id' => $region->id,
        'aggregate_date' => now()->toDateString(),
        'total_circuits' => 25,
        'active_planners' => 5,
        'total_units' => 1000,
        'total_linear_ft' => 15000.0,
    ]);

    $summary = $this->service->getRegionalSummary($region->id);

    expect($summary['region_id'])->toBe($region->id)
        ->and($summary['total_circuits'])->toBe(25)
        ->and($summary['total_units'])->toBe(1000);
});

it('returns null for non-existent regional summary', function () {
    $summary = $this->service->getRegionalSummary(999);

    expect($summary)->toBeNull();
});

it('gets all regions summary with region details', function () {
    $regions = Region::take(2)->get();

    foreach ($regions as $index => $region) {
        RegionalDailyAggregate::create([
            'region_id' => $region->id,
            'aggregate_date' => now()->toDateString(),
            'total_circuits' => 10 + ($index * 5),
            'active_planners' => 2 + $index,
            'total_units' => 100 * ($index + 1),
        ]);
    }

    $allSummaries = $this->service->getAllRegionsSummary();

    expect($allSummaries)->toHaveCount(2)
        ->and($allSummaries[0])->toHaveKey('region_name')
        ->and($allSummaries[0])->toHaveKey('region_code');
});

it('gets circuit aggregate by id', function () {
    $circuit = Circuit::factory()->create();

    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(2))->create([
        'total_units' => 50,
    ]);
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now())->create([
        'total_units' => 100,
    ]);

    $aggregate = $this->service->getCircuitAggregate($circuit->id);

    expect($aggregate->total_units)->toBe(100); // Latest
});

it('gets circuit aggregate for specific date', function () {
    $circuit = Circuit::factory()->create();
    $targetDate = now()->subDays(5);

    CircuitAggregate::factory()->forCircuit($circuit)->forDate($targetDate)->create([
        'total_units' => 75,
    ]);
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now())->create([
        'total_units' => 100,
    ]);

    $aggregate = $this->service->getCircuitAggregate($circuit->id, $targetDate->toDateString());

    expect($aggregate->total_units)->toBe(75);
});

it('gets circuits by region', function () {
    $region = Region::first();

    $circuit1 = Circuit::factory()->forRegion($region)->create();
    $circuit2 = Circuit::factory()->forRegion($region)->create();

    CircuitAggregate::factory()->forCircuit($circuit1)->forDate(now())->create(['total_units' => 50]);
    CircuitAggregate::factory()->forCircuit($circuit2)->forDate(now())->create(['total_units' => 75]);

    $circuits = $this->service->getCircuitsByRegion($region->id);

    expect($circuits)->toHaveCount(2);
});

it('gets planner productivity for date range', function () {
    $user = User::factory()->create();
    $region = Region::first();

    // Create 5 days of productivity data
    for ($i = 0; $i < 5; $i++) {
        PlannerDailyAggregate::create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'aggregate_date' => now()->subDays($i)->toDateString(),
            'circuits_worked' => 2,
            'total_units_assessed' => 20,
            'total_linear_ft' => 200.0,
            'circuits_list' => [$i + 1],
        ]);
    }

    $productivity = $this->service->getPlannerProductivity(
        $user->id,
        now()->subDays(10)->toDateString(),
        now()->toDateString()
    );

    expect($productivity['user_id'])->toBe($user->id)
        ->and($productivity['days_worked'])->toBe(5)
        ->and($productivity['totals']['total_units'])->toBe(100) // 20 * 5
        ->and($productivity['daily'])->toHaveCount(5);
});

it('gets circuit time series', function () {
    $circuit = Circuit::factory()->create();

    // Create aggregates for multiple days
    for ($i = 0; $i < 7; $i++) {
        CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays($i))->create([
            'total_units' => 50 + ($i * 10),
        ]);
    }

    $timeSeries = $this->service->getCircuitTimeSeries(
        $circuit->id,
        now()->subDays(6)->toDateString(),
        now()->toDateString()
    );

    expect($timeSeries)->toHaveCount(7)
        ->and($timeSeries[0])->toHaveKey('date')
        ->and($timeSeries[0])->toHaveKey('total_units');
});

it('gets permission breakdown by region', function () {
    $regions = Region::take(2)->get();

    foreach ($regions as $region) {
        RegionalDailyAggregate::create([
            'region_id' => $region->id,
            'aggregate_date' => now()->toDateString(),
            'total_circuits' => 10,
            'active_planners' => 2,
            'units_approved' => 800,
            'units_refused' => 100,
            'units_pending' => 100,
        ]);
    }

    $breakdown = $this->service->getPermissionBreakdownByRegion();

    expect($breakdown)->toHaveCount(4) // All 4 regions
        ->and($breakdown->first())->toHaveKey('approved')
        ->and($breakdown->first())->toHaveKey('refused')
        ->and($breakdown->first())->toHaveKey('approved_percent');
});

it('gets top planners by productivity', function () {
    $users = User::factory()->count(5)->create();
    $region = Region::first();

    foreach ($users as $index => $user) {
        PlannerDailyAggregate::create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'aggregate_date' => now()->toDateString(),
            'circuits_worked' => 1,
            'total_units_assessed' => ($index + 1) * 100, // 100, 200, 300, 400, 500
        ]);
    }

    $topPlanners = $this->service->getTopPlanners(
        now()->subDay()->toDateString(),
        now()->toDateString(),
        3
    );

    expect($topPlanners)->toHaveCount(3)
        ->and($topPlanners->first()['total_units'])->toBe(500); // Highest first
});

it('merges unit counts by type correctly', function () {
    $regions = Region::take(2)->get();

    RegionalDailyAggregate::create([
        'region_id' => $regions[0]->id,
        'aggregate_date' => now()->toDateString(),
        'total_circuits' => 1,
        'active_planners' => 1,
        'unit_counts_by_type' => ['SPM' => 100, 'HCB' => 50],
    ]);

    RegionalDailyAggregate::create([
        'region_id' => $regions[1]->id,
        'aggregate_date' => now()->toDateString(),
        'total_circuits' => 1,
        'active_planners' => 1,
        'unit_counts_by_type' => ['SPM' => 150, 'MPM' => 25],
    ]);

    $summary = $this->service->getGlobalSummary();

    expect($summary['unit_counts_by_type']['SPM'])->toBe(250)
        ->and($summary['unit_counts_by_type']['HCB'])->toBe(50)
        ->and($summary['unit_counts_by_type']['MPM'])->toBe(25);
});

it('handles empty date ranges gracefully', function () {
    $user = User::factory()->create();

    $productivity = $this->service->getPlannerProductivity(
        $user->id,
        now()->subDay()->toDateString(),
        now()->toDateString()
    );

    expect($productivity['days_worked'])->toBe(0)
        ->and($productivity['daily'])->toBeEmpty();
});
