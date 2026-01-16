<?php

namespace App\Services\WorkStudio;

use App\Enums\SnapshotTrigger;
use App\Models\Circuit;
use App\Models\PlannedUnitsSnapshot;
use App\Services\WorkStudio\Transformers\PlannedUnitsNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for creating and managing planned units snapshots.
 *
 * Handles snapshot creation with deduplication, milestone detection,
 * and conditional triggering based on circuit state.
 */
class PlannedUnitsSnapshotService
{
    public function __construct(
        protected PlannedUnitsNormalizer $normalizer
    ) {}

    /**
     * Create a snapshot for a circuit from raw planned units data.
     *
     * Returns null if:
     * - Circuit is at 0% (no meaningful data)
     * - Content hash matches existing snapshot (no changes)
     *
     * @param  Collection  $rawData  Raw API response rows
     */
    public function createSnapshot(
        Circuit $circuit,
        Collection $rawData,
        SnapshotTrigger $trigger,
        ?int $createdById = null
    ): ?PlannedUnitsSnapshot {
        // Skip if circuit is at 0% (no meaningful data to capture)
        if ($circuit->percent_complete <= 0 && $trigger !== SnapshotTrigger::Manual) {
            Log::debug('Skipping snapshot for circuit at 0%', [
                'circuit_id' => $circuit->id,
                'work_order' => $circuit->work_order,
            ]);

            return null;
        }

        // Skip if no units data
        if ($rawData->isEmpty() && $trigger !== SnapshotTrigger::Manual) {
            Log::debug('Skipping snapshot - no units data', [
                'circuit_id' => $circuit->id,
            ]);

            return null;
        }

        // Normalize the data
        $normalizedData = $this->normalizer->normalize($rawData);

        // Generate content hash for deduplication
        $contentHash = $this->normalizer->generateHash($normalizedData);

        // Check for duplicate (same content already exists)
        if ($this->isDuplicate($circuit->id, $contentHash)) {
            Log::debug('Skipping snapshot - content unchanged', [
                'circuit_id' => $circuit->id,
                'content_hash' => substr($contentHash, 0, 16).'...',
            ]);

            return null;
        }

        // Get quick stats for model columns
        $quickStats = $this->normalizer->getQuickStats($normalizedData);

        // Create the snapshot
        $snapshot = PlannedUnitsSnapshot::create([
            'circuit_id' => $circuit->id,
            'work_order' => $circuit->work_order,
            'snapshot_trigger' => $trigger,
            'percent_complete' => $circuit->percent_complete,
            'api_status' => $circuit->api_status,
            'content_hash' => $contentHash,
            'unit_count' => $quickStats['unit_count'],
            'total_trees' => $quickStats['total_trees'],
            'total_linear_ft' => $quickStats['total_linear_ft'],
            'total_acres' => $quickStats['total_acres'],
            'raw_json' => $normalizedData,
            'created_by' => $createdById,
        ]);

        Log::info('Created planned units snapshot', [
            'snapshot_id' => $snapshot->id,
            'circuit_id' => $circuit->id,
            'work_order' => $circuit->work_order,
            'trigger' => $trigger->value,
            'unit_count' => $quickStats['unit_count'],
        ]);

        return $snapshot;
    }

    /**
     * Create a manual snapshot (admin triggered).
     */
    public function createManualSnapshot(
        Circuit $circuit,
        Collection $rawData,
        int $createdById
    ): ?PlannedUnitsSnapshot {
        return $this->createSnapshot($circuit, $rawData, SnapshotTrigger::Manual, $createdById);
    }

    /**
     * Check if a 50% milestone snapshot should be created.
     */
    public function shouldCreateMilestone50Snapshot(Circuit $circuit): bool
    {
        // Check if circuit has reached 50% but we don't have a milestone snapshot yet
        if ($circuit->percent_complete < 50) {
            return false;
        }

        return ! PlannedUnitsSnapshot::where('circuit_id', $circuit->id)
            ->where('snapshot_trigger', SnapshotTrigger::Milestone50)
            ->exists();
    }

