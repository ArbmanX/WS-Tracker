<?php

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Livewire\DataManagement\SyncLogs;
use App\Models\SyncLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-sudo users cannot access sync logs', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.data.sync-logs'))
        ->assertForbidden();
});

test('sudo_admin can access sync logs', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.data.sync-logs'))
        ->assertSeeLivewire(SyncLogs::class);
});

test('displays sync logs list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    SyncLog::factory()->count(5)->create();

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 5);
});

test('can filter sync logs by status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    SyncLog::factory()->count(3)->create(['sync_status' => SyncStatus::Completed]);
    SyncLog::factory()->count(2)->failed()->create();

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->set('statusFilter', 'failed')
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 2);
});

test('can filter sync logs by trigger', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    SyncLog::factory()->count(3)->create(['sync_trigger' => SyncTrigger::Scheduled]);
    SyncLog::factory()->count(2)->manual()->create();

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->set('triggerFilter', 'manual')
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 2);
});

test('can filter sync logs by type', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    SyncLog::factory()->count(3)->circuitList()->create();
    SyncLog::factory()->count(2)->aggregates()->create();

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->set('typeFilter', 'circuit_list')
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 3);
});

test('can filter sync logs by date range', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    SyncLog::factory()->create(['started_at' => now()->subDays(5)]);
    SyncLog::factory()->create(['started_at' => now()->subDays(2)]);
    SyncLog::factory()->create(['started_at' => now()]);

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->set('dateFrom', now()->subDays(3)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 2);
});

test('can view sync log details', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $log = SyncLog::factory()->create();

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->assertSet('showModal', false)
        ->call('viewLog', $log->id)
        ->assertSet('showModal', true)
        ->assertSet('selectedLogId', $log->id);
});

test('can close detail modal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $log = SyncLog::factory()->create();

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->call('viewLog', $log->id)
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('selectedLogId', null);
});

test('can clear all filters', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->set('statusFilter', 'failed')
        ->set('triggerFilter', 'manual')
        ->set('typeFilter', 'circuit_list')
        ->set('dateFrom', '2024-01-01')
        ->set('dateTo', '2024-12-31')
        ->call('clearFilters')
        ->assertSet('statusFilter', '')
        ->assertSet('triggerFilter', '')
        ->assertSet('typeFilter', '')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '');
});

test('hasFilters property detects active filters', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $component = Livewire::actingAs($admin)->test(SyncLogs::class);

    expect($component->get('hasFilters'))->toBeFalse();

    $component->set('statusFilter', 'failed');
    expect($component->get('hasFilters'))->toBeTrue();
});

test('failed sync logs show error information', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $log = SyncLog::factory()->failed('Connection timeout')->create([
        'error_details' => ['exception' => 'TimeoutException', 'code' => 504],
    ]);

    Livewire::actingAs($admin)
        ->test(SyncLogs::class)
        ->call('viewLog', $log->id)
        ->assertSee('Connection timeout');
});
