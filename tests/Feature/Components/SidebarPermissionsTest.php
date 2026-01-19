<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('sudo_admin sees all menu sections including administration', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Dashboard')
        ->assertSee('Assessments')
        ->assertSee('Administration')
        ->assertSee('Manage Planners')
        ->assertSee('Data Management')
        ->assertSee('Sync Controls')
        ->assertSee('User Management');
});

test('admin sees administration section but not sudo_admin only items', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Dashboard')
        ->assertSee('Assessments')
        ->assertSee('Administration')
        ->assertSee('Manage Planners')
        ->assertSee('Sync Controls')
        ->assertDontSee('Data Management')  // sudo_admin only
        ->assertDontSee('User Management'); // sudo_admin only
});

test('planner sees limited menu without administration section', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Dashboard')
        ->assertSee('Assessments')
        ->assertSee('Planning')
        ->assertDontSee('Administration')
        ->assertDontSee('Manage Planners')
        ->assertDontSee('Sync Controls');
});

test('planner management link routes to correct page for admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.planners'))
        ->assertSuccessful();
});

test('planner management link routes to correct page for sudo_admin', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.planners'))
        ->assertSuccessful();
});

test('planner cannot access planner management page', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');

    $this->actingAs($user)
        ->get(route('admin.planners'))
        ->assertForbidden();
});

test('admin can access sync controls', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.sync'))
        ->assertSuccessful();
});

test('planner cannot access sync controls', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');

    $this->actingAs($user)
        ->get(route('admin.sync'))
        ->assertForbidden();
});

test('admin cannot access data management (sudo_admin only)', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.data'))
        ->assertForbidden();
});

test('sudo_admin can access data management', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.data'))
        ->assertSuccessful();
});

test('admin cannot access user management (sudo_admin only)', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertForbidden();
});

test('sudo_admin can access user management', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertSuccessful();
});
