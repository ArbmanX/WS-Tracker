# Phase 1G: Admin Features

> **Goal:** Build admin-only features for sync control and user management.
> **Estimated Time:** 2 days
> **Dependencies:** Phase 1C (sync jobs), Phase 1A (user models)

---

## Status: Not Started

| Item | Status | Notes |
|------|--------|-------|
| SyncControl | Needed | Force sync UI |
| SyncHistory | Needed | View sync logs |
| UnlinkedPlanners | Needed | Link API users to local |
| UserManagement | Needed | Manage users (sudo only) |

---

## Access Control

| Feature | sudo_admin | admin | planner | general_foreman |
|---------|------------|-------|---------|-----------------|
| Sync Control | Full | View only | - | - |
| Sync History | Full | Full | - | - |
| Unlinked Planners | Full | Full | - | - |
| User Management | Full | - | - | - |
| Settings | Full | - | - | - |

---

## Checklist

### Livewire Components
- [ ] Create SyncControl component
  - Status checkboxes (ACTIV, QC, REWORK, CLOSE)
  - Sync now button
  - Progress indicator
  - Last sync timestamp
- [ ] Create SyncHistory component with paginated logs
- [ ] Create UnlinkedPlanners management component
- [ ] Create UserManagement component
- [ ] Create UserWsCredentials component for credential setup

### Middleware
- [ ] Create EnsureSudoAdmin middleware
- [ ] Create EnsureAdmin middleware

### Routes
- [ ] Add admin routes with middleware

### Authorization
- [ ] Implement permission gates
- [ ] Add activity logging for admin actions

### Tests
- [ ] Write authorization tests
- [ ] Write feature tests for each component

---

## File Structure

```
app/
├── Livewire/
│   └── Admin/
│       ├── SyncControl.php
│       ├── SyncHistory.php
│       ├── UnlinkedPlanners.php
│       ├── UserManagement.php
│       └── UserWsCredentials.php
├── Http/
│   └── Middleware/
│       ├── EnsureSudoAdmin.php
│       └── EnsureAdmin.php

resources/views/livewire/admin/
    ├── sync-control.blade.php
    ├── sync-history.blade.php
    ├── unlinked-planners.blade.php
    ├── user-management.blade.php
    └── user-ws-credentials.blade.php
```

---

## Key Implementation Details

### SyncControl Component

```php
<?php

namespace App\Livewire\Admin;

use App\Jobs\SyncCircuitsJob;
use App\Models\SyncLog;
use App\Enums\SyncTrigger;
use Livewire\Component;
use Livewire\Attributes\Computed;

class SyncControl extends Component
{
    public array $selectedStatuses = ['ACTIV'];
    public bool $syncing = false;

    public function triggerSync(): void
    {
        $this->authorize('force-sync');

        $this->syncing = true;

        dispatch(new SyncCircuitsJob(
            statuses: $this->selectedStatuses,
            triggeredByUserId: auth()->id(),
            triggerType: SyncTrigger::Manual
        ));

        $this->dispatch('sync-started');
    }

    #[Computed]
    public function lastSync(): ?SyncLog
    {
        return SyncLog::latest('started_at')->first();
    }

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

    public function render()
    {
        return view('livewire.admin.sync-control');
    }
}
```

### SyncControl Blade View

```blade
{{-- resources/views/livewire/admin/sync-control.blade.php --}}
<div class="card bg-base-100 shadow">
    <div class="card-body">
        <h2 class="card-title">Sync Control</h2>

        {{-- Last Sync Info --}}
        @if($this->lastSync)
            <div class="alert alert-{{ $this->lastSync->status->value === 'completed' ? 'success' : 'warning' }} mb-4">
                <div>
                    <span class="font-semibold">Last Sync:</span>
                    {{ $this->lastSync->started_at->diffForHumans() }}
                    ({{ $this->lastSync->circuits_processed }} circuits)
                </div>
            </div>
        @endif

        {{-- Status Selection --}}
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Select Statuses to Sync</span>
            </label>
            <div class="flex flex-wrap gap-2">
                @foreach($this->availableStatuses as $code => $label)
                    <label class="label cursor-pointer gap-2">
                        <input
                            type="checkbox"
                            wire:model="selectedStatuses"
                            value="{{ $code }}"
                            class="checkbox checkbox-primary"
                        />
                        <span class="label-text">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Sync Button --}}
        <div class="card-actions justify-end mt-4">
            @can('force-sync')
                <button
                    wire:click="triggerSync"
                    wire:loading.attr="disabled"
                    class="btn btn-primary"
                    @disabled(empty($selectedStatuses) || $syncing)
                >
                    <span wire:loading.remove wire:target="triggerSync">
                        <flux:icon name="arrow-path" class="w-4 h-4 mr-2" />
                        Sync Now
                    </span>
                    <span wire:loading wire:target="triggerSync">
                        <span class="loading loading-spinner loading-sm mr-2"></span>
                        Syncing...
                    </span>
                </button>
            @else
                <div class="text-sm text-base-content/60">
                    Sync triggers require sudo admin permissions
                </div>
            @endcan
        </div>

        {{-- Progress (if syncing) --}}
        @if($syncing)
            <div class="mt-4">
                <progress class="progress progress-primary w-full"></progress>
                <p class="text-sm text-center mt-2">Sync in progress...</p>
            </div>
        @endif
    </div>
</div>
```

