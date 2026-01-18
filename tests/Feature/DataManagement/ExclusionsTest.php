<?php

use App\Livewire\DataManagement\Exclusions;
use App\Models\Circuit;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-sudo users cannot access exclusions', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.data.exclusions'))
        ->assertForbidden();
});

test('sudo_admin can access exclusions', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.data.exclusions'))
        ->assertSeeLivewire(Exclusions::class);
});

test('displays only excluded circuits', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->count(2)->create(['is_excluded' => true, 'region_id' => $region->id]);
    Circuit::factory()->count(3)->create(['is_excluded' => false, 'region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(Exclusions::class)
        ->assertViewHas('circuits', fn ($circuits) => $circuits->count() === 2);
});

test('shows empty state when no excluded circuits', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->count(3)->create(['is_excluded' => false, 'region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(Exclusions::class)
        ->assertViewHas('circuits', fn ($circuits) => $circuits->count() === 0);
});

test('excluded circuit appears in exclusions list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create([
        'work_order' => 'WO-EXCLUDED',
        'is_excluded' => true,
        'exclusion_reason' => 'Test reason',
        'excluded_by' => $admin->id,
        'excluded_at' => now(),
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Exclusions::class)
        ->assertSee('WO-EXCLUDED')
        ->assertSee('Test reason');
});

test('can include excluded circuit', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->create([
        'is_excluded' => true,
        'exclusion_reason' => 'Previously excluded',
        'excluded_by' => $admin->id,
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Exclusions::class)
        ->call('includeCircuit', $circuit->id)
        ->assertDispatched('notify');

    $circuit->refresh();
    expect($circuit->is_excluded)->toBeFalse();
    expect($circuit->exclusion_reason)->toBeNull();
    expect($circuit->excluded_by)->toBeNull();
});

test('include all excludes all circuits', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->count(3)->create([
        'is_excluded' => true,
        'exclusion_reason' => 'Bulk excluded',
        'excluded_by' => $admin->id,
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Exclusions::class)
        ->assertSet('excludedCount', 3)
        ->call('includeAll')
        ->assertDispatched('notify');

    expect(Circuit::excluded()->count())->toBe(0);
});

test('excluded count property is accurate', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->count(5)->create(['is_excluded' => true, 'region_id' => $region->id]);
    Circuit::factory()->count(3)->create(['is_excluded' => false, 'region_id' => $region->id]);

    Livewire::actingAs($admin)
        ->test(Exclusions::class)
        ->assertSet('excludedCount', 5);
});

test('shows excluder information', function () {
    $admin = User::factory()->create(['name' => 'Admin User']);
    $admin->assignRole('sudo_admin');

    $region = Region::factory()->create();
    Circuit::factory()->create([
        'is_excluded' => true,
        'excluded_by' => $admin->id,
        'excluded_at' => now(),
        'region_id' => $region->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Exclusions::class)
        ->assertSee('Admin User');
});
