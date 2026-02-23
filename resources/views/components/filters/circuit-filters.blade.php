@props([
    'statusFilter' => [],
    'cycleTypeFilter' => [],
    'availableStatuses' => [],
    'availableCycleTypes' => collect(),
    'showClear' => true,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }}>
    {{-- Status Filter Dropdown --}}
    <div class="dropdown" x-data="{ open: false }">
        <button
            type="button"
            class="btn btn-sm btn-outline gap-2"
            @click="open = !open"
        >
            <x-heroicon-o-funnel class="size-4" />
            Status
            @if(count($statusFilter) < count($availableStatuses))
                <span class="badge badge-primary badge-sm">{{ count($statusFilter) }}</span>
            @endif
            <x-heroicon-o-chevron-down class="size-3" x-bind:class="open && 'rotate-180'" />
        </button>

        <div
            class="dropdown-content z-50 mt-2 w-56 bg-base-100 rounded-box shadow-lg border border-base-300 p-2"
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.away="open = false"
        >
            <div class="flex items-center justify-between px-2 pb-2 border-b border-base-200 mb-2">
                <span class="text-xs font-semibold text-base-content/70 uppercase">Assessment Status</span>
                @if(count($statusFilter) < count($availableStatuses))
                    <button
                        type="button"
                        wire:click="selectAllStatuses"
                        class="text-xs text-primary hover:underline"
                        @click.stop
                    >
                        Select All
                    </button>
                @endif
            </div>

            <ul class="space-y-1">
                @foreach($availableStatuses as $status => $label)
                    <li>
                        <label class="flex items-center gap-3 px-2 py-1.5 rounded hover:bg-base-200 cursor-pointer">
                            <input
                                type="checkbox"
                                class="checkbox checkbox-sm checkbox-primary"
                                wire:click="toggleStatus('{{ $status }}')"
                                @checked(in_array($status, $statusFilter))
                                @click.stop
                                @if(in_array($status, $statusFilter) && count($statusFilter) === 1)
                                    disabled
                                    title="At least one status must be selected"
                                @endif
                            />
                            <span class="flex-1 text-sm">{{ $label }}</span>
                            <span class="badge {{ \App\Support\WorkStudioStatus::badgeClass($status) }} badge-xs">{{ $status }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Cycle Type Filter Dropdown --}}
    @if($availableCycleTypes->isNotEmpty())
        <div class="dropdown" x-data="{ open: false }">
            <button
                type="button"
                class="btn btn-sm btn-outline gap-2"
                @click="open = !open"
            >
                <x-heroicon-o-arrow-path class="size-4" />
                Cycle Type
                @if(count($cycleTypeFilter) > 0)
                    <span class="badge badge-secondary badge-sm">{{ count($cycleTypeFilter) }}</span>
                @endif
                <x-heroicon-o-chevron-down class="size-3" x-bind:class="open && 'rotate-180'" />
            </button>

            <div
                class="dropdown-content z-50 mt-2 w-72 bg-base-100 rounded-box shadow-lg border border-base-300 p-2"
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="open = false"
            >
                <div class="flex items-center justify-between px-2 pb-2 border-b border-base-200 mb-2">
                    <span class="text-xs font-semibold text-base-content/70 uppercase">Cycle Type</span>
                    @if(count($cycleTypeFilter) > 0)
                        <button
                            type="button"
                            wire:click="selectAllCycleTypes"
                            class="text-xs text-primary hover:underline"
                            @click.stop
                        >
                            Clear
                        </button>
                    @endif
                </div>

                <div class="max-h-64 overflow-y-auto">
                    <ul class="space-y-1">
                        @foreach($availableCycleTypes as $cycleType)
                            <li>
                                <label class="flex items-center gap-3 px-2 py-1.5 rounded hover:bg-base-200 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="checkbox checkbox-sm checkbox-secondary"
                                        wire:click="toggleCycleType('{{ $cycleType }}')"
                                        @checked(in_array($cycleType, $cycleTypeFilter))
                                        @click.stop
                                    />
                                    <span class="flex-1 text-sm truncate" title="{{ $cycleType }}">{{ $cycleType }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if(count($cycleTypeFilter) === 0)
                    <div class="px-2 pt-2 border-t border-base-200 mt-2">
                        <span class="text-xs text-base-content/50">All cycle types shown</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Active Filter Badges --}}
    <div class="flex flex-wrap items-center gap-2">
        @if(count($statusFilter) < count($availableStatuses))
            <span class="badge badge-outline badge-sm gap-1">
                <x-heroicon-o-funnel class="size-3" />
                {{ count($statusFilter) }} status{{ count($statusFilter) !== 1 ? 'es' : '' }}
            </span>
        @endif

        @if(count($cycleTypeFilter) > 0)
            <span class="badge badge-outline badge-sm gap-1">
                <x-heroicon-o-arrow-path class="size-3" />
                {{ count($cycleTypeFilter) }} cycle{{ count($cycleTypeFilter) !== 1 ? 's' : '' }}
            </span>
        @endif
    </div>

    {{-- Clear All Button --}}
    @if($showClear && (count($statusFilter) < count($availableStatuses) || count($cycleTypeFilter) > 0))
        <button
            type="button"
            wire:click="clearCircuitFilters"
            class="btn btn-ghost btn-sm gap-1"
        >
            <x-heroicon-o-x-mark class="size-4" />
            Clear Filters
        </button>
    @endif
</div>
