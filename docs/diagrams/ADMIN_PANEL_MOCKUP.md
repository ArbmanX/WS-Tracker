# WS-Tracker Admin Panel Mockup Specification

This document provides detailed wireframe specifications for recreating the Admin Panel UI in Draw.io, Figma, or directly as Blade/Livewire components.

---

## Overview

The Admin Panel is accessible to users with `admin` or `sudo_admin` roles. It provides tools for managing API sync operations, linking planners to circuits, user management, and system settings.

## Access Control

| Role | Tabs Visible |
|------|--------------|
| `admin` | Sync Control, Unlinked Planners, Settings |
| `sudo_admin` | All tabs including User Management |

---

## Layout Structure

```
+------------------------------------------------------------------+
|  [HEADER/NAVBAR - Same as Dashboard]                             |
+------------------------------------------------------------------+
|        |                                                          |
| [SIDE] |  ADMIN CONTROL PANEL                                    |
| [BAR]  |  +-------------------------------------------------+    |
|        |  | [Sync] [Planners] [Users*] [Settings]           |    |
|        |  +-------------------------------------------------+    |
|        |                                                          |
|        |  [TAB CONTENT AREA]                                     |
|        |                                                          |
+------------------------------------------------------------------+
* Users tab only visible to sudo_admin
```

---

## Tab 1: Sync Control

### Layout
```
+------------------------------------------------------------------+
|  SYNC CONTROL                                                     |
+------------------------------------------------------------------+
|  +--------------------------+  +-------------------------------+  |
|  | LAST SYNC STATUS         |  | SYNC HISTORY (Last 10)        |  |
|  |                          |  |                               |  |
|  | ACTIV: 4:32 AM (Success) |  | [Table: Date, Filter, Status] |  |
|  | QC: Mon 4:30 AM (Success)|  | Jan 8, 4:32 AM, ACTIV, OK     |  |
|  | REWORK: Mon 4:30 AM      |  | Jan 8, 4:30 AM, QC, OK        |  |
|  | CLOSE: Mon 4:30 AM       |  | Jan 7, 4:32 PM, ACTIV, OK     |  |
|  |                          |  | ...                           |  |
|  | Next ACTIV: 4:30 PM      |  |                               |  |
|  +--------------------------+  +-------------------------------+  |
|                                                                   |
|  +--------------------------+  +-------------------------------+  |
|  | FORCE SYNC               |  | SYNC PROGRESS                 |  |
|  |                          |  |                               |  |
|  | Filter: [ACTIV    v]     |  | [=========>          ] 45%    |  |
|  | [Run Manual Sync]        |  | Syncing circuit 23 of 51...   |  |
|  |                          |  |                               |  |
|  +--------------------------+  +-------------------------------+  |
+------------------------------------------------------------------+
```

### Last Sync Status Card
```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
  <div class="card-body">
    <h3 class="card-title text-base flex items-center gap-2">
      <flux:icon name="arrow-path" class="w-5 h-5 text-primary" />
      Last Sync Status
    </h3>

    <div class="mt-4 space-y-3">
      @foreach(['ACTIV', 'QC', 'REWORK', 'CLOSE'] as $filter)
      <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
        <div class="flex items-center gap-3">
          <span class="badge badge-sm {{ $filter === 'ACTIV' ? 'badge-primary' : 'badge-ghost' }}">
            {{ $filter }}
          </span>
          <span class="text-sm text-base-content/70">
            {{ $lastSync[$filter]?->completed_at->format('M j, g:i A') ?? 'Never' }}
          </span>
        </div>
        @if($lastSync[$filter]?->status === 'success')
          <span class="badge badge-success badge-sm gap-1">
            <flux:icon name="check-circle" class="w-3 h-3" /> Success
          </span>
        @elseif($lastSync[$filter]?->status === 'failed')
          <span class="badge badge-error badge-sm gap-1">
            <flux:icon name="x-circle" class="w-3 h-3" /> Failed
          </span>
        @else
          <span class="badge badge-ghost badge-sm">Pending</span>
        @endif
      </div>
      @endforeach
    </div>

    <div class="divider my-2"></div>

    <div class="flex items-center justify-between text-sm">
      <span class="text-base-content/60">Next scheduled sync:</span>
      <span class="font-medium">{{ $nextSync->format('M j, g:i A') }} (ACTIV)</span>
    </div>
  </div>
</div>
```

