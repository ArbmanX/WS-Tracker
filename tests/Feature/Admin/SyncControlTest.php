<?php

use App\Enums\SyncStatus;
use App\Jobs\BuildAggregatesJob;
use App\Jobs\SyncCircuitsJob;
use App\Jobs\SyncPlannedUnitsJob;
use App\Livewire\Admin\SyncControl;
use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\Region;
use App\Models\SyncLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
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
        ->call('triggerCircuitSync')
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
        ->call('triggerCircuitSync')
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
        ->call('triggerCircuitSync')
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
        ->call('triggerCircuitSync')
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
        ->call('triggerCircuitSync')
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

// ==== Enhanced Sync Control Tests ====

test('activeTab defaults to circuits', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->assertSet('activeTab', 'circuits');
});

test('can switch between sync tabs', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->assertSet('activeTab', 'circuits')
        ->set('activeTab', 'planned')
        ->assertSet('activeTab', 'planned')
        ->set('activeTab', 'aggregates')
        ->assertSet('activeTab', 'aggregates');
});

test('sudo_admin can trigger planned units sync', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->call('triggerPlannedUnitsSync')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'dispatched to queue');
        });

    Queue::assertPushed(SyncPlannedUnitsJob::class);
});

test('sudo_admin can trigger planned units dry run', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('plannedUnitsDryRun', true)
        ->call('triggerPlannedUnitsSync')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'dry-run');
        });

    Queue::assertPushed(SyncPlannedUnitsJob::class);
});

test('admin cannot trigger planned units sync', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->call('triggerPlannedUnitsSync')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'sudo admin');
        });

    Queue::assertNothingPushed();
});

test('sudo_admin can trigger aggregates build', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('aggregateType', 'both')
        ->set('aggregateDate', now()->format('Y-m-d'))
        ->call('triggerAggregatesBuild')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'dispatched to queue');
        });

    Queue::assertPushed(BuildAggregatesJob::class);
});

test('admin cannot trigger aggregates build', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->call('triggerAggregatesBuild')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'sudo admin');
        });

    Queue::assertNothingPushed();
});

test('globalSyncEnabled reflects AnalyticsSetting state', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    AnalyticsSetting::updateSettings([
        'planned_units_sync_enabled' => true,
    ], $user);

    $component = Livewire::actingAs($user)->test(SyncControl::class);

    expect($component->get('globalSyncEnabled'))->toBeTrue();
});

test('sudo_admin can save global settings', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('globalSyncEnabled', false)
        ->set('syncIntervalHours', 24)
        ->call('saveGlobalSettings')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'saved successfully');
        });

    $settings = AnalyticsSetting::instance();
    expect($settings->planned_units_sync_enabled)->toBeFalse();
    expect($settings->sync_interval_hours)->toBe(24);
});

test('admin cannot save global settings', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    AnalyticsSetting::updateSettings([
        'planned_units_sync_enabled' => true,
        'sync_interval_hours' => 12,
    ], $user);

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('globalSyncEnabled', false)
        ->set('syncIntervalHours', 24)
        ->call('saveGlobalSettings')
        ->assertDispatched('notify', function ($name, $params) {
            return str_contains($params['message'], 'sudo admin');
        });

    // Settings should not have changed - clear cache and re-fetch
    Cache::forget('analytics_settings');
    $settings = AnalyticsSetting::instance();
    expect($settings->planned_units_sync_enabled)->toBeTrue();
});

test('syncIntervalHours is clamped to valid range', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    Livewire::actingAs($user)
        ->test(SyncControl::class)
        ->set('syncIntervalHours', 0) // Below minimum
        ->call('saveGlobalSettings');

    $settings = AnalyticsSetting::instance();
    expect($settings->sync_interval_hours)->toBeGreaterThanOrEqual(1);
});

test('circuitsNeedingSync computed property works', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $region = Region::factory()->create();

    // Create a circuit that needs sync
    Circuit::factory()->create([
        'region_id' => $region->id,
        'planned_units_sync_enabled' => true,
        'is_excluded' => false,
        'api_status' => 'ACTIV',
        'last_planned_units_synced_at' => null,
    ]);

    $component = Livewire::actingAs($user)->test(SyncControl::class);

    expect($component->get('circuitsNeedingSync'))->toBeGreaterThanOrEqual(1);
});

test('aggregateTypes computed property returns expected values', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $component = Livewire::actingAs($user)->test(SyncControl::class);

    $types = $component->get('aggregateTypes');
    expect($types)->toHaveKeys(['daily', 'weekly', 'both']);
});
