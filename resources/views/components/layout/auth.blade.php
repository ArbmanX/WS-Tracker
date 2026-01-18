@props([
    'title' => null,
])

{{--
    Auth Layout

    Simple centered layout for authentication pages:
    - Login
    - Forgot password
    - Reset password
    - Two-factor challenge
    - Email verification
    - Confirm password

    Usage:
    <x-layout.auth title="Log In">
        <!-- Auth form content -->
    </x-layout.auth>
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

    <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name') }}</title>

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
    <div class="flex min-h-screen flex-col items-center justify-center p-6">
        {{-- Logo --}}
        <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2">
            <div class="flex size-10 items-center justify-center rounded-lg bg-primary text-primary-content">
                <x-heroicon-s-bolt class="size-6" />
            </div>
            <span class="text-lg font-bold">{{ config('app.name') }}</span>
        </a>

        {{-- Card Container --}}
        <div class="card bg-base-100 shadow-xl w-full max-w-sm">
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
