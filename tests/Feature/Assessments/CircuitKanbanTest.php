<?php

use App\Livewire\Assessments\CircuitKanban;
use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Update analytics settings with current scope year
    AnalyticsSetting::instance()->update([
        'scope_year' => (string) date('Y'),
        'selected_cycle_types' => null,
        'selected_contractors' => null,
    ]);
});

test('unauthenticated users cannot access circuit kanban', function () {
    $this->get(route('assessments.kanban'))
        ->assertRedirect(route('login'));
});

test('authenticated users can access circuit kanban', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $this->actingAs($user)
        ->get(route('assessments.kanban'))
        ->assertOk()
        ->assertSeeLivewire(CircuitKanban::class);
});

test('displays circuits grouped by status in kanban columns', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');

    // Create circuits with different statuses
    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-001",
    ]);
    Circuit::factory()->create([
        'api_status' => 'QC',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-002",
    ]);
    Circuit::factory()->create([
        'api_status' => 'CLOSE',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-003",
    ]);

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->assertSet('statusFilter', ['ACTIV']); // Default filter

    // Only ACTIV by default
    expect($component->get('totalCircuits'))->toBe(1);
});

test('can filter circuits by region', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region1 = Region::factory()->create(['name' => 'Region 1']);
    $region2 = Region::factory()->create(['name' => 'Region 2']);
    $currentYear = date('Y');

    Circuit::factory()->count(2)->create([
        'api_status' => 'ACTIV',
        'region_id' => $region1->id,
        'work_order' => "{$currentYear}-001",
    ]);
    Circuit::factory()->count(3)->create([
        'api_status' => 'ACTIV',
        'region_id' => $region2->id,
        'work_order' => "{$currentYear}-002",
    ]);

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->set('regionFilter', $region1->id);

    $circuitsByStatus = $component->get('circuitsByStatus');
    expect($circuitsByStatus['ACTIV']->count())->toBe(2);
});

test('can filter circuits by planner', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');

    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'taken_by' => 'John Smith',
        'work_order' => "{$currentYear}-001",
    ]);
    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'taken_by' => 'Jane Doe',
        'work_order' => "{$currentYear}-002",
    ]);

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->set('plannerFilter', 'John Smith');

    $circuitsByStatus = $component->get('circuitsByStatus');
    expect($circuitsByStatus['ACTIV']->count())->toBe(1);
    expect($circuitsByStatus['ACTIV']->first()->taken_by)->toBe('John Smith');
});

test('can search circuits by work order', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');

    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-12345",
    ]);
    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-99999",
    ]);

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->set('search', '12345');

    $circuitsByStatus = $component->get('circuitsByStatus');
    expect($circuitsByStatus['ACTIV']->count())->toBe(1);
});

test('can search circuits by title', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');

    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'title' => 'North Region Circuit',
        'work_order' => "{$currentYear}-001",
    ]);
    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'title' => 'South Region Circuit',
        'work_order' => "{$currentYear}-002",
    ]);

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->set('search', 'North');

    $circuitsByStatus = $component->get('circuitsByStatus');
    expect($circuitsByStatus['ACTIV']->count())->toBe(1);
    expect($circuitsByStatus['ACTIV']->first()->title)->toBe('North Region Circuit');
});

test('can view circuit details in modal', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');
    $circuit = Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-001",
        'title' => 'Test Circuit',
    ]);

    Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->assertSet('showDetailModal', false)
        ->call('viewCircuit', $circuit->id)
        ->assertSet('showDetailModal', true)
        ->assertSet('selectedCircuitId', $circuit->id);
});

test('can close detail modal', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');
    $circuit = Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-001",
    ]);

    Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->call('viewCircuit', $circuit->id)
        ->assertSet('showDetailModal', true)
        ->call('closeDetailModal')
        ->assertSet('showDetailModal', false)
        ->assertSet('selectedCircuitId', null);
});

test('can clear all filters', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();

    Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->set('search', 'test')
        ->set('regionFilter', $region->id)
        ->set('plannerFilter', 'John')
        ->call('clearAllFilters')
        ->assertSet('search', '')
        ->assertSet('regionFilter', null)
        ->assertSet('plannerFilter', '')
        ->assertSet('statusFilter', ['ACTIV']); // Default
});

test('hasAnyFilters returns true when filters are active', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->set('search', 'test');

    expect($component->call('hasAnyFilters')->get('hasAnyFilters'))->toBeTrue();
});

test('kanban columns include all configured statuses', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class);

    $columns = $component->get('columns');

    expect($columns)->toHaveKeys(['ACTIV', 'QC', 'REWRK', 'CLOSE']);
    expect($columns['ACTIV']['label'])->toBe('Active');
    expect($columns['QC']['label'])->toBe('Quality Control');
    expect($columns['REWRK']['label'])->toBe('Rework');
    expect($columns['CLOSE']['label'])->toBe('Closed');
});

test('excluded circuits are not shown in kanban', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');

    // Create one excluded and one included circuit
    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'is_excluded' => true,
        'work_order' => "{$currentYear}-001",
    ]);
    Circuit::factory()->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'is_excluded' => false,
        'work_order' => "{$currentYear}-002",
    ]);

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class);

    $circuitsByStatus = $component->get('circuitsByStatus');
    expect($circuitsByStatus['ACTIV']->count())->toBe(1);
});

test('can toggle status filter', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->assertSet('statusFilter', ['ACTIV'])
        ->call('toggleStatus', 'QC')
        ->assertSet('statusFilter', ['ACTIV', 'QC']);
});

test('column counts are accurate', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');

    // Create circuits with different statuses
    Circuit::factory()->count(3)->create([
        'api_status' => 'ACTIV',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-001",
    ]);
    Circuit::factory()->count(2)->create([
        'api_status' => 'QC',
        'region_id' => $region->id,
        'work_order' => "{$currentYear}-002",
    ]);

    // Select all statuses to see counts
    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->call('selectAllStatuses');

    $columnCounts = $component->get('columnCounts');
    expect($columnCounts['ACTIV'])->toBe(3);
    expect($columnCounts['QC'])->toBe(2);
    expect($columnCounts['REWRK'])->toBe(0);
    expect($columnCounts['CLOSE'])->toBe(0);
});

test('url state persists for filters', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();

    Livewire::actingAs($user)
        ->test(CircuitKanban::class)
        ->set('regionFilter', $region->id)
        ->set('plannerFilter', 'John')
        ->set('search', 'test')
        ->assertSet('regionFilter', $region->id)
        ->assertSet('plannerFilter', 'John')
        ->assertSet('search', 'test');
});

test('available planners are fetched from circuits', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');
    $user->markAsOnboarded();

    $region = Region::factory()->create();
    $currentYear = date('Y');

    Circuit::factory()->create([
        'region_id' => $region->id,
        'taken_by' => 'Alice',
        'work_order' => "{$currentYear}-001",
    ]);
    Circuit::factory()->create([
        'region_id' => $region->id,
        'taken_by' => 'Bob',
        'work_order' => "{$currentYear}-002",
    ]);
    Circuit::factory()->create([
        'region_id' => $region->id,
        'taken_by' => null, // Should not appear in planners
        'work_order' => "{$currentYear}-003",
    ]);

    $component = Livewire::actingAs($user)
        ->test(CircuitKanban::class);

    $planners = $component->get('planners');
    expect($planners)->toContain('Alice');
    expect($planners)->toContain('Bob');
    expect($planners)->not->toContain(null);
});