### SyncHistory Component

```php
<?php

namespace App\Livewire\Admin;

use App\Models\SyncLog;
use Livewire\Component;
use Livewire\WithPagination;

class SyncHistory extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $typeFilter = '';

    public function render()
    {
        $logs = SyncLog::query()
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn($q) => $q->where('sync_type', $this->typeFilter))
            ->latest('started_at')
            ->paginate(15);

        return view('livewire.admin.sync-history', [
            'logs' => $logs,
        ]);
    }
}
```

### UnlinkedPlanners Component

```php
<?php

namespace App\Livewire\Admin;

use App\Models\UnlinkedPlanner;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UnlinkedPlanners extends Component
{
    use WithPagination;

    public ?int $linkingPlannerId = null;
    public ?int $selectedUserId = null;

    public function startLinking(int $plannerId): void
    {
        $this->linkingPlannerId = $plannerId;
        $this->selectedUserId = null;
    }

    public function cancelLinking(): void
    {
        $this->linkingPlannerId = null;
        $this->selectedUserId = null;
    }

    public function linkPlanner(): void
    {
        $this->authorize('link-planners');

        $unlinkedPlanner = UnlinkedPlanner::findOrFail($this->linkingPlannerId);
        $user = User::findOrFail($this->selectedUserId);

        // Update user with API username
        $user->update(['api_username' => $unlinkedPlanner->api_username]);

        // Mark as resolved
        $unlinkedPlanner->update([
            'linked_user_id' => $user->id,
            'is_resolved' => true,
            'resolved_by_user_id' => auth()->id(),
            'resolved_at' => now(),
        ]);

        // Re-link any circuits with this API username
        $this->relinkCircuits($unlinkedPlanner->api_username, $user->id);

        $this->cancelLinking();
        $this->dispatch('planner-linked');
    }

    public function ignorePlanner(int $plannerId): void
    {
        $this->authorize('link-planners');

        UnlinkedPlanner::findOrFail($plannerId)->update([
            'is_resolved' => true,
            'resolved_by_user_id' => auth()->id(),
            'resolved_at' => now(),
        ]);
    }

    private function relinkCircuits(string $apiUsername, int $userId): void
    {
        // Find circuits where this planner was assigned via API
        // and update the circuit_user pivot
        \DB::table('circuit_user')
            ->join('circuits', 'circuit_user.circuit_id', '=', 'circuits.id')
            ->whereRaw("circuits.api_data_json->>'SS_TAKENBY' = ?", [$apiUsername])
            ->update(['user_id' => $userId]);
    }

    public function render()
    {
        $planners = UnlinkedPlanner::query()
            ->where('is_resolved', false)
            ->orderBy('occurrence_count', 'desc')
            ->paginate(10);

        $availableUsers = User::whereNull('api_username')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.unlinked-planners', [
            'planners' => $planners,
            'availableUsers' => $availableUsers,
        ]);
    }
}
```

### UnlinkedPlanners Blade View

