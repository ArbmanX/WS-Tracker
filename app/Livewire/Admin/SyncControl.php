<?php

namespace App\Livewire\Admin;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Jobs\BuildAggregatesJob;
use App\Jobs\SyncCircuitsJob;
use App\Jobs\SyncPlannedUnitsJob;
use App\Models\AnalyticsSetting;
use App\Models\Circuit;
use App\Models\SyncLog;
use App\Services\Sync\SyncOutputLogger;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Sync Control'])]
class SyncControl extends Component
{
    /**
     * Current active tab.
     */
    #[Url]
    public string $activeTab = 'circuits';

    /**
     * Circuit sync options.
     */
    public array $selectedStatuses = ['ACTIV'];

    public bool $forceOverwrite = false;

    public bool $runInBackground = true;

    /**
     * Planned units sync options.
     */
    public bool $plannedUnitsRespectFilters = true;

    public bool $plannedUnitsDryRun = false;

    /**
     * Aggregates build options.
     */
    public string $aggregateType = 'both';

    public ?string $aggregateDate = null;

    /**
     * Global sync settings.
     */
    public bool $globalSyncEnabled = true;

    public int $syncIntervalHours = 12;

    /**
     * Track last log index for efficient polling.
     */
    public int $lastLogIndex = 0;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $settings = AnalyticsSetting::instance();
        $this->globalSyncEnabled = $settings->planned_units_sync_enabled ?? true;
        $this->syncIntervalHours = $settings->sync_interval_hours ?? 12;
        $this->aggregateDate = now()->format('Y-m-d');
    }

    /**
     * Get the output logger key for the current user.
     */
    protected function getLoggerKey(): string
    {
        return 'user_'.auth()->id();
    }

    /**
     * Get the output logger instance.
     */
    protected function getLogger(): SyncOutputLogger
    {
        return new SyncOutputLogger($this->getLoggerKey());
    }

    /**
     * Get available API statuses for syncing.
     */
    #[Computed]
    public function availableStatuses(): array
    {
        return [
            'ACTIV' => 'In Progress',
            'QC' => 'Quality Control',
            'REWRK' => 'Rework',
            'CLOSE' => 'Closed',
        ];
    }

    /**
     * Get available aggregate types.
     */
    #[Computed]
    public function aggregateTypes(): array
    {
        return [
            'daily' => 'Daily Only',
            'weekly' => 'Weekly Only',
            'both' => 'Both Daily & Weekly',
        ];
    }

    /**
     * Get the most recent sync log.
     */
    #[Computed]
    public function lastSync(): ?SyncLog
    {
        return SyncLog::query()
            ->latest('started_at')
            ->first();
    }

    /**
     * Get recent sync logs for display.
     */
    #[Computed]
    public function recentSyncs(): \Illuminate\Support\Collection
    {
        return SyncLog::query()
            ->with('triggeredBy')
            ->latest('started_at')
            ->limit(5)
            ->get();
    }

    /**
     * Check if a sync is currently in progress (database).
     */
    #[Computed]
    public function isSyncing(): bool
    {
        return SyncLog::query()
            ->where('sync_status', SyncStatus::Started)
            ->where('started_at', '>=', now()->subMinutes(30))
            ->exists();
    }

    /**
     * Check if sync output logger is currently running.
     */
    #[Computed]
    public function isOutputRunning(): bool
    {
        return $this->getLogger()->isRunning();
    }

    /**
     * Get the current sync output state.
     */
    #[Computed]
    public function syncOutput(): array
    {
        return $this->getLogger()->getOutput();
    }

    /**
     * Get circuits count that need sync.
     */
    #[Computed]
    public function circuitsNeedingSync(): int
    {
        return Circuit::query()
            ->where('planned_units_sync_enabled', true)
            ->notExcluded()
            ->needsSync()
            ->whereIn('api_status', ['ACTIV', 'QC', 'REWRK'])
            ->count();
    }

    /**
     * Get circuits count matching current analytics filters.
     */
    #[Computed]
    public function circuitsMatchingFilters(): int
    {
        return Circuit::query()
            ->forAnalytics()
            ->where('planned_units_sync_enabled', true)
            ->notExcluded()
            ->count();
    }

    /**
     * Get new logs since last poll (for efficient updates).
     */
    public function pollForUpdates(): void
    {
        // This method is called by wire:poll
        // Computed properties will refresh automatically
        unset($this->isOutputRunning);
        unset($this->syncOutput);
        unset($this->isSyncing);
        unset($this->lastSync);
        unset($this->recentSyncs);
        unset($this->circuitsNeedingSync);
    }

    /**
     * Clear the sync output log.
     */
    public function clearLog(): void
    {
        $this->getLogger()->clear();
        $this->lastLogIndex = 0;

        unset($this->syncOutput);
        unset($this->isOutputRunning);
    }

    /**
     * Save global sync settings.
     */
    public function saveGlobalSettings(): void
    {
        if (! auth()->user()?->hasRole('sudo_admin')) {
            $this->dispatch('notify', message: 'Only sudo admins can modify sync settings.', type: 'error');

            return;
        }

        AnalyticsSetting::updateSettings([
            'planned_units_sync_enabled' => $this->globalSyncEnabled,
            'sync_interval_hours' => max(1, min(168, $this->syncIntervalHours)), // 1 hour to 1 week
        ], auth()->user());

        $this->dispatch('notify', message: 'Sync settings saved successfully.', type: 'success');
    }

    /**
     * Trigger a manual circuit sync.
     */
    public function triggerCircuitSync(): void
    {
        if (! auth()->user()?->hasRole('sudo_admin')) {
            $this->dispatch('notify', message: 'Only sudo admins can trigger syncs.', type: 'error');

            return;
        }

        if (empty($this->selectedStatuses)) {
            $this->dispatch('notify', message: 'Please select at least one status to sync.', type: 'warning');

            return;
        }

        if ($this->isSyncing || $this->isOutputRunning) {
            $this->dispatch('notify', message: 'A sync is already in progress.', type: 'warning');

            return;
        }

        // Clear previous output
        $this->clearLog();

        if ($this->runInBackground) {
            $this->dispatchBackgroundCircuitSync();
        } else {
            $this->runSynchronousCircuitSync();
        }
    }

    /**
     * Dispatch circuit sync job to the queue.
     */
    protected function dispatchBackgroundCircuitSync(): void
    {
        $loggerKey = $this->getLoggerKey();

        dispatch(new SyncCircuitsJob(
            statuses: $this->selectedStatuses,
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: auth()->id(),
            forceOverwrite: $this->forceOverwrite,
            outputLoggerKey: $loggerKey,
        ));

        $this->dispatch('notify', message: 'Circuit sync job dispatched to queue.', type: 'success');
        $this->clearComputedCaches();
    }

    /**
     * Run circuit sync synchronously.
     */
    protected function runSynchronousCircuitSync(): void
    {
        $loggerKey = $this->getLoggerKey();

        $job = new SyncCircuitsJob(
            statuses: $this->selectedStatuses,
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: auth()->id(),
            forceOverwrite: $this->forceOverwrite,
            outputLoggerKey: $loggerKey,
        );

        dispatch_sync($job);

        $this->dispatch('notify', message: 'Circuit sync completed.', type: 'success');
        $this->clearComputedCaches();
    }

    /**
     * Trigger a planned units sync.
     */
    public function triggerPlannedUnitsSync(): void
    {
        if (! auth()->user()?->hasRole('sudo_admin')) {
            $this->dispatch('notify', message: 'Only sudo admins can trigger syncs.', type: 'error');

            return;
        }

        if ($this->isSyncing || $this->isOutputRunning) {
            $this->dispatch('notify', message: 'A sync is already in progress.', type: 'warning');

            return;
        }

        // Clear previous output
        $this->clearLog();

        $loggerKey = $this->getLoggerKey();

        dispatch(new SyncPlannedUnitsJob(
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: auth()->id(),
            circuitIds: null, // Batch mode
            respectFilters: $this->plannedUnitsRespectFilters,
            dryRun: $this->plannedUnitsDryRun,
            outputLoggerKey: $loggerKey,
        ));

        $message = $this->plannedUnitsDryRun
            ? 'Planned units dry-run dispatched to queue.'
            : 'Planned units sync job dispatched to queue.';

        $this->dispatch('notify', message: $message, type: 'success');
        $this->clearComputedCaches();
    }

    /**
     * Trigger aggregate build.
     */
    public function triggerAggregatesBuild(): void
    {
        if (! auth()->user()?->hasRole('sudo_admin')) {
            $this->dispatch('notify', message: 'Only sudo admins can trigger aggregate builds.', type: 'error');

            return;
        }

        if ($this->isSyncing || $this->isOutputRunning) {
            $this->dispatch('notify', message: 'A sync is already in progress.', type: 'warning');

            return;
        }

        // Clear previous output
        $this->clearLog();

        $loggerKey = $this->getLoggerKey();
        $targetDate = $this->aggregateDate ? \Carbon\Carbon::parse($this->aggregateDate) : now();

        dispatch(new BuildAggregatesJob(
            aggregateType: $this->aggregateType,
            targetDate: $targetDate,
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: auth()->id(),
            outputLoggerKey: $loggerKey,
        ));

        $this->dispatch('notify', message: 'Aggregates build job dispatched to queue.', type: 'success');
        $this->clearComputedCaches();
    }

    /**
     * Cancel the current sync (clears output logger state).
     * Note: This doesn't actually stop a running queue job.
     */
    public function cancelSync(): void
    {
        $logger = $this->getLogger();

        if ($logger->isRunning()) {
            $logger->fail('Sync cancelled by user');
        }

        unset($this->isOutputRunning);
        unset($this->syncOutput);
    }

    /**
     * Clear computed property caches.
     */
    protected function clearComputedCaches(): void
    {
        unset($this->isSyncing);
        unset($this->lastSync);
        unset($this->recentSyncs);
        unset($this->isOutputRunning);
        unset($this->syncOutput);
        unset($this->circuitsNeedingSync);
        unset($this->circuitsMatchingFilters);
    }

    public function render()
    {
        return view('livewire.admin.sync-control');
    }
}
