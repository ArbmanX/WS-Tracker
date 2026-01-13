# WS-Tracker UI Design System

> **Version:** 1.0
> **Stack:** DaisyUI 5 + Tailwind CSS 4 + Livewire 3 + Flux UI Free
> **Target:** Vegetation maintenance admin dashboard for PPL Electric Utilities

---

## Table of Contents

1. [Theme Configuration](#theme-configuration)
2. [Color System](#color-system)
3. [Typography Scale](#typography-scale)
4. [Spacing System](#spacing-system)
5. [Component Library](#component-library)
   - [Dashboard Header](#1-dashboard-header)
   - [Kanban Board](#2-kanban-board)
   - [Circuit Cards](#3-circuit-cards)
   - [Filter Panel](#4-filter-panel)
   - [Stats Panel](#5-stats-panel)
   - [Charts](#6-charts)
   - [Data Tables](#7-data-tables)
6. [Loading & Empty States](#loading--empty-states)
7. [Responsive Patterns](#responsive-patterns)

---

## Theme Configuration

### DaisyUI 5 Custom Themes in Tailwind CSS 4

In Tailwind CSS 4 with DaisyUI 5, themes are defined in your CSS file using CSS custom properties. Create the following theme file:

**File: `/resources/css/themes/ppl-themes.css`**

```css
/* =============================================================
   WS-TRACKER CUSTOM THEMES
   DaisyUI 5 + Tailwind CSS 4
   ============================================================= */

/* -------------------------------------------------------------
   PPL BRAND THEME (Light)
   Primary: Cyan Cornflower Blue #1882C5
   Secondary: St. Patrick's Blue #28317E
   Accent: Red Damask #E27434
   Neutral: Orient Blue #00598D
   ------------------------------------------------------------- */
[data-theme="ppl-brand"] {
  color-scheme: light;

  /* Base Colors */
  --color-base-100: oklch(100% 0 0);
  --color-base-200: oklch(98% 0.005 230);
  --color-base-300: oklch(95% 0.01 230);
  --color-base-content: oklch(22% 0.02 250);

  /* PPL Primary - Cyan Cornflower Blue #1882C5 */
  --color-primary: oklch(55% 0.13 230);
  --color-primary-content: oklch(98% 0.01 230);

  /* PPL Secondary - St. Patrick's Blue #28317E */
  --color-secondary: oklch(32% 0.14 270);
  --color-secondary-content: oklch(95% 0.02 270);

  /* Asplundh Accent - Red Damask #E27434 */
  --color-accent: oklch(62% 0.18 45);
  --color-accent-content: oklch(98% 0.01 45);

  /* Neutral - Orient Blue #00598D */
  --color-neutral: oklch(40% 0.11 235);
  --color-neutral-content: oklch(95% 0.01 235);

  /* Semantic Colors */
  --color-info: oklch(62% 0.14 230);
  --color-info-content: oklch(98% 0.01 230);

  --color-success: oklch(65% 0.19 145);
  --color-success-content: oklch(98% 0.02 145);

  --color-warning: oklch(78% 0.16 80);
  --color-warning-content: oklch(25% 0.05 80);

  --color-error: oklch(58% 0.22 25);
  --color-error-content: oklch(98% 0.02 25);

  /* Component Sizing */
  --radius-selector: 0.375rem;
  --radius-field: 0.375rem;
  --radius-box: 0.75rem;
  --border: 1px;
  --depth: 1;
  --noise: 0;
}

/* -------------------------------------------------------------
   PPL BRAND DARK THEME
   ------------------------------------------------------------- */
[data-theme="ppl-brand-dark"] {
  color-scheme: dark;

  /* Base Colors - Dark variants */
  --color-base-100: oklch(20% 0.02 235);
  --color-base-200: oklch(18% 0.02 235);
  --color-base-300: oklch(25% 0.02 235);
  --color-base-content: oklch(92% 0.01 230);

  /* PPL Primary - Brighter for dark mode */
  --color-primary: oklch(62% 0.15 230);
  --color-primary-content: oklch(15% 0.02 230);

  /* PPL Secondary */
  --color-secondary: oklch(50% 0.14 270);
  --color-secondary-content: oklch(95% 0.02 270);

  /* Accent */
  --color-accent: oklch(68% 0.18 45);
  --color-accent-content: oklch(15% 0.02 45);

  /* Neutral */
  --color-neutral: oklch(30% 0.08 235);
  --color-neutral-content: oklch(92% 0.01 235);

  /* Semantic Colors - Adjusted for dark */
  --color-info: oklch(68% 0.14 230);
  --color-info-content: oklch(15% 0.02 230);

  --color-success: oklch(70% 0.17 145);
  --color-success-content: oklch(15% 0.02 145);

  --color-warning: oklch(80% 0.16 80);
  --color-warning-content: oklch(20% 0.05 80);

  --color-error: oklch(65% 0.22 25);
  --color-error-content: oklch(15% 0.02 25);

  /* Component Sizing */
  --radius-selector: 0.375rem;
  --radius-field: 0.375rem;
  --radius-box: 0.75rem;
  --border: 1px;
  --depth: 1;
  --noise: 0;
}
```

### Update app.css

**File: `/resources/css/app.css`**

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';
@import './themes/ppl-themes.css';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../vendor/livewire/flux-pro/stubs/**/*.blade.php';
@source '../../vendor/livewire/flux/stubs/**/*.blade.php';

@plugin "daisyui" {
  themes: light, dark, ppl-brand, ppl-brand-dark, corporate, business, dim;
}

@layer base {
  *,
  ::after,
  ::before,
  ::backdrop,
  ::file-selector-button {
    border-color: var(--color-base-300, currentColor);
  }
}

/* Flux UI overrides for DaisyUI compatibility */
[data-flux-field]:not(ui-radio, ui-checkbox) {
  @apply grid gap-2;
}

[data-flux-label] {
  @apply !mb-0 !leading-tight;
}

input:focus[data-flux-control],
textarea:focus[data-flux-control],
select:focus[data-flux-control] {
  @apply outline-hidden ring-2 ring-primary ring-offset-2 ring-offset-base-100;
}
```

---

## Color System

### Semantic Color Usage

| Color | DaisyUI Class | Usage |
|-------|---------------|-------|
| Primary | `bg-primary`, `text-primary`, `btn-primary` | CTAs, links, active states |
| Secondary | `bg-secondary`, `text-secondary`, `btn-secondary` | Headers, emphasis, secondary actions |
| Accent | `bg-accent`, `text-accent`, `btn-accent` | Warnings, highlights, attention |
| Neutral | `bg-neutral`, `text-neutral` | Closed states, disabled, muted |
| Success | `bg-success`, `text-success` | Active status, completion |
| Warning | `bg-warning`, `text-warning` | Pending, caution |
| Info | `bg-info`, `text-info` | QC status, informational |
| Error | `bg-error`, `text-error` | Rework, errors, critical |

### Status-to-Color Mapping

```php
// config/workflow.php
return [
    'statuses' => [
        'active' => [
            'label' => 'Active',
            'badge' => 'badge-success',
            'bg' => 'bg-success/10',
            'border' => 'border-success/30',
            'dot' => 'bg-success',
        ],
        'pending_permissions' => [
            'label' => 'Pending Permissions',
            'badge' => 'badge-warning',
            'bg' => 'bg-warning/10',
            'border' => 'border-warning/30',
            'dot' => 'bg-warning',
        ],
        'qc' => [
            'label' => 'QC',
            'badge' => 'badge-info',
            'bg' => 'bg-info/10',
            'border' => 'border-info/30',
            'dot' => 'bg-info',
        ],
        'rework' => [
            'label' => 'Rework',
            'badge' => 'badge-error',
            'bg' => 'bg-error/10',
            'border' => 'border-error/30',
            'dot' => 'bg-error',
        ],
        'closed' => [
            'label' => 'Closed',
            'badge' => 'badge-neutral',
            'bg' => 'bg-neutral/10',
            'border' => 'border-neutral/30',
            'dot' => 'bg-neutral',
        ],
    ],
    'regions' => [
        'central' => 'badge-primary',
        'lancaster' => 'badge-secondary',
        'lehigh' => 'badge-accent',
        'harrisburg' => 'badge-neutral',
    ],
];
```

---

## Typography Scale

### Tailwind CSS 4 Typography Classes

```html
<!-- Display - Hero headlines -->
<h1 class="text-4xl font-bold tracking-tight text-base-content">Hero Title</h1>

<!-- H1 - Page titles -->
<h1 class="text-3xl font-bold text-base-content">Page Title</h1>

<!-- H2 - Section headers -->
<h2 class="text-2xl font-semibold text-base-content">Section Header</h2>

<!-- H3 - Card titles -->
<h3 class="text-xl font-semibold text-base-content">Card Title</h3>

<!-- H4 - Subsection -->
<h4 class="text-lg font-medium text-base-content">Subsection</h4>

<!-- Body text -->
<p class="text-base text-base-content">Default body text</p>

<!-- Small text -->
<p class="text-sm text-base-content/70">Secondary text</p>

<!-- Tiny/Caption -->
<span class="text-xs text-base-content/50">Caption text</span>

<!-- Monospace (work orders) -->
<span class="font-mono text-sm font-semibold">2025-1930-A</span>
```

---

## Spacing System

DaisyUI uses Tailwind's spacing scale. Use these consistently:

| Token | Size | Usage |
|-------|------|-------|
| `gap-1` / `p-1` | 4px | Tight inline spacing |
| `gap-2` / `p-2` | 8px | Default small gaps |
| `gap-3` / `p-3` | 12px | Card body padding |
| `gap-4` / `p-4` | 16px | Section gaps |
| `gap-6` / `p-6` | 24px | Large section spacing |
| `gap-8` / `p-8` | 32px | Hero spacing |

---

## Component Library

### 1. Dashboard Header

**File: `/resources/views/components/dashboard-header.blade.php`**

```blade
{{-- Dashboard Header with Navigation, Search, Sync Status, Theme, User --}}
<nav class="navbar bg-base-100 border-b border-base-300 sticky top-0 z-50 px-4">
    {{-- Mobile menu toggle --}}
    <div class="flex-none lg:hidden">
        <label for="main-drawer" class="btn btn-ghost btn-square drawer-button">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </label>
    </div>

    {{-- Logo / Brand --}}
    <div class="flex-1 px-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2" wire:navigate>
            <x-app-logo-icon class="size-8 text-primary" />
            <span class="font-bold text-xl hidden sm:inline text-base-content">WS-Tracker</span>
        </a>
    </div>

    {{-- Right side actions --}}
    <div class="flex-none flex items-center gap-2">
        {{-- Global Search --}}
        <div class="form-control hidden md:block">
            <div class="join">
                <input
                    type="text"
                    placeholder="Search circuits..."
                    class="input input-bordered input-sm join-item w-48 lg:w-64 bg-base-200"
                    wire:model.live.debounce.300ms="search"
                />
                <button class="btn btn-sm btn-primary join-item">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Sync Status Indicator --}}
        <x-sync-status-indicator />

        {{-- Theme Switcher --}}
        <x-theme-switcher />

        {{-- User Dropdown --}}
        <x-user-dropdown />
    </div>
</nav>
```

**Sync Status Indicator Component:**

```blade
{{-- /resources/views/components/sync-status-indicator.blade.php --}}
@props(['lastSync' => null, 'status' => 'synced'])

<div class="tooltip tooltip-bottom" data-tip="Last synced: {{ $lastSync ?? 'Never' }}">
    <div @class([
        'flex items-center gap-1.5 px-3 py-1.5 rounded-full',
        'bg-success/10' => $status === 'synced',
        'bg-warning/10' => $status === 'syncing',
        'bg-error/10' => $status === 'error',
    ])>
        {{-- Animated dot --}}
        <span class="relative flex size-2">
            @if($status === 'syncing')
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning opacity-75"></span>
            @endif
            <span @class([
                'relative inline-flex rounded-full size-2',
                'bg-success' => $status === 'synced',
                'bg-warning' => $status === 'syncing',
                'bg-error' => $status === 'error',
            ])></span>
        </span>
        <span @class([
            'text-xs font-medium hidden sm:inline',
            'text-success' => $status === 'synced',
            'text-warning' => $status === 'syncing',
            'text-error' => $status === 'error',
        ])>
            {{ match($status) {
                'synced' => 'Synced',
                'syncing' => 'Syncing...',
                'error' => 'Sync Error',
                default => 'Unknown'
            } }}
        </span>
    </div>
</div>
```

**Theme Switcher Component:**

```blade
{{-- /resources/views/components/theme-switcher.blade.php --}}
<div
    x-data="themeSwitcher"
    class="dropdown dropdown-end"
>
    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
        {{-- Dynamic icon based on theme --}}
        <template x-if="theme === 'light' || theme === 'ppl-brand'">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </template>
        <template x-if="theme === 'dark' || theme === 'ppl-brand-dark' || theme === 'business' || theme === 'dim'">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </template>
        <template x-if="theme === 'system'">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </template>
    </div>

    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300">
        <li class="menu-title"><span>Theme</span></li>

        {{-- Light themes --}}
        <li><a @click="setTheme('light')" :class="{ 'active': theme === 'light' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="5"/></svg>
            Light
        </a></li>
        <li><a @click="setTheme('ppl-brand')" :class="{ 'active': theme === 'ppl-brand' }">
            <svg class="size-4 text-primary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            PPL Brand
        </a></li>
        <li><a @click="setTheme('corporate')" :class="{ 'active': theme === 'corporate' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
            Corporate
        </a></li>

        <li class="menu-title mt-2"><span>Dark</span></li>

        {{-- Dark themes --}}
        <li><a @click="setTheme('dark')" :class="{ 'active': theme === 'dark' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            Dark
        </a></li>
        <li><a @click="setTheme('ppl-brand-dark')" :class="{ 'active': theme === 'ppl-brand-dark' }">
            <svg class="size-4 text-primary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            PPL Dark
        </a></li>
        <li><a @click="setTheme('business')" :class="{ 'active': theme === 'business' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Business
        </a></li>
        <li><a @click="setTheme('dim')" :class="{ 'active': theme === 'dim' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Dim
        </a></li>

        <div class="divider my-1"></div>

        <li><a @click="setTheme('system')" :class="{ 'active': theme === 'system' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            System
        </a></li>
    </ul>
</div>
```

**User Dropdown Component:**

```blade
{{-- /resources/views/components/user-dropdown.blade.php --}}
<div class="dropdown dropdown-end">
    <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
        <div class="bg-primary text-primary-content rounded-full w-10">
            <span class="text-sm">{{ auth()->user()->initials() }}</span>
        </div>
    </div>
    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-56 border border-base-300">
        {{-- User info header --}}
        <li class="pointer-events-none">
            <div class="flex items-center gap-3 py-2">
                <div class="avatar placeholder">
                    <div class="bg-primary text-primary-content rounded-full w-10">
                        <span>{{ auth()->user()->initials() }}</span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <span class="font-medium text-base-content">{{ auth()->user()->name }}</span>
                    <span class="text-xs text-base-content/60">{{ auth()->user()->email }}</span>
                </div>
            </div>
        </li>
        <div class="divider my-1"></div>

        <li><a href="{{ route('profile.edit') }}" wire:navigate>
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Profile
        </a></li>
        <li><a href="{{ route('settings.appearance') }}" wire:navigate>
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Settings
        </a></li>

        @can('access-admin-panel')
        <li><a href="{{ route('admin.index') }}" wire:navigate>
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            Admin Panel
        </a></li>
        @endcan

        <div class="divider my-1"></div>

        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-start text-error">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign Out
                </button>
            </form>
        </li>
    </ul>
</div>
```

---

### 2. Kanban Board

**Workflow Board Container:**

```blade
{{-- /resources/views/livewire/dashboard/workflow-board.blade.php --}}
<div class="flex gap-4 overflow-x-auto pb-4 scrollbar-thin scrollbar-thumb-base-300 scrollbar-track-base-200"
     style="min-height: calc(100vh - 320px);">

    @foreach($columns as $stage => $config)
        <x-workflow-column
            :stage="$stage"
            :config="$config"
            :circuits="$circuits[$stage] ?? collect()"
        />
    @endforeach
</div>
```

**Workflow Column Component:**

```blade
{{-- /resources/views/components/workflow-column.blade.php --}}
@props(['stage', 'config', 'circuits'])

<div class="flex-shrink-0 w-[300px] flex flex-col">
    {{-- Column Header --}}
    <div class="flex items-center justify-between px-3 py-2.5 {{ $config['bg'] }} rounded-t-xl border {{ $config['border'] }}">
        <div class="flex items-center gap-2">
            <span class="size-2.5 rounded-full {{ $config['dot'] }}"></span>
            <h3 class="font-semibold text-sm text-base-content">{{ $config['label'] }}</h3>
            <span class="badge badge-sm badge-ghost">{{ $circuits->count() }}</span>
        </div>

        {{-- Optional column actions --}}
        <div class="dropdown dropdown-end">
            <label tabindex="0" class="btn btn-ghost btn-xs btn-circle">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                </svg>
            </label>
            <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-40">
                <li><a wire:click="expandAll('{{ $stage }}')">Expand All</a></li>
                <li><a wire:click="collapseAll('{{ $stage }}')">Collapse All</a></li>
            </ul>
        </div>
    </div>

    {{-- Column Body - Sortable container --}}
    <div
        class="flex-1 overflow-y-auto p-2 bg-base-200/50 rounded-b-xl border-x border-b border-base-300 space-y-2"
        wire:sortable="updateCircuitOrder"
        wire:sortable-group="{{ $stage }}"
        data-column="{{ $stage }}"
    >
        @forelse($circuits as $circuit)
            <div
                wire:key="circuit-{{ $circuit->id }}"
                wire:sortable.item="{{ $circuit->id }}"
                class="transition-transform duration-200"
            >
                <livewire:dashboard.circuit-card
                    :circuit="$circuit"
                    :key="'card-'.$circuit->id"
                />
            </div>
        @empty
            <x-empty-column :stage="$stage" />
        @endforelse
    </div>
</div>
```

**Empty Column State:**

```blade
{{-- /resources/views/components/empty-column.blade.php --}}
@props(['stage'])

<div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="size-16 rounded-full bg-base-300/50 flex items-center justify-center mb-4">
        <svg class="size-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
    </div>
    <h4 class="font-medium text-base-content/60">No circuits</h4>
    <p class="text-sm text-base-content/40 mt-1">Drag circuits here to move them</p>
</div>
```

---

### 3. Circuit Cards

**Circuit Card Component:**

```blade
{{-- /resources/views/livewire/dashboard/circuit-card.blade.php --}}
<div
    wire:sortable.handle
    class="card bg-base-100 shadow-sm border border-base-300 cursor-grab
           active:cursor-grabbing hover:shadow-md hover:border-primary/30
           transition-all duration-200 group"
    x-data="{ expanded: false }"
>
    <div class="card-body p-3 gap-2">
        {{-- Header Row --}}
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-2">
                {{-- Drag handle indicator --}}
                <svg class="size-4 text-base-content/30 group-hover:text-base-content/50 transition-colors"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 8h16M4 16h16"/>
                </svg>
                <span class="font-mono text-sm font-semibold text-base-content">
                    {{ $circuit->work_order }}{{ $circuit->extension }}
                </span>
            </div>
            <span class="badge badge-sm {{ $this->regionBadgeClass }}">
                {{ $circuit->region->abbreviation ?? $circuit->region->name }}
            </span>
        </div>

        {{-- Title --}}
        <p class="text-xs text-base-content/70 line-clamp-1" title="{{ $circuit->title }}">
            {{ $circuit->title }}
        </p>

        {{-- Progress Bar --}}
        <div>
            <div class="flex justify-between text-xs mb-1">
                <span class="text-base-content/60">Progress</span>
                <span class="font-medium {{ $this->progressTextClass }}">
                    {{ number_format($circuit->percent_complete, 0) }}%
                </span>
            </div>
            <progress
                class="progress h-2 w-full {{ $this->progressBarClass }}"
                value="{{ $circuit->percent_complete }}"
                max="100"
            ></progress>
        </div>

        {{-- Stats Row --}}
        <div class="flex justify-between items-center text-xs text-base-content/60">
            <span class="flex items-center gap-1">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                {{ number_format($circuit->miles_planned, 2) }} / {{ number_format($circuit->total_miles, 2) }} mi
            </span>
            <span class="flex items-center gap-1">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ $circuit->api_modified_date->format('m/d') }}
            </span>
        </div>

        {{-- Footer Row --}}
        <div class="flex justify-between items-center pt-2 border-t border-base-200">
            {{-- Planner Avatars --}}
            @if($circuit->planners->isNotEmpty())
                <div class="avatar-group -space-x-3 rtl:space-x-reverse">
                    @foreach($circuit->planners->take(3) as $planner)
                        <div class="avatar placeholder" title="{{ $planner->name }}">
                            <div class="bg-neutral text-neutral-content rounded-full w-6 h-6">
                                <span class="text-[10px]">{{ $planner->initials }}</span>
                            </div>
                        </div>
                    @endforeach
                    @if($circuit->planners->count() > 3)
                        <div class="avatar placeholder">
                            <div class="bg-base-300 text-base-content rounded-full w-6 h-6">
                                <span class="text-[10px]">+{{ $circuit->planners->count() - 3 }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <span class="text-xs text-base-content/40 italic">No planner</span>
            @endif

            {{-- Split Assessment Indicator --}}
            @if($circuit->children->isNotEmpty())
                <button
                    @click="expanded = !expanded"
                    class="btn btn-ghost btn-xs gap-1"
                >
                    <svg
                        class="size-3 transition-transform duration-200"
                        :class="{ 'rotate-180': expanded }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    {{ $circuit->children->count() }} splits
                </button>
            @endif
        </div>

        {{-- Expandable Split Assessments --}}
        @if($circuit->children->isNotEmpty())
            <div
                x-show="expanded"
                x-collapse
                class="pt-2 space-y-1.5"
            >
                @foreach($circuit->children as $child)
                    <div class="flex justify-between items-center p-2 bg-base-200/50 rounded-lg text-xs">
                        <span class="font-mono font-medium">{{ $child->extension }}</span>
                        <span class="text-base-content/60">
                            {{ number_format($child->miles_planned, 2) }} mi
                        </span>
                        <span class="font-medium">{{ number_format($child->percent_complete, 0) }}%</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
```

**Circuit Card Livewire Component:**

```php
<?php
// app/Livewire/Dashboard/CircuitCard.php

namespace App\Livewire\Dashboard;

use App\Models\Circuit;
use Livewire\Component;
use Livewire\Attributes\Computed;

class CircuitCard extends Component
{
    public Circuit $circuit;

    #[Computed]
    public function regionBadgeClass(): string
    {
        return match(strtolower($this->circuit->region->name)) {
            'central' => 'badge-primary',
            'lancaster' => 'badge-secondary',
            'lehigh' => 'badge-accent',
            'harrisburg' => 'badge-neutral',
            default => 'badge-ghost',
        };
    }

    #[Computed]
    public function progressBarClass(): string
    {
        $percent = $this->circuit->percent_complete;

        return match(true) {
            $percent >= 90 => 'progress-success',
            $percent >= 50 => 'progress-primary',
            $percent >= 25 => 'progress-warning',
            default => 'progress-error',
        };
    }

    #[Computed]
    public function progressTextClass(): string
    {
        $percent = $this->circuit->percent_complete;

        return match(true) {
            $percent >= 90 => 'text-success',
            $percent >= 50 => 'text-primary',
            $percent >= 25 => 'text-warning',
            default => 'text-error',
        };
    }

    public function render()
    {
        return view('livewire.dashboard.circuit-card');
    }
}
```

---

### 4. Filter Panel

```blade
{{-- /resources/views/livewire/dashboard/filter-panel.blade.php --}}
<div class="card bg-base-100 shadow-sm border border-base-300">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-end gap-4">

            {{-- Region Filter --}}
            <div class="form-control w-full sm:w-48">
                <label class="label py-1">
                    <span class="label-text text-xs font-medium uppercase tracking-wide text-base-content/60">Region</span>
                </label>
                <select
                    wire:model.live="filters.region"
                    class="select select-bordered select-sm w-full"
                >
                    <option value="">All Regions</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}">{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Planner Filter --}}
            <div class="form-control w-full sm:w-48">
                <label class="label py-1">
                    <span class="label-text text-xs font-medium uppercase tracking-wide text-base-content/60">Planner</span>
                </label>
                <select
                    wire:model.live="filters.planner"
                    class="select select-bordered select-sm w-full"
                >
                    <option value="">All Planners</option>
                    @foreach($planners as $planner)
                        <option value="{{ $planner->id }}">{{ $planner->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="form-control w-full sm:w-auto">
                <label class="label py-1">
                    <span class="label-text text-xs font-medium uppercase tracking-wide text-base-content/60">Date Range</span>
                </label>
                <div class="join">
                    <input
                        type="date"
                        wire:model.live="filters.dateFrom"
                        class="input input-bordered input-sm join-item w-32"
                        placeholder="From"
                    />
                    <span class="join-item flex items-center px-2 bg-base-200 text-xs">to</span>
                    <input
                        type="date"
                        wire:model.live="filters.dateTo"
                        class="input input-bordered input-sm join-item w-32"
                        placeholder="To"
                    />
                </div>
            </div>

            {{-- Status Chips --}}
            <div class="form-control flex-1 min-w-[200px]">
                <label class="label py-1">
                    <span class="label-text text-xs font-medium uppercase tracking-wide text-base-content/60">Status</span>
                </label>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($this->statusOptions as $key => $config)
                        <label class="cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="filters.statuses"
                                value="{{ $key }}"
                                class="peer hidden"
                            />
                            <span class="badge {{ $config['badge'] }} badge-outline peer-checked:badge-{{ str_replace('badge-', '', $config['badge']) }} transition-colors">
                                {{ $config['label'] }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Reset Button --}}
            <div class="flex items-end">
                <button
                    wire:click="resetFilters"
                    class="btn btn-ghost btn-sm gap-1"
                    @disabled(!$this->hasActiveFilters)
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reset
                </button>
            </div>
        </div>

        {{-- Active Filters Summary --}}
        @if($this->hasActiveFilters)
            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-base-200">
                <span class="text-xs text-base-content/60">Active filters:</span>
                <div class="flex flex-wrap gap-1">
                    @if($filters['region'])
                        <span class="badge badge-sm badge-primary gap-1">
                            Region: {{ $this->selectedRegionName }}
                            <button wire:click="clearFilter('region')" class="hover:text-primary-content/80">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    @endif
                    @if($filters['planner'])
                        <span class="badge badge-sm badge-secondary gap-1">
                            Planner: {{ $this->selectedPlannerName }}
                            <button wire:click="clearFilter('planner')" class="hover:text-secondary-content/80">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    @endif
                    @if($filters['dateFrom'] || $filters['dateTo'])
                        <span class="badge badge-sm badge-accent gap-1">
                            Date: {{ $filters['dateFrom'] ?? '*' }} - {{ $filters['dateTo'] ?? '*' }}
                            <button wire:click="clearFilter('dates')" class="hover:text-accent-content/80">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
```

---

### 5. Stats Panel

```blade
{{-- /resources/views/livewire/dashboard/stats-panel.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

    {{-- Miles Progress Card --}}
    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-medium text-base-content/60 uppercase tracking-wide">
                        Miles Planned
                    </h3>
                    <div class="mt-1">
                        <span class="text-3xl font-bold text-base-content">
                            {{ number_format($stats['milesPlanned'], 1) }}
                        </span>
                        <span class="text-lg text-base-content/60">
                            / {{ number_format($stats['totalMiles'], 1) }} mi
                        </span>
                    </div>
                    <div class="flex items-center gap-1 mt-2">
                        @if($stats['milesPercentChange'] >= 0)
                            <svg class="size-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            <span class="text-sm text-success">+{{ number_format($stats['milesPercentChange'], 1) }}%</span>
                        @else
                            <svg class="size-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                            </svg>
                            <span class="text-sm text-error">{{ number_format($stats['milesPercentChange'], 1) }}%</span>
                        @endif
                        <span class="text-xs text-base-content/50">vs last week</span>
                    </div>
                </div>

                {{-- Radial Progress --}}
                <div
                    class="radial-progress text-primary"
                    style="--value:{{ $stats['milesPercent'] }}; --size:5rem; --thickness:6px;"
                    role="progressbar"
                >
                    <span class="text-lg font-semibold">{{ number_format($stats['milesPercent'], 0) }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Circuits by Region Card --}}
    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body p-4">
            <h3 class="text-xs font-medium text-base-content/60 uppercase tracking-wide">
                Circuits by Region
            </h3>
            <div class="flex items-end gap-3 mt-3 h-20">
                @foreach($stats['circuitsByRegion'] as $region)
                    <div class="flex flex-col items-center gap-1 flex-1">
                        <div
                            class="w-full rounded-t transition-all duration-300 {{ $region['colorClass'] }}"
                            style="height: {{ max(10, $region['percent']) }}%;"
                        ></div>
                        <span class="text-[10px] text-base-content/60 uppercase">{{ $region['abbr'] }}</span>
                        <span class="text-xs font-semibold text-base-content">{{ $region['count'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-base-content/50 mt-3 pt-2 border-t border-base-200">
                Total: {{ $stats['totalCircuits'] }} circuits
            </p>
        </div>
    </div>

    {{-- Overall Progress Card (Gradient) --}}
    <div class="card bg-gradient-to-br from-primary to-secondary text-primary-content shadow-lg">
        <div class="card-body p-4">
            <h3 class="text-xs font-medium opacity-80 uppercase tracking-wide">
                Overall Progress
            </h3>
            <div class="flex items-center justify-between mt-2">
                <div>
                    <span class="text-5xl font-bold">{{ number_format($stats['overallPercent'], 0) }}%</span>
                    <div class="flex items-center gap-1 mt-1">
                        @if($stats['onTrack'])
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm opacity-90">On Track</span>
                        @else
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="text-sm opacity-90">Needs Attention</span>
                        @endif
                    </div>
                </div>
            </div>
            <progress
                class="progress bg-primary-content/20 w-full mt-4"
                value="{{ $stats['overallPercent'] }}"
                max="100"
            ></progress>
            <div class="flex justify-between text-xs opacity-70 mt-1">
                <span>{{ $stats['completedCircuits'] }} completed</span>
                <span>{{ $stats['remainingCircuits'] }} remaining</span>
            </div>
        </div>
    </div>
</div>
```

**DaisyUI Stats Alternative (Simpler):**

```blade
{{-- Alternative using DaisyUI stats component --}}
<div class="stats stats-vertical lg:stats-horizontal shadow-sm border border-base-300 w-full">
    <div class="stat">
        <div class="stat-figure text-primary">
            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
        </div>
        <div class="stat-title">Miles Planned</div>
        <div class="stat-value text-primary">{{ number_format($stats['milesPlanned'], 1) }}</div>
        <div class="stat-desc">of {{ number_format($stats['totalMiles'], 1) }} total miles</div>
    </div>

    <div class="stat">
        <div class="stat-figure text-secondary">
            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div class="stat-title">Active Circuits</div>
        <div class="stat-value text-secondary">{{ $stats['activeCircuits'] }}</div>
        <div class="stat-desc">{{ $stats['totalCircuits'] }} total circuits</div>
    </div>

    <div class="stat">
        <div class="stat-figure text-success">
            <div class="radial-progress text-success" style="--value:{{ $stats['overallPercent'] }};" role="progressbar">
                {{ number_format($stats['overallPercent'], 0) }}%
            </div>
        </div>
        <div class="stat-title">Completion</div>
        <div class="stat-value text-success">{{ number_format($stats['overallPercent'], 0) }}%</div>
        <div class="stat-desc text-success">{{ $stats['onTrack'] ? 'On track' : 'Behind schedule' }}</div>
    </div>
</div>
```

---

### 6. Charts

**ApexCharts Wrapper Component:**

```blade
{{-- /resources/views/components/apex-chart.blade.php --}}
@props([
    'chartId',
    'type' => 'bar',
    'height' => 300,
])

<div
    x-data="apexChart(@js($chartId), @js($type))"
    x-init="init()"
    wire:ignore
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    <div x-ref="chart" style="min-height: {{ $height }}px;"></div>
</div>

@pushOnce('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('apexChart', (chartId, chartType) => ({
        chart: null,

        init() {
            this.renderChart();

            // Listen for Livewire updates
            Livewire.on(`chart-update-${chartId}`, (data) => {
                this.updateChart(data);
            });
        },

        renderChart() {
            const options = this.getOptions();
            this.chart = new ApexCharts(this.$refs.chart, options);
            this.chart.render();
        },

        updateChart(newData) {
            if (this.chart) {
                this.chart.updateOptions(newData);
            }
        },

        getOptions() {
            // Get DaisyUI theme colors
            const style = getComputedStyle(document.documentElement);
            const primary = style.getPropertyValue('--color-primary').trim() || '#1882C5';
            const secondary = style.getPropertyValue('--color-secondary').trim() || '#28317E';
            const accent = style.getPropertyValue('--color-accent').trim() || '#E27434';
            const baseContent = style.getPropertyValue('--color-base-content').trim() || '#1f2937';
            const base200 = style.getPropertyValue('--color-base-200').trim() || '#f3f4f6';

            return {
                chart: {
                    type: chartType,
                    height: {{ $height }},
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    background: 'transparent',
                },
                colors: [primary, secondary, accent, '#10B981', '#F59E0B'],
                theme: {
                    mode: document.documentElement.getAttribute('data-theme')?.includes('dark') ? 'dark' : 'light',
                },
                grid: {
                    borderColor: base200,
                    strokeDashArray: 4,
                },
                xaxis: {
                    labels: {
                        style: { colors: baseContent },
                    },
                },
                yaxis: {
                    labels: {
                        style: { colors: baseContent },
                    },
                },
                legend: {
                    labels: { colors: baseContent },
                },
                tooltip: {
                    theme: document.documentElement.getAttribute('data-theme')?.includes('dark') ? 'dark' : 'light',
                },
                // Default empty data - will be populated by Livewire
                series: [],
            };
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
            }
        },
    }));
});
</script>
@endPushOnce
```

**Miles by Region Bar Chart:**

```blade
{{-- /resources/views/livewire/dashboard/charts/miles-by-region.blade.php --}}
<div class="card bg-base-100 shadow-sm border border-base-300">
    <div class="card-body p-4">
        <h4 class="text-sm font-medium text-base-content mb-3 flex items-center gap-2">
            <svg class="size-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Miles by Region
        </h4>

        <div
            x-data="{ chart: null }"
            x-init="
                chart = new ApexCharts($refs.regionChart, {
                    chart: {
                        type: 'bar',
                        height: 200,
                        toolbar: { show: false },
                        background: 'transparent',
                    },
                    series: [{
                        name: 'Planned',
                        data: @js($regionData->pluck('planned'))
                    }, {
                        name: 'Total',
                        data: @js($regionData->pluck('total'))
                    }],
                    xaxis: {
                        categories: @js($regionData->pluck('name')),
                    },
                    colors: ['oklch(var(--color-primary))', 'oklch(var(--color-base-300))'],
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            columnWidth: '60%',
                        }
                    },
                    dataLabels: { enabled: false },
                    legend: { position: 'top' },
                });
                chart.render();
            "
            wire:ignore
        >
            <div x-ref="regionChart"></div>
        </div>
    </div>
</div>
```

**Permission Status Donut Chart:**

```blade
{{-- /resources/views/livewire/dashboard/charts/permission-status.blade.php --}}
<div class="card bg-base-100 shadow-sm border border-base-300">
    <div class="card-body p-4">
        <h4 class="text-sm font-medium text-base-content mb-3 flex items-center gap-2">
            <svg class="size-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
            </svg>
            Permission Status
        </h4>

        <div
            x-data="{ chart: null }"
            x-init="
                chart = new ApexCharts($refs.permissionChart, {
                    chart: {
                        type: 'donut',
                        height: 200,
                        background: 'transparent',
                    },
                    series: @js($permissionData->pluck('count')),
                    labels: @js($permissionData->pluck('label')),
                    colors: ['oklch(var(--color-success))', 'oklch(var(--color-warning))', 'oklch(var(--color-error))'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        fontSize: '14px',
                                    }
                                }
                            }
                        }
                    },
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false },
                });
                chart.render();
            "
            wire:ignore
        >
            <div x-ref="permissionChart"></div>
        </div>
    </div>
</div>
```

---

### 7. Data Tables

```blade
{{-- /resources/views/components/data-table.blade.php --}}
@props([
    'headers' => [],
    'sortable' => true,
    'currentSort' => null,
    'sortDirection' => 'asc',
])

<div class="overflow-x-auto rounded-xl border border-base-300">
    <table class="table table-zebra">
        <thead class="bg-base-200">
            <tr>
                @foreach($headers as $key => $header)
                    <th @class(['cursor-pointer select-none hover:bg-base-300 transition-colors' => $sortable && ($header['sortable'] ?? true)])>
                        @if($sortable && ($header['sortable'] ?? true))
                            <button
                                wire:click="sortBy('{{ $key }}')"
                                class="flex items-center gap-1 w-full"
                            >
                                <span>{{ $header['label'] }}</span>
                                @if($currentSort === $key)
                                    <svg class="size-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @else
                                    <svg class="size-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                @endif
                            </button>
                        @else
                            {{ $header['label'] }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
```

**Example Table Row:**

```blade
{{-- Example usage --}}
<x-data-table :headers="[
    'work_order' => ['label' => 'Work Order', 'sortable' => true],
    'title' => ['label' => 'Title', 'sortable' => true],
    'region' => ['label' => 'Region', 'sortable' => true],
    'progress' => ['label' => 'Progress', 'sortable' => true],
    'planner' => ['label' => 'Planner', 'sortable' => false],
    'actions' => ['label' => '', 'sortable' => false],
]" :currentSort="$sortField" :sortDirection="$sortDirection">

    @forelse($circuits as $circuit)
        <tr class="hover">
            <td>
                <span class="font-mono font-semibold">{{ $circuit->work_order }}{{ $circuit->extension }}</span>
            </td>
            <td>
                <span class="line-clamp-1 max-w-xs" title="{{ $circuit->title }}">
                    {{ $circuit->title }}
                </span>
            </td>
            <td>
                <span class="badge badge-sm {{ $circuit->region->badge_class }}">
                    {{ $circuit->region->name }}
                </span>
            </td>
            <td>
                <div class="flex items-center gap-2">
                    <progress
                        class="progress progress-primary w-20 h-2"
                        value="{{ $circuit->percent_complete }}"
                        max="100"
                    ></progress>
                    <span class="text-xs font-medium">{{ number_format($circuit->percent_complete, 0) }}%</span>
                </div>
            </td>
            <td>
                @if($circuit->planners->isNotEmpty())
                    <div class="avatar-group -space-x-3">
                        @foreach($circuit->planners->take(2) as $planner)
                            <div class="avatar placeholder tooltip" data-tip="{{ $planner->name }}">
                                <div class="bg-neutral text-neutral-content rounded-full w-6 h-6">
                                    <span class="text-[10px]">{{ $planner->initials }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <span class="text-xs text-base-content/40">-</span>
                @endif
            </td>
            <td>
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-xs btn-circle">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </label>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-40 border border-base-300">
                        <li><a wire:click="viewCircuit({{ $circuit->id }})">View Details</a></li>
                        <li><a wire:click="editCircuit({{ $circuit->id }})">Edit</a></li>
                        @can('change-workflow-stage', $circuit)
                            <li><a wire:click="moveCircuit({{ $circuit->id }})">Move to...</a></li>
                        @endcan
                    </ul>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ count($headers) }}" class="text-center py-12">
                <x-empty-state
                    title="No circuits found"
                    description="Try adjusting your filters or search criteria."
                    icon="document-search"
                />
            </td>
        </tr>
    @endforelse
</x-data-table>

{{-- Pagination --}}
<div class="mt-4">
    {{ $circuits->links() }}
</div>
```

---

## Loading & Empty States

### Skeleton Loaders

```blade
{{-- /resources/views/components/skeletons/circuit-card.blade.php --}}
<div class="card bg-base-100 shadow-sm border border-base-300 animate-pulse">
    <div class="card-body p-3 gap-2">
        <div class="flex justify-between items-start">
            <div class="skeleton h-4 w-24"></div>
            <div class="skeleton h-5 w-16 rounded-full"></div>
        </div>
        <div class="skeleton h-3 w-full"></div>
        <div class="skeleton h-2 w-full mt-2"></div>
        <div class="flex justify-between mt-2">
            <div class="skeleton h-3 w-20"></div>
            <div class="skeleton h-3 w-12"></div>
        </div>
        <div class="flex justify-between mt-2 pt-2 border-t border-base-200">
            <div class="flex -space-x-2">
                <div class="skeleton size-6 rounded-full"></div>
                <div class="skeleton size-6 rounded-full"></div>
            </div>
            <div class="skeleton h-4 w-16"></div>
        </div>
    </div>
</div>
```

```blade
{{-- /resources/views/components/skeletons/stats-card.blade.php --}}
<div class="card bg-base-100 shadow-sm border border-base-300 animate-pulse">
    <div class="card-body p-4">
        <div class="skeleton h-3 w-20 mb-2"></div>
        <div class="skeleton h-8 w-32 mb-2"></div>
        <div class="skeleton h-3 w-24"></div>
    </div>
</div>
```

### Empty States

```blade
{{-- /resources/views/components/empty-state.blade.php --}}
@props([
    'title' => 'No data',
    'description' => '',
    'icon' => 'inbox',
    'action' => null,
    'actionLabel' => 'Take action',
])

<div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="size-20 rounded-full bg-base-200 flex items-center justify-center mb-4">
        @switch($icon)
            @case('inbox')
                <svg class="size-10 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                @break
            @case('search')
                <svg class="size-10 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                @break
            @case('document-search')
                <svg class="size-10 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z"/>
                </svg>
                @break
            @default
                <svg class="size-10 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
        @endswitch
    </div>

    <h3 class="text-lg font-semibold text-base-content/70">{{ $title }}</h3>

    @if($description)
        <p class="text-sm text-base-content/50 mt-2 max-w-md">{{ $description }}</p>
    @endif

    @if($action)
        <button wire:click="{{ $action }}" class="btn btn-primary btn-sm mt-4">
            {{ $actionLabel }}
        </button>
    @endif
</div>
```

### Loading Indicator

```blade
{{-- /resources/views/components/loading-overlay.blade.php --}}
<div
    wire:loading.flex
    wire:target="{{ $target ?? '' }}"
    class="absolute inset-0 bg-base-100/80 backdrop-blur-sm z-50 items-center justify-center"
>
    <span class="loading loading-spinner loading-lg text-primary"></span>
</div>
```

---

## Responsive Patterns

### Breakpoint Reference

| Breakpoint | Width | Description |
|------------|-------|-------------|
| `sm` | 640px | Small tablets, large phones |
| `md` | 768px | Tablets |
| `lg` | 1024px | Laptops |
| `xl` | 1280px | Desktops |
| `2xl` | 1536px | Large screens |

### Common Responsive Patterns

```blade
{{-- Stack on mobile, row on desktop --}}
<div class="flex flex-col lg:flex-row gap-4">
    <!-- content -->
</div>

{{-- Hide on mobile, show on desktop --}}
<div class="hidden md:block">
    <!-- Desktop only -->
</div>
<div class="md:hidden">
    <!-- Mobile only -->
</div>

{{-- Grid columns responsive --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <!-- Cards -->
</div>

{{-- Kanban board horizontal scroll on mobile --}}
<div class="flex gap-4 overflow-x-auto snap-x snap-mandatory lg:snap-none pb-4">
    <div class="snap-center shrink-0 w-[85vw] sm:w-[300px]">
        <!-- Column -->
    </div>
</div>

{{-- Responsive text sizes --}}
<h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold">Responsive Heading</h1>
```

### Mobile-First Dashboard Layout

```blade
{{-- /resources/views/livewire/dashboard/circuit-dashboard.blade.php --}}
<div class="min-h-screen bg-base-200">
    {{-- Header is in layout --}}

    <div class="p-4 lg:p-6 space-y-4 lg:space-y-6">
        {{-- Page Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-base-content">Circuit Dashboard</h1>
                <p class="text-sm text-base-content/60 mt-1">Manage and track vegetation maintenance circuits</p>
            </div>

            {{-- Mobile search (hidden on desktop where it's in header) --}}
            <div class="md:hidden">
                <input
                    type="text"
                    placeholder="Search circuits..."
                    wire:model.live.debounce.300ms="search"
                    class="input input-bordered w-full"
                />
            </div>
        </div>

        {{-- Stats Panel --}}
        <livewire:dashboard.stats-panel />

        {{-- Filter Panel --}}
        <livewire:dashboard.filter-panel />

        {{-- View Toggle (Mobile: List, Desktop: Kanban) --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm text-base-content/60">View:</span>
                <div class="join">
                    <button
                        wire:click="$set('viewMode', 'kanban')"
                        @class([
                            'btn btn-sm join-item',
                            'btn-active' => $viewMode === 'kanban',
                        ])
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        <span class="hidden sm:inline">Kanban</span>
                    </button>
                    <button
                        wire:click="$set('viewMode', 'table')"
                        @class([
                            'btn btn-sm join-item',
                            'btn-active' => $viewMode === 'table',
                        ])
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <span class="hidden sm:inline">Table</span>
                    </button>
                </div>
            </div>

            <span class="text-sm text-base-content/60">
                {{ $this->totalCircuits }} circuits
            </span>
        </div>

        {{-- Main Content Area --}}
        <div class="relative">
            {{-- Loading overlay --}}
            <x-loading-overlay target="filters, search" />

            @if($viewMode === 'kanban')
                <livewire:dashboard.workflow-board :circuits="$circuits" />
            @else
                <livewire:dashboard.circuit-table :circuits="$circuits" />
            @endif
        </div>

        {{-- Charts Panel (Collapsible) --}}
        <div class="collapse collapse-arrow bg-base-100 border border-base-300 rounded-xl">
            <input type="checkbox" />
            <div class="collapse-title font-medium flex items-center gap-2">
                <svg class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Charts & Analytics
            </div>
            <div class="collapse-content">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-4">
                    <livewire:dashboard.charts.miles-by-region />
                    <livewire:dashboard.charts.planner-progress />
                    <livewire:dashboard.charts.permission-status />
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## Quick Reference: DaisyUI Component Classes

### Buttons

```html
<!-- Variants -->
<button class="btn">Default</button>
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-accent">Accent</button>
<button class="btn btn-neutral">Neutral</button>
<button class="btn btn-ghost">Ghost</button>
<button class="btn btn-link">Link</button>

<!-- Sizes -->
<button class="btn btn-xs">Extra Small</button>
<button class="btn btn-sm">Small</button>
<button class="btn btn-md">Medium</button>
<button class="btn btn-lg">Large</button>

<!-- States -->
<button class="btn btn-primary" disabled>Disabled</button>
<button class="btn btn-primary loading">Loading</button>
<button class="btn btn-outline btn-primary">Outline</button>
```

### Badges

```html
<span class="badge">Default</span>
<span class="badge badge-primary">Primary</span>
<span class="badge badge-secondary">Secondary</span>
<span class="badge badge-accent">Accent</span>
<span class="badge badge-neutral">Neutral</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-error">Error</span>
<span class="badge badge-info">Info</span>
<span class="badge badge-ghost">Ghost</span>
<span class="badge badge-outline">Outline</span>

<!-- Sizes -->
<span class="badge badge-sm">Small</span>
<span class="badge badge-md">Medium</span>
<span class="badge badge-lg">Large</span>
```

### Cards

```html
<div class="card bg-base-100 shadow-sm border border-base-300">
    <div class="card-body">
        <h2 class="card-title">Title</h2>
        <p>Content</p>
        <div class="card-actions justify-end">
            <button class="btn btn-primary">Action</button>
        </div>
    </div>
</div>
```

### Progress

```html
<!-- Default -->
<progress class="progress w-full" value="70" max="100"></progress>

<!-- Colored -->
<progress class="progress progress-primary w-full" value="70" max="100"></progress>
<progress class="progress progress-success w-full" value="70" max="100"></progress>
<progress class="progress progress-warning w-full" value="70" max="100"></progress>
<progress class="progress progress-error w-full" value="70" max="100"></progress>

<!-- Radial -->
<div class="radial-progress text-primary" style="--value:70;">70%</div>
```

### Avatars

```html
<!-- Avatar Group -->
<div class="avatar-group -space-x-3 rtl:space-x-reverse">
    <div class="avatar">
        <div class="w-8">
            <img src="..." />
        </div>
    </div>
    <div class="avatar placeholder">
        <div class="bg-primary text-primary-content rounded-full w-8">
            <span>JD</span>
        </div>
    </div>
</div>
```

### Alerts

```html
<div class="alert">Default</div>
<div class="alert alert-info">Info</div>
<div class="alert alert-success">Success</div>
<div class="alert alert-warning">Warning</div>
<div class="alert alert-error">Error</div>
```

---

## Theme Switcher JavaScript

**File: `/resources/js/theme-switcher.js`**

```javascript
// Theme Switcher Alpine Component
document.addEventListener('alpine:init', () => {
    Alpine.data('themeSwitcher', () => ({
        theme: localStorage.getItem('ws-tracker-theme') || 'system',
        themes: ['light', 'dark', 'ppl-brand', 'ppl-brand-dark', 'corporate', 'business', 'dim'],

        init() {
            this.applyTheme();

            // Listen for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.theme === 'system') {
                    this.applyTheme();
                }
            });
        },

        setTheme(newTheme) {
            this.theme = newTheme;
            localStorage.setItem('ws-tracker-theme', newTheme);
            this.applyTheme();

            // Dispatch event for Livewire to sync to server
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('theme-changed', { theme: newTheme });
            }

            // Update charts if ApexCharts is loaded
            this.updateChartTheme();
        },

        applyTheme() {
            const html = document.documentElement;
            let effectiveTheme;

            if (this.theme === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                effectiveTheme = prefersDark ? 'dark' : 'light';
            } else {
                effectiveTheme = this.theme;
            }

            html.setAttribute('data-theme', effectiveTheme);
        },

        get effectiveTheme() {
            if (this.theme === 'system') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            return this.theme;
        },

        get isDark() {
            const dark = ['dark', 'ppl-brand-dark', 'business', 'dim'];
            return dark.includes(this.effectiveTheme);
        },

        updateChartTheme() {
            // Update ApexCharts theme if charts exist
            if (typeof ApexCharts !== 'undefined') {
                const charts = document.querySelectorAll('[x-ref$="Chart"]');
                charts.forEach(chart => {
                    // Charts will be re-rendered by their Alpine components
                });
            }
        }
    }));
});
```

**Import in app.js:**

```javascript
// resources/js/app.js
import './bootstrap';
import 'livewire-sortable';
import ApexCharts from 'apexcharts';
import './theme-switcher';

window.ApexCharts = ApexCharts;
```

---

## Summary

This design system provides:

1. **Custom PPL Brand Themes** - Light and dark variants using DaisyUI 5 CSS variables in OKLCH format
2. **Semantic Color Usage** - All colors reference DaisyUI semantic colors (primary, secondary, accent, etc.)
3. **Complete Component Library** - Headers, Kanban boards, cards, filters, stats, charts, and tables
4. **Responsive Patterns** - Mobile-first design with breakpoint-aware layouts
5. **Loading States** - Skeleton loaders and loading overlays
6. **Empty States** - Contextual empty state components
7. **Theme Switching** - Alpine.js-based theme switcher with system preference detection

All components use **only DaisyUI semantic colors** (never direct Tailwind colors like `blue-500`), ensuring theme compatibility across light, dark, and brand themes.