### Force Sync Card
```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
  <div class="card-body">
    <h3 class="card-title text-base flex items-center gap-2">
      <flux:icon name="bolt" class="w-5 h-5 text-warning" />
      Force Manual Sync
    </h3>

    <div class="form-control mt-4">
      <label class="label">
        <span class="label-text">Select Filter</span>
      </label>
      <select wire:model="selectedFilter" class="select select-bordered">
        <option value="ACTIV">ACTIV (In Progress)</option>
        <option value="QC">QC</option>
        <option value="REWORK">REWORK</option>
        <option value="CLOSE">CLOSE</option>
        <option value="ALL">ALL (Full Sync)</option>
      </select>
    </div>

    <button wire:click="triggerSync"
            wire:loading.attr="disabled"
            class="btn btn-primary mt-4 w-full"
            :disabled="$isSyncing">
      <flux:icon name="arrow-path" class="w-4 h-4 mr-2"
                 wire:loading.class="animate-spin" wire:target="triggerSync" />
      <span wire:loading.remove wire:target="triggerSync">Run Manual Sync</span>
      <span wire:loading wire:target="triggerSync">Syncing...</span>
    </button>

    @if($syncWarning)
    <div class="alert alert-warning mt-4">
      <flux:icon name="exclamation-triangle" class="w-5 h-5" />
      <span>Full sync may take several minutes. Continue?</span>
    </div>
    @endif
  </div>
</div>
```

### Sync Progress Card
```blade
<div class="card bg-base-100 shadow-sm border border-base-200" wire:poll.1s="refreshSyncProgress">
  <div class="card-body">
    <h3 class="card-title text-base">Sync Progress</h3>

    @if($isSyncing)
    <div class="mt-4">
      <div class="flex justify-between text-sm mb-2">
        <span>{{ $syncStatus }}</span>
        <span class="font-mono">{{ $syncProgress }}%</span>
      </div>
      <progress class="progress progress-primary w-full" value="{{ $syncProgress }}" max="100"></progress>
      <p class="text-xs text-base-content/60 mt-2">
        Processing circuit {{ $currentCircuit }} of {{ $totalCircuits }}...
      </p>
    </div>
    @else
    <div class="flex flex-col items-center justify-center py-8 text-center">
      <div class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center mb-3">
        <flux:icon name="check" class="w-6 h-6 text-success" />
      </div>
      <p class="text-sm text-base-content/60">No sync in progress</p>
    </div>
    @endif
  </div>
</div>
```

### Sync History Table
```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
  <div class="card-body">
    <h3 class="card-title text-base">Sync History</h3>

    <div class="overflow-x-auto mt-4">
      <table class="table table-sm">
        <thead>
          <tr class="bg-base-200/50">
            <th>Date/Time</th>
            <th>Filter</th>
            <th>Circuits</th>
            <th>Duration</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($syncHistory as $sync)
          <tr class="hover:bg-base-50">
            <td class="text-sm">{{ $sync->completed_at->format('M j, g:i A') }}</td>
            <td><span class="badge badge-ghost badge-sm">{{ $sync->filter_value }}</span></td>
            <td class="text-sm">{{ $sync->circuits_processed }}</td>
            <td class="text-sm font-mono">{{ $sync->duration }}s</td>
            <td>
              @if($sync->status === 'success')
                <span class="badge badge-success badge-sm">OK</span>
              @else
                <span class="badge badge-error badge-sm tooltip" data-tip="{{ $sync->error_message }}">Failed</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
```

---

## Tab 2: Unlinked Planners

