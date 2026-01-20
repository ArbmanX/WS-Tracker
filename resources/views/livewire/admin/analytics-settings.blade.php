<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Analytics Settings</h1>
            <p class="text-base-content/60">Configure global filters for all analytics dashboards</p>
        </div>

        @if($this->currentSettings->updatedByUser)
            <div class="text-sm text-base-content/60">
                Last updated by {{ $this->currentSettings->updatedByUser->name }}
                <span class="text-base-content/40">{{ $this->currentSettings->updated_at?->diffForHumans() }}</span>
            </div>
        @endif
    </div>

    {{-- Current Settings Summary --}}
    <div class="alert alert-info">
        <x-heroicon-o-information-circle class="size-5" />
        <div>
            <p class="font-medium">Current Active Filters</p>
            <p class="text-sm">
                Scope Year: <span class="font-mono font-bold">{{ $this->currentSettings->scope_year }}</span>
                &bull;
                Cycle Types: <span class="font-bold">{{ $this->currentSettings->selected_cycle_types ? count($this->currentSettings->selected_cycle_types) . ' selected' : 'All' }}</span>
                &bull;
                Contractors: <span class="font-bold">{{ $this->currentSettings->selected_contractors ? count($this->currentSettings->selected_contractors) . ' selected' : 'All' }}</span>
            </p>
        </div>
    </div>

    {{-- Settings Form --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Scope Year --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">
                    <x-heroicon-o-calendar class="size-5 text-primary" />
                    Scope Year
                </h2>
                <p class="text-sm text-base-content/60 mb-4">
                    Only include circuits from this scope year (extracted from work order number).
                </p>

                <select
                    wire:model.live="scopeYear"
                    class="select select-bordered w-full"
                >
                    @foreach($this->availableScopeYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>

                @if(empty($this->availableScopeYears))
                    <p class="text-sm text-warning mt-2">
                        <x-heroicon-o-exclamation-triangle class="size-4 inline" />
                        No circuit data found. Sync circuits to populate available years.
                    </p>
                @endif
            </div>
        </div>

        {{-- Cycle Types --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">
                    <x-heroicon-o-arrow-path class="size-5 text-secondary" />
                    Cycle Types
                </h2>
                <p class="text-sm text-base-content/60 mb-4">
                    Filter analytics to only include specific cycle types.
                </p>

                <label class="label cursor-pointer justify-start gap-3 mb-2">
                    <input
                        type="checkbox"
                        wire:model.live="allCycleTypes"
                        class="checkbox checkbox-primary"
                    />
                    <span class="label-text font-medium">Include All Cycle Types</span>
                </label>

                @if(!$allCycleTypes)
                    <div class="divider my-2">Select specific types</div>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @forelse($this->availableCycleTypes as $cycleType)
                            <label class="label cursor-pointer justify-start gap-3 py-1">
                                <input
                                    type="checkbox"
                                    value="{{ $cycleType }}"
                                    wire:model.live="selectedCycleTypes"
                                    class="checkbox checkbox-sm checkbox-secondary"
                                />
                                <span class="label-text text-sm">{{ $cycleType }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-base-content/40 py-2">
                                No cycle types found in circuit data.
                            </p>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        {{-- Contractors --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">
                    <x-heroicon-o-building-office class="size-5 text-accent" />
                    Contractors
                </h2>
                <p class="text-sm text-base-content/60 mb-4">
                    Filter planners by contractor (from username prefix like "ASPLUNDH\").
                </p>

                <label class="label cursor-pointer justify-start gap-3 mb-2">
                    <input
                        type="checkbox"
                        wire:model.live="allContractors"
                        class="checkbox checkbox-primary"
                    />
                    <span class="label-text font-medium">Include All Contractors</span>
                </label>

                @if(!$allContractors)
                    <div class="divider my-2">Select specific contractors</div>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @forelse($this->availableContractors as $contractor)
                            <label class="label cursor-pointer justify-start gap-3 py-1">
                                <input
                                    type="checkbox"
                                    value="{{ $contractor }}"
                                    wire:model.live="selectedContractors"
                                    class="checkbox checkbox-sm checkbox-accent"
                                />
                                <span class="label-text text-sm font-mono">{{ $contractor }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-base-content/40 py-2">
                                No contractors found. Planners need ws_username with contractor prefix.
                            </p>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Preview Stats --}}
    <div class="card bg-base-200 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">
                <x-heroicon-o-eye class="size-5" />
                Preview Impact
            </h2>
            <p class="text-sm text-base-content/60 mb-4">
                With current filter settings, analytics will include:
            </p>

            @php $preview = $this->previewStats; @endphp
            <div class="stats stats-horizontal bg-base-100 shadow">
                <div class="stat">
                    <div class="stat-title">Circuits</div>
                    <div class="stat-value text-primary">{{ number_format($preview['circuits']) }}</div>
                    <div class="stat-desc">matching filters</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Planners</div>
                    <div class="stat-value text-secondary">{{ number_format($preview['planners']) }}</div>
                    <div class="stat-desc">matching filters</div>
                </div>
            </div>

            @if($preview['circuits'] === 0)
                <div class="alert alert-warning mt-4">
                    <x-heroicon-o-exclamation-triangle class="size-5" />
                    <span>No circuits match the current filters. Analytics dashboards will show no data.</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row justify-end gap-3">
        <button
            type="button"
            wire:click="resetToDefaults"
            wire:confirm="Reset all settings to defaults? This will include all cycle types and contractors for the current year."
            class="btn btn-ghost"
        >
            <x-heroicon-o-arrow-uturn-left class="size-4" />
            Reset to Defaults
        </button>

        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            class="btn btn-primary"
        >
            <x-heroicon-o-check class="size-4" wire:loading.remove wire:target="save" />
            <span class="loading loading-spinner loading-sm" wire:loading wire:target="save"></span>
            Save Settings
        </button>
    </div>

    {{-- Permission Warning --}}
    @unless(auth()->user()?->hasRole('sudo_admin'))
        <div class="alert alert-warning">
            <x-heroicon-o-lock-closed class="size-5" />
            <span>Only sudo admins can modify these settings. You are viewing in read-only mode.</span>
        </div>
    @endunless
</div>
