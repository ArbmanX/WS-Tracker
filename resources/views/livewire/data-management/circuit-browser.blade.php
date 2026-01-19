<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm breadcrumbs">
            <ul>
                <li><a href="{{ route('admin.data') }}">Data Management</a></li>
                <li>Circuit Browser</li>
            </ul>
        </div>
        <h1 class="text-2xl font-bold text-base-content">Circuit Browser</h1>
        <p class="text-base-content/60">Browse, search, and manage circuit data</p>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body py-4">
            <div class="flex flex-wrap gap-4 items-end">
                {{-- Search --}}
                <div class="form-control flex-1 min-w-[200px]">
                    <label class="label">
                        <span class="label-text text-xs">Search</span>
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Work order, title, or GUID..."
                        class="input input-bordered input-sm"
                    />
                </div>

                {{-- Region Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Region</span>
                    </label>
                    <select wire:model.live="regionFilter" class="select select-bordered select-sm">
                        <option value="">All Regions</option>
                        @foreach($this->regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- API Status Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">API Status</span>
                    </label>
                    <select wire:model.live="apiStatusFilter" class="select select-bordered select-sm">
                        <option value="">All Statuses</option>
                        @foreach($this->apiStatusOptions as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Scope Year Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Scope Year</span>
                    </label>
                    <select wire:model.live="scopeYearFilter" class="select select-bordered select-sm">
                        <option value="">All Years</option>
                        @foreach($this->scopeYearOptions as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Cycle Type Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Cycle Type</span>
                    </label>
                    <select wire:model.live="cycleTypeFilter" class="select select-bordered select-sm">
                        <option value="">All Types</option>
                        @foreach($this->cycleTypeOptions as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Excluded Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Excluded</span>
                    </label>
                    <select wire:model.live="excludedFilter" class="select select-bordered select-sm">
                        <option value="">Any</option>
                        <option value="yes">Excluded Only</option>
                        <option value="no">Not Excluded</option>
                    </select>
                </div>

                {{-- Modified Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Modified</span>
                    </label>
                    <select wire:model.live="modifiedFilter" class="select select-bordered select-sm">
                        <option value="">Any</option>
                        <option value="yes">User Modified</option>
                        <option value="no">Not Modified</option>
                    </select>
                </div>

                {{-- Clear Filters --}}
                @if($search || $regionFilter || $apiStatusFilter || $excludedFilter || $modifiedFilter || $scopeYearFilter || $cycleTypeFilter)
                    <button wire:click="clearFilters" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Bulk Action Bar --}}
    @if(count($selectedCircuits) > 0)
        <div class="alert bg-primary/10 border-primary mb-6">
            <div class="flex w-full items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-check-circle class="h-5 w-5 text-primary" />
                    <span class="font-medium">{{ count($selectedCircuits) }} circuit(s) selected</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($excludedFilter === 'yes')
                        <button wire:click="bulkIncludeCircuits" wire:confirm="Include {{ count($selectedCircuits) }} circuits back in reporting?" class="btn btn-success btn-sm">
                            <x-heroicon-o-eye class="h-4 w-4" />
                            Include All
                        </button>
                    @else
                        <button wire:click="openBulkExcludeModal" class="btn btn-warning btn-sm">
                            <x-heroicon-o-eye-slash class="h-4 w-4" />
                            Exclude All
                        </button>
                    @endif
                    <button wire:click="clearCircuitSelection" class="btn btn-ghost btn-sm">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                        Clear
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Circuits Table --}}
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            @if($circuits->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <p class="text-base-content/40">No circuits found</p>
                    @if($search || $regionFilter || $apiStatusFilter || $excludedFilter || $modifiedFilter || $scopeYearFilter || $cycleTypeFilter)
                        <button wire:click="clearFilters" class="btn btn-ghost btn-sm mt-2">Clear filters</button>
                    @endif
                </div>
            @else
                @php
                    $pageIds = $circuits->pluck('id')->toArray();
                    $allPageSelected = !empty($pageIds) && empty(array_diff($pageIds, $selectedCircuits));
                @endphp
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="w-10">
                                    <label class="cursor-pointer">
                                        <input
                                            type="checkbox"
                                            class="checkbox checkbox-sm checkbox-primary"
                                            wire:click="{{ $allPageSelected ? 'deselectAllOnPage' : 'selectAllOnPage' }}({{ json_encode($pageIds) }})"
                                            @checked($allPageSelected)
                                        />
                                    </label>
                                </th>
                                <th>Work Order</th>
                                <th>Title</th>
                                <th>Year</th>
                                <th>Cycle Type</th>
                                <th>Region</th>
                                <th>Status</th>
                                <th>Miles</th>
                                <th>Flags</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($circuits as $circuit)
                                <tr class="hover" wire:key="circuit-{{ $circuit->id }}">
                                    <td>
                                        <label class="cursor-pointer">
                                            <input
                                                type="checkbox"
                                                class="checkbox checkbox-sm checkbox-primary"
                                                wire:click="toggleCircuitSelection({{ $circuit->id }})"
                                                @checked($this->isCircuitSelected($circuit->id))
                                            />
                                        </label>
                                    </td>
                                    <td>
                                        <span class="font-mono text-sm">{{ $circuit->display_work_order }}</span>
                                    </td>
                                    <td class="max-w-xs truncate" title="{{ $circuit->title }}">
                                        {{ Str::limit($circuit->title, 40) }}
                                    </td>
                                    <td class="font-mono text-sm text-base-content/70">
                                        {{ $circuit->scope_year ?? '-' }}
                                    </td>
                                    <td class="text-sm max-w-32 truncate" title="{{ $circuit->cycle_type }}">
                                        {{ Str::limit($circuit->cycle_type, 20) ?? '-' }}
                                    </td>
                                    <td class="text-sm">
                                        {{ $circuit->region?->name ?? '-' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-ghost badge-sm">{{ $circuit->api_status }}</span>
                                    </td>
                                    <td class="font-mono text-sm">
                                        {{ number_format($circuit->total_miles, 2) }}
                                    </td>
                                    <td>
                                        <div class="flex gap-1">
                                            @if($circuit->is_excluded)
                                                <span class="badge badge-warning badge-xs" title="Excluded from reporting">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                    </svg>
                                                </span>
                                            @endif
                                            @if($circuit->hasUserModifications())
                                                <span class="badge badge-info badge-xs" title="User modified">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown dropdown-end">
                                            <div tabindex="0" role="button" class="btn btn-ghost btn-xs btn-circle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                </svg>
                                            </div>
                                            <ul tabindex="0" class="dropdown-content z-50 menu p-2 shadow-lg bg-base-100 rounded-box w-40 border border-base-200">
                                                <li>
                                                    <button wire:click="viewCircuit({{ $circuit->id }})">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View
                                                    </button>
                                                </li>
                                                @if($circuit->is_excluded)
                                                    <li>
                                                        <button wire:click="includeCircuit({{ $circuit->id }})" class="text-success">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                            Include
                                                        </button>
                                                    </li>
                                                @else
                                                    <li>
                                                        <button wire:click="openExcludeModal({{ $circuit->id }})" class="text-warning">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                            </svg>
                                                            Exclude
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $circuits->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Circuit Detail Modal --}}
    <dialog class="modal {{ $showModal ? 'modal-open' : '' }}">
        <div class="modal-box max-w-4xl">
            @if($this->selectedCircuit)
                @php $circuit = $this->selectedCircuit; @endphp

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-lg">{{ $circuit->display_work_order }}</h3>
                        <p class="text-sm text-base-content/60">{{ $circuit->title }}</p>
                    </div>
                    <div class="flex gap-2">
                        @if($circuit->is_excluded)
                            <span class="badge badge-warning">Excluded</span>
                        @endif
                        @if($circuit->hasUserModifications())
                            <span class="badge badge-info">Modified</span>
                        @endif
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="tabs tabs-boxed mb-4">
                    <button
                        wire:click="$set('activeTab', 'overview')"
                        class="tab {{ $activeTab === 'overview' ? 'tab-active' : '' }}"
                    >
                        Overview
                    </button>
                    <button
                        wire:click="$set('activeTab', 'raw')"
                        class="tab {{ $activeTab === 'raw' ? 'tab-active' : '' }}"
                    >
                        Raw Data
                    </button>
                    <button
                        wire:click="$set('activeTab', 'history')"
                        class="tab {{ $activeTab === 'history' ? 'tab-active' : '' }}"
                    >
                        History
                    </button>
                </div>

                {{-- Tab Content --}}
                @if($activeTab === 'overview')
                    @if($isEditing)
                        {{-- Edit Form --}}
                        <div class="alert alert-warning mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Modified fields will be preserved during API syncs.</span>
                        </div>

                        <form wire:submit="saveEdit" class="space-y-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Title</span></label>
                                <input type="text" wire:model="editTitle" class="input input-bordered @error('editTitle') input-error @enderror" />
                                @error('editTitle')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label"><span class="label-text">Total Miles</span></label>
                                <input type="number" step="0.01" wire:model="editTotalMiles" class="input input-bordered @error('editTotalMiles') input-error @enderror" />
                                @error('editTotalMiles')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label"><span class="label-text">Contractor</span></label>
                                <input type="text" wire:model="editContractor" class="input input-bordered @error('editContractor') input-error @enderror" />
                                @error('editContractor')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label"><span class="label-text">Cycle Type</span></label>
                                <input type="text" wire:model="editCycleType" class="input input-bordered @error('editCycleType') input-error @enderror" />
                                @error('editCycleType')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Reason for changes <span class="text-error">*</span></span>
                                </label>
                                <textarea wire:model="editReason" class="textarea textarea-bordered @error('editReason') textarea-error @enderror" placeholder="Explain why you're making these changes..." rows="2"></textarea>
                                @error('editReason')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                            </div>

                            <div class="flex gap-2 justify-end">
                                <button type="button" wire:click="cancelEdit" class="btn btn-ghost">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    @else
                        {{-- Overview Display --}}
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Work Order</span>
                                    <p class="font-mono">{{ $circuit->display_work_order }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Job GUID</span>
                                    <p class="font-mono text-sm break-all">{{ $circuit->job_guid }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Region</span>
                                    <p>{{ $circuit->region?->name ?? '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60 uppercase tracking-wide">API Status</span>
                                    <p><span class="badge badge-ghost">{{ $circuit->api_status }}</span></p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Total Miles</span>
                                    <p class="font-mono">{{ number_format($circuit->total_miles, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Contractor</span>
                                    <p>{{ $circuit->contractor ?? '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Cycle Type</span>
                                    <p>{{ $circuit->cycle_type ?? '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Last Synced</span>
                                    <p>{{ $circuit->last_synced_at?->diffForHumans() ?? 'Never' }}</p>
                                </div>
                            </div>
                        </div>

                        @if($circuit->hasUserModifications())
                            <div class="mt-4 p-3 bg-info/10 rounded-lg">
                                <span class="text-xs font-semibold text-info uppercase">User Modified Fields</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($circuit->getUserModifiedFieldNames() as $field)
                                        <span class="badge badge-info badge-sm">{{ Str::title(str_replace('_', ' ', $field)) }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($circuit->is_excluded)
                            <div class="mt-4 p-3 bg-warning/10 rounded-lg">
                                <span class="text-xs font-semibold text-warning uppercase">Excluded from Reporting</span>
                                <p class="text-sm mt-1">{{ $circuit->exclusion_reason ?? 'No reason provided' }}</p>
                                <p class="text-xs text-base-content/60 mt-1">
                                    By {{ $circuit->excludedBy?->name ?? 'Unknown' }} on {{ $circuit->excluded_at?->format('M d, Y g:i A') }}
                                </p>
                            </div>
                        @endif

                        <div class="flex justify-end mt-4">
                            <button wire:click="startEdit" class="btn btn-primary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Circuit
                            </button>
                        </div>
                    @endif

                @elseif($activeTab === 'raw')
                    <x-ui.json-viewer :data="$circuit->api_data_json" maxHeight="500px" />

                @elseif($activeTab === 'history')
                    <x-ui.activity-log :activities="$this->activities" maxHeight="500px" />
                @endif
            @endif

            <div class="modal-action">
                <button wire:click="closeModal" class="btn">Close</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button wire:click="closeModal">close</button>
        </form>
    </dialog>

    {{-- Single Exclude Modal --}}
    <dialog class="modal {{ $showExcludeModal ? 'modal-open' : '' }}">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Exclude Circuit from Reporting</h3>
            <p class="py-2 text-base-content/60">This circuit will be hidden from all reports and dashboards.</p>

            <form wire:submit="excludeCircuit">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Reason for exclusion <span class="text-error">*</span></span>
                    </label>
                    <textarea
                        wire:model="excludeReason"
                        class="textarea textarea-bordered @error('excludeReason') textarea-error @enderror"
                        placeholder="Why is this circuit being excluded?"
                        rows="3"
                    ></textarea>
                    @error('excludeReason')
                        <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                    @enderror
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="$set('showExcludeModal', false)" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-warning">Exclude Circuit</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button wire:click="$set('showExcludeModal', false)">close</button>
        </form>
    </dialog>

    {{-- Bulk Exclude Modal --}}
    <dialog class="modal {{ $showBulkExcludeModal ? 'modal-open' : '' }}">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg">Exclude {{ count($selectedCircuits) }} Circuits from Reporting</h3>
            <p class="py-2 text-base-content/60">
                The following circuits will be hidden from all reports and dashboards:
            </p>

            {{-- List of selected circuits --}}
            <div class="max-h-48 overflow-y-auto bg-base-200 rounded-lg p-3 my-4">
                <ul class="space-y-1">
                    @foreach($this->selectedCircuitsData as $circuit)
                        <li class="flex items-center gap-2 text-sm">
                            <x-heroicon-o-map class="h-4 w-4 text-base-content/40" />
                            <span class="font-mono">{{ $circuit->display_work_order }}</span>
                            <span class="text-base-content/60 truncate">{{ Str::limit($circuit->title, 30) }}</span>
                            <span class="badge badge-ghost badge-xs ml-auto">{{ $circuit->api_status }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <form wire:submit="bulkExcludeCircuits">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Reason for exclusion <span class="text-error">*</span></span>
                    </label>
                    <textarea
                        wire:model="bulkExcludeReason"
                        class="textarea textarea-bordered @error('bulkExcludeReason') textarea-error @enderror"
                        placeholder="Why are these circuits being excluded?"
                        rows="3"
                    ></textarea>
                    @error('bulkExcludeReason')
                        <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                    @enderror
                    <label class="label">
                        <span class="label-text-alt text-base-content/50">This reason will be applied to all selected circuits</span>
                    </label>
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="cancelBulkExclude" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <x-heroicon-o-eye-slash class="h-4 w-4" />
                        Exclude {{ count($selectedCircuits) }} Circuits
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button wire:click="cancelBulkExclude">close</button>
        </form>
    </dialog>
</div>
