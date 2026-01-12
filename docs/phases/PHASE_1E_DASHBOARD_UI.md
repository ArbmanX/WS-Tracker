# Phase 1E: Dashboard UI

> **Goal:** Build the main circuit dashboard with Kanban board and filtering.
> **Estimated Time:** 4 days
> **Dependencies:** Phase 1A (models), Phase 1C (sync jobs to populate data)

---

## Status: Not Started

| Item | Status | Notes |
|------|--------|-------|
| CircuitDashboard | Needed | Main container component |
| WorkflowBoard | Needed | Kanban-style board |
| WorkflowColumn | Needed | Individual stage columns |
| CircuitCard | Needed | Draggable circuit cards |
| FilterPanel | Needed | Region, planner, date filters |
| StatsPanel | Needed | Summary statistics |
| CircuitPolicy | Needed | Authorization rules |

---

## Workflow Columns

```
┌────────────┬───────────────────┬──────────┬───────────┬──────────┐
│   Active   │ Pending Permissions │    QC    │  Rework   │  Closed  │
└────────────┴───────────────────┴──────────┴───────────┴──────────┘
```

Circuits can be dragged between columns (admin/sudo_admin only).

---

## Checklist

### Livewire Components
- [ ] Create CircuitDashboard container component
- [ ] Create WorkflowBoard with 5 columns
- [ ] Create WorkflowColumn with wire:sortable
- [ ] Create CircuitCard with drag handle
- [ ] Create FilterPanel (region, planner, date range)
- [ ] Create StatsPanel (totals, progress)

### Drag-Drop Integration
- [ ] Implement livewire-sortable integration
- [ ] Handle cross-column movement
- [ ] Update circuit UI state on drop
- [ ] Create snapshot on stage change
- [ ] Broadcast workflow change event

### Authorization
- [ ] Create CircuitPolicy
- [ ] Add `visibleToUser` scope to Circuit model
- [ ] Implement role-based column restrictions

### Split Assessments
- [ ] Show parent with expandable children
- [ ] Aggregate totals on parent card

### Routes
- [ ] Add dashboard route
- [ ] Add circuit detail route (if needed)

### Tests
- [ ] Write Livewire component tests
- [ ] Write authorization tests
- [ ] Write drag-drop feature tests

---

## File Structure

```
app/
├── Livewire/
│   └── Dashboard/
│       ├── CircuitDashboard.php
│       ├── WorkflowBoard.php
│       ├── WorkflowColumn.php
│       ├── CircuitCard.php
│       ├── FilterPanel.php
│       └── StatsPanel.php
├── Policies/
│   └── CircuitPolicy.php

resources/views/livewire/dashboard/
    ├── circuit-dashboard.blade.php
    ├── workflow-board.blade.php
    ├── workflow-column.blade.php
    ├── circuit-card.blade.php
    ├── filter-panel.blade.php
    └── stats-panel.blade.php
```

---

## Key Implementation Details

### CircuitDashboard Component

```php
<?php

namespace App\Livewire\Dashboard;

use App\Models\Circuit;
use App\Models\Region;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class CircuitDashboard extends Component
{
    public ?int $regionFilter = null;
    public ?string $plannerFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    #[On('circuit-moved')]
    public function handleCircuitMoved(int $circuitId, string $newStage, int $newPosition): void
    {
        $circuit = Circuit::findOrFail($circuitId);

        $this->authorize('change-workflow-stage', $circuit);

        $circuit->uiState->update([
            'workflow_stage' => $newStage,
            'column_position' => $newPosition,
        ]);

        // Create status change snapshot
        $circuit->snapshots()->create([
            'miles_planned' => $circuit->miles_planned,
            'percent_complete' => $circuit->percent_complete,
            'api_status' => $circuit->api_status,
            'workflow_stage' => $newStage,
            'snapshot_type' => \App\Enums\SnapshotType::StatusChange,
            'snapshot_date' => today(),
        ]);

        $this->dispatch('circuit-updated', circuitId: $circuitId);

        event(new \App\Events\CircuitWorkflowChangedEvent($circuit, $newStage));
    }

    #[Computed]
    public function circuits(): \Illuminate\Support\Collection
    {
        return Circuit::query()
            ->with(['region', 'uiState', 'planners'])
            ->visibleToUser(auth()->user())
            ->when($this->regionFilter, fn($q) => $q->where('region_id', $this->regionFilter))
            ->when($this->plannerFilter, fn($q) => $q->whereHas('planners',
                fn($pq) => $pq->where('users.id', $this->plannerFilter)))
            ->when($this->dateFrom, fn($q) => $q->where('api_modified_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('api_modified_date', '<=', $this->dateTo))
            ->orderBy('api_modified_date', 'desc')
            ->get()
            ->groupBy(fn($c) => $c->uiState?->workflow_stage->value ?? 'active');
    }

    #[Computed]
    public function regions(): \Illuminate\Support\Collection
    {
        return Region::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.dashboard.circuit-dashboard');
    }
}
```

