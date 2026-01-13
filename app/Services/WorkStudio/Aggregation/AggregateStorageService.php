<?php

namespace App\Services\WorkStudio\Aggregation;

use App\Models\CircuitAggregate;
use App\Models\PlannerDailyAggregate;
use App\Models\RegionalDailyAggregate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for persisting aggregate data to the database.
 *
 * Handles upsert logic and maintains historical records.
 */
class AggregateStorageService
{
    /**
     * Store or update a circuit aggregate.
     *
     * @param  array  $data  Aggregate data from AggregateCalculationService
     * @return CircuitAggregate The created/updated aggregate
     */
    public function storeCircuitAggregate(array $data): CircuitAggregate
    {
        // Remove error markers if present
        unset($data['_error']);

        // Ensure date is consistently formatted as Y-m-d
        $aggregateDate = $data['aggregate_date'];
        if ($aggregateDate instanceof \Carbon\Carbon || $aggregateDate instanceof \DateTime) {
            $aggregateDate = $aggregateDate->format('Y-m-d');
        }

        // Find existing record using whereDate for consistent matching
        $existing = CircuitAggregate::where('circuit_id', $data['circuit_id'])
            ->whereDate('aggregate_date', $aggregateDate)
            ->where('is_rollup', $data['is_rollup'] ?? false)
            ->first();

        $values = [
            'total_units' => $data['total_units'] ?? 0,
            'total_linear_ft' => $data['total_linear_ft'] ?? 0,
            'total_acres' => $data['total_acres'] ?? 0,
            'total_trees' => $data['total_trees'] ?? 0,
            'units_approved' => $data['units_approved'] ?? 0,
            'units_refused' => $data['units_refused'] ?? 0,
            'units_pending' => $data['units_pending'] ?? 0,
            'unit_counts_by_type' => $data['unit_counts_by_type'] ?? [],
            'linear_ft_by_type' => $data['linear_ft_by_type'] ?? [],
            'acres_by_type' => $data['acres_by_type'] ?? [],
            'planner_distribution' => $data['planner_distribution'] ?? [],
        ];

        if ($existing) {
            $existing->update($values);

            return $existing;
        }

        return CircuitAggregate::create([
            'circuit_id' => $data['circuit_id'],
            'aggregate_date' => $aggregateDate,
            'is_rollup' => $data['is_rollup'] ?? false,
            ...$values,
        ]);
    }

