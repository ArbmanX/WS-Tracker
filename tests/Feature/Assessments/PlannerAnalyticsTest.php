<?php

use App\Livewire\Assessments\PlannerAnalytics;
use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\PlannerDailyAggregate;
use App\Models\PlannerWeeklyAggregate;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();

    // Set global analytics settings to be permissive for tests
    AnalyticsSetting::instance()->update([
        'scope_year' => date('Y'),
        'selected_cycle_types' => null,
        'selected_contractors' => null,
    ]);
});

it('renders planner analytics page for authenticated users', function () {
    actingAs($this->user)
        ->get(route('assessments.planner-analytics'))
        ->assertSuccessful()
        ->assertSeeLivewire(PlannerAnalytics::class);
});

it('redirects unauthenticated users to login', function () {
    $this->get(route('assessments.planner-analytics'))
        ->assertRedirect(route('login'));
});

it('displays summary stats from weekly aggregates', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();
    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    PlannerWeeklyAggregate::factory()
        ->forUser($planner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create([
            'total_units_assessed' => 500,
            'miles_planned' => 25.5,
            'units_approved' => 400,
            'units_refused' => 50,
            'units_pending' => 50,
        ]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $stats = $component->get('summaryStats');

    expect($stats['active_planners'])->toBe(1);
    expect($stats['total_units'])->toBe(500);
    expect($stats['miles_planned'])->toBe(25.5);
    expect($stats['approval_rate'])->toBe(80.0); // 400 / 500 = 80%
});

it('excludes planners marked for exclusion from stats', function () {
    $includedPlanner = User::factory()->create([
        'name' => 'Included Planner',
        'is_excluded_from_analytics' => false,
    ]);
    $includedPlanner->assignRole('planner');

    $excludedPlanner = User::factory()->create([
        'name' => 'Excluded Planner',
        'is_excluded_from_analytics' => true,
        'exclusion_reason' => 'Test account',
    ]);
    $excludedPlanner->assignRole('planner');

    $region = Region::factory()->create();
    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    // Aggregate for included planner
    PlannerWeeklyAggregate::factory()
        ->forUser($includedPlanner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 100]);

    // Aggregate for excluded planner (should not count)
    PlannerWeeklyAggregate::factory()
        ->forUser($excludedPlanner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 500]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $stats = $component->get('summaryStats');

    expect($stats['active_planners'])->toBe(1);
    expect($stats['total_units'])->toBe(100); // Only included planner's units
});

it('shows planner leaderboard sorted by units', function () {
    $region = Region::factory()->create();
    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    // Create three planners with different productivity
    $topPlanner = User::factory()->create(['name' => 'Top Performer', 'is_excluded_from_analytics' => false]);
    $topPlanner->assignRole('planner');
    PlannerWeeklyAggregate::factory()
        ->forUser($topPlanner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 300, 'days_worked' => 5]);

    $middlePlanner = User::factory()->create(['name' => 'Middle Performer', 'is_excluded_from_analytics' => false]);
    $middlePlanner->assignRole('planner');
    PlannerWeeklyAggregate::factory()
        ->forUser($middlePlanner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 200, 'days_worked' => 5]);

    $bottomPlanner = User::factory()->create(['name' => 'Bottom Performer', 'is_excluded_from_analytics' => false]);
    $bottomPlanner->assignRole('planner');
    PlannerWeeklyAggregate::factory()
        ->forUser($bottomPlanner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 100, 'days_worked' => 5]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $metrics = $component->get('plannerMetrics');

    expect($metrics)->toHaveCount(3);
    expect($metrics[0]['name'])->toBe('Top Performer');
    expect($metrics[0]['rank'])->toBe(1);
    expect($metrics[1]['name'])->toBe('Middle Performer');
    expect($metrics[1]['rank'])->toBe(2);
    expect($metrics[2]['name'])->toBe('Bottom Performer');
    expect($metrics[2]['rank'])->toBe(3);
});

