<?php

use App\Livewire\Assessments\Dashboard\Overview;
use App\Models\Circuit;
use App\Models\Region;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Create a test user for authentication
    $this->user = User::factory()->create();
});

it('renders the overview page', function () {
    $region = Region::factory()->create();
    Circuit::factory()->forRegion($region)->count(3)->create();

    actingAs($this->user)
        ->get(route('assessments.overview'))
        ->assertSuccessful()
        ->assertSeeLivewire(Overview::class);
});

it('displays regional stats computed from circuits', function () {
    $region = Region::factory()->ppl('Central')->create();

    // Create circuits with known values
    Circuit::factory()->forRegion($region)->count(5)->create([
        'api_status' => 'ACTIV',
        'total_miles' => 100,
        'miles_planned' => 50,
        'percent_complete' => 50,
    ]);

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSee('Central')
        ->assertSee('500') // 5 circuits * 100 miles
        ->assertSee('5'); // 5 active circuits
});

it('excludes excluded circuits from stats', function () {
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

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSee('Lehigh')
        ->assertSee('300') // Only 3 * 100 miles (excluded circuits ignored)
        ->assertSee('3'); // Only 3 active circuits counted
});

it('shows correct status breakdown', function () {
    $region = Region::factory()->create();

    // Create circuits with different statuses
    Circuit::factory()->forRegion($region)->count(4)->create(['api_status' => 'ACTIV']);
    Circuit::factory()->forRegion($region)->count(2)->qc()->create();
    Circuit::factory()->forRegion($region)->count(1)->closed()->create();

    // Default filter is ACTIV only, so set all statuses to see full breakdown
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->set('statusFilter', ['ACTIV', 'QC', 'CLOSE', 'REWRK']);

    // Get the computed stats via the component instance
    $stats = $component->instance()->regionStats[$region->id] ?? null;

    expect($stats)->not->toBeNull();
    expect($stats->active_circuits)->toBe(4);
    expect($stats->qc_circuits)->toBe(2);
    expect($stats->closed_circuits)->toBe(1);
    expect($stats->total_circuits)->toBe(7);
});

it('toggles between cards and table view', function () {
    Region::factory()->create();

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSet('viewMode', 'cards')
        ->set('viewMode', 'table')
        ->assertSet('viewMode', 'table')
        ->set('viewMode', 'cards')
        ->assertSet('viewMode', 'cards');
});

it('opens region panel when clicking a region', function () {
    $region = Region::factory()->create();
    Circuit::factory()->forRegion($region)->create();

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSet('panelOpen', false)
        ->assertSet('selectedRegionId', null)
        ->call('openPanel', $region->id)
        ->assertSet('panelOpen', true)
        ->assertSet('selectedRegionId', $region->id)
        ->assertDispatched('open-panel');
});

it('closes region panel', function () {
    $region = Region::factory()->create();

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->call('openPanel', $region->id)
        ->assertSet('panelOpen', true)
        ->call('closePanel')
        ->assertSet('panelOpen', false)
        ->assertSet('selectedRegionId', null)
        ->assertDispatched('close-panel');
});

it('sorts by column', function () {
    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDir', 'asc')
        ->call('sort', 'total_miles')
        ->assertSet('sortBy', 'total_miles')
        ->assertSet('sortDir', 'asc')
        ->call('sort', 'total_miles') // Toggle direction
        ->assertSet('sortBy', 'total_miles')
        ->assertSet('sortDir', 'desc');
});

it('only shows active regions', function () {
    $activeRegion = Region::factory()->create(['name' => 'ActiveRegion', 'is_active' => true]);
    $inactiveRegion = Region::factory()->inactive()->create(['name' => 'InactiveRegion']);

    Circuit::factory()->forRegion($activeRegion)->create();
    Circuit::factory()->forRegion($inactiveRegion)->create();

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSee('ActiveRegion')
        ->assertDontSee('InactiveRegion');
});

