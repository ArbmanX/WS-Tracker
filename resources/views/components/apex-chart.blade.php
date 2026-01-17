@props([
    'chartId' => 'chart-' . uniqid(),
    'type' => 'bar',
    'height' => 350,
    'options' => [],
    'series' => [],
])

<div
    x-data="apexChart({
        chartId: '{{ $chartId }}',
        type: '{{ $type }}',
        height: {{ $height }},
        options: {{ Js::from($options) }},
        series: {{ Js::from($series) }}
    })"
    x-init="init()"
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    <div
        id="{{ $chartId }}"
        wire:ignore
        class="min-h-[{{ $height }}px]"
    ></div>
</div>