### Layout
```
+------------------------------------------------------------------+
|  UNLINKED PLANNERS                                                |
|  Planners from WorkStudio API that aren't linked to users         |
+------------------------------------------------------------------+
|  [Search planners...]                [Refresh List]              |
+------------------------------------------------------------------+
|  TABLE                                                            |
|  +--------------------------------------------------------------+ |
|  | WS Username        | Domain      | Circuits | Actions        | |
|  +--------------------------------------------------------------+ |
|  | plongenecker       | ASPLUNDH    | 12       | [Link to User] | |
|  | jsmith.ppl         | ONEPPL      | 8        | [Link to User] | |
|  | mwilliams          | ASPLUNDH    | 5        | [Link to User] | |
|  +--------------------------------------------------------------+ |
+------------------------------------------------------------------+
```

### Full Component
```blade
<div class="space-y-4">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-xl font-semibold">Unlinked Planners</h2>
      <p class="text-sm text-base-content/60">
        These planners appear in WorkStudio API responses but aren't linked to any user account.
      </p>
    </div>
    <button wire:click="refreshUnlinkedPlanners" class="btn btn-outline btn-sm">
      <flux:icon name="arrow-path" class="w-4 h-4 mr-1" wire:loading.class="animate-spin" />
      Refresh List
    </button>
  </div>

  <!-- Search -->
  <div class="form-control">
    <input type="text" wire:model.live.debounce.300ms="search"
           placeholder="Search by username or domain..."
           class="input input-bordered w-full sm:w-64" />
  </div>

  <!-- Table -->
  <div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>WS Username</th>
            <th>Domain</th>
            <th class="text-center">Assigned Circuits</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($unlinkedPlanners as $planner)
          <tr class="hover:bg-base-50">
            <td>
              <div class="flex items-center gap-3">
                <div class="avatar placeholder">
                  <div class="bg-warning/20 text-warning rounded-full w-10">
                    <flux:icon name="user-circle" class="w-6 h-6" />
                  </div>
                </div>
                <span class="font-mono text-sm">{{ $planner->ws_username }}</span>
              </div>
            </td>
            <td><span class="badge badge-ghost badge-sm">{{ $planner->domain }}</span></td>
            <td class="text-center">
              <span class="badge badge-neutral">{{ $planner->circuits_count }}</span>
            </td>
            <td class="text-right">
              <button wire:click="openLinkModal('{{ $planner->id }}')" class="btn btn-primary btn-sm">
                <flux:icon name="link" class="w-4 h-4 mr-1" />
                Link to User
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="text-center py-12">
              <flux:icon name="check-circle" class="w-12 h-12 mx-auto text-success mb-3" />
              <p class="font-medium">All planners are linked!</p>
              <p class="text-sm text-base-content/60">No unlinked planners found.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{ $unlinkedPlanners->links() }}
</div>
```

### Link Planner Modal
```blade
<dialog wire:ignore.self class="modal" @class(['modal-open' => $showLinkModal])>
  <div class="modal-box">
    <h3 class="font-bold text-lg">Link Planner to User</h3>

    <div class="py-4">
      <div class="flex items-center gap-3 p-4 bg-base-200/50 rounded-lg mb-4">
        <div class="avatar placeholder">
          <div class="bg-warning/20 text-warning rounded-full w-12">
            <flux:icon name="user-circle" class="w-8 h-8" />
          </div>
        </div>
        <div>
          <p class="font-mono font-semibold">{{ $linkingPlanner?->ws_username }}</p>
          <p class="text-sm text-base-content/60">{{ $linkingPlanner?->domain }}</p>
        </div>
      </div>

      <div class="form-control">
        <label class="label">
          <span class="label-text">Select User to Link</span>
        </label>
        <select wire:model="selectedUserId" class="select select-bordered">
          <option value="">-- Select User --</option>
          @foreach($availableUsers as $user)
          <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
          @endforeach
        </select>
      </div>

      <div class="divider">OR</div>

      <button wire:click="createNewUserForPlanner" class="btn btn-outline w-full">
        <flux:icon name="user-plus" class="w-4 h-4 mr-2" />
        Create New User Account
      </button>
    </div>

    <div class="modal-action">
      <button wire:click="closeLinkModal" class="btn btn-ghost">Cancel</button>
      <button wire:click="linkPlanner" class="btn btn-primary" :disabled="!$selectedUserId">
        Link Planner
      </button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button wire:click="closeLinkModal">close</button>
  </form>
</dialog>
```

