@props([
    'sidebar' => false,
])

{{--
    App Logo Component (DaisyUI)

    Displays the application logo with branding.

    Usage:
    <x-app-logo />
    <x-app-logo :sidebar="true" />
--}}

<a href="{{ route('home') }}" class="flex items-center gap-2" {{ $attributes }}>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary text-primary-content">
        <x-app-logo-icon class="size-5 fill-current" />
    </div>
    @if(!$sidebar)
        <span class="font-semibold text-base-content">{{ config('app.name') }}</span>
    @endif
</a>