### WorkflowColumn Blade View

```blade
{{-- resources/views/livewire/dashboard/workflow-column.blade.php --}}
<div class="flex flex-col h-full min-w-[280px] max-w-[320px]">
    <div class="flex items-center justify-between p-3 bg-base-200 rounded-t-lg">
        <h3 class="font-semibold text-base-content">
            {{ $title }}
            <span class="badge badge-neutral ml-2">{{ $circuits->count() }}</span>
        </h3>
    </div>

    <div
        class="flex-1 overflow-y-auto p-2 bg-base-100 rounded-b-lg min-h-[400px] space-y-2"
        wire:sortable="updateOrder"
        wire:sortable-group="{{ $stage }}"
    >
        @forelse($circuits as $circuit)
            <div
                wire:key="circuit-{{ $circuit->id }}"
                wire:sortable.item="{{ $circuit->id }}"
                class="cursor-move"
            >
                <livewire:dashboard.circuit-card
                    :circuit="$circuit"
                    :key="'card-'.$circuit->id"
                />
            </div>
        @empty
            <div class="text-center text-base-content/50 py-8">
                No circuits in this stage
            </div>
        @endforelse
    </div>
</div>
```

### CircuitCard Blade View

```blade
{{-- resources/views/livewire/dashboard/circuit-card.blade.php --}}
<div
    wire:sortable.handle
    class="card bg-base-100 shadow-sm border border-base-300 hover:shadow-md transition-shadow"
>
    <div class="card-body p-3">
        {{-- Header --}}
        <div class="flex justify-between items-start">
            <div>
                <h4 class="font-medium text-sm">
                    {{ $circuit->work_order }}{{ $circuit->extension }}
                </h4>
                <p class="text-xs text-base-content/70 truncate max-w-[200px]">
                    {{ $circuit->title }}
                </p>
            </div>
            <span class="badge badge-sm badge-{{ $circuit->region->color ?? 'neutral' }}">
                {{ $circuit->region->name }}
            </span>
        </div>

        {{-- Progress Bar --}}
        <div class="mt-2">
            <div class="flex justify-between text-xs mb-1">
                <span>Progress</span>
                <span>{{ number_format($circuit->percent_complete, 0) }}%</span>
            </div>
            <progress
                class="progress progress-primary w-full h-2"
                value="{{ $circuit->percent_complete }}"
                max="100"
            ></progress>
        </div>

        {{-- Stats Row --}}
        <div class="flex justify-between text-xs text-base-content/70 mt-2">
            <span>
                {{ number_format($circuit->miles_planned, 2) }} /
                {{ number_format($circuit->total_miles, 2) }} mi
            </span>
            <span>{{ $circuit->api_modified_date->format('m/d') }}</span>
        </div>

        {{-- Planners --}}
        @if($circuit->planners->isNotEmpty())
            <div class="flex items-center gap-1 mt-2">
                <div class="avatar-group -space-x-3">
                    @foreach($circuit->planners->take(3) as $planner)
                        <div class="avatar placeholder">
                            <div class="bg-neutral text-neutral-content rounded-full w-6">
                                <span class="text-xs">{{ substr($planner->name, 0, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($circuit->planners->count() > 3)
                    <span class="text-xs text-base-content/50">
                        +{{ $circuit->planners->count() - 3 }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Split Assessment Indicator --}}
        @if($circuit->children->isNotEmpty())
            <div class="mt-2">
                <button
                    wire:click="toggleChildren"
                    class="btn btn-xs btn-ghost gap-1"
                >
                    <flux:icon name="chevron-down" class="w-3 h-3" />
                    {{ $circuit->children->count() }} split assessments
                </button>
            </div>
        @endif
    </div>
</div>
```

### FilterPanel Component