    /**
     * Store multiple circuit aggregates in a transaction.
     *
     * @param  Collection<array>  $aggregates  Collection of aggregate data arrays
     * @return int Number of successfully stored aggregates
     */
    public function storeCircuitAggregates(Collection $aggregates): int
    {
        $stored = 0;

        DB::transaction(function () use ($aggregates, &$stored) {
            foreach ($aggregates as $data) {
                try {
                    $this->storeCircuitAggregate($data);
                    $stored++;
                } catch (\Exception $e) {
                    Log::error('Failed to store circuit aggregate', [
                        'circuit_id' => $data['circuit_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return $stored;
    }

    /**
     * Store or update a planner daily aggregate.
     *
     * @param  array  $data  Planner aggregate data
     * @return PlannerDailyAggregate The created/updated aggregate
     */
    public function storePlannerAggregate(array $data): PlannerDailyAggregate
    {
        return PlannerDailyAggregate::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'region_id' => $data['region_id'],
                'aggregate_date' => $data['aggregate_date'],
            ],
            [
                'circuits_worked' => $data['circuits_worked'] ?? 0,
                'total_units_assessed' => $data['total_units_assessed'] ?? 0,
                'total_linear_ft' => $data['total_linear_ft'] ?? 0,
                'total_acres' => $data['total_acres'] ?? 0,
                'units_approved' => $data['units_approved'] ?? 0,
                'units_refused' => $data['units_refused'] ?? 0,
                'units_pending' => $data['units_pending'] ?? 0,
                'unit_counts_by_type' => $data['unit_counts_by_type'] ?? [],
                'circuits_list' => $data['circuits_list'] ?? [],
            ]
        );
    }

    /**
     * Store or update a regional daily aggregate.
     *
     * @param  array  $data  Regional aggregate data
     * @return RegionalDailyAggregate The created/updated aggregate
     */
    public function storeRegionalAggregate(array $data): RegionalDailyAggregate
    {
        return RegionalDailyAggregate::updateOrCreate(
            [
                'region_id' => $data['region_id'],
                'aggregate_date' => $data['aggregate_date'],
            ],
            [
                'total_circuits' => $data['total_circuits'] ?? 0,
                'total_planners' => $data['total_planners'] ?? 0,
                'total_units' => $data['total_units'] ?? 0,
                'total_linear_ft' => $data['total_linear_ft'] ?? 0,
                'total_acres' => $data['total_acres'] ?? 0,
                'total_trees' => $data['total_trees'] ?? 0,
                'units_approved' => $data['units_approved'] ?? 0,
                'units_refused' => $data['units_refused'] ?? 0,
                'units_pending' => $data['units_pending'] ?? 0,
                'unit_counts_by_type' => $data['unit_counts_by_type'] ?? [],
                'permission_counts' => $data['permission_counts'] ?? [],
            ]
        );
    }

    /**
     * Create a rollup aggregate that combines daily aggregates for a circuit.
     *
     * @param  int  $circuitId  The circuit ID
     * @param  string  $fromDate  Start date for rollup
     * @param  string  $toDate  End date for rollup
     * @return CircuitAggregate The rollup aggregate
     */
    public function createCircuitRollup(int $circuitId, string $fromDate, string $toDate): CircuitAggregate
    {
        // Get all aggregates in the date range using whereDate for consistent matching
        $aggregates = CircuitAggregate::where('circuit_id', $circuitId)
            ->whereDate('aggregate_date', '>=', $fromDate)
            ->whereDate('aggregate_date', '<=', $toDate)
            ->where('is_rollup', false)
            ->orderBy('aggregate_date', 'desc')
            ->get();

        if ($aggregates->isEmpty()) {
            // Return an empty rollup
            return $this->storeCircuitAggregate([
                'circuit_id' => $circuitId,
                'aggregate_date' => $toDate,
                'is_rollup' => true,
            ]);
        }

        // Use the latest aggregate values (rollup shows current state)
        $latest = $aggregates->first();

        return $this->storeCircuitAggregate([
            'circuit_id' => $circuitId,
            'aggregate_date' => $toDate,
            'is_rollup' => true,
            'total_units' => $latest->total_units,
            'total_linear_ft' => $latest->total_linear_ft,
            'total_acres' => $latest->total_acres,
            'total_trees' => $latest->total_trees,
            'units_approved' => $latest->units_approved,
            'units_refused' => $latest->units_refused,
            'units_pending' => $latest->units_pending,
            'unit_counts_by_type' => $latest->unit_counts_by_type,
            'linear_ft_by_type' => $latest->linear_ft_by_type,
            'acres_by_type' => $latest->acres_by_type,
            'planner_distribution' => $latest->planner_distribution,
        ]);
    }

    /**
     * Delete old aggregates beyond retention period.
     *
     * @param  int  $daysToKeep  Number of days to retain (default 365)
     * @return array Count of deleted records per table
     */
    public function pruneOldAggregates(int $daysToKeep = 365): array
    {
        $cutoffDate = now()->subDays($daysToKeep)->toDateString();

        return DB::transaction(function () use ($cutoffDate) {
            return [
                'circuit_aggregates' => CircuitAggregate::whereDate('aggregate_date', '<', $cutoffDate)->delete(),
                'planner_daily_aggregates' => PlannerDailyAggregate::whereDate('aggregate_date', '<', $cutoffDate)->delete(),
                'regional_daily_aggregates' => RegionalDailyAggregate::whereDate('aggregate_date', '<', $cutoffDate)->delete(),
            ];
        });
    }

    /**
     * Get the latest aggregate date for a circuit.
     */
    public function getLatestAggregateDate(int $circuitId): ?string
    {
        $aggregate = CircuitAggregate::where('circuit_id', $circuitId)
            ->where('is_rollup', false)
            ->orderBy('aggregate_date', 'desc')
            ->first();

        // Return formatted date string (Y-m-d) or null
        return $aggregate?->aggregate_date?->format('Y-m-d');
    }

    /**
     * Check if an aggregate exists for a circuit on a specific date.
     */
    public function hasAggregateForDate(int $circuitId, string $date): bool
    {
        return CircuitAggregate::where('circuit_id', $circuitId)
            ->whereDate('aggregate_date', $date)
            ->where('is_rollup', false)
            ->exists();
    }
}
