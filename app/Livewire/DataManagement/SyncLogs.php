<?php

namespace App\Livewire\DataManagement;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Models\SyncLog;
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
        return collect(SyncStatus::cases())
            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
            ->toArray();
    }

    /**
     * Get available trigger options.
     *
     * @return array<string, string>
     */
    public function getTriggerOptionsProperty(): array
    {
        return collect(SyncTrigger::cases())
            ->mapWithKeys(fn ($trigger) => [$trigger->value => $trigger->label()])
            ->toArray();
    }

    /**
     * Get available type options.
     *
     * @return array<string, string>
     */
    public function getTypeOptionsProperty(): array
    {
        return collect(SyncType::cases())
            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
            ->toArray();
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
        $logs = SyncLog::query()
            ->with(['triggeredBy', 'region'])
            ->when($this->statusFilter, fn ($q) => $q->where('sync_status', $this->statusFilter))
            ->when($this->triggerFilter, fn ($q) => $q->where('sync_trigger', $this->triggerFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('sync_type', $this->typeFilter))
            ->when($this->dateFrom, fn ($q) => $q->where('started_at', '>=', $this->dateFrom.' 00:00:00'))
            ->when($this->dateTo, fn ($q) => $q->where('started_at', '<=', $this->dateTo.' 23:59:59'))
            ->latest('started_at')
            ->paginate(15);

        return view('livewire.data-management.sync-logs', [
            'logs' => $logs,
        ]);
    }
}
