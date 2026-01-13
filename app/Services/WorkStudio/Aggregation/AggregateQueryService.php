<?php

namespace App\Services\WorkStudio\Aggregation;

use App\Models\CircuitAggregate;
use App\Models\PlannerDailyAggregate;
use App\Models\PlannerWeeklyAggregate;
use App\Models\Region;
use App\Models\RegionalDailyAggregate;
use App\Models\RegionalWeeklyAggregate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for querying aggregate data across hierarchy levels.
 *
 * Provides query methods for dashboard and reporting use cases.
 */
class AggregateQueryService
{
    /**
     * Get global summary (all regions combined).
     *
     * @param  string|null  $date  Date to query (defaults to latest)
     * @return array Global summary data
     */
    public function getGlobalSummary(?string $date = null): array
    {
        $query = RegionalDailyAggregate::query();

        if ($date) {
            $query->whereDate('aggregate_date', $date);
        } else {
            // Get latest date's data
            $query->whereIn('id', function ($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('regional_daily_aggregates')
                    ->groupBy('region_id');
            });
        }

        $regionals = $query->get();

        return [
            'total_circuits' => $regionals->sum('total_circuits'),
            'active_planners' => $regionals->sum('active_planners'),
            'total_units' => $regionals->sum('total_units'),
            'total_linear_ft' => round($regionals->sum('total_linear_ft'), 2),
            'total_acres' => round($regionals->sum('total_acres'), 4),
            'total_trees' => $regionals->sum('total_trees'),
            'units_approved' => $regionals->sum('units_approved'),
            'units_refused' => $regionals->sum('units_refused'),
            'units_pending' => $regionals->sum('units_pending'),
            'unit_counts_by_type' => $this->mergeUnitCountsByType($regionals),
            'regions_count' => $regionals->count(),
            'as_of_date' => $regionals->max('aggregate_date'),
        ];
    }

    /**
     * Get regional summary.
     *
     * @param  int  $regionId  Region ID
     * @param  string|null  $date  Date to query
     * @return array|null Regional summary or null if not found
     */
    public function getRegionalSummary(int $regionId, ?string $date = null): ?array
    {
        $query = RegionalDailyAggregate::where('region_id', $regionId);

        if ($date) {
            $query->whereDate('aggregate_date', $date);
        } else {
            $query->orderBy('aggregate_date', 'desc');
        }

        $aggregate = $query->first();

        if (! $aggregate) {
            return null;
        }

        return $aggregate->toArray();
    }

    /**
     * Get all regions summary with regional breakdowns.
     *
     * @param  string|null  $date  Date to query
     */
    public function getAllRegionsSummary(?string $date = null): Collection
    {
        $query = RegionalDailyAggregate::with('region');

        if ($date) {
            $query->whereDate('aggregate_date', $date);
        } else {
            $query->whereIn('id', function ($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('regional_daily_aggregates')
                    ->groupBy('region_id');
            });
        }

        return $query->get()->map(fn ($agg) => [
            'region_id' => $agg->region_id,
            'region_name' => $agg->region->name,
            'region_code' => $agg->region->code,
            ...$agg->only([
                'total_circuits',
                'active_planners',
                'total_units',
                'total_linear_ft',
                'total_acres',
                'total_trees',
                'units_approved',
                'units_refused',
                'units_pending',
            ]),
            'aggregate_date' => $agg->aggregate_date,
        ]);
    }

    /**
     * Get circuit aggregate by circuit ID.
     *
     * @param  int  $circuitId  Circuit ID
     * @param  string|null  $date  Date to query (null for latest)
     */
    public function getCircuitAggregate(int $circuitId, ?string $date = null): ?CircuitAggregate
    {
        $query = CircuitAggregate::where('circuit_id', $circuitId)
            ->where('is_rollup', false);

        if ($date) {
            $query->whereDate('aggregate_date', $date);
        } else {
            $query->orderBy('aggregate_date', 'desc');
        }

        return $query->first();
    }

