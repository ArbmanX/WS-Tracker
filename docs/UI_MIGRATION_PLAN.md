# WS-Tracker UI Migration Plan

> **Goal:** Completely remove Flux UI and replace all views with DaisyUI 5 components
> **Priority:** Full replacement - zero Flux UI remnants
> **Estimated Time:** 3-4 days

---

## Current State Analysis

The codebase currently uses **Flux UI Free** (`livewire/flux`) for all UI components. This needs to be **completely removed** and replaced with **DaisyUI 5** semantic components.

### Flux UI Usage Found

**23 files** contain `flux:` component tags
**5 files** contain `@fluxScripts` directive

---

## Step 1: Remove Flux UI Package

### Composer Removal

```bash
composer remove livewire/flux
```

### Files to Delete After Package Removal

```bash
# Delete Flux view overrides
rm -rf resources/views/flux/

# This removes:
# - resources/views/flux/icon/folder-git-2.blade.php
# - resources/views/flux/icon/chevrons-up-down.blade.php
# - resources/views/flux/icon/layout-grid.blade.php
# - resources/views/flux/icon/book-open-text.blade.php
# - resources/views/flux/navlist/group.blade.php
```

### Update CSS (Remove Flux Import)

**File:** `resources/css/app.css`

```css
/* REMOVE THIS LINE */
@import '../../vendor/livewire/flux/dist/flux.css';
```

---

## Step 2: Files Inventory

### Files to DELETE Completely

| File | Reason |
|------|--------|
| `resources/views/flux/` (entire directory) | Flux UI overrides no longer needed |
| `resources/views/welcome.blade.php` | Replace with new landing page |
| `resources/views/components/placeholder-pattern.blade.php` | Not needed |

### Files to REWRITE Completely

All files below currently use Flux UI and must be completely rewritten with DaisyUI.

#### Layouts (Priority 1)

| File | Flux Components Used |
|------|---------------------|
| `components/layouts/app.blade.php` | `flux:main` |
| `components/layouts/app/sidebar.blade.php` | `flux:sidebar`, `flux:navbar`, `flux:dropdown`, `flux:menu`, `flux:avatar`, `flux:heading`, `flux:text`, `flux:spacer` |
| `components/layouts/app/header.blade.php` | `flux:header`, `flux:sidebar`, `flux:navbar`, `flux:tooltip`, `flux:spacer` |
| `components/layouts/auth.blade.php` | Layout wrapper |
| `components/layouts/auth/card.blade.php` | `@fluxScripts` |
| `components/layouts/auth/simple.blade.php` | `@fluxScripts` |
| `components/layouts/auth/split.blade.php` | `flux:brand`, `@fluxScripts` |

#### Core Components (Priority 2)

| File | Flux Components Used |
|------|---------------------|
| `components/app-logo.blade.php` | `flux:link` |
| `components/auth-header.blade.php` | `flux:heading`, `flux:text` |
| `components/desktop-user-menu.blade.php` | `flux:dropdown`, `flux:profile`, `flux:menu`, `flux:avatar`, `flux:heading`, `flux:text` |
| `components/settings/layout.blade.php` | `flux:heading`, `flux:text`, `flux:separator`, `flux:navlist` |
| `partials/settings-heading.blade.php` | `flux:heading`, `flux:text` |

#### Auth Views (Priority 3)

| File | Flux Components Used |
|------|---------------------|
| `livewire/auth/login.blade.php` | `flux:input`, `flux:checkbox`, `flux:button`, `flux:link` |
| `livewire/auth/register.blade.php` | `flux:input`, `flux:button`, `flux:link` |
| `livewire/auth/forgot-password.blade.php` | `flux:input`, `flux:button`, `flux:link` |
| `livewire/auth/reset-password.blade.php` | `flux:input`, `flux:button` |
| `livewire/auth/verify-email.blade.php` | `flux:button`, `flux:link` |
| `livewire/auth/confirm-password.blade.php` | `flux:input`, `flux:button` |
| `livewire/auth/two-factor-challenge.blade.php` | `flux:input`, `flux:button`, `flux:link` |

#### Settings Views (Priority 4)

