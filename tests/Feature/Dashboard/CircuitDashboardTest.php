<?php

use App\Enums\WorkflowStage;
use App\Livewire\Dashboard\CircuitDashboard;
use App\Models\Circuit;
use App\Models\CircuitUiState;
use App\Models\PlannerDailyAggregate;
use App\Models\Region;
use App\Models\RegionalDailyAggregate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

describe('CircuitDashboard Rendering', function () {
    test('renders for authenticated user', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSuccessful();
    });

    test('renders with empty data', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Livewire::actingAs($user)
            ->test(CircuitDashboard::class)
            ->assertSuccessful();
    });
});

describe('CircuitDashboard Filters', function () {
    test('filters circuits by region', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $centralRegion = Region::factory()->create(['name' => 'Central']);
        $lancasterRegion = Region::factory()->create(['name' => 'Lancaster']);

        Circuit::factory()->create(['region_id' => $centralRegion->id]);
        Circuit::factory()->create(['region_id' => $lancasterRegion->id]);

        $component = Livewire::actingAs($user)
            ->test(CircuitDashboard::class)
            ->set('regionFilter', 'Central');

        $circuits = $component->get('circuits')->flatten(1);
        expect($circuits)->toHaveCount(1);
        expect($circuits->first()['region']['name'])->toBe('Central');
    });

    test('filters circuits by date range', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $region = Region::factory()->create();
        Circuit::factory()->create([
            'region_id' => $region->id,
            'api_modified_date' => now()->subDays(5),
        ]);
        Circuit::factory()->create([
            'region_id' => $region->id,
            'api_modified_date' => now()->subDays(15),
        ]);

        $component = Livewire::actingAs($user)
            ->test(CircuitDashboard::class)
            ->set('dateFrom', now()->subDays(10)->toDateString());

        $circuits = $component->get('circuits')->flatten(1);
        expect($circuits)->toHaveCount(1);
    });

    test('can clear all filters', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Livewire::actingAs($user)
            ->test(CircuitDashboard::class)
            ->set('regionFilter', 'Central')
            ->set('plannerFilter', 'John')
            ->set('dateFrom', '2024-01-01')
            ->call('clearFilters')
            ->assertSet('regionFilter', null)
            ->assertSet('plannerFilter', null)
            ->assertSet('dateFrom', null);
    });
});

describe('CircuitDashboard Views', function () {
    test('defaults to kanban view', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Livewire::actingAs($user)
            ->test(CircuitDashboard::class)
            ->assertSet('activeView', 'kanban');
    });

    test('can switch to analytics view', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Livewire::actingAs($user)
            ->test(CircuitDashboard::class)
            ->call('setView', 'analytics')
            ->assertSet('activeView', 'analytics');
    });
});

describe('CircuitDashboard Closed Circuits', function () {
    test('hides closed circuits by default', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $region = Region::factory()->create();

        $activeCircuit = Circuit::factory()->create(['region_id' => $region->id]);
        CircuitUiState::factory()->create([
            'circuit_id' => $activeCircuit->id,
            'workflow_stage' => WorkflowStage::Active,
        ]);

        $closedCircuit = Circuit::factory()->create(['region_id' => $region->id]);
        CircuitUiState::factory()->create([
            'circuit_id' => $closedCircuit->id,
            'workflow_stage' => WorkflowStage::Closed,
        ]);

        $component = Livewire::actingAs($user)->test(CircuitDashboard::class);

        $circuits = $component->get('circuits')->flatten(1);
        expect($circuits)->toHaveCount(1);
    });

    test('can toggle showing closed circuits', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $region = Region::factory()->create();

        $activeCircuit = Circuit::factory()->create(['region_id' => $region->id]);
        CircuitUiState::factory()->create([
            'circuit_id' => $activeCircuit->id,
            'workflow_stage' => WorkflowStage::Active,
        ]);

        $closedCircuit = Circuit::factory()->create(['region_id' => $region->id]);
        CircuitUiState::factory()->create([
            'circuit_id' => $closedCircuit->id,
            'workflow_stage' => WorkflowStage::Closed,
        ]);

        Livewire::actingAs($user)
            ->test(CircuitDashboard::class)
            ->assertSet('showClosedCircuits', false)
            ->call('toggleClosedCircuits')
            ->assertSet('showClosedCircuits', true);
    });
});

describe('CircuitDashboard Stats', function () {
    test('computes aggregate stats', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $region = Region::factory()->create();
        Circuit::factory()->count(3)->create([
            'region_id' => $region->id,
            'percent_complete' => 50,
            'miles_planned' => 10,
            'total_miles' => 20,
        ]);

        $component = Livewire::actingAs($user)->test(CircuitDashboard::class);
        $stats = $component->get('stats');

        expect($stats['total_circuits'])->toBe(3);
        expect($stats['total_miles_planned'])->toBe(30.0);
        expect($stats['avg_completion'])->toBe(50.0);
    });
});

describe('CircuitDashboard Data', function () {
    test('returns regions for dropdown', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Region::factory()->count(4)->create(['is_active' => true]);

        $component = Livewire::actingAs($user)->test(CircuitDashboard::class);
        $regions = $component->get('regions');

        expect($regions)->toHaveCount(4);
    });

    test('returns planners who have circuits', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $planner = User::factory()->create();
        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create(['region_id' => $region->id]);
        $circuit->planners()->attach($planner);

        $component = Livewire::actingAs($user)->test(CircuitDashboard::class);
        $planners = $component->get('planners');

        expect($planners)->toHaveCount(1);
        expect($planners->first()['name'])->toBe($planner->name);
    });
});

describe('CircuitDashboard Circuit Move', function () {
    test('handles circuit moved event', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create(['region_id' => $region->id]);
        CircuitUiState::factory()->create(['circuit_id' => $circuit->id]);

        Livewire::actingAs($user)
            ->test(CircuitDashboard::class)
            ->call('handleCircuitMoved', $circuit->id, 'qc', 0)
            ->assertDispatched('notify');
    });
});

describe('CircuitDashboard Analytics Data', function () {
    test('returns regional performance data', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $region = Region::factory()->create(['is_active' => true]);
        RegionalDailyAggregate::factory()->create([
            'region_id' => $region->id,
            'aggregate_date' => now()->toDateString(),
            'miles_planned' => 100,
            'total_miles' => 200,
        ]);

        $component = Livewire::actingAs($user)->test(CircuitDashboard::class);
        $performance = $component->get('regionalPerformance');

        expect($performance)->toHaveCount(1);
        expect($performance->first()['miles_planned'])->toBe(100.0);
    });

    test('returns top planners data', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $planner = User::factory()->create();
        $region = Region::factory()->create();

        // Create aggregate within last 30 days for the topPlanners query
        PlannerDailyAggregate::factory()->create([
            'user_id' => $planner->id,
            'region_id' => $region->id,
            'aggregate_date' => now()->subDays(5)->toDateString(),
            'total_units_assessed' => 50,
            'total_linear_ft' => 1000,
            'circuits_worked' => 3,
        ]);

        $component = Livewire::actingAs($user)->test(CircuitDashboard::class);
        $topPlanners = $component->get('topPlanners');

        expect($topPlanners)->toHaveCount(1);
    });

    test('returns recent activity', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $region = Region::factory()->create();
        Circuit::factory()->count(3)->create(['region_id' => $region->id]);

        $component = Livewire::actingAs($user)->test(CircuitDashboard::class);
        $activity = $component->get('recentActivity');

        expect($activity)->toHaveCount(3);
    });
});
