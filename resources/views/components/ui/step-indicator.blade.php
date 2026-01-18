@props([
    'current' => 1,
    'total' => 5,
])

{{--
    Step Indicator Component

    Shows progress through a multi-step process using DaisyUI steps.

    Usage:
    <x-ui.step-indicator :current="2" :total="5" />
--}}

@php
    $labels = ['Verify', 'Password', 'Theme', 'Preferences', 'Complete'];
@endphp

<ul {{ $attributes->merge(['class' => 'steps steps-horizontal w-full max-w-md']) }}>
    @for($i = 1; $i <= $total; $i++)
        <li class="step {{ $i <= $current ? 'step-primary' : '' }}">
            <span class="hidden sm:inline">{{ $labels[$i - 1] ?? '' }}</span>
        </li>
    @endfor
</ul>