| File | Flux Components Used |
|------|---------------------|
| `livewire/settings/profile.blade.php` | `flux:input`, `flux:button` |
| `livewire/settings/password.blade.php` | `flux:input`, `flux:button` |
| `livewire/settings/appearance.blade.php` | `flux:radio`, `flux:button` |
| `livewire/settings/two-factor.blade.php` | `flux:button`, `flux:modal` |
| `livewire/settings/two-factor/recovery-codes.blade.php` | `flux:button` |
| `livewire/settings/delete-user-form.blade.php` | `flux:button`, `flux:modal`, `flux:input` |

#### Pages (Priority 5)

| File | Status |
|------|--------|
| `dashboard.blade.php` | Rewrite with new dashboard |
| `welcome.blade.php` | Delete and create new |

#### Other Files (Keep/Modify)

| File | Action |
|------|--------|
| `components/app-logo-icon.blade.php` | Keep (pure SVG) |
| `components/action-message.blade.php` | Rewrite with DaisyUI alert |
| `components/auth-session-status.blade.php` | Rewrite with DaisyUI alert |
| `partials/head.blade.php` | Update for theme initialization |

---

## Step 3: Flux to DaisyUI Component Mapping

### Form Components

| Flux UI | DaisyUI Replacement |
|---------|---------------------|
| `<flux:input>` | `<input class="input input-bordered">` |
| `<flux:input type="password" viewable>` | Custom password input with toggle |
| `<flux:checkbox>` | `<input type="checkbox" class="checkbox">` |
| `<flux:radio>` | `<input type="radio" class="radio">` |
| `<flux:select>` | `<select class="select select-bordered">` |
| `<flux:textarea>` | `<textarea class="textarea textarea-bordered">` |
| `<flux:field>` | `<div class="form-control">` |
| `<flux:button>` | `<button class="btn">` |
| `<flux:button variant="primary">` | `<button class="btn btn-primary">` |
| `<flux:button variant="danger">` | `<button class="btn btn-error">` |
| `<flux:button variant="ghost">` | `<button class="btn btn-ghost">` |

### Navigation Components

| Flux UI | DaisyUI Replacement |
|---------|---------------------|
| `<flux:navbar>` | `<div class="navbar">` |
| `<flux:navbar.item>` | `<a class="btn btn-ghost">` or menu item |
| `<flux:sidebar>` | `<div class="drawer">` + `<div class="drawer-side">` |
| `<flux:sidebar.item>` | `<li><a>` in menu |
| `<flux:sidebar.group>` | `<li class="menu-title">` |
| `<flux:navlist>` | `<ul class="menu">` |
| `<flux:navlist.item>` | `<li><a>` |

### Display Components

| Flux UI | DaisyUI Replacement |
|---------|---------------------|
| `<flux:heading>` | `<h1-h6 class="text-*">` |
| `<flux:text>` | `<p class="text-base-content">` |
| `<flux:link>` | `<a class="link">` |
| `<flux:separator>` | `<div class="divider">` |
| `<flux:spacer>` | `<div class="flex-1">` |
| `<flux:badge>` | `<span class="badge">` |

### Interactive Components

| Flux UI | DaisyUI Replacement |
|---------|---------------------|
| `<flux:dropdown>` | `<div class="dropdown">` |
| `<flux:menu>` | `<ul class="menu">` in dropdown-content |
| `<flux:menu.item>` | `<li><a>` or `<li><button>` |
| `<flux:menu.separator>` | `<li class="divider">` |
| `<flux:modal>` | `<dialog class="modal">` |
| `<flux:tooltip>` | `<div class="tooltip">` |
| `<flux:avatar>` | `<div class="avatar">` |
| `<flux:profile>` | Custom with avatar + text |

### Layout Components

| Flux UI | DaisyUI Replacement |
|---------|---------------------|
| `<flux:main>` | `<main class="...">` |
| `<flux:header>` | `<header class="navbar">` |
| `<flux:brand>` | Custom logo component |
| `@fluxScripts` | Remove entirely (not needed) |

---

## Step 4: New File Structure

### New Components to Create

