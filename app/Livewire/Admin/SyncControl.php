<?php

namespace App\Livewire\Admin;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Jobs\SyncCircuitsJob;
use App\Models\SyncLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Sync Control'])]
class SyncControl extends Component
{
    public array $selectedStatuses = ['ACTIV'];

    public bool $forceOverwrite = false;

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
     * Check if a sync is currently in progress.
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

        if ($this->isSyncing) {
            $this->dispatch('notify', message: 'A sync is already in progress.', type: 'warning');

            return;
        }

        dispatch(new SyncCircuitsJob(
            statuses: $this->selectedStatuses,
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: auth()->id(),
            forceOverwrite: $this->forceOverwrite,
        ));

        $this->dispatch('notify', message: 'Sync job dispatched successfully.', type: 'success');

        // Clear computed property cache
        unset($this->isSyncing);
        unset($this->lastSync);
        unset($this->recentSyncs);
    }

    public function render()
    {
        return view('livewire.admin.sync-control');
    }
}