    /**
     * Get circuits aggregates for a region.
     *
     * @param  int  $regionId  Region ID
     * @param  string|null  $date  Date to query (null for latest)
     */
    public function getCircuitsByRegion(int $regionId, ?string $date = null): Collection
    {
        $query = CircuitAggregate::query()
            ->where('is_rollup', false)
            ->whereHas('circuit', fn ($q) => $q->where('region_id', $regionId));

        if ($date) {
            $query->whereDate('aggregate_date', $date);
        } else {
            // Get latest aggregate for each circuit
            $query->whereIn('id', function ($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('circuit_aggregates')
                    ->where('is_rollup', false)
                    ->groupBy('circuit_id');
            });
        }

        return $query->with('circuit')->get();
    }

    /**
     * Get planner productivity summary.
     *
     * @param  int  $userId  User (planner) ID
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     */
    public function getPlannerProductivity(int $userId, string $fromDate, string $toDate): array
    {
        $aggregates = PlannerDailyAggregate::where('user_id', $userId)
            ->whereDate('aggregate_date', '>=', $fromDate)
            ->whereDate('aggregate_date', '<=', $toDate)
            ->orderBy('aggregate_date')
            ->get();

        if ($aggregates->isEmpty()) {
            return [
                'user_id' => $userId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'days_worked' => 0,
                'totals' => [],
                'daily' => [],
            ];
        }

        return [
            'user_id' => $userId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'days_worked' => $aggregates->count(),
            'totals' => [
                'circuits_worked' => $aggregates->unique('circuits_list', true)->flatten()->unique()->count(),
                'total_units' => $aggregates->sum('total_units_assessed'),
                'total_linear_ft' => round($aggregates->sum('total_linear_ft'), 2),
                'total_acres' => round($aggregates->sum('total_acres'), 4),
                'units_approved' => $aggregates->sum('units_approved'),
                'units_refused' => $aggregates->sum('units_refused'),
                'units_pending' => $aggregates->sum('units_pending'),
            ],
            'daily' => $aggregates->map(fn ($agg) => [
                'date' => $agg->aggregate_date,
                'circuits_worked' => $agg->circuits_worked,
                'units' => $agg->total_units_assessed,
                'linear_ft' => $agg->total_linear_ft,
            ])->values()->toArray(),
        ];
    }

    /**
     * Get time series data for a circuit.
     *
     * @param  int  $circuitId  Circuit ID
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     */
    public function getCircuitTimeSeries(int $circuitId, string $fromDate, string $toDate): Collection
    {
        return CircuitAggregate::where('circuit_id', $circuitId)
            ->where('is_rollup', false)
            ->whereDate('aggregate_date', '>=', $fromDate)
            ->whereDate('aggregate_date', '<=', $toDate)
            ->orderBy('aggregate_date')
            ->get()
            ->map(fn ($agg) => [
                'date' => $agg->aggregate_date,
                'total_units' => $agg->total_units,
                'total_linear_ft' => $agg->total_linear_ft,
                'total_acres' => $agg->total_acres,
                'units_approved' => $agg->units_approved,
                'units_refused' => $agg->units_refused,
                'units_pending' => $agg->units_pending,
            ]);
    }

