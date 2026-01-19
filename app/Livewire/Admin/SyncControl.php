<?php

namespace App\Livewire\Admin;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Jobs\SyncCircuitsJob;
use App\Models\SyncLog;
use App\Services\Sync\SyncOutputLogger;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Sync Control'])]
class SyncControl extends Component
{
    public array $selectedStatuses = ['ACTIV'];

    public bool $forceOverwrite = false;

    public bool $runInBackground = true;

    public int $lastLogIndex = 0;

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
     * Trigger a manual sync.
     */
    public function triggerSync(): void
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
            $this->dispatchBackgroundSync();
        } else {
            $this->runSynchronousSync();
        }
    }

    /**
     * Dispatch sync job to the queue (background execution).
     */
    protected function dispatchBackgroundSync(): void
    {
        $loggerKey = $this->getLoggerKey();

        dispatch(new SyncCircuitsJob(
            statuses: $this->selectedStatuses,
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: auth()->id(),
            forceOverwrite: $this->forceOverwrite,
            outputLoggerKey: $loggerKey,
        ));

        $this->dispatch('notify', message: 'Sync job dispatched to queue.', type: 'success');

        // Clear computed property cache
        unset($this->isSyncing);
        unset($this->lastSync);
        unset($this->recentSyncs);
        unset($this->isOutputRunning);
        unset($this->syncOutput);
    }

    /**
     * Run sync synchronously with live output.
     */
    protected function runSynchronousSync(): void
    {
        $loggerKey = $this->getLoggerKey();

        // Create and run the job synchronously
        $job = new SyncCircuitsJob(
            statuses: $this->selectedStatuses,
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: auth()->id(),
            forceOverwrite: $this->forceOverwrite,
            outputLoggerKey: $loggerKey,
        );

        // Run synchronously using dispatchSync
        dispatch_sync($job);

        $this->dispatch('notify', message: 'Sync completed.', type: 'success');

        // Clear computed property cache
        unset($this->isSyncing);
        unset($this->lastSync);
        unset($this->recentSyncs);
        unset($this->isOutputRunning);
        unset($this->syncOutput);
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

    public function render()
    {
        return view('livewire.admin.sync-control');
    }
}
