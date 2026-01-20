<?php

use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('instance returns singleton instance', function () {
    $instance1 = AnalyticsSetting::instance();
    $instance2 = AnalyticsSetting::instance();

    expect($instance1->id)->toBe($instance2->id);
});

test('getScopeYear returns scope year from settings', function () {
    AnalyticsSetting::instance()->update(['scope_year' => '2025']);

    expect(AnalyticsSetting::getScopeYear())->toBe('2025');
});

test('getSelectedCycleTypes returns null for all types', function () {
    AnalyticsSetting::instance()->update(['selected_cycle_types' => null]);

    expect(AnalyticsSetting::getSelectedCycleTypes())->toBeNull();
});

test('getSelectedCycleTypes returns array for specific types', function () {
    AnalyticsSetting::instance()->update(['selected_cycle_types' => ['Reactive', 'VM Detection']]);

    expect(AnalyticsSetting::getSelectedCycleTypes())->toBe(['Reactive', 'VM Detection']);
});

test('getSelectedContractors returns null for all contractors', function () {
    AnalyticsSetting::instance()->update(['selected_contractors' => null]);

    expect(AnalyticsSetting::getSelectedContractors())->toBeNull();
});

test('getSelectedContractors returns array for specific contractors', function () {
    AnalyticsSetting::instance()->update(['selected_contractors' => ['ASPLUNDH', 'DAVEY']]);

    expect(AnalyticsSetting::getSelectedContractors())->toBe(['ASPLUNDH', 'DAVEY']);
});

test('getAvailableScopeYears returns years from circuits', function () {
    Circuit::factory()->create(['work_order' => '2026-001']);
    Circuit::factory()->create(['work_order' => '2025-001']);
    Circuit::factory()->create(['work_order' => '2024-001']);

    $years = AnalyticsSetting::getAvailableScopeYears();

    expect($years)->toContain('2026', '2025', '2024');
});

test('getAvailableCycleTypes returns unique cycle types', function () {
    Circuit::factory()->create(['work_order' => '2026-001', 'cycle_type' => 'Reactive']);
    Circuit::factory()->create(['work_order' => '2026-002', 'cycle_type' => 'VM Detection']);
    Circuit::factory()->create(['work_order' => '2026-003', 'cycle_type' => 'Reactive']); // Duplicate

    $types = AnalyticsSetting::getAvailableCycleTypes();

    expect($types)->toContain('Reactive', 'VM Detection');
    expect(array_count_values($types)['Reactive'])->toBe(1); // Only one Reactive
});

test('getAvailableContractors returns unique contractors from planners', function () {
    $planner1 = User::factory()->create(['ws_username' => 'ASPLUNDH\\jsmith']);
    $planner1->assignRole('planner');

    $planner2 = User::factory()->create(['ws_username' => 'DAVEY\\jdoe']);
    $planner2->assignRole('planner');

    $planner3 = User::factory()->create(['ws_username' => 'ASPLUNDH\\mwilson']); // Same contractor
    $planner3->assignRole('planner');

    $contractors = AnalyticsSetting::getAvailableContractors();

    expect($contractors)->toContain('ASPLUNDH', 'DAVEY');
    expect(count(array_filter($contractors, fn ($c) => $c === 'ASPLUNDH')))->toBe(1);
});

test('updateSettings clears cache', function () {
    $originalYear = AnalyticsSetting::instance()->scope_year;

    AnalyticsSetting::updateSettings([
        'scope_year' => '2020',
    ], User::factory()->create());

    expect(AnalyticsSetting::instance()->scope_year)->toBe('2020');
    expect(AnalyticsSetting::getScopeYear())->toBe('2020');
});

test('updateSettings tracks who updated', function () {
    $user = User::factory()->create();

    AnalyticsSetting::updateSettings([
        'scope_year' => '2020',
    ], $user);

    $settings = AnalyticsSetting::instance();
    expect($settings->updated_by)->toBe($user->id);
});

// Circuit scope tests
test('circuit forScopeYear filters by work order prefix', function () {
    Circuit::factory()->create(['work_order' => '2026-001']);
    Circuit::factory()->create(['work_order' => '2025-001']);
    Circuit::factory()->create(['work_order' => '2026-002']);

    $circuits = Circuit::forScopeYear('2026')->get();

    expect($circuits)->toHaveCount(2);
    expect($circuits->pluck('work_order')->toArray())->each->toStartWith('2026-');
});

