<?php

namespace App\Jobs;

use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Models\Circuit;
use App\Models\SyncLog;
use App\Services\WorkStudio\Aggregation\AggregateCalculationService;
use App\Services\WorkStudio\Aggregation\AggregateDiffService;
use App\Services\WorkStudio\Aggregation\AggregateStorageService;
use App\Services\WorkStudio\PlannedUnitsSnapshotService;
use App\Services\WorkStudio\WorkStudioApiService;
use App\Support\WorkStudioStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job for syncing circuit aggregates (planned units data) from WorkStudio API.
 *
 * Fetches planned units for each circuit needing sync, computes aggregates,
 * and stores them. Also creates planned units snapshots when milestones are reached.
 */
class SyncCircuitAggregatesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds before the job should timeout.
     */
    public int $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     *
     * @param  array<string>|null  $apiStatuses  Only sync circuits with these API statuses (null for all)
     * @param  SyncTrigger  $triggerType  What triggered this sync
     * @param  int|null  $triggeredByUserId  User who triggered the sync (if manual)
     * @param  array<int>|null  $circuitIds  Specific circuit IDs to sync (null for auto-detection)
     */
    public function __construct(
        private ?array $apiStatuses = null,
        private SyncTrigger $triggerType = SyncTrigger::Scheduled,
        private ?int $triggeredByUserId = null,
        private ?array $circuitIds = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        WorkStudioApiService $api,
        AggregateCalculationService $calculationService,
        AggregateStorageService $storageService,
        AggregateDiffService $diffService,
        PlannedUnitsSnapshotService $snapshotService,
    ): void {
        // Start sync log
        $syncLog = SyncLog::start(
            type: SyncType::Aggregates,
            trigger: $this->triggerType,
            apiStatusFilter: $this->apiStatuses ? implode(',', $this->apiStatuses) : null,
            triggeredBy: $this->triggeredByUserId,
            context: [
                'circuit_ids' => $this->circuitIds,
                'api_statuses' => $this->apiStatuses,
            ],
        );

        try {
            // Health check
            if (! $api->healthCheck()) {
                throw new \RuntimeException('WorkStudio API is unavailable');
            }

            // Determine which circuits to sync
            $circuits = $this->getCircuitsToSync();

            if ($circuits->isEmpty()) {
                Log::info('No circuits to sync aggregates for', [
                    'sync_log_id' => $syncLog->id,
                ]);

                $syncLog->complete([
                    'circuits_processed' => 0,
                    'aggregates_created' => 0,
                ]);

                return;
            }

            $results = [
                'circuits_processed' => 0,
                'aggregates_created' => 0,
                'snapshots_created' => 0,
                'errors' => [],
                'changes_detected' => 0,
            ];

            $callCount = 0;
            $callsBeforeDelay = config('workstudio.sync.calls_before_delay', 5);
            $rateLimitDelay = config('workstudio.sync.rate_limit_delay', 500000); // 500ms

            foreach ($circuits as $circuit) {
                try {
                    // Store previous state for milestone detection
                    $previousStatus = $circuit->api_status;
                    $previousPercent = (float) $circuit->percent_complete;

                    // Calculate aggregate
                    $aggregateData = $calculationService->calculateForCircuit($circuit);

                    // Check if there are meaningful changes
                    $existingAggregate = $circuit->latestAggregate;
                    $diff = $diffService->compare($aggregateData, $existingAggregate);

                    if ($diff['has_changes']) {
                        // Store the aggregate
                        $storageService->storeCircuitAggregate($aggregateData);
                        $results['aggregates_created']++;
                        $results['changes_detected']++;

                        // Check if we should create a snapshot
                        $rawUnits = $api->getPlannedUnits($circuit->work_order);
                        if ($snapshotService->createSnapshotIfNeeded(
                            $circuit,
                            $rawUnits,
                            $previousStatus,
                            $previousPercent
                        )) {
                            $results['snapshots_created']++;
                        }
                    }

                    // Update circuit sync timestamp
                    $circuit->update([
                        'last_planned_units_synced_at' => now(),
                    ]);

                    $results['circuits_processed']++;

                    // Rate limiting
                    $callCount++;
                    if ($callCount % $callsBeforeDelay === 0) {
                        usleep($rateLimitDelay);
                    }

                    Log::debug('Synced circuit aggregate', [
                        'circuit_id' => $circuit->id,
                        'work_order' => $circuit->work_order,
                        'has_changes' => $diff['has_changes'],
                    ]);
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'circuit_id' => $circuit->id,
                        'work_order' => $circuit->work_order,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync circuit aggregate', [
                        'circuit_id' => $circuit->id,
                        'work_order' => $circuit->work_order,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Complete with appropriate status
            if (! empty($results['errors']) && $results['circuits_processed'] > 0) {
                $syncLog->completeWithWarning(
                    count($results['errors']).' circuits failed aggregate sync',
                    $results
                );
            } else {
                $syncLog->complete($results);
            }

            Log::info('Aggregate sync completed', [
                'sync_log_id' => $syncLog->id,
                'processed' => $results['circuits_processed'],
                'aggregates_created' => $results['aggregates_created'],
                'changes_detected' => $results['changes_detected'],
            ]);

        } catch (\Exception $e) {
            $syncLog->fail($e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            Log::error('Aggregate sync failed', [
                'sync_log_id' => $syncLog->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the circuits that need aggregate syncing.
     */
    protected function getCircuitsToSync(): \Illuminate\Support\Collection
    {
        // If specific circuit IDs provided, use those
        if (! empty($this->circuitIds)) {
            return Circuit::whereIn('id', $this->circuitIds)
                ->where('planned_units_sync_enabled', true)
                ->with('latestAggregate')
                ->get();
        }

        // Build query for circuits needing sync
        $query = Circuit::query()
            ->where('planned_units_sync_enabled', true)
            ->whereNull('deleted_at')
            ->notExcluded()
            ->with('latestAggregate');

        // Filter by API status if provided
        if (! empty($this->apiStatuses)) {
            $query->whereIn('api_status', $this->apiStatuses);
        } else {
            // Default: only sync active, QC, and rework circuits
            $query->whereIn('api_status', WorkStudioStatus::plannedUnitsSyncable());
        }

        // Apply needs sync scope (not synced recently)
        $query->needsSync();

        return $query->get();
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Aggregate sync job failed permanently', [
            'api_statuses' => $this->apiStatuses,
            'circuit_ids' => $this->circuitIds,
            'trigger' => $this->triggerType->value,
            'triggered_by' => $this->triggeredByUserId,
            'exception' => $exception->getMessage(),
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
        $tags = ['sync', 'aggregates'];

        if ($this->apiStatuses) {
            $tags[] = 'statuses:'.implode(',', $this->apiStatuses);
        }

        if ($this->circuitIds) {
            $tags[] = 'circuit_count:'.count($this->circuitIds);
        }

        return $tags;
    }
}
