<?php

namespace App\Livewire\Assessments;

use App\Livewire\Concerns\WithCircuitFilters;
use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\PermissionStatus;
use App\Models\PlannedUnitsSnapshot;
use App\Models\PlannerDailyAggregate;
use App\Models\PlannerWeeklyAggregate;
use App\Models\Region;
use App\Models\UnitType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Planner Analytics'])]
class PlannerAnalytics extends Component
{
    use WithCircuitFilters;

    #[Url]
    public string $dateRange = 'this_week';

    #[Url]
    public ?string $startDate = null;

    #[Url]
    public ?string $endDate = null;

    #[Url]
    public ?int $regionId = null;

    #[Url]
    public ?int $plannerId = null;

    /**
     * Get the date boundaries based on selected range.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    protected function getDateBounds(): array
    {
        $now = now();

        return match ($this->dateRange) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'this_week' => [
                'start' => $now->copy()->startOfWeek(Carbon::SUNDAY),
                'end' => $now->copy()->endOfWeek(Carbon::SATURDAY),
            ],
            'last_week' => [
                'start' => $now->copy()->subWeek()->startOfWeek(Carbon::SUNDAY),
                'end' => $now->copy()->subWeek()->endOfWeek(Carbon::SATURDAY),
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'last_30_days' => [
                'start' => $now->copy()->subDays(30)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'custom' => [
                'start' => $this->startDate ? Carbon::parse($this->startDate) : $now->copy()->startOfWeek(Carbon::SUNDAY),
                'end' => $this->endDate ? Carbon::parse($this->endDate) : $now->copy()->endOfWeek(Carbon::SATURDAY),
            ],
            default => [
                'start' => $now->copy()->startOfWeek(Carbon::SUNDAY),
                'end' => $now->copy()->endOfWeek(Carbon::SATURDAY),
            ],
        };
    }

    /**
     * Get the week ending dates for weekly aggregates.
     *
     * @return array{startWeek: Carbon, endWeek: Carbon}
     */
    protected function getWeekBounds(): array
    {
        $bounds = $this->getDateBounds();

        return [
            'startWeek' => PlannerWeeklyAggregate::getWeekEndingForDate($bounds['start']),
            'endWeek' => PlannerWeeklyAggregate::getWeekEndingForDate($bounds['end']),
        ];
    }

    /**
     * Get planner IDs that are included in analytics.
     * Respects global analytics settings for contractor filtering.
     *
     * @return array<int>
     */
    protected function getIncludedPlannerIds(): array
    {
        return User::query()
            ->role('planner')
            ->includedInAnalytics()
            ->withAllowedContractors()
            ->pluck('id')
            ->toArray();
    }

