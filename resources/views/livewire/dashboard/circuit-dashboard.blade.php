<div class="flex h-full flex-col">
    {{-- Quick Stats Bar --}}
    <div class="bg-base-200/50 border-b border-base-200 px-6 py-4">
        <div class="flex flex-wrap gap-4 justify-between items-center">
            {{-- Stat Pills --}}
            <div class="flex gap-3 flex-wrap">
                <div class="tooltip tooltip-bottom" data-tip="Total active circuits across all regions">
                    <div class="stat-pill flex items-center gap-3 bg-base-100 rounded-lg px-4 py-2.5 border border-base-300 transition-colors cursor-default">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-base-content/60">Active Circuits</div>
                            <div class="text-lg font-bold text-primary">{{ $this->stats['total_circuits'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="tooltip tooltip-bottom" data-tip="Circuits awaiting QC approval">
                    <div class="stat-pill flex items-center gap-3 bg-base-100 rounded-lg px-4 py-2.5 border border-base-300 transition-colors cursor-default">
                        <div class="w-10 h-10 rounded-full bg-warning/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-base-content/60">Pending QC</div>
                            <div class="text-lg font-bold text-warning">{{ $this->circuits->get('qc', collect())->count() }}</div>
                        </div>
                    </div>
                </div>

                <div class="tooltip tooltip-bottom" data-tip="Successfully completed and closed circuits">
                    <div class="stat-pill flex items-center gap-3 bg-base-100 rounded-lg px-4 py-2.5 border border-base-300 transition-colors cursor-default">
                        <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-base-content/60">Completed</div>
                            <div class="text-lg font-bold text-success">{{ $this->circuits->get('closed', collect())->count() }}</div>
                        </div>
                    </div>
                </div>

                <div class="tooltip tooltip-bottom" data-tip="Total miles planned across all circuits">
                    <div class="stat-pill flex items-center gap-3 bg-base-100 rounded-lg px-4 py-2.5 border border-base-300 transition-colors cursor-default">
                        <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-base-content/60">Miles Planned</div>
                            <div class="text-lg font-bold text-secondary">{{ number_format($this->stats['total_miles_planned'], 1) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- View Toggle & Filters --}}
            <div class="flex items-center gap-3">
                {{-- View Toggle Tabs --}}
                <div class="tabs tabs-boxed bg-base-300/50 p-1">
                    <button wire:click="setView('kanban')"
                            class="tab tab-sm gap-2 {{ $activeView === 'kanban' ? 'tab-active bg-base-100 shadow-sm' : '' }}"
                            aria-label="Switch to Kanban view">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        Kanban
                    </button>
                    <button wire:click="setView('analytics')"
                            class="tab tab-sm gap-2 {{ $activeView === 'analytics' ? 'tab-active bg-base-100 shadow-sm' : '' }}"
                            aria-label="Switch to Analytics view">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Analytics
                    </button>
                </div>

                {{-- Filter Controls --}}
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-sm btn-ghost gap-2" aria-label="Filter options">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filters
                        @if($regionFilter || $plannerFilter || $dateFrom || $dateTo)
                            <span class="badge badge-primary badge-xs">{{ collect([$regionFilter, $plannerFilter, $dateFrom, $dateTo])->filter()->count() }}</span>
                        @endif
                    </div>
                    <div tabindex="0" class="dropdown-content z-50 mt-2 w-80 rounded-box bg-base-100 p-4 shadow-lg border border-base-200">
                        <h3 class="font-semibold text-sm mb-3">Filter Circuits</h3>

                        {{-- Region Filter --}}
                        <div class="form-control mb-3">
                            <label class="label py-1">
                                <span class="label-text text-xs font-medium">Region</span>
                            </label>
                            <select wire:model.live="regionFilter" class="select select-bordered select-sm w-full">
                                <option value="">All Regions</option>
                                @foreach ($this->regions as $region)
                                    <option value="{{ $region['name'] }}">
                                        {{ $region['name'] }} ({{ $region['circuits_count'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Planner Filter --}}
                        <div class="form-control mb-3">
                            <label class="label py-1">
                                <span class="label-text text-xs font-medium">Planner</span>
                            </label>
                            <select wire:model.live="plannerFilter" class="select select-bordered select-sm w-full">
                                <option value="">All Planners</option>
                                @foreach ($this->planners as $planner)
                                    <option value="{{ $planner['name'] }}">{{ $planner['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date Range --}}
                        <div class="form-control mb-3">
                            <label class="label py-1">
                                <span class="label-text text-xs font-medium">Date Range</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="date" wire:model.live="dateFrom" class="input input-bordered input-sm flex-1" placeholder="From">
                                <input type="date" wire:model.live="dateTo" class="input input-bordered input-sm flex-1" placeholder="To">
                            </div>
                        </div>

                        {{-- Show Closed Toggle --}}
                        <label class="label cursor-pointer justify-start gap-3 py-2">
                            <input type="checkbox" wire:model.live="showClosedCircuits" class="checkbox checkbox-sm checkbox-primary">
                            <span class="label-text text-sm">Show Closed Circuits</span>
                        </label>

                        {{-- Clear Filters --}}
                        @if($regionFilter || $plannerFilter || $dateFrom || $dateTo)
                            <button wire:click="clearFilters" class="btn btn-ghost btn-sm w-full mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Clear All Filters
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Filters Display --}}
        @if ($regionFilter || $plannerFilter || $dateFrom || $dateTo)
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="text-xs text-base-content/60">Active filters:</span>
                @if ($regionFilter)
                    <span class="badge badge-primary badge-sm gap-1">
                        {{ $regionFilter }}
                        <button wire:click="$set('regionFilter', null)" class="hover:text-primary-content" aria-label="Remove region filter">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                @endif
                @if ($plannerFilter)
                    <span class="badge badge-secondary badge-sm gap-1">
                        {{ $plannerFilter }}
                        <button wire:click="$set('plannerFilter', null)" aria-label="Remove planner filter">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                @endif
                @if ($dateFrom || $dateTo)
                    <span class="badge badge-accent badge-sm gap-1">
                        {{ $dateFrom ?? '...' }} - {{ $dateTo ?? '...' }}
                        <button wire:click="$set('dateFrom', null); $set('dateTo', null)" aria-label="Remove date filter">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Main Content Area --}}
    <div class="flex-1 overflow-hidden">
        @if($activeView === 'kanban')
            {{-- KANBAN VIEW --}}
            @include('livewire.dashboard.partials.kanban-view')
        @else
            {{-- ANALYTICS VIEW --}}
            @include('livewire.dashboard.partials.analytics-view')
        @endif
    </div>
</div>
