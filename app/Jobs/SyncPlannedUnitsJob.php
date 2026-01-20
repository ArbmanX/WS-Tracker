<?php

namespace App\Jobs;

use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\SyncLog;
use App\Services\Sync\SyncOutputLogger;
use App\Services\WorkStudio\Aggregation\AggregateCalculationService;
use App\Services\WorkStudio\Aggregation\AggregateDiffService;
use App\Services\WorkStudio\Aggregation\AggregateStorageService;
use App\Services\WorkStudio\PlannedUnitsSnapshotService;
use App\Services\WorkStudio\WorkStudioApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Job for syncing planned units data from WorkStudio API.
 *
 * This job provides enhanced control over planned units syncing including:
 * - Batch mode (sync all circuits matching analytics filters)
 * - Individual circuit mode (sync specific circuits)
 * - Dry-run mode (preview what would sync without executing)
 * - Real-time progress tracking via SyncOutputLogger
 */
class SyncPlannedUnitsJob implements ShouldQueue
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
     * @param  SyncTrigger  $triggerType  What triggered this sync
     * @param  int|null  $triggeredByUserId  User who triggered the sync (if manual)
     * @param  array<int>|null  $circuitIds  Specific circuit IDs to sync (null for batch mode)
     * @param  bool  $respectFilters  Whether to apply AnalyticsSetting filters in batch mode
     * @param  bool  $dryRun  If true, only preview what would sync without executing
     * @param  string|null  $outputLoggerKey  Key for SyncOutputLogger (for live progress)
     */
    public function __construct(
        private SyncTrigger $triggerType = SyncTrigger::Manual,
        private ?int $triggeredByUserId = null,
        private ?array $circuitIds = null,
        private bool $respectFilters = true,
        private bool $dryRun = false,
        private ?string $outputLoggerKey = null,
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
        // Check if sync is globally enabled
        if (! AnalyticsSetting::isPlannedUnitsSyncEnabled() && empty($this->circuitIds)) {
            Log::info('Planned units sync is globally disabled, skipping batch sync');

            return;
        }

        // Initialize output logger if key provided
        $outputLogger = $this->outputLoggerKey
            ? new SyncOutputLogger($this->outputLoggerKey)
            : null;

        $modeLabel = $this->dryRun ? '[DRY RUN] ' : '';
        $outputLogger?->start("{$modeLabel}Starting planned units sync...");

        // Start sync log (only for non-dry-run)
        $syncLog = null;
        if (! $this->dryRun) {
            $syncLog = SyncLog::start(
                type: SyncType::Aggregates,
                trigger: $this->triggerType,
                triggeredBy: $this->triggeredByUserId,
                context: [
                    'circuit_ids' => $this->circuitIds,
                    'respect_filters' => $this->respectFilters,
                    'dry_run' => $this->dryRun,
                ],
            );
            $outputLogger?->info('Sync log ID: '.$syncLog->id);
        }

        try {
            // Health check
            $outputLogger?->info('Checking WorkStudio API health...');
            if (! $api->healthCheck()) {
                throw new \RuntimeException('WorkStudio API is unavailable');
            }
            $outputLogger?->success('API health check passed');

            // Get circuits to sync
            $circuits = $this->getCircuitsToSync();
            $totalCircuits = $circuits->count();

            if ($totalCircuits === 0) {
                $outputLogger?->warning('No circuits found to sync');
                $syncLog?->complete(['circuits_processed' => 0, 'message' => 'No circuits to sync']);

                return;
            }

            $outputLogger?->info("Found {$totalCircuits} circuits to sync");

            $results = [
                'circuits_processed' => 0,
                'circuits_skipped' => 0,
                'aggregates_created' => 0,
                'snapshots_created' => 0,
                'changes_detected' => 0,
                'errors' => [],
            ];

            $callCount = 0;
            $callsBeforeDelay = config('workstudio.sync.calls_before_delay', 5);
            $rateLimitDelay = config('workstudio.sync.rate_limit_delay', 500000); // 500ms

            foreach ($circuits as $index => $circuit) {
                $current = $index + 1;
                $outputLogger?->progress($current, $totalCircuits, "{$modeLabel}Processing {$circuit->work_order}");

                try {
                    if ($this->dryRun) {
                        // Dry run - just report what would be done
                        $wouldSync = $this->checkIfWouldSync($circuit, $diffService);
                        if ($wouldSync) {
                            $outputLogger?->info("[DRY RUN] Would sync: {$circuit->work_order}");
                            $results['changes_detected']++;
                        } else {
                            $outputLogger?->info("[DRY RUN] Would skip (no changes): {$circuit->work_order}");
                            $results['circuits_skipped']++;
                        }
                        $results['circuits_processed']++;

                        continue;
                    }

                    // Actual sync logic
                    $previousPercent = (float) $circuit->percent_complete;
                    $previousStatus = $circuit->api_status;

                    // Calculate aggregate from current data
                    $aggregateData = $calculationService->calculateForCircuit($circuit);

                    // Check for changes
                    $existingAggregate = $circuit->latestAggregate;
                    $diff = $diffService->compare($aggregateData, $existingAggregate);

                    if ($diff['has_changes']) {
                        // Store the aggregate
                        $storageService->storeCircuitAggregate($aggregateData);
                        $results['aggregates_created']++;
                        $results['changes_detected']++;

                        $outputLogger?->success("Synced {$circuit->work_order}: changes detected");

                        // Check if we should create a snapshot
                        $rawUnits = $api->getPlannedUnits($circuit->work_order);
                        if ($snapshotService->createSnapshotIfNeeded(
                            $circuit,
                            $rawUnits,
                            $previousStatus,
                            $previousPercent
                        )) {
                            $results['snapshots_created']++;
                            $outputLogger?->info("Created snapshot for {$circuit->work_order}");
                        }
                    } else {
                        $outputLogger?->info("Skipped {$circuit->work_order}: no changes");
                        $results['circuits_skipped']++;
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

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'circuit_id' => $circuit->id,
                        'work_order' => $circuit->work_order,
                        'error' => $e->getMessage(),
                    ];

                    $outputLogger?->error("Failed {$circuit->work_order}: {$e->getMessage()}");

                    Log::error('Failed to sync planned units for circuit', [
                        'circuit_id' => $circuit->id,
                        'work_order' => $circuit->work_order,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Complete sync log
            if ($syncLog) {
                if (! empty($results['errors']) && $results['circuits_processed'] > 0) {
                    $syncLog->completeWithWarning(
                        count($results['errors']).' circuits failed to sync',
                        $results
                    );
                } else {
                    $syncLog->complete($results);
                }
            }

            // Summary message
            $summaryMessage = $this->dryRun
                ? sprintf(
                    '[DRY RUN] Preview: %d circuits checked, %d would sync, %d would skip',
                    $results['circuits_processed'],
                    $results['changes_detected'],
                    $results['circuits_skipped']
                )
                : sprintf(
                    'Sync completed: %d processed, %d synced, %d skipped, %d errors',
                    $results['circuits_processed'],
                    $results['aggregates_created'],
                    $results['circuits_skipped'],
                    count($results['errors'])
                );

            $outputLogger?->complete($summaryMessage);

            Log::info('Planned units sync completed', [
                'sync_log_id' => $syncLog?->id,
                'dry_run' => $this->dryRun,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            $syncLog?->fail($e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            $outputLogger?->fail('Sync failed: '.$e->getMessage());

            Log::error('Planned units sync failed', [
                'sync_log_id' => $syncLog?->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the circuits that should be synced.
     */
    protected function getCircuitsToSync(): Collection
    {
        // If specific circuit IDs provided, use those
        if (! empty($this->circuitIds)) {
            return Circuit::whereIn('id', $this->circuitIds)
                ->with('latestAggregate')
                ->get();
        }

        // Build query for batch mode
        $query = Circuit::query()
            ->where('planned_units_sync_enabled', true)
            ->whereNull('deleted_at')
            ->notExcluded()
            ->with('latestAggregate');

        // Apply analytics filters if requested
        if ($this->respectFilters) {
            $query->forAnalytics();
        }

        // Apply needs sync scope
        $query->needsSync();

        // Default: only sync active circuits in batch mode
        $query->whereIn('api_status', ['ACTIV', 'QC', 'REWRK']);

        return $query->get();
    }

    /**
     * Check if a circuit would be synced (for dry-run mode).
     */
    protected function checkIfWouldSync(Circuit $circuit, AggregateDiffService $diffService): bool
    {
        // In a real dry-run, we'd fetch the data and compare
        // For now, we check if there's no recent sync
        if (! $circuit->last_planned_units_synced_at) {
            return true;
        }

        $interval = AnalyticsSetting::getSyncIntervalHours();

        return $circuit->last_planned_units_synced_at->lt(now()->subHours($interval));
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Planned units sync job failed permanently', [
            'trigger' => $this->triggerType->value,
            'triggered_by' => $this->triggeredByUserId,
            'circuit_ids' => $this->circuitIds,
            'dry_run' => $this->dryRun,
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
        $tags = ['sync', 'planned-units'];

        if ($this->dryRun) {
            $tags[] = 'dry-run';
        }

        if ($this->circuitIds) {
            $tags[] = 'circuit_count:'.count($this->circuitIds);
        } else {
            $tags[] = 'batch-mode';
        }

        return $tags;
    }
}