```
resources/views/components/
    ui/
        input.blade.php          # Reusable input with label/error
        password-input.blade.php # Password with visibility toggle
        button.blade.php         # Button variants
        modal.blade.php          # Modal wrapper
        dropdown.blade.php       # Dropdown wrapper
        alert.blade.php          # Alert/toast component
        card.blade.php           # Card wrapper
        badge.blade.php          # Badge component
    theme-switcher.blade.php     # Theme dropdown
    sync-status.blade.php        # API sync indicator
    empty-state.blade.php        # Empty state placeholder
    loading.blade.php            # Loading spinner/overlay

resources/views/components/layouts/
    app.blade.php                # Main app layout (drawer-based)
    auth.blade.php               # Auth layout (centered card)
    guest.blade.php              # Guest/public layout
```

### New CSS/JS Files

```
resources/css/
    app.css                      # Main CSS (updated)
    themes/
        ppl-themes.css           # Custom DaisyUI themes

resources/js/
    app.js                       # Main JS (updated)
    theme-switcher.js            # Alpine theme component
```

---

## Step 5: Detailed Rewrites

### 5.1 Head Partial

**File:** `resources/views/partials/head.blade.php`

```blade
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ?? config('app.name', 'WS-Tracker') }}</title>

{{-- Fonts --}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

{{-- Scripts & Styles --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Theme initialization (before page render to prevent flash) --}}
<script>
    (function() {
        const theme = localStorage.getItem('ws-tracker-theme') || 'system';
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const effectiveTheme = theme === 'system' ? (prefersDark ? 'dark' : 'light') : theme;
        document.documentElement.setAttribute('data-theme', effectiveTheme);
    })();
</script>
```

### 5.2 Main App Layout

**File:** `resources/views/components/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="themeSwitcher" :data-theme="effectiveTheme">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-base-200">
    <div class="drawer lg:drawer-open">
        <input id="main-drawer" type="checkbox" class="drawer-toggle" />

        {{-- Main Content Area --}}
        <div class="drawer-content flex flex-col">
            {{-- Top Navbar --}}
            <header class="navbar bg-base-100 border-b border-base-300 sticky top-0 z-30">
                {{-- Mobile menu button --}}
                <div class="flex-none lg:hidden">
                    <label for="main-drawer" class="btn btn-square btn-ghost">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </label>
                </div>

                {{-- Logo (mobile) --}}
                <div class="flex-1 lg:hidden">
                    <a href="{{ route('dashboard') }}" class="btn btn-ghost text-xl">
                        <x-app-logo-icon class="size-6 text-primary" />
                        <span class="font-bold">WS-Tracker</span>
                    </a>
                </div>

                {{-- Spacer (desktop) --}}
                <div class="flex-1 hidden lg:block"></div>

                {{-- Right side actions --}}
                <div class="flex-none flex items-center gap-2">
                    {{-- Search (desktop) --}}
                    <div class="form-control hidden md:block">
                        <input type="text" placeholder="Search..." class="input input-bordered input-sm w-48" />
                    </div>

                    {{-- Sync Status --}}
                    <x-sync-status />

                    {{-- Theme Switcher --}}
                    <x-theme-switcher />

                    {{-- User Menu --}}
                    <x-user-menu />
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>

        {{-- Sidebar --}}
        <div class="drawer-side z-40">
            <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside class="w-64 min-h-screen bg-base-100 border-r border-base-300 flex flex-col">
                {{-- Sidebar Header --}}
                <div class="p-4 border-b border-base-300">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-app-logo-icon class="size-8 text-primary" />
                        <span class="text-xl font-bold text-base-content">WS-Tracker</span>
                    </a>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 p-4">
                    <ul class="menu menu-lg gap-1">
                        <li class="menu-title">Platform</li>
                        <li>
                            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>

                        @can('access-admin-panel')
                        <li>
                            <a href="{{ route('admin.index') }}" @class(['active' => request()->routeIs('admin.*')])>
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Admin Panel
                            </a>
                        </li>
                        @endcan
                    </ul>
                </nav>

                {{-- Sidebar Footer --}}
                <div class="p-4 border-t border-base-300">
                    <div class="flex items-center gap-3">
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-full w-10">
                                <span>{{ auth()->user()->initials() }}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-base-content/60 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    @livewireScripts
</body>
</html>
```

