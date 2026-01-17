<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

describe('EnsureAdmin middleware', function () {
    test('sudo_admin can access admin routes', function () {
        $user = User::factory()->create();
        $user->assignRole('sudo_admin');

        $this->actingAs($user)
            ->get(route('admin.sync'))
            ->assertSuccessful();
    });

    test('admin can access admin routes', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('admin.sync'))
            ->assertSuccessful();
    });

    test('planner cannot access admin routes', function () {
        $user = User::factory()->create();
        $user->assignRole('planner');

        $this->actingAs($user)
            ->get(route('admin.sync'))
            ->assertForbidden();
    });

    test('user without role cannot access admin routes', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.sync'))
            ->assertForbidden();
    });

    test('unauthenticated user is redirected to login', function () {
        $this->get(route('admin.sync'))
            ->assertRedirect(route('login'));
    });
});

describe('EnsureSudoAdmin middleware', function () {
    test('sudo_admin can access sudo_admin routes', function () {
        $user = User::factory()->create();
        $user->assignRole('sudo_admin');

        $this->actingAs($user)
            ->get(route('admin.users'))
            ->assertSuccessful();
    });

    test('admin cannot access sudo_admin routes', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('admin.users'))
            ->assertForbidden();
    });

    test('planner cannot access sudo_admin routes', function () {
        $user = User::factory()->create();
        $user->assignRole('planner');

        $this->actingAs($user)
            ->get(route('admin.users'))
            ->assertForbidden();
    });

    test('unauthenticated user is redirected to login for sudo routes', function () {
        $this->get(route('admin.users'))
            ->assertRedirect(route('login'));
    });
});
