<?php

namespace App\Jobs;

use App\Models\Circuit;
use App\Models\PlannerWeeklyAggregate;
use App\Models\Region;
use App\Models\RegionalWeeklyAggregate;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job for building weekly aggregates from circuit data.
 *
 * Computes and stores regional and planner weekly summaries.
 * Typically scheduled to run at end of week (Saturday night).
 */
class BuildWeeklyAggregatesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds before the job should timeout.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
     * @param  Carbon|string|null  $weekEnding  The Saturday ending the week (defaults to current week)
     * @param  bool  $includePlanners  Whether to also build planner aggregates
     */
    public function __construct(
        private Carbon|string|null $weekEnding = null,
        private bool $includePlanners = true,
    ) {
        if ($this->weekEnding === null) {
            $this->weekEnding = PlannerWeeklyAggregate::getWeekEndingForDate(now());
        } elseif (is_string($this->weekEnding)) {
            $this->weekEnding = Carbon::parse($this->weekEnding);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $weekEnding = $this->weekEnding instanceof Carbon
            ? $this->weekEnding
            : Carbon::parse($this->weekEnding);

        $weekStarting = PlannerWeeklyAggregate::getWeekStartingForDate($weekEnding);

        Log::info('Building weekly aggregates', [
            'week_starting' => $weekStarting->toDateString(),
            'week_ending' => $weekEnding->toDateString(),
            'include_planners' => $this->includePlanners,
        ]);

        // Build regional aggregates
        $regionsProcessed = $this->buildRegionalAggregates($weekStarting, $weekEnding);

        // Build planner aggregates if requested
        $plannersProcessed = 0;
        if ($this->includePlanners) {
            $plannersProcessed = $this->buildPlannerAggregates($weekStarting, $weekEnding);
        }

        Log::info('Weekly aggregates built successfully', [
            'week_ending' => $weekEnding->toDateString(),
            'regions_processed' => $regionsProcessed,
            'planners_processed' => $plannersProcessed,
        ]);
    }

    /**
     * Build regional weekly aggregates for all active regions.
     */
    protected function buildRegionalAggregates(Carbon $weekStarting, Carbon $weekEnding): int
    {
        $regions = Region::where('is_active', true)->get();
        $processed = 0;

        foreach ($regions as $region) {
            $stats = $this->computeRegionalStats($region, $weekEnding);

            RegionalWeeklyAggregate::updateOrCreate(
                [
                    'region_id' => $region->id,
                    'week_ending' => $weekEnding,
                ],
                [
                    'week_starting' => $weekStarting,
                    ...$stats,
                ]
            );

            $processed++;
        }

        return $processed;
    }

    /**
     * Compute stats for a single region.
     *
     * @return array<string, mixed>
     */
    protected function computeRegionalStats(Region $region, Carbon $weekEnding): array
    {
        // Get circuit stats (excludes excluded circuits)
        $circuitStats = Circuit::query()
            ->where('region_id', $region->id)
            ->whereNull('deleted_at')
            ->notExcluded()
            ->select([
                DB::raw('COUNT(*) as total_circuits'),
                DB::raw("COUNT(CASE WHEN api_status = 'ACTIV' THEN 1 END) as active_circuits"),
                DB::raw("COUNT(CASE WHEN api_status = 'QC' THEN 1 END) as qc_circuits"),
                DB::raw("COUNT(CASE WHEN api_status = 'CLOSE' THEN 1 END) as closed_circuits"),
                DB::raw("COUNT(CASE WHEN api_status = 'REWRK' THEN 1 END) as rework_circuits"),
                DB::raw('COALESCE(SUM(total_miles), 0) as total_miles'),
                DB::raw('COALESCE(SUM(miles_planned), 0) as miles_planned'),
                DB::raw('COALESCE(SUM(total_miles) - SUM(miles_planned), 0) as miles_remaining'),
                DB::raw('COALESCE(AVG(percent_complete), 0) as avg_percent_complete'),
                DB::raw('COALESCE(SUM(total_acres), 0) as total_acres'),
            ])
            ->first();

        // Count excluded circuits separately
        $excludedCount = Circuit::query()
            ->where('region_id', $region->id)
            ->whereNull('deleted_at')
            ->where('is_excluded', true)
            ->count();

        // Get planner count for this region (excludes excluded circuits)
        $activePlanners = DB::table('circuit_user')
            ->join('circuits', 'circuit_user.circuit_id', '=', 'circuits.id')
            ->where('circuits.region_id', $region->id)
            ->whereNull('circuits.deleted_at')
            ->where('circuits.is_excluded', false)
            ->distinct()
            ->count('circuit_user.user_id');

        // Get unit stats from circuit_aggregates (latest per circuit, excludes excluded circuits)
        $unitStats = DB::table('circuit_aggregates')
            ->join('circuits', 'circuit_aggregates.circuit_id', '=', 'circuits.id')
            ->where('circuits.region_id', $region->id)
            ->whereNull('circuits.deleted_at')
            ->where('circuits.is_excluded', false)
            ->where('circuit_aggregates.is_rollup', false)
            ->whereIn('circuit_aggregates.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('circuit_aggregates')
                    ->where('is_rollup', false)
                    ->groupBy('circuit_id');
            })
            ->select([
                DB::raw('COALESCE(SUM(total_units), 0) as total_units'),
                DB::raw('COALESCE(SUM(units_approved), 0) as units_approved'),
                DB::raw('COALESCE(SUM(units_refused), 0) as units_refused'),
                DB::raw('COALESCE(SUM(units_pending), 0) as units_pending'),
                DB::raw('COALESCE(SUM(total_trees), 0) as total_trees'),
                DB::raw('COALESCE(SUM(total_linear_ft), 0) as total_linear_ft'),
            ])
            ->first();

        return [
            'active_circuits' => (int) $circuitStats->active_circuits,
            'qc_circuits' => (int) $circuitStats->qc_circuits,
            'closed_circuits' => (int) $circuitStats->closed_circuits,
            'total_circuits' => (int) $circuitStats->total_circuits,
            'excluded_circuits' => $excludedCount,
            'total_miles' => (float) $circuitStats->total_miles,
            'miles_planned' => (float) $circuitStats->miles_planned,
            'miles_remaining' => (float) $circuitStats->miles_remaining,
            'avg_percent_complete' => round((float) $circuitStats->avg_percent_complete, 2),
            'total_units' => (int) ($unitStats->total_units ?? 0),
            'total_linear_ft' => (float) ($unitStats->total_linear_ft ?? 0),
            'total_acres' => (float) $circuitStats->total_acres,
            'total_trees' => (int) ($unitStats->total_trees ?? 0),
            'units_approved' => (int) ($unitStats->units_approved ?? 0),
            'units_refused' => (int) ($unitStats->units_refused ?? 0),
            'units_pending' => (int) ($unitStats->units_pending ?? 0),
            'active_planners' => $activePlanners,
            'total_planner_days' => 0, // Could be computed from daily aggregates if needed
            'unit_counts_by_type' => [], // Could be aggregated from circuit_aggregates
            'status_breakdown' => [
                'ACTIV' => (int) $circuitStats->active_circuits,
                'QC' => (int) $circuitStats->qc_circuits,
                'CLOSE' => (int) $circuitStats->closed_circuits,
                'REWRK' => (int) ($circuitStats->rework_circuits ?? 0),
            ],
            'daily_breakdown' => null,
        ];
    }

    /**
     * Build planner weekly aggregates for all planners with circuit assignments.
     */
    protected function buildPlannerAggregates(Carbon $weekStarting, Carbon $weekEnding): int
    {
        // Get all planners who have circuit assignments (excludes excluded circuits)
        $plannerRegions = DB::table('circuit_user')
            ->join('circuits', 'circuit_user.circuit_id', '=', 'circuits.id')
            ->whereNull('circuits.deleted_at')
            ->where('circuits.is_excluded', false)
            ->select('circuit_user.user_id', 'circuits.region_id')
            ->distinct()
            ->get();

        $processed = 0;

        foreach ($plannerRegions as $pr) {
            $stats = $this->computePlannerStats($pr->user_id, $pr->region_id, $weekStarting, $weekEnding);

            PlannerWeeklyAggregate::updateOrCreate(
                [
                    'user_id' => $pr->user_id,
                    'region_id' => $pr->region_id,
                    'week_ending' => $weekEnding,
                ],
                [
                    'week_starting' => $weekStarting,
                    ...$stats,
                ]
            );

            $processed++;
        }

        return $processed;
    }

    /**
     * Compute stats for a single planner in a region.
     *
     * @return array<string, mixed>
     */
    protected function computePlannerStats(int $userId, int $regionId, Carbon $weekStarting, Carbon $weekEnding): array
    {
        // Get circuits this planner is assigned to in this region (excludes excluded circuits)
        $circuitIds = DB::table('circuit_user')
            ->join('circuits', 'circuit_user.circuit_id', '=', 'circuits.id')
            ->where('circuit_user.user_id', $userId)
            ->where('circuits.region_id', $regionId)
            ->whereNull('circuits.deleted_at')
            ->where('circuits.is_excluded', false)
            ->pluck('circuits.id');

        $circuitsWorked = $circuitIds->count();

        if ($circuitsWorked === 0) {
            return [
                'days_worked' => 0,
                'circuits_worked' => 0,
                'total_units_assessed' => 0,
                'total_linear_ft' => 0,
                'total_acres' => 0,
                'total_trees' => 0,
                'miles_planned' => 0,
                'miles_planned_start' => 0,
                'miles_planned_end' => 0,
                'miles_delta' => 0,
                'met_weekly_target' => false,
                'units_approved' => 0,
                'units_refused' => 0,
                'units_pending' => 0,
                'unit_counts_by_type' => [],
                'daily_breakdown' => null,
            ];
        }

        // Calculate miles delta from snapshots
        $milesDelta = $this->computeMilesDelta($circuitIds, $weekStarting, $weekEnding);

        // Get current miles planned from circuits (cumulative total)
        $milesPlanned = Circuit::whereIn('id', $circuitIds)
            ->sum('miles_planned');

        // Get unit stats from circuit_aggregates for these circuits
        $unitStats = DB::table('circuit_aggregates')
            ->whereIn('circuit_id', $circuitIds)
            ->where('is_rollup', false)
            ->whereIn('id', function ($query) use ($circuitIds) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('circuit_aggregates')
                    ->whereIn('circuit_id', $circuitIds)
                    ->where('is_rollup', false)
                    ->groupBy('circuit_id');
            })
            ->select([
                DB::raw('COALESCE(SUM(total_units), 0) as total_units'),
                DB::raw('COALESCE(SUM(units_approved), 0) as units_approved'),
                DB::raw('COALESCE(SUM(units_refused), 0) as units_refused'),
                DB::raw('COALESCE(SUM(units_pending), 0) as units_pending'),
                DB::raw('COALESCE(SUM(total_trees), 0) as total_trees'),
                DB::raw('COALESCE(SUM(total_linear_ft), 0) as total_linear_ft'),
                DB::raw('COALESCE(SUM(total_acres), 0) as total_acres'),
            ])
            ->first();

        return [
            'days_worked' => 5, // Default assumption; could be computed from daily aggregates
            'circuits_worked' => $circuitsWorked,
            'total_units_assessed' => (int) ($unitStats->total_units ?? 0),
            'total_linear_ft' => (float) ($unitStats->total_linear_ft ?? 0),
            'total_acres' => (float) ($unitStats->total_acres ?? 0),
            'total_trees' => (int) ($unitStats->total_trees ?? 0),
            'miles_planned' => (float) $milesPlanned,
            'miles_planned_start' => $milesDelta['start'],
            'miles_planned_end' => $milesDelta['end'],
            'miles_delta' => $milesDelta['delta'],
            'met_weekly_target' => $milesDelta['delta'] >= PlannerWeeklyAggregate::WEEKLY_MILES_TARGET,
            'units_approved' => (int) ($unitStats->units_approved ?? 0),
            'units_refused' => (int) ($unitStats->units_refused ?? 0),
            'units_pending' => (int) ($unitStats->units_pending ?? 0),
            'unit_counts_by_type' => [],
            'daily_breakdown' => null,
        ];
    }

    /**
     * Compute miles planned delta from circuit snapshots.
     *
     * Looks for snapshots at week start and end to calculate how many
     * miles were planned during the week. Uses current circuit values
     * if end-of-week snapshot doesn't exist.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $circuitIds
     * @return array{start: float, end: float, delta: float}
     */
    protected function computeMilesDelta($circuitIds, Carbon $weekStarting, Carbon $weekEnding): array
    {
        if ($circuitIds->isEmpty()) {
            return ['start' => 0.0, 'end' => 0.0, 'delta' => 0.0];
        }

        // Get miles at start of week (Sunday) - use earliest snapshot on or before week start
        // If no snapshot exists before the week, assume 0 (new circuit that week)
        $startMiles = DB::table('circuit_snapshots')
            ->whereIn('circuit_id', $circuitIds)
            ->where('snapshot_date', '<=', $weekStarting->toDateString())
            ->whereIn('id', function ($query) use ($circuitIds, $weekStarting) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('circuit_snapshots')
                    ->whereIn('circuit_id', $circuitIds)
                    ->where('snapshot_date', '<=', $weekStarting->toDateString())
                    ->groupBy('circuit_id');
            })
            ->sum('miles_planned') ?? 0;

        // Get miles at end of week - prefer snapshot, fall back to current circuit values
        $endSnapshot = DB::table('circuit_snapshots')
            ->whereIn('circuit_id', $circuitIds)
            ->where('snapshot_date', '<=', $weekEnding->toDateString())
            ->whereIn('id', function ($query) use ($circuitIds, $weekEnding) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('circuit_snapshots')
                    ->whereIn('circuit_id', $circuitIds)
                    ->where('snapshot_date', '<=', $weekEnding->toDateString())
                    ->groupBy('circuit_id');
            })
            ->sum('miles_planned');

        // If we have end-of-week snapshots, use them; otherwise use current circuit values
        if ($endSnapshot > 0) {
            $endMiles = (float) $endSnapshot;
        } else {
            // Fall back to current circuit values
            $endMiles = (float) Circuit::whereIn('id', $circuitIds)->sum('miles_planned');
        }

        $startMiles = (float) $startMiles;
        $delta = max(0, $endMiles - $startMiles); // Delta can't be negative

        return [
            'start' => round($startMiles, 2),
            'end' => round($endMiles, 2),
            'delta' => round($delta, 2),
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to build weekly aggregates', [
            'week_ending' => $this->weekEnding,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return ['aggregates', 'weekly'];
    }
}
