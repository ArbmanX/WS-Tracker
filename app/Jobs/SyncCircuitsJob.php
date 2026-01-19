<?php

namespace App\Jobs;

use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Events\SyncCompletedEvent;
use App\Events\SyncFailedEvent;
use App\Events\SyncStartedEvent;
use App\Models\SyncLog;
use App\Services\Sync\SyncOutputLogger;
use App\Services\WorkStudio\Sync\CircuitSyncService;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use App\Services\WorkStudio\WorkStudioApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job for syncing circuits from the WorkStudio API.
 *
 * Fetches circuits by status from the API and syncs them to the database,
 * respecting user modifications unless force overwrite is enabled.
 */
class SyncCircuitsJob implements ShouldQueue
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
     * @param  array<string>  $statuses  API statuses to sync (e.g., ['ACTIV', 'QC'])
     * @param  SyncTrigger  $triggerType  What triggered this sync
     * @param  int|null  $triggeredByUserId  User who triggered the sync (if manual)
     * @param  bool  $forceOverwrite  If true, overwrite user modifications
     * @param  int|null  $regionId  Optional region filter
     * @param  string|null  $outputLoggerKey  Key for SyncOutputLogger (for live progress)
     */
    public function __construct(
        private array $statuses,
        private SyncTrigger $triggerType = SyncTrigger::Scheduled,
        private ?int $triggeredByUserId = null,
        private bool $forceOverwrite = false,
        private ?int $regionId = null,
        private ?string $outputLoggerKey = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        WorkStudioApiService $api,
        CircuitTransformer $transformer,
        CircuitSyncService $syncService,
    ): void {
        // Initialize output logger if key provided
        $outputLogger = $this->outputLoggerKey
            ? new SyncOutputLogger($this->outputLoggerKey)
            : null;

        // Start sync log
        $syncLog = SyncLog::start(
            type: SyncType::CircuitList,
            trigger: $this->triggerType,
            regionId: $this->regionId,
            apiStatusFilter: implode(',', $this->statuses),
            triggeredBy: $this->triggeredByUserId,
            context: [
                'force_overwrite' => $this->forceOverwrite,
                'statuses' => $this->statuses,
            ],
        );

        // Dispatch started event
        event(new SyncStartedEvent($syncLog));

        $outputLogger?->start('Starting circuit sync for: '.implode(', ', $this->statuses));
        $outputLogger?->info('Sync log ID: '.$syncLog->id);

        try {
            // Health check
            $outputLogger?->info('Checking WorkStudio API health...');
            if (! $api->healthCheck()) {
                throw new \RuntimeException('WorkStudio API is unavailable');
            }
            $outputLogger?->success('API health check passed');

            $totalResults = [
                'circuits_processed' => 0,
                'circuits_created' => 0,
                'circuits_updated' => 0,
                'user_preserved_fields' => [],
                'errors' => [],
                'planners_linked' => 0,
                'planners_unlinked' => 0,
            ];

            $callCount = 0;
            $callsBeforeDelay = config('workstudio.sync.calls_before_delay', 5);
            $rateLimitDelay = config('workstudio.sync.rate_limit_delay', 500000); // 500ms

            // Process each status
            foreach ($this->statuses as $status) {
                $outputLogger?->info("Fetching {$status} circuits from API...");
                Log::info('Syncing circuits', [
                    'status' => $status,
                    'sync_log_id' => $syncLog->id,
                ]);

                // Fetch circuits from API
                $circuits = $api->getCircuitsByStatus($status, $this->triggeredByUserId);
                $circuitCount = count($circuits);
                $outputLogger?->success("Retrieved {$circuitCount} {$status} circuits");

                // Sync each circuit
                $statusProcessed = 0;
                foreach ($circuits as $circuitData) {
                    try {
                        $workOrder = $circuitData['work_order'] ?? 'unknown';

                        // Update progress
                        $outputLogger?->progress(
                            $totalResults['circuits_processed'] + 1,
                            $circuitCount,
                            "Processing {$workOrder}"
                        );

                        // Sync the circuit
                        $circuit = $syncService->syncCircuit(
                            $circuitData,
                            $this->forceOverwrite
                        );

                        // Extract and sync planners
                        $rawApiData = $circuitData['api_data_json'] ?? [];
                        $plannerIdentifiers = $transformer->extractPlanners($rawApiData);

                        if (! empty($plannerIdentifiers)) {
                            $plannerResult = $syncService->syncPlanners($circuit, $plannerIdentifiers);
                            $totalResults['planners_linked'] += $plannerResult['linked'];
                            $totalResults['planners_unlinked'] += $plannerResult['unlinked'];
                        }

                        $totalResults['circuits_processed']++;
                        $statusProcessed++;

                        // Rate limiting
                        $callCount++;
                        if ($callCount % $callsBeforeDelay === 0) {
                            usleep($rateLimitDelay);
                        }
                    } catch (\Exception $e) {
                        $totalResults['errors'][] = [
                            'job_guid' => $circuitData['job_guid'] ?? 'unknown',
                            'work_order' => $circuitData['work_order'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];

                        $outputLogger?->warning("Failed to sync {$circuitData['work_order']}: {$e->getMessage()}");

                        Log::error('Failed to sync circuit', [
                            'job_guid' => $circuitData['job_guid'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $outputLogger?->success("Completed {$status}: {$statusProcessed} circuits processed");
            }

            // Get final results from sync service
            $serviceResults = $syncService->getResults();
            $totalResults['circuits_created'] = $serviceResults['created'];
            $totalResults['circuits_updated'] = $serviceResults['updated'];
            $totalResults['user_preserved_fields'] = $serviceResults['user_preserved_fields'];

            // Determine if we have warnings (errors but some success)
            if (! empty($totalResults['errors']) && $totalResults['circuits_processed'] > 0) {
                $syncLog->completeWithWarning(
                    count($totalResults['errors']).' circuits failed to sync',
                    $totalResults
                );
                $outputLogger?->warning('Sync completed with '.count($totalResults['errors']).' errors');
            } else {
                $syncLog->complete($totalResults);
            }

            // Dispatch completed event
            event(new SyncCompletedEvent($syncLog));

            $summaryMessage = sprintf(
                'Sync completed: %d processed, %d created, %d updated',
                $totalResults['circuits_processed'],
                $totalResults['circuits_created'],
                $totalResults['circuits_updated']
            );

            $outputLogger?->complete($summaryMessage);

            Log::info('Circuit sync completed', [
                'sync_log_id' => $syncLog->id,
                'processed' => $totalResults['circuits_processed'],
                'created' => $totalResults['circuits_created'],
                'updated' => $totalResults['circuits_updated'],
                'preserved_fields_count' => count($totalResults['user_preserved_fields']),
            ]);

            // Dispatch aggregate sync job for updated circuits
            if ($totalResults['circuits_processed'] > 0) {
                $outputLogger?->info('Dispatching aggregate sync job...');
                dispatch(new SyncCircuitAggregatesJob(
                    $this->statuses,
                    $this->triggerType,
                    $this->triggeredByUserId,
                ))->delay(now()->addSeconds(30)); // Delay to allow rate limiting
            }

        } catch (\Exception $e) {
            $syncLog->fail($e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            // Dispatch failed event
            event(new SyncFailedEvent($syncLog, $e));

            $outputLogger?->fail('Sync failed: '.$e->getMessage());

            Log::error('Circuit sync failed', [
                'sync_log_id' => $syncLog->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Circuit sync job failed permanently', [
            'statuses' => $this->statuses,
            'trigger' => $this->triggerType->value,
            'triggered_by' => $this->triggeredByUserId,
            'force_overwrite' => $this->forceOverwrite,
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
        return [
            'sync',
            'circuits',
            'statuses:'.implode(',', $this->statuses),
            $this->forceOverwrite ? 'force-overwrite' : 'preserve-user-changes',
        ];
    }
}
