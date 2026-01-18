<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm breadcrumbs">
            <ul>
                <li><a href="{{ route('admin.data') }}">Data Management</a></li>
                <li>Sync Logs</li>
            </ul>
        </div>
        <h1 class="text-2xl font-bold text-base-content">Sync Logs</h1>
        <p class="text-base-content/60">Detailed history of all data synchronizations</p>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body py-4">
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

                {{-- Date From --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">From</span>
                    </label>
                    <input
                        type="date"
                        wire:model.live="dateFrom"
                        class="input input-bordered input-sm"
                    />
                </div>

                {{-- Date To --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-xs">To</span>
                    </label>
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="input input-bordered input-sm"
                    />
                </div>

                {{-- Clear Filters --}}
                @if($this->hasFilters)
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

    {{-- Sync Logs Table --}}
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            @if($logs->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-base-content/40">No sync logs found</p>
                    @if($this->hasFilters)
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="hover {{ $log->sync_status->value === 'failed' ? 'bg-error/5' : '' }}" wire:key="log-{{ $log->id }}">
                                    <td>
                                        <span class="badge badge-{{ $log->sync_status->color() }} badge-sm">
                                            {{ $log->sync_status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-sm">{{ $log->sync_type->label() }}</td>
                                    <td class="text-sm">{{ $log->sync_trigger->label() }}</td>
                                    <td class="text-sm">
                                        <div class="tooltip" data-tip="{{ $log->started_at->format('M d, Y g:i:s A') }}">
                                            {{ $log->started_at->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td class="font-mono text-sm">
                                        @if($log->duration_seconds !== null)
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
                                        <button wire:click="viewLog({{ $log->id }})" class="btn btn-ghost btn-xs btn-circle">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
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

    {{-- Detail Modal --}}
    <dialog class="modal {{ $showModal ? 'modal-open' : '' }}">
        <div class="modal-box max-w-3xl">
            @if($this->selectedLog)
                @php $log = $this->selectedLog; @endphp

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-lg">Sync Log Details</h3>
                        <p class="text-sm text-base-content/60">{{ $log->started_at->format('M d, Y g:i:s A') }}</p>
                    </div>
                    <span class="badge badge-{{ $log->sync_status->color() }}">
                        {{ $log->sync_status->label() }}
                    </span>
                </div>

                {{-- Overview --}}
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-base-content/60 uppercase tracking-wide">Type</span>
                            <p>{{ $log->sync_type->label() }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60 uppercase tracking-wide">Trigger</span>
                            <p>{{ $log->sync_trigger->label() }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60 uppercase tracking-wide">Region</span>
                            <p>{{ $log->region?->name ?? 'All Regions' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60 uppercase tracking-wide">API Status Filter</span>
                            <p>{{ $log->api_status_filter ?? 'All Statuses' }}</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-base-content/60 uppercase tracking-wide">Duration</span>
                            <p class="font-mono">{{ $log->duration_seconds ? gmdate('H:i:s', $log->duration_seconds) : 'In Progress' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60 uppercase tracking-wide">Circuits Processed</span>
                            <p class="font-mono">{{ number_format($log->circuits_processed ?? 0) }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60 uppercase tracking-wide">Created / Updated</span>
                            <p class="font-mono">{{ number_format($log->circuits_created ?? 0) }} / {{ number_format($log->circuits_updated ?? 0) }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-base-content/60 uppercase tracking-wide">Triggered By</span>
                            <p>{{ $log->triggeredBy?->name ?? 'System' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Error Section --}}
                @if($log->error_message)
                    <div class="mb-6">
                        <span class="text-xs text-error uppercase tracking-wide font-semibold">Error Message</span>
                        <div class="mt-1 p-3 bg-error/10 rounded-lg border border-error/20">
                            <p class="text-sm text-error">{{ $log->error_message }}</p>
                        </div>
                    </div>
                @endif

                {{-- Error Details --}}
                @if($log->error_details)
                    <div class="mb-6">
                        <span class="text-xs text-base-content/60 uppercase tracking-wide">Error Details</span>
                        <div class="mt-1">
                            <x-ui.json-viewer :data="$log->error_details" maxHeight="200px" />
                        </div>
                    </div>
                @endif

                {{-- Context --}}
                @if($log->context_json)
                    <div class="mb-6">
                        <span class="text-xs text-base-content/60 uppercase tracking-wide">Context</span>
                        <div class="mt-1">
                            <x-ui.json-viewer :data="$log->context_json" maxHeight="200px" />
                        </div>
                    </div>
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
</div>
