# Phase 2: Overview Dashboard - Regional Circuit Stats

**Status:** Draft
**Branch:** `feature/overview-dashboard`
**Depends On:** Phase 1 (App Shell Layout)
**Estimated Files:** 12 new, 3 modified, 3 removed

---

## Overview

The "home base" dashboard showing high-level circuit metrics across all regions. Users can toggle between card grid and table views. Clicking a region card opens a slide-out detail panel; a "View Full Analytics" button navigates to the Analytics page.

### Goals
1. Display regional circuit stats in card and table formats
2. Create reusable stat/metric components for future dashboards
3. Implement slide-out panel for region details
4. Save view preference (cards/table) to user settings
5. Build responsive layout (cards stack on mobile, table scrolls)

### Non-Goals (Future Phases)
- Drag-and-drop card arrangement
- Custom dashboard widgets
- Work Jobs data (mirror structure later)

---

## Data Requirements

### Per Region Metrics

Data sourced from `regional_weekly_aggregates` (latest) or computed from `circuits`:

| Metric | Column/Calculation | Display |
|--------|-------------------|---------|
| Active Circuits | `active_circuits` | "72 active" |
| Total Circuits | `total_circuits` | "72 / 85 total" |
| Total Miles | `total_miles` | "2,209 mi" |
| Miles Planned | `miles_planned` | "993 mi planned" |
| Miles Remaining | `total_miles - miles_planned` | "1,216 mi remaining" |
| Avg % Complete | `avg_percent_complete` | "45%" with progress bar |
| Total Units | `total_units` | "12,450 units" |
| Active Planners | `active_planners` | "8 planners" |

### Query Strategy

```php
// Livewire computed property
#[Computed]
public function regionStats(): Collection
{
    return RegionalWeeklyAggregate::query()
        ->select('region_id', 'active_circuits', 'total_circuits',
                 'total_miles', 'miles_planned', 'miles_remaining',
                 'avg_percent_complete', 'total_units', 'active_planners')
        ->whereIn('region_id', $this->getAccessibleRegionIds())
        ->latest('week_ending')
        ->get()
        ->keyBy('region_id');
}
```

---

## Component Specifications

### 1. Stat Card (`components/ui/stat-card.blade.php`)

Reusable single-metric display card.

```html
@props([
    'label' => '',           // "Total Miles"
    'value' => '',           // "2,209"
    'suffix' => '',          // "mi"
    'icon' => null,          // Optional heroicon name
    'trend' => null,         // +5.2 or -3.1 (percentage change)
    'trendLabel' => '',      // "vs last week"
    'color' => 'primary',    // primary|secondary|accent|success|warning|error
    'size' => 'md',          // sm|md|lg
])
```

**DaisyUI Structure:**
```html
<div class="stat bg-base-100 rounded-box shadow">
    <div class="stat-figure text-primary">
        <x-ui.icon :name="$icon" class="size-8" />
    </div>
    <div class="stat-title">{{ $label }}</div>
    <div class="stat-value text-primary">{{ $value }}{{ $suffix }}</div>
    @if($trend)
        <div class="stat-desc {{ $trend > 0 ? 'text-success' : 'text-error' }}">
            {{ $trend > 0 ? '↑' : '↓' }} {{ abs($trend) }}% {{ $trendLabel }}
        </div>
    @endif
</div>
```

---

### 2. Region Card (`components/dashboard/assessments/region-card.blade.php`)

Summary card for a single region with key metrics.

```html
@props([
    'region' => null,        // Region model
    'stats' => null,         // RegionalWeeklyAggregate
    'clickable' => true,     // Open panel on click
])
```

**Layout:**
```
┌─────────────────────────────────────┐
│ [Icon] Central                      │
│        Region                       │
├─────────────────────────────────────┤
│  72 circuits     │  2,209 mi        │
│  active          │  total           │
├─────────────────────────────────────┤
│  ████████░░░░░░░░░░  45%           │
│  993 mi planned / 1,216 remaining   │
├─────────────────────────────────────┤
│  8 planners  •  12,450 units        │
├─────────────────────────────────────┤
│                    [View Analytics →]│
└─────────────────────────────────────┘
```

**DaisyUI Components:**
- `card`, `card-body`, `card-actions`
- `progress` for completion bar
- `badge` for counts
- `btn btn-ghost btn-sm` for action

---

### 3. Region Table (`components/dashboard/assessments/region-table.blade.php`)

