<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Planner Management</h1>
            <p class="text-base-content/60">View and manage all planners</p>
        </div>
        <a href="{{ route('admin.planners.unlinked') }}" class="btn btn-outline btn-sm">
            <x-heroicon-o-link class="h-4 w-4" />
            Link Planners
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="stats shadow w-full mb-6 bg-base-100">
        <div class="stat">
            <div class="stat-figure text-primary">
                <x-heroicon-o-user-circle class="h-8 w-8" />
            </div>
            <div class="stat-title">Linked Planners</div>
            <div class="stat-value text-primary">{{ $this->stats['total_linked'] }}</div>
            @if($this->stats['excluded_linked'] > 0)
                <div class="stat-desc text-warning">{{ $this->stats['excluded_linked'] }} excluded</div>
            @endif
        </div>
        <div class="stat">
            <div class="stat-figure text-secondary">
                <x-heroicon-o-user-plus class="h-8 w-8" />
            </div>
            <div class="stat-title">Unlinked Planners</div>
            <div class="stat-value text-secondary">{{ $this->stats['total_unlinked'] }}</div>
            @if($this->stats['excluded_unlinked'] > 0)
                <div class="stat-desc text-warning">{{ $this->stats['excluded_unlinked'] }} excluded</div>
            @endif
        </div>
        <div class="stat">
            <div class="stat-figure text-accent">
                <x-heroicon-o-users class="h-8 w-8" />
            </div>
            <div class="stat-title">Total Active</div>
            <div class="stat-value text-accent">
                {{ $this->stats['total_linked'] + $this->stats['total_unlinked'] - $this->stats['excluded_linked'] - $this->stats['excluded_unlinked'] }}
            </div>
            <div class="stat-desc">Included in analytics</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body py-4">
            <div class="flex flex-wrap gap-4 items-end">
                {{-- Search --}}
                <div class="form-control flex-1 min-w-48">
                    <label class="label">
                        <span class="label-text">Search</span>
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name or username..."
                        class="input input-bordered input-sm w-full"
                    />
                </div>

                {{-- Link Status Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Status</span>
                    </label>
                    <select wire:model.live="filter" class="select select-bordered select-sm">
                        <option value="all">All Planners</option>
                        <option value="linked">Linked Only</option>
                        <option value="unlinked">Unlinked Only</option>
                    </select>
                </div>

                {{-- Exclusion Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Analytics</span>
                    </label>
                    <select wire:model.live="exclusionFilter" class="select select-bordered select-sm">
                        <option value="active">Active</option>
                        <option value="excluded">Excluded</option>
                        <option value="all">All</option>
                    </select>
                </div>

                {{-- Region Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Region</span>
                    </label>
                    <select wire:model.live="regionFilter" class="select select-bordered select-sm">
                        <option value="">All Regions</option>
                        @foreach($this->regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Action Bar --}}
    @if(count($selectedPlanners) > 0)
        <div class="alert bg-primary/10 border-primary mb-6">
            <div class="flex w-full items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-check-circle class="h-5 w-5 text-primary" />
                    <span class="font-medium">{{ count($selectedPlanners) }} planner(s) selected</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($exclusionFilter === 'excluded')
                        <button wire:click="bulkInclude" wire:confirm="Include {{ count($selectedPlanners) }} planners back in analytics?" class="btn btn-success btn-sm">
                            <x-heroicon-o-eye class="h-4 w-4" />
                            Include All
                        </button>
                    @else
                        <button wire:click="openBulkExcludeModal" class="btn btn-warning btn-sm">
                            <x-heroicon-o-eye-slash class="h-4 w-4" />
                            Exclude All
                        </button>
                    @endif
                    <button wire:click="clearSelection" class="btn btn-ghost btn-sm">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                        Clear
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Planners Table --}}
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <label class="cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="checkbox checkbox-sm checkbox-primary"
                                        wire:click="toggleSelectAll"
                                        @checked($this->allSelected)
                                    />
                                </label>
                            </th>
                            <th>Name</th>
                            <th>WS Username</th>
                            <th>Status</th>
                            <th>Circuits</th>
                            <th>Region</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->allPlanners as $planner)
                            <tr class="hover" wire:key="{{ $planner['type'] }}-{{ $planner['id'] }}">
                                {{-- Checkbox --}}
                                <td>
                                    <label class="cursor-pointer">
                                        <input
                                            type="checkbox"
                                            class="checkbox checkbox-sm checkbox-primary"
                                            wire:click="toggleSelection('{{ $planner['type'] }}-{{ $planner['id'] }}')"
                                            @checked($this->isSelected($planner['type'], $planner['id']))
                                        />
                                    </label>
                                </td>
                                {{-- Name (with edit support) --}}
                                <td>
                                    @if($editingPlannerId === $planner['id'] && $editingPlannerType === $planner['type'])
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="text"
                                                wire:model="editName"
                                                wire:keydown.enter="saveName"
                                                wire:keydown.escape="cancelEditing"
                                                class="input input-bordered input-xs w-40"
                                                autofocus
                                            />
                                            <button wire:click="saveName" class="btn btn-ghost btn-xs text-success">
                                                <x-heroicon-o-check class="h-4 w-4" />
                                            </button>
                                            <button wire:click="cancelEditing" class="btn btn-ghost btn-xs text-error">
                                                <x-heroicon-o-x-mark class="h-4 w-4" />
                                            </button>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <span @class(['line-through text-base-content/50' => $planner['is_excluded']])>
                                                {{ $planner['name'] }}
                                            </span>
                                            @if($planner['is_excluded'])
                                                <div class="tooltip tooltip-right" data-tip="{{ $planner['exclusion_reason'] }}">
                                                    <span class="badge badge-warning badge-xs">excluded</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- WS Username --}}
                                <td class="font-mono text-xs text-base-content/60">
                                    {{ $planner['ws_username'] ?? '-' }}
                                </td>

                                {{-- Link Status --}}
                                <td>
                                    @if($planner['is_linked'])
                                        <span class="badge badge-success badge-sm gap-1">
                                            <x-heroicon-o-link class="h-3 w-3" />
                                            Linked
                                        </span>
                                    @else
                                        <span class="badge badge-neutral badge-sm gap-1">
                                            <x-heroicon-o-link-slash class="h-3 w-3" />
                                            Unlinked
                                        </span>
                                    @endif
                                </td>

                                {{-- Circuit Count --}}
                                <td>
                                    <span class="font-mono">{{ number_format($planner['circuit_count']) }}</span>
                                </td>

                                {{-- Region --}}
                                <td class="text-sm">
                                    {{ $planner['regions'] ?: '-' }}
                                </td>

                                {{-- Actions --}}
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if(auth()->user()?->hasAnyRole(['sudo_admin', 'admin']))
                                            {{-- Edit Name --}}
                                            <button
                                                wire:click="startEditing({{ $planner['id'] }}, '{{ $planner['type'] }}')"
                                                class="btn btn-ghost btn-xs"
                                                title="Edit name"
                                            >
                                                <x-heroicon-o-pencil class="h-4 w-4" />
                                            </button>

                                            {{-- Exclude/Include --}}
                                            @if($planner['is_excluded'])
                                                <button
                                                    wire:click="includePlanner({{ $planner['id'] }}, '{{ $planner['type'] }}')"
                                                    wire:confirm="Include this planner back in analytics?"
                                                    class="btn btn-ghost btn-xs text-success"
                                                    title="Include in analytics"
                                                >
                                                    <x-heroicon-o-eye class="h-4 w-4" />
                                                </button>
                                            @else
                                                <button
                                                    wire:click="startExcluding({{ $planner['id'] }}, '{{ $planner['type'] }}')"
                                                    class="btn btn-ghost btn-xs text-warning"
                                                    title="Exclude from analytics"
                                                >
                                                    <x-heroicon-o-eye-slash class="h-4 w-4" />
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-base-content/40">
                                    No planners found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-sm text-base-content/60">
                Showing {{ $this->allPlanners->count() }} planners
            </div>
        </div>
    </div>

    {{-- Single Exclusion Modal --}}
    @if($excludingPlannerId)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Exclude Planner from Analytics</h3>
                <p class="py-4 text-base-content/60">
                    This planner will be hidden from all analytics, reports, and dashboards.
                </p>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Reason for exclusion <span class="text-error">*</span></span>
                    </label>
                    <textarea
                        wire:model="exclusionReason"
                        class="textarea textarea-bordered"
                        placeholder="e.g., Contractor no longer active, Test account, etc."
                        rows="3"
                    ></textarea>
                </div>

                <div class="modal-action">
                    <button wire:click="cancelExcluding" class="btn btn-ghost">Cancel</button>
                    <button wire:click="excludePlanner" class="btn btn-warning">
                        <x-heroicon-o-eye-slash class="h-4 w-4" />
                        Exclude Planner
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button wire:click="cancelExcluding">close</button>
            </form>
        </div>
    @endif

    {{-- Bulk Exclusion Modal --}}
    @if($showBulkExcludeModal)
        <div class="modal modal-open">
            <div class="modal-box max-w-2xl">
                <h3 class="font-bold text-lg">Exclude {{ count($selectedPlanners) }} Planners from Analytics</h3>
                <p class="py-2 text-base-content/60">
                    The following planners will be hidden from all analytics, reports, and dashboards:
                </p>

                {{-- List of selected planners --}}
                <div class="max-h-48 overflow-y-auto bg-base-200 rounded-lg p-3 my-4">
                    <ul class="space-y-1">
                        @foreach($this->selectedPlannersData as $planner)
                            <li class="flex items-center gap-2 text-sm">
                                <x-heroicon-o-user class="h-4 w-4 text-base-content/40" />
                                <span>{{ $planner['name'] }}</span>
                                @if($planner['ws_username'])
                                    <span class="text-base-content/40 font-mono text-xs">({{ $planner['ws_username'] }})</span>
                                @endif
                                <span class="badge badge-{{ $planner['is_linked'] ? 'success' : 'neutral' }} badge-xs ml-auto">
                                    {{ $planner['is_linked'] ? 'Linked' : 'Unlinked' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Reason for exclusion <span class="text-error">*</span></span>
                    </label>
                    <textarea
                        wire:model="bulkExclusionReason"
                        class="textarea textarea-bordered"
                        placeholder="e.g., Contractors no longer active, Test accounts, etc."
                        rows="3"
                    ></textarea>
                    <label class="label">
                        <span class="label-text-alt text-base-content/50">This reason will be applied to all selected planners</span>
                    </label>
                </div>

                <div class="modal-action">
                    <button wire:click="cancelBulkExclude" class="btn btn-ghost">Cancel</button>
                    <button wire:click="bulkExclude" class="btn btn-warning">
                        <x-heroicon-o-eye-slash class="h-4 w-4" />
                        Exclude {{ count($selectedPlanners) }} Planners
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button wire:click="cancelBulkExclude">close</button>
            </form>
        </div>
    @endif
</div>