### 5.3 Auth Layout

**File:** `resources/views/components/layouts/auth.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="themeSwitcher" :data-theme="effectiveTheme">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-base-200 flex flex-col">
    {{-- Optional: Top bar with logo and theme switcher --}}
    <header class="navbar bg-base-100 border-b border-base-300">
        <div class="flex-1">
            <a href="{{ route('home') }}" class="btn btn-ghost text-xl">
                <x-app-logo-icon class="size-6 text-primary" />
                <span class="font-bold">WS-Tracker</span>
            </a>
        </div>
        <div class="flex-none">
            <x-theme-switcher />
        </div>
    </header>

    {{-- Main content --}}
    <main class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </main>

    {{-- Footer --}}
    <footer class="footer footer-center p-4 text-base-content/60 text-sm">
        <p>PPL Electric Utilities - Vegetation Management</p>
    </footer>

    @livewireScripts
</body>
</html>
```

### 5.4 Login Page

**File:** `resources/views/livewire/auth/login.blade.php`

```blade
<x-layouts.auth>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            {{-- Header --}}
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-base-content">Log in to your account</h1>
                <p class="text-sm text-base-content/60 mt-1">Enter your email and password below</p>
            </div>

            {{-- Session Status --}}
            @if (session('status'))
                <div class="alert alert-success mb-4">
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{-- Login Form --}}
            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div class="form-control">
                    <label class="label" for="email">
                        <span class="label-text font-medium">Email address</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="input input-bordered w-full @error('email') input-error @enderror"
                        placeholder="email@example.com"
                        required
                        autofocus
                        autocomplete="email"
                    />
                    @error('email')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="form-control">
                    <label class="label" for="password">
                        <span class="label-text font-medium">Password</span>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="label-text-alt link link-primary">
                                Forgot password?
                            </a>
                        @endif
                    </label>
                    <div class="relative" x-data="{ show: false }">
                        <input
                            id="password"
                            :type="show ? 'text' : 'password'"
                            name="password"
                            class="input input-bordered w-full pr-10 @error('password') input-error @enderror"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3"
                            @click="show = !show"
                        >
                            <svg x-show="!show" class="size-5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="show" x-cloak class="size-5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Remember Me --}}
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm" {{ old('remember') ? 'checked' : '' }} />
                        <span class="label-text">Remember me</span>
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary w-full">
                    Log in
                </button>
            </form>

            {{-- Register Link --}}
            @if (Route::has('register'))
                <div class="divider text-xs text-base-content/40">OR</div>
                <p class="text-center text-sm text-base-content/60">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="link link-primary font-medium">Sign up</a>
                </p>
            @endif
        </div>
    </div>
</x-layouts.auth>
```

### 5.5 Theme Switcher Component

**File:** `resources/views/components/theme-switcher.blade.php`

```blade
<div x-data class="dropdown dropdown-end">
    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
        {{-- Sun icon (light themes) --}}
        <svg
            x-show="!$store.theme.isDark"
            class="size-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        {{-- Moon icon (dark themes) --}}
        <svg
            x-show="$store.theme.isDark"
            x-cloak
            class="size-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
    </div>

    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300">
        <li class="menu-title"><span>Theme</span></li>

        {{-- Light Themes --}}
        <li><a @click="$store.theme.set('light')" :class="{ 'active': $store.theme.current === 'light' }">
            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg>
            Light
        </a></li>
        <li><a @click="$store.theme.set('ppl-brand')" :class="{ 'active': $store.theme.current === 'ppl-brand' }">
            <svg class="size-4 text-primary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/></svg>
            PPL Brand
        </a></li>
        <li><a @click="$store.theme.set('corporate')" :class="{ 'active': $store.theme.current === 'corporate' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
            Corporate
        </a></li>

        <li class="menu-title mt-2"><span>Dark</span></li>

        {{-- Dark Themes --}}
        <li><a @click="$store.theme.set('dark')" :class="{ 'active': $store.theme.current === 'dark' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            Dark
        </a></li>
        <li><a @click="$store.theme.set('ppl-brand-dark')" :class="{ 'active': $store.theme.current === 'ppl-brand-dark' }">
            <svg class="size-4 text-primary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/></svg>
            PPL Dark
        </a></li>
        <li><a @click="$store.theme.set('business')" :class="{ 'active': $store.theme.current === 'business' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Business
        </a></li>
        <li><a @click="$store.theme.set('dim')" :class="{ 'active': $store.theme.current === 'dim' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Dim
        </a></li>

        <div class="divider my-1"></div>

        <li><a @click="$store.theme.set('system')" :class="{ 'active': $store.theme.current === 'system' }">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            System
        </a></li>
    </ul>
</div>
```

