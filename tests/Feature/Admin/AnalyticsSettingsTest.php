<?php

use App\Livewire\Admin\AnalyticsSettings;
use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('analytics settings page renders for sudo_admin', function () {
    $user = User::factory()->create();
    $user->assignRole('sudo_admin');

    $this->actingAs($user)
        ->get(route('admin.analytics-settings'))
        ->assertSeeLivewire(AnalyticsSettings::class);
});

test('analytics settings page redirects non-sudo_admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.analytics-settings'))
        ->assertForbidden();
});

test('analytics settings page redirects regular users', function () {
    $user = User::factory()->create();
    $user->assignRole('planner');

    $this->actingAs($user)
        ->get(route('admin.analytics-settings'))
        ->assertForbidden();
});

test('displays current settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    $settings = AnalyticsSetting::instance();
    $settings->update([
        'scope_year' => '2026',
        'selected_cycle_types' => null,
        'selected_contractors' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(AnalyticsSettings::class)
        ->assertSet('scopeYear', '2026')
        ->assertSet('allCycleTypes', true)
        ->assertSet('allContractors', true);
});

test('can update scope year', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(AnalyticsSettings::class)
        ->set('scopeYear', '2025')
        ->call('save')
        ->assertDispatched('notify');

    expect(AnalyticsSetting::instance()->scope_year)->toBe('2025');
});

test('can select specific cycle types', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    // Create some circuits with cycle types
    Circuit::factory()->create(['work_order' => '2026-001', 'cycle_type' => 'Reactive']);
    Circuit::factory()->create(['work_order' => '2026-002', 'cycle_type' => 'VM Detection']);

    Livewire::actingAs($admin)
        ->test(AnalyticsSettings::class)
        ->set('allCycleTypes', false)
        ->set('selectedCycleTypes', ['Reactive'])
        ->call('save')
        ->assertDispatched('notify');

    $settings = AnalyticsSetting::instance();
    expect($settings->selected_cycle_types)->toBe(['Reactive']);
});

test('can select specific contractors', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    // Create planners with contractor prefixes
    $planner1 = User::factory()->create(['ws_username' => 'ASPLUNDH\\jsmith']);
    $planner1->assignRole('planner');

    $planner2 = User::factory()->create(['ws_username' => 'DAVEY\\jdoe']);
    $planner2->assignRole('planner');

    Livewire::actingAs($admin)
        ->test(AnalyticsSettings::class)
        ->set('allContractors', false)
        ->set('selectedContractors', ['ASPLUNDH'])
        ->call('save')
        ->assertDispatched('notify');

    $settings = AnalyticsSetting::instance();
    expect($settings->selected_contractors)->toBe(['ASPLUNDH']);
});

test('can reset to defaults', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    // Set non-default values first
    AnalyticsSetting::updateSettings([
        'scope_year' => '2020',
        'selected_cycle_types' => ['Reactive'],
        'selected_contractors' => ['ASPLUNDH'],
    ], $admin);

    Livewire::actingAs($admin)
        ->test(AnalyticsSettings::class)
        ->call('resetToDefaults')
        ->assertDispatched('notify');

    $settings = AnalyticsSetting::instance();
    expect($settings->scope_year)->toBe(date('Y'));
    expect($settings->selected_cycle_types)->toBeNull();
    expect($settings->selected_contractors)->toBeNull();
});

test('non-sudo_admin cannot save settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // This should fail silently with error message
    Livewire::actingAs($admin)
        ->test(AnalyticsSettings::class)
        ->set('scopeYear', '2020')
        ->call('save')
        ->assertDispatched('notify', function ($name, $params) {
            return $params['type'] === 'error';
        });

    // Settings should not have changed
    expect(AnalyticsSetting::instance()->scope_year)->not->toBe('2020');
});

test('toggling all cycle types clears selection', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(AnalyticsSettings::class)
        ->set('allCycleTypes', false)
        ->set('selectedCycleTypes', ['Reactive', 'VM Detection'])
        ->set('allCycleTypes', true)
        ->assertSet('selectedCycleTypes', []);
});

test('toggling all contractors clears selection', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(AnalyticsSettings::class)
        ->set('allContractors', false)
        ->set('selectedContractors', ['ASPLUNDH', 'DAVEY'])
        ->set('allContractors', true)
        ->assertSet('selectedContractors', []);
});

test('preview stats shows correct circuit count for scope year', function () {
    // Create circuits with specific scope year and cycle types
    Circuit::factory()->create([
        'work_order' => '2026-001',
        'cycle_type' => 'Reactive',
        'is_excluded' => false,
    ]);
    Circuit::factory()->create([
        'work_order' => '2026-002',
        'cycle_type' => 'VM Detection',
        'is_excluded' => false,
    ]);
    Circuit::factory()->create([
        'work_order' => '2025-001',
        'cycle_type' => 'Reactive',
        'is_excluded' => false,
    ]);

    // Test the scope directly via model
    $circuitsFor2026 = Circuit::forScopeYear('2026')->count();
    $circuitsFor2025 = Circuit::forScopeYear('2025')->count();

    expect($circuitsFor2026)->toBe(2);
    expect($circuitsFor2025)->toBe(1);
});
