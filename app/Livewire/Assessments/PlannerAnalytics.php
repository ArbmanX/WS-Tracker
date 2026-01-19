<?php

namespace App\Livewire\Assessments;

use App\Livewire\Concerns\WithCircuitFilters;
use App\Models\Circuit;
use App\Models\PlannerDailyAggregate;
use App\Models\PlannerWeeklyAggregate;
use App\Models\Region;
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
     *
     * @return array<int>
     */
    protected function getIncludedPlannerIds(): array
    {
        return User::query()
            ->role('planner')
            ->includedInAnalytics()
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
     * Permission status breakdown for donut chart.
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
            ->with(['region:id,name', 'latestAggregate']);

        // Apply circuit filters
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
                    'last_modified' => $circuit->api_modified_date,
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
     */
    #[Computed]
    public function planners(): Collection
    {
        return User::query()
            ->role('planner')
            ->includedInAnalytics()
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
