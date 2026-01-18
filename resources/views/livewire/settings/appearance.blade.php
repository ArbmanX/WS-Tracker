<div
    class="w-full"
    x-data="{
        theme: $wire.entangle('theme'),
        applyTheme(newTheme) {
            let effective = newTheme;
            if (newTheme === 'system') {
                effective = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'corporate';
            }
            document.documentElement.setAttribute('data-theme', effective);
            localStorage.setItem('ws-theme', newTheme);
        }
    }"
    x-init="$watch('theme', val => applyTheme(val))"
    @theme-updated.window="applyTheme($event.detail.theme)"
>
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Customize the appearance of the application')">
        <div class="space-y-6">
            {{-- System Option --}}
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium">{{ __('Color Scheme') }}</span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all
                    {{ $theme === 'system' ? 'border-primary bg-primary/10' : 'border-base-300 hover:border-base-content/20' }}">
                    <input
                        type="radio"
                        wire:model.live="theme"
                        value="system"
                        class="radio radio-primary"
                    />
                    <div>
                        <span class="font-medium">{{ __('Follow System') }}</span>
                        <p class="text-sm text-base-content/60">{{ __('Automatically match your device settings') }}</p>
                    </div>
                </label>
            </div>

            {{-- Quick Toggle: Light / Dark --}}
            <div>
                <label class="label">
                    <span class="label-text font-medium">{{ __('Manual Selection') }}</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 cursor-pointer transition-all
                        {{ $theme === 'light' ? 'border-primary bg-primary/10' : 'border-base-300 hover:border-base-content/20' }}">
                        <x-heroicon-o-sun class="size-8" />
                        <input type="radio" wire:model.live="theme" value="light" class="radio radio-primary" />
                        <span class="text-sm">{{ __('Light') }}</span>
                    </label>
                    <label class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 cursor-pointer transition-all
                        {{ $theme === 'dark' ? 'border-primary bg-primary/10' : 'border-base-300 hover:border-base-content/20' }}">
                        <x-heroicon-o-moon class="size-8" />
                        <input type="radio" wire:model.live="theme" value="dark" class="radio radio-primary" />
                        <span class="text-sm">{{ __('Dark') }}</span>
                    </label>
                </div>
            </div>

            {{-- PPL Brand Themes --}}
            <div>
                <label class="label">
                    <span class="label-text font-medium">{{ __('PPL Brand Themes') }}</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all
                        {{ $theme === 'ppl-light' ? 'border-primary bg-primary/10' : 'border-base-300 hover:border-base-content/20' }}">
                        <input type="radio" wire:model.live="theme" value="ppl-light" class="radio radio-primary" />
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-building-office class="size-5" />
                            <span class="text-sm">PPL Light</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all
                        {{ $theme === 'ppl-dark' ? 'border-primary bg-primary/10' : 'border-base-300 hover:border-base-content/20' }}">
                        <input type="radio" wire:model.live="theme" value="ppl-dark" class="radio radio-primary" />
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-moon class="size-5" />
                            <span class="text-sm">PPL Dark</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Additional Themes --}}
            <div class="collapse collapse-arrow bg-base-200 rounded-lg">
                <input type="checkbox" />
                <div class="collapse-title text-sm font-medium">{{ __('More themes') }}</div>
                <div class="collapse-content">
                    <div class="grid grid-cols-3 gap-2 pt-2">
                        @foreach(['corporate' => 'Corporate', 'forest' => 'Forest', 'dracula' => 'Dracula', 'night' => 'Night', 'winter' => 'Winter', 'synthwave' => 'Synthwave', 'cyberpunk' => 'Cyberpunk', 'cupcake' => 'Cupcake'] as $value => $label)
                            <label class="flex flex-col items-center gap-1 p-2 rounded cursor-pointer transition-all
                                {{ $theme === $value ? 'bg-primary/20 ring-2 ring-primary' : 'hover:bg-base-300' }}">
                                <div class="w-full h-8 rounded overflow-hidden" data-theme="{{ $value }}">
                                    <div class="h-full bg-base-100 flex items-center justify-center gap-0.5">
                                        <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-secondary"></div>
                                    </div>
                                </div>
                                <input type="radio" wire:model.live="theme" value="{{ $value }}" class="hidden" />
                                <span class="text-xs truncate w-full text-center">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Theme Preview --}}
            <div>
                <label class="label">
                    <span class="label-text font-medium">{{ __('Preview') }}</span>
                </label>
                <div class="rounded-lg border border-base-300 p-4 bg-base-200">
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
                        <button type="button" class="btn btn-primary btn-sm">Primary</button>
                        <button type="button" class="btn btn-secondary btn-sm">Secondary</button>
                        <button type="button" class="btn btn-accent btn-sm">Accent</button>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</div>
