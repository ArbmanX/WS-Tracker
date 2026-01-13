<?php

namespace App\Services\WorkStudio\Aggregation;

use App\Models\CircuitAggregate;

/**
 * Service for comparing aggregate data to detect changes.
 *
 * Used to determine if sync updates are needed and to track progress deltas.
 */
class AggregateDiffService
{
    /**
     * Fields to compare for detecting meaningful changes.
     */
    private const COMPARISON_FIELDS = [
        'total_units',
        'total_linear_ft',
        'total_acres',
        'total_trees',
        'units_approved',
        'units_refused',
        'units_pending',
    ];

    /**
     * Threshold for considering a numeric change significant.
     */
    private const SIGNIFICANCE_THRESHOLD = 0.01;

    /**
     * Compare new aggregate data with existing aggregate.
     *
     * @param  array  $newData  New aggregate data
     * @param  CircuitAggregate|null  $existing  Existing aggregate (null if first time)
     * @return array{has_changes: bool, changes: array, delta: array}
     */
    public function compare(array $newData, ?CircuitAggregate $existing): array
    {
        if (! $existing) {
            return [
                'has_changes' => true,
                'changes' => ['initial_sync' => true],
                'delta' => $this->buildDeltaFromNew($newData),
            ];
        }

        $changes = [];
        $delta = [];

        foreach (self::COMPARISON_FIELDS as $field) {
            $oldValue = $existing->{$field} ?? 0;
            $newValue = $newData[$field] ?? 0;

            if ($this->isSignificantChange($oldValue, $newValue)) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];

                $delta[$field] = $newValue - $oldValue;
            }
        }

        // Check JSONB fields for changes
        $jsonbChanges = $this->compareJsonbFields($newData, $existing);
        if (! empty($jsonbChanges)) {
            $changes['jsonb_fields'] = $jsonbChanges;
        }

        return [
            'has_changes' => ! empty($changes),
            'changes' => $changes,
            'delta' => $delta,
        ];
    }

    /**
     * Check if a numeric change is significant.
     */
    protected function isSignificantChange(float $old, float $new): bool
    {
        $diff = abs($new - $old);

        // If either is zero, check for any non-zero change
        if ($old == 0 || $new == 0) {
            return $diff > 0;
        }

        // Check percentage change
        $percentChange = $diff / max(abs($old), 1);

        return $percentChange > self::SIGNIFICANCE_THRESHOLD;
    }

    /**
     * Compare JSONB fields for changes.
     */
    protected function compareJsonbFields(array $newData, CircuitAggregate $existing): array
    {
        $jsonbFields = [
            'unit_counts_by_type',
            'planner_distribution',
        ];

        $changes = [];

        foreach ($jsonbFields as $field) {
            $oldValue = $existing->{$field} ?? [];
            $newValue = $newData[$field] ?? [];

            if ($this->hasJsonbChanged($oldValue, $newValue)) {
                $changes[$field] = true;
            }
        }

        return $changes;
    }

    /**
     * Check if a JSONB field has changed meaningfully.
     */
    protected function hasJsonbChanged(array $old, array $new): bool
    {
        // Sort keys for consistent comparison
        ksort($old);
        ksort($new);

        return json_encode($old) !== json_encode($new);
    }

    /**
     * Build a delta structure from new data (for first-time sync).
     */
    protected function buildDeltaFromNew(array $newData): array
    {
        $delta = [];

        foreach (self::COMPARISON_FIELDS as $field) {
            $delta[$field] = $newData[$field] ?? 0;
        }

        return $delta;
    }

    /**
     * Calculate progress delta between two dates for a circuit.
     *
     * @param  int  $circuitId  Circuit ID
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     * @return array Progress delta
     */
    public function calculateProgressDelta(int $circuitId, string $fromDate, string $toDate): array
    {
        $fromAggregate = CircuitAggregate::where('circuit_id', $circuitId)
            ->whereDate('aggregate_date', '<=', $fromDate)
            ->where('is_rollup', false)
            ->orderBy('aggregate_date', 'desc')
            ->first();

        $toAggregate = CircuitAggregate::where('circuit_id', $circuitId)
            ->whereDate('aggregate_date', '<=', $toDate)
            ->where('is_rollup', false)
            ->orderBy('aggregate_date', 'desc')
            ->first();

        if (! $fromAggregate || ! $toAggregate) {
            return ['error' => 'Missing aggregate data for date range'];
        }

        $delta = [];

        foreach (self::COMPARISON_FIELDS as $field) {
            $fromValue = $fromAggregate->{$field} ?? 0;
            $toValue = $toAggregate->{$field} ?? 0;

            $delta[$field] = [
                'from' => $fromValue,
                'to' => $toValue,
                'change' => $toValue - $fromValue,
                'percent_change' => $fromValue > 0
                    ? round((($toValue - $fromValue) / $fromValue) * 100, 2)
                    : null,
            ];
        }

        return [
            'circuit_id' => $circuitId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'delta' => $delta,
        ];
    }

    /**
     * Get circuits with significant changes since a date.
     *
     * @param  string  $sinceDate  Date to compare from
     * @return \Illuminate\Support\Collection<array>
     */
    public function getCircuitsWithChanges(string $sinceDate): \Illuminate\Support\Collection
    {
        $latestAggregates = CircuitAggregate::query()
            ->where('is_rollup', false)
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('circuit_aggregates')
                    ->where('is_rollup', false)
                    ->groupBy('circuit_id');
            })
            ->get();

        $previousAggregates = CircuitAggregate::query()
            ->where('is_rollup', false)
            ->whereDate('aggregate_date', '<=', $sinceDate)
            ->whereIn('id', function ($query) use ($sinceDate) {
                $query->selectRaw('MAX(id)')
                    ->from('circuit_aggregates')
                    ->where('is_rollup', false)
                    ->whereRaw('date(aggregate_date) <= ?', [$sinceDate])
                    ->groupBy('circuit_id');
            })
            ->get()
            ->keyBy('circuit_id');

        return $latestAggregates->map(function ($latest) use ($previousAggregates) {
            $previous = $previousAggregates->get($latest->circuit_id);

            $comparison = $this->compare(
                $latest->toArray(),
                $previous
            );

            return [
                'circuit_id' => $latest->circuit_id,
                'has_changes' => $comparison['has_changes'],
                'delta' => $comparison['delta'],
                'latest_date' => $latest->aggregate_date,
            ];
        })->filter(fn ($item) => $item['has_changes']);
    }
}
