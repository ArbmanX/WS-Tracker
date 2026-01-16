<?php

namespace App\Jobs;

use App\Enums\SnapshotType;
use App\Models\Circuit;
use App\Models\CircuitSnapshot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job for creating daily snapshots of all active circuits.
 *
 * Captures the current state of each non-closed circuit for historical tracking.
 * Skips circuits that already have a snapshot for today.
 */
class CreateDailySnapshotsJob implements ShouldQueue
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
     * @param  string|null  $snapshotDate  Date for snapshots (defaults to today)
     * @param  array<string>|null  $excludeStatuses  API statuses to exclude (defaults to ['CLOSE'])
     */
    public function __construct(
        private ?string $snapshotDate = null,
        private ?array $excludeStatuses = null,
    ) {
        $this->snapshotDate ??= now()->toDateString();
        $this->excludeStatuses ??= ['CLOSE'];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting daily snapshot creation', [
            'date' => $this->snapshotDate,
            'exclude_statuses' => $this->excludeStatuses,
        ]);

        $results = [
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_excluded' => 0,
            'errors' => [],
        ];

        // Get circuits that need snapshots
        $circuits = $this->getCircuitsForSnapshot();

        // Get circuits that already have snapshots for today
        $existingSnapshotCircuitIds = CircuitSnapshot::where('snapshot_date', $this->snapshotDate)
            ->where('snapshot_type', SnapshotType::Daily)
            ->pluck('circuit_id')
            ->toArray();

        foreach ($circuits as $circuit) {
            try {
                // Skip if already has a snapshot for today
                if (in_array($circuit->id, $existingSnapshotCircuitIds)) {
                    $results['skipped_existing']++;

                    continue;
                }

                // Create snapshot
                DB::transaction(function () use ($circuit) {
                    CircuitSnapshot::createFromCircuit(
                        $circuit,
                        SnapshotType::Daily
                    );
                });

                $results['created']++;

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'circuit_id' => $circuit->id,
                    'work_order' => $circuit->work_order,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to create daily snapshot', [
                    'circuit_id' => $circuit->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Daily snapshot creation completed', [
            'date' => $this->snapshotDate,
            'created' => $results['created'],
            'skipped_existing' => $results['skipped_existing'],
            'errors_count' => count($results['errors']),
        ]);
    }

    /**
     * Get circuits that should have daily snapshots created.
     */
    protected function getCircuitsForSnapshot(): \Illuminate\Support\Collection
    {
        return Circuit::query()
            ->whereNull('deleted_at')
            ->whereNotIn('api_status', $this->excludeStatuses)
            ->notExcluded()
            ->with('latestAggregate', 'uiState')
            ->get();
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Daily snapshot job failed permanently', [
            'snapshot_date' => $this->snapshotDate,
            'exclude_statuses' => $this->excludeStatuses,
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
            'snapshots',
            'daily',
            'date:'.$this->snapshotDate,
        ];
    }
}
