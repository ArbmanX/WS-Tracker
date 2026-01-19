@props([
    'currentRoute' => null,
])

{{--
    Sidebar Navigation Component

    Responsive sidebar using DaisyUI drawer:
    - Mobile (<768px): Drawer overlay, hamburger toggle
    - Tablet (768-1024px): Collapsed to icons only
    - Desktop (>1024px): Expanded, collapsible to icons

    Uses Alpine.js $store.sidebar for state management.

    Usage:
    <x-layout.sidebar :currentRoute="Route::currentRouteName()" />
--}}

@php
    // Navigation structure - can be moved to config if needed
    $navigation = [
        [
            'section' => 'Main',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'icon' => 'home',
                ],
            ],
        ],
        [
            'section' => 'Assessments',
            'items' => [
                [
                    'label' => 'Overview',
                    'route' => 'assessments.overview',
                    'icon' => 'chart-bar',
                ],
                [
                    'label' => 'Kanban',
                    'route' => 'assessments.kanban',
                    'icon' => 'view-columns',
                ],
                [
                    'label' => 'Analytics',
                    'route' => 'assessments.analytics',
                    'icon' => 'chart-pie',
                ],
            ],
        ],
        [
            'section' => 'Planning',
            'items' => [
                [
                    'label' => 'Planner Dashboard',
                    'route' => 'planner.dashboard',
                    'icon' => 'calendar-days',
                    'permission' => 'planner',
                ],
            ],
        ],
        [
            'section' => 'Administration',
            'permission' => 'admin',
            'items' => [
                [
                    'label' => 'Planner Management',
                    'route' => 'admin.planners',
                    'icon' => 'user-group',
                    'permission' => 'admin',
                ],
                [
                    'label' => 'Data Management',
                    'route' => 'admin.data',
                    'icon' => 'circle-stack',
                    'permission' => 'sudo_admin',
                ],
                [
                    'label' => 'Sync Controls',
                    'route' => 'admin.sync',
                    'icon' => 'arrow-path',
                    'permission' => 'admin',
                ],
                [
                    'label' => 'User Management',
                    'route' => 'admin.users',
                    'icon' => 'users',
                    'permission' => 'sudo_admin',
                ],
                [
                    'label' => 'Settings',
                    'route' => 'admin.settings',
                    'icon' => 'cog-6-tooth',
                    'permission' => 'admin',
                ],
            ],
        ],
    ];

    // Helper to check if route is active
    $isActive = fn($route) => $currentRoute === $route;

    // Helper to check if any route in section is active
    $sectionHasActive = function($items) use ($currentRoute) {
        foreach ($items as $item) {
            if (($item['route'] ?? null) === $currentRoute) {
                return true;
            }
        }
        return false;
    };

    // Helper to check role-based permission
    // Permission values: 'sudo_admin', 'admin', 'planner', or null (public)
    $checkPermission = function(?string $permission) {
        if ($permission === null) {
            return true; // No permission required
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // sudo_admin has access to everything
        if ($user->hasRole('sudo_admin')) {
            return true;
        }

        // admin has access to 'admin' and 'planner' permissions
        if ($user->hasRole('admin') && in_array($permission, ['admin', 'planner'])) {
            return true;
        }

        // planner only has access to 'planner' permission
        if ($user->hasRole('planner') && $permission === 'planner') {
            return true;
        }

        return false;
    };
@endphp

{{-- Sidebar Container --}}
<aside
    x-data
    class="h-full bg-base-200 transition-all duration-300"
    :class="$store.sidebar.widthClass"
    @mouseenter="$store.sidebar.hoverEnter()"
    @mouseleave="$store.sidebar.hoverLeave()"
>
    {{-- Logo Section --}}
    <div class="flex h-16 items-center gap-3 px-4">
        <a
            href="{{ route('dashboard') }}"
            wire:navigate
            class="flex items-center gap-3"
        >
            {{-- Logo Icon --}}
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-content">
                <x-ui.icon name="bolt" size="lg" variant="solid" />
            </div>
            {{-- Logo Text (hidden when collapsed) --}}
            <span
                x-show="$store.sidebar.showLabels"
                x-transition:enter="transition-opacity duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="text-lg font-bold"
            >
                WS-Tracker
            </span>
        </a>
    </div>

    <div class="divider my-0 px-4"></div>

    {{-- Navigation Menu --}}
    <nav class="p-2">
        <ul class="menu menu-sm gap-1">
            @foreach($navigation as $section)
                {{-- Check section-level permission --}}
                @php
                    $sectionPermission = $section['permission'] ?? null;
                    $hasPermission = $checkPermission($sectionPermission);
                @endphp

                @if($hasPermission)
                    {{-- Section Title --}}
                    <li
                        x-show="$store.sidebar.showLabels"
                        class="menu-title mt-4 first:mt-0"
                    >
                        {{ $section['section'] }}
                    </li>

                    {{-- Section Items --}}
                    @foreach($section['items'] as $item)
                        @php
                            $itemPermission = $item['permission'] ?? null;
                            $hasItemPermission = $checkPermission($itemPermission);
                            $active = $isActive($item['route'] ?? '');
                            $routeExists = Route::has($item['route'] ?? '');
                        @endphp

                        @if($hasItemPermission)
                            <li>
                                @if($routeExists)
                                    <a
                                        href="{{ route($item['route']) }}"
                                        wire:navigate
                                        @class([
                                            'flex items-center gap-3',
                                            'menu-active' => $active,
                                        ])
                                    >
                                        <x-ui.tooltip
                                            :text="$item['label']"
                                            position="right"
                                            x-show="!$store.sidebar.showLabels"
                                        >
                                            <x-ui.icon :name="$item['icon']" size="md" />
                                        </x-ui.tooltip>
                                        <x-ui.icon
                                            :name="$item['icon']"
                                            size="md"
                                            x-show="$store.sidebar.showLabels"
                                        />
                                        <span x-show="$store.sidebar.showLabels">
                                            {{ $item['label'] }}
                                        </span>
                                    </a>
                                @else
                                    {{-- Route doesn't exist yet - show disabled --}}
                                    <span class="flex items-center gap-3 opacity-50 cursor-not-allowed">
                                        <x-ui.icon :name="$item['icon']" size="md" />
                                        <span x-show="$store.sidebar.showLabels">
                                            {{ $item['label'] }}
                                        </span>
                                    </span>
                                @endif
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </ul>
    </nav>

    {{-- Collapse Toggle (Desktop Only) --}}
    <div
        class="absolute bottom-4 left-0 right-0 px-2"
        x-show="$store.sidebar.breakpoint === 'desktop'"
    >
        <button
            type="button"
            @click="$store.sidebar.toggleCollapse()"
            class="btn btn-ghost btn-sm w-full justify-start gap-3"
        >
            <x-ui.icon
                name="chevron-double-left"
                size="md"
                x-show="!$store.sidebar.isCollapsed"
            />
            <x-ui.icon
                name="chevron-double-right"
                size="md"
                x-show="$store.sidebar.isCollapsed"
            />
            <span x-show="$store.sidebar.showLabels">
                Collapse
            </span>
        </button>
    </div>
</aside>