test('circuit withCycleTypes filters by cycle type', function () {
    Circuit::factory()->create(['work_order' => '2026-001', 'cycle_type' => 'Reactive']);
    Circuit::factory()->create(['work_order' => '2026-002', 'cycle_type' => 'VM Detection']);
    Circuit::factory()->create(['work_order' => '2026-003', 'cycle_type' => 'Proactive']);

    $circuits = Circuit::withCycleTypes(['Reactive', 'VM Detection'])->get();

    expect($circuits)->toHaveCount(2);
});

test('circuit forAnalytics applies settings filters', function () {
    AnalyticsSetting::instance()->update([
        'scope_year' => '2026',
        'selected_cycle_types' => ['Reactive'],
    ]);

    Circuit::factory()->create(['work_order' => '2026-001', 'cycle_type' => 'Reactive']);
    Circuit::factory()->create(['work_order' => '2026-002', 'cycle_type' => 'VM Detection']);
    Circuit::factory()->create(['work_order' => '2025-001', 'cycle_type' => 'Reactive']);

    $circuits = Circuit::forAnalytics()->get();

    expect($circuits)->toHaveCount(1);
    expect($circuits->first()->work_order)->toBe('2026-001');
});

// User scope tests
test('user contractor accessor extracts prefix', function () {
    $user = User::factory()->create(['ws_username' => 'ASPLUNDH\\jsmith']);

    expect($user->contractor)->toBe('ASPLUNDH');
});

test('user contractor accessor returns null for missing prefix', function () {
    $user = User::factory()->create(['ws_username' => 'localuser']);

    expect($user->contractor)->toBeNull();
});

test('user contractor accessor returns null for missing username', function () {
    $user = User::factory()->create(['ws_username' => null]);

    expect($user->contractor)->toBeNull();
});

test('user withContractor filters by contractor prefix', function () {
    $user1 = User::factory()->create(['ws_username' => 'ASPLUNDH\\jsmith']);
    $user2 = User::factory()->create(['ws_username' => 'DAVEY\\jdoe']);
    $user3 = User::factory()->create(['ws_username' => 'ASPLUNDH\\mwilson']);

    $users = User::withContractor('ASPLUNDH')->get();

    expect($users)->toHaveCount(2);
    expect($users->pluck('ws_username')->toArray())->each->toStartWith('ASPLUNDH\\');
});

test('user withContractor accepts array of contractors', function () {
    $user1 = User::factory()->create(['ws_username' => 'ASPLUNDH\\jsmith']);
    $user2 = User::factory()->create(['ws_username' => 'DAVEY\\jdoe']);
    $user3 = User::factory()->create(['ws_username' => 'OTHER\\mwilson']);

    $users = User::withContractor(['ASPLUNDH', 'DAVEY'])->get();

    expect($users)->toHaveCount(2);
});

test('user withAllowedContractors uses settings filter', function () {
    $planner1 = User::factory()->create(['ws_username' => 'ASPLUNDH\\jsmith']);
    $planner1->assignRole('planner');

    $planner2 = User::factory()->create(['ws_username' => 'DAVEY\\jdoe']);
    $planner2->assignRole('planner');

    AnalyticsSetting::instance()->update(['selected_contractors' => ['ASPLUNDH']]);

    $users = User::role('planner')->withAllowedContractors()->get();

    expect($users)->toHaveCount(1);
    expect($users->first()->ws_username)->toBe('ASPLUNDH\\jsmith');
});

test('user withAllowedContractors returns all when null', function () {
    $planner1 = User::factory()->create(['ws_username' => 'ASPLUNDH\\jsmith']);
    $planner1->assignRole('planner');

    $planner2 = User::factory()->create(['ws_username' => 'DAVEY\\jdoe']);
    $planner2->assignRole('planner');

    AnalyticsSetting::instance()->update(['selected_contractors' => null]);

    $users = User::role('planner')->withAllowedContractors()->get();

    expect($users)->toHaveCount(2);
});
