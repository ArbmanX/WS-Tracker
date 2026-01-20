# WS-Tracker Dashboard Mockup Specification

This document provides detailed wireframe specifications for recreating the Dashboard UI in Draw.io, Figma, or directly as Blade/Livewire components.

---

## Overview

The Dashboard is the main operational view for planners, supervisors, and admins. It displays circuits in a Kanban-style workflow board with filtering, statistics, and progress tracking.

## Layout Structure

```
+------------------------------------------------------------------+
|  [HEADER/NAVBAR]                                                 |
|  Logo | WS-Tracker | Search | Sync Status | Theme | User Avatar  |
+------------------------------------------------------------------+
|        |                                                          |
| [SIDE] |  [FILTER PANEL - Horizontal]                            |
| [BAR]  |  Region | Planner | Status | Reset                      |
|        |  +-----------------------------------------------------+ |
|  Home  |  |  STATS CARDS (3 columns)                            | |
| -----  |  |  [Miles Progress] [Regions Chart] [Overall %]       | |
| Dash   |  +-----------------------------------------------------+ |
| Admin  |                                                          |
| -----  |  +-----------------------------------------------------+ |
| Theme  |  |  KANBAN BOARD (5 columns)                           | |
|        |  |  [Active] [Pending] [QC] [Rework] [Closed]          | |
|        |  |                                                     | |
|        |  |  [Card]   [Card]   [Card]  [Card]   [Card]          | |
|        |  |  [Card]   [Card]   [Card]           [Card]          | |
|        |  |  [Card]   [Card]                    [Card]          | |
|        |  |  [Card]                                             | |
|        |  +-----------------------------------------------------+ |
|        |                                                          |
|        |  [CHARTS PANEL - Collapsible]                           |
|        |  [Miles by Region] [Planner Progress] [Permission Pie]  |
+------------------------------------------------------------------+
```

---

## 1. Header/Navbar

### Dimensions
- Height: 64px (h-16)
- Background: `bg-base-100` with bottom border

### Components
```
+------------------------------------------------------------------+
| [=] | [Logo] WS-Tracker        [Search...]  [Sync] [Theme] [User]|
+------------------------------------------------------------------+
```

### Specifications

| Element | Component | Details |
|---------|-----------|---------|
| Hamburger | `btn btn-ghost` | Mobile only, toggles sidebar |
| Logo | SVG/Image | PPL Logo or WS-Tracker text |
| Search | `input input-bordered` | `wire:model.live.debounce.300ms="search"` |
| Sync Status | `tooltip` + icon | Green dot = synced, yellow = syncing |
| Theme Toggle | `swap` | Sun/Moon icons |
| User Avatar | `avatar placeholder` | Dropdown with profile/logout |

### Blade Implementation
```blade
<nav class="navbar bg-base-100 border-b border-base-300 sticky top-0 z-50">
  <div class="flex-none lg:hidden">
    <label for="main-drawer" class="btn btn-ghost btn-square drawer-button">
      <svg class="w-5 h-5"><!-- hamburger --></svg>
    </label>
  </div>

  <div class="flex-1 px-2">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
      <img src="/images/logo.svg" alt="WS-Tracker" class="h-8" />
      <span class="font-bold text-xl hidden sm:inline">WS-Tracker</span>
    </a>
  </div>

  <div class="flex-none gap-2">
    <!-- Global Search -->
    <div class="form-control hidden md:block">
      <input type="text" placeholder="Search circuits..."
             class="input input-bordered input-sm w-48 lg:w-64" />
    </div>

    <!-- Sync Status -->
    <div class="tooltip tooltip-bottom" data-tip="Last synced: 5 min ago">
      <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-success/10">
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute h-full w-full rounded-full bg-success opacity-75"></span>
          <span class="relative rounded-full h-2 w-2 bg-success"></span>
        </span>
        <span class="text-xs font-medium text-success hidden sm:inline">Synced</span>
      </div>
    </div>

    <!-- Theme Toggle -->
    <label class="swap swap-rotate btn btn-ghost btn-circle">
      <input type="checkbox" class="theme-controller" value="dark" />
      <svg class="swap-on w-5 h-5"><!-- sun icon --></svg>
      <svg class="swap-off w-5 h-5"><!-- moon icon --></svg>
    </label>

    <!-- User Dropdown -->
    <div class="dropdown dropdown-end">
      <div tabindex="0" class="avatar placeholder cursor-pointer">
        <div class="bg-primary text-primary-content rounded-full w-10">
          <span>{{ Auth::user()->initials() }}</span>
        </div>
      </div>
      <ul tabindex="0" class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-box w-52">
        <li><a href="{{ route('profile') }}">Profile</a></li>
        <li><a wire:click="logout">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
```

