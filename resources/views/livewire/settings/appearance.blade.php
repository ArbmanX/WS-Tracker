<section class="w-full" x-data="{
    theme: $wire.entangle('theme'),
    applyTheme(newTheme) {
        if (newTheme === 'system') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
        } else {
            document.documentElement.setAttribute('data-theme', newTheme);
        }
        localStorage.setItem('theme', newTheme);
    }
}" x-init="$watch('theme', val => applyTheme(val))"
@theme-updated.window="applyTheme($event.detail.theme)">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Customize the appearance of the application')">
        <div class="space-y-6">
            {{-- Quick Toggle: Light / Dark / System --}}
            <div>
                <flux:label class="mb-3">{{ __('Color Scheme') }}</flux:label>
                <flux:radio.group variant="segmented" wire:model.live="theme">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </div>

            {{-- PPL Brand Themes --}}
            <div>
                <flux:label class="mb-3">{{ __('PPL Brand Themes') }}</flux:label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        wire:click="$set('theme', 'ppl-light')"
                        class="btn btn-outline {{ $theme === 'ppl-light' ? 'btn-primary' : '' }}"
                    >
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            PPL Light
                        </span>
                        @if($theme === 'ppl-light')
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @endif
                    </button>
                    <button
                        type="button"
                        wire:click="$set('theme', 'ppl-dark')"
                        class="btn btn-outline {{ $theme === 'ppl-dark' ? 'btn-primary' : '' }}"
                    >
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            PPL Dark
                        </span>
                        @if($theme === 'ppl-dark')
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @endif
                    </button>
                </div>
            </div>

            {{-- Additional Themes --}}
            <div>
                <flux:label class="mb-3">{{ __('Additional Themes') }}</flux:label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['corporate' => 'Corporate', 'forest' => 'Forest', 'dracula' => 'Dracula', 'night' => 'Night', 'winter' => 'Winter', 'cupcake' => 'Cupcake'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('theme', '{{ $value }}')"
                            class="btn btn-sm btn-ghost {{ $theme === $value ? 'btn-active' : '' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Theme Preview --}}
            <div class="divider"></div>
            <div>
                <flux:label class="mb-3">{{ __('Preview') }}</flux:label>
                <div class="rounded-box border border-base-300 p-4 bg-base-200">
                    <div class="flex flex-wrap gap-2">
                        <span class="badge badge-primary">Primary</span>
                        <span class="badge badge-secondary">Secondary</span>
                        <span class="badge badge-accent">Accent</span>
                        <span class="badge badge-neutral">Neutral</span>
                        <span class="badge badge-info">Info</span>
                        <span class="badge badge-success">Success</span>
                        <span class="badge badge-warning">Warning</span>
                        <span class="badge badge-error">Error</span>
                    </div>
                    <div class="flex gap-2 mt-3">
                        <button class="btn btn-primary btn-sm">Primary</button>
                        <button class="btn btn-secondary btn-sm">Secondary</button>
                        <button class="btn btn-accent btn-sm">Accent</button>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>
