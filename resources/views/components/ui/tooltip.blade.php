@props([
    'text' => '',
    'position' => 'top',
    'color' => null,
])

@php
    $positionClasses = [
        'top' => 'tooltip-top',
        'bottom' => 'tooltip-bottom',
        'left' => 'tooltip-left',
        'right' => 'tooltip-right',
    ];

    $colorClasses = [
        'primary' => 'tooltip-primary',
        'secondary' => 'tooltip-secondary',
        'accent' => 'tooltip-accent',
        'info' => 'tooltip-info',
        'success' => 'tooltip-success',
        'warning' => 'tooltip-warning',
        'error' => 'tooltip-error',
    ];

    $classes = collect([
        'tooltip',
        $positionClasses[$position] ?? 'tooltip-top',
        $color ? ($colorClasses[$color] ?? null) : null,
    ])->filter()->join(' ');
@endphp

<div
    class="{{ $classes }}"
    data-tip="{{ $text }}"
    {{ $attributes }}
>
    {{ $slot }}
</div>
