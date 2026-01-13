<?php

namespace App\Services\WorkStudio\Aggregation;

use App\Models\CircuitAggregate;
use App\Models\PlannerDailyAggregate;
use App\Models\Region;
use App\Models\RegionalDailyAggregate;
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
}
