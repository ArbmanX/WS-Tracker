<?php

use App\Enums\WorkflowStage;
use App\Models\Circuit;
use App\Models\CircuitAggregate;
use App\Models\CircuitSnapshot;
use App\Models\CircuitUiState;
use App\Models\Region;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('has a region relationship', function () {
    $region = Region::factory()->create();
    $circuit = Circuit::factory()->forRegion($region)->create();

    expect($circuit->region)->toBeInstanceOf(Region::class)
        ->and($circuit->region->id)->toBe($region->id);
});

it('has a ui state relationship', function () {
    $circuit = Circuit::factory()->create();
    $uiState = CircuitUiState::factory()->create(['circuit_id' => $circuit->id]);

    expect($circuit->uiState)->toBeInstanceOf(CircuitUiState::class)
        ->and($circuit->uiState->id)->toBe($uiState->id);
});

it('has planners through pivot', function () {
    $circuit = Circuit::factory()->create();
    $planner = User::factory()->create();

    $circuit->planners()->attach($planner->id, [
        'assignment_source' => 'manual',
        'assigned_at' => now(),
    ]);

    expect($circuit->planners)->toHaveCount(1)
        ->and($circuit->planners->first()->id)->toBe($planner->id)
        ->and($circuit->planners->first()->pivot->assignment_source)->toBe('manual');
});

it('has children for split assessments', function () {
    $parent = Circuit::factory()->create(['extension' => '@']);
    $childA = Circuit::factory()->create([
        'parent_circuit_id' => $parent->id,
        'extension' => 'A',
        'work_order' => $parent->work_order,
        'region_id' => $parent->region_id,
    ]);
    $childB = Circuit::factory()->create([
        'parent_circuit_id' => $parent->id,
        'extension' => 'B',
        'work_order' => $parent->work_order,
        'region_id' => $parent->region_id,
    ]);

    expect($parent->children)->toHaveCount(2)
        ->and($parent->hasSplits())->toBeTrue()
        ->and($childA->isSplit())->toBeTrue()
        ->and($childA->parent->id)->toBe($parent->id);
});

it('can get or create ui state', function () {
    $circuit = Circuit::factory()->create();

    expect($circuit->uiState)->toBeNull();

    $uiState = $circuit->getOrCreateUiState();

    expect($uiState)->toBeInstanceOf(CircuitUiState::class)
        ->and($uiState->workflow_stage)->toBe(WorkflowStage::Active)
        ->and($circuit->fresh()->uiState)->not->toBeNull();
});

it('has aggregates relationship', function () {
    $circuit = Circuit::factory()->create();

    // Create aggregates with explicit different dates to avoid unique constraint violation
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(1))->create();
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(2))->create();
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(3))->create();

    expect($circuit->aggregates)->toHaveCount(3);
});

it('can get latest aggregate', function () {
    $circuit = Circuit::factory()->create();

    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(5))->create();
    $latest = CircuitAggregate::factory()->forCircuit($circuit)->forDate(now())->create();
    CircuitAggregate::factory()->forCircuit($circuit)->forDate(now()->subDays(3))->create();

    expect($circuit->latestAggregate->id)->toBe($latest->id);
});

it('has snapshots relationship', function () {
    $circuit = Circuit::factory()->create();
    CircuitSnapshot::factory()->count(2)->create(['circuit_id' => $circuit->id]);

    expect($circuit->snapshots)->toHaveCount(2);
});

it('generates display work order correctly', function () {
    $circuit = Circuit::factory()->create([
        'work_order' => '2025-1234',
        'extension' => '@',
    ]);

    expect($circuit->display_work_order)->toBe('2025-1234');

    $split = Circuit::factory()->create([
        'work_order' => '2025-1234',
        'extension' => 'A',
    ]);

    expect($split->display_work_order)->toBe('2025-1234 (A)');
});

it('scopes by api status', function () {
    Circuit::factory()->count(3)->create(['api_status' => 'ACTIV']);
    Circuit::factory()->count(2)->create(['api_status' => 'QC']);

    expect(Circuit::byApiStatus('ACTIV')->count())->toBe(3)
        ->and(Circuit::byApiStatus('QC')->count())->toBe(2);
});

it('scopes by region', function () {
    $region1 = Region::factory()->create();
    $region2 = Region::factory()->create();

    Circuit::factory()->count(3)->forRegion($region1)->create();
    Circuit::factory()->count(2)->forRegion($region2)->create();

    expect(Circuit::inRegion($region1->id)->count())->toBe(3)
        ->and(Circuit::inRegion($region2->id)->count())->toBe(2);
});
