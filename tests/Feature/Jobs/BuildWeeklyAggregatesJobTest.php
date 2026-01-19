<?php

use App\Jobs\BuildWeeklyAggregatesJob;
use App\Models\Circuit;
use App\Models\CircuitAggregate;
use App\Models\PlannerWeeklyAggregate;
use App\Models\Region;
use App\Models\RegionalWeeklyAggregate;
use App\Models\User;

// RefreshDatabase trait handles database cleanup automatically

it('builds regional aggregates for active regions', function () {
    $region = Region::factory()->ppl('Central')->create();

    // Create circuits with known values
    Circuit::factory()->forRegion($region)->count(3)->create([
        'api_status' => 'ACTIV',
        'total_miles' => 100,
        'miles_planned' => 50,
    ]);

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job->handle();

    expect(RegionalWeeklyAggregate::count())->toBe(1);

    $aggregate = RegionalWeeklyAggregate::first();
    expect($aggregate->region_id)->toBe($region->id);
    expect($aggregate->active_circuits)->toBe(3);
    expect((float) $aggregate->total_miles)->toBe(300.0);
    expect((float) $aggregate->miles_planned)->toBe(150.0);
});

it('excludes excluded circuits from regional stats', function () {
    $region = Region::factory()->ppl('Lehigh')->create();

    // Create 3 normal circuits
    Circuit::factory()->forRegion($region)->count(3)->create([
        'api_status' => 'ACTIV',
        'total_miles' => 100,
        'is_excluded' => false,
    ]);

    // Create 2 excluded circuits (should NOT be counted)
    Circuit::factory()->forRegion($region)->count(2)->excluded()->create([
        'api_status' => 'ACTIV',
        'total_miles' => 100,
    ]);

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job->handle();

    $aggregate = RegionalWeeklyAggregate::first();
    expect($aggregate->active_circuits)->toBe(3);
    expect((float) $aggregate->total_miles)->toBe(300.0);
    expect($aggregate->excluded_circuits)->toBe(2);
});

it('tracks status breakdown in regional aggregates', function () {
    $region = Region::factory()->create();

    Circuit::factory()->forRegion($region)->count(4)->create(['api_status' => 'ACTIV']);
    Circuit::factory()->forRegion($region)->count(2)->qc()->create();
    Circuit::factory()->forRegion($region)->count(1)->closed()->create();

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job->handle();

    $aggregate = RegionalWeeklyAggregate::first();
    expect($aggregate->active_circuits)->toBe(4);
    expect($aggregate->qc_circuits)->toBe(2);
    expect($aggregate->closed_circuits)->toBe(1);
    expect($aggregate->total_circuits)->toBe(7);
    expect($aggregate->status_breakdown)->toBe([
        'ACTIV' => 4,
        'QC' => 2,
        'CLOSE' => 1,
        'REWRK' => 0,
    ]);
});

it('skips inactive regions', function () {
    $activeRegion = Region::factory()->create(['is_active' => true]);
    $inactiveRegion = Region::factory()->inactive()->create();

    Circuit::factory()->forRegion($activeRegion)->create();
    Circuit::factory()->forRegion($inactiveRegion)->create();

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job->handle();

    expect(RegionalWeeklyAggregate::count())->toBe(1);
    expect(RegionalWeeklyAggregate::first()->region_id)->toBe($activeRegion->id);
});

it('builds planner aggregates when includePlanners is true', function () {
    $region = Region::factory()->create();
    $planner = User::factory()->create();

    // Create circuit and assign planner
    $circuit = Circuit::factory()->forRegion($region)->create([
        'total_miles' => 100,
        'miles_planned' => 75,
    ]);

    $circuit->planners()->attach($planner->id, [
        'assignment_source' => 'api',
        'assigned_at' => now(),
    ]);

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, true);
    $job->handle();

    expect(PlannerWeeklyAggregate::count())->toBe(1);

    $aggregate = PlannerWeeklyAggregate::first();
    expect($aggregate->user_id)->toBe($planner->id);
    expect($aggregate->region_id)->toBe($region->id);
    expect($aggregate->circuits_worked)->toBe(1);
    expect((float) $aggregate->miles_planned)->toBe(75.0);
});

