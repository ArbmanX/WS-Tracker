<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="themeManager"
    x-init="initTheme()"
    :data-theme="effectiveTheme"
>
    <head>
        @include('partials.head')
        <script>
            // FOUC prevention - runs before Alpine loads
            (function() {
                const savedTheme = localStorage.getItem('theme') || '{{ auth()->user()?->theme_preference ?? 'system' }}';
                let theme = savedTheme;
                if (savedTheme === 'system') {
                    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                console.log('Setting theme to:', theme);
                document.documentElement.setAttribute('data-theme', theme);
            })();
        </script>
        <style>
            /* Kanban & Dashboard Animations */
            .kanban-card {
                transition: all 0.2s ease;
                cursor: grab;
            }
            .kanban-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
            }
            .kanban-card:active {
                cursor: grabbing;
            }
            .kanban-column {
                min-height: calc(100vh - 280px);
            }
            .metric-card {
                transition: all 0.2s ease;
            }
            .metric-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1);
            }
            .stat-pill:hover {
                border-color: var(--color-primary);
            }
            .sidebar-item {
                transition: all 0.15s ease;
            }
            .sidebar-item:hover {
                padding-left: 1.25rem;
            }
            /* Smooth page transitions */
            .page-content {
                animation: fadeIn 0.3s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(4px); }
                to { opacity: 1; transform: translateY(0); }
            }
            /* Progress bar animation */
            .progress-animate {
                animation: progressFill 1s ease-out;
            }
            @keyframes progressFill {
                from { width: 0%; }
            }
            /* Custom scrollbar for kanban */
            .kanban-scroll::-webkit-scrollbar {
                height: 8px;
            }
            .kanban-scroll::-webkit-scrollbar-track {
                background: oklch(var(--b2));
                border-radius: 4px;
            }
            .kanban-scroll::-webkit-scrollbar-thumb {
                background: oklch(var(--bc) / 0.2);
                border-radius: 4px;
            }
            .kanban-scroll::-webkit-scrollbar-thumb:hover {
                background: oklch(var(--bc) / 0.3);
            }
        </style>
    </head>
    <body class="min-h-screen bg-base-100">
        {{-- Global theme listener for syncing preferences to database --}}
        @auth
            <livewire:theme-listener />
        @endauth

        <div class="drawer lg:drawer-open">
            <input id="dashboard-drawer" type="checkbox" class="drawer-toggle" />

            {{-- Main Content Area --}}
            <div class="drawer-content flex flex-col">
                {{-- Top Navbar --}}
                <div class="navbar bg-base-100 border-b border-base-200 sticky top-0 z-30 px-4 lg:px-6">
                    {{-- Mobile Menu Toggle --}}
                    <div class="flex-none lg:hidden">
                        <label for="dashboard-drawer" class="btn btn-square btn-ghost" aria-label="Open menu">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-6 w-6 stroke-current">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </label>
                    </div>

                    {{-- Page Title --}}
                    <div class="flex-1 px-2">
                        <h1 class="text-xl font-semibold text-base-content">{{ $title ?? 'Dashboard' }}</h1>
                    </div>

                    {{-- Navbar Actions --}}
                    <div class="flex gap-2">
                        
                        {{-- Search (Desktop) --}}
                        <div class="form-control hidden md:block">
                            <div class="join">
                                <input type="text" placeholder="Search circuits..." class="input input-bordered input-sm join-item w-48 focus:w-64 transition-all" />
                                <button class="btn btn-sm btn-primary join-item" aria-label="Search">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Sync Status --}}
                        <div class="tooltip tooltip-bottom" data-tip="Data syncs automatically every 30 minutes">
                            <button class="btn btn-ghost btn-circle btn-sm" aria-label="Sync status">
                                <div class="indicator">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span class="badge badge-xs badge-success indicator-item animate-pulse"></span>
                                </div>
                            </button>
                        </div>

                        {{-- Notifications --}}
                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="btn btn-ghost btn-circle btn-sm" aria-label="Notifications">
                                <div class="indicator">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <span class="badge badge-sm badge-primary indicator-item">3</span>
                                </div>
                            </div>
                            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-80 p-2 shadow-lg border border-base-200 mt-2">
                                <li class="menu-title px-2 py-1">
                                    <span class="text-sm font-semibold">Notifications</span>
                                </li>
                                <li>
                                    <a class="flex gap-3 py-3">
                                        <div class="badge badge-warning badge-sm flex-shrink-0">QC</div>
                                        <span class="flex-1 text-sm">Circuit 2025-0142 moved to QC Review</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="flex gap-3 py-3">
                                        <div class="badge badge-success badge-sm flex-shrink-0">Sync</div>
                                        <span class="flex-1 text-sm">Data sync completed successfully</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="flex gap-3 py-3">
                                        <div class="badge badge-info badge-sm flex-shrink-0">New</div>
                                        <span class="flex-1 text-sm">3 new circuits added to Harrisburg</span>
                                    </a>
                                </li>
                                <li class="border-t border-base-200 mt-2 pt-2">
                                    <a class="text-primary text-sm justify-center">View all notifications</a>
                                </li>
                            </ul>
                        </div>

                        {{-- Theme Picker --}}
                        <x-utils.color-changer />

                        {{-- User Menu --}}
                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar" aria-label="User menu">
                                <div class="w-9 rounded-full bg-primary text-primary-content flex items-center justify-center ring ring-primary ring-offset-base-100 ring-offset-1">
                                    <span class="text-sm font-medium">{{ auth()->user()->initials() }}</span>
                                </div>
                            </div>
                            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-56 p-2 shadow-lg border border-base-200 mt-2">
                                <li class="menu-title px-2">
                                    <div class="flex flex-col">
                                        <span class="font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="text-xs font-normal text-base-content/60">{{ auth()->user()->email }}</span>
                                    </div>
                                </li>
                                <li class="border-t border-base-200 mt-2 pt-2">
                                    <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Profile
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('appearance.edit') }}" wire:navigate class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Settings
                                    </a>
                                </li>
                                <li class="border-t border-base-200 mt-2 pt-2">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex items-center gap-2 text-error w-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Log Out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Page Content --}}
                <main class="flex-1 page-content">
                    {{ $slot }}
                </main>
            </div>

            {{-- Sidebar --}}
            <div class="drawer-side z-40">
                <label for="dashboard-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
                <aside class="bg-base-100 w-64 min-h-full border-r border-base-200 flex flex-col">
                    {{-- Logo --}}
                    <div class="p-4 border-b border-base-200">
                        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L8 8h3v4H8l4 6 4-6h-3V8h3L12 2z"/>
                                    <path d="M12 18v4"/>
                                    <path d="M8 22h8"/>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-lg text-base-content">WS-Tracker</span>
                                <p class="text-xs text-base-content/60">Vegetation Management</p>
                            </div>
                        </a>
                    </div>

                    {{-- Navigation --}}
                    <nav class="flex-1 p-4">
                        <ul class="menu gap-1">
                            <li class="menu-title text-xs uppercase tracking-wider text-base-content/50">
                                <span>Main</span>
                            </li>
                            <li>
                                <a href="{{ route('dashboard') }}" wire:navigate
                                   class="sidebar-item flex items-center gap-3 {{ request()->routeIs('dashboard') ? 'active bg-primary/10 text-primary font-medium' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                    </svg>
                                    Kanban Board
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('dashboard') }}?view=analytics" wire:navigate
                                   class="sidebar-item flex items-center gap-3 {{ request()->query('view') === 'analytics' ? 'active bg-primary/10 text-primary font-medium' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Analytics
                                </a>
                            </li>

                            <li class="menu-title text-xs uppercase tracking-wider text-base-content/50 mt-4">
                                <span>Views</span>
                            </li>
                            <li>
                                <a class="sidebar-item flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                                    </svg>
                                    Regions
                                    <span class="badge badge-sm badge-ghost ml-auto">4</span>
                                </a>
                            </li>
                            <li>
                                <a class="sidebar-item flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    All Circuits
                                    <span class="badge badge-sm badge-ghost ml-auto">148</span>
                                </a>
                            </li>
                            <li>
                                <a class="sidebar-item flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Planners
                                    <span class="badge badge-sm badge-ghost ml-auto">12</span>
                                </a>
                            </li>

                            <li class="menu-title text-xs uppercase tracking-wider text-base-content/50 mt-4">
                                <span>Admin</span>
                            </li>
                            <li>
                                <a class="sidebar-item flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Sync Control
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}" wire:navigate class="sidebar-item flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Settings
                                </a>
                            </li>
                        </ul>
                    </nav>

                    {{-- Sidebar Footer --}}
                    <div class="p-4 border-t border-base-200">
                        {{-- Sync Status Card --}}
                        <div class="rounded-lg bg-base-200/50 p-3 mb-3">
                            <div class="flex items-center gap-2 text-xs">
                                <div class="w-2 h-2 rounded-full bg-success animate-pulse"></div>
                                <span class="text-base-content/70">Last sync: 5 min ago</span>
                            </div>
                            <div class="text-xs text-base-content/50 mt-1">Next: in 25 minutes</div>
                        </div>

                        {{-- Version --}}
                        <div class="flex items-center justify-between text-xs text-base-content/50">
                            <span>WS-Tracker v1.0.0</span>
                            <a href="https://github.com" target="_blank" class="hover:text-primary transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </body>
</html>
