@props([
    'title' => null,
    'step' => 1,
    'totalSteps' => 5,
])

{{--
    Onboarding Layout

    Simplified centered layout for onboarding wizard:
    - No sidebar
    - Step indicator
    - Centered card container
    - Theme support with FOUC prevention

    Usage:
    <x-layout.onboarding :step="2" :totalSteps="5" title="Theme Selection">
        <!-- Step content -->
    </x-layout.onboarding>
--}}

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data
    :data-theme="localStorage.getItem('ws-theme') || '{{ config('themes.default', 'corporate') }}'"
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title ? $title . ' - ' : '' }}Welcome | {{ config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- FOUC Prevention --}}
    <script>
        (function() {
            const theme = localStorage.getItem('ws-theme') || '{{ config('themes.default', 'corporate') }}';
            let effective = theme;
            if (theme === 'system') {
                effective = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'corporate';
            }
            document.documentElement.setAttribute('data-theme', effective);
        })();
    </script>
</head>
<body class="min-h-screen bg-base-200">
    <div class="flex min-h-screen flex-col items-center justify-center p-4">
        {{-- Logo --}}
        <div class="mb-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <div class="flex size-12 items-center justify-center rounded-lg bg-primary text-primary-content">
                    <x-heroicon-s-bolt class="size-7" />
                </div>
                <span class="text-xl font-bold">{{ config('app.name') }}</span>
            </a>
        </div>

        {{-- Step Indicator --}}
        <x-ui.step-indicator :current="$step" :total="$totalSteps" class="mb-8" />

        {{-- Card Container --}}
        <div class="card bg-base-100 shadow-xl w-full max-w-md">
            <div class="card-body">
                {{ $slot }}
            </div>
        </div>

        {{-- Footer --}}
        <p class="mt-6 text-sm text-base-content/50">
            {{ config('app.name') }} &copy; {{ date('Y') }}
        </p>
    </div>
</body>
</html>
