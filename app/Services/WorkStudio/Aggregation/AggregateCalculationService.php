<?php

namespace App\Services\WorkStudio\Aggregation;

use App\Models\Circuit;
use App\Models\Region;
use App\Models\User;
use App\Services\WorkStudio\Transformers\PlannedUnitAggregateTransformer;
use App\Services\WorkStudio\WorkStudioApiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for calculating aggregates from WorkStudio API data.
 *
 * Orchestrates fetching planned units and computing circuit/planner/regional aggregates.
 */
class AggregateCalculationService
{
    public function __construct(
        private WorkStudioApiService $apiService,
        private PlannedUnitAggregateTransformer $transformer,
    ) {}

    /**
     * Calculate aggregate data for a specific circuit.
     *
     * @param  Circuit  $circuit  The circuit to calculate aggregates for
     * @return array Aggregate data ready for storage
     */
    public function calculateForCircuit(Circuit $circuit): array
    {
        try {
            // Fetch planned units from API
            $rawUnits = $this->apiService->getPlannedUnits($circuit->work_order);

            // Filter to this specific circuit (by extension)
            $circuitUnits = $rawUnits->filter(function ($unit) use ($circuit) {
                $extension = $unit['SS_EXT'] ?? '@';

                return $extension === $circuit->extension;
            });

            // Transform to aggregate data
            $aggregate = $this->transformer->transformToAggregate($circuitUnits);

            // Add metadata
            $aggregate['circuit_id'] = $circuit->id;
            $aggregate['aggregate_date'] = now()->toDateString();
            $aggregate['is_rollup'] = false;

            return $aggregate;

        } catch (\Exception $e) {
            Log::error('Failed to calculate circuit aggregate', [
                'circuit_id' => $circuit->id,
                'work_order' => $circuit->work_order,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate aggregate data for multiple circuits.
     *
     * @param  Collection<Circuit>  $circuits
     * @return Collection<array> Collection of aggregate data arrays
     */
    public function calculateForCircuits(Collection $circuits): Collection
    {
        return $circuits->map(function (Circuit $circuit) {
            try {
                return $this->calculateForCircuit($circuit);
            } catch (\Exception $e) {
                // Return empty aggregate on failure, log is handled in calculateForCircuit
                $empty = $this->transformer->emptyAggregate();
                $empty['circuit_id'] = $circuit->id;
                $empty['aggregate_date'] = now()->toDateString();
                $empty['is_rollup'] = false;
                $empty['_error'] = $e->getMessage();

                return $empty;
            }
        });
    }

    /**
     * Calculate planner-level aggregates for a specific date.
     *
     * @param  User  $planner  The planner user
     * @param  Region  $region  The region to aggregate for
     * @param  string|null  $date  The date to aggregate (defaults to today)
     * @return array Planner aggregate data
     */
    public function calculateForPlanner(User $planner, Region $region, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        // Get circuits this planner is assigned to in this region
        $circuits = Circuit::query()
            ->where('region_id', $region->id)
            ->whereHas('planners', fn ($q) => $q->where('users.id', $planner->id))
            ->whereNull('deleted_at')
            ->get();

        if ($circuits->isEmpty()) {
            return $this->emptyPlannerAggregate($planner->id, $region->id, $date);
        }

        // Aggregate data across all circuits
        $totals = [
            'user_id' => $planner->id,
            'region_id' => $region->id,
            'aggregate_date' => $date,
            'circuits_worked' => $circuits->count(),
            'total_units_assessed' => 0,
            'total_linear_ft' => 0,
            'total_acres' => 0,
            'units_approved' => 0,
            'units_refused' => 0,
            'units_pending' => 0,
            'unit_counts_by_type' => [],
            'circuits_list' => $circuits->pluck('id')->toArray(),
        ];

        // Sum up from circuit aggregates for this planner
        foreach ($circuits as $circuit) {
            $circuitAggregate = $circuit->latestAggregate;

            if (! $circuitAggregate) {
                continue;
            }

            // Get planner's portion from distribution
            $distribution = $circuitAggregate->planner_distribution ?? [];
            $plannerName = $planner->ws_username ?? $planner->name;
            $plannerData = $distribution[$plannerName] ?? null;

            if ($plannerData) {
                $totals['total_units_assessed'] += $plannerData['unit_count'] ?? 0;
                $totals['total_linear_ft'] += $plannerData['linear_ft'] ?? 0;
                $totals['total_acres'] += $plannerData['acres'] ?? 0;
            }

            // Permission counts are circuit-level, so we proportionally attribute
            // based on this planner's share of the circuit
            $totalUnits = $circuitAggregate->total_units;
            if ($totalUnits > 0 && $plannerData) {
                $ratio = ($plannerData['unit_count'] ?? 0) / $totalUnits;
                $totals['units_approved'] += (int) round($circuitAggregate->units_approved * $ratio);
                $totals['units_refused'] += (int) round($circuitAggregate->units_refused * $ratio);
                $totals['units_pending'] += (int) round($circuitAggregate->units_pending * $ratio);
            }
        }

        return $totals;
    }

    /**
     * Calculate regional rollup aggregate.
     *
     * @param  Region  $region  The region to aggregate
     * @param  string|null  $date  The date to aggregate (defaults to today)
     * @return array Regional aggregate data
     */
    public function calculateForRegion(Region $region, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        // Get all circuits in this region
        $circuits = Circuit::query()
            ->where('region_id', $region->id)
            ->whereNull('deleted_at')
            ->with('latestAggregate')
            ->get();

        // Get unique planners working in this region
        $planners = User::query()
            ->whereHas('circuits', fn ($q) => $q->where('region_id', $region->id))
            ->get();

        $totals = [
            'region_id' => $region->id,
            'aggregate_date' => $date,
            'total_circuits' => $circuits->count(),
            'total_planners' => $planners->count(),
            'total_units' => 0,
            'total_linear_ft' => 0,
            'total_acres' => 0,
            'total_trees' => 0,
            'units_approved' => 0,
            'units_refused' => 0,
            'units_pending' => 0,
            'unit_counts_by_type' => [],
            'permission_counts' => [],
        ];

        // Sum from circuit aggregates
        foreach ($circuits as $circuit) {
            $agg = $circuit->latestAggregate;
            if (! $agg) {
                continue;
            }

            $totals['total_units'] += $agg->total_units;
            $totals['total_linear_ft'] += $agg->total_linear_ft;
            $totals['total_acres'] += $agg->total_acres;
            $totals['total_trees'] += $agg->total_trees;
            $totals['units_approved'] += $agg->units_approved;
            $totals['units_refused'] += $agg->units_refused;
            $totals['units_pending'] += $agg->units_pending;

            // Merge unit counts by type
            foreach ($agg->unit_counts_by_type ?? [] as $type => $count) {
                $totals['unit_counts_by_type'][$type] = ($totals['unit_counts_by_type'][$type] ?? 0) + $count;
            }
        }

        // Calculate permission counts summary
        $totals['permission_counts'] = [
            'approved' => $totals['units_approved'],
            'refused' => $totals['units_refused'],
            'pending' => $totals['units_pending'],
        ];

        return $totals;
    }

    /**
     * Get an empty planner aggregate structure.
     */
    protected function emptyPlannerAggregate(int $userId, int $regionId, string $date): array
    {
        return [
            'user_id' => $userId,
            'region_id' => $regionId,
            'aggregate_date' => $date,
            'circuits_worked' => 0,
            'total_units_assessed' => 0,
            'total_linear_ft' => 0,
            'total_acres' => 0,
            'units_approved' => 0,
            'units_refused' => 0,
            'units_pending' => 0,
            'unit_counts_by_type' => [],
            'circuits_list' => [],
        ];
    }
}