it('persists view mode in URL', function () {
    actingAs($this->user)
        ->get(route('assessments.overview', ['viewMode' => 'table']))
        ->assertSuccessful();

    Livewire::actingAs($this->user)
        ->withQueryParams(['viewMode' => 'table'])
        ->test(Overview::class)
        ->assertSet('viewMode', 'table');
});

it('computes correct miles remaining', function () {
    $region = Region::factory()->create();

    Circuit::factory()->forRegion($region)->create([
        'total_miles' => 100,
        'miles_planned' => 30,
    ]);

    $component = Livewire::actingAs($this->user)->test(Overview::class);
    $stats = $component->instance()->regionStats[$region->id] ?? null;

    expect($stats)->not->toBeNull();
    expect($stats->miles_remaining)->toBe(70.0);
});

// Circuit Filter Tests

it('filters circuits by status', function () {
    $region = Region::factory()->create();

    // Create circuits with different statuses
    Circuit::factory()->forRegion($region)->count(3)->create(['api_status' => 'ACTIV', 'total_miles' => 100]);
    Circuit::factory()->forRegion($region)->count(2)->qc()->create(['total_miles' => 100]);
    Circuit::factory()->forRegion($region)->count(1)->closed()->create(['total_miles' => 100]);

    // With only ACTIV filter, should show only 3 circuits
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->set('statusFilter', ['ACTIV']);

    $stats = $component->instance()->regionStats[$region->id] ?? null;

    expect($stats)->not->toBeNull();
    expect($stats->total_circuits)->toBe(3);
    expect($stats->total_miles)->toBe(300.0);
});

it('filters circuits by multiple statuses', function () {
    $region = Region::factory()->create();

    Circuit::factory()->forRegion($region)->count(3)->create(['api_status' => 'ACTIV', 'total_miles' => 100]);
    Circuit::factory()->forRegion($region)->count(2)->qc()->create(['total_miles' => 100]);
    Circuit::factory()->forRegion($region)->count(1)->closed()->create(['total_miles' => 100]);

    // With ACTIV and QC filter, should show 5 circuits
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->set('statusFilter', ['ACTIV', 'QC']);

    $stats = $component->instance()->regionStats[$region->id] ?? null;

    expect($stats)->not->toBeNull();
    expect($stats->total_circuits)->toBe(5);
});

it('filters circuits by cycle type', function () {
    $region = Region::factory()->create();

    Circuit::factory()->forRegion($region)->count(3)->create([
        'api_status' => 'ACTIV',
        'cycle_type' => 'Reactive',
        'total_miles' => 100,
    ]);
    Circuit::factory()->forRegion($region)->count(2)->create([
        'api_status' => 'ACTIV',
        'cycle_type' => 'VM Detection',
        'total_miles' => 100,
    ]);

    // With only Reactive cycle type filter
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->set('cycleTypeFilter', ['Reactive']);

    $stats = $component->instance()->regionStats[$region->id] ?? null;

    expect($stats)->not->toBeNull();
    expect($stats->total_circuits)->toBe(3);
    expect($stats->total_miles)->toBe(300.0);
});

it('combines status and cycle type filters', function () {
    $region = Region::factory()->create();

    // Active Reactive circuits
    Circuit::factory()->forRegion($region)->count(2)->create([
        'api_status' => 'ACTIV',
        'cycle_type' => 'Reactive',
        'total_miles' => 100,
    ]);
    // QC Reactive circuits
    Circuit::factory()->forRegion($region)->count(1)->qc()->create([
        'cycle_type' => 'Reactive',
        'total_miles' => 100,
    ]);
    // Active VM Detection circuits
    Circuit::factory()->forRegion($region)->count(3)->create([
        'api_status' => 'ACTIV',
        'cycle_type' => 'VM Detection',
        'total_miles' => 100,
    ]);

    // Filter: only ACTIV status and Reactive cycle type
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->set('statusFilter', ['ACTIV'])
        ->set('cycleTypeFilter', ['Reactive']);

    $stats = $component->instance()->regionStats[$region->id] ?? null;

    expect($stats)->not->toBeNull();
    expect($stats->total_circuits)->toBe(2);
});