### 5.6 User Menu Component

**File:** `resources/views/components/user-menu.blade.php`

```blade
<div class="dropdown dropdown-end">
    <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
        <div class="bg-primary text-primary-content rounded-full w-10">
            <span class="text-sm">{{ auth()->user()->initials() }}</span>
        </div>
    </div>

    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-56 border border-base-300">
        {{-- User Info --}}
        <li class="pointer-events-none px-3 py-2">
            <div class="flex items-center gap-3">
                <div class="avatar placeholder">
                    <div class="bg-primary text-primary-content rounded-full w-10">
                        <span>{{ auth()->user()->initials() }}</span>
                    </div>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-medium truncate">{{ auth()->user()->name }}</span>
                    <span class="text-xs text-base-content/60 truncate">{{ auth()->user()->email }}</span>
                </div>
            </div>
        </li>

        <div class="divider my-1"></div>

        {{-- Menu Items --}}
        <li>
            <a href="{{ route('profile.edit') }}">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile
            </a>
        </li>
        <li>
            <a href="{{ route('settings.appearance') ?? route('profile.edit') }}">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Settings
            </a>
        </li>

        @can('access-admin-panel')
        <li>
            <a href="{{ route('admin.index') }}">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                Admin Panel
            </a>
        </li>
        @endcan

        <div class="divider my-1"></div>

        {{-- Logout --}}
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

### 5.7 Theme Switcher JavaScript

**File:** `resources/js/theme-switcher.js`

```javascript
// Alpine.js Theme Store
document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        current: localStorage.getItem('ws-tracker-theme') || 'system',
        darkThemes: ['dark', 'ppl-brand-dark', 'business', 'dim'],

        init() {
            this.apply();

            // Listen for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.current === 'system') {
                    this.apply();
                }
            });
        },

        get isDark() {
            if (this.current === 'system') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
            return this.darkThemes.includes(this.current);
        },

        get effectiveTheme() {
            if (this.current === 'system') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            return this.current;
        },

        set(theme) {
            this.current = theme;
            localStorage.setItem('ws-tracker-theme', theme);
            this.apply();

            // Sync to server if Livewire is available
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('theme-changed', { theme });
            }
        },

        apply() {
            document.documentElement.setAttribute('data-theme', this.effectiveTheme);
        }
    });

    // Initialize on load
    Alpine.store('theme').init();
});

// Also expose for x-data usage
document.addEventListener('alpine:init', () => {
    Alpine.data('themeSwitcher', () => ({
        get effectiveTheme() {
            return Alpine.store('theme').effectiveTheme;
        }
    }));
});
```

### 5.8 Updated app.js

**File:** `resources/js/app.js`

```javascript
import './bootstrap';
import 'livewire-sortable';
import ApexCharts from 'apexcharts';
import './theme-switcher';

// Make ApexCharts globally available
window.ApexCharts = ApexCharts;
```

### 5.9 Updated app.css

**File:** `resources/css/app.css`

```css
@import 'tailwindcss';
@import './themes/ppl-themes.css';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';

@plugin "daisyui" {
  themes: light, dark, ppl-brand, ppl-brand-dark, corporate, business, dim;
}

/* Base layer adjustments */
@layer base {
  /* Ensure borders use theme colors */
  *,
  ::after,
  ::before,
  ::backdrop,
  ::file-selector-button {
    border-color: oklch(var(--bc) / 0.2);
  }

  /* Hide elements with x-cloak until Alpine loads */
  [x-cloak] {
    display: none !important;
  }
}

