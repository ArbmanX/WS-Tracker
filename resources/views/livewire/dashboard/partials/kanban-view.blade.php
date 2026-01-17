{{-- Kanban Board View --}}
<div class="flex h-full gap-4 overflow-x-auto p-6 kanban-scroll" wire:sortable-group="handleCircuitMoved">
    @php
        $stages = [
            'active' => [
                'label' => 'Active',
                'description' => 'Circuits currently being worked on',
                'color' => 'primary',
                'dot' => 'bg-primary',
            ],
            'pending_permissions' => [
                'label' => 'Pending Permissions',
                'description' => 'Awaiting property owner approvals',
                'color' => 'warning',
                'dot' => 'bg-warning',
            ],
            'qc' => [
                'label' => 'QC Review',
                'description' => 'Quality control review in progress',
                'color' => 'info',
                'dot' => 'bg-info',
            ],
            'rework' => [
                'label' => 'Rework',
                'description' => 'Circuits requiring corrections',
                'color' => 'error',
                'dot' => 'bg-error',
            ],
            'closed' => [
                'label' => 'Closed',
                'description' => 'Successfully completed circuits',
                'color' => 'success',
                'dot' => 'bg-success',
            ],
        ];
    @endphp

    @foreach ($stages as $stage => $config)
        @if ($stage !== 'closed' || $showClosedCircuits)
            <div class="flex h-full w-80 flex-shrink-0 flex-col">
                {{-- Column Header --}}
                <div class="flex items-center justify-between rounded-t-xl bg-base-200 p-4">
                    <div class="flex items-center gap-2">
                        <div class="tooltip tooltip-right" data-tip="{{ $config['description'] }}">
                            <div class="h-3 w-3 rounded-full {{ $config['dot'] }} cursor-help"></div>
                        </div>
                        <h3 class="text-sm font-semibold text-base-content">{{ $config['label'] }}</h3>
                        <div class="badge badge-sm">{{ $this->circuits->get($stage, collect())->count() }}</div>
                    </div>
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-xs btn-circle" aria-label="Column options">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </div>
                        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-40 p-2 shadow-lg border border-base-200">
                            <li><a class="text-xs">Sort by Date</a></li>
                            <li><a class="text-xs">Sort by Progress</a></li>
                            <li><a class="text-xs">Sort by Miles</a></li>
                        </ul>
                    </div>
                </div>

                {{-- Column Body with Gradient Background --}}
                <div class="kanban-column flex-1 space-y-3 overflow-y-auto rounded-b-xl p-4"
                     style="background: linear-gradient(180deg, oklch(var(--b2)) 0%, oklch(var(--b1)) 100%); min-height: calc(100vh - 280px);"
                     wire:sortable-group.item-group="{{ $stage }}">
                    @forelse($this->circuits->get($stage, collect()) as $circuit)
                        <div wire:key="circuit-{{ $circuit['id'] }}"
                             wire:sortable-group.item="{{ $circuit['id'] }}"
                             class="kanban-card {{ $stage === 'closed' ? 'opacity-75' : '' }}">
                            {{-- Circuit Card --}}
                            <div class="card bg-base-100 shadow-sm border
                                @if($stage === 'pending_permissions') border-warning/30
                                @elseif($stage === 'qc') border-info/30
                                @elseif($stage === 'rework') border-error/30
                                @elseif($stage === 'closed') border-success/30
                                @else border-base-200 @endif">
                                <div class="card-body gap-3 p-4">
                                    {{-- Header with Badge and Menu --}}
                                    <div class="flex justify-between items-start">
                                        <div class="tooltip" data-tip="Work Order: {{ $circuit['work_order'] }}{{ $circuit['extension'] }}">
                                            <div class="badge badge-{{ $config['color'] }} badge-sm cursor-help">
                                                {{ $circuit['work_order'] }}{{ $circuit['extension'] }}
                                            </div>
                                        </div>
                                        <div class="dropdown dropdown-end">
                                            <div tabindex="0" role="button" class="btn btn-ghost btn-xs btn-circle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                                </svg>
                                            </div>
                                            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-40 p-2 shadow-lg border border-base-200">
                                                <li><a class="text-xs">View Details</a></li>
                                                <li><a class="text-xs">Edit Circuit</a></li>
                                                <li><a class="text-xs">View History</a></li>
                                            </ul>
                                        </div>
                                    </div>

                                    {{-- Title and Region --}}
                                    <div>
                                        <h4 class="font-medium text-sm">{{ $circuit['title'] }}</h4>
                                        <p class="text-xs text-base-content/60">{{ $circuit['region']['name'] }} Region</p>
                                    </div>

                                    {{-- Warning Alert for Pending Permissions --}}
                                    @if($stage === 'pending_permissions' && $circuit['aggregate']['units_pending'] > 0)
                                        <div class="alert alert-warning py-2 px-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span class="text-xs">{{ $circuit['aggregate']['units_pending'] }} permits pending</span>
                                        </div>
                                    @endif

                                    {{-- Error Alert for Rework --}}
                                    @if($stage === 'rework')
                                        <div class="alert alert-error py-2 px-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-xs">Requires corrections</span>
                                        </div>
                                    @endif

                                    {{-- Progress Bar --}}
                                    <div>
                                        <div class="mb-1 flex justify-between text-xs">
                                            <span class="text-base-content/60">Progress</span>
                                            <span class="font-medium">{{ $circuit['percent_complete'] }}%</span>
                                        </div>
                                        <div class="tooltip tooltip-bottom w-full" data-tip="{{ number_format($circuit['miles_planned'], 1) }} of {{ number_format($circuit['total_miles'], 1) }} miles planned">
                                            <progress
                                                class="progress h-2 w-full progress-{{ $config['color'] }}"
                                                value="{{ $circuit['percent_complete'] }}" max="100">
                                            </progress>
                                        </div>
                                    </div>

                                    {{-- Footer: Miles and Planners --}}
                                    <div class="flex justify-between items-center pt-3 border-t border-base-200">
                                        <div class="tooltip" data-tip="Total miles: {{ number_format($circuit['total_miles'], 1) }}">
                                            <div class="flex items-center gap-1 text-xs text-base-content/60 cursor-help">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                                <span>{{ number_format($circuit['miles_planned'], 1) }} mi</span>
                                            </div>
                                        </div>
                                        <div class="tooltip tooltip-left" data-tip="Assigned: {{ collect($circuit['planners'])->pluck('name')->join(', ') ?: 'Unassigned' }}">
                                            <div class="avatar-group -space-x-4 rtl:space-x-reverse cursor-help">
                                                @forelse(array_slice($circuit['planners'], 0, 2) as $planner)
                                                    <div class="avatar placeholder">
                                                        <div class="bg-secondary text-secondary-content w-6 rounded-full">
                                                            <span class="text-xs">{{ $planner['initials'] }}</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="avatar placeholder">
                                                        <div class="bg-base-300 text-base-content/50 w-6 rounded-full">
                                                            <span class="text-xs">?</span>
                                                        </div>
                                                    </div>
                                                @endforelse
                                                @if(count($circuit['planners']) > 2)
                                                    <div class="avatar placeholder">
                                                        <div class="bg-accent text-accent-content w-6 rounded-full">
                                                            <span class="text-xs">+{{ count($circuit['planners']) - 2 }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Closed: Completion Date --}}
                                    @if($stage === 'closed')
                                        <div class="text-xs text-base-content/60 -mt-2">
                                            Completed {{ $circuit['api_modified_date'] }}
                                        </div>
                                    @endif

                                    {{-- QC Actions --}}
                                    @if($stage === 'qc')
                                        <div class="flex gap-2 pt-2 border-t border-base-200">
                                            <button class="btn btn-success btn-xs flex-1">Approve</button>
                                            <button class="btn btn-error btn-xs btn-outline flex-1">Rework</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-40 flex-col items-center justify-center rounded-lg border-2 border-dashed border-base-300 text-center p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-sm text-base-content/40">No circuits in this stage</p>
                            <p class="text-xs text-base-content/30 mt-1">Drag circuits here to move them</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    @endforeach
</div>
