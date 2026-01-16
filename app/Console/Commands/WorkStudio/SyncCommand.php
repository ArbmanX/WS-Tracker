<?php

namespace App\Console\Commands\WorkStudio;

use App\Enums\SyncTrigger;
use App\Jobs\CreateDailySnapshotsJob;
use App\Jobs\SyncCircuitAggregatesJob;
use App\Jobs\SyncCircuitsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'workstudio:sync
        {type? : Type of sync: circuits, aggregates, snapshots, or all}
        {--status=* : API statuses to sync (ACTIV, QC, REWRK, CLOSE)}
        {--force : Force overwrite user modifications}
        {--queue : Queue the job instead of running synchronously}
        {--preview : Preview what would be synced without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Manually trigger WorkStudio sync operations';

    /**
     * Available sync types.
     */
    private const SYNC_TYPES = [
        'circuits' => 'Sync circuit list from API',
        'aggregates' => 'Sync planned units and compute aggregates',
        'snapshots' => 'Create daily snapshots for all circuits',
        'all' => 'Run all sync operations',
    ];

    /**
     * Available API statuses.
     */
    private const API_STATUSES = [
        'ACTIV' => 'Active circuits',
        'QC' => 'Quality Control',
        'REWRK' => 'Rework',
        'CLOSE' => 'Closed',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type') ?? $this->promptForType();
        $statuses = $this->getStatuses();
        $forceOverwrite = $this->option('force');
        $queue = $this->option('queue');
        $preview = $this->option('preview');

        // Confirm force overwrite
        if ($forceOverwrite && ! $this->confirmForceOverwrite()) {
            warning('Sync cancelled.');

            return self::SUCCESS;
        }

        // Preview mode
        if ($preview) {
            return $this->previewSync($type, $statuses);
        }

        // Show what we're about to do
        $this->displaySyncInfo($type, $statuses, $forceOverwrite, $queue);


        /** Get current user ID if available
         *
        */ 
        $userId = Auth::user()?->id;

        // Dispatch the appropriate job(s)
        return match ($type) {
            'circuits' => $this->syncCircuits($statuses, $forceOverwrite, $queue, $userId),
            'aggregates' => $this->syncAggregates($statuses, $queue, $userId),
            'snapshots' => $this->createSnapshots($queue),
            'all' => $this->syncAll($statuses, $forceOverwrite, $queue, $userId),
            default => $this->invalidType($type),
        };
    }

    /**
     * Prompt for sync type interactively.
     */
    private function promptForType(): string
    {
        return select(
            label: 'What would you like to sync?',
            options: self::SYNC_TYPES,
            default: 'circuits'
        );
    }

    /**
     * Get the statuses to sync.
     */
    private function getStatuses(): array
    {
        $statuses = $this->option('status');

        if (empty($statuses)) {
            // If running interactively, prompt for statuses
            if ($this->input->isInteractive() && ! $this->argument('type')) {
                $statuses = multiselect(
                    label: 'Which statuses do you want to sync?',
                    options: self::API_STATUSES,
                    default: ['ACTIV'],
                    required: true
                );
            } else {
                // Default to ACTIV
                $statuses = ['ACTIV'];
            }
        }

        return $statuses;
    }

    /**
     * Confirm force overwrite with the user.
     */
    private function confirmForceOverwrite(): bool
    {
        if (! $this->input->isInteractive()) {
            return true; // Assume confirmed in non-interactive mode
        }

        warning('Force overwrite will replace ALL user modifications with API data!');

        return confirm(
            label: 'Are you sure you want to overwrite user modifications?',
            default: false
        );
    }

    /**
     * Display information about the sync operation.
     */
    private function displaySyncInfo(string $type, array $statuses, bool $forceOverwrite, bool $queue): void
    {
        info('Starting WorkStudio Sync');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Type', self::SYNC_TYPES[$type] ?? $type],
                ['Statuses', implode(', ', $statuses)],
                ['Force Overwrite', $forceOverwrite ? 'Yes' : 'No'],
                ['Execution', $queue ? 'Queued' : 'Synchronous'],
            ]
        );
    }

    /**
     * Preview sync without making changes.
     */
    private function previewSync(string $type, array $statuses): int
    {
        note('Preview mode - no changes will be made');

        if ($type === 'circuits' || $type === 'all') {
            $syncService = app(\App\Services\WorkStudio\Sync\CircuitSyncService::class);
            $api = app(\App\Services\WorkStudio\WorkStudioApiService::class);
            $transformer = app(\App\Services\WorkStudio\Transformers\CircuitTransformer::class);

            foreach ($statuses as $status) {
                $this->info("Fetching circuits with status: {$status}...");

                try {
                    $circuits = $api->getCircuitsByStatus($status);
                    $preview = $syncService->previewSync($circuits, false);

                    $this->table(
                        ['Metric', 'Count'],
                        [
                            ['Would Create', $preview['would_create']],
                            ['Would Update', $preview['would_update']],
                            ['Unchanged', $preview['unchanged']],
                            ['Would Preserve User Fields', count($preview['would_preserve'])],
                        ]
                    );

                    if (! empty($preview['would_preserve'])) {
                        $this->info('Circuits with preserved user modifications:');
                        foreach ($preview['would_preserve'] as $guid => $fields) {
                            $this->line("  - {$guid}: ".implode(', ', array_keys($fields)));
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to fetch circuits: {$e->getMessage()}");
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * Sync circuits.
     */
    private function syncCircuits(array $statuses, bool $forceOverwrite, bool $queue, ?int $userId): int
    {
        $job = new SyncCircuitsJob(
            statuses: $statuses,
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: $userId,
            forceOverwrite: $forceOverwrite
        );

        if ($queue) {
            dispatch($job);
            info('Circuit sync job queued successfully.');
        } else {
            $this->info('Running circuit sync...');

            try {
                app()->call([$job, 'handle']);
                info('Circuit sync completed successfully.');
            } catch (\Exception $e) {
                $this->error("Sync failed: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Sync aggregates.
     */
    private function syncAggregates(array $statuses, bool $queue, ?int $userId): int
    {
        $job = new SyncCircuitAggregatesJob(
            apiStatuses: $statuses,
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: $userId
        );

        if ($queue) {
            dispatch($job);
            info('Aggregate sync job queued successfully.');
        } else {
            $this->info('Running aggregate sync...');

            try {
                app()->call([$job, 'handle']);
                info('Aggregate sync completed successfully.');
            } catch (\Exception $e) {
                $this->error("Sync failed: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Create daily snapshots.
     */
    private function createSnapshots(bool $queue): int
    {
        $job = new CreateDailySnapshotsJob;

        if ($queue) {
            dispatch($job);
            info('Snapshot creation job queued successfully.');
        } else {
            $this->info('Creating daily snapshots...');

            try {
                app()->call([$job, 'handle']);
                info('Snapshots created successfully.');
            } catch (\Exception $e) {
                $this->error("Snapshot creation failed: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Run all sync operations.
     */
    private function syncAll(array $statuses, bool $forceOverwrite, bool $queue, ?int $userId): int
    {
        $this->info('Running all sync operations...');

        // Sync circuits first
        $result = $this->syncCircuits($statuses, $forceOverwrite, $queue, $userId);
        if ($result !== self::SUCCESS) {
            return $result;
        }

        // Then aggregates (with delay if queued)
        if ($queue) {
            dispatch(new SyncCircuitAggregatesJob(
                apiStatuses: $statuses,
                triggerType: SyncTrigger::Manual,
                triggeredByUserId: $userId
            ))->delay(now()->addMinutes(2));
            info('Aggregate sync job queued (will run in 2 minutes).');
        } else {
            $result = $this->syncAggregates($statuses, false, $userId);
            if ($result !== self::SUCCESS) {
                return $result;
            }
        }

        // Finally snapshots
        return $this->createSnapshots($queue);
    }

    /**
     * Handle invalid sync type.
     */
    private function invalidType(string $type): int
    {
        $this->error("Invalid sync type: {$type}");
        $this->line('Available types: '.implode(', ', array_keys(self::SYNC_TYPES)));

        return self::FAILURE;
    }
}
