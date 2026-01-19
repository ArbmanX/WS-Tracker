<?php

use App\Livewire\Admin\PlannerManagement;
use App\Models\Region;
use App\Models\UnlinkedPlanner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('planner management page renders for admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.planners'))
        ->assertSuccessful()
        ->assertSeeLivewire(PlannerManagement::class);
});

test('planner management page is forbidden for regular users', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');

    $this->actingAs($user)
        ->get(route('admin.planners'))
        ->assertForbidden();
});

test('displays linked planners with planner role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create(['name' => 'Test Planner']);
    $planner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->assertSee('Test Planner');
});

test('displays unlinked planners', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    UnlinkedPlanner::factory()->create([
        'display_name' => 'Unlinked Test Planner',
        'linked_to_user_id' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->assertSee('Unlinked Test Planner');
});

test('stats display correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create planners
    $planner1 = User::factory()->create();
    $planner1->assignRole('planner');

    $planner2 = User::factory()->create();
    $planner2->assignRole('planner');

    UnlinkedPlanner::factory()->create(['linked_to_user_id' => null]);

    $component = Livewire::actingAs($admin)->test(PlannerManagement::class);
    $stats = $component->get('stats');

    expect($stats['total_linked'])->toBe(2);
    expect($stats['total_unlinked'])->toBe(1);
});

test('can filter by link status - linked only', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create(['name' => 'John LinkedUser']);
    $planner->assignRole('planner');

    UnlinkedPlanner::factory()->create([
        'display_name' => 'Sarah UnlinkedPerson',
        'linked_to_user_id' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->set('filter', 'linked')
        ->assertSee('John LinkedUser')
        ->assertDontSee('Sarah UnlinkedPerson');
});

test('can filter by link status - unlinked only', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create(['name' => 'John LinkedUser']);
    $planner->assignRole('planner');

    UnlinkedPlanner::factory()->create([
        'display_name' => 'Sarah UnlinkedPerson',
        'linked_to_user_id' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->set('filter', 'unlinked')
        ->assertDontSee('John LinkedUser')
        ->assertSee('Sarah UnlinkedPerson');
});

test('can search planners by name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner1 = User::factory()->create(['name' => 'John Smith']);
    $planner1->assignRole('planner');

    $planner2 = User::factory()->create(['name' => 'Jane Doe']);
    $planner2->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->set('search', 'John')
        ->assertSee('John Smith')
        ->assertDontSee('Jane Doe');
});

test('admin can exclude a planner', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create(['name' => 'Test Planner']);
    $planner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->call('startExcluding', $planner->id, 'user')
        ->set('exclusionReason', 'Test account - not real planner')
        ->call('excludePlanner')
        ->assertDispatched('notify');

    $planner->refresh();
    expect($planner->is_excluded_from_analytics)->toBeTrue();
    expect($planner->exclusion_reason)->toBe('Test account - not real planner');
    expect($planner->excluded_by)->toBe($admin->id);
});

test('admin can include an excluded planner', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create([
        'name' => 'Excluded Planner',
        'is_excluded_from_analytics' => true,
        'exclusion_reason' => 'Was excluded',
    ]);
    $planner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->set('exclusionFilter', 'excluded')
        ->call('includePlanner', $planner->id, 'user')
        ->assertDispatched('notify');

    $planner->refresh();
    expect($planner->is_excluded_from_analytics)->toBeFalse();
    expect($planner->exclusion_reason)->toBeNull();
});

test('can filter by exclusion status - active only', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activePlanner = User::factory()->create([
        'name' => 'Active Planner',
        'is_excluded_from_analytics' => false,
    ]);
    $activePlanner->assignRole('planner');

    $excludedPlanner = User::factory()->create([
        'name' => 'Excluded Planner',
        'is_excluded_from_analytics' => true,
        'exclusion_reason' => 'Test',
    ]);
    $excludedPlanner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->set('exclusionFilter', 'active')
        ->assertSee('Active Planner')
        ->assertDontSee('Excluded Planner');
});