it('excludes excluded circuits from planner stats', function () {
    $region = Region::factory()->create();
    $planner = User::factory()->create();

    // Create normal circuit and assign planner
    $normalCircuit = Circuit::factory()->forRegion($region)->create([
        'total_miles' => 100,
        'miles_planned' => 50,
        'is_excluded' => false,
    ]);
    $normalCircuit->planners()->attach($planner->id, [
        'assignment_source' => 'api',
        'assigned_at' => now(),
    ]);

    // Create excluded circuit and assign same planner
    $excludedCircuit = Circuit::factory()->forRegion($region)->excluded()->create([
        'total_miles' => 200,
        'miles_planned' => 100,
    ]);
    $excludedCircuit->planners()->attach($planner->id, [
        'assignment_source' => 'api',
        'assigned_at' => now(),
    ]);

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, true);
    $job->handle();

    $aggregate = PlannerWeeklyAggregate::first();
    expect($aggregate->circuits_worked)->toBe(1); // Only the non-excluded circuit
    expect((float) $aggregate->miles_planned)->toBe(50.0); // Only from non-excluded
});

it('does not build planner aggregates when includePlanners is false', function () {
    $region = Region::factory()->create();
    $planner = User::factory()->create();

    $circuit = Circuit::factory()->forRegion($region)->create();
    $circuit->planners()->attach($planner->id, [
        'assignment_source' => 'api',
        'assigned_at' => now(),
    ]);

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job->handle();

    expect(PlannerWeeklyAggregate::count())->toBe(0);
    expect(RegionalWeeklyAggregate::count())->toBe(1);
});

it('uses updateOrCreate to be idempotent', function () {
    $region = Region::factory()->create();
    Circuit::factory()->forRegion($region)->count(3)->create([
        'api_status' => 'ACTIV',
        'total_miles' => 100,
    ]);

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    // Run the job twice
    $job1 = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job1->handle();

    $job2 = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job2->handle();

    // Should still only have one aggregate for this week
    expect(RegionalWeeklyAggregate::count())->toBe(1);
});

it('accepts string date for weekEnding', function () {
    $region = Region::factory()->create();
    Circuit::factory()->forRegion($region)->create();

    $weekEnding = '2025-01-18'; // A Saturday

    $job = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job->handle();

    $aggregate = RegionalWeeklyAggregate::first();
    expect($aggregate->week_ending->toDateString())->toBe('2025-01-18');
});

it('defaults to current week when weekEnding is null', function () {
    $region = Region::factory()->create();
    Circuit::factory()->forRegion($region)->create();

    $job = new BuildWeeklyAggregatesJob(null, false);
    $job->handle();

    $expectedWeekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());
    $aggregate = RegionalWeeklyAggregate::first();
    expect($aggregate->week_ending->toDateString())->toBe($expectedWeekEnding->toDateString());
});

it('includes unit stats from circuit_aggregates', function () {
    $region = Region::factory()->create();

    $circuit = Circuit::factory()->forRegion($region)->create();

    // Create a circuit aggregate with unit data
    CircuitAggregate::create([
        'circuit_id' => $circuit->id,
        'aggregate_date' => now(),
        'is_rollup' => false,
        'total_units' => 100,
        'units_approved' => 80,
        'units_refused' => 10,
        'units_pending' => 10,
        'total_trees' => 50,
        'total_linear_ft' => 1000.5,
        'total_acres' => 25.5,
    ]);

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job->handle();

    $aggregate = RegionalWeeklyAggregate::first();
    expect($aggregate->total_units)->toBe(100);
    expect($aggregate->units_approved)->toBe(80);
    expect($aggregate->units_refused)->toBe(10);
    expect($aggregate->units_pending)->toBe(10);
    expect($aggregate->total_trees)->toBe(50);
    expect((float) $aggregate->total_linear_ft)->toBe(1000.5);
});

it('counts active planners per region', function () {
    $region = Region::factory()->create();
    $planner1 = User::factory()->create();
    $planner2 = User::factory()->create();

    $circuit1 = Circuit::factory()->forRegion($region)->create();
    $circuit2 = Circuit::factory()->forRegion($region)->create();

    // Assign different planners to different circuits
    $circuit1->planners()->attach($planner1->id, [
        'assignment_source' => 'api',
        'assigned_at' => now(),
    ]);
    $circuit2->planners()->attach($planner2->id, [
        'assignment_source' => 'api',
        'assigned_at' => now(),
    ]);

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    $job = new BuildWeeklyAggregatesJob($weekEnding, false);
    $job->handle();

    $aggregate = RegionalWeeklyAggregate::first();
    expect($aggregate->active_planners)->toBe(2);
});

it('implements ShouldQueue interface', function () {
    $job = new BuildWeeklyAggregatesJob;

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('has appropriate job tags', function () {
    $job = new BuildWeeklyAggregatesJob;

    expect($job->tags())->toBe(['aggregates', 'weekly']);
});

it('has proper retry and timeout settings', function () {
    $job = new BuildWeeklyAggregatesJob;

    expect($job->tries)->toBe(3);
    expect($job->timeout)->toBe(600);
});
