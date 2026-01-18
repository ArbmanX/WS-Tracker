<?php

namespace App\Livewire\Admin;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Models\SyncLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout.app-shell', ['title' => 'Sync History'])]
class SyncHistory extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'trigger')]
    public string $triggerFilter = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    /**
     * Reset pagination when filters change.
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTriggerFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'triggerFilter', 'typeFilter']);
        $this->resetPage();
    }

    /**
     * Get available status options.
     */
    public function getStatusOptionsProperty(): array
    {
        return collect(SyncStatus::cases())
            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
            ->toArray();
    }

    /**
     * Get available trigger options.
     */
    public function getTriggerOptionsProperty(): array
    {
        return collect(SyncTrigger::cases())
            ->mapWithKeys(fn ($trigger) => [$trigger->value => $trigger->label()])
            ->toArray();
    }

    /**
     * Get available type options.
     */
    public function getTypeOptionsProperty(): array
    {
        return collect(SyncType::cases())
            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
            ->toArray();
    }

    public function render()
    {
        $logs = SyncLog::query()
            ->with(['triggeredBy', 'region'])
            ->when($this->statusFilter, fn ($q) => $q->where('sync_status', $this->statusFilter))
            ->when($this->triggerFilter, fn ($q) => $q->where('sync_trigger', $this->triggerFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('sync_type', $this->typeFilter))
            ->latest('started_at')
            ->paginate(15);

        return view('livewire.admin.sync-history', [
            'logs' => $logs,
        ]);
    }
}