---

## Tab 3: User Management (sudo_admin only)

### Layout
```
+------------------------------------------------------------------+
|  USER MANAGEMENT                                                  |
+------------------------------------------------------------------+
|  [Search users...]   [Filter: Role v]  [Region v]   [+ Add User] |
+------------------------------------------------------------------+
|  TABLE                                                            |
|  +--------------------------------------------------------------+ |
|  | User              | Role        | Regions      | WS | Actions| |
|  +--------------------------------------------------------------+ |
|  | [Av] John Smith   | Admin       | Central, Lan | [Y]| [E][D] | |
|  |      jsmith@...   |             |              |    |        | |
|  | [Av] Jane Doe     | Planner     | Lancaster    | [Y]| [E][D] | |
|  |      jdoe@...     |             |              |    |        | |
|  +--------------------------------------------------------------+ |
+------------------------------------------------------------------+
```

### Full Component
```blade
<div class="space-y-4">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h2 class="text-xl font-semibold">User Management</h2>
    <button wire:click="openCreateUserModal" class="btn btn-primary btn-sm">
      <flux:icon name="user-plus" class="w-4 h-4 mr-1" />
      Add User
    </button>
  </div>

  <!-- Filters -->
  <div class="flex flex-wrap gap-3">
    <input type="text" wire:model.live.debounce.300ms="search"
           placeholder="Search users..." class="input input-bordered input-sm w-48" />
    <select wire:model.live="filterRole" class="select select-bordered select-sm">
      <option value="">All Roles</option>
      <option value="sudo_admin">Sudo Admin</option>
      <option value="admin">Admin</option>
      <option value="supervisor">Supervisor</option>
      <option value="planner">Planner</option>
    </select>
    <select wire:model.live="filterRegion" class="select select-bordered select-sm">
      <option value="">All Regions</option>
      @foreach($regions as $region)
      <option value="{{ $region->id }}">{{ $region->name }}</option>
      @endforeach
    </select>
  </div>

  <!-- Table -->
  <div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>User</th>
            <th>Role</th>
            <th>Regions</th>
            <th class="text-center">WS Verified</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($users as $user)
          <tr class="hover:bg-base-50">
            <td>
              <div class="flex items-center gap-3">
                <div class="avatar placeholder">
                  <div class="bg-neutral text-neutral-content rounded-full w-10">
                    <span>{{ $user->initials() }}</span>
                  </div>
                </div>
                <div>
                  <p class="font-medium">{{ $user->name }}</p>
                  <p class="text-sm text-base-content/60">{{ $user->email }}</p>
                </div>
              </div>
            </td>
            <td>
              @php
                $roleColors = [
                  'sudo_admin' => 'badge-error',
                  'admin' => 'badge-warning',
                  'supervisor' => 'badge-info',
                  'planner' => 'badge-success',
                ];
              @endphp
              <span class="badge {{ $roleColors[$user->roles->first()?->name] ?? 'badge-ghost' }} badge-sm">
                {{ ucfirst(str_replace('_', ' ', $user->roles->first()?->name ?? 'none')) }}
              </span>
            </td>
            <td>
              <div class="flex flex-wrap gap-1 max-w-[200px]">
                @foreach($user->regions->take(3) as $region)
                  <span class="badge badge-outline badge-xs">{{ $region->name }}</span>
                @endforeach
                @if($user->regions->count() > 3)
                  <span class="badge badge-ghost badge-xs">+{{ $user->regions->count() - 3 }}</span>
                @endif
              </div>
            </td>
            <td class="text-center">
              @if($user->ws_credentials_verified_at)
                <flux:icon name="check-circle" class="w-5 h-5 text-success inline" />
              @else
                <flux:icon name="x-circle" class="w-5 h-5 text-base-content/30 inline" />
              @endif
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-1">
                <button wire:click="editUser({{ $user->id }})" class="btn btn-ghost btn-sm btn-square">
                  <flux:icon name="pencil" class="w-4 h-4" />
                </button>
                @if($user->id !== auth()->id())
                <button wire:click="confirmDeleteUser({{ $user->id }})" class="btn btn-ghost btn-sm btn-square text-error">
                  <flux:icon name="trash" class="w-4 h-4" />
                </button>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{ $users->links() }}
</div>
```

