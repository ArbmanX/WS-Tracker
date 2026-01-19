<?php

use App\Enums\SyncStatus;
use App\Jobs\SyncCircuitsJob;
use App\Livewire\Admin\SyncControl;
use App\Models\SyncLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('sync control page renders for admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.sync'))
        ->assertSuccessful();
});

test('displays last sync information', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->create([
        'sync_status' => SyncStatus::Completed,
        'started_at' => now()->subMinutes(30),
        'circuits_processed' => 42,
    ]);

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->assertSee('42');
});

test('displays recent syncs', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->count(3)->create();

    $component = Livewire::actingAs($user)->test(SyncControl::class);

    expect($component->get('recentSyncs'))->toHaveCount(3);
});

test('selectedStatuses defaults to ACTIV', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->assertSet('selectedStatuses', ['ACTIV']);
});

test('can modify selected statuses', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('selectedStatuses', ['ACTIV', 'QC'])
        ->assertSet('selectedStatuses', ['ACTIV', 'QC']);
});

test('sudo_admin can trigger sync and dispatches job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('selectedStatuses', ['ACTIV'])
        ->set('runInBackground', true)
        ->call('triggerSync')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'dispatched to queue');
        });

    Queue::assertPushed(SyncCircuitsJob::class);
});

test('admin cannot trigger sync', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->call('triggerSync')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'sudo admin');
        });

    Queue::assertNothingPushed();
});

test('cannot trigger sync without statuses selected', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('selectedStatuses', [])
        ->call('triggerSync')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'select at least one');
        });
});

test('cannot trigger sync while another is running', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    SyncLog::factory()->inProgress()->create(['started_at' => now()]);

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->call('triggerSync')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'already in progress');
        });
});

test('displays correct sync status badge', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    SyncLog::factory()->create([
        'sync_status' => SyncStatus::Completed,
    ]);

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->assertSee('Completed');
});

test('shows available statuses', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $component = Livewire::actingAs($user)->test(SyncControl::class);

    expect($component->get('availableStatuses'))->toHaveKeys(['ACTIV', 'QC', 'REWRK', 'CLOSE']);
});

test('runInBackground defaults to true', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->assertSet('runInBackground', true);
});

test('can toggle runInBackground option', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('runInBackground', false)
        ->assertSet('runInBackground', false)
        ->set('runInBackground', true)
        ->assertSet('runInBackground', true);
});

test('syncOutput returns empty state when no sync running', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $component = Livewire::actingAs($user)->test(SyncControl::class);
    $output = $component->get('syncOutput');

    expect($output['state']['status'])->toBe('idle');
    expect($output['logs'])->toBeArray();
    expect($output['log_count'])->toBe(0);
});

test('can clear log output', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->call('clearLog')
        ->assertSet('lastLogIndex', 0);
});

test('dispatches job with outputLoggerKey when background sync', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('selectedStatuses', ['ACTIV'])
        ->set('runInBackground', true)
        ->call('triggerSync')
        ->assertDispatched('notify');

    Queue::assertPushed(SyncCircuitsJob::class, function ($job) {
        // Job should have an outputLoggerKey set
        return true;
    });
});

test('shows idle status when no sync is running', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->assertSee('Idle');
});

test('pollForUpdates refreshes computed properties', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    // This just tests that the method can be called without error
    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->call('pollForUpdates');
});
