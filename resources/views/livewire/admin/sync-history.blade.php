<div class="container mx-auto p-6">
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm breadcrumbs">
            <ul>
                <li><a href="{{ route('admin.sync') }}">Sync Control</a></li>
                <li>History</li>
            </ul>
        </div>
        <h1 class="text-2xl font-bold text-base-content">Sync History</h1>
        <p class="text-base-content/60">View all past synchronization logs</p>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body">
            <div class="flex flex-wrap gap-4 items-end">
                {{-- Status Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Status</span>
                    </label>
                    <select wire:model.live="statusFilter" class="select select-bordered select-sm">
                        <option value="">All Statuses</option>
                        @foreach($this->statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Trigger Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Trigger</span>
                    </label>
                    <select wire:model.live="triggerFilter" class="select select-bordered select-sm">
                        <option value="">All Triggers</option>
                        @foreach($this->triggerOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Type Filter --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">Type</span>
                    </label>
                    <select wire:model.live="typeFilter" class="select select-bordered select-sm">
                        <option value="">All Types</option>
                        @foreach($this->typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear Filters --}}
                @if($statusFilter || $triggerFilter || $typeFilter)
                    <button wire:click="clearFilters" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear Filters
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Sync Logs Table --}}
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            @if($logs->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-base-content/40">No sync logs found</p>
                    @if($statusFilter || $triggerFilter || $typeFilter)
                        <button wire:click="clearFilters" class="btn btn-ghost btn-sm mt-2">Clear filters</button>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Trigger</th>
                                <th>Started</th>
                                <th>Duration</th>
                                <th>Circuits</th>
                                <th>Triggered By</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="hover" wire:key="log-{{ $log->id }}">
                                    <td>
                                        <span class="badge badge-{{ $log->sync_status->color() }}">
                                            {{ $log->sync_status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-sm">{{ $log->sync_type->label() }}</td>
                                    <td class="text-sm">{{ $log->sync_trigger->label() }}</td>
                                    <td class="text-sm">
                                        <div class="tooltip" data-tip="{{ $log->started_at->format('M d, Y g:i A') }}">
                                            {{ $log->started_at->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td class="font-mono text-sm">
                                        @if($log->duration_seconds)
                                            {{ gmdate('i:s', $log->duration_seconds) }}
                                        @else
                                            <span class="loading loading-dots loading-xs"></span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-sm">
                                        @if($log->circuits_processed)
                                            <span class="tooltip" data-tip="Created: {{ $log->circuits_created ?? 0 }}, Updated: {{ $log->circuits_updated ?? 0 }}">
                                                {{ $log->circuits_processed }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-sm">
                                        @if($log->triggeredBy)
                                            <div class="flex items-center gap-2">
                                                <div class="avatar placeholder">
                                                    <div class="bg-primary text-primary-content w-6 rounded-full">
                                                        <span class="text-xs">{{ $log->triggeredBy->initials() }}</span>
                                                    </div>
                                                </div>
                                                <span class="text-xs">{{ $log->triggeredBy->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-base-content/40">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->error_message || $log->api_status_filter)
                                            <div class="dropdown dropdown-end">
                                                <div tabindex="0" role="button" class="btn btn-ghost btn-xs btn-circle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div tabindex="0" class="dropdown-content z-50 card card-compact w-64 p-2 shadow-lg bg-base-100 border border-base-300">
                                                    <div class="card-body">
                                                        @if($log->api_status_filter)
                                                            <div class="text-xs">
                                                                <span class="font-semibold">Statuses:</span>
                                                                {{ $log->api_status_filter }}
                                                            </div>
                                                        @endif
                                                        @if($log->error_message)
                                                            <div class="text-xs text-error mt-2">
                                                                <span class="font-semibold">Error:</span>
                                                                {{ Str::limit($log->error_message, 100) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