it('calculates average daily units correctly', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();
    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    PlannerWeeklyAggregate::factory()
        ->forUser($planner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create([
            'total_units_assessed' => 350,
            'days_worked' => 5,
        ]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $metrics = $component->get('plannerMetrics');

    expect($metrics)->toHaveCount(1);
    expect($metrics[0]['avg_daily'])->toBe(70.0); // 350 / 5 = 70
});

it('filters by date range - this week', function () {
    Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->assertSet('dateRange', 'this_week')
        ->set('dateRange', 'last_week')
        ->assertSet('dateRange', 'last_week');
});

it('filters by region', function () {
    $region1 = Region::factory()->create(['name' => 'Region One']);
    $region2 = Region::factory()->create(['name' => 'Region Two']);

    // Need two different planners to avoid unique constraint on user_id + week_ending
    $planner1 = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner1->assignRole('planner');

    $planner2 = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner2->assignRole('planner');

    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    // Aggregate in region 1
    PlannerWeeklyAggregate::factory()
        ->forUser($planner1)
        ->forRegion($region1)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 100]);

    // Aggregate in region 2
    PlannerWeeklyAggregate::factory()
        ->forUser($planner2)
        ->forRegion($region2)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 200]);

    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->set('regionId', $region1->id);

    $stats = $component->get('summaryStats');
    expect($stats['total_units'])->toBe(100); // Only region 1
});

it('filters by planner', function () {
    $planner1 = User::factory()->create(['name' => 'Planner One', 'is_excluded_from_analytics' => false]);
    $planner1->assignRole('planner');

    $planner2 = User::factory()->create(['name' => 'Planner Two', 'is_excluded_from_analytics' => false]);
    $planner2->assignRole('planner');

    $region = Region::factory()->create();
    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    PlannerWeeklyAggregate::factory()
        ->forUser($planner1)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 150]);

    PlannerWeeklyAggregate::factory()
        ->forUser($planner2)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create(['total_units_assessed' => 250]);

    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->call('filterByPlanner', $planner1->id);

    expect($component->get('plannerId'))->toBe($planner1->id);
    $stats = $component->get('summaryStats');
    expect($stats['total_units'])->toBe(150);
});

it('clears planner filter', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->call('filterByPlanner', $planner->id)
        ->assertSet('plannerId', $planner->id)
        ->call('clearPlannerFilter')
        ->assertSet('plannerId', null);
});

it('shows circuit breakdown when planner selected', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();

    $circuit = Circuit::factory()->forRegion($region)->create([
        'work_order' => date('Y').'-12345',
        'extension' => '@',
    ]);

    $planner->circuits()->attach($circuit);

    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->call('filterByPlanner', $planner->id);

    $breakdown = $component->get('circuitBreakdown');
    expect($breakdown)->toHaveCount(1);
    expect($breakdown[0]['work_order'])->toBe(date('Y').'-12345');
});

it('hides circuit breakdown when no planner selected', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class);

    $breakdown = $component->get('circuitBreakdown');
    expect($breakdown)->toBeEmpty();
});

it('provides progression data for chart', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();

    // Create daily aggregates for the current week
    PlannerDailyAggregate::factory()
        ->forUser($planner)
        ->forRegion($region)
        ->forDate(now()->format('Y-m-d'))
        ->create([
            'total_units_assessed' => 50,
            'miles_planned' => 5.5,
        ]);

    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class);

    $progression = $component->get('progressionData');

    expect($progression)->toHaveKey('dates');
    expect($progression)->toHaveKey('units');
    expect($progression)->toHaveKey('miles');
    expect($progression['dates'])->not->toBeEmpty();
});

