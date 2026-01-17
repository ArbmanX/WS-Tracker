<?php

use App\Livewire\Admin\UnlinkedPlanners;
use App\Models\UnlinkedPlanner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('unlinked planners page renders for admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.planners.unlinked'))
        ->assertSeeLivewire(UnlinkedPlanners::class);
});

test('displays unlinked planners', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    UnlinkedPlanner::factory()->count(3)->create();

    Livewire::actingAs($user)
        ->test(UnlinkedPlanners::class)
        ->assertViewHas('planners', fn ($planners) => $planners->count() === 3);
});

test('hides linked planners by default', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    UnlinkedPlanner::factory()->create();
    UnlinkedPlanner::factory()->linked()->create();

    Livewire::actingAs($user)
        ->test(UnlinkedPlanners::class)
        ->assertViewHas('planners', fn ($planners) => $planners->count() === 1);
});

test('can show linked planners', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    UnlinkedPlanner::factory()->create();
    UnlinkedPlanner::factory()->linked()->create();

    Livewire::actingAs($user)
        ->test(UnlinkedPlanners::class)
        ->set('showLinked', true)
        ->assertViewHas('planners', fn ($planners) => $planners->count() === 2);
});

test('can start linking process', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $planner = UnlinkedPlanner::factory()->create();

    Livewire::actingAs($user)
        ->test(UnlinkedPlanners::class)
        ->assertSet('linkingPlannerId', null)
        ->call('startLinking', $planner->id)
        ->assertSet('linkingPlannerId', $planner->id);
});

test('can cancel linking process', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $planner = UnlinkedPlanner::factory()->create();

    Livewire::actingAs($user)
        ->test(UnlinkedPlanners::class)
        ->call('startLinking', $planner->id)
        ->assertSet('linkingPlannerId', $planner->id)
        ->call('cancelLinking')
        ->assertSet('linkingPlannerId', null);
});

test('admin can link planner to user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create(['is_ws_linked' => false]);
    $planner = UnlinkedPlanner::factory()->create();

    Livewire::actingAs($admin)
        ->test(UnlinkedPlanners::class)
        ->call('startLinking', $planner->id)
        ->set('selectedUserId', $targetUser->id)
        ->call('linkPlanner')
        ->assertDispatched('notify');

    expect($planner->refresh()->linked_to_user_id)->toBe($targetUser->id);
    expect($targetUser->refresh()->is_ws_linked)->toBeTrue();
});

test('planner cannot link planners', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');

    $targetUser = User::factory()->create(['is_ws_linked' => false]);
    $planner = UnlinkedPlanner::factory()->create();

    Livewire::actingAs($user)
        ->test(UnlinkedPlanners::class)
        ->call('startLinking', $planner->id)
        ->set('selectedUserId', $targetUser->id)
        ->call('linkPlanner')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'permission');
        });

    expect($planner->refresh()->linked_to_user_id)->toBeNull();
});

test('sudo_admin can create user from planner', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $planner = UnlinkedPlanner::factory()->create([
        'display_name' => 'John Doe',
        'ws_username' => 'john.doe',
        'ws_user_guid' => 'test-guid-123',
    ]);

    Livewire::actingAs($admin)
        ->test(UnlinkedPlanners::class)
        ->call('createUser', $planner->id)
        ->assertDispatched('notify');

    expect(User::where('ws_user_guid', 'test-guid-123')->exists())->toBeTrue();
    expect($planner->refresh()->linked_to_user_id)->not->toBeNull();
});

test('admin cannot create user from planner', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $planner = UnlinkedPlanner::factory()->create();

    Livewire::actingAs($admin)
        ->test(UnlinkedPlanners::class)
        ->call('createUser', $planner->id)
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'sudo admin');
        });

    expect($planner->refresh()->linked_to_user_id)->toBeNull();
});

test('available users excludes already linked users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $unlinkedUser = User::factory()->create(['is_ws_linked' => false]);
    User::factory()->create(['is_ws_linked' => true]);

    $component = Livewire::actingAs($admin)
        ->test(UnlinkedPlanners::class);

    $availableUsers = $component->get('availableUsers');

    expect($availableUsers)->toHaveCount(2); // admin + unlinkedUser
    expect($availableUsers->pluck('id'))->toContain($unlinkedUser->id);
});