### Edit User Modal (Tabbed)
```blade
<dialog wire:ignore.self class="modal" @class(['modal-open' => $showEditModal])>
  <div class="modal-box max-w-2xl">
    <form wire:submit="updateUser">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-lg">Edit User: {{ $editingUser?->name }}</h3>
        <button type="button" wire:click="closeEditModal" class="btn btn-sm btn-circle btn-ghost">
          <flux:icon name="x-mark" class="w-5 h-5" />
        </button>
      </div>

      <!-- Tab Navigation -->
      <div class="tabs tabs-bordered mb-6">
        <button type="button" wire:click="$set('editTab', 'profile')"
                @class(['tab', 'tab-active' => $editTab === 'profile'])>Profile</button>
        <button type="button" wire:click="$set('editTab', 'roles')"
                @class(['tab', 'tab-active' => $editTab === 'roles'])>Role & Regions</button>
        <button type="button" wire:click="$set('editTab', 'credentials')"
                @class(['tab', 'tab-active' => $editTab === 'credentials'])>Credentials</button>
      </div>

      <!-- Profile Tab -->
      @if($editTab === 'profile')
      <div class="space-y-4">
        <div class="form-control">
          <label class="label"><span class="label-text">Name *</span></label>
          <input type="text" wire:model="editForm.name" class="input input-bordered" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Email *</span></label>
          <input type="email" wire:model="editForm.email" class="input input-bordered" />
        </div>
      </div>
      @endif

      <!-- Roles Tab -->
      @if($editTab === 'roles')
      <div class="space-y-4">
        <div class="form-control">
          <label class="label"><span class="label-text">Role *</span></label>
          <select wire:model="editForm.role" class="select select-bordered">
            <option value="planner">Planner</option>
            <option value="supervisor">Supervisor</option>
            <option value="admin">Admin</option>
            @if(auth()->user()->hasRole('sudo_admin'))
            <option value="sudo_admin">Sudo Admin</option>
            @endif
          </select>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Assigned Regions</span></label>
          <div class="grid grid-cols-2 gap-3 p-4 bg-base-200/50 rounded-lg">
            @foreach($allRegions as $region)
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" wire:model="editForm.regions" value="{{ $region->id }}"
                     class="checkbox checkbox-primary checkbox-sm" />
              <span class="text-sm">{{ $region->name }}</span>
            </label>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      <!-- Credentials Tab -->
      @if($editTab === 'credentials')
      <div class="space-y-4">
        <div class="form-control">
          <label class="label"><span class="label-text">API Username</span></label>
          <div class="flex gap-2">
            <input type="text" wire:model="editForm.api_username" class="input input-bordered flex-1" />
            <button type="button" wire:click="testApiCredentials" class="btn btn-outline">Test</button>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4 p-4 bg-base-200/50 rounded-lg">
          <div>
            <p class="text-xs text-base-content/60">WS Verified</p>
            <p class="font-medium">
              {{ $editingUser?->ws_credentials_verified_at?->format('M j, Y') ?? 'Not verified' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-base-content/60">Last Used</p>
            <p class="font-medium">
              {{ $editingUser?->ws_credentials_last_used_at?->diffForHumans() ?? 'Never' }}
            </p>
          </div>
        </div>
        <button type="button" wire:click="sendPasswordReset" class="btn btn-outline btn-warning w-full">
          <flux:icon name="key" class="w-4 h-4 mr-2" />
          Send Password Reset Email
        </button>
      </div>
      @endif

      <div class="modal-action">
        <button type="button" wire:click="closeEditModal" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</dialog>
```