```blade
{{-- resources/views/livewire/admin/unlinked-planners.blade.php --}}
<div class="card bg-base-100 shadow">
    <div class="card-body">
        <h2 class="card-title">Unlinked Planners</h2>
        <p class="text-sm text-base-content/70 mb-4">
            These WorkStudio usernames were found in API data but don't match any local users.
        </p>

        @if($planners->isEmpty())
            <div class="alert alert-success">
                <flux:icon name="check-circle" class="w-5 h-5" />
                All planners are linked!
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>API Username</th>
                            <th>First Seen</th>
                            <th>Occurrences</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($planners as $planner)
                            <tr>
                                <td class="font-mono text-sm">{{ $planner->api_username }}</td>
                                <td>{{ $planner->first_seen_at->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge badge-neutral">{{ $planner->occurrence_count }}</span>
                                </td>
                                <td>
                                    @if($linkingPlannerId === $planner->id)
                                        <div class="flex items-center gap-2">
                                            <select
                                                wire:model="selectedUserId"
                                                class="select select-bordered select-sm"
                                            >
                                                <option value="">Select user...</option>
                                                @foreach($availableUsers as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                            <button
                                                wire:click="linkPlanner"
                                                class="btn btn-success btn-sm"
                                                @disabled(!$selectedUserId)
                                            >
                                                Link
                                            </button>
                                            <button
                                                wire:click="cancelLinking"
                                                class="btn btn-ghost btn-sm"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    @else
                                        <div class="flex gap-2">
                                            <button
                                                wire:click="startLinking({{ $planner->id }})"
                                                class="btn btn-primary btn-sm"
                                            >
                                                Link
                                            </button>
                                            <button
                                                wire:click="ignorePlanner({{ $planner->id }})"
                                                wire:confirm="Are you sure you want to ignore this planner?"
                                                class="btn btn-ghost btn-sm"
                                            >
                                                Ignore
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $planners->links() }}
            </div>
        @endif
    </div>
</div>
```

### Middleware

```php
// app/Http/Middleware/EnsureSudoAdmin.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSudoAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->hasRole('sudo_admin')) {
            abort(403, 'Unauthorized. Sudo admin access required.');
        }

        return $next($request);
    }
}

// app/Http/Middleware/EnsureAdmin.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->hasAnyRole(['sudo_admin', 'admin'])) {
            abort(403, 'Unauthorized. Admin access required.');
        }

        return $next($request);
    }
}
```

### Routes

```php
// routes/web.php

Route::middleware(['auth', 'verified'])->group(function () {
    // Admin routes
    Route::middleware(\App\Http\Middleware\EnsureAdmin::class)
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/sync', \App\Livewire\Admin\SyncControl::class)->name('sync');
            Route::get('/sync/history', \App\Livewire\Admin\SyncHistory::class)->name('sync.history');
            Route::get('/planners/unlinked', \App\Livewire\Admin\UnlinkedPlanners::class)->name('planners.unlinked');
        });

    // Sudo admin only routes
    Route::middleware(\App\Http\Middleware\EnsureSudoAdmin::class)
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/users', \App\Livewire\Admin\UserManagement::class)->name('users');
            Route::get('/settings', \App\Livewire\Admin\Settings::class)->name('settings');
        });
});
```

---

## Testing Requirements

```php
// tests/Feature/Admin/SyncControlTest.php
it('allows sudo admin to trigger sync', function () {
    $admin = User::factory()->create();
    $admin->assignRole('sudo_admin');

    Livewire::actingAs($admin)
        ->test(SyncControl::class)
        ->set('selectedStatuses', ['ACTIV'])
        ->call('triggerSync')
        ->assertDispatched('sync-started');

    Queue::assertPushed(SyncCircuitsJob::class);
});

it('denies sync trigger for non-admin', function () {
    $planner = User::factory()->create();
    $planner->assignRole('planner');

    Livewire::actingAs($planner)
        ->test(SyncControl::class)
        ->call('triggerSync')
        ->assertForbidden();
});

it('shows sync progress', function () {...});

// tests/Feature/Admin/UnlinkedPlannersTest.php
it('lists unlinked API usernames', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $unlinked = UnlinkedPlanner::factory()->create([
        'api_username' => 'ASPLUNDH\\jsmith',
    ]);

    Livewire::actingAs($admin)
        ->test(UnlinkedPlanners::class)
        ->assertSee('ASPLUNDH\\jsmith');
});

it('links API username to local user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['api_username' => null]);
    $unlinked = UnlinkedPlanner::factory()->create([
        'api_username' => 'ASPLUNDH\\jsmith',
    ]);

    Livewire::actingAs($admin)
        ->test(UnlinkedPlanners::class)
        ->call('startLinking', $unlinked->id)
        ->set('selectedUserId', $user->id)
        ->call('linkPlanner');

    expect($user->fresh()->api_username)->toBe('ASPLUNDH\\jsmith');
    expect($unlinked->fresh()->is_resolved)->toBeTrue();
});

// tests/Feature/Admin/AccessControlTest.php
it('denies admin routes to planners', function () {
    $planner = User::factory()->create();
    $planner->assignRole('planner');

    $this->actingAs($planner)
        ->get('/admin/sync')
        ->assertForbidden();
});

it('allows admin routes to admins', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/sync')
        ->assertOk();
});
```

---

## Next Phase

Once all items are checked, proceed to **[Phase 1H: Testing & Polish](./PHASE_1H_TESTING.md)**.
