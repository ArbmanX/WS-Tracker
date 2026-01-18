<?php

use App\Livewire\DataManagement\Index;
use App\Models\Circuit;
use App\Models\Region;
use App\Models\SyncLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-sudo users cannot access data management index', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.data'))
        ->assertForbidden();
});

test('planner cannot access data management', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');

    $this->actingAs($user)
        ->get(route('admin.data'))
        ->assertForbidden();
});

test('sudo_admin can access data management index', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.data'))
        ->assertSeeLivewire(Index::class);
});

test('stats show correct circuit counts', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->count(5)->create(['region_id' => $region->id, 'is_excluded' => false]);
    Circuit::factory()->count(2)->create(['region_id' => $region->id, 'is_excluded' => true]);
    Circuit::factory()->create([
        'region_id' => $region->id,
        'user_modified_fields' => ['title' => ['modified_at' => now()]],
    ]);

    $component = Livewire::actingAs($admin)->test(Index::class);

    expect($component->get('stats')['total_circuits'])->toBe(8);
    expect($component->get('stats')['excluded_circuits'])->toBe(2);
    expect($component->get('stats')['modified_circuits'])->toBe(1);
});

test('stats show correct sync counts', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    // Create some recent sync logs
    SyncLog::factory()->count(3)->create(['started_at' => now()]);
    SyncLog::factory()->count(2)->create([
        'started_at' => now(),
        'sync_status' => 'failed',
    ]);
    // Create old sync log (should not be counted)
    SyncLog::factory()->create(['started_at' => now()->subDays(2)]);

    $component = Livewire::actingAs($admin)->test(Index::class);

    expect($component->get('stats')['syncs_24h'])->toBe(5);
    expect($component->get('stats')['failed_syncs_24h'])->toBe(2);
});

test('tabs property contains correct navigation items', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $component = Livewire::actingAs($admin)->test(Index::class);

    $tabs = $component->get('tabs');
    expect($tabs)->toHaveCount(4);
    expect(collect($tabs)->pluck('route')->toArray())->toContain(
        'admin.data.circuits',
        'admin.data.exclusions',
        'admin.data.sync-logs',
        'admin.data.endpoints'
    );
});

test('renders navigation cards for all sections', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertSee('Circuit Browser')
        ->assertSee('Exclusions')
        ->assertSee('Sync Logs')
        ->assertSee('API Endpoints');
});