---

## Tab 4: Settings Panel

### Layout
```
+------------------------------------------------------------------+
|  SETTINGS (2-column grid on desktop)                              |
+------------------------------------------------------------------+
|  +----------------------------+ +-------------------------------+ |
|  | SYNC SCHEDULE              | | API CREDENTIALS               | |
|  | (Read-only)                | |                               | |
|  |                            | | Service Account: Active [*]   | |
|  | ACTIV: 4:30 AM/PM Weekdays | | Last verified: 2 days ago     | |
|  | QC/REWORK/CLOSE: Mon 4:30  | |                               | |
|  +----------------------------+ | [ Test Connection ]           | |
|                                 +-------------------------------+ |
|  +----------------------------+ +-------------------------------+ |
|  | CACHE MANAGEMENT           | | SYSTEM INFO                   | |
|  |                            | |                               | |
|  | [ Clear View Cache    ]    | | Laravel: 12.x                 | |
|  | [ Clear Config Cache  ]    | | PHP: 8.4.x                    | |
|  | [ Clear Route Cache   ]    | | Database: PostgreSQL          | |
|  | [ Clear All Caches    ]    | | Environment: local            | |
|  +----------------------------+ +-------------------------------+ |
+------------------------------------------------------------------+
```

### Sync Schedule Card
```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
  <div class="card-body">
    <div class="flex items-center justify-between">
      <h3 class="card-title text-base">Sync Schedule</h3>
      <span class="badge badge-ghost badge-sm">Read-only</span>
    </div>

    <div class="mt-4 space-y-4">
      <!-- ACTIV Schedule -->
      <div class="p-3 bg-base-200/50 rounded-lg">
        <div class="flex items-center gap-2 mb-2">
          <span class="badge badge-primary badge-sm">ACTIV</span>
          <span class="text-sm font-medium">In Progress Circuits</span>
        </div>
        <div class="text-sm text-base-content/70 space-y-1">
          <div class="flex items-center gap-2">
            <flux:icon name="clock" class="w-4 h-4" />
            <span>Weekdays at 4:30 AM & 4:30 PM EST</span>
          </div>
        </div>
      </div>

      <!-- Other Schedules -->
      <div class="p-3 bg-base-200/50 rounded-lg">
        <div class="flex items-center gap-2 mb-2">
          <span class="badge badge-secondary badge-sm">QC</span>
          <span class="badge badge-accent badge-sm">REWORK</span>
          <span class="badge badge-neutral badge-sm">CLOSE</span>
        </div>
        <div class="text-sm text-base-content/70">
          <div class="flex items-center gap-2">
            <flux:icon name="clock" class="w-4 h-4" />
            <span>Mondays at 4:30 AM EST</span>
          </div>
        </div>
      </div>

      <p class="text-xs text-base-content/50">Timezone: America/New_York</p>
    </div>
  </div>
</div>
```

### API Credentials Card
```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
  <div class="card-body">
    <h3 class="card-title text-base">API Credentials</h3>

    <div class="mt-4 space-y-4">
      <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
        <div>
          <p class="text-sm font-medium">Service Account</p>
          <p class="text-xs text-base-content/60">Automated sync operations</p>
        </div>
        @if($serviceAccountActive)
          <span class="badge badge-success gap-1">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute h-full w-full rounded-full bg-success opacity-75"></span>
              <span class="relative rounded-full h-2 w-2 bg-success"></span>
            </span>
            Active
          </span>
        @else
          <span class="badge badge-error">Inactive</span>
        @endif
      </div>

      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-base-content/60">Last Verified</p>
          <p class="font-medium">{{ $lastVerified?->diffForHumans() ?? 'Never' }}</p>
        </div>
        <div>
          <p class="text-base-content/60">Last Error</p>
          <p class="font-medium">{{ $lastError ?? 'None' }}</p>
        </div>
      </div>

      <button wire:click="testConnection" class="btn btn-outline w-full">
        <flux:icon name="signal" class="w-4 h-4 mr-2" />
        Test Connection
      </button>
    </div>
  </div>
</div>
```

