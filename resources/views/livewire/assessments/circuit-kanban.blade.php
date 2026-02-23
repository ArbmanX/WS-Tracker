<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm breadcrumbs">
            <ul>
                <li><a href="{{ route('assessments.overview') }}">Assessments</a></li>
                <li>Kanban Board</li>
            </ul>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Circuit Kanban</h1>
                <p class="text-base-content/60">Visual workflow view of circuits by status</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-neutral badge-lg">
                    {{ $this->totalCircuits }} {{ Str::plural('circuit', $this->totalCircuits) }}
                </span>
            </div>
        </div>
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
                        placeholder="Work order, title, or planner..."
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

                {{-- Planner Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Planner</span>
                    </label>
                    <select wire:model.live="plannerFilter" class="select select-bordered select-sm">
                        <option value="">All Planners</option>
                        @foreach($this->planners as $planner)
                            <option value="{{ $planner }}">{{ $planner }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter (from WithCircuitFilters trait) --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Statuses</span>
                    </label>
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-sm btn-outline gap-1">
                            <x-heroicon-o-funnel class="size-4" />
                            {{ count($statusFilter) }} selected
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-52">
                            @foreach($this->availableStatuses as $code => $label)
                                <li>
                                    <label class="label cursor-pointer justify-start gap-3">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleStatus('{{ $code }}')"
                                            @checked(in_array($code, $statusFilter))
                                            class="checkbox checkbox-primary checkbox-sm"
                                        />
                                        <span class="label-text">{{ $label }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Clear Filters --}}
                @if($this->hasAnyFilters)
                    <button
                        wire:click="clearAllFilters"
                        class="btn btn-sm btn-ghost text-error"
                    >
                        <x-heroicon-o-x-mark class="size-4" />
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($this->columns as $status => $config)
            <div wire:key="column-{{ $status }}" class="flex flex-col">
                {{-- Column Header --}}
                <div class="flex items-center justify-between mb-3 px-2">
                    <div class="flex items-center gap-2">
                        <span class="badge badge-{{ $config['color'] }} badge-sm"></span>
                        <h3 class="font-semibold text-base-content">{{ $config['label'] }}</h3>
                    </div>
                    <span class="badge badge-ghost badge-sm">
                        {{ $this->columnCounts[$status] ?? 0 }}
                    </span>
                </div>

                {{-- Column Content --}}
                <div
                    class="flex-1 bg-base-200/50 rounded-lg p-2 min-h-[400px] max-h-[70vh] overflow-y-auto space-y-2"
                    data-status="{{ $status }}"
                >
                    @forelse($this->circuitsByStatus[$status] ?? collect() as $circuit)
                        <div
                            wire:key="card-{{ $circuit->id }}"
                            wire:click="viewCircuit({{ $circuit->id }})"
                            class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow cursor-pointer border-l-4 border-{{ $config['color'] }}"
                        >
                            <div class="card-body p-3">
                                {{-- Work Order --}}
                                <div class="flex items-start justify-between gap-2">
                                    <span class="font-mono text-sm font-semibold text-base-content">
                                        {{ $circuit->display_work_order }}
                                    </span>
                                    @if($circuit->region)
                                        <span class="badge badge-ghost badge-xs shrink-0">
                                            {{ Str::limit($circuit->region->name, 8) }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Title --}}
                                <p class="text-sm text-base-content/80 line-clamp-2">
                                    {{ $circuit->title ?: 'Untitled' }}
                                </p>

                                {{-- Footer --}}
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-base-200">
                                    {{-- Planner --}}
                                    <div class="flex items-center gap-1 text-xs text-base-content/60">
                                        <x-heroicon-o-user class="size-3" />
                                        <span>{{ $circuit->taken_by ?: 'Unassigned' }}</span>
                                    </div>

                                    {{-- Miles --}}
                                    <div class="flex items-center gap-1 text-xs text-base-content/60">
                                        <x-heroicon-o-map class="size-3" />
                                        <span>{{ number_format($circuit->total_miles, 1) }} mi</span>
                                    </div>
                                </div>

                                {{-- Progress (if available) --}}
                                @if($circuit->percent_complete > 0)
                                    <div class="mt-2">
                                        <div class="flex items-center justify-between text-xs text-base-content/60 mb-1">
                                            <span>Progress</span>
                                            <span>{{ number_format($circuit->percent_complete, 0) }}%</span>
                                        </div>
                                        <progress
                                            class="progress progress-{{ $config['color'] }} h-1"
                                            value="{{ $circuit->percent_complete }}"
                                            max="100"
                                        ></progress>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-base-content/40">
                            <x-heroicon-o-inbox class="size-8 mb-2" />
                            <p class="text-sm">No circuits</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $this->selectedCircuit)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-2xl">
                <button
                    wire:click="closeDetailModal"
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                >
                    <x-heroicon-o-x-mark class="size-5" />
                </button>

                <h3 class="font-bold text-lg mb-4">
                    {{ $this->selectedCircuit->display_work_order }}
                </h3>

                <div class="space-y-4">
                    {{-- Basic Info --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-base-content/60 uppercase tracking-wide">Title</label>
                            <p class="font-medium">{{ $this->selectedCircuit->title ?: 'Untitled' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-base-content/60 uppercase tracking-wide">Status</label>
                            <p>
                                <span class="badge badge-{{ \App\Support\WorkStudioStatus::daisyColor($this->selectedCircuit->api_status) }}">
                                    {{ $this->columns[$this->selectedCircuit->api_status]['label'] ?? $this->selectedCircuit->api_status }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-base-content/60 uppercase tracking-wide">Region</label>
                            <p class="font-medium">{{ $this->selectedCircuit->region?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-base-content/60 uppercase tracking-wide">Planner (Taken By)</label>
                            <p class="font-medium">{{ $this->selectedCircuit->taken_by ?: 'Unassigned' }}</p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    {{-- Metrics --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div class="stat bg-base-200 rounded-lg p-3">
                            <div class="stat-title text-xs">Total Miles</div>
                            <div class="stat-value text-lg">{{ number_format($this->selectedCircuit->total_miles, 2) }}</div>
                        </div>
                        <div class="stat bg-base-200 rounded-lg p-3">
                            <div class="stat-title text-xs">Miles Planned</div>
                            <div class="stat-value text-lg">{{ number_format($this->selectedCircuit->miles_planned, 2) }}</div>
                        </div>
                        <div class="stat bg-base-200 rounded-lg p-3">
                            <div class="stat-title text-xs">Completion</div>
                            <div class="stat-value text-lg">{{ number_format($this->selectedCircuit->percent_complete, 0) }}%</div>
                        </div>
                    </div>

                    {{-- Additional Details --}}
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="text-xs text-base-content/60 uppercase tracking-wide">Contractor</label>
                            <p>{{ $this->selectedCircuit->contractor ?: 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-base-content/60 uppercase tracking-wide">Cycle Type</label>
                            <p>{{ $this->selectedCircuit->cycle_type ?: 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-base-content/60 uppercase tracking-wide">Start Date</label>
                            <p>{{ $this->selectedCircuit->start_date?->format('M j, Y') ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-base-content/60 uppercase tracking-wide">Last API Update</label>
                            <p>{{ $this->selectedCircuit->api_modified_at?->diffForHumans() ?? 'Never' }}</p>
                        </div>
                    </div>
                </div>

                <div class="modal-action">
                    <a
                        href="{{ route('admin.data.circuits') }}?search={{ $this->selectedCircuit->work_order }}"
                        class="btn btn-outline btn-sm"
                    >
                        <x-heroicon-o-magnifying-glass class="size-4" />
                        View in Browser
                    </a>
                    <button wire:click="closeDetailModal" class="btn btn-primary btn-sm">
                        Close
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button wire:click="closeDetailModal">close</button>
            </form>
        </dialog>
    @endif

    {{-- Loading Overlay --}}
    <div
        wire:loading.flex
        class="fixed inset-0 z-40 items-center justify-center bg-base-100/50"
    >
        <span class="loading loading-spinner loading-lg text-primary"></span>
    </div>
</div>