test('can filter by exclusion status - excluded only', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activePlanner = User::factory()->create([
        'name' => 'Active Planner',
        'is_excluded_from_analytics' => false,
    ]);
    $activePlanner->assignRole('planner');

    $excludedPlanner = User::factory()->create([
        'name' => 'Excluded Planner',
        'is_excluded_from_analytics' => true,
        'exclusion_reason' => 'Test',
    ]);
    $excludedPlanner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->set('exclusionFilter', 'excluded')
        ->assertDontSee('Active Planner')
        ->assertSee('Excluded Planner');
});

test('admin can edit linked planner name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create(['name' => 'Original Name']);
    $planner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->call('startEditing', $planner->id, 'user')
        ->assertSet('editName', 'Original Name')
        ->set('editName', 'Updated Name')
        ->call('saveName')
        ->assertDispatched('notify');

    $planner->refresh();
    expect($planner->name)->toBe('Updated Name');
});

test('admin can edit unlinked planner display name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = UnlinkedPlanner::factory()->create([
        'display_name' => 'Original Display',
        'linked_to_user_id' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->call('startEditing', $planner->id, 'unlinked')
        ->set('editName', 'Updated Display')
        ->call('saveName')
        ->assertDispatched('notify');

    $planner->refresh();
    expect($planner->display_name)->toBe('Updated Display');
});

test('exclusion requires reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create();
    $planner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->call('startExcluding', $planner->id, 'user')
        ->call('excludePlanner')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'reason');
        });

    $planner->refresh();
    expect($planner->is_excluded_from_analytics)->toBeFalse();
});

test('can exclude unlinked planner', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = UnlinkedPlanner::factory()->create([
        'display_name' => 'Unlinked Planner',
        'linked_to_user_id' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->call('startExcluding', $planner->id, 'unlinked')
        ->set('exclusionReason', 'No longer active')
        ->call('excludePlanner')
        ->assertDispatched('notify');

    $planner->refresh();
    expect($planner->is_excluded_from_analytics)->toBeTrue();
});

test('can filter by region', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $region1 = Region::factory()->create(['name' => 'Region One']);
    $region2 = Region::factory()->create(['name' => 'Region Two']);

    $planner1 = User::factory()->create(['name' => 'Planner One']);
    $planner1->assignRole('planner');
    $planner1->regions()->attach($region1);

    $planner2 = User::factory()->create(['name' => 'Planner Two']);
    $planner2->assignRole('planner');
    $planner2->regions()->attach($region2);

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->set('regionFilter', $region1->id)
        ->assertSee('Planner One')
        ->assertDontSee('Planner Two');
});

test('regular user cannot exclude planner', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('admin');
    // Note: admin can exclude, so we're testing the guard check

    // Actually let's create a user without proper role
    $planner = User::factory()->create();
    $planner->assignRole('planner');

    $targetPlanner = User::factory()->create();
    $targetPlanner->assignRole('planner');

    Livewire::actingAs($planner)
        ->test(PlannerManagement::class)
        ->call('startExcluding', $targetPlanner->id, 'user')
        ->set('exclusionReason', 'Trying to exclude')
        ->call('excludePlanner')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'permission');
        });

    $targetPlanner->refresh();
    expect($targetPlanner->is_excluded_from_analytics)->toBeFalse();
});

test('cancel editing clears state', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create();
    $planner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->call('startEditing', $planner->id, 'user')
        ->assertSet('editingPlannerId', $planner->id)
        ->call('cancelEditing')
        ->assertSet('editingPlannerId', null)
        ->assertSet('editName', '');
});

test('cancel excluding clears state', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = User::factory()->create();
    $planner->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(PlannerManagement::class)
        ->call('startExcluding', $planner->id, 'user')
        ->assertSet('excludingPlannerId', $planner->id)
        ->call('cancelExcluding')
        ->assertSet('excludingPlannerId', null)
        ->assertSet('exclusionReason', '');
});
