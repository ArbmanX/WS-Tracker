@props([
    'region' => null,
    'stats' => null,
    'show' => false,
])

@php
    $percentComplete = $stats?->avg_percent_complete ?? 0;
    $totalMiles = $stats?->total_miles ?? 0;
    $milesPlanned = $stats?->miles_planned ?? 0;
    $milesRemaining = $stats?->miles_remaining ?? ($totalMiles - $milesPlanned);
@endphp

{{-- Backdrop --}}
<div
    x-data="{ open: @js($show) }"
    x-show="open"
    x-on:open-panel.window="open = true"
    x-on:close-panel.window="open = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 bg-black/50"
    @click="open = false; $wire.closePanel()"
>
    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed right-0 top-0 h-full w-full max-w-md bg-base-100 shadow-xl overflow-y-auto"
        @click.stop
    >
        @if($region)
            {{-- Header --}}
            <div class="sticky top-0 z-10 flex items-center justify-between bg-base-100 border-b border-base-200 p-4">
                <div class="flex items-center gap-3">
                    <button type="button" class="btn btn-ghost btn-sm btn-square" @click="open = false; $wire.closePanel()">
                        <x-heroicon-o-arrow-left class="size-5" />
                    </button>
                    <div>
                        <h2 class="text-lg font-semibold">{{ $region->name }}</h2>
                        <p class="text-sm text-base-content/60">{{ $region->code }}</p>
                    </div>
                </div>
                <button type="button" class="btn btn-ghost btn-sm btn-square" @click="open = false; $wire.closePanel()">
                    <x-heroicon-o-x-mark class="size-5" />
                </button>
            </div>

            {{-- Content --}}
            <div class="p-4 space-y-6">
                {{-- Circuit Summary --}}
                <div>
                    <h3 class="text-sm font-medium text-base-content/70 mb-3">Circuit Summary</h3>
                    <div class="stats stats-vertical sm:stats-horizontal shadow w-full">
                        <div class="stat py-3 px-4">
                            <div class="stat-title text-xs">Active</div>
                            <div class="stat-value text-xl text-primary">{{ $stats?->active_circuits ?? 0 }}</div>
                        </div>
                        <div class="stat py-3 px-4">
                            <div class="stat-title text-xs">Quality Control</div>
                            <div class="stat-value text-xl text-warning">{{ $stats?->qc_circuits ?? 0 }}</div>
                        </div>
                        <div class="stat py-3 px-4">
                            <div class="stat-title text-xs">Closed</div>
                            <div class="stat-value text-xl text-success">{{ $stats?->closed_circuits ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                {{-- Miles Progress --}}
                <div>
                    <h3 class="text-sm font-medium text-base-content/70 mb-3">Miles Progress</h3>
                    <div class="bg-base-200 rounded-box p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl font-bold {{ $percentComplete >= 75 ? 'text-success' : ($percentComplete >= 50 ? 'text-warning' : 'text-primary') }}">
                                {{ number_format($percentComplete, 1) }}%
                            </span>
                            <span class="text-sm text-base-content/60">complete</span>
                        </div>
                        <progress
                            class="progress h-3 w-full {{ $percentComplete >= 75 ? 'progress-success' : ($percentComplete >= 50 ? 'progress-warning' : 'progress-primary') }}"
                            value="{{ $percentComplete }}"
                            max="100"
                        ></progress>
                        <div class="flex justify-between mt-2 text-sm">
                            <span>
                                <span class="font-semibold">{{ number_format($milesPlanned, 0) }}</span>
                                <span class="text-base-content/60">of {{ number_format($totalMiles, 0) }} mi planned</span>
                            </span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-base-300 grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs text-base-content/60">Remaining</div>
                                <div class="font-semibold">{{ number_format($milesRemaining, 0) }} mi</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">Total</div>
                                <div class="font-semibold">{{ number_format($totalMiles, 0) }} mi</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Units Summary --}}
                <div>
                    <h3 class="text-sm font-medium text-base-content/70 mb-3">Units Summary</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-success/10 rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-success">{{ number_format($stats?->units_approved ?? 0) }}</div>
                            <div class="text-xs text-success/70">Approved</div>
                        </div>
                        <div class="bg-warning/10 rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-warning">{{ number_format($stats?->units_pending ?? 0) }}</div>
                            <div class="text-xs text-warning/70">Pending</div>
                        </div>
                        <div class="bg-error/10 rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-error">{{ number_format($stats?->units_refused ?? 0) }}</div>
                            <div class="text-xs text-error/70">Refused</div>
                        </div>
                    </div>
                    <div class="mt-3 text-center text-sm text-base-content/60">
                        Total: <span class="font-semibold text-base-content">{{ number_format($stats?->total_units ?? 0) }}</span> units
                    </div>
                </div>

                {{-- Planners --}}
                <div>
                    <h3 class="text-sm font-medium text-base-content/70 mb-3">Active Planners</h3>
                    <div class="flex items-center gap-3 bg-base-200 rounded-box p-4">
                        <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <x-heroicon-o-users class="size-6" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ $stats?->active_planners ?? 0 }}</div>
                            <div class="text-sm text-base-content/60">planners working</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="sticky bottom-0 bg-base-100 border-t border-base-200 p-4">
                <button type="button" class="btn btn-primary w-full" disabled>
                    <x-heroicon-o-chart-bar class="size-4" />
                    View Full Analytics (Coming Soon)
                </button>
            </div>
        @else
            <div class="flex items-center justify-center h-full">
                <span class="loading loading-spinner loading-lg text-primary"></span>
            </div>
        @endif
    </div>
</div>
