@props([
    'data' => null,
    'collapsed' => false,
    'maxHeight' => '400px',
])

@php
    $json = is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $isEmpty = empty($data) || $json === '{}' || $json === '[]' || $json === 'null';
    $uniqueId = 'json-viewer-' . uniqid();
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }}>
    @if($isEmpty)
        <div class="text-base-content/50 italic text-sm p-4">No data available</div>
    @else
        <div class="absolute top-2 right-2 z-10">
            <button
                type="button"
                x-data="{ copied: false }"
                x-on:click="
                    navigator.clipboard.writeText(@js($json));
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
                class="btn btn-ghost btn-xs"
                :class="copied ? 'text-success' : ''"
            >
                <template x-if="!copied">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </template>
                <template x-if="copied">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
            </button>
        </div>

        <div
            class="mockup-code bg-base-200 text-sm overflow-auto"
            style="max-height: {{ $maxHeight }}"
            x-data="{
                collapsed: {{ $collapsed ? 'true' : 'false' }},
                json: @js($data)
            }"
        >
            <pre class="px-4 py-2"><code class="language-json text-base-content">{!! e($json) !!}</code></pre>
        </div>
    @endif
</div>