it('calculates approval rate correctly', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();
    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    PlannerWeeklyAggregate::factory()
        ->forUser($planner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create([
            'units_approved' => 80,
            'units_refused' => 10,
            'units_pending' => 10,
        ]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $stats = $component->get('summaryStats');

    // Approval rate = 80 / (80 + 10 + 10) = 80%
    expect($stats['approval_rate'])->toBe(80.0);
});

it('provides permission status breakdown for donut chart', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();
    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    PlannerWeeklyAggregate::factory()
        ->forUser($planner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create([
            'units_approved' => 100,
            'units_refused' => 20,
            'units_pending' => 30,
        ]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $permission = $component->get('permissionStatus');

    expect($permission['approved'])->toBe(100);
    expect($permission['pending'])->toBe(30);
    expect($permission['refused'])->toBe(20);
});

it('persists filters in URL', function () {
    $region = Region::factory()->create();
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    // Test URL parameters are respected
    Livewire::actingAs($this->user)
        ->withQueryParams([
            'dateRange' => 'last_week',
            'regionId' => $region->id,
            'plannerId' => $planner->id,
        ])
        ->test(PlannerAnalytics::class)
        ->assertSet('dateRange', 'last_week')
        ->assertSet('regionId', $region->id)
        ->assertSet('plannerId', $planner->id);
});

it('handles custom date range', function () {
    Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->set('dateRange', 'custom')
        ->set('startDate', '2025-01-01')
        ->set('endDate', '2025-01-15')
        ->assertSet('dateRange', 'custom')
        ->assertSet('startDate', '2025-01-01')
        ->assertSet('endDate', '2025-01-15');
});

it('shows empty state when no data', function () {
    Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->assertSee('No planner data available');
});

it('provides regions for filter dropdown', function () {
    Region::factory()->count(3)->create(['is_active' => true]);
    Region::factory()->inactive()->create(); // Should not appear

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $regions = $component->get('regions');

    expect($regions)->toHaveCount(3);
});

it('provides planners for filter dropdown', function () {
    $includedPlanner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $includedPlanner->assignRole('planner');

    $excludedPlanner = User::factory()->create(['is_excluded_from_analytics' => true]);
    $excludedPlanner->assignRole('planner');

    $nonPlanner = User::factory()->create(['is_excluded_from_analytics' => false]);
    // No planner role assigned

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $planners = $component->get('planners');

    // Should only include non-excluded planners
    expect($planners)->toHaveCount(1);
    expect($planners->first()->id)->toBe($includedPlanner->id);
});

it('aggregates multiple weeks within date range', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();

    // Create aggregates for two consecutive weeks
    $week1 = PlannerWeeklyAggregate::getWeekEndingForDate(now());
    $week2 = PlannerWeeklyAggregate::getWeekEndingForDate(now()->subWeek());

    PlannerWeeklyAggregate::factory()
        ->forUser($planner)
        ->forRegion($region)
        ->forWeekEnding($week1)
        ->create(['total_units_assessed' => 100]);

    PlannerWeeklyAggregate::factory()
        ->forUser($planner)
        ->forRegion($region)
        ->forWeekEnding($week2)
        ->create(['total_units_assessed' => 150]);

    // Query for last 30 days (should include both weeks)
    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->set('dateRange', 'last_30_days');

    $stats = $component->get('summaryStats');
    expect($stats['total_units'])->toBe(250); // 100 + 150
});

it('handles zero days worked gracefully for avg daily calculation', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();
    $weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());

    PlannerWeeklyAggregate::factory()
        ->forUser($planner)
        ->forRegion($region)
        ->forWeekEnding($weekEnding)
        ->create([
            'total_units_assessed' => 100,
            'days_worked' => 0, // Edge case
        ]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $metrics = $component->get('plannerMetrics');

    expect($metrics)->toHaveCount(1);
    expect($metrics[0]['avg_daily'])->toEqual(0); // Should not divide by zero
});

// Phase 5: Full Permission Status Breakdown Tests

it('provides full permission breakdown from snapshot data', function () {
    $this->seed(\Database\Seeders\PermissionStatusesSeeder::class);

    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->forRegion($region)->create();
    $planner->circuits()->attach($circuit);

    // Create a snapshot with specific permission breakdown
    \App\Models\PlannedUnitsSnapshot::factory()
        ->forCircuit($circuit)
        ->create([
            'raw_json' => [
                'summary' => [
                    'by_permission' => [
                        'Approved' => 50,
                        'Refused' => 10,
                        'No Contact' => 15,
                        'Deferred' => 5,
                        'Unknown' => 20, // This maps to Pending
                    ],
                    'by_unit_type' => [],
                    'total_trees' => 0,
                    'total_linear_ft' => 0,
                    'total_acres' => 0,
                ],
                'units' => [],
            ],
        ]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $breakdown = $component->get('fullPermissionBreakdown');

    expect($breakdown)->toBeArray();
    expect($breakdown)->not->toBeEmpty();

    // Should have counts aggregated
    $approved = collect($breakdown)->firstWhere('name', 'Approved');
    if ($approved) {
        expect($approved['count'])->toBe(50);
    }
});

it('returns empty full permission breakdown when no planners included', function () {
    // No planners created or all excluded
    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $breakdown = $component->get('fullPermissionBreakdown');

    expect($breakdown)->toBeEmpty();
});

// Phase 5: Unit Type Breakdown Tests

it('provides unit type breakdown grouped by category', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->forRegion($region)->create();
    $planner->circuits()->attach($circuit);

    // Create a snapshot with unit type breakdown
    \App\Models\PlannedUnitsSnapshot::factory()
        ->forCircuit($circuit)
        ->create([
            'raw_json' => [
                'summary' => [
                    'by_permission' => [],
                    'by_unit_type' => [
                        'SPM' => 30,  // Line trimming
                        'HCB' => 20,  // Brush/herbicide
                        'RMV' => 15,  // Tree removal
                    ],
                    'total_trees' => 15,
                    'total_linear_ft' => 500.5,
                    'total_acres' => 2.5,
                ],
                'units' => [],
            ],
        ]);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $breakdown = $component->get('unitTypeBreakdown');

    expect($breakdown)->toBeArray();
    // Should have the three main categories (keys from UnitType::aggregationGroups())
    expect($breakdown)->toHaveKeys(['trim_line', 'brush_area', 'tree_removal']);

    // Check tree removal category has the total
    expect($breakdown['tree_removal']['total'])->toBe(15);
    expect($breakdown['trim_line']['total'])->toEqual(500.5);
    expect($breakdown['brush_area']['total'])->toEqual(2.5);
});