---

## 2. Sidebar (Flux UI)

### Dimensions
- Width: 256px expanded, 64px collapsed
- Background: `bg-base-200`

### Navigation Items
```
[Home Icon] Dashboard
[Admin Icon] Admin Panel (admin/sudo_admin only)
[Separator]
[Theme Icon] Theme
```

### Flux UI Implementation
```blade
<flux:sidebar class="border-r border-base-300">
  <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

  <flux:brand href="{{ route('dashboard') }}" name="WS-Tracker" />

  <flux:navlist>
    <flux:navlist.item :href="route('dashboard')" icon="home" :current="request()->routeIs('dashboard')">
      Dashboard
    </flux:navlist.item>

    @can('access-admin-panel')
    <flux:navlist.item :href="route('admin.index')" icon="cog-6-tooth" :current="request()->routeIs('admin.*')">
      Admin Panel
    </flux:navlist.item>
    @endcan
  </flux:navlist>

  <flux:spacer />

  <flux:navlist>
    <flux:navlist.item icon="sun" @click="toggleTheme">
      Toggle Theme
    </flux:navlist.item>
  </flux:navlist>
</flux:sidebar>
```

---

## 3. Filter Panel

### Layout
```
+------------------------------------------------------------------+
| Region          Planner          Status Chips          [Reset]   |
| [Dropdown v]    [Dropdown v]     [x]Active [x]QC...   [Clear]    |
+------------------------------------------------------------------+
```

### Specifications
- Container: `bg-base-100`, `rounded-xl`, `p-4`, `shadow-sm`
- Layout: `flex flex-wrap items-center gap-4`

### Filter Components

| Filter | Type | Behavior |
|--------|------|----------|
| Region | `select` | Multi-select dropdown |
| Planner | `select` | Searchable dropdown |
| Status | Checkbox chips | Toggle visibility |
| Reset | `btn btn-ghost` | Clear all filters |

### Blade Implementation
```blade
<div class="bg-base-100 rounded-xl border border-base-300 p-4 shadow-sm">
  <div class="flex flex-wrap items-end gap-4">

    <!-- Region Filter -->
    <div class="form-control w-full sm:w-48">
      <label class="label"><span class="label-text text-xs font-medium">Region</span></label>
      <select wire:model.live="filters.region" class="select select-bordered select-sm">
        <option value="">All Regions</option>
        @foreach($regions as $region)
        <option value="{{ $region->id }}">{{ $region->name }}</option>
        @endforeach
      </select>
    </div>

    <!-- Planner Filter -->
    <div class="form-control w-full sm:w-48">
      <label class="label"><span class="label-text text-xs font-medium">Planner</span></label>
      <select wire:model.live="filters.planner" class="select select-bordered select-sm">
        <option value="">All Planners</option>
        @foreach($planners as $planner)
        <option value="{{ $planner->id }}">{{ $planner->name }}</option>
        @endforeach
      </select>
    </div>

    <!-- Status Chips -->
    <div class="form-control flex-1">
      <label class="label"><span class="label-text text-xs font-medium">Status</span></label>
      <div class="flex flex-wrap gap-1">
        @foreach(['active' => 'Active', 'pending_permissions' => 'Pending', 'qc' => 'QC', 'rework' => 'Rework', 'closed' => 'Closed'] as $key => $label)
        <label class="cursor-pointer">
          <input type="checkbox" wire:model.live="filters.statuses" value="{{ $key }}" class="peer hidden" />
          <span class="badge peer-checked:badge-primary peer-not-checked:badge-ghost">{{ $label }}</span>
        </label>
        @endforeach
      </div>
    </div>

    <!-- Reset Button -->
    <button wire:click="resetFilters" class="btn btn-ghost btn-sm">
      <svg class="w-4 h-4"><!-- x-circle --></svg>
      Reset
    </button>
  </div>
</div>
```

---

## 4. Stats Panel (3 Cards)

