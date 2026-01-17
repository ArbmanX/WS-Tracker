<div class="container mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Sync Control</h1>
        <p class="text-base-content/60">Manage WorkStudio API synchronization</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Sync Control Card --}}
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Trigger Sync
                </h2>

                {{-- Last Sync Info --}}
                @if($this->lastSync)
                    <div class="alert alert-{{ $this->lastSync->sync_status->color() }} mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <span class="font-semibold">Last Sync:</span>
                            {{ $this->lastSync->started_at->diffForHumans() }}
                            @if($this->lastSync->circuits_processed)
                                ({{ $this->lastSync->circuits_processed }} circuits)
                            @endif
                            <span class="badge badge-{{ $this->lastSync->sync_status->color() }} badge-sm ml-2">
                                {{ $this->lastSync->sync_status->label() }}
                            </span>
                        </div>
                    </div>
                @endif

                {{-- Syncing in Progress Alert --}}
                @if($this->isSyncing)
                    <div class="alert alert-info mb-4">
                        <span class="loading loading-spinner loading-sm"></span>
                        <span>Sync in progress...</span>
                    </div>
                @endif

                {{-- Status Selection --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Select Statuses to Sync</span>
                    </label>
                    <div class="flex flex-wrap gap-4">
                        @foreach($this->availableStatuses as $code => $label)
                            <label class="label cursor-pointer gap-2">
                                <input
                                    type="checkbox"
                                    wire:model="selectedStatuses"
                                    value="{{ $code }}"
                                    class="checkbox checkbox-primary"
                                />
                                <span class="label-text">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Force Overwrite Option --}}
                <div class="form-control mt-4">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input
                            type="checkbox"
                            wire:model="forceOverwrite"
                            class="checkbox checkbox-warning"
                        />
                        <div>
                            <span class="label-text font-medium">Force Overwrite</span>
                            <p class="text-xs text-base-content/60">Override user modifications with API data</p>
                        </div>
                    </label>
                </div>

                {{-- Sync Button --}}
                <div class="card-actions justify-end mt-6">
                    @if(auth()->user()?->hasRole('sudo_admin'))
                        <button
                            wire:click="triggerSync"
                            wire:loading.attr="disabled"
                            wire:target="triggerSync"
                            class="btn btn-primary"
                            @disabled(empty($selectedStatuses) || $this->isSyncing)
                        >
                            <span wire:loading.remove wire:target="triggerSync">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Sync Now
                            </span>
                            <span wire:loading wire:target="triggerSync">
                                <span class="loading loading-spinner loading-sm mr-2"></span>
                                Dispatching...
                            </span>
                        </button>
                    @else
                        <div class="tooltip" data-tip="Only sudo admins can trigger syncs">
                            <button class="btn btn-disabled">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Sync Now
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Syncs Card --}}
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Recent Syncs
                    </h2>
                    <a href="{{ route('admin.sync.history') }}" class="btn btn-ghost btn-sm">
                        View All
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                @if($this->recentSyncs->isEmpty())
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-base-content/20 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-base-content/40">No sync history yet</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Circuits</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->recentSyncs as $sync)
                                    <tr class="hover">
                                        <td>
                                            <span class="badge badge-{{ $sync->sync_status->color() }} badge-sm">
                                                {{ $sync->sync_status->label() }}
                                            </span>
                                        </td>
                                        <td class="text-xs">
                                            {{ $sync->sync_trigger->label() }}
                                        </td>
                                        <td class="font-mono text-sm">
                                            {{ $sync->circuits_processed ?? '-' }}
                                        </td>
                                        <td class="text-xs text-base-content/60">
                                            {{ $sync->started_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sync Schedule Info --}}
    <div class="card bg-base-100 shadow-lg mt-6">
        <div class="card-body">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Scheduled Syncs
            </h2>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Frequency</th>
                            <th>Schedule (ET)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <span class="badge badge-primary">ACTIV</span>
                            </td>
                            <td>Twice Daily</td>
                            <td class="font-mono text-sm">4:30 AM, 4:30 PM</td>
                        </tr>
                        <tr>
                            <td>
                                <span class="badge badge-info">QC</span>
                                <span class="badge badge-warning">REWRK</span>
                                <span class="badge badge-neutral">CLOSE</span>
                            </td>
                            <td>Weekly</td>
                            <td class="font-mono text-sm">Monday 4:30 AM</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
