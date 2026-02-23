<?php

namespace App\Livewire\DataManagement;

use App\Models\SyncLog;
use App\Services\Sync\SyncLogFilterOptionsService;
use App\Services\Sync\SyncLogQueryService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout.app-shell', ['title' => 'Sync Logs'])]
class SyncLogs extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'trigger')]
    public string $triggerFilter = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    // Detail Modal
    public bool $showModal = false;

    public ?int $selectedLogId = null;

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

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'triggerFilter', 'typeFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    /**
     * View sync log details.
     */
    public function viewLog(int $logId): void
    {
        $this->selectedLogId = $logId;
        $this->showModal = true;
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedLogId = null;
    }

    /**
     * Get available status options.
     *
     * @return array<string, string>
     */
    public function getStatusOptionsProperty(): array
    {
        return app(SyncLogFilterOptionsService::class)->statusOptions();
    }

    /**
     * Get available trigger options.
     *
     * @return array<string, string>
     */
    public function getTriggerOptionsProperty(): array
    {
        return app(SyncLogFilterOptionsService::class)->triggerOptions();
    }

    /**
     * Get available type options.
     *
     * @return array<string, string>
     */
    public function getTypeOptionsProperty(): array
    {
        return app(SyncLogFilterOptionsService::class)->typeOptions();
    }

    /**
     * Get the selected sync log.
     */
    public function getSelectedLogProperty(): ?SyncLog
    {
        if (! $this->selectedLogId) {
            return null;
        }

        return SyncLog::with(['triggeredBy', 'region'])->find($this->selectedLogId);
    }

    /**
     * Check if any filters are active.
     */
    public function getHasFiltersProperty(): bool
    {
        return $this->statusFilter || $this->triggerFilter || $this->typeFilter || $this->dateFrom || $this->dateTo;
    }

    public function render()
    {
        $logs = app(SyncLogQueryService::class)
            ->filtered(
                status: $this->statusFilter,
                trigger: $this->triggerFilter,
                type: $this->typeFilter,
                dateFrom: $this->dateFrom,
                dateTo: $this->dateTo,
            )
            ->latest('started_at')
            ->paginate(15);

        return view('livewire.data-management.sync-logs', [
            'logs' => $logs,
        ]);
    }
}
