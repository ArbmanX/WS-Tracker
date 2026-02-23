<?php

namespace App\Livewire\Concerns;

use App\Services\WorkStudio\Queries\CircuitFilterOptionsService;
use App\Support\WorkStudioStatus;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

trait WithCircuitFilters
{
    /** @var array<string> */
    #[Url(as: 'status')]
    public array $statusFilter = [];

    /** @var array<string> */
    #[Url(as: 'cycle')]
    public array $cycleTypeFilter = [];

    /**
     * The default status filter value.
     * Dashboards should focus on active circuits by default.
     */
    protected function getDefaultStatusFilter(): array
    {
        return WorkStudioStatus::defaultFilter();
    }

    /**
     * Initialize filter defaults when trait is booted.
     */
    public function initializeWithCircuitFilters(): void
    {
        if (empty($this->statusFilter)) {
            $this->statusFilter = $this->getDefaultStatusFilter();
        }
    }

    /**
     * Available status options with labels.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function availableStatuses(): array
    {
        return WorkStudioStatus::labels();
    }

    /**
     * Get distinct cycle types from the database.
     * Respects global analytics settings (scope year filter).
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function availableCycleTypes(): Collection
    {
        return app(CircuitFilterOptionsService::class)->analyticsCycleTypes();
    }

    /**
     * Toggle a status in the filter.
     */
    public function toggleStatus(string $status): void
    {
        if (in_array($status, $this->statusFilter)) {
            // Prevent removing the last status
            if (count($this->statusFilter) > 1) {
                $this->statusFilter = array_values(array_diff($this->statusFilter, [$status]));
            }
        } else {
            $this->statusFilter[] = $status;
        }

        $this->onCircuitFiltersUpdated();
    }

    /**
     * Toggle a cycle type in the filter.
     */
    public function toggleCycleType(string $type): void
    {
        if (in_array($type, $this->cycleTypeFilter)) {
            $this->cycleTypeFilter = array_values(array_diff($this->cycleTypeFilter, [$type]));
        } else {
            $this->cycleTypeFilter[] = $type;
        }

        $this->onCircuitFiltersUpdated();
    }

    /**
     * Select all statuses.
     */
    public function selectAllStatuses(): void
    {
        $this->statusFilter = array_keys($this->availableStatuses);
        $this->onCircuitFiltersUpdated();
    }

    /**
     * Select all cycle types.
     */
    public function selectAllCycleTypes(): void
    {
        $this->cycleTypeFilter = [];
        $this->onCircuitFiltersUpdated();
    }

    /**
     * Clear all circuit filters to component defaults.
     */
    public function clearCircuitFilters(): void
    {
        $this->statusFilter = $this->getDefaultStatusFilter();
        $this->cycleTypeFilter = [];
        $this->onCircuitFiltersUpdated();
    }

    /**
     * Check if any circuit filters are active (non-default).
     */
    public function hasActiveCircuitFilters(): bool
    {
        $defaultStatuses = $this->getDefaultStatusFilter();
        $hasStatusFilter = count($this->statusFilter) !== count($defaultStatuses)
            || ! empty(array_diff($defaultStatuses, $this->statusFilter))
            || ! empty(array_diff($this->statusFilter, $defaultStatuses));
        $hasCycleFilter = ! empty($this->cycleTypeFilter);

        return $hasStatusFilter || $hasCycleFilter;
    }

    /**
     * Get the count of selected statuses.
     */
    public function selectedStatusCount(): int
    {
        return count($this->statusFilter);
    }

    /**
     * Get the count of selected cycle types.
     */
    public function selectedCycleTypeCount(): int
    {
        return count($this->cycleTypeFilter);
    }

    /**
     * Apply circuit filters to a query builder or relationship.
     *
     * @template T of BuilderContract
     *
     * @param  T  $query
     * @return T
     */
    protected function applyCircuitFilters(BuilderContract $query): BuilderContract
    {
        return $query
            ->when(! empty($this->statusFilter), fn ($q) => $q->whereIn('api_status', $this->statusFilter))
            ->when(! empty($this->cycleTypeFilter), fn ($q) => $q->whereIn('cycle_type', $this->cycleTypeFilter));
    }

    /**
     * Build a WHERE clause array for raw queries.
     *
     * @return array{status: array<string>, cycle: array<string>}
     */
    protected function getCircuitFilterConditions(): array
    {
        return [
            'status' => $this->statusFilter,
            'cycle' => $this->cycleTypeFilter,
        ];
    }

    /**
     * Hook called when filters are updated.
     * Override in consuming component to clear computed caches.
     */
    protected function onCircuitFiltersUpdated(): void
    {
        // Override in consuming component if needed
    }
}