### Layout
```
+---------------------------+---------------------------+---------------------------+
|  MILES PROGRESS           |  CIRCUITS BY REGION       |  OVERALL PROGRESS         |
|  [Radial Progress]        |  [Mini Bar Chart]         |  [Large Percentage]       |
|  245.8 / 892.4 mi         |  [C: 45] [L: 32]...       |  67%                      |
|  "27.5% Complete"         |                           |  "On Track"               |
+---------------------------+---------------------------+---------------------------+
```

### Card 1: Miles Progress
```blade
<div class="card bg-base-100 shadow-sm border border-base-300">
  <div class="card-body p-4">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-sm font-medium text-base-content/60 uppercase tracking-wide">Miles Planned</h3>
        <div class="mt-1">
          <span class="text-2xl font-bold">245.8</span>
          <span class="text-lg text-base-content/60">/ 892.4 mi</span>
        </div>
        <p class="text-sm text-success mt-1">27.5% Complete</p>
      </div>
      <div class="radial-progress text-primary" style="--value:27.5; --size:5rem; --thickness:6px;">
        <span class="text-lg font-semibold">27%</span>
      </div>
    </div>
  </div>
</div>
```

### Card 2: Circuits by Region
```blade
<div class="card bg-base-100 shadow-sm border border-base-300">
  <div class="card-body p-4">
    <h3 class="text-sm font-medium text-base-content/60 uppercase tracking-wide">Circuits by Region</h3>
    <div class="flex items-end gap-2 mt-3 h-16">
      <!-- Mini bars for each region -->
      <div class="flex flex-col items-center gap-1 flex-1">
        <div class="w-full bg-primary rounded-t" style="height: 60%;"></div>
        <span class="text-xs text-base-content/60">C</span>
        <span class="text-xs font-semibold">45</span>
      </div>
      <!-- Repeat for Lancaster, Lehigh, Harrisburg -->
    </div>
    <p class="text-xs text-base-content/50 mt-2">Total: 112 circuits</p>
  </div>
</div>
```

### Card 3: Overall Progress
```blade
<div class="card bg-gradient-to-br from-primary to-secondary text-primary-content shadow-sm">
  <div class="card-body p-4">
    <h3 class="text-sm font-medium opacity-80 uppercase tracking-wide">Overall Progress</h3>
    <div class="flex items-center justify-between mt-2">
      <div>
        <span class="text-5xl font-bold">67%</span>
        <div class="flex items-center gap-1 mt-1">
          <svg class="w-4 h-4"><!-- trending-up --></svg>
          <span class="text-sm opacity-90">On Track</span>
        </div>
      </div>
    </div>
    <progress class="progress progress-primary bg-primary-content/20 w-full mt-3" value="67" max="100"></progress>
  </div>
</div>
```

---

## 5. Kanban Board

### Layout
```
+-------------+-------------+-------------+-------------+-------------+
|   ACTIVE    |   PENDING   |     QC      |   REWORK    |   CLOSED    |
|   (23)      | PERMISSIONS |   (8)       |   (3)       |   (156)     |
+-------------+-------------+-------------+-------------+-------------+
|  [Card 1]   |  [Card 5]   |  [Card 9]   |  [Card 11]  |  [Card 12]  |
|  [Card 2]   |  [Card 6]   |  [Card 10]  |             |  [Card 13]  |
|  [Card 3]   |  [Card 7]   |             |             |  [Card 14]  |
|  [Card 4]   |  [Card 8]   |             |             |             |
|   ...       |   ...       |   ...       |   ...       |   ...       |
+-------------+-------------+-------------+-------------+-------------+
```

### Column Header Colors

| Column | Header BG | Indicator | Border |
|--------|-----------|-----------|--------|
| Active | `bg-success/10` | `bg-success` | `border-success/30` |
| Pending | `bg-warning/10` | `bg-warning` | `border-warning/30` |
| QC | `bg-info/10` | `bg-info` | `border-info/30` |
| Rework | `bg-error/10` | `bg-error` | `border-error/30` |
| Closed | `bg-neutral/10` | `bg-neutral` | `border-neutral/30` |