### Cache Management Card
```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
  <div class="card-body">
    <h3 class="card-title text-base">Cache Management</h3>

    <div class="mt-4 space-y-3">
      <button wire:click="clearViewCache" class="btn btn-outline btn-sm w-full justify-start">
        <flux:icon name="eye" class="w-4 h-4 mr-2" />
        Clear View Cache
      </button>
      <button wire:click="clearConfigCache" class="btn btn-outline btn-sm w-full justify-start">
        <flux:icon name="cog-6-tooth" class="w-4 h-4 mr-2" />
        Clear Config Cache
      </button>
      <button wire:click="clearRouteCache" class="btn btn-outline btn-sm w-full justify-start">
        <flux:icon name="map" class="w-4 h-4 mr-2" />
        Clear Route Cache
      </button>

      <div class="divider my-2"></div>

      <button wire:click="clearAllCaches" class="btn btn-warning btn-sm w-full">
        <flux:icon name="trash" class="w-4 h-4 mr-2" />
        Clear All Caches
      </button>
    </div>
  </div>
</div>
```

### System Info Card
```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
  <div class="card-body">
    <h3 class="card-title text-base">System Information</h3>

    <div class="mt-4">
      <table class="table table-sm">
        <tbody>
          <tr>
            <td class="text-base-content/60">Laravel</td>
            <td class="font-mono text-sm">{{ app()->version() }}</td>
          </tr>
          <tr>
            <td class="text-base-content/60">PHP</td>
            <td class="font-mono text-sm">{{ PHP_VERSION }}</td>
          </tr>
          <tr>
            <td class="text-base-content/60">Database</td>
            <td class="font-mono text-sm">PostgreSQL</td>
          </tr>
          <tr>
            <td class="text-base-content/60">Environment</td>
            <td>
              <span @class([
                'badge badge-sm',
                'badge-success' => app()->environment('production'),
                'badge-warning' => app()->environment('staging'),
                'badge-info' => app()->environment('local'),
              ])>
                {{ app()->environment() }}
              </span>
            </td>
          </tr>
          <tr>
            <td class="text-base-content/60">Timezone</td>
            <td class="font-mono text-sm">{{ config('app.timezone') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
```

---

## Color Palette

### Brand Colors
| Name | Hex | Usage |
|------|-----|-------|
| PPL Cyan | `#1882C5` | Primary |
| St. Patrick's Blue | `#28317E` | Secondary |
| Asplundh Orange | `#E27434` | Accent |

### Role Badge Colors
| Role | Badge Class |
|------|-------------|
| sudo_admin | `badge-error` |
| admin | `badge-warning` |
| supervisor | `badge-info` |
| planner | `badge-success` |

---

## Responsive Behavior

| Breakpoint | Layout Changes |
|------------|----------------|
| Mobile (<640px) | Single column, stacked cards |
| Tablet (640-1024px) | 2-column grids |
| Desktop (>1024px) | Full layout with sidebar |

---

## Component Structure

```
app/Livewire/Admin/
├── AdminControlPanel.php      (Parent with tab state)
├── SyncControl.php            (Tab 1)
├── UnlinkedPlanners.php       (Tab 2)
├── UserManagement.php         (Tab 3 - sudo_admin)
└── AdminSettings.php          (Tab 4)
```

---

## Icon Reference (Heroicons/Flux)

| Purpose | Icon Name |
|---------|-----------|
| Sync | `arrow-path` |
| User | `user` |
| Add User | `user-plus` |
| Settings | `cog-6-tooth` |
| Check | `check-circle` |
| Error | `x-circle` |
| Warning | `exclamation-triangle` |
| Link | `link` |
| Edit | `pencil` |
| Delete | `trash` |
| Search | `magnifying-glass` |
| Close | `x-mark` |

---

*This specification provides all details needed to implement the WS-Tracker Admin Panel UI.*
