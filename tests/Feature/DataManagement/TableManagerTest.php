<?php

use App\Livewire\DataManagement\TableManager;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-sudo users cannot access table manager', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.data.tables'))
        ->assertForbidden();
});

test('sudo_admin can access table manager', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.data.tables'))
        ->assertSeeLivewire(TableManager::class);
});

test('displays table stats', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $component = Livewire::actingAs($admin)
        ->test(TableManager::class);

    $tableStats = $component->get('tableStats');
    expect($tableStats)->toBeArray();
    expect($tableStats)->toHaveKeys(['circuit_snapshots', 'sync_logs']);
});

test('summary computes totals correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $component = Livewire::actingAs($admin)
        ->test(TableManager::class);

    $summary = $component->get('summary');

    expect($summary)->toHaveKeys(['total_rows', 'tables_with_data', 'tables_with_user_changes']);
});

test('can toggle table selection', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->assertSet('selectedTables', [])
        ->call('toggleTableSelection', 'sync_logs')
        ->assertSet('selectedTables', ['sync_logs'])
        ->call('toggleTableSelection', 'sync_logs')
        ->assertSet('selectedTables', []);
});

test('can select all tables', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $component = Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->assertSet('selectedTables', [])
        ->call('selectAllTables');

    $selectedTables = $component->get('selectedTables');
    expect($selectedTables)->toHaveCount(8); // All 8 clearable tables
});

test('can clear selection', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->call('selectAllTables')
        ->call('clearSelection')
        ->assertSet('selectedTables', []);
});

test('opens confirm modal for single table', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->assertSet('showConfirmModal', false)
        ->call('confirmClear', 'sync_logs')
        ->assertSet('showConfirmModal', true)
        ->assertSet('selectedTable', 'sync_logs')
        ->assertSet('bulkMode', false);
});

test('opens confirm modal for bulk clear', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->call('toggleTableSelection', 'sync_logs')
        ->call('toggleTableSelection', 'circuit_aggregates')
        ->call('confirmBulkClear')
        ->assertSet('showConfirmModal', true)
        ->assertSet('bulkMode', true);
});

test('shows warning when no tables selected for bulk clear', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->assertSet('selectedTables', [])
        ->call('confirmBulkClear')
        ->assertSet('showConfirmModal', false)
        ->assertDispatched('notify');
});

test('can close modal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->call('confirmClear', 'sync_logs')
        ->assertSet('showConfirmModal', true)
        ->call('closeModal')
        ->assertSet('showConfirmModal', false)
        ->assertSet('selectedTable', null)
        ->assertSet('bulkMode', false);
});

test('clears single table', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    // Seed some data in sync_logs
    DB::table('sync_logs')->insert([
        'sync_type' => 'circuit_list',
        'sync_status' => 'completed',
        'sync_trigger' => 'manual',
        'started_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('sync_logs')->count())->toBeGreaterThan(0);

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->call('confirmClear', 'sync_logs')
        ->call('clearTable')
        ->assertDispatched('notify');

    expect(DB::table('sync_logs')->count())->toBe(0);
});

test('clears multiple tables in bulk mode', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    // Seed some data
    DB::table('sync_logs')->insert([
        'sync_type' => 'circuit_list',
        'sync_status' => 'completed',
        'sync_trigger' => 'manual',
        'started_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->call('toggleTableSelection', 'sync_logs')
        ->call('confirmBulkClear')
        ->call('clearTable')
        ->assertDispatched('notify');

    expect(DB::table('sync_logs')->count())->toBe(0);
});

test('ignores non-clearable tables', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $component = Livewire::actingAs($admin)
        ->test(TableManager::class);

    // Manually set an invalid table (simulating tampered request)
    $component->set('selectedTable', 'users');
    $component->set('showConfirmModal', true);
    $component->call('clearTable');

    // Users table should still have data
    expect(User::count())->toBeGreaterThan(0);
});

test('groups tables by category', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $component = Livewire::actingAs($admin)
        ->test(TableManager::class);

    $groupedTables = $component->get('groupedTables');

    expect($groupedTables)->toHaveKeys(['snapshots', 'aggregates', 'logs']);
});

test('logs activity on table clear', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    // Seed some data
    DB::table('sync_logs')->insert([
        'sync_type' => 'circuit_list',
        'sync_status' => 'completed',
        'sync_trigger' => 'manual',
        'started_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(TableManager::class)
        ->call('confirmClear', 'sync_logs')
        ->call('clearTable');

    // Check activity log was created
    $activity = DB::table('activity_log')
        ->where('description', 'like', '%Truncated database tables%')
        ->first();

    expect($activity)->not->toBeNull();
});
