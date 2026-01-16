{{-- Theme Switcher Component --}}
{{-- Uses Alpine.js persist plugin (included with Livewire 3) for localStorage persistence --}}

<div x-data="{
    theme: $persist('ppl-light').as('theme'),
    themes: [
        { value: 'light', label: 'Light', icon: '○' },
        { value: 'dark', label: 'Dark', icon: '●' },
        { value: 'ppl-light', label: 'PPL Light', icon: '☀️' },
        { value: 'ppl-dark', label: 'PPL Dark', icon: '🌙' },
        { value: 'cupcake', label: 'Cupcake', icon: '🧁' },
        { value: 'forest', label: 'Forest', icon: '🌲' },
        { value: 'synthwave', label: 'Synthwave', icon: '🌆' },
        { value: 'retro', label: 'Retro', icon: '📺' },
        { value: 'cyberpunk', label: 'Cyberpunk', icon: '🤖' },
        { value: 'dracula', label: 'Dracula', icon: '🧛' },
        { value: 'night', label: 'Night', icon: '🌃' },
        { value: 'winter', label: 'Winter', icon: '❄️' },
        { value: 'emerald', label: 'Emerald', icon: '💎' },
        { value: 'silk', label: 'Silk', icon: '💎' },
        { value: 'autumn', label: 'Autumn', icon: '💎' },
        { value: 'corporate', label: 'Corporate', icon: '💎' },
        { value: 'garden', label: 'Garden', icon: '💎' },
        { value: 'coffee', label: 'Coffee', icon: '💎' }
    ]
}" x-init="$watch('theme', val => document.documentElement.setAttribute('data-theme', val));
document.documentElement.setAttribute('data-theme', theme)">

    <div class="dropdown dropdown-end">
        {{-- Trigger Button --}}
        <label tabindex="0" class="btn btn-ghost btn-circle">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
        </label>

        {{-- Dropdown Content --}}
        <div tabindex="0"
            class="dropdown-content z-50 mt-3 w-56 rounded-box bg-base-100 p-3 shadow-lg border border-base-300">
            <div class="mb-2 text-sm font-semibold text-base-content">Select Theme</div>

            <ul class="menu menu-sm gap-1 p-0">
                <template x-for="t in themes" :key="t.value">
                    <li>
                        <button @click="theme = t.value"
                            :class="{ 'active bg-primary text-primary-content': theme === t.value }"
                            class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <span x-text="t.icon"></span>
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
        </div>
    </div>
</div>