### Board Container
```blade
<div class="flex gap-4 overflow-x-auto pb-4" style="min-height: 500px;">
  @foreach($columns as $status => $config)
  <div class="flex-shrink-0 w-[280px] flex flex-col">
    <!-- Column Header -->
    <div class="flex items-center justify-between px-3 py-2 {{ $config['headerBg'] }} rounded-t-lg border {{ $config['border'] }}">
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full {{ $config['dot'] }}"></span>
        <h3 class="font-semibold text-sm">{{ $config['label'] }}</h3>
        <span class="badge badge-sm badge-ghost">{{ $circuits[$status]->count() }}</span>
      </div>
    </div>

    <!-- Column Body -->
    <div class="flex-1 overflow-y-auto p-2 bg-base-200/50 rounded-b-lg border-x border-b border-base-300 space-y-2"
         wire:sortable="updateOrder" wire:sortable-group="{{ $status }}">
      @foreach($circuits[$status] as $circuit)
        <div wire:key="circuit-{{ $circuit->id }}" wire:sortable.item="{{ $circuit->id }}">
          <livewire:circuit-card :circuit="$circuit" :key="$circuit->id" />
        </div>
      @endforeach
    </div>
  </div>
  @endforeach
</div>
```

---

## 6. Circuit Card

### Anatomy
```
+----------------------------------------------------------+
|  [WO] 2025-1930           [Region Badge: Central]        |
+----------------------------------------------------------+
|  Lititz-Manheim 23kV Circuit Assessment                  |
+----------------------------------------------------------+
|  Progress: =============================--------  72%     |
+----------------------------------------------------------+
|  4.38 / 14.93 mi                        Modified: 01/08  |
+----------------------------------------------------------+
|  [Avatar][Avatar][Avatar]        [Split: 3 assessments]  |
+----------------------------------------------------------+
```

### Specifications
- Width: 100% of column (264px with padding)
- Min Height: 120px
- Border Radius: 12px (`rounded-xl`)
- Shadow: `shadow-sm` default, `shadow-md` on hover

### Progress Bar Colors

| Percentage | Class |
|------------|-------|
| >= 90% | `progress-success` |
| >= 50% | `progress-primary` |
| >= 25% | `progress-warning` |
| < 25% | `progress-error` |

### Region Badge Colors

| Region | Badge Class |
|--------|-------------|
| Central | `badge-primary` |
| Lancaster | `badge-secondary` |
| Lehigh | `badge-accent` |
| Harrisburg | `badge-neutral` |

### Full Card Implementation
```blade
<div wire:sortable.handle
     class="card bg-base-100 shadow-sm border border-base-300 cursor-grab
            active:cursor-grabbing hover:shadow-md hover:border-primary/30
            transition-all duration-200"
     draggable="true">
  <div class="card-body p-3 gap-2">
    <!-- Header Row -->
    <div class="flex justify-between items-start">
      <span class="font-mono text-sm font-semibold">
        {{ $circuit->work_order }}{{ $circuit->extension }}
      </span>
      <span class="badge badge-sm badge-primary">{{ $circuit->region->name }}</span>
    </div>

    <!-- Title -->
    <p class="text-xs text-base-content/70 line-clamp-1">{{ $circuit->title }}</p>

    <!-- Progress Bar -->
    <div>
      <div class="flex justify-between text-xs mb-1">
        <span class="text-base-content/60">Progress</span>
        <span class="font-medium">{{ number_format($circuit->percent_complete, 0) }}%</span>
      </div>
      <progress class="progress h-2 w-full progress-primary"
                value="{{ $circuit->percent_complete }}" max="100"></progress>
    </div>

    <!-- Stats Row -->
    <div class="flex justify-between items-center text-xs text-base-content/60">
      <span>{{ number_format($circuit->miles_planned, 2) }} / {{ number_format($circuit->total_miles, 2) }} mi</span>
      <span>{{ $circuit->api_modified_date->format('m/d') }}</span>
    </div>

    <!-- Footer Row -->
    <div class="flex justify-between items-center pt-1 border-t border-base-200">
      <!-- Planner Avatars -->
      @if($circuit->planners->isNotEmpty())
        <div class="avatar-group -space-x-2">
          @foreach($circuit->planners->take(3) as $planner)
            <div class="avatar placeholder">
              <div class="bg-neutral text-neutral-content rounded-full w-6 h-6 text-[10px]">
                <span>{{ $planner->initials }}</span>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <span class="text-xs text-base-content/40 italic">No planner</span>
      @endif

      <!-- Split Indicator -->
      @if($circuit->children->isNotEmpty())
        <button wire:click="toggleSplitAssessments" class="btn btn-ghost btn-xs">
          {{ $circuit->children->count() }} splits
        </button>
      @endif
    </div>
  </div>
</div>
```

---

## 7. Charts Panel (Collapsible)

