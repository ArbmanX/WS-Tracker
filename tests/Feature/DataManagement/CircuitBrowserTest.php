<?php

use App\Livewire\DataManagement\CircuitBrowser;
use App\Models\Circuit;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-sudo users cannot access circuit browser', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.data.circuits'))
        ->assertForbidden();
});

test('sudo_admin can access circuit browser', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.data.circuits'))
        ->assertSeeLivewire(CircuitBrowser::class);
});

test('displays circuits list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->count(3)->create(['region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->assertViewHas('circuits', fn ($circuits) => $circuits->count() === 3);
});

test('can search circuits by work order', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->create(['work_order' => 'WO-12345', 'region_id' => $region->id]);
    Circuit::factory()->create(['work_order' => 'WO-99999', 'region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->set('search', '12345')
        ->assertViewHas('circuits', fn ($circuits) => $circuits->count() === 1);
});

test('can search circuits by title', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->create(['title' => 'North Region Circuit', 'region_id' => $region->id]);
    Circuit::factory()->create(['title' => 'South Region Circuit', 'region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->set('search', 'North')
        ->assertViewHas('circuits', fn ($circuits) => $circuits->count() === 1);
});

test('can filter circuits by region', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region1 = Region::factory()->create(['name' => 'Region 1']);
    $region2 = Region::factory()->create(['name' => 'Region 2']);
    Circuit::factory()->count(2)->create(['region_id' => $region1->id]);
    Circuit::factory()->count(3)->create(['region_id' => $region2->id]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->set('regionFilter', $region1->id)
        ->assertViewHas('circuits', fn ($circuits) => $circuits->count() === 2);
});

test('can filter circuits by excluded state', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->create(['is_excluded' => true, 'region_id' => $region->id]);
    Circuit::factory()->count(2)->create(['is_excluded' => false, 'region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->set('excludedFilter', 'yes')
        ->assertViewHas('circuits', fn ($circuits) => $circuits->count() === 1);
});

test('can filter circuits by modified state', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->create([
        'user_modified_fields' => ['title' => ['modified_at' => now()]],
        'region_id' => $region->id,
    ]);
    Circuit::factory()->count(2)->create([
        'user_modified_fields' => null,
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->set('modifiedFilter', 'yes')
        ->assertViewHas('circuits', fn ($circuits) => $circuits->count() === 1);
});

test('can view circuit details', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create(['region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->assertSet('showModal', false)
        ->call('viewCircuit', $circuit->id)
        ->assertSet('showModal', true)
        ->assertSet('selectedCircuitId', $circuit->id);
});

test('can start editing a circuit', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create([
        'title' => 'Original Title',
        'total_miles' => 10.5,
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->call('viewCircuit', $circuit->id)
        ->call('startEdit')
        ->assertSet('isEditing', true)
        ->assertSet('editTitle', 'Original Title')
        ->assertSet('editTotalMiles', 10.5);
});

test('edit requires reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create([
        'title' => 'Original Title',
        'total_miles' => 10.5,
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->call('viewCircuit', $circuit->id)
        ->call('startEdit')
        ->set('editTitle', 'New Title')
        ->set('editReason', '') // Empty reason
        ->call('saveEdit')
        ->assertHasErrors(['editReason']);
});

test('editing marks fields as user-modified', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create([
        'title' => 'Original Title',
        'total_miles' => 10.5,
        'user_modified_fields' => null,
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->call('viewCircuit', $circuit->id)
        ->call('startEdit')
        ->set('editTitle', 'New Title')
        ->set('editReason', 'Testing modification tracking')
        ->call('saveEdit')
        ->assertSet('isEditing', false)
        ->assertDispatched('notify');

    $circuit->refresh();
    expect($circuit->title)->toBe('New Title');
    expect($circuit->isFieldUserModified('title'))->toBeTrue();
});

test('can exclude circuit with reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create([
        'is_excluded' => false,
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->call('openExcludeModal', $circuit->id)
        ->assertSet('showExcludeModal', true)
        ->set('excludeReason', 'Testing exclusion feature')
        ->call('excludeCircuit')
        ->assertSet('showExcludeModal', false)
        ->assertDispatched('notify');

    $circuit->refresh();
    expect($circuit->is_excluded)->toBeTrue();
    expect($circuit->exclusion_reason)->toBe('Testing exclusion feature');
    expect($circuit->excluded_by)->toBe($admin->id);
});

test('can include excluded circuit', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create([
        'is_excluded' => true,
        'exclusion_reason' => 'Previously excluded',
        'excluded_by' => $admin->id,
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->call('includeCircuit', $circuit->id)
        ->assertDispatched('notify');

    $circuit->refresh();
    expect($circuit->is_excluded)->toBeFalse();
    expect($circuit->exclusion_reason)->toBeNull();
});

test('can clear all filters', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->set('search', 'test')
        ->set('regionFilter', $region->id)
        ->set('excludedFilter', 'yes')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('regionFilter', null)
        ->assertSet('excludedFilter', '');
});

test('can switch modal tabs', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create(['region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(CircuitBrowser::class)
        ->call('viewCircuit', $circuit->id)
        ->assertSet('activeTab', 'overview')
        ->set('activeTab', 'raw')
        ->assertSet('activeTab', 'raw')
        ->set('activeTab', 'history')
        ->assertSet('activeTab', 'history');
});