it('returns empty unit type breakdown when no planners', function () {
    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $breakdown = $component->get('unitTypeBreakdown');

    expect($breakdown)->toBeEmpty();
});

// Phase 5: Activity Timestamps Tests

it('provides activity timestamps when planner selected', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();
    $circuit = Circuit::factory()->forRegion($region)->create([
        'api_status' => 'ACTIV',
        'miles_planned' => 5.5, // In progress
        'api_modified_date' => now()->subDays(5),
    ]);
    $planner->circuits()->attach($circuit);

    // Create a recent snapshot
    \App\Models\PlannedUnitsSnapshot::factory()
        ->forCircuit($circuit)
        ->create();

    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->call('filterByPlanner', $planner->id);

    $activity = $component->get('activityTimestamps');

    expect($activity)->toHaveKeys(['last_unit_created', 'last_snapshot', 'planner_activity']);
    expect($activity['planner_activity'])->not->toBeEmpty();
    expect($activity['planner_activity']['active_circuits'])->toBe(1);
});

it('tracks oldest in-progress circuit for planner', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();

    // Create two active circuits with different ages
    $olderCircuit = Circuit::factory()->forRegion($region)->create([
        'work_order' => '2025-1111',
        'extension' => '@',
        'api_status' => 'ACTIV',
        'miles_planned' => 3.0, // In progress
        'api_modified_date' => now()->subDays(30),
    ]);

    $newerCircuit = Circuit::factory()->forRegion($region)->create([
        'work_order' => '2025-2222',
        'extension' => '@',
        'api_status' => 'ACTIV',
        'miles_planned' => 1.0, // In progress
        'api_modified_date' => now()->subDays(5),
    ]);

    $planner->circuits()->attach([$olderCircuit->id, $newerCircuit->id]);

    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->call('filterByPlanner', $planner->id);

    $activity = $component->get('activityTimestamps');

    // Should find the older circuit as oldest in progress
    expect($activity['planner_activity']['oldest_in_progress_wo'])->toBe('2025-1111');
});

it('returns empty activity when no planner selected', function () {
    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $activity = $component->get('activityTimestamps');

    expect($activity['last_unit_created'])->toBeNull();
    expect($activity['planner_activity'])->toBeEmpty();
});

it('counts circuit statuses correctly in activity', function () {
    $planner = User::factory()->create(['is_excluded_from_analytics' => false]);
    $planner->assignRole('planner');

    $region = Region::factory()->create();

    // Create circuits in different statuses
    $activeCircuit = Circuit::factory()->forRegion($region)->create(['api_status' => 'ACTIV']);
    $qcCircuit = Circuit::factory()->forRegion($region)->create(['api_status' => 'QC']);
    $closedCircuit1 = Circuit::factory()->forRegion($region)->create(['api_status' => 'CLOSE']);
    $closedCircuit2 = Circuit::factory()->forRegion($region)->create(['api_status' => 'CLOSE']);

    $planner->circuits()->attach([
        $activeCircuit->id,
        $qcCircuit->id,
        $closedCircuit1->id,
        $closedCircuit2->id,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(PlannerAnalytics::class)
        ->call('filterByPlanner', $planner->id);

    $activity = $component->get('activityTimestamps');

    expect($activity['planner_activity']['active_circuits'])->toBe(1);
    expect($activity['planner_activity']['qc_circuits'])->toBe(1);
    expect($activity['planner_activity']['closed_circuits'])->toBe(2);
});

// Phase 5: Available Permission Statuses Test

it('provides available permission statuses', function () {
    $this->seed(\Database\Seeders\PermissionStatusesSeeder::class);

    $component = Livewire::actingAs($this->user)->test(PlannerAnalytics::class);
    $statuses = $component->get('availablePermissionStatuses');

    expect($statuses)->not->toBeEmpty();
    expect($statuses->first())->toHaveKeys(['name', 'code', 'color']);
});
