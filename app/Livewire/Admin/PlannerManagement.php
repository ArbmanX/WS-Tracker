<?php

namespace App\Livewire\Admin;

use App\Models\Region;
use App\Models\UnlinkedPlanner;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout.app-shell', ['title' => 'Planner Management'])]
class PlannerManagement extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all'; // all, linked, unlinked

    public string $exclusionFilter = 'active'; // active, excluded, all

    public ?int $regionFilter = null;

    public ?int $editingPlannerId = null;

    public ?string $editingPlannerType = null; // 'user' or 'unlinked'

    public string $editName = '';

    public ?int $excludingPlannerId = null;

    public ?string $excludingPlannerType = null;

    public string $exclusionReason = '';

    /**
     * Reset pagination when search changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
        unset($this->planners);
        unset($this->allPlanners);
    }

    /**
     * Reset pagination when filter changes.
     */
    public function updatedFilter(): void
    {
        $this->resetPage();
        unset($this->planners);
        unset($this->allPlanners);
    }

    /**
     * Reset pagination when exclusion filter changes.
     */
    public function updatedExclusionFilter(): void
    {
        $this->resetPage();
        unset($this->planners);
        unset($this->allPlanners);
    }

    /**
     * Reset pagination when region filter changes.
     */
    public function updatedRegionFilter(): void
    {
        $this->resetPage();
        unset($this->planners);
        unset($this->allPlanners);
    }

    /**
     * Get available regions for filtering.
     */
    #[Computed]
    public function regions(): \Illuminate\Support\Collection
    {
        return Region::orderBy('name')->get();
    }

    /**
     * Get unified planner data (linked users + unlinked planners).
     *
     * @return array{linked: \Illuminate\Support\Collection, unlinked: \Illuminate\Support\Collection}
     */
    #[Computed]
    public function planners(): array
    {
        $linkedPlanners = collect();
        $unlinkedPlanners = collect();

        // Get linked planners (users with planner role)
        if ($this->filter !== 'unlinked') {
            $search = strtolower($this->search);
            $linkedQuery = User::query()
                ->role('planner')
                ->when($this->search, function ($q) use ($search) {
                    $q->where(function ($q) use ($search) {
                        $q->whereRaw('lower(name) like ?', ["%{$search}%"])
                            ->orWhereRaw('lower(ws_username) like ?', ["%{$search}%"])
                            ->orWhereRaw('lower(email) like ?', ["%{$search}%"]);
                    });
                })
                ->when($this->exclusionFilter === 'active', fn ($q) => $q->includedInAnalytics())
                ->when($this->exclusionFilter === 'excluded', fn ($q) => $q->excludedFromAnalytics())
                ->when($this->regionFilter, function ($q) {
                    $q->inRegion($this->regionFilter);
                })
                ->withCount('circuits')
                ->with(['excludedByUser', 'regions']);

            $linkedPlanners = $linkedQuery->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'type' => 'user',
                    'name' => $user->name,
                    'ws_username' => $user->ws_username,
                    'email' => $user->email,
                    'circuit_count' => $user->circuits_count,
                    'is_excluded' => $user->is_excluded_from_analytics,
                    'exclusion_reason' => $user->exclusion_reason,
                    'excluded_at' => $user->excluded_at,
                    'excluded_by' => $user->excludedByUser?->name,
                    'regions' => $user->regions->pluck('name')->join(', '),
                    'is_linked' => true,
                    'last_seen_at' => null,
                ];
            });
        }

        // Get unlinked planners
        if ($this->filter !== 'linked') {
            $search = strtolower($this->search);
            $unlinkedQuery = UnlinkedPlanner::query()
                ->unlinked()
                ->when($this->search, function ($q) use ($search) {
                    $q->where(function ($q) use ($search) {
                        $q->whereRaw('lower(display_name) like ?', ["%{$search}%"])
                            ->orWhereRaw('lower(ws_username) like ?', ["%{$search}%"]);
                    });
                })
                ->when($this->exclusionFilter === 'active', fn ($q) => $q->includedInAnalytics())
                ->when($this->exclusionFilter === 'excluded', fn ($q) => $q->excludedFromAnalytics())
                ->with('excludedByUser');

            $unlinkedPlanners = $unlinkedQuery->get()->map(function ($planner) {
                return [
                    'id' => $planner->id,
                    'type' => 'unlinked',
                    'name' => $planner->display_name ?? $planner->ws_username,
                    'ws_username' => $planner->ws_username,
                    'email' => null,
                    'circuit_count' => $planner->circuit_count,
                    'is_excluded' => $planner->is_excluded_from_analytics,
                    'exclusion_reason' => $planner->exclusion_reason,
                    'excluded_at' => $planner->excluded_at,
                    'excluded_by' => $planner->excludedByUser?->name,
                    'regions' => null,
                    'is_linked' => false,
                    'last_seen_at' => $planner->last_seen_at,
                ];
            });
        }

        return [
            'linked' => $linkedPlanners,
            'unlinked' => $unlinkedPlanners,
        ];
    }

    /**
     * Get all planners combined and sorted.
     */
    #[Computed]
    public function allPlanners(): \Illuminate\Support\Collection
    {
        $planners = $this->planners;

        return $planners['linked']
            ->concat($planners['unlinked'])
            ->sortByDesc('circuit_count')
            ->values();
    }

    /**
     * Get planner counts for stats.
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'total_linked' => User::role('planner')->count(),
            'total_unlinked' => UnlinkedPlanner::unlinked()->count(),
            'excluded_linked' => User::role('planner')->excludedFromAnalytics()->count(),
            'excluded_unlinked' => UnlinkedPlanner::unlinked()->excludedFromAnalytics()->count(),
        ];
    }

    /**
     * Start editing a planner's name.
     */
    public function startEditing(int $id, string $type): void
    {
        $this->editingPlannerId = $id;
        $this->editingPlannerType = $type;

        if ($type === 'user') {
            $this->editName = User::find($id)?->name ?? '';
        } else {
            $planner = UnlinkedPlanner::find($id);
            $this->editName = $planner?->display_name ?? $planner?->ws_username ?? '';
        }
    }

    /**
     * Cancel editing.
     */
    public function cancelEditing(): void
    {
        $this->editingPlannerId = null;
        $this->editingPlannerType = null;
        $this->editName = '';
    }

    /**
     * Save the edited name.
     */
    public function saveName(): void
    {
        if (! auth()->user()?->hasAnyRole(['sudo_admin', 'admin'])) {
            $this->dispatch('notify', message: 'You do not have permission to edit planners.', type: 'error');

            return;
        }

        if (! $this->editingPlannerId || empty(trim($this->editName))) {
            return;
        }

        if ($this->editingPlannerType === 'user') {
            User::where('id', $this->editingPlannerId)->update(['name' => trim($this->editName)]);
        } else {
            UnlinkedPlanner::where('id', $this->editingPlannerId)->update(['display_name' => trim($this->editName)]);
        }

        $this->dispatch('notify', message: 'Name updated successfully.', type: 'success');
        $this->cancelEditing();

        // Clear the cached computed properties
        unset($this->planners);
        unset($this->allPlanners);
    }

    /**
     * Start the exclusion process for a planner.
     */
    public function startExcluding(int $id, string $type): void
    {
        $this->excludingPlannerId = $id;
        $this->excludingPlannerType = $type;
        $this->exclusionReason = '';
    }

    /**
     * Cancel the exclusion process.
     */
    public function cancelExcluding(): void
    {
        $this->excludingPlannerId = null;
        $this->excludingPlannerType = null;
        $this->exclusionReason = '';
    }

    /**
     * Exclude a planner from analytics.
     */
    public function excludePlanner(): void
    {
        if (! auth()->user()?->hasAnyRole(['sudo_admin', 'admin'])) {
            $this->dispatch('notify', message: 'You do not have permission to exclude planners.', type: 'error');

            return;
        }

        if (! $this->excludingPlannerId || empty(trim($this->exclusionReason))) {
            $this->dispatch('notify', message: 'Please provide a reason for exclusion.', type: 'warning');

            return;
        }

        if ($this->excludingPlannerType === 'user') {
            $planner = User::find($this->excludingPlannerId);
            $planner?->excludeFromAnalytics(trim($this->exclusionReason), auth()->user());
        } else {
            $planner = UnlinkedPlanner::find($this->excludingPlannerId);
            $planner?->excludeFromAnalytics(trim($this->exclusionReason), auth()->user());
        }

        $this->dispatch('notify', message: 'Planner excluded from analytics.', type: 'success');
        $this->cancelExcluding();

        // Clear the cached computed properties
        unset($this->planners);
        unset($this->allPlanners);
        unset($this->stats);
    }

    /**
     * Include a planner back in analytics.
     */
    public function includePlanner(int $id, string $type): void
    {
        if (! auth()->user()?->hasAnyRole(['sudo_admin', 'admin'])) {
            $this->dispatch('notify', message: 'You do not have permission to include planners.', type: 'error');

            return;
        }

        if ($type === 'user') {
            User::find($id)?->includeInAnalytics();
        } else {
            UnlinkedPlanner::find($id)?->includeInAnalytics();
        }

        $this->dispatch('notify', message: 'Planner included in analytics.', type: 'success');

        // Clear the cached computed properties
        unset($this->planners);
        unset($this->allPlanners);
        unset($this->stats);
    }

    public function render()
    {
        return view('livewire.admin.planner-management');
    }
}
