<?php

namespace App\Livewire\Assessments;

use App\Livewire\Concerns\WithCircuitFilters;
use App\Models\Circuit;
use App\Models\Region;
use App\Services\WorkStudio\Queries\CircuitAnalyticsQueryFactory;
use App\Services\WorkStudio\Queries\CircuitFilterOptionsService;
use App\Support\WorkStudioStatus;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'Circuit Kanban'])]
class CircuitKanban extends Component
{
    use WithCircuitFilters;

    #[Url(as: 'region')]
    public ?int $regionFilter = null;

    #[Url(as: 'planner')]
    public string $plannerFilter = '';

    #[Url(as: 'search')]
    public string $search = '';

    /**
     * Modal state for circuit details.
     */
    public bool $showDetailModal = false;

    public ?int $selectedCircuitId = null;

    public function mount(): void
    {
        $this->initializeWithCircuitFilters();
    }

    /**
     * Get column configuration for the view.
     *
     * @return array<string, array{label: string, color: string, description: string}>
     */
    #[Computed]
    public function columns(): array
    {
        return WorkStudioStatus::kanbanColumns();
    }

    /**
     * Get circuits grouped by status for Kanban columns.
     *
     * @return Collection<string, Collection<int, Circuit>>
     */
    #[Computed]
    public function circuitsByStatus(): Collection
    {
        $query = app(CircuitAnalyticsQueryFactory::class)->baseIncluded()
            ->with(['region']);

        // Apply circuit filters from trait (status, cycle type)
        $this->applyCircuitFilters($query);

        // Apply region filter
        if ($this->regionFilter) {
            $query->where('region_id', $this->regionFilter);
        }

        // Apply planner filter
        if ($this->plannerFilter) {
            $query->where('taken_by', $this->plannerFilter);
        }

        // Apply search filter
        if ($this->search) {
            $searchTerm = '%'.strtolower($this->search).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(work_order) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(title) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(taken_by) LIKE ?', [$searchTerm]);
            });
        }

        // Order by work_order within each group
        $circuits = $query->orderBy('work_order')->get();

        // Group by api_status, ensuring all columns exist
        $grouped = $circuits->groupBy('api_status');

        // Ensure all configured columns exist in the result
        $result = collect();
        foreach (array_keys($this->columns) as $status) {
            $result[$status] = $grouped->get($status, collect());
        }

        return $result;
    }

    /**
     * Get count of circuits per column.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function columnCounts(): array
    {
        $counts = [];
        foreach ($this->circuitsByStatus as $status => $circuits) {
            $counts[$status] = $circuits->count();
        }

        return $counts;
    }

    /**
     * Get total circuit count across all columns.
     */
    #[Computed]
    public function totalCircuits(): int
    {
        return array_sum($this->columnCounts);
    }

    /**
     * Get available regions for filtering.
     */
    #[Computed]
    public function regions(): Collection
    {
        return Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get available planners for filtering.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function planners(): Collection
    {
        return app(CircuitFilterOptionsService::class)->analyticsPlannerTakenBy();
    }

    /**
     * Open the detail modal for a circuit.
     */
    public function viewCircuit(int $circuitId): void
    {
        $this->selectedCircuitId = $circuitId;
        $this->showDetailModal = true;
    }

    /**
     * Close the detail modal.
     */
    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedCircuitId = null;
    }

    /**
     * Get the selected circuit for the detail modal.
     */
    #[Computed]
    public function selectedCircuit(): ?Circuit
    {
        if (! $this->selectedCircuitId) {
            return null;
        }

        return Circuit::with(['region'])->find($this->selectedCircuitId);
    }

    /**
     * Clear all filters to defaults.
     */
    public function clearAllFilters(): void
    {
        $this->clearCircuitFilters();
        $this->regionFilter = null;
        $this->plannerFilter = '';
        $this->search = '';
        $this->clearComputedCaches();
    }

    /**
     * Check if any filters are active.
     */
    #[Computed]
    public function hasAnyFilters(): bool
    {
        return $this->hasActiveCircuitFilters()
            || $this->regionFilter !== null
            || $this->plannerFilter !== ''
            || $this->search !== '';
    }

    /**
     * Clear computed caches when filters change.
     */
    protected function onCircuitFiltersUpdated(): void
    {
        $this->clearComputedCaches();
    }

    public function updatedRegionFilter(): void
    {
        $this->clearComputedCaches();
    }

    public function updatedPlannerFilter(): void
    {
        $this->clearComputedCaches();
    }

    public function updatedSearch(): void
    {
        $this->clearComputedCaches();
    }

    /**
     * Clear all computed property caches.
     */
    protected function clearComputedCaches(): void
    {
        unset(
            $this->circuitsByStatus,
            $this->columnCounts,
            $this->totalCircuits,
            $this->selectedCircuit
        );
    }

    public function render()
    {
        return view('livewire.assessments.circuit-kanban');
    }
}
