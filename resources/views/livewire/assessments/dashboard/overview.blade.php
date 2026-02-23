<div class="space-y-6">
    @php
        $summary = $this->summaryStats;
        $overallPercent = $summary['overall_percent'] ?? 0;
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Regional Overview</h1>
            <p class="text-base-content/60">Circuit assessment progress across all regions</p>
        </div>

        <div class="flex items-center gap-3">
            <x-ui.view-toggle :current="$viewMode" />

            <button type="button" class="btn btn-ghost btn-sm btn-square" wire:click="$refresh"
                wire:loading.attr="disabled">
                <x-heroicon-o-arrow-path class="size-4" wire:loading.class="animate-spin" />
            </button>
        </div>
    </div>

    {{-- Circuit Filters --}}
    {{-- <div class="card bg-base-100 shadow">
        <div class="card-body py-3">
            <x-filters.circuit-filters :statusFilter="$statusFilter" :cycleTypeFilter="$cycleTypeFilter" :availableStatuses="$this->availableStatuses" :availableCycleTypes="$this->availableCycleTypes" />
        </div>
    </div> --}}


    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-ui.stat-card label="Total Assessments" :value="number_format($summary['total_assessments'] ?? 0)" icon="bolt" color="primary" size="sm" />

        <x-ui.stat-card label="Total Miles" :value="number_format($summary['total_miles'] ?? 0, 0)" suffix="mi" icon="map" size="sm" />

        <x-ui.stat-card label="Overall Progress" :value="number_format($overallPercent, 0)" suffix="%" icon="chart-bar" :color="$overallPercent >= 75 ? 'success' : ($overallPercent <= 70 ? 'warning' : 'primary')"
            size="sm" />

        <x-ui.stat-card label="Active Planners" :value="number_format($summary['active_planners'] ?? 0)" icon="users" size="sm" />
    </div>

    {{-- <div class="">
        <div class="stats stats-vertical sm:stats-horizontal shadow grid grid-cols-2 md:grid-cols-4 gap-4" >
            <div class="stat">
                <div class="stat-title">Active Assessments</div>
                <div class="stat-value">{{ number_format($totalStats['active_count'] ?? 0) }}</div>
                <div class="stat-desc">Jan 1st - Feb 1st</div>
            </div>

            <div class="stat">
                <div class="stat-title">In QC</div>
                <div class="stat-value">{{ number_format($totalStats['qc_count'] ?? 0) }}</div>
                <div class="stat-desc">↗︎ 400 (22%)</div>
            </div>

            <div class="stat">
                <div class="stat-title">Sent To Rework</div>
                <div class="stat-value">{{ number_format($totalStats['rework_count'] ?? 0) }}</div>
                <div class="stat-desc">↘︎ 90 (14%)</div>
            </div>

            <div class="stat">
                <div class="stat-title">Closed</div>
                <div class="stat-value">{{ number_format($totalStats['closed_count'] ?? 0) }}</div>
                <div class="stat-desc">↘︎ 90 (14%)</div>
            </div>
        </div>
    </div> --}}


    {{-- Content --}}
    <div class="bg-base-100 rounded-box shadow">
        @if ($viewMode === 'cards')
            <div class="p-4">
                @if ($this->regions->isEmpty())
                    <div class="text-center py-12">
                        <x-heroicon-o-map class="size-12 mx-auto mb-4 text-base-content/30" />
                        <h3 class="text-lg font-medium mb-1">No Regions Found</h3>
                        <p class="text-base-content/60">There are no active regions to display.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach ($this->sortedRegions as $region)
                            <x-dashboard.assessments.region-card :region="$region" :stats="$this->regionStats[$region->id] ?? null"
                                wire:key="region-card-{{ $region->id }}" />
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <x-dashboard.assessments.region-table :regions="$this->sortedRegions" :stats="$this->regionStats" :sortBy="$sortBy"
                :sortDir="$sortDir" />
        @endif
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.flex wire:target="openPanel, closePanel, sort"
        class="fixed inset-0 z-40 items-center justify-center bg-base-100/50">
        <span class="loading loading-spinner loading-lg text-primary"></span>
    </div>

    {{-- Region Detail Panel --}}
    @if ($panelOpen && $this->selectedRegion)
        <x-dashboard.assessments.region-panel :region="$this->selectedRegion" :stats="$this->selectedRegionStats" :show="$panelOpen" />
    @endif
</div>
