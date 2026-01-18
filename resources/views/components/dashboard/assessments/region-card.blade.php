@props([
    'region' => null,
    'stats' => null,
])

@php
    $percentComplete = $stats?->avg_percent_complete ?? 0;
    $totalMiles = $stats?->total_miles ?? 0;
    $milesPlanned = $stats?->miles_planned ?? 0;
    $milesRemaining = $stats?->miles_remaining ?? ($totalMiles - $milesPlanned);
@endphp

<div
    {{ $attributes->merge(['class' => 'card bg-base-100 shadow hover:shadow-lg transition-shadow cursor-pointer']) }}
    wire:click="openPanel({{ $region?->id }})"
>
    <div class="card-body p-4 gap-3">
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <x-heroicon-o-map-pin class="size-5" />
            </div>
            <div>
                <h3 class="card-title text-base">{{ $region?->name ?? 'Unknown' }}</h3>
                <p class="text-xs text-base-content/60">{{ $region?->code ?? '' }}</p>
            </div>
        </div>

        {{-- Metrics Row --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-base-200/50 p-2.5">
                <x-ui.metric-pill
                    :value="number_format($stats?->active_circuits ?? 0)"
                    label="circuits"
                    icon="bolt"
                    color="primary"
                />
            </div>
            <div class="rounded-lg bg-base-200/50 p-2.5">
                <x-ui.metric-pill
                    :value="number_format($totalMiles, 0)"
                    label="mi total"
                    icon="map"
                />
            </div>
        </div>

        {{-- Progress --}}
        <div class="space-y-1.5">
            <div class="flex items-center justify-between text-sm">
                <span class="text-base-content/70">Progress</span>
                <span class="font-semibold {{ $percentComplete >= 75 ? 'text-success' : ($percentComplete >= 50 ? 'text-warning' : '') }}">
                    {{ number_format($percentComplete, 0) }}%
                </span>
            </div>
            <progress
                class="progress h-2 {{ $percentComplete >= 75 ? 'progress-success' : ($percentComplete >= 50 ? 'progress-warning' : 'progress-primary') }}"
                value="{{ $percentComplete }}"
                max="100"
            ></progress>
            <div class="flex justify-between text-xs text-base-content/60">
                <span>{{ number_format($milesPlanned, 0) }} mi planned</span>
                <span>{{ number_format($milesRemaining, 0) }} mi remaining</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between pt-2 border-t border-base-200">
            <div class="flex items-center gap-3">
                <x-ui.metric-pill :value="$stats?->active_planners ?? 0" label="planners" icon="users" />
                <span class="text-base-content/30">•</span>
                <x-ui.metric-pill :value="number_format($stats?->total_units ?? 0)" label="units" />
            </div>
        </div>
    </div>
</div>
