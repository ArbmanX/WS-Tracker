<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Planner Analytics</h1>
            <p class="text-base-content/60">Performance metrics and planning progress</p>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                class="btn btn-ghost btn-sm btn-square"
                wire:click="$refresh"
                wire:loading.attr="disabled"
            >
                <x-heroicon-o-arrow-path class="size-4" wire:loading.class="animate-spin" />
            </button>
        </div>
    </div>

    {{-- Global Analytics Settings Indicator --}}
    @php $settings = $this->globalSettings; @endphp
    <div class="alert alert-info py-2">
        <x-heroicon-o-funnel class="size-5" />
        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
            <span>
                <span class="font-medium">Scope Year:</span>
                <span class="font-mono">{{ $settings->scope_year }}</span>
            </span>
            <span>
                <span class="font-medium">Cycle Types:</span>
                @if($settings->selected_cycle_types)
                    <span class="font-mono">{{ count($settings->selected_cycle_types) }} selected</span>
                @else
                    <span>All</span>
                @endif
            </span>
            <span>
                <span class="font-medium">Contractors:</span>
                @if($settings->selected_contractors)
                    <span class="font-mono">{{ count($settings->selected_contractors) }} selected</span>
                @else
                    <span>All</span>
                @endif
            </span>
            @can('update', App\Models\AnalyticsSetting::class)
                <a href="{{ route('admin.analytics-settings') }}" class="link link-hover font-medium">
                    <x-heroicon-o-cog-6-tooth class="size-4 inline" />
                    Settings
                </a>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body py-4">
            <div class="flex flex-wrap gap-4 items-end">
                {{-- Date Range --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Period</span>
                    </label>
                    <select wire:model.live="dateRange" class="select select-bordered select-sm">
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="last_week">Last Week</option>
                        <option value="this_month">This Month</option>
                        <option value="last_30_days">Last 30 Days</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                {{-- Custom Date Range --}}
                @if($dateRange === 'custom')
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Start Date</span>
                        </label>
                        <input
                            type="date"
                            wire:model.live="startDate"
                            class="input input-bordered input-sm"
                        />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">End Date</span>
                        </label>
                        <input
                            type="date"
                            wire:model.live="endDate"
                            class="input input-bordered input-sm"
                        />
                    </div>
                @endif

                {{-- Region Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Region</span>
                    </label>
                    <select wire:model.live="regionId" class="select select-bordered select-sm">
                        <option value="">All Regions</option>
                        @foreach($this->regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Planner Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Planner</span>
                    </label>
                    <select wire:model.live="plannerId" class="select select-bordered select-sm">
                        <option value="">All Planners</option>
                        @foreach($this->planners as $planner)
                            <option value="{{ $planner->id }}">{{ $planner->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear Filters --}}
                @if($regionId || $plannerId || $dateRange !== 'this_week')
                    <button
                        type="button"
                        wire:click="clearAllFilters"
                        class="btn btn-ghost btn-sm"
                    >
                        <x-heroicon-o-x-mark class="size-4" />
                        Clear
                    </button>
                @endif
            </div>

            {{-- Circuit Filters (affects circuit breakdown) --}}
            @if($plannerId)
                <div class="divider my-2"></div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-base-content/60 uppercase font-semibold">Circuit Filters</span>
                    <x-filters.circuit-filters
                        :statusFilter="$statusFilter"
                        :cycleTypeFilter="$cycleTypeFilter"
                        :availableStatuses="$this->availableStatuses"
                        :availableCycleTypes="$this->availableCycleTypes"
                    />
                </div>
            @endif
        </div>
    </div>

    {{-- Summary Stats --}}
    @php $stats = $this->summaryStats; @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-ui.stat-card
            label="Active Planners"
            :value="number_format($stats['active_planners'])"
            icon="users"
            color="primary"
            size="sm"
        />
        <x-ui.stat-card
            label="Total Units"
            :value="number_format($stats['total_units'])"
            icon="clipboard-document-list"
            color="secondary"
            size="sm"
        />
        <x-ui.stat-card
            label="Miles Planned"
            :value="number_format($stats['miles_planned'], 1)"
            suffix="mi"
            icon="map"
            size="sm"
        />
        <x-ui.stat-card
            label="Approval Rate"
            :value="number_format($stats['approval_rate'], 1)"
            suffix="%"
            icon="check-badge"
            :color="$stats['approval_rate'] >= 75 ? 'success' : ($stats['approval_rate'] >= 50 ? 'warning' : 'error')"
            size="sm"
        />
    </div>

    {{-- Weekly Target Progress Card --}}
    @php
        $targetSummary = $this->weeklyTargetSummary;
        $targetMetrics = $this->weeklyTargetMetrics;
    @endphp
    @if($targetMetrics->isNotEmpty())
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-base">
                        <x-heroicon-o-flag class="size-5 text-primary" />
                        Weekly Miles Target
                        <span class="badge badge-primary badge-sm">{{ $targetSummary['target'] }} mi/week</span>
                    </h2>
                    <div class="flex items-center gap-4 text-sm">
                        <div class="flex items-center gap-1">
                            <span class="badge badge-success badge-xs"></span>
                            <span class="font-medium">{{ $targetSummary['met_target'] }}</span>
                            <span class="text-base-content/60">met target</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="badge badge-warning badge-xs"></span>
                            <span class="font-medium">{{ $targetSummary['below_target'] }}</span>
                            <span class="text-base-content/60">below target</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-4">
                    @foreach($targetMetrics as $planner)
                        <div
                            class="p-3 rounded-lg border {{ $planner['met_target'] ? 'border-success/30 bg-success/5' : 'border-warning/30 bg-warning/5' }}"
                            wire:key="target-{{ $planner['user_id'] }}"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-sm">{{ $planner['name'] }}</span>
                                @if($planner['met_target'])
                                    <span class="badge badge-success badge-xs gap-1">
                                        <x-heroicon-s-check class="size-3" />
                                        Target Met
                                    </span>
                                @else
                                    <span class="badge badge-warning badge-xs">
                                        {{ $planner['miles_remaining'] }} mi to go
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <progress
                                        class="progress {{ $planner['met_target'] ? 'progress-success' : 'progress-warning' }} h-3"
                                        value="{{ min(100, $planner['target_percentage']) }}"
                                        max="100"
                                    ></progress>
                                </div>
                                <span class="text-sm font-mono font-semibold {{ $planner['met_target'] ? 'text-success' : 'text-warning' }}">
                                    {{ number_format($planner['miles_delta'], 1) }} mi
                                </span>
                            </div>

                            <div class="flex justify-between text-xs text-base-content/50 mt-1">
                                <span>Start: {{ number_format($planner['miles_start'], 1) }} mi</span>
                                <span>End: {{ number_format($planner['miles_end'], 1) }} mi</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($targetMetrics->isEmpty())
                    <div class="flex items-center justify-center py-8 text-base-content/40">
                        <div class="text-center">
                            <x-heroicon-o-chart-bar class="size-12 mx-auto mb-2" />
                            <p>No weekly target data available yet.</p>
                            <p class="text-sm">Data will appear as daily snapshots are collected.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Full Permission Status Breakdown (All 6 Statuses) --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">
                    <x-heroicon-o-shield-check class="size-5 text-primary" />
                    Permission Status
                    <span class="badge badge-ghost badge-sm">All Statuses</span>
                </h2>

                @php $fullPermission = $this->fullPermissionBreakdown; @endphp
                @if(!empty($fullPermission))
                    @php
                        $totalUnits = collect($fullPermission)->sum('count');
                        $chartColors = collect($fullPermission)->pluck('color')->values()->toArray();
                        $chartLabels = collect($fullPermission)->pluck('name')->values()->toArray();
                        $chartSeries = collect($fullPermission)->pluck('count')->values()->toArray();
                    @endphp

                    @if($totalUnits > 0)
                        <div wire:key="permission-chart-{{ md5(json_encode($chartSeries)) }}">
                            <x-apex-chart
                                chart-id="full-permission-donut"
                                type="donut"
                                :height="280"
                                :series="$chartSeries"
                                :options="[
                                    'labels' => $chartLabels,
                                    'colors' => $chartColors,
                                    'legend' => [
                                        'position' => 'bottom',
                                        'labels' => ['useSeriesColors' => true]
                                    ],
                                    'plotOptions' => [
                                        'pie' => [
                                            'donut' => [
                                                'size' => '65%',
                                                'labels' => [
                                                    'show' => true,
                                                    'total' => [
                                                        'show' => true,
                                                        'label' => 'Total Units',
                                                        'fontSize' => '14px',
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],
                                    'dataLabels' => ['enabled' => false],
                                ]"
                            />
                        </div>

                        {{-- Detailed breakdown with counts and percentages --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4">
                            @foreach($fullPermission as $code => $status)
                                <div class="flex items-center gap-2 text-sm">
                                    <span
                                        class="w-3 h-3 rounded-full shrink-0"
                                        style="background-color: {{ $status['color'] }}"
                                    ></span>
                                    <span class="truncate" title="{{ $status['name'] }}">{{ $status['name'] }}</span>
                                    <span class="font-mono text-base-content/70">{{ number_format($status['count']) }}</span>
                                    <span class="text-xs text-base-content/50">({{ $status['percentage'] }}%)</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center justify-center h-64 text-base-content/40">
                            <div class="text-center">
                                <x-heroicon-o-chart-pie class="size-12 mx-auto mb-2" />
                                <p>No permission data available</p>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="flex items-center justify-center h-64 text-base-content/40">
                        <div class="text-center">
                            <x-heroicon-o-chart-pie class="size-12 mx-auto mb-2" />
                            <p>No permission data available</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Planning Progression Chart --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">Planning Progression</h2>
                @php $progression = $this->progressionData; @endphp
                @if(!empty($progression['dates']))
                    <div wire:key="progression-chart-{{ md5(json_encode($progression)) }}">
                        <x-apex-chart
                            chart-id="progression-chart"
                            type="area"
                            :height="280"
                            :series="[
                                ['name' => 'Units', 'data' => $progression['units']],
                                ['name' => 'Miles', 'data' => $progression['miles']]
                            ]"
                            :options="[
                                'xaxis' => [
                                    'categories' => $progression['dates'],
                                    'labels' => ['rotate' => -45]
                                ],
                                'yaxis' => [
                                    ['title' => ['text' => 'Units'], 'seriesName' => 'Units'],
                                    ['opposite' => true, 'title' => ['text' => 'Miles'], 'seriesName' => 'Miles']
                                ],
                                'stroke' => ['curve' => 'smooth', 'width' => 2],
                                'fill' => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.4, 'opacityTo' => 0.1]],
                                'legend' => ['position' => 'top'],
                                'tooltip' => ['shared' => true],
                            ]"
                        />
                    </div>
                @else
                    <div class="flex items-center justify-center h-64 text-base-content/40">
                        <div class="text-center">
                            <x-heroicon-o-chart-bar class="size-12 mx-auto mb-2" />
                            <p>No progression data available</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Unit Type Breakdown --}}
    @php $unitTypes = $this->unitTypeBreakdown; @endphp
    @if(!empty($unitTypes))
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">
                    <x-heroicon-o-squares-2x2 class="size-5 text-secondary" />
                    Unit Type Breakdown
                    <span class="badge badge-ghost badge-sm">By Category</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                    @foreach($unitTypes as $key => $category)
                        @php
                            $bgColor = match($key) {
                                'trim_line' => 'bg-info/10 border-info/30',
                                'brush_area' => 'bg-success/10 border-success/30',
                                'tree_removal' => 'bg-warning/10 border-warning/30',
                                default => 'bg-base-200 border-base-300',
                            };
                            $iconColor = match($key) {
                                'trim_line' => 'text-info',
                                'brush_area' => 'text-success',
                                'tree_removal' => 'text-warning',
                                default => 'text-base-content',
                            };
                            $icon = match($key) {
                                'trim_line' => 'o-scissors',
                                'brush_area' => 'o-beaker',
                                'tree_removal' => 'o-fire',
                                default => 'o-cube',
                            };
                        @endphp
                        <div class="p-4 rounded-lg border {{ $bgColor }}">
                            <div class="flex items-center gap-2 mb-3">
                                <x-dynamic-component :component="'heroicon-' . $icon" class="size-5 {{ $iconColor }}" />
                                <h3 class="font-semibold">{{ $category['label'] }}</h3>
                            </div>

                            <div class="flex justify-between items-baseline mb-4">
                                <div>
                                    <span class="text-3xl font-bold">{{ number_format($category['total']) }}</span>
                                    <span class="text-sm text-base-content/60 ml-1">{{ $category['unit_label'] }}</span>
                                </div>
                                <div class="text-sm text-base-content/60">
                                    {{ number_format($category['count']) }} units
                                </div>
                            </div>

                            @if(!empty($category['types']))
                                <div class="divider my-2 text-xs">By Type</div>
                                <div class="space-y-1 max-h-32 overflow-y-auto">
                                    @foreach($category['types'] as $code => $type)
                                        <div class="flex justify-between text-sm">
                                            <span class="truncate" title="{{ $type['name'] }}">{{ $code }}</span>
                                            <span class="font-mono text-base-content/70">{{ number_format($type['count']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-sm text-base-content/40 text-center py-2">
                                    No units in this category
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Planner Activity Details (when planner selected) --}}
    @if($plannerId)
        @php $activity = $this->activityTimestamps; @endphp
        @if($activity['planner_activity']->isNotEmpty())
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title text-base">
                        <x-heroicon-o-clock class="size-5 text-accent" />
                        Activity Timeline
                        @if($this->selectedPlanner)
                            <span class="text-base-content/60 font-normal">for {{ $this->selectedPlanner->name }}</span>
                        @endif
                    </h2>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                        {{-- Last Snapshot --}}
                        <div class="stat bg-base-200/50 rounded-lg">
                            <div class="stat-title text-xs">Last Data Sync</div>
                            <div class="stat-value text-base">
                                @if($activity['planner_activity']['last_snapshot'])
                                    {{ $activity['planner_activity']['last_snapshot']->diffForHumans() }}
                                @else
                                    <span class="text-base-content/40">No data</span>
                                @endif
                            </div>
                            @if($activity['planner_activity']['last_snapshot'])
                                <div class="stat-desc">{{ $activity['planner_activity']['last_snapshot']->format('M d, Y H:i') }}</div>
                            @endif
                        </div>

                        {{-- Circuit Status Counts --}}
                        <div class="stat bg-base-200/50 rounded-lg">
                            <div class="stat-title text-xs">Circuit Status</div>
                            <div class="stat-value text-base flex gap-2">
                                <span class="badge badge-primary badge-sm" title="Active">
                                    {{ $activity['planner_activity']['active_circuits'] ?? 0 }}
                                </span>
                                <span class="badge badge-warning badge-sm" title="{{ \App\Support\WorkStudioStatus::labelFor(\App\Support\WorkStudioStatus::QC) }}">
                                    {{ $activity['planner_activity']['qc_circuits'] ?? 0 }}
                                </span>
                                <span class="badge badge-success badge-sm" title="Closed">
                                    {{ $activity['planner_activity']['closed_circuits'] ?? 0 }}
                                </span>
                            </div>
                            <div class="stat-desc">Active / Quality Control / Closed</div>
                        </div>

                        {{-- Oldest In-Progress --}}
                        <div class="stat bg-base-200/50 rounded-lg">
                            <div class="stat-title text-xs">Oldest In Progress</div>
                            <div class="stat-value text-base">
                                @if($activity['planner_activity']['oldest_in_progress'])
                                    {{ $activity['planner_activity']['oldest_in_progress']->diffForHumans() }}
                                @else
                                    <span class="text-base-content/40">None</span>
                                @endif
                            </div>
                            @if($activity['planner_activity']['oldest_in_progress_wo'])
                                <div class="stat-desc font-mono">{{ $activity['planner_activity']['oldest_in_progress_wo'] }}</div>
                            @endif
                        </div>

                        {{-- Total Circuits --}}
                        <div class="stat bg-base-200/50 rounded-lg">
                            <div class="stat-title text-xs">Total Assigned</div>
                            <div class="stat-value text-base">
                                {{ ($activity['planner_activity']['active_circuits'] ?? 0) + ($activity['planner_activity']['qc_circuits'] ?? 0) + ($activity['planner_activity']['closed_circuits'] ?? 0) }}
                            </div>
                            <div class="stat-desc">circuits</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Planner Leaderboard --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">
                Planner Performance
                @if($plannerId && $this->selectedPlanner)
                    <span class="badge badge-primary">{{ $this->selectedPlanner->name }}</span>
                    <button
                        wire:click="clearPlannerFilter"
                        class="btn btn-ghost btn-xs"
                    >
                        <x-heroicon-o-x-mark class="size-4" />
                    </button>
                @endif
            </h2>

            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Planner</th>
                            <th class="text-right">Circuits</th>
                            <th class="text-right">Units</th>
                            <th class="text-right">
                                <span class="tooltip tooltip-left" data-tip="Total units assessed ÷ days worked (5-day work week assumed)">
                                    Units/Day
                                </span>
                            </th>
                            <th class="text-right">Miles</th>
                            <th class="text-right">
                                <span class="tooltip tooltip-left" data-tip="Miles planned this week (target: 6.5 mi)">
                                    Weekly Δ
                                </span>
                            </th>
                            <th class="text-right">Approval</th>
                            <th>Status Breakdown</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->plannerMetrics as $planner)
                            <tr
                                class="hover cursor-pointer"
                                wire:key="planner-{{ $planner['user_id'] }}"
                                wire:click="filterByPlanner({{ $planner['user_id'] }})"
                            >
                                <td class="font-mono text-base-content/60">{{ $planner['rank'] }}</td>
                                <td>
                                    <div class="font-medium">{{ $planner['name'] }}</div>
                                    @if($planner['ws_username'])
                                        <div class="text-xs text-base-content/50 font-mono">{{ $planner['ws_username'] }}</div>
                                    @endif
                                </td>
                                <td class="text-right font-mono">{{ number_format($planner['circuits']) }}</td>
                                <td class="text-right font-mono font-medium">{{ number_format($planner['total_units']) }}</td>
                                <td class="text-right font-mono">{{ number_format($planner['avg_daily'], 1) }}</td>
                                <td class="text-right font-mono">{{ number_format($planner['miles_planned'], 1) }}</td>
                                <td class="text-right">
                                    @php
                                        $deltaClass = $planner['met_target'] ? 'text-success' : 'text-warning';
                                    @endphp
                                    <span class="font-mono {{ $deltaClass }}">
                                        {{ number_format($planner['miles_delta'], 1) }}
                                        @if($planner['met_target'])
                                            <x-heroicon-s-check-circle class="size-4 inline-block" />
                                        @endif
                                    </span>
                                </td>
                                <td class="text-right">
                                    @php
                                        $rate = $planner['approval_rate'];
                                        $badgeClass = $rate >= 75 ? 'badge-success' : ($rate >= 50 ? 'badge-warning' : 'badge-error');
                                    @endphp
                                    <span class="badge {{ $badgeClass }} badge-sm">{{ number_format($rate, 1) }}%</span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <span class="badge badge-success badge-xs" title="Approved">{{ $planner['units_approved'] }}</span>
                                        <span class="badge badge-warning badge-xs" title="Pending">{{ $planner['units_pending'] }}</span>
                                        <span class="badge badge-error badge-xs" title="Refused">{{ $planner['units_refused'] }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-12 text-base-content/40">
                                    <x-heroicon-o-user-group class="size-12 mx-auto mb-2" />
                                    <p>No planner data available for this period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->plannerMetrics->isNotEmpty())
                <div class="text-sm text-base-content/60 mt-2">
                    Showing {{ $this->plannerMetrics->count() }} planners
                </div>
            @endif
        </div>
    </div>

    {{-- Circuit Breakdown (when planner selected) --}}
    @if($plannerId && $this->circuitBreakdown->isNotEmpty())
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">
                    Circuit Breakdown
                    @if($this->selectedPlanner)
                        <span class="text-base-content/60 font-normal">for {{ $this->selectedPlanner->name }}</span>
                    @endif
                </h2>

                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Work Order</th>
                                <th>Circuit Name</th>
                                <th>Region</th>
                                <th>Status</th>
                                <th class="text-right">Total Units</th>
                                <th class="text-right">Total Miles</th>
                                <th class="text-right">Planned Miles</th>
                                <th class="text-right">Approval</th>
                                <th>Last Modified</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->circuitBreakdown as $circuit)
                                <tr wire:key="circuit-{{ $circuit['id'] }}">
                                    <td class="font-mono">{{ $circuit['work_order'] }}</td>
                                    <td class="max-w-xs truncate" title="{{ $circuit['title'] }}">{{ $circuit['title'] }}</td>
                                    <td>{{ $circuit['region'] }}</td>
                                    <td>
                                        <span class="badge {{ $circuit['status_badge'] }} badge-sm">{{ $circuit['status_label'] }}</span>
                                    </td>
                                    <td class="text-right font-mono">{{ number_format($circuit['total_units']) }}</td>
                                    <td class="text-right font-mono">{{ number_format($circuit['total_miles'], 1) }}</td>
                                    <td class="text-right font-mono">{{ number_format($circuit['miles_planned'], 1) }}</td>
                                    <td class="text-right">
                                        @php
                                            $rate = $circuit['approval_rate'];
                                            $badgeClass = $rate >= 75 ? 'badge-success' : ($rate >= 50 ? 'badge-warning' : 'badge-error');
                                        @endphp
                                        <span class="badge {{ $badgeClass }} badge-sm">{{ number_format($rate, 1) }}%</span>
                                    </td>
                                    <td class="text-sm text-base-content/70">
                                        @if($circuit['last_modified'])
                                            <span class="tooltip" data-tip="{{ $circuit['last_modified']->format('M d, Y') }}">
                                                {{ $circuit['last_modified']->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-base-content/40">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="text-sm text-base-content/60 mt-2">
                    {{ $this->circuitBreakdown->count() }} circuits assigned
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div
        wire:loading.flex
        wire:target="dateRange, regionId, plannerId, startDate, endDate, filterByPlanner, clearPlannerFilter, clearAllFilters"
        class="fixed inset-0 z-40 items-center justify-center bg-base-100/50"
    >
        <span class="loading loading-spinner loading-lg text-primary"></span>
    </div>
</div>