it('toggles status filter', function () {
    // Default is now ACTIV only, so first set multiple statuses to test toggle
    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSet('statusFilter', ['ACTIV']) // New default is ACTIV only
        ->set('statusFilter', ['ACTIV', 'QC', 'CLOSE'])
        ->call('toggleStatus', 'CLOSE')
        ->assertSet('statusFilter', ['ACTIV', 'QC']);
});

it('prevents removing last status filter', function () {
    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->set('statusFilter', ['ACTIV'])
        ->call('toggleStatus', 'ACTIV')
        ->assertSet('statusFilter', ['ACTIV']); // Should still have ACTIV
});

it('toggles cycle type filter', function () {
    $region = Region::factory()->create();
    Circuit::factory()->forRegion($region)->create(['cycle_type' => 'Reactive']);

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSet('cycleTypeFilter', [])
        ->call('toggleCycleType', 'Reactive')
        ->assertSet('cycleTypeFilter', ['Reactive'])
        ->call('toggleCycleType', 'Reactive')
        ->assertSet('cycleTypeFilter', []);
});

it('clears all circuit filters', function () {
    // Clear filters should reset to ACTIV only (the new default)
    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->set('statusFilter', ['ACTIV', 'QC', 'CLOSE'])
        ->set('cycleTypeFilter', ['Reactive'])
        ->call('clearCircuitFilters')
        ->assertSet('statusFilter', ['ACTIV']) // New default is ACTIV only
        ->assertSet('cycleTypeFilter', []);
});

it('persists status filter in URL', function () {
    actingAs($this->user)
        ->get(route('assessments.overview', ['status' => ['ACTIV', 'QC']]))
        ->assertSuccessful();

    Livewire::actingAs($this->user)
        ->withQueryParams(['status' => ['ACTIV', 'QC']])
        ->test(Overview::class)
        ->assertSet('statusFilter', ['ACTIV', 'QC']);
});

it('persists cycle type filter in URL', function () {
    actingAs($this->user)
        ->get(route('assessments.overview', ['cycle' => ['Reactive']]))
        ->assertSuccessful();

    Livewire::actingAs($this->user)
        ->withQueryParams(['cycle' => ['Reactive']])
        ->test(Overview::class)
        ->assertSet('cycleTypeFilter', ['Reactive']);
});

it('detects when circuit filters are active', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class);

    // Default state (ACTIV only) - no active filters from default perspective
    expect($component->instance()->hasActiveCircuitFilters())->toBeFalse();

    // When we add more statuses beyond default, filters are considered active
    $component->set('statusFilter', ['ACTIV', 'QC']);
    expect($component->instance()->hasActiveCircuitFilters())->toBeTrue();

    // Reset to ACTIV only (the default)
    $component->call('clearCircuitFilters');
    expect($component->instance()->hasActiveCircuitFilters())->toBeFalse();

    // Cycle type filter active
    $component->set('cycleTypeFilter', ['Reactive']);
    expect($component->instance()->hasActiveCircuitFilters())->toBeTrue();
});

it('provides available cycle types from database', function () {
    $region = Region::factory()->create();
    Circuit::factory()->forRegion($region)->create(['cycle_type' => 'Reactive']);
    Circuit::factory()->forRegion($region)->create(['cycle_type' => 'VM Detection']);
    Circuit::factory()->forRegion($region)->create(['cycle_type' => 'Reactive']); // Duplicate

    $component = Livewire::actingAs($this->user)->test(Overview::class);
    $cycleTypes = $component->instance()->availableCycleTypes;

    expect($cycleTypes)->toContain('Reactive');
    expect($cycleTypes)->toContain('VM Detection');
    expect($cycleTypes)->toHaveCount(2); // Should be distinct
});