    /**
     * Summary stats for the dashboard cards.
     */
    #[Computed]
    public function summaryStats(): array
    {
        $weekBounds = $this->getWeekBounds();
        $includedPlannerIds = $this->getIncludedPlannerIds();

        if (empty($includedPlannerIds)) {
            return [
                'active_planners' => 0,
                'total_units' => 0,
                'miles_planned' => 0,
                'approval_rate' => 0,
                'units_approved' => 0,
                'units_refused' => 0,
                'units_pending' => 0,
            ];
        }

        $query = PlannerWeeklyAggregate::query()
            ->betweenWeeks($weekBounds['startWeek'], $weekBounds['endWeek'])
            ->whereIn('user_id', $includedPlannerIds);

        if ($this->regionId) {
            $query->inRegion($this->regionId);
        }

        if ($this->plannerId) {
            $query->forPlanner($this->plannerId);
        }

        $stats = $query->selectRaw('
            COUNT(DISTINCT user_id) as active_planners,
            COALESCE(SUM(total_units_assessed), 0) as total_units,
            COALESCE(SUM(miles_planned), 0) as miles_planned,
            COALESCE(SUM(units_approved), 0) as units_approved,
            COALESCE(SUM(units_refused), 0) as units_refused,
            COALESCE(SUM(units_pending), 0) as units_pending
        ')->first();

        $totalPermission = $stats->units_approved + $stats->units_refused + $stats->units_pending;
        $approvalRate = $totalPermission > 0
            ? round(($stats->units_approved / $totalPermission) * 100, 1)
            : 0;

        return [
            'active_planners' => (int) $stats->active_planners,
            'total_units' => (int) $stats->total_units,
            'miles_planned' => round((float) $stats->miles_planned, 1),
            'approval_rate' => $approvalRate,
            'units_approved' => (int) $stats->units_approved,
            'units_refused' => (int) $stats->units_refused,
            'units_pending' => (int) $stats->units_pending,
        ];
    }

    /**
     * Permission status breakdown for donut chart (simple 3-status).
     */
    #[Computed]
    public function permissionStatus(): array
    {
        $stats = $this->summaryStats;

        return [
            'approved' => $stats['units_approved'],
            'pending' => $stats['units_pending'],
            'refused' => $stats['units_refused'],
        ];
    }

    /**
     * Map DaisyUI semantic color names to hex values for ApexCharts.
     */
    protected function daisyColorToHex(string $daisyColor): string
    {
        return match ($daisyColor) {
            'primary' => '#570df8',
            'secondary' => '#f000b8',
            'accent' => '#37cdbe',
            'neutral' => '#3d4451',
            'info' => '#3abff8',
            'success' => '#36d399',
            'warning' => '#fbbd23',
            'error' => '#f87272',
            default => '#6b7280', // gray fallback
        };
    }

    /**
     * Full permission status breakdown (all 6 statuses) from snapshot data.
     * Uses the raw_json->summary->by_permission data from PlannedUnitsSnapshots.
     *
     * @return array<string, array{name: string, code: string, color: string, count: int, percentage: float}>
     */
    #[Computed]
    public function fullPermissionBreakdown(): array
    {
        $includedPlannerIds = $this->getIncludedPlannerIds();

        if (empty($includedPlannerIds)) {
            return [];
        }

        // Get circuits for the filtered planners (respecting global analytics settings)
        $circuitIds = Circuit::query()
            ->forAnalytics()
            ->whereHas('planners', fn ($q) => $q->whereIn('users.id', $includedPlannerIds))
            ->when($this->regionId, fn ($q) => $q->where('region_id', $this->regionId))
            ->pluck('id');

        if ($circuitIds->isEmpty()) {
            return [];
        }

        // Get latest snapshots for these circuits and aggregate permission counts
        $snapshots = PlannedUnitsSnapshot::query()
            ->whereIn('circuit_id', $circuitIds)
            ->whereIn('id', function ($sub) use ($circuitIds) {
                $sub->selectRaw('MAX(id)')
                    ->from('planned_units_snapshots')
                    ->whereIn('circuit_id', $circuitIds)
                    ->groupBy('circuit_id');
            })
            ->whereNotNull('raw_json')
            ->get();

        // Aggregate permission counts from snapshots
        $permissionCounts = [];
        foreach ($snapshots as $snapshot) {
            $byPermission = $snapshot->raw_json['summary']['by_permission'] ?? [];
            foreach ($byPermission as $status => $count) {
                // Normalize "Unknown" to "Pending"
                $normalizedStatus = $status === 'Unknown' ? '' : $status;
                $permissionCounts[$normalizedStatus] = ($permissionCounts[$normalizedStatus] ?? 0) + $count;
            }
        }

        // Get permission status definitions with colors
        $statuses = PermissionStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('code');

        $total = array_sum($permissionCounts);
        $result = [];

        foreach ($statuses as $code => $status) {
            $count = $permissionCounts[$code] ?? 0;
            $result[$code] = [
                'name' => $status->name,
                'code' => $code,
                'color' => $this->daisyColorToHex($status->color),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    /**
     * Unit type breakdown aggregated by category.
     * Groups units into Line Trimming (linear ft), Brush/Herbicide (acres), Tree Removal (trees).
     *
     * @return array<string, array{label: string, unit_label: string, total: float|int, count: int, types: array}>
     */
    #[Computed]
    public function unitTypeBreakdown(): array
    {
        $includedPlannerIds = $this->getIncludedPlannerIds();

        if (empty($includedPlannerIds)) {
            return [];
        }

        // Get circuits for the filtered planners (respecting global analytics settings)
        $circuitIds = Circuit::query()
            ->forAnalytics()
            ->whereHas('planners', fn ($q) => $q->whereIn('users.id', $includedPlannerIds))
            ->when($this->regionId, fn ($q) => $q->where('region_id', $this->regionId))
            ->pluck('id');

        if ($circuitIds->isEmpty()) {
            return [];
        }

        // Get latest snapshots for these circuits
        $snapshots = PlannedUnitsSnapshot::query()
            ->whereIn('circuit_id', $circuitIds)
            ->whereIn('id', function ($sub) use ($circuitIds) {
                $sub->selectRaw('MAX(id)')
                    ->from('planned_units_snapshots')
                    ->whereIn('circuit_id', $circuitIds)
                    ->groupBy('circuit_id');
            })
            ->whereNotNull('raw_json')
            ->get();

        // Aggregate unit counts by type from snapshots
        $unitCounts = [];
        $totals = ['trees' => 0, 'linear_ft' => 0, 'acres' => 0];

        foreach ($snapshots as $snapshot) {
            $byType = $snapshot->raw_json['summary']['by_unit_type'] ?? [];
            foreach ($byType as $typeCode => $count) {
                $unitCounts[$typeCode] = ($unitCounts[$typeCode] ?? 0) + $count;
            }
            $totals['trees'] += $snapshot->raw_json['summary']['total_trees'] ?? 0;
            $totals['linear_ft'] += $snapshot->raw_json['summary']['total_linear_ft'] ?? 0;
            $totals['acres'] += $snapshot->raw_json['summary']['total_acres'] ?? 0;
        }

        // Get unit type definitions grouped by category
        $unitTypes = UnitType::allByCode();
        $groups = UnitType::aggregationGroups();

        $result = [];
        foreach ($groups as $key => $group) {
            $categoryTypes = [];
            $categoryCount = 0;

            foreach ($group['codes'] as $code) {
                if (isset($unitCounts[$code]) && $unitCounts[$code] > 0) {
                    $type = $unitTypes->get($code);
                    $categoryTypes[$code] = [
                        'name' => $type?->name ?? $code,
                        'count' => $unitCounts[$code],
                    ];
                    $categoryCount += $unitCounts[$code];
                }
            }

            $totalValue = match ($group['measurement']) {
                UnitType::MEASUREMENT_TREE_COUNT => $totals['trees'],
                UnitType::MEASUREMENT_LINEAR_FT => round($totals['linear_ft'], 1),
                UnitType::MEASUREMENT_ACRES => round($totals['acres'], 2),
                default => 0,
            };

            $result[$key] = [
                'label' => $group['label'],
                'unit_label' => $group['unit_label'],
                'total' => $totalValue,
                'count' => $categoryCount,
                'types' => $categoryTypes,
            ];
        }

        return $result;
    }

    /**
     * Activity timestamps for planners.
     * Shows last activity, oldest pending items, etc.
     *
     * @return array{last_unit_created: ?Carbon, last_snapshot: ?Carbon, planner_activity: Collection}
     */
    #[Computed]
    public function activityTimestamps(): array
    {
        $includedPlannerIds = $this->getIncludedPlannerIds();

        if (empty($includedPlannerIds)) {
            return [
                'last_unit_created' => null,
                'last_snapshot' => null,
                'planner_activity' => collect(),
            ];
        }

        // Get last snapshot across all tracked circuits (respecting global analytics settings)
        $lastSnapshot = PlannedUnitsSnapshot::query()
            ->whereHas('circuit', fn ($q) => $q->forAnalytics()->whereHas('planners', fn ($pq) => $pq->whereIn('users.id', $includedPlannerIds)))
            ->when($this->regionId, fn ($q) => $q->whereHas('circuit', fn ($cq) => $cq->where('region_id', $this->regionId)))
            ->latest()
            ->first();

        // Get per-planner activity stats
        $plannerActivity = collect();

        if ($this->plannerId) {
            // Single planner view - get detailed activity
            $planner = User::find($this->plannerId);
            if ($planner) {
                $circuits = $planner->circuits()
                    ->when($this->regionId, fn ($q) => $q->where('region_id', $this->regionId))
                    ->get();

                $circuitIds = $circuits->pluck('id');

                // Get latest snapshot for this planner's circuits
                $latestSnapshot = PlannedUnitsSnapshot::query()
                    ->whereIn('circuit_id', $circuitIds)
                    ->latest()
                    ->first();

                // Find circuits by status
                $activeCircuits = $circuits->where('api_status', 'ACTIV')->count();
                $qcCircuits = $circuits->where('api_status', 'QC')->count();
                $closedCircuits = $circuits->where('api_status', 'CLOSE')->count();

                // Get oldest circuit that's still in progress
                $oldestInProgress = $circuits
                    ->where('api_status', 'ACTIV')
                    ->where('miles_planned', '>', 0)
                    ->sortBy('api_modified_at')
                    ->first();

                $plannerActivity = collect([
                    'planner_id' => $this->plannerId,
                    'planner_name' => $planner->name,
                    'last_snapshot' => $latestSnapshot?->created_at,
                    'active_circuits' => $activeCircuits,
                    'qc_circuits' => $qcCircuits,
                    'closed_circuits' => $closedCircuits,
                    'oldest_in_progress' => $oldestInProgress?->api_modified_at,
                    'oldest_in_progress_wo' => $oldestInProgress?->display_work_order,
                ]);
            }
        }

        return [
            'last_unit_created' => $lastSnapshot?->created_at,
            'last_snapshot' => $lastSnapshot?->created_at,
            'planner_activity' => $plannerActivity,
        ];
    }

    /**
     * Available permission statuses for display.
     *
     * @return Collection<int, PermissionStatus>
     */
    #[Computed]
    public function availablePermissionStatuses(): Collection
    {
        return PermissionStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Weekly miles target metrics for dashboard.
     * Shows each planner's progress toward the 6.5 mi/week target.
     */
    #[Computed]
    public function weeklyTargetMetrics(): Collection
    {
        $weekBounds = $this->getWeekBounds();
        $includedPlannerIds = $this->getIncludedPlannerIds();

        if (empty($includedPlannerIds)) {
            return collect();
        }

        $query = PlannerWeeklyAggregate::query()
            ->forWeekEnding($weekBounds['endWeek'])
            ->whereIn('user_id', $includedPlannerIds)
            ->with('user:id,name');

        if ($this->regionId) {
            $query->inRegion($this->regionId);
        }

        if ($this->plannerId) {
            $query->forPlanner($this->plannerId);
        }

        return $query
            ->select([
                'user_id',
                DB::raw('SUM(miles_planned_start) as miles_start'),
                DB::raw('SUM(miles_planned_end) as miles_end'),
                DB::raw('SUM(miles_delta) as miles_delta'),
                DB::raw('CASE WHEN SUM(miles_delta) >= '.PlannerWeeklyAggregate::WEEKLY_MILES_TARGET.' THEN true ELSE false END as met_target'),
            ])
            ->groupBy('user_id')
            ->orderByDesc('miles_delta')
            ->get()
            ->map(function ($row) {
                $target = PlannerWeeklyAggregate::WEEKLY_MILES_TARGET;
                $delta = (float) $row->miles_delta;
                $percentage = $target > 0 ? min(100, round(($delta / $target) * 100, 1)) : 0;

                return [
                    'user_id' => $row->user_id,
                    'name' => $row->user?->name ?? 'Unknown',
                    'miles_start' => round((float) $row->miles_start, 2),
                    'miles_end' => round((float) $row->miles_end, 2),
                    'miles_delta' => round($delta, 2),
                    'target' => $target,
                    'target_percentage' => $percentage,
                    'met_target' => (bool) $row->met_target || $delta >= $target,
                    'miles_remaining' => max(0, round($target - $delta, 2)),
                ];
            });
    }

    /**
     * Summary of weekly target achievement.
     */
    #[Computed]
    public function weeklyTargetSummary(): array
    {
        $metrics = $this->weeklyTargetMetrics;

        if ($metrics->isEmpty()) {
            return [
                'total_planners' => 0,
                'met_target' => 0,
                'below_target' => 0,
                'avg_delta' => 0,
                'target' => PlannerWeeklyAggregate::WEEKLY_MILES_TARGET,
            ];
        }

        return [
            'total_planners' => $metrics->count(),
            'met_target' => $metrics->where('met_target', true)->count(),
            'below_target' => $metrics->where('met_target', false)->count(),
            'avg_delta' => round($metrics->avg('miles_delta'), 2),
            'target' => PlannerWeeklyAggregate::WEEKLY_MILES_TARGET,
        ];
    }

    /**
     * Planner metrics for leaderboard table.
     */
    #[Computed]
    public function plannerMetrics(): Collection
    {
        $weekBounds = $this->getWeekBounds();
        $includedPlannerIds = $this->getIncludedPlannerIds();

        if (empty($includedPlannerIds)) {
            return collect();
        }

        $query = PlannerWeeklyAggregate::query()
            ->betweenWeeks($weekBounds['startWeek'], $weekBounds['endWeek'])
            ->whereIn('user_id', $includedPlannerIds)
            ->with('user:id,name,ws_username');

        if ($this->regionId) {
            $query->inRegion($this->regionId);
        }

        if ($this->plannerId) {
            $query->forPlanner($this->plannerId);
        }

        return $query
            ->select([
                'user_id',
                DB::raw('SUM(total_units_assessed) as total_units'),
                DB::raw('SUM(circuits_worked) as circuits_worked'),
                DB::raw('SUM(days_worked) as days_worked'),
                DB::raw('SUM(miles_planned) as miles_planned'),
                DB::raw('SUM(miles_delta) as miles_delta'),
                DB::raw('SUM(units_approved) as units_approved'),
                DB::raw('SUM(units_refused) as units_refused'),
                DB::raw('SUM(units_pending) as units_pending'),
            ])
            ->groupBy('user_id')
            ->orderByDesc('total_units')
            ->get()
            ->map(function ($row, $index) {
                $totalPermission = $row->units_approved + $row->units_refused + $row->units_pending;
                $approvalRate = $totalPermission > 0
                    ? round(($row->units_approved / $totalPermission) * 100, 1)
                    : 0;

                $avgDaily = $row->days_worked > 0
                    ? round($row->total_units / $row->days_worked, 1)
                    : 0;

                $milesDelta = round((float) ($row->miles_delta ?? 0), 1);
                $metTarget = $milesDelta >= PlannerWeeklyAggregate::WEEKLY_MILES_TARGET;

                return [
                    'rank' => $index + 1,
                    'user_id' => $row->user_id,
                    'name' => $row->user?->name ?? 'Unknown',
                    'ws_username' => $row->user?->ws_username,
                    'circuits' => (int) $row->circuits_worked,
                    'total_units' => (int) $row->total_units,
                    'days_worked' => (int) $row->days_worked,
                    'avg_daily' => $avgDaily,
                    'miles_planned' => round((float) $row->miles_planned, 1),
                    'miles_delta' => $milesDelta,
                    'met_target' => $metTarget,
                    'approval_rate' => $approvalRate,
                    'units_approved' => (int) $row->units_approved,
                    'units_refused' => (int) $row->units_refused,
                    'units_pending' => (int) $row->units_pending,
                ];
            });
    }

    /**
     * Daily progression data for line chart.
     */
    #[Computed]
    public function progressionData(): array
    {
        $bounds = $this->getDateBounds();
        $includedPlannerIds = $this->getIncludedPlannerIds();

        if (empty($includedPlannerIds)) {
            return [
                'dates' => [],
                'units' => [],
                'miles' => [],
            ];
        }

        $query = PlannerDailyAggregate::query()
            ->betweenDates($bounds['start']->format('Y-m-d'), $bounds['end']->format('Y-m-d'))
            ->whereIn('user_id', $includedPlannerIds);

        if ($this->regionId) {
            $query->inRegion($this->regionId);
        }

        if ($this->plannerId) {
            $query->forPlanner($this->plannerId);
        }

        $data = $query
            ->select([
                'aggregate_date',
                DB::raw('SUM(total_units_assessed) as total_units'),
                DB::raw('SUM(miles_planned) as miles_planned'),
            ])
            ->groupBy('aggregate_date')
            ->orderBy('aggregate_date')
            ->get();

        return [
            'dates' => $data->pluck('aggregate_date')->map(fn ($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'units' => $data->pluck('total_units')->map(fn ($v) => (int) $v)->toArray(),
            'miles' => $data->pluck('miles_planned')->map(fn ($v) => round((float) $v, 1))->toArray(),
        ];
    }

    /**
     * Circuit breakdown when a planner is selected.
     */
    #[Computed]
    public function circuitBreakdown(): Collection
    {
        if (! $this->plannerId) {
            return collect();
        }

        $user = User::find($this->plannerId);
        if (! $user) {
            return collect();
        }

        $query = $user->circuits()
            ->forAnalytics()
            ->with(['region:id,name', 'latestAggregate']);

        // Apply circuit filters (status/cycle from URL)
        $this->applyCircuitFilters($query);

        return $query->get()
            ->map(function ($circuit) {
                $aggregate = $circuit->latestAggregate;

                $approvalRate = 0;
                if ($aggregate) {
                    $total = $aggregate->units_approved + $aggregate->units_refused + $aggregate->units_pending;
                    $approvalRate = $total > 0
                        ? round(($aggregate->units_approved / $total) * 100, 1)
                        : 0;
                }

                return [
                    'id' => $circuit->id,
                    'work_order' => $circuit->display_work_order,
                    'title' => $circuit->title,
                    'region' => $circuit->region?->name ?? 'Unknown',
                    'status' => $circuit->api_status,
                    'cycle_type' => $circuit->cycle_type,
                    'total_miles' => round($circuit->total_miles ?? 0, 1),
                    'miles_planned' => round($circuit->miles_planned ?? 0, 1),
                    'total_units' => $aggregate?->total_units ?? 0,
                    'approval_rate' => $approvalRate,
                    'last_modified' => $circuit->api_modified_at,
                ];
            });
    }

    /**
     * Selected planner details.
     */
    #[Computed]
    public function selectedPlanner(): ?User
    {
        return $this->plannerId
            ? User::find($this->plannerId)
            : null;
    }

    /**
     * Get the current global analytics settings for display.
     */
    #[Computed]
    public function globalSettings(): AnalyticsSetting
    {
        return AnalyticsSetting::instance();
    }

    /**
     * Available regions for filter dropdown.
     */
    #[Computed]
    public function regions(): Collection
    {
        return Region::query()
            ->active()
            ->ordered()
            ->get(['id', 'name']);
    }

    /**
     * Available planners for filter dropdown.
     * Respects global analytics settings for contractor filtering.
     */
    #[Computed]
    public function planners(): Collection
    {
        return User::query()
            ->role('planner')
            ->includedInAnalytics()
            ->withAllowedContractors()
            ->orderBy('name')
            ->get(['id', 'name', 'ws_username']);
    }

    /**
     * Filter by a specific planner.
     */
    public function filterByPlanner(int $plannerId): void
    {
        $this->plannerId = $plannerId;
        $this->clearComputedCache();
    }

    /**
     * Clear the planner filter.
     */
    public function clearPlannerFilter(): void
    {
        $this->plannerId = null;
        $this->clearComputedCache();
    }

    /**
     * Clear all filters and reset to defaults.
     */
    public function clearAllFilters(): void
    {
        $this->regionId = null;
        $this->plannerId = null;
        $this->dateRange = 'this_week';
        $this->startDate = null;
        $this->endDate = null;
        $this->clearComputedCache();
    }

    /**
     * Clear computed property caches when filters change.
     */
    protected function clearComputedCache(): void
    {
        unset(
            $this->summaryStats,
            $this->permissionStatus,
            $this->fullPermissionBreakdown,
            $this->unitTypeBreakdown,
            $this->activityTimestamps,
            $this->weeklyTargetMetrics,
            $this->weeklyTargetSummary,
            $this->plannerMetrics,
            $this->progressionData,
            $this->circuitBreakdown,
            $this->selectedPlanner
        );
    }

    /**
     * Handle filter changes.
     */
    public function updatedDateRange(): void
    {
        $this->clearComputedCache();
    }

    public function updatedRegionId(): void
    {
        $this->clearComputedCache();
    }

    public function updatedPlannerId(): void
    {
        $this->clearComputedCache();
    }

    public function updatedStartDate(): void
    {
        $this->clearComputedCache();
    }

    public function updatedEndDate(): void
    {
        $this->clearComputedCache();
    }

    /**
     * Clear computed caches when circuit filters change.
     */
    protected function onCircuitFiltersUpdated(): void
    {
        unset(
            $this->circuitBreakdown,
            $this->availableCycleTypes
        );
    }

    public function render()
    {
        return view('livewire.assessments.planner-analytics');
    }
}
