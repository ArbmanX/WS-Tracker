{{-- Theme Switcher Component --}}
{{-- Uses Alpine.js persist plugin (included with Livewire 3) for localStorage persistence --}}
{{-- Syncs with Livewire to save preference to database --}}

<div x-data="{
    theme: $persist('{{ auth()->user()?->theme_preference ?? 'system' }}').as('theme'),
    themes: [
        { value: 'system', label: 'System', icon: 'computer-desktop', category: 'default' },
        { value: 'light', label: 'Light', icon: 'sun', category: 'default' },
        { value: 'dark', label: 'Dark', icon: 'moon', category: 'default' },
        { value: 'ppl-light', label: 'PPL Light', icon: 'building-office', category: 'ppl' },
        { value: 'ppl-dark', label: 'PPL Dark', icon: 'moon', category: 'ppl' },
        { value: 'corporate', label: 'Corporate', icon: 'briefcase', category: 'other' },
        { value: 'forest', label: 'Forest', icon: 'sparkles', category: 'other' },
        { value: 'dracula', label: 'Dracula', icon: 'moon', category: 'other' },
        { value: 'night', label: 'Night', icon: 'moon', category: 'other' },
        { value: 'winter', label: 'Winter', icon: 'sparkles', category: 'other' },
        { value: 'cupcake', label: 'Cupcake', icon: 'sparkles', category: 'other' }
    ],
    get effectiveTheme() {
        if (this.theme === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return this.theme;
    },
    setTheme(newTheme) {
        this.theme = newTheme;
        document.documentElement.setAttribute('data-theme', this.effectiveTheme);

        // Dispatch to Livewire to save to database
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('theme-changed', { theme: newTheme });
        }
    }
}" x-init="
    $watch('theme', val => document.documentElement.setAttribute('data-theme', val === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : val));
    document.documentElement.setAttribute('data-theme', effectiveTheme);

    // Listen for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (theme === 'system') {
            document.documentElement.setAttribute('data-theme', effectiveTheme);
        }
    });
">

    <div class="dropdown dropdown-end">
        {{-- Trigger Button --}}
        <label tabindex="0" class="btn btn-ghost btn-circle" aria-label="Change theme">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
        </label>

        {{-- Dropdown Content --}}
        <div tabindex="0"
            class="dropdown-content z-50 mt-3 w-56 rounded-box bg-base-100 p-3 shadow-lg border border-base-300">
            <div class="mb-2 text-sm font-semibold text-base-content">{{ __('Select Theme') }}</div>

            {{-- Default Themes --}}
            <ul class="menu menu-sm gap-1 p-0">
                <template x-for="t in themes.filter(t => t.category === 'default')" :key="t.value">
                    <li>
                        <button @click="setTheme(t.value)"
                            :class="{ 'active bg-primary text-primary-content': theme === t.value }"
                            class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <template x-if="t.icon === 'sun'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </template>
                                <template x-if="t.icon === 'moon'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                                </template>
                                <template x-if="t.icon === 'computer-desktop'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </template>
                                <span x-text="t.label"></span>
                            </span>
                            <svg x-show="theme === t.value" xmlns="http://www.w3.org/2000/svg" class="size-4"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </li>
                </template>
            </ul>

            {{-- PPL Themes --}}
            <div class="divider my-2 text-xs text-base-content/50">PPL Brand</div>
            <ul class="menu menu-sm gap-1 p-0">
                <template x-for="t in themes.filter(t => t.category === 'ppl')" :key="t.value">
                    <li>
                        <button @click="setTheme(t.value)"
                            :class="{ 'active bg-primary text-primary-content': theme === t.value }"
                            class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <span x-text="t.label"></span>
                            </span>
                            <svg x-show="theme === t.value" xmlns="http://www.w3.org/2000/svg" class="size-4"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </li>
                </template>
            </ul>

            {{-- Other Themes --}}
            <div class="divider my-2 text-xs text-base-content/50">More Themes</div>
            <ul class="menu menu-sm gap-1 p-0 max-h-32 overflow-y-auto">
                <template x-for="t in themes.filter(t => t.category === 'other')" :key="t.value">
                    <li>
                        <button @click="setTheme(t.value)"
                            :class="{ 'active bg-primary text-primary-content': theme === t.value }"
                            class="flex items-center justify-between">
                            <span x-text="t.label"></span>
                            <svg x-show="theme === t.value" xmlns="http://www.w3.org/2000/svg" class="size-4"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