/* Custom utilities */
@layer utilities {
  /* Scrollbar styling */
  .scrollbar-thin {
    scrollbar-width: thin;
  }

  .scrollbar-thin::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }

  .scrollbar-thin::-webkit-scrollbar-track {
    background: oklch(var(--b2));
  }

  .scrollbar-thin::-webkit-scrollbar-thumb {
    background: oklch(var(--bc) / 0.2);
    border-radius: 3px;
  }

  .scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: oklch(var(--bc) / 0.3);
  }
}
```

---

## Step 6: Migration Execution Order

### Day 1: Foundation

1. **Remove Flux UI package**
   ```bash
   composer remove livewire/flux
   ```

2. **Delete Flux view overrides**
   ```bash
   rm -rf resources/views/flux/
   ```

3. **Create theme CSS file**
   - Create `resources/css/themes/ppl-themes.css`

4. **Update CSS imports**
   - Update `resources/css/app.css` (remove Flux import)

5. **Create theme JS**
   - Create `resources/js/theme-switcher.js`
   - Update `resources/js/app.js`

6. **Update head partial**
   - Rewrite `partials/head.blade.php`

7. **Run build**
   ```bash
   npm run build
   ```

### Day 2: Layouts & Core Components

1. **Create new layouts**
   - `components/layouts/app.blade.php`
   - `components/layouts/auth.blade.php`

2. **Delete old layout variants** (if not needed)
   - `components/layouts/app/sidebar.blade.php`
   - `components/layouts/app/header.blade.php`
   - `components/layouts/auth/card.blade.php`
   - `components/layouts/auth/simple.blade.php`
   - `components/layouts/auth/split.blade.php`

3. **Create core components**
   - `components/theme-switcher.blade.php`
   - `components/user-menu.blade.php`
   - `components/sync-status.blade.php`

4. **Rewrite utility components**
   - `components/app-logo.blade.php`
   - `components/auth-header.blade.php`
   - `components/action-message.blade.php`
   - `components/auth-session-status.blade.php`

### Day 3: Auth & Settings Pages

1. **Rewrite all auth pages**
   - `livewire/auth/login.blade.php`
   - `livewire/auth/register.blade.php`
   - `livewire/auth/forgot-password.blade.php`
   - `livewire/auth/reset-password.blade.php`
   - `livewire/auth/verify-email.blade.php`
   - `livewire/auth/confirm-password.blade.php`
   - `livewire/auth/two-factor-challenge.blade.php`

2. **Rewrite settings pages**
   - `components/settings/layout.blade.php`
   - `partials/settings-heading.blade.php`
   - `livewire/settings/profile.blade.php`
   - `livewire/settings/password.blade.php`
   - `livewire/settings/appearance.blade.php`
   - `livewire/settings/two-factor.blade.php`
   - `livewire/settings/two-factor/recovery-codes.blade.php`
   - `livewire/settings/delete-user-form.blade.php`

### Day 4: Pages & Dashboard

1. **Create new welcome page**
   - Delete old `welcome.blade.php`
   - Create new landing page

2. **Create dashboard**
   - Rewrite `dashboard.blade.php`
   - Create dashboard Livewire components

3. **Final cleanup & testing**
   - Remove any remaining Flux references
   - Test all pages
   - Test all themes
   - Run full test suite

---

## Step 7: Verification Checklist

After migration, verify:

- [ ] `composer show livewire/flux` returns "not installed"
- [ ] `grep -r "flux:" resources/views/` returns no results
- [ ] `grep -r "@fluxScripts" resources/views/` returns no results
- [ ] All themes switch correctly
- [ ] Theme persists across page loads
- [ ] Login flow works
- [ ] Registration flow works
- [ ] Password reset flow works
- [ ] 2FA flow works
- [ ] Settings pages work
- [ ] Dashboard loads
- [ ] No console errors
- [ ] Mobile responsive
- [ ] `php artisan test` passes

---

## Summary

**Files to DELETE:** 6 files (Flux overrides + old welcome)
**Files to REWRITE:** 28 files (all views using Flux)
**Files to CREATE:** ~15 new component files
**Package to REMOVE:** `livewire/flux`

Total estimated time: **3-4 days**
