@props([
    'heading' => '',
    'subheading' => '',
])

{{--
    Settings Layout Component (DaisyUI)

    Provides sidebar navigation and content area for settings pages.

    Usage:
    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your profile information')">
        <!-- Settings form content -->
    </x-settings.layout>
--}}

<div class="flex flex-col md:flex-row gap-6">
    {{-- Sidebar Navigation --}}
    <div class="w-full md:w-56 shrink-0">
        <ul class="menu bg-base-200 rounded-lg w-full">
            <li>
                <a
                    href="{{ route('profile.edit') }}"
                    wire:navigate
                    class="{{ request()->routeIs('profile.edit') ? 'active' : '' }}"
                >
                    <x-ui.icon name="user" size="md" />
                    {{ __('Profile') }}
                </a>
            </li>
            <li>
                <a
                    href="{{ route('user-password.edit') }}"
                    wire:navigate
                    class="{{ request()->routeIs('user-password.edit') ? 'active' : '' }}"
                >
                    <x-ui.icon name="key" size="md" />
                    {{ __('Password') }}
                </a>
            </li>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <li>
                    <a
                        href="{{ route('two-factor.show') }}"
                        wire:navigate
                        class="{{ request()->routeIs('two-factor.show') ? 'active' : '' }}"
                    >
                        <x-ui.icon name="shield-check" size="md" />
                        {{ __('Two-Factor Auth') }}
                    </a>
                </li>
            @endif
            <li>
                <a
                    href="{{ route('appearance.edit') }}"
                    wire:navigate
                    class="{{ request()->routeIs('appearance.edit') ? 'active' : '' }}"
                >
                    <x-ui.icon name="paint-brush" size="md" />
                    {{ __('Appearance') }}
                </a>
            </li>
        </ul>
    </div>

    {{-- Content Area --}}
    <div class="flex-1 min-w-0">
        @if($heading || $subheading)
            <div class="mb-6">
                @if($heading)
                    <h2 class="text-xl font-semibold text-base-content">{{ $heading }}</h2>
                @endif
                @if($subheading)
                    <p class="text-sm text-base-content/60 mt-1">{{ $subheading }}</p>
                @endif
            </div>
        @endif

        <div class="max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
