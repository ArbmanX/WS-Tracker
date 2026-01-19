<div
    class="container mx-auto p-6"
    @if($this->isOutputRunning)
        wire:poll.1s="pollForUpdates"
    @elseif($this->isSyncing)
        wire:poll.3s="pollForUpdates"
    @endif
>
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Sync Control</h1>
            <p class="text-base-content/60">Manage WorkStudio API synchronization</p>
        </div>
        <div class="badge badge-outline badge-lg gap-2">
            @if(auth()->user()?->hasRole('sudo_admin'))
                <span class="inline-block h-2 w-2 rounded-full bg-success"></span>
                Admin Access
            @else
                <span class="inline-block h-2 w-2 rounded-full bg-error"></span>
                View Only
            @endif
        </div>
    </div>

    {{-- Status Bar --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body py-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="font-medium">Status:</span>
                    @if($this->isOutputRunning)
                        <span class="badge badge-info gap-2">
                            <span class="loading loading-spinner loading-xs"></span>
                            Running
                        </span>
                    @elseif($this->isSyncing)
                        <span class="badge badge-warning gap-2">
                            <span class="loading loading-spinner loading-xs"></span>
                            In Queue
                        </span>
                    @else
                        <span class="badge badge-neutral gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-base-content/40"></span>
                            Idle
                        </span>
                    @endif
                </div>
                @if($this->lastSync)
                    <div class="text-sm text-base-content/60">
                        <span class="font-medium">Last sync:</span>
                        {{ $this->lastSync->started_at->format('g:i A') }}
                        @if($this->lastSync->circuits_processed)
                            ({{ number_format($this->lastSync->circuits_processed) }} circuits,
                            {{ $this->lastSync->duration_seconds }}s)
                        @endif
                        <span class="badge badge-{{ $this->lastSync->sync_status->color() }} badge-xs ml-1">
                            {{ $this->lastSync->sync_status->label() }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Live Output Panel (2/3 width) --}}
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-lg h-full">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="card-title text-lg">
                            <x-heroicon-o-command-line class="h-5 w-5" />
                            Live Output
                        </h2>
                        <button
                            wire:click="clearLog"
                            class="btn btn-ghost btn-sm"
                            @disabled($this->isOutputRunning)
                        >
                            <x-heroicon-o-trash class="h-4 w-4" />
                            Clear
                        </button>
                    </div>

                    {{-- Progress Bar (when running) --}}
                    @if($this->isOutputRunning && $this->syncOutput['state']['total_items'] > 0)
                        @php
                            $progress = ($this->syncOutput['state']['current_item'] / $this->syncOutput['state']['total_items']) * 100;
                        @endphp
                        <div class="mb-4">
                            <div class="flex justify-between text-xs mb-1">
                                <span>{{ $this->syncOutput['state']['current_operation'] }}</span>
                                <span>{{ $this->syncOutput['state']['current_item'] }} / {{ $this->syncOutput['state']['total_items'] }}</span>
                            </div>
                            <progress class="progress progress-primary w-full" value="{{ $progress }}" max="100"></progress>
                        </div>
                    @endif

                    {{-- Log Output Area --}}
                    <div
                        class="bg-base-200 rounded-lg p-4 font-mono text-sm h-80 overflow-y-auto"
                        x-data="{ autoScroll: true }"
                        x-init="$watch('$wire.syncOutput', () => { if (autoScroll) $el.scrollTop = $el.scrollHeight })"
                        @scroll="autoScroll = ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 20)"
                    >
                        @forelse($this->syncOutput['logs'] as $log)
                            <div class="flex gap-2 py-0.5 hover:bg-base-300/50 px-1 -mx-1 rounded">
                                <span class="text-base-content/40 shrink-0">{{ $log['timestamp'] }}</span>
                                <span @class([
                                    'shrink-0 font-semibold',
                                    'text-info' => $log['level'] === 'info',
                                    'text-success' => $log['level'] === 'success',
                                    'text-warning' => $log['level'] === 'warning',
                                    'text-error' => $log['level'] === 'error',
                                ])>[{{ strtoupper($log['level']) }}]</span>
                                <span class="text-base-content">{{ $log['message'] }}</span>
                            </div>
                        @empty
                            <div class="text-base-content/40 text-center py-8">
                                Ready to sync. Select statuses and click "Sync Now".
                            </div>
                        @endforelse
                    </div>

                    {{-- Error Count Badge --}}
                    @if(($this->syncOutput['state']['error_count'] ?? 0) > 0)
                        <div class="mt-2">
                            <span class="badge badge-error gap-1">
                                <x-heroicon-o-exclamation-triangle class="h-3 w-3" />
                                {{ $this->syncOutput['state']['error_count'] }} errors
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sync Options Panel (1/3 width) --}}
        <div class="space-y-6">
            {{-- Sync Options Card --}}
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-lg mb-4">
                        <x-heroicon-o-cog-6-tooth class="h-5 w-5" />
                        Sync Options
                    </h2>

                    {{-- Status Selection --}}
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Statuses</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($this->availableStatuses as $code => $label)
                                <label class="label cursor-pointer gap-2 p-2 rounded-lg hover:bg-base-200 transition-colors">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedStatuses"
                                        value="{{ $code }}"
                                        class="checkbox checkbox-primary checkbox-sm"
                                        @disabled($this->isOutputRunning || $this->isSyncing)
                                    />
                                    <span class="label-text">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="divider my-2"></div>

                    {{-- Force Overwrite Option --}}
                    <label class="label cursor-pointer justify-start gap-3 p-2 rounded-lg hover:bg-base-200 transition-colors">
                        <input
                            type="checkbox"
                            wire:model.live="forceOverwrite"
                            class="checkbox checkbox-warning checkbox-sm"
                            @disabled($this->isOutputRunning || $this->isSyncing)
                        />
                        <div>
                            <span class="label-text font-medium">Force Overwrite</span>
                            <p class="text-xs text-base-content/60">Override user modifications</p>
                        </div>
                    </label>

                    {{-- Run in Background Option --}}
                    <label class="label cursor-pointer justify-start gap-3 p-2 rounded-lg hover:bg-base-200 transition-colors">
                        <input
                            type="checkbox"
                            wire:model.live="runInBackground"
                            class="checkbox checkbox-sm"
                            @disabled($this->isOutputRunning || $this->isSyncing)
                        />
                        <div>
                            <span class="label-text font-medium">Run in Background</span>
                            <p class="text-xs text-base-content/60">Dispatch to queue worker</p>
                        </div>
                    </label>

                    <div class="divider my-2"></div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-2">
                        @if(auth()->user()?->hasRole('sudo_admin'))
                            @if($this->isOutputRunning)
                                <button
                                    wire:click="cancelSync"
                                    class="btn btn-error btn-sm flex-1"
                                >
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                    Cancel
                                </button>
                            @else
                                <button
                                    wire:click="triggerSync"
                                    wire:loading.attr="disabled"
                                    wire:target="triggerSync"
                                    class="btn btn-primary flex-1"
                                    @disabled(empty($selectedStatuses) || $this->isSyncing)
                                >
                                    <span wire:loading.remove wire:target="triggerSync">
                                        <x-heroicon-o-arrow-path class="h-4 w-4" />
                                        Sync Now
                                    </span>
                                    <span wire:loading wire:target="triggerSync">
                                        <span class="loading loading-spinner loading-sm"></span>
                                        Starting...
                                    </span>
                                </button>
                            @endif
                        @else
                            <div class="tooltip flex-1" data-tip="Only sudo admins can trigger syncs">
                                <button class="btn btn-disabled w-full">
                                    <x-heroicon-o-lock-closed class="h-4 w-4" />
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
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="card-title text-lg">
                            <x-heroicon-o-clock class="h-5 w-5" />
                            Recent Syncs
                        </h2>
                        <a href="{{ route('admin.sync.history') }}" class="btn btn-ghost btn-xs">
                            View All
                            <x-heroicon-o-chevron-right class="h-3 w-3" />
                        </a>
                    </div>

                    @if($this->recentSyncs->isEmpty())
                        <div class="text-center py-4 text-base-content/40 text-sm">
                            No sync history yet
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($this->recentSyncs as $sync)
                                <div class="flex items-center justify-between py-2 px-2 rounded-lg hover:bg-base-200 transition-colors text-sm">
                                    <div class="flex items-center gap-2">
                                        <span @class([
                                            'badge badge-xs',
                                            'badge-success' => $sync->sync_status === \App\Enums\SyncStatus::Completed,
                                            'badge-error' => $sync->sync_status === \App\Enums\SyncStatus::Failed,
                                            'badge-warning' => $sync->sync_status === \App\Enums\SyncStatus::Warning,
                                            'badge-info' => $sync->sync_status === \App\Enums\SyncStatus::Started,
                                        ])>
                                            @if($sync->sync_status === \App\Enums\SyncStatus::Completed)
                                                <x-heroicon-o-check class="h-3 w-3" />
                                            @elseif($sync->sync_status === \App\Enums\SyncStatus::Failed)
                                                <x-heroicon-o-x-mark class="h-3 w-3" />
                                            @elseif($sync->sync_status === \App\Enums\SyncStatus::Warning)
                                                <x-heroicon-o-exclamation-triangle class="h-3 w-3" />
                                            @else
                                                <span class="loading loading-spinner loading-xs"></span>
                                            @endif
                                        </span>
                                        <span class="text-base-content/60">{{ $sync->started_at->format('g:i A') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-base-content/60">{{ $sync->sync_trigger->label() }}</span>
                                        <span class="font-mono">{{ $sync->circuits_processed ?? '-' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Sync Schedule Info --}}
    <div class="card bg-base-100 shadow-lg mt-6">
        <div class="card-body">
            <h2 class="card-title text-lg">
                <x-heroicon-o-calendar-days class="h-5 w-5" />
                Scheduled Syncs
            </h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Frequency</th>
                            <th>Schedule (ET)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="hover">
                            <td>
                                <span class="badge badge-primary badge-sm">ACTIV</span>
                            </td>
                            <td>Twice Daily</td>
                            <td class="font-mono text-sm">4:30 AM, 4:30 PM</td>
                        </tr>
                        <tr class="hover">
                            <td class="space-x-1">
                                <span class="badge badge-info badge-sm">QC</span>
                                <span class="badge badge-warning badge-sm">REWRK</span>
                                <span class="badge badge-neutral badge-sm">CLOSE</span>
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