```php
<?php

namespace App\Livewire\Dashboard;

use App\Models\Region;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Computed;

class FilterPanel extends Component
{
    public ?int $regionFilter = null;
    public ?int $plannerFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function updatedRegionFilter(): void
    {
        $this->dispatch('filters-changed', [
            'region' => $this->regionFilter,
            'planner' => $this->plannerFilter,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
    }

    public function updatedPlannerFilter(): void
    {
        $this->dispatch('filters-changed', [
            'region' => $this->regionFilter,
            'planner' => $this->plannerFilter,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
    }

    public function clearFilters(): void
    {
        $this->reset(['regionFilter', 'plannerFilter', 'dateFrom', 'dateTo']);
        $this->dispatch('filters-changed', []);
    }

    #[Computed]
    public function regions()
    {
        return Region::orderBy('name')->get();
    }

    #[Computed]
    public function planners()
    {
        return User::whereHas('assignedCircuits')
            ->orWhereHas('regions')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.filter-panel');
    }
}
```

### CircuitPolicy

```php
<?php

namespace App\Policies;

use App\Models\Circuit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CircuitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view some circuits
    }

    public function view(User $user, Circuit $circuit): bool
    {
        // Admins see all
        if ($user->hasAnyRole(['sudo_admin', 'admin'])) {
            return true;
        }

        // Planners/GFs see their assigned circuits
        if ($circuit->planners->contains($user)) {
            return true;
        }

        // Planners/GFs see circuits in their regions
        if ($user->regions->contains($circuit->region)) {
            return true;
        }

        return false;
    }

    public function changeWorkflowStage(User $user, Circuit $circuit): bool
    {
        return $user->hasAnyRole(['sudo_admin', 'admin']);
    }

    public function hide(User $user, Circuit $circuit): bool
    {
        return $user->hasRole('sudo_admin');
    }
}
```

### Circuit Model Scope

```php
// app/Models/Circuit.php

public function scopeVisibleToUser(Builder $query, User $user): Builder
{
    // Admins see all
    if ($user->hasAnyRole(['sudo_admin', 'admin'])) {
        return $query;
    }

    return $query->where(function ($q) use ($user) {
        // Assigned circuits
        $q->whereHas('planners', fn($pq) => $pq->where('users.id', $user->id))
            // Region assignments
            ->orWhereHas('region', fn($rq) =>
                $rq->whereHas('users', fn($uq) => $uq->where('users.id', $user->id))
            );
    });
}
```

---

## Routes

```php
// routes/web.php

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', \App\Livewire\Dashboard\CircuitDashboard::class)
        ->name('dashboard');

    Route::get('/circuits/{circuit}', \App\Livewire\Dashboard\CircuitDetail::class)
        ->name('circuits.show');
});
```

---

## Testing Requirements

```php
// tests/Feature/Dashboard/CircuitDashboardTest.php
it('shows circuits for admin users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $circuit = Circuit::factory()->create();

    Livewire::actingAs($admin)
        ->test(CircuitDashboard::class)
        ->assertSee($circuit->work_order);
});

it('filters circuits by region', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $circuit1 = Circuit::factory()->forRegion('Central')->create();
    $circuit2 = Circuit::factory()->forRegion('Lancaster')->create();

    Livewire::actingAs($admin)
        ->test(CircuitDashboard::class)
        ->set('regionFilter', $circuit1->region_id)
        ->assertSee($circuit1->work_order)
        ->assertDontSee($circuit2->work_order);
});

it('filters circuits by planner', function () {...});
it('filters circuits by date range', function () {...});

// tests/Feature/Dashboard/WorkflowDragDropTest.php
it('moves circuit to new stage', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $circuit = Circuit::factory()
        ->has(CircuitUiState::factory()->state(['workflow_stage' => 'active']))
        ->create();

    Livewire::actingAs($admin)
        ->test(CircuitDashboard::class)
        ->call('handleCircuitMoved', $circuit->id, 'qc', 1)
        ->assertDispatched('circuit-updated');

    expect($circuit->fresh()->uiState->workflow_stage->value)->toBe('qc');
});

it('creates snapshot on stage change', function () {...});
it('broadcasts workflow change event', function () {...});
it('denies stage change for unauthorized users', function () {...});

// tests/Feature/Auth/RoleAccessTest.php
it('restricts planners to assigned circuits', function () {
    $planner = User::factory()->create();
    $planner->assignRole('planner');

    $assignedCircuit = Circuit::factory()->create();
    $assignedCircuit->planners()->attach($planner);

    $unassignedCircuit = Circuit::factory()->create();

    Livewire::actingAs($planner)
        ->test(CircuitDashboard::class)
        ->assertSee($assignedCircuit->work_order)
        ->assertDontSee($unassignedCircuit->work_order);
});

it('allows admins to view all circuits', function () {...});
```

---

## Next Phase

Once all items are checked, proceed to **[Phase 1F: Charts & Visualization](./PHASE_1F_CHARTS.md)**.