Tabular view of all regions with sortable columns.

```html
@props([
    'regions' => [],         // Collection of regions with stats
    'sortBy' => 'name',      // Current sort column
    'sortDir' => 'asc',      // asc|desc
])
```

**Columns:**
| Column | Sortable | Mobile |
|--------|----------|--------|
| Region | Yes | Always shown |
| Active | Yes | Hidden <md |
| Total | Yes | Hidden <md |
| Miles | Yes | Shown |
| Planned | Yes | Hidden <lg |
| Remaining | Yes | Hidden <lg |
| % Complete | Yes | Shown |
| Units | Yes | Hidden <lg |
| Planners | Yes | Hidden <xl |
| Actions | No | Always shown |

**DaisyUI Components:**
- `table`, `table-zebra`, `table-pin-rows`
- `btn btn-ghost btn-xs` for sort headers
- `badge` for status indicators

---

### 4. View Toggle (`components/ui/view-toggle.blade.php`)

Toggle between card and table views.

```html
@props([
    'current' => 'cards',    // cards|table
    'wireModel' => null,     // Livewire binding
])
```

**DaisyUI Structure:**
```html
<div class="join">
    <button class="join-item btn btn-sm {{ $current === 'cards' ? 'btn-active' : '' }}">
        <x-ui.icon name="squares-2x2" class="size-4" />
        <span class="sr-only sm:not-sr-only sm:ml-1">Cards</span>
    </button>
    <button class="join-item btn btn-sm {{ $current === 'table' ? 'btn-active' : '' }}">
        <x-ui.icon name="table-cells" class="size-4" />
        <span class="sr-only sm:not-sr-only sm:ml-1">Table</span>
    </button>
</div>
```

---

### 5. Metric Pill (`components/ui/metric-pill.blade.php`)

Compact inline stat for use within cards.

```html
@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    'color' => 'base',
])
```

**Structure:**
```html
<div class="flex items-center gap-1.5 text-sm">
    @if($icon)
        <x-ui.icon :name="$icon" class="size-4 text-{{ $color }}" />
    @endif
    <span class="font-semibold">{{ $value }}</span>
    <span class="text-base-content/60">{{ $label }}</span>
</div>
```

---

### 6. Region Detail Panel (`components/dashboard/assessments/region-panel.blade.php`)

Slide-out panel showing expanded region details.

```html
@props([
    'region' => null,
    'stats' => null,
    'show' => false,
])
```

**Layout:**
```
┌──────────────────────────────────────┐
│ [←] Central Region           [✕]    │
├──────────────────────────────────────┤
│                                      │
│ Circuit Summary                      │
│ ┌────────┬────────┬────────┐        │
│ │ Active │   QC   │ Closed │        │
│ │   45   │   12   │   15   │        │
│ └────────┴────────┴────────┘        │
│                                      │
│ Miles Progress                       │
│ ████████████░░░░░░░░░ 45%           │
│ 993 of 2,209 miles planned          │
│                                      │
│ Top Planners                         │
│ 1. John Smith - 124 mi              │
│ 2. Jane Doe - 98 mi                 │
│ 3. Bob Wilson - 87 mi               │
│                                      │
│ Recent Activity                      │
│ • Circuit 12345 moved to QC         │
│ • Circuit 67890 assigned to Jane    │
│                                      │
├──────────────────────────────────────┤
│ [View Full Analytics]                │
└──────────────────────────────────────┘
```

**DaisyUI Components:**
- `drawer`, `drawer-side`, `drawer-content`
- `stats` for summary boxes
- `progress` for miles bar
- `menu` for activity list

---

### 7. Overview Livewire Component (`app/Livewire/Assessments/Dashboard/Overview.php`)

```php
class Overview extends Component
{
    // View state
    public string $viewMode = 'cards'; // cards|table

    // Panel state
    public bool $panelOpen = false;
    public ?int $selectedRegionId = null;

    // Table sorting
    public string $sortBy = 'name';
    public string $sortDir = 'asc';

    #[Computed]
    public function regions(): Collection
    {
        return Region::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function regionStats(): Collection
    {
        // Latest weekly aggregates per region
    }

    #[Computed]
    public function selectedRegion(): ?Region
    {
        return $this->selectedRegionId
            ? Region::find($this->selectedRegionId)
            : null;
    }

    public function openPanel(int $regionId): void
    {
        $this->selectedRegionId = $regionId;
        $this->panelOpen = true;
    }

    public function closePanel(): void
    {
        $this->panelOpen = false;
        $this->selectedRegionId = null;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        $this->dispatch('dashboard-state-changed',
            key: 'overview.viewMode',
            value: $mode
        );
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }
}
```