    /**
     * Check if a 100% milestone snapshot should be created.
     */
    public function shouldCreateMilestone100Snapshot(Circuit $circuit): bool
    {
        if ($circuit->percent_complete < 100) {
            return false;
        }

        return ! PlannedUnitsSnapshot::where('circuit_id', $circuit->id)
            ->where('snapshot_trigger', SnapshotTrigger::Milestone100)
            ->exists();
    }

    /**
     * Check if a QC status change snapshot should be created.
     */
    public function shouldCreateQcSnapshot(Circuit $circuit, ?string $previousStatus): bool
    {
        // Only trigger when status changes TO QC
        if ($circuit->api_status !== 'QC') {
            return false;
        }

        // Only if previous status was not QC
        if ($previousStatus === 'QC') {
            return false;
        }

        // Check if we already have a QC snapshot for this circuit
        // (allow multiple if status flip-flops, but check recent)
        $recentQcSnapshot = PlannedUnitsSnapshot::where('circuit_id', $circuit->id)
            ->where('snapshot_trigger', SnapshotTrigger::StatusToQc)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        return ! $recentQcSnapshot;
    }

    /**
     * Determine appropriate trigger type and create snapshot if conditions are met.
     *
     * Call this during sync to automatically handle milestone/status snapshots.
     */
    public function createSnapshotIfNeeded(
        Circuit $circuit,
        Collection $rawData,
        ?string $previousStatus = null,
        ?float $previousPercent = null
    ): ?PlannedUnitsSnapshot {
        // Priority order: QC status change > 100% milestone > 50% milestone > scheduled

        // Check for QC status change
        if ($this->shouldCreateQcSnapshot($circuit, $previousStatus)) {
            return $this->createSnapshot($circuit, $rawData, SnapshotTrigger::StatusToQc);
        }

        // Check for 100% milestone (only if we just crossed it)
        if ($previousPercent !== null && $previousPercent < 100 && $circuit->percent_complete >= 100) {
            if ($this->shouldCreateMilestone100Snapshot($circuit)) {
                return $this->createSnapshot($circuit, $rawData, SnapshotTrigger::Milestone100);
            }
        }

        // Check for 50% milestone (only if we just crossed it)
        if ($previousPercent !== null && $previousPercent < 50 && $circuit->percent_complete >= 50) {
            if ($this->shouldCreateMilestone50Snapshot($circuit)) {
                return $this->createSnapshot($circuit, $rawData, SnapshotTrigger::Milestone50);
            }
        }

        // Regular scheduled snapshot (with deduplication)
        return $this->createSnapshot($circuit, $rawData, SnapshotTrigger::Scheduled);
    }

    /**
     * Check if content already exists for this circuit.
     */
    protected function isDuplicate(int $circuitId, string $contentHash): bool
    {
        return PlannedUnitsSnapshot::hashExists($circuitId, $contentHash);
    }

    /**
     * Get snapshot history for a circuit.
     */
    public function getHistory(Circuit $circuit, int $limit = 50): Collection
    {
        return PlannedUnitsSnapshot::where('circuit_id', $circuit->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get timeline for a circuit (ordered oldest to newest).
     */
    public function getTimeline(Circuit $circuit): Collection
    {
        return PlannedUnitsSnapshot::timeline($circuit->id);
    }

    /**
     * Compare two snapshots and return the differences.
     *
     * @return array{added: array, removed: array, changed: array}
     */
    public function compareSnapshots(
        PlannedUnitsSnapshot $older,
        PlannedUnitsSnapshot $newer
    ): array {
        $olderUnits = collect($older->getUnits())->keyBy('id');
        $newerUnits = collect($newer->getUnits())->keyBy('id');

        $added = [];
        $removed = [];
        $changed = [];

        // Find added and changed units
        foreach ($newerUnits as $id => $unit) {
            if (! $olderUnits->has($id)) {
                $added[] = $unit;
            } elseif ($olderUnits[$id] !== $unit) {
                $changed[] = [
                    'id' => $id,
                    'before' => $olderUnits[$id],
                    'after' => $unit,
                ];
            }
        }

        // Find removed units
        foreach ($olderUnits as $id => $unit) {
            if (! $newerUnits->has($id)) {
                $removed[] = $unit;
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
        ];
    }
}