### Layout
```
+-----------------------------------------------------------------------------------+
|  [v] Charts & Analytics                                          [Expand/Collapse]|
+-----------------------------------------------------------------------------------+
|  +---------------------------+  +-------------------------+  +-------------------+|
|  |  MILES BY REGION          |  |  PLANNER PROGRESS       |  | PERMISSION STATUS ||
|  |  [Bar Chart]              |  |  [Horizontal Bars]      |  | [Donut Chart]     ||
|  +---------------------------+  +-------------------------+  +-------------------+|
+-----------------------------------------------------------------------------------+
```

### Implementation
```blade
<div class="collapse collapse-arrow bg-base-100 border border-base-300 rounded-xl">
  <input type="checkbox" class="peer" />
  <div class="collapse-title font-medium flex items-center gap-2">
    <svg class="w-5 h-5 text-primary"><!-- chart-bar --></svg>
    <span>Charts & Analytics</span>
  </div>
  <div class="collapse-content">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-4">
      <!-- Miles by Region (Bar Chart) -->
      <div class="card bg-base-200/30 p-4">
        <h4 class="text-sm font-medium mb-3">Miles by Region</h4>
        <div x-ref="regionChart" wire:ignore></div>
      </div>

      <!-- Planner Progress (Horizontal Bars) -->
      <div class="card bg-base-200/30 p-4">
        <h4 class="text-sm font-medium mb-3">Planner Progress</h4>
        <div x-ref="plannerChart" wire:ignore></div>
      </div>

      <!-- Permission Status (Donut) -->
      <div class="card bg-base-200/30 p-4">
        <h4 class="text-sm font-medium mb-3">Permission Status</h4>
        <div x-ref="permissionChart" wire:ignore></div>
      </div>
    </div>
  </div>
</div>
```

---

## 8. Empty States

### Empty Column
```blade
<div class="flex flex-col items-center justify-center py-12 text-center">
  <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-4">
    <svg class="w-8 h-8 text-base-content/30"><!-- inbox --></svg>
  </div>
  <h4 class="font-medium text-base-content/60">No circuits</h4>
  <p class="text-sm text-base-content/40 mt-1">Drag circuits here to move them</p>
</div>
```

### No Search Results
```blade
<div class="flex flex-col items-center justify-center py-20 text-center">
  <div class="w-20 h-20 rounded-full bg-base-200 flex items-center justify-center mb-4">
    <svg class="w-10 h-10 text-base-content/30"><!-- search-x --></svg>
  </div>
  <h3 class="text-lg font-semibold text-base-content/70">No matching circuits</h3>
  <p class="text-sm text-base-content/50 mt-2 max-w-md">
    Try adjusting your filters or search terms.
  </p>
  <button wire:click="resetFilters" class="btn btn-primary btn-sm mt-4">Clear Filters</button>
</div>
```

---

## 9. Loading States

### Skeleton Card
```blade
<div class="card bg-base-100 shadow-sm border border-base-300">
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
  </div>
</div>
```

---

## 10. Color Palette

### Brand Colors
| Name | Hex | Usage |
|------|-----|-------|
| PPL Cyan | `#1882C5` | Primary actions, links |
| St. Patrick's Blue | `#28317E` | Secondary, headers |
| Asplundh Orange | `#E27434` | Accent, highlights |
| Orient Blue | `#004B6E` | Dark backgrounds |

### Semantic Colors (DaisyUI)
| Name | Usage |
|------|-------|
| `success` | Active status, completion |
| `warning` | Pending, caution |
| `info` | QC status |
| `error` | Rework, errors |
| `neutral` | Closed, disabled |

---

## 11. Responsive Breakpoints

| Breakpoint | Width | Layout Changes |
|------------|-------|----------------|
| `sm` | 640px | Stack filter chips |
| `md` | 768px | 2-column stats, horizontal scroll kanban |
| `lg` | 1024px | Show sidebar, 3-column stats |
| `xl` | 1280px | Full 5-column kanban |
| `2xl` | 1536px | Wider columns |

---

## 12. Artboard Sizes for Design Tools

| Artboard | Dimensions | Use Case |
|----------|------------|----------|
| Desktop Full | 1440 x 900 | Primary design |
| Desktop Wide | 1920 x 1080 | Large displays |
| Tablet | 768 x 1024 | iPad portrait |
| Mobile | 375 x 812 | iPhone design |

---

*This specification provides all details needed to implement the WS-Tracker Dashboard UI.*
