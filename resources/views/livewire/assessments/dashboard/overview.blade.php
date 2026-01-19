<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Regional Overview</h1>
            <p class="text-base-content/60">Circuit assessment progress across all regions</p>
        </div>

        <div class="flex items-center gap-3">
            <x-ui.view-toggle :current="$viewMode" />

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

    {{-- Circuit Filters --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body py-3">
            <x-filters.circuit-filters
                :statusFilter="$statusFilter"
                :cycleTypeFilter="$cycleTypeFilter"
                :availableStatuses="$this->availableStatuses"
                :availableCycleTypes="$this->availableCycleTypes"
            />
        </div>
    </div>

    {{-- Summary Stats --}}
    @php
        $totalStats = [
            'circuits' => $this->regionStats->sum('active_circuits'),
            'miles' => $this->regionStats->sum('total_miles'),
            'planned' => $this->regionStats->sum('miles_planned'),
            'units' => $this->regionStats->sum('total_units'),
            'planners' => $this->regionStats->sum('active_planners'),
        ];
        $overallPercent = $totalStats['miles'] > 0 ? ($totalStats['planned'] / $totalStats['miles']) * 100 : 0;
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-ui.stat-card
            label="Active Circuits"
            :value="number_format($totalStats['circuits'])"
            icon="bolt"
            color="primary"
            size="sm"
        />
        <x-ui.stat-card
            label="Total Miles"
            :value="number_format($totalStats['miles'], 0)"
            suffix="mi"
            icon="map"
            size="sm"
        />
        <x-ui.stat-card
            label="Overall Progress"
            :value="number_format($overallPercent, 0)"
            suffix="%"
            icon="chart-bar"
            :color="$overallPercent >= 75 ? 'success' : ($overallPercent >= 50 ? 'warning' : 'primary')"
            size="sm"
        />
        <x-ui.stat-card
            label="Active Planners"
            :value="number_format($totalStats['planners'])"
            icon="users"
            size="sm"
        />
    </div>

    {{-- Content --}}
    <div class="bg-base-100 rounded-box shadow">
        @if($viewMode === 'cards')
            <div class="p-4">
                @if($this->regions->isEmpty())
                    <div class="text-center py-12">
                        <x-heroicon-o-map class="size-12 mx-auto mb-4 text-base-content/30" />
                        <h3 class="text-lg font-medium mb-1">No Regions Found</h3>
                        <p class="text-base-content/60">There are no active regions to display.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($this->regions as $region)
                            <x-dashboard.assessments.region-card
                                :region="$region"
                                :stats="$this->regionStats[$region->id] ?? null"
                                wire:key="region-card-{{ $region->id }}"
                            />
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <x-dashboard.assessments.region-table
                :regions="$this->sortedRegions"
                :stats="$this->regionStats"
                :sortBy="$sortBy"
                :sortDir="$sortDir"
            />
        @endif
    </div>

    {{-- Loading Overlay --}}
    <div
        wire:loading.flex
        wire:target="openPanel, closePanel, sort"
        class="fixed inset-0 z-40 items-center justify-center bg-base-100/50"
    >
        <span class="loading loading-spinner loading-lg text-primary"></span>
    </div>

    {{-- Region Detail Panel --}}
    @if($panelOpen && $this->selectedRegion)
        <x-dashboard.assessments.region-panel
            :region="$this->selectedRegion"
            :stats="$this->selectedRegionStats"
            :show="$panelOpen"
        />
    @endif
</div>
