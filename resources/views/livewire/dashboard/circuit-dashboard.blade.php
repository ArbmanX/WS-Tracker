<div class="flex h-full flex-col gap-4 p-4" x-data="{ theme: 'ppl-light' }">
    {{-- Header Section --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Circuit Dashboard</h1>
            <p class="text-sm text-base-content/70">Vegetation maintenance workflow management</p>
        </div>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Theme Selector --}}
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    Theme
                </div>
                <ul tabindex="0" class="menu dropdown-content z-[1] w-52 rounded-box bg-base-100 p-2 shadow-lg">
                    <li><button onclick="document.documentElement.setAttribute('data-theme', 'light')">Light</button></li>
                    <li><button onclick="document.documentElement.setAttribute('data-theme', 'dark')">Dark</button></li>
                    <li><button onclick="document.documentElement.setAttribute('data-theme', 'ppl-light')">PPL Light</button></li>
                    <li><button onclick="document.documentElement.setAttribute('data-theme', 'ppl-dark')">PPL Dark</button></li>
                </ul>
            </div>

            {{-- Sync Status (Mock) --}}
            <div class="badge badge-success gap-1">
                <span class="h-2 w-2 animate-pulse rounded-full bg-success-content"></span>
                Last sync: 2 min ago
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body p-4">
            <div class="flex flex-wrap items-end gap-4">
                {{-- Region Filter --}}
                <div class="form-control w-full max-w-xs">
                    <label class="label py-1">
                        <span class="label-text text-xs font-medium">Region</span>
                    </label>
                    <select wire:model.live="regionFilter" class="select select-bordered select-sm">
                        <option value="">All Regions</option>
                        @foreach($this->regions as $region)
                            <option value="{{ $region['name'] }}">
                                {{ $region['name'] }} ({{ $region['circuits_count'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Planner Filter --}}
                <div class="form-control w-full max-w-xs">
                    <label class="label py-1">
                        <span class="label-text text-xs font-medium">Planner</span>
                    </label>
                    <select wire:model.live="plannerFilter" class="select select-bordered select-sm">
                        <option value="">All Planners</option>
                        @foreach($this->planners as $planner)
                            <option value="{{ $planner['name'] }}">{{ $planner['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="form-control w-full max-w-xs">
                    <label class="label py-1">
                        <span class="label-text text-xs font-medium">Date Range</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="date" wire:model.live="dateFrom" class="input input-bordered input-sm flex-1" placeholder="From">
                        <input type="date" wire:model.live="dateTo" class="input input-bordered input-sm flex-1" placeholder="To">
                    </div>
                </div>

                {{-- Clear & Toggle Buttons --}}
                <div class="flex gap-2">
                    <button wire:click="clearFilters" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                    <label class="label cursor-pointer gap-2">
                        <input type="checkbox" wire:model.live="showClosedCircuits" class="checkbox checkbox-sm checkbox-primary">
                        <span class="label-text text-sm">Show Closed</span>
                    </label>
                </div>
            </div>

            {{-- Active Filters Display --}}
            @if($regionFilter || $plannerFilter || $dateFrom || $dateTo)
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-base-content/60">Active filters:</span>
                    @if($regionFilter)
                        <span class="badge badge-primary badge-sm gap-1">
                            {{ $regionFilter }}
                            <button wire:click="$set('regionFilter', null)" class="hover:text-primary-content">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </span>
                    @endif
                    @if($plannerFilter)
                        <span class="badge badge-secondary badge-sm gap-1">
                            {{ $plannerFilter }}
                            <button wire:click="$set('plannerFilter', null)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Stats Panel --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
        {{-- Total Circuits --}}
        <div class="stats shadow-sm border border-base-300 bg-base-100">
            <div class="stat p-3">
                <div class="stat-figure text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div class="stat-title text-xs">Circuits</div>
                <div class="stat-value text-xl text-primary">{{ $this->stats['total_circuits'] }}</div>
            </div>
        </div>

        {{-- Miles Planned --}}
        <div class="stats shadow-sm border border-base-300 bg-base-100">
            <div class="stat p-3">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                <div class="stat-title text-xs">Miles Planned</div>
                <div class="stat-value text-xl text-secondary">{{ $this->stats['total_miles_planned'] }}</div>
                <div class="stat-desc text-xs">of {{ $this->stats['total_miles'] }} mi</div>
            </div>
        </div>

        {{-- Avg Completion --}}
        <div class="stats shadow-sm border border-base-300 bg-base-100">
            <div class="stat p-3">
                <div class="stat-figure text-accent">
                    <div class="radial-progress text-accent text-xs" style="--value:{{ $this->stats['avg_completion'] }}; --size:2.5rem; --thickness:4px;">
                        {{ $this->stats['avg_completion'] }}%
                    </div>
                </div>
                <div class="stat-title text-xs">Avg Progress</div>
                <div class="stat-value text-xl text-accent">{{ $this->stats['avg_completion'] }}%</div>
            </div>
        </div>

        {{-- Active Planners --}}
        <div class="stats shadow-sm border border-base-300 bg-base-100">
            <div class="stat p-3">
                <div class="stat-figure text-info">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="stat-title text-xs">Planners</div>
                <div class="stat-value text-xl text-info">{{ $this->stats['active_planners'] }}</div>
            </div>
        </div>

        {{-- Permissions Summary --}}
        <div class="stats shadow-sm border border-base-300 bg-base-100">
            <div class="stat p-3">
                <div class="stat-title text-xs">Permissions</div>
                <div class="flex gap-1 mt-1">
                    <div class="tooltip" data-tip="Approved">
                        <span class="badge badge-success badge-sm">{{ $this->stats['permission_summary']['approved'] }}</span>
                    </div>
                    <div class="tooltip" data-tip="Pending">
                        <span class="badge badge-warning badge-sm">{{ $this->stats['permission_summary']['pending'] }}</span>
                    </div>
                    <div class="tooltip" data-tip="Refused">
                        <span class="badge badge-error badge-sm">{{ $this->stats['permission_summary']['refused'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div class="flex-1 overflow-hidden">
        <div class="flex h-full gap-4 overflow-x-auto pb-4" wire:sortable-group="handleCircuitMoved">
            @php
                $stages = [
                    'active' => ['label' => 'Active', 'color' => 'info', 'bg' => 'bg-info/10', 'border' => 'border-info/30'],
                    'pending_permissions' => ['label' => 'Pending Permissions', 'color' => 'warning', 'bg' => 'bg-warning/10', 'border' => 'border-warning/30'],
                    'qc' => ['label' => 'QC Review', 'color' => 'secondary', 'bg' => 'bg-secondary/10', 'border' => 'border-secondary/30'],
                    'rework' => ['label' => 'Rework', 'color' => 'error', 'bg' => 'bg-error/10', 'border' => 'border-error/30'],
                    'closed' => ['label' => 'Closed', 'color' => 'success', 'bg' => 'bg-success/10', 'border' => 'border-success/30'],
                ];
            @endphp

            @foreach($stages as $stage => $config)
                @if($stage !== 'closed' || $showClosedCircuits)
                    <div class="flex h-full w-80 flex-shrink-0 flex-col">
                        {{-- Column Header --}}
                        <div class="flex items-center justify-between rounded-t-lg p-3 {{ $config['bg'] }} border {{ $config['border'] }}">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-{{ $config['color'] }}"></span>
                                <h3 class="text-sm font-semibold text-base-content">{{ $config['label'] }}</h3>
                            </div>
                            <span class="badge badge-neutral badge-sm">
                                {{ $this->circuits->get($stage, collect())->count() }}
                            </span>
                        </div>

                        {{-- Column Body --}}
                        <div class="flex-1 space-y-2 overflow-y-auto rounded-b-lg border border-t-0 border-base-300 bg-base-200/30 p-2"
                             style="min-height: 400px;"
                             wire:sortable-group.item-group="{{ $stage }}">
                            @forelse($this->circuits->get($stage, collect()) as $circuit)
                                <div wire:key="circuit-{{ $circuit['id'] }}"
                                     wire:sortable-group.item="{{ $circuit['id'] }}"
                                     class="cursor-grab active:cursor-grabbing">
                                    {{-- Circuit Card --}}
                                    <div class="card border border-base-300 bg-base-100 shadow-sm transition-all hover:border-primary/50 hover:shadow-md">
                                        <div class="card-body gap-2 p-3">
                                            {{-- Header --}}
                                            <div class="flex items-start justify-between">
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="truncate font-mono text-sm font-semibold">
                                                        {{ $circuit['work_order'] }}{{ $circuit['extension'] }}
                                                    </h4>
                                                    <p class="truncate text-xs text-base-content/70" title="{{ $circuit['title'] }}">
                                                        {{ $circuit['title'] }}
                                                    </p>
                                                </div>
                                                <span class="badge badge-sm flex-shrink-0
                                                    @if($circuit['region']['code'] === 'CTR') badge-primary
                                                    @elseif($circuit['region']['code'] === 'LAN') badge-secondary
                                                    @elseif($circuit['region']['code'] === 'LEH') badge-accent
                                                    @else badge-neutral
                                                    @endif">
                                                    {{ $circuit['region']['name'] }}
                                                </span>
                                            </div>

                                            {{-- Progress Bar --}}
                                            <div>
                                                <div class="mb-1 flex justify-between text-xs">
                                                    <span class="text-base-content/60">Progress</span>
                                                    <span class="font-medium
                                                        @if($circuit['percent_complete'] >= 90) text-success
                                                        @elseif($circuit['percent_complete'] >= 50) text-primary
                                                        @elseif($circuit['percent_complete'] >= 25) text-warning
                                                        @else text-error
                                                        @endif">
                                                        {{ $circuit['percent_complete'] }}%
                                                    </span>
                                                </div>
                                                <progress
                                                    class="progress h-2 w-full
                                                        @if($circuit['percent_complete'] >= 90) progress-success
                                                        @elseif($circuit['percent_complete'] >= 50) progress-primary
                                                        @elseif($circuit['percent_complete'] >= 25) progress-warning
                                                        @else progress-error
                                                        @endif"
                                                    value="{{ $circuit['percent_complete'] }}"
                                                    max="100">
                                                </progress>
                                            </div>

                                            {{-- Stats --}}
                                            <div class="flex justify-between text-xs text-base-content/60">
                                                <span>{{ number_format($circuit['miles_planned'], 1) }} / {{ number_format($circuit['total_miles'], 1) }} mi</span>
                                                <span>{{ $circuit['api_modified_date'] }}</span>
                                            </div>

                                            {{-- Aggregate Metrics --}}
                                            <div class="flex flex-wrap gap-1">
                                                <span class="badge badge-outline badge-xs">{{ $circuit['aggregate']['total_units'] }} units</span>
                                                @if($circuit['aggregate']['total_linear_ft'] > 0)
                                                    <span class="badge badge-outline badge-xs">{{ number_format($circuit['aggregate']['total_linear_ft']) }} ft</span>
                                                @endif
                                                @if($circuit['aggregate']['total_acres'] > 0)
                                                    <span class="badge badge-outline badge-xs">{{ number_format($circuit['aggregate']['total_acres'], 1) }} ac</span>
                                                @endif
                                            </div>

                                            {{-- Planners --}}
                                            <div class="flex items-center justify-between border-t border-base-200 pt-2">
                                                <div class="avatar-group -space-x-2">
                                                    @foreach(array_slice($circuit['planners'], 0, 3) as $planner)
                                                        <div class="avatar placeholder">
                                                            <div class="w-6 rounded-full bg-neutral text-neutral-content">
                                                                <span class="text-[10px]">{{ $planner['initials'] }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    @if(count($circuit['planners']) > 3)
                                                        <div class="avatar placeholder">
                                                            <div class="w-6 rounded-full bg-neutral/50 text-neutral-content">
                                                                <span class="text-[10px]">+{{ count($circuit['planners']) - 3 }}</span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Permission Status Dots --}}
                                                <div class="flex gap-0.5">
                                                    @if($circuit['aggregate']['units_approved'] > 0)
                                                        <div class="tooltip" data-tip="{{ $circuit['aggregate']['units_approved'] }} approved">
                                                            <span class="badge badge-success badge-xs"></span>
                                                        </div>
                                                    @endif
                                                    @if($circuit['aggregate']['units_pending'] > 0)
                                                        <div class="tooltip" data-tip="{{ $circuit['aggregate']['units_pending'] }} pending">
                                                            <span class="badge badge-warning badge-xs"></span>
                                                        </div>
                                                    @endif
                                                    @if($circuit['aggregate']['units_refused'] > 0)
                                                        <div class="tooltip" data-tip="{{ $circuit['aggregate']['units_refused'] }} refused">
                                                            <span class="badge badge-error badge-xs"></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex h-32 flex-col items-center justify-center rounded-lg border-2 border-dashed border-base-300 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-8 w-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="text-xs text-base-content/50">No circuits</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Charts Section (Collapsible) --}}
    <div class="collapse collapse-arrow border border-base-300 bg-base-100">
        <input type="checkbox" checked />
        <div class="collapse-title text-sm font-medium">
            Analytics & Charts
        </div>
        <div class="collapse-content">
            <div class="grid grid-cols-1 gap-4 pt-2 lg:grid-cols-3">
                {{-- Miles by Region Chart --}}
                <div class="card border border-base-300 bg-base-100">
                    <div class="card-body p-4">
                        <h3 class="card-title text-sm">Miles by Region</h3>
                        <div id="milesChart" class="h-48" wire:ignore></div>
                    </div>
                </div>

                {{-- Permission Status Chart --}}
                <div class="card border border-base-300 bg-base-100">
                    <div class="card-body p-4">
                        <h3 class="card-title text-sm">Permission Status</h3>
                        <div id="permissionChart" class="h-48" wire:ignore></div>
                    </div>
                </div>

                {{-- Workflow Distribution Chart --}}
                <div class="card border border-base-300 bg-base-100">
                    <div class="card-body p-4">
                        <h3 class="card-title text-sm">Workflow Distribution</h3>
                        <div id="workflowChart" class="h-48" wire:ignore></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    // Initialize charts when component is ready
    document.addEventListener('livewire:navigated', initCharts);
    initCharts();

    function initCharts() {
        // Miles by Region Chart
        if (document.getElementById('milesChart') && !document.getElementById('milesChart').hasChildNodes()) {
            const milesOptions = {
                series: [14.93, 33.46, 16.1, 15.5],
                labels: ['Lehigh', 'Lancaster', 'Central', 'Harrisburg'],
                chart: {
                    type: 'donut',
                    height: 180,
                },
                colors: ['#E27434', '#28317E', '#1882C5', '#00598D'],
                legend: {
                    position: 'bottom',
                    fontSize: '11px',
                },
                dataLabels: {
                    enabled: false,
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Miles',
                                    fontSize: '11px',
                                    formatter: () => '79.99'
                                }
                            }
                        }
                    }
                }
            };
            new ApexCharts(document.getElementById('milesChart'), milesOptions).render();
        }

        // Permission Status Chart
        if (document.getElementById('permissionChart') && !document.getElementById('permissionChart').hasChildNodes()) {
            const permissionOptions = {
                series: [1444, 368, 64],
                labels: ['Approved', 'Pending', 'Refused'],
                chart: {
                    type: 'pie',
                    height: 180,
                },
                colors: ['#22c55e', '#eab308', '#ef4444'],
                legend: {
                    position: 'bottom',
                    fontSize: '11px',
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val.toFixed(0) + '%';
                    }
                }
            };
            new ApexCharts(document.getElementById('permissionChart'), permissionOptions).render();
        }

        // Workflow Distribution Chart
        if (document.getElementById('workflowChart') && !document.getElementById('workflowChart').hasChildNodes()) {
            const workflowOptions = {
                series: [{
                    name: 'Circuits',
                    data: [4, 2, 2, 1]
                }],
                chart: {
                    type: 'bar',
                    height: 180,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        distributed: true,
                        barHeight: '70%',
                    }
                },
                colors: ['#3b82f6', '#eab308', '#28317E', '#ef4444'],
                xaxis: {
                    categories: ['Active', 'Pending', 'QC', 'Rework'],
                },
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '11px'
                    }
                }
            };
            new ApexCharts(document.getElementById('workflowChart'), workflowOptions).render();
        }
    }
</script>
@endscript
