<?php

namespace App\Livewire\DataManagement;

use App\Models\Circuit;
use App\Models\Region;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layout.app-shell', ['title' => 'Circuit Browser'])]
class CircuitBrowser extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'region')]
    public ?int $regionFilter = null;

    #[Url(as: 'status')]
    public string $apiStatusFilter = '';

    #[Url(as: 'excluded')]
    public string $excludedFilter = '';

    #[Url(as: 'modified')]
    public string $modifiedFilter = '';

    // Detail/Edit Modal State
    public bool $showModal = false;

    public ?int $selectedCircuitId = null;

    public string $activeTab = 'overview';

    // Edit form state
    public bool $isEditing = false;

    public string $editTitle = '';

    public ?float $editTotalMiles = null;

    public string $editContractor = '';

    public string $editCycleType = '';

    public string $editReason = '';

    // Exclude form state
    public bool $showExcludeModal = false;

    public string $excludeReason = '';

    /**
     * Reset pagination when filters change.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRegionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedApiStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedExcludedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedModifiedFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->reset(['search', 'regionFilter', 'apiStatusFilter', 'excludedFilter', 'modifiedFilter']);
        $this->resetPage();
    }

    /**
     * View circuit details.
     */
    public function viewCircuit(int $circuitId): void
    {
        $this->selectedCircuitId = $circuitId;
        $this->activeTab = 'overview';
        $this->isEditing = false;
        $this->showModal = true;
    }

    /**
     * Start editing a circuit.
     */
    public function startEdit(): void
    {
        $circuit = $this->getSelectedCircuitProperty();
        if (! $circuit) {
            return;
        }

        $this->editTitle = $circuit->title ?? '';
        $this->editTotalMiles = $circuit->total_miles;
        $this->editContractor = $circuit->contractor ?? '';
        $this->editCycleType = $circuit->cycle_type ?? '';
        $this->editReason = '';
        $this->isEditing = true;
    }

    /**
     * Cancel editing.
     */
    public function cancelEdit(): void
    {
        $this->isEditing = false;
        $this->resetValidation();
    }

    /**
     * Save circuit edits.
     */
    public function saveEdit(): void
    {
        $this->validate([
            'editTitle' => ['required', 'string', 'max:255'],
            'editTotalMiles' => ['required', 'numeric', 'min:0'],
            'editContractor' => ['nullable', 'string', 'max:255'],
            'editCycleType' => ['nullable', 'string', 'max:100'],
            'editReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'editReason.required' => 'Please provide a reason for your changes.',
            'editReason.min' => 'Please provide a more detailed reason (at least 5 characters).',
        ]);

        $circuit = Circuit::findOrFail($this->selectedCircuitId);
        $changedFields = [];

        // Track which fields changed
        if ($circuit->title !== $this->editTitle) {
            $changedFields[] = 'title';
        }
        if ((float) $circuit->total_miles !== (float) $this->editTotalMiles) {
            $changedFields[] = 'total_miles';
        }
        if ($circuit->contractor !== $this->editContractor) {
            $changedFields[] = 'contractor';
        }
        if ($circuit->cycle_type !== $this->editCycleType) {
            $changedFields[] = 'cycle_type';
        }

        // Update the circuit
        $circuit->update([
            'title' => $this->editTitle,
            'total_miles' => $this->editTotalMiles,
            'contractor' => $this->editContractor,
            'cycle_type' => $this->editCycleType,
        ]);

        // Mark fields as user-modified
        $circuit->markFieldsAsUserModified($changedFields, auth()->id());
        $circuit->save();

        // Log the activity with the reason
        activity()
            ->causedBy(auth()->user())
            ->performedOn($circuit)
            ->withProperties([
                'reason' => $this->editReason,
                'changed_fields' => $changedFields,
            ])
            ->log('Circuit manually edited: '.$this->editReason);

        $this->isEditing = false;
        $this->dispatch('notify', message: 'Circuit updated successfully.', type: 'success');
    }

    /**
     * Open exclude modal.
     */
    public function openExcludeModal(int $circuitId): void
    {
        $this->selectedCircuitId = $circuitId;
        $this->excludeReason = '';
        $this->showExcludeModal = true;
    }

    /**
     * Exclude a circuit.
     */
    public function excludeCircuit(): void
    {
        $this->validate([
            'excludeReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'excludeReason.required' => 'Please provide a reason for excluding this circuit.',
        ]);

        $circuit = Circuit::findOrFail($this->selectedCircuitId);
        $circuit->exclude($this->excludeReason, auth()->id());

        activity()
            ->causedBy(auth()->user())
            ->performedOn($circuit)
            ->withProperties(['reason' => $this->excludeReason])
            ->log('Circuit excluded from reporting: '.$this->excludeReason);

        $this->showExcludeModal = false;
        $this->showModal = false;
        $this->dispatch('notify', message: 'Circuit excluded from reporting.', type: 'success');
    }

    /**
     * Include (un-exclude) a circuit.
     */
    public function includeCircuit(int $circuitId): void
    {
        $circuit = Circuit::findOrFail($circuitId);
        $circuit->include();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($circuit)
            ->log('Circuit included in reporting');

        $this->dispatch('notify', message: 'Circuit included in reporting.', type: 'success');
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->showExcludeModal = false;
        $this->selectedCircuitId = null;
        $this->isEditing = false;
        $this->resetValidation();
    }

    /**
     * Get available regions.
     */
    public function getRegionsProperty(): \Illuminate\Support\Collection
    {
        return Region::active()->ordered()->get();
    }

    /**
     * Get available API statuses.
     *
     * @return array<string, string>
     */
    public function getApiStatusOptionsProperty(): array
    {
        return Circuit::query()
            ->select('api_status')
            ->distinct()
            ->whereNotNull('api_status')
            ->orderBy('api_status')
            ->pluck('api_status', 'api_status')
            ->toArray();
    }

    /**
     * Get the selected circuit.
     */
    public function getSelectedCircuitProperty(): ?Circuit
    {
        if (! $this->selectedCircuitId) {
            return null;
        }

        return Circuit::with(['region', 'excludedBy', 'lastModifiedBy'])
            ->find($this->selectedCircuitId);
    }

    /**
     * Get activities for the selected circuit.
     */
    public function getActivitiesProperty(): \Illuminate\Support\Collection
    {
        if (! $this->selectedCircuitId) {
            return collect();
        }

        return Activity::query()
            ->where('subject_type', Circuit::class)
            ->where('subject_id', $this->selectedCircuitId)
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get();
    }

    public function render()
    {
        $circuits = Circuit::query()
            ->with(['region'])
            ->when($this->search, function ($q) {
                $search = '%'.strtolower($this->search).'%';
                $q->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(work_order) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(title) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(job_guid) LIKE ?', [$search]);
                });
            })
            ->when($this->regionFilter, fn ($q) => $q->where('region_id', $this->regionFilter))
            ->when($this->apiStatusFilter, fn ($q) => $q->where('api_status', $this->apiStatusFilter))
            ->when($this->excludedFilter === 'yes', fn ($q) => $q->excluded())
            ->when($this->excludedFilter === 'no', fn ($q) => $q->notExcluded())
            ->when($this->modifiedFilter === 'yes', fn ($q) => $q->withUserModifications())
            ->when($this->modifiedFilter === 'no', fn ($q) => $q->withoutUserModifications())
            ->orderBy('work_order')
            ->paginate(25);

        return view('livewire.data-management.circuit-browser', [
            'circuits' => $circuits,
        ]);
    }
}