---

## Folder Structure

```
resources/views/
├── components/
│   ├── ui/
│   │   ├── stat-card.blade.php          # NEW
│   │   ├── view-toggle.blade.php        # NEW
│   │   └── metric-pill.blade.php        # NEW
│   └── dashboard/
│       └── assessments/
│           ├── region-card.blade.php    # NEW
│           ├── region-table.blade.php   # NEW
│           ├── region-panel.blade.php   # NEW
│           └── overview-header.blade.php # NEW
│
├── livewire/
│   └── assessments/
│       └── dashboard/
│           └── overview.blade.php       # NEW

app/Livewire/
└── Assessments/
    └── Dashboard/
        └── Overview.php                 # NEW
```

---

## Files Summary

### Files to Create (12)

| File | Purpose |
|------|---------|
| `resources/views/components/ui/stat-card.blade.php` | Reusable stat display |
| `resources/views/components/ui/view-toggle.blade.php` | Cards/Table switcher |
| `resources/views/components/ui/metric-pill.blade.php` | Compact inline stat |
| `resources/views/components/dashboard/assessments/region-card.blade.php` | Region summary card |
| `resources/views/components/dashboard/assessments/region-table.blade.php` | Regions table |
| `resources/views/components/dashboard/assessments/region-panel.blade.php` | Slide-out detail panel |
| `resources/views/components/dashboard/assessments/overview-header.blade.php` | Page header |
| `app/Livewire/Assessments/Dashboard/Overview.php` | Main Livewire component |
| `resources/views/livewire/assessments/dashboard/overview.blade.php` | Livewire view |
| `tests/Feature/Assessments/OverviewTest.php` | Feature tests |

### Files to Modify (3)

| File | Change |
|------|--------|
| `routes/web.php` | Add `/assessments/overview` route |
| `resources/js/alpine/dashboard-state.js` | Add viewMode preference |
| Sidebar navigation | Add Overview link |

### Files to Remove (3)

| File | Reason |
|------|--------|
| `app/Livewire/Dashboard/CircuitDashboard.php` | Replaced by Overview |
| `resources/views/livewire/dashboard/circuit-dashboard.blade.php` | Replaced |
| `resources/views/livewire/dashboard/partials/kanban-view.blade.php` | Moved to Phase 4 |

---

## Responsive Behavior

| Breakpoint | Cards View | Table View |
|------------|------------|------------|
| Mobile (<640px) | 1 column, full width | Horizontal scroll, 3 columns visible |
| Tablet (640-1024px) | 2 columns | Most columns visible |
| Desktop (>1024px) | 3-4 columns | All columns visible |

---

## Acceptance Criteria

### Layout & Views
- [ ] Card grid displays all regions with key metrics
- [ ] Table view shows all metrics with sortable columns
- [ ] View toggle switches between cards and table
- [ ] View preference persists in localStorage
- [ ] Responsive layout works at all breakpoints

### Region Cards
- [ ] Shows region name, circuit count, miles, % complete
- [ ] Progress bar reflects completion percentage
- [ ] Click opens slide-out panel
- [ ] "View Analytics" button navigates to Analytics page

### Slide-out Panel
- [ ] Opens smoothly from right side
- [ ] Shows expanded region details
- [ ] Close button and click-outside closes panel
- [ ] "View Full Analytics" navigates to Analytics page

### Table View
- [ ] All columns display correct data
- [ ] Sorting works on all sortable columns
- [ ] Sort direction indicator shows current sort
- [ ] Row click opens panel

### Data
- [ ] Stats match database values
- [ ] Empty state shown if no regions
- [ ] Loading state shown while fetching

---

## Decisions Made

| Decision | Choice |
|----------|--------|
| Card click behavior | Opens slide-out panel |
| Navigation to Analytics | Via "View Analytics" button in panel |
| View preference storage | localStorage + DB sync |
| Table mobile behavior | Horizontal scroll with pinned first column |
| Panel width | 400px on desktop, full width on mobile |

---

## Approval Status

**Status:** Draft - Awaiting Review

**To approve:** Reply "Approved" after reviewing.
