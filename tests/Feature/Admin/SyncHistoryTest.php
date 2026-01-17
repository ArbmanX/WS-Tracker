<?php

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Livewire\Admin\SyncHistory;
use App\Models\SyncLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('sync history page renders for admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.sync.history'))
        ->assertSeeLivewire(SyncHistory::class);
});

test('displays sync logs', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->count(5)->create();

    Livewire::actingAs($user)
        ->test(SyncHistory::class)
        ->assertViewHas('logs', function ($logs) {
            return $logs->count() === 5;
        });
});

test('can filter by status', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->create(['sync_status' => SyncStatus::Completed]);
    SyncLog::factory()->failed()->create();

    Livewire::actingAs($user)
        ->test(SyncHistory::class)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 2)
        ->set('statusFilter', SyncStatus::Completed->value)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 1);
});

test('can filter by trigger type', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->create(['sync_trigger' => SyncTrigger::Scheduled]);
    SyncLog::factory()->manual()->create();

    Livewire::actingAs($user)
        ->test(SyncHistory::class)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 2)
        ->set('triggerFilter', SyncTrigger::Manual->value)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 1);
});

test('can filter by sync type', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->circuitList()->create();
    SyncLog::factory()->full()->create();

    Livewire::actingAs($user)
        ->test(SyncHistory::class)
        ->set('typeFilter', SyncType::CircuitList->value)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 1);
});

test('can clear all filters', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->count(3)->create();

    Livewire::actingAs($user)
        ->test(SyncHistory::class)
        ->set('statusFilter', SyncStatus::Completed->value)
        ->set('triggerFilter', SyncTrigger::Manual->value)
        ->call('clearFilters')
        ->assertSet('statusFilter', '')
        ->assertSet('triggerFilter', '')
        ->assertSet('typeFilter', '');
});

test('pagination works correctly', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->count(25)->create();

    Livewire::actingAs($user)
        ->test(SyncHistory::class)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 15)
        ->call('nextPage')
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 10);
});

test('shows error message for failed syncs', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->failed('Connection timeout')->create();

    Livewire::actingAs($user)
        ->test(SyncHistory::class)
        ->assertSee('Connection timeout');
});
