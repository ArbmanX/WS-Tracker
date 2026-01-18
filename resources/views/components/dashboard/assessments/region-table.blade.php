@props([
    'regions' => collect(),
    'stats' => collect(),
    'sortBy' => 'name',
    'sortDir' => 'asc',
])

@php
    $sortIcon = $sortDir === 'asc' ? 'chevron-up' : 'chevron-down';
@endphp

<div class="overflow-x-auto">
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>
                    <button type="button" class="btn btn-ghost btn-xs gap-1" wire:click="sort('name')">
                        Region
                        @if($sortBy === 'name')
                            <x-dynamic-component :component="'heroicon-o-' . $sortIcon" class="size-3" />
                        @endif
                    </button>
                </th>
                <th class="hidden md:table-cell text-center">
                    <button type="button" class="btn btn-ghost btn-xs gap-1" wire:click="sort('active_circuits')">
                        Active
                        @if($sortBy === 'active_circuits')
                            <x-dynamic-component :component="'heroicon-o-' . $sortIcon" class="size-3" />
                        @endif
                    </button>
                </th>
                <th class="text-right">
                    <button type="button" class="btn btn-ghost btn-xs gap-1" wire:click="sort('total_miles')">
                        Miles
                        @if($sortBy === 'total_miles')
                            <x-dynamic-component :component="'heroicon-o-' . $sortIcon" class="size-3" />
                        @endif
                    </button>
                </th>
                <th class="hidden lg:table-cell text-right">
                    <button type="button" class="btn btn-ghost btn-xs gap-1" wire:click="sort('miles_planned')">
                        Planned
                        @if($sortBy === 'miles_planned')
                            <x-dynamic-component :component="'heroicon-o-' . $sortIcon" class="size-3" />
                        @endif
                    </button>
                </th>
                <th class="text-center">
                    <button type="button" class="btn btn-ghost btn-xs gap-1" wire:click="sort('avg_percent_complete')">
                        % Complete
                        @if($sortBy === 'avg_percent_complete')
                            <x-dynamic-component :component="'heroicon-o-' . $sortIcon" class="size-3" />
                        @endif
                    </button>
                </th>
                <th class="hidden xl:table-cell text-center">Planners</th>
                <th class="w-10"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($regions as $region)
                @php
                    $regionStats = $stats[$region->id] ?? null;
                    $percentComplete = $regionStats?->avg_percent_complete ?? 0;
                @endphp
                <tr class="hover cursor-pointer" wire:click="openPanel({{ $region->id }})">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded bg-primary/10 text-primary">
                                <x-heroicon-o-map-pin class="size-4" />
                            </div>
                            <div>
                                <div class="font-medium">{{ $region->name }}</div>
                                <div class="text-xs text-base-content/60">{{ $region->code }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="hidden md:table-cell text-center">
                        <span class="badge badge-primary badge-sm">{{ $regionStats?->active_circuits ?? 0 }}</span>
                    </td>
                    <td class="text-right font-medium">
                        {{ number_format($regionStats?->total_miles ?? 0, 0) }}
                        <span class="text-xs text-base-content/60">mi</span>
                    </td>
                    <td class="hidden lg:table-cell text-right text-base-content/70">
                        {{ number_format($regionStats?->miles_planned ?? 0, 0) }}
                    </td>
                    <td class="text-center">
                        <div class="flex flex-col items-center gap-1">
                            <span class="font-semibold {{ $percentComplete >= 75 ? 'text-success' : ($percentComplete >= 50 ? 'text-warning' : '') }}">
                                {{ number_format($percentComplete, 0) }}%
                            </span>
                            <progress
                                class="progress w-16 h-1.5 {{ $percentComplete >= 75 ? 'progress-success' : ($percentComplete >= 50 ? 'progress-warning' : 'progress-primary') }}"
                                value="{{ $percentComplete }}"
                                max="100"
                            ></progress>
                        </div>
                    </td>
                    <td class="hidden xl:table-cell text-center">
                        <div class="flex items-center justify-center gap-1">
                            <x-heroicon-o-users class="size-4 text-base-content/50" />
                            <span>{{ $regionStats?->active_planners ?? 0 }}</span>
                        </div>
                    </td>
                    <td>
                        <x-heroicon-o-chevron-right class="size-4 text-base-content/40" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-base-content/60">
                        <x-heroicon-o-map class="size-12 mx-auto mb-2 opacity-30" />
                        <p>No regions found</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
