<?php

use App\Livewire\Assessments\Dashboard\Overview;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

describe('Dashboard Route', function () {
    test('guests are redirected to login', function () {
        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    });

    test('authenticated users not onboarded are redirected to onboarding', function () {
        $user = User::factory()->pendingOnboarding()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('onboarding'));
    });

    test('onboarded users are redirected to assessments overview', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->markAsOnboarded();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('assessments.overview'));
    });
});

describe('Overview Rendering', function () {
    test('overview route renders livewire component for onboarded user', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->markAsOnboarded();

        $this->actingAs($user)
            ->get(route('assessments.overview'))
            ->assertSuccessful()
            ->assertSeeLivewire(Overview::class);
    });
});