    /**
     * Get permission status breakdown by region.
     */
    public function getPermissionBreakdownByRegion(): Collection
    {
        return Region::all()->map(function ($region) {
            $latestAggregate = RegionalDailyAggregate::where('region_id', $region->id)
                ->orderBy('aggregate_date', 'desc')
                ->first();

            $total = ($latestAggregate?->units_approved ?? 0)
                + ($latestAggregate?->units_refused ?? 0)
                + ($latestAggregate?->units_pending ?? 0);

            return [
                'region_id' => $region->id,
                'region_name' => $region->name,
                'approved' => $latestAggregate?->units_approved ?? 0,
                'refused' => $latestAggregate?->units_refused ?? 0,
                'pending' => $latestAggregate?->units_pending ?? 0,
                'total' => $total,
                'approved_percent' => $total > 0 ? round(($latestAggregate?->units_approved ?? 0) / $total * 100, 1) : 0,
                'refused_percent' => $total > 0 ? round(($latestAggregate?->units_refused ?? 0) / $total * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get top planners by productivity.
     *
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     * @param  int  $limit  Number of planners to return
     */
    public function getTopPlanners(string $fromDate, string $toDate, int $limit = 10): Collection
    {
        return PlannerDailyAggregate::query()
            ->select('user_id', DB::raw('SUM(total_units_assessed) as total_units'))
            ->whereDate('aggregate_date', '>=', $fromDate)
            ->whereDate('aggregate_date', '<=', $toDate)
            ->groupBy('user_id')
            ->orderByDesc('total_units')
            ->limit($limit)
            ->with('user:id,name,email')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'name' => $row->user?->name ?? 'Unknown',
                'total_units' => $row->total_units,
            ]);
    }

    /**
     * Merge unit counts by type from multiple aggregates.
     */
    protected function mergeUnitCountsByType(Collection $aggregates): array
    {
        $merged = [];

        foreach ($aggregates as $agg) {
            foreach ($agg->unit_counts_by_type ?? [] as $type => $count) {
                $merged[$type] = ($merged[$type] ?? 0) + $count;
            }
        }

        return $merged;
    }

    // ================================================================
    // Weekly Aggregate Queries
    // ================================================================

    /**
     * Get planner's weekly summary.
     *
     * @param  int  $userId  User (planner) ID
     * @param  string  $weekEnding  Saturday date that ends the week
     */
    public function getPlannerWeeklySummary(int $userId, string $weekEnding): ?PlannerWeeklyAggregate
    {
        return PlannerWeeklyAggregate::forPlanner($userId)
            ->forWeekEnding($weekEnding)
            ->first();
    }

    /**
     * Get planner productivity for a date range of weeks.
     *
     * @param  int  $userId  User (planner) ID
     * @param  string  $fromWeekEnding  Starting week ending date
     * @param  string  $toWeekEnding  Ending week ending date
     */
    public function getPlannerWeeklyHistory(int $userId, string $fromWeekEnding, string $toWeekEnding): Collection
    {
        return PlannerWeeklyAggregate::forPlanner($userId)
            ->betweenWeeks($fromWeekEnding, $toWeekEnding)
            ->orderBy('week_ending')
            ->get();
    }

    /**
     * Get regional weekly summary.
     *
     * @param  int  $regionId  Region ID
     * @param  string  $weekEnding  Saturday date that ends the week
     */
    public function getRegionalWeeklySummary(int $regionId, string $weekEnding): ?RegionalWeeklyAggregate
    {
        return RegionalWeeklyAggregate::forRegion($regionId)
            ->forWeekEnding($weekEnding)
            ->first();
    }

    /**
     * Get all regions' weekly summaries for a given week.
     *
     * @param  string  $weekEnding  Saturday date that ends the week
     */
    public function getAllRegionsWeeklySummary(string $weekEnding): Collection
    {
        return RegionalWeeklyAggregate::with('region')
            ->forWeekEnding($weekEnding)
            ->get()
            ->map(fn ($agg) => [
                'region_id' => $agg->region_id,
                'region_name' => $agg->region->name,
                'region_code' => $agg->region->code,
                'week_ending' => $agg->week_ending,
                'total_miles' => $agg->total_miles,
                'miles_planned' => $agg->miles_planned,
                'miles_remaining' => $agg->miles_remaining,
                'completion_percent' => $agg->getCompletionPercentage(),
                'total_units' => $agg->total_units,
                'active_planners' => $agg->active_planners,
                'total_circuits' => $agg->total_circuits,
                'excluded_circuits' => $agg->excluded_circuits,
            ]);
    }

    /**
     * Get global weekly summary (all regions combined).
     *
     * @param  string  $weekEnding  Saturday date that ends the week
     */
    public function getGlobalWeeklySummary(string $weekEnding): array
    {
        $regionals = RegionalWeeklyAggregate::forWeekEnding($weekEnding)->get();

        if ($regionals->isEmpty()) {
            return [
                'week_ending' => $weekEnding,
                'regions_count' => 0,
                'total_miles' => 0,
                'miles_planned' => 0,
                'miles_remaining' => 0,
                'total_units' => 0,
                'total_circuits' => 0,
                'excluded_circuits' => 0,
                'active_planners' => 0,
            ];
        }

        $totalMiles = $regionals->sum('total_miles');
        $milesPlanned = $regionals->sum('miles_planned');

        return [
            'week_ending' => $weekEnding,
            'regions_count' => $regionals->count(),
            'total_miles' => round($totalMiles, 2),
            'miles_planned' => round($milesPlanned, 2),
            'miles_remaining' => round($totalMiles - $milesPlanned, 2),
            'completion_percent' => $totalMiles > 0 ? round(($milesPlanned / $totalMiles) * 100, 1) : 0,
            'total_units' => $regionals->sum('total_units'),
            'total_linear_ft' => round($regionals->sum('total_linear_ft'), 2),
            'total_acres' => round($regionals->sum('total_acres'), 4),
            'total_trees' => $regionals->sum('total_trees'),
            'units_approved' => $regionals->sum('units_approved'),
            'units_refused' => $regionals->sum('units_refused'),
            'units_pending' => $regionals->sum('units_pending'),
            'total_circuits' => $regionals->sum('total_circuits'),
            'excluded_circuits' => $regionals->sum('excluded_circuits'),
            'active_planners' => $regionals->sum('active_planners'),
            'unit_counts_by_type' => $this->mergeUnitCountsByType($regionals),
        ];
    }

    /**
     * Get top planners for a specific week.
     *
     * @param  string  $weekEnding  Saturday date that ends the week
     * @param  int  $limit  Number of planners to return
     */
    public function getTopPlannersForWeek(string $weekEnding, int $limit = 10): Collection
    {
        return PlannerWeeklyAggregate::forWeekEnding($weekEnding)
            ->with('user:id,name,email')
            ->orderByDesc('total_units_assessed')
            ->limit($limit)
            ->get()
            ->map(fn ($agg) => [
                'user_id' => $agg->user_id,
                'name' => $agg->user?->name ?? 'Unknown',
                'total_units' => $agg->total_units_assessed,
                'miles_planned' => $agg->miles_planned,
                'days_worked' => $agg->days_worked,
                'avg_daily_units' => $agg->getAvgDailyUnits(),
            ]);
    }

    /**
     * Get planner's units for a specific date (historical lookup).
     *
     * @param  int  $userId  User (planner) ID
     * @param  string  $date  The specific date to look up
     */
    public function getPlannerDailyUnits(int $userId, string $date): ?array
    {
        $aggregate = PlannerDailyAggregate::forPlanner($userId)
            ->forDate($date)
            ->first();

        if (! $aggregate) {
            return null;
        }

        return [
            'user_id' => $aggregate->user_id,
            'date' => $aggregate->aggregate_date,
            'units_assessed' => $aggregate->total_units_assessed,
            'circuits_worked' => $aggregate->circuits_worked,
            'linear_ft' => $aggregate->total_linear_ft,
            'acres' => $aggregate->total_acres,
            'trees' => $aggregate->total_trees,
            'miles_planned' => $aggregate->miles_planned,
            'units_approved' => $aggregate->units_approved,
            'units_refused' => $aggregate->units_refused,
            'units_pending' => $aggregate->units_pending,
            'unit_counts_by_type' => $aggregate->unit_counts_by_type,
        ];
    }

    /**
     * Get the Saturday week-ending date for a given date.
     */
    public function getWeekEndingForDate(Carbon|string $date): Carbon
    {
        return PlannerWeeklyAggregate::getWeekEndingForDate($date);
    }

    /**
     * Get miles summary for a region (current state).
     *
     * @param  int  $regionId  Region ID
     * @param  bool  $excludeExcluded  Whether to exclude excluded circuits
     */
    public function getRegionMilesSummary(int $regionId, bool $excludeExcluded = true): array
    {
        $query = \App\Models\Circuit::where('region_id', $regionId);

        if ($excludeExcluded) {
            $query->notExcluded();
        }

        $circuits = $query->get();

        return [
            'region_id' => $regionId,
            'total_circuits' => $circuits->count(),
            'total_miles' => round($circuits->sum('total_miles'), 2),
            'miles_planned' => round($circuits->sum('miles_planned'), 2),
            'miles_remaining' => round($circuits->sum('total_miles') - $circuits->sum('miles_planned'), 2),
            'avg_percent_complete' => $circuits->avg('percent_complete') ?? 0,
        ];
    }
}
