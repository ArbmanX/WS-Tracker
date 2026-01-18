@props([
    'name' => '',
    'size' => 'md',
    'variant' => 'outline',
])

@php
    $sizes = [
        'xs' => 'size-3',
        'sm' => 'size-4',
        'md' => 'size-5',
        'lg' => 'size-6',
        'xl' => 'size-8',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // Build the icon component name for Blade's dynamic component
    // Heroicons use the format: heroicon-o-{name} for outline, heroicon-s-{name} for solid
    $prefix = match($variant) {
        'solid' => 'heroicon-s',
        'mini' => 'heroicon-m',
        default => 'heroicon-o',
    };
@endphp

<x-dynamic-component
    :component="$prefix . '-' . $name"
    {{ $attributes->class([$sizeClass]) }}
/>

