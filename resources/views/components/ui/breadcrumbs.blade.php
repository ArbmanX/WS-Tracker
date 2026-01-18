@props([
    'items' => [],
])

{{--
    Breadcrumbs Component

    Usage:
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Assessments', 'route' => 'assessments.overview'],
        ['label' => 'Lehigh Region'],  // No route = current page
    ]" />

    Or with icons:
    <x-ui.breadcrumbs :items="[
        ['label' => 'Home', 'route' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Settings'],
    ]" />
--}}

<div {{ $attributes->merge(['class' => 'breadcrumbs text-sm']) }}>
    <ul>
        @foreach($items as $index => $item)
            <li>
                @php
                    $isLast = $index === count($items) - 1;
                    $hasRoute = isset($item['route']) && !$isLast;
                    $icon = $item['icon'] ?? null;
                @endphp

                @if($hasRoute)
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" wire:navigate class="inline-flex items-center gap-1.5">
                        @if($icon)
                            <x-ui.icon :name="$icon" size="sm" />
                        @endif
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="inline-flex items-center gap-1.5">
                        @if($icon)
                            <x-ui.icon :name="$icon" size="sm" />
                        @endif
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
