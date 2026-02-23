<?php

namespace App\Livewire\Admin;

use App\Models\SyncLog;
use App\Services\Sync\SyncLogFilterOptionsService;
use App\Services\Sync\SyncLogQueryService;
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
        return app(SyncLogFilterOptionsService::class)->statusOptions();
    }

    /**
     * Get available trigger options.
     */
    public function getTriggerOptionsProperty(): array
    {
        return app(SyncLogFilterOptionsService::class)->triggerOptions();
    }

    /**
     * Get available type options.
     */
    public function getTypeOptionsProperty(): array
    {
        return app(SyncLogFilterOptionsService::class)->typeOptions();
    }

    public function render()
    {
        $logs = app(SyncLogQueryService::class)
            ->filtered(
                status: $this->statusFilter,
                trigger: $this->triggerFilter,
                type: $this->typeFilter,
            )
            ->latest('started_at')
            ->paginate(15);

        return view('livewire.admin.sync-history', [
            'logs' => $logs,
        ]);
    }
}
