# WS-Tracker Project Reference

> Consolidated documentation for the WorkStudio Vegetation Management Tracker

---

## Overview

**WS-Tracker** is a Laravel 12 application for tracking vegetation management assessments across PPL Electric Utilities transmission circuits. It synchronizes data from the WorkStudio DDOProtocol API and provides dashboards, analytics, and planning tools.

### Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.4, Laravel 12 |
| Frontend | Livewire 3, Alpine.js |
| Styling | Tailwind CSS 4, DaisyUI 5 |
| Database | PostgreSQL (JSONB for aggregates) |
| Charts | ApexCharts |
| Auth | Laravel Fortify |
| Testing | Pest 4 |

---

## Architecture

### Request Flow
```
User → Livewire Component → Service Layer → Model/Query → Database
                        ↓
              WorkStudio API (sync jobs)
```

### Key Directories
```
app/
├── Livewire/           # Livewire components
│   ├── Assessments/    # Dashboard, Planner Analytics
│   ├── Admin/          # Settings, User Management
│   └── Concerns/       # Shared traits (WithCircuitFilters)
├── Models/             # Eloquent models
├── Services/           # WorkStudioService, SyncService
└── Jobs/               # Sync jobs (planned units, aggregates)
```

### Data Storage Pattern
- **Circuits**: Full records synced from API
- **Aggregates**: Pre-computed stats stored as JSONB (no raw planned_units table)
- **Global Settings**: Singleton `AnalyticsSetting` model with caching

---

## WorkStudio API

### Endpoint Format
```
{base_url}/DDOProtocol/{PROTOCOL_NAME}
```

### Key Protocols
| Protocol | Purpose |
|----------|---------|
| `GETVIEWDATA` | Fetch view data (circuits, planned units) |
| `GETVIEWLIST` | List available views |

### Response Format (DDOTable)
```json
{
  "Protocol": "VIEWDATA",
  "ViewDefinitionGuid": "{guid}",
  "DDOTable": {
    "Columns": [{"Name": "field_name", "DataType": "String"}],
    "Rows": [["value1", "value2"]]
  }
}
```

### Job Status Values
| Code | Description |
|------|-------------|
| `ACTIV` | Active |
| `QC` | Quality Control |
| `REWRK` | Rework |
| `CLOSE` | Closed |
| `CANCEL` | Cancelled |

---

## Data Reference

### Circuit Model
| Field | Type | Description |
|-------|------|-------------|
| `job_guid` | string | Unique API identifier |
| `work_order` | string | Format: `YYYY-NNNN` |
| `region_id` | int | FK to regions |
| `title` | string | Circuit name |
| `api_status` | string | ACTIV, QC, CLOSE, REWRK |
| `cycle_type` | string | Maintenance cycle category |
| `total_miles` | decimal | Total circuit length |
| `miles_planned` | decimal | Miles with planned units |
| `percent_complete` | decimal | Progress percentage |
| `is_excluded` | bool | Excluded from analytics |

### Aggregate Data Structure
```php
[
    'circuit_id' => 123,
    'scope_year' => '2026',
    'unit_counts' => [
        'TREE' => ['total' => 50, 'by_status' => ['A' => 30, 'P' => 20]],
        'VINE' => ['total' => 25, 'by_status' => ['A' => 25]],
    ],
    'permission_counts' => [
        'N' => 10,  // Needed
        'O' => 40,  // Obtained
    ],
]
```

### Cycle Types
- Cycle Maintenance - Herbicide/Trim
- FFP CPM Maintenance
- Proactive Data Directed Maintenance
- Reactive
- VM Detection
- PUC-STORM FOLLOW UP

---

## UI Design System

### DaisyUI 5 + Tailwind CSS 4

**Core Principle**: Use DaisyUI semantic colors, never raw Tailwind colors.

```blade
{{-- DO --}}
<div class="bg-base-100 text-base-content">
<button class="btn btn-primary">

{{-- DON'T --}}
<div class="bg-white text-gray-800">
<button class="bg-blue-500">
```

### Color Semantics
| Semantic | Usage |
|----------|-------|
| `primary` | Actions, links |
| `secondary` | Secondary actions |
| `accent` | Highlights |
| `base-100/200/300` | Backgrounds |
| `base-content` | Text |
| `success/warning/error/info` | Status feedback |

### Component Patterns
```blade
{{-- Card --}}
<div class="card bg-base-100 shadow-xl">
  <div class="card-body">
    <h2 class="card-title">Title</h2>
    <div class="card-actions justify-end">
      <button class="btn btn-primary">Action</button>
    </div>
  </div>
</div>

{{-- Stats --}}
<div class="stats shadow">
  <div class="stat">
    <div class="stat-title">Total</div>
    <div class="stat-value text-primary">245</div>
    <div class="stat-desc">Active circuits</div>
  </div>
</div>

{{-- Filter Dropdown --}}
<div class="dropdown">
  <button class="btn btn-ghost btn-sm">Filter ▼</button>
  <ul class="dropdown-content menu bg-base-100 shadow-lg">
    <li><label class="label cursor-pointer gap-2">
      <input type="checkbox" class="checkbox checkbox-sm" />
      <span>Option</span>
    </label></li>
  </ul>
</div>
```

---

## Livewire Patterns

### Shared Filter Trait
```php
// app/Livewire/Concerns/WithCircuitFilters.php
trait WithCircuitFilters
{
    #[Url(as: 'status')]
    public array $statusFilter = [];

    #[Url(as: 'cycle')]
    public array $cycleTypeFilter = [];

    protected function applyCircuitFilters($query)
    {
        return $query
            ->when($this->statusFilter, fn ($q) =>
                $q->whereIn('api_status', $this->statusFilter))
            ->when($this->cycleTypeFilter, fn ($q) =>
                $q->whereIn('cycle_type', $this->cycleTypeFilter));
    }
}
```

### URL State Persistence
Filters automatically sync to URL for bookmarkability:
```
/assessments/overview?status[]=ACTIV&status[]=QC&cycle[]=Reactive
```

---

## Testing

### Run Tests
```bash
sail artisan test --compact                          # All tests
sail artisan test --compact tests/Feature/...       # Specific file
sail artisan test --compact --filter=testName       # By name
```

### Livewire Test Pattern
```php
Livewire::actingAs($user)
    ->test(Component::class)
    ->assertSet('property', 'value')
    ->set('filter', ['ACTIV'])
    ->call('save')
    ->assertDispatched('notify');
```

---

## Code Quality

```bash
sail composer pint          # Fix formatting
sail artisan test --compact # Run tests
```

### Review Checklist
- [ ] DaisyUI components used (no raw colors)
- [ ] Tests written for new features
- [ ] Pint formatting applied
- [ ] No N+1 queries (use eager loading)

---

## Quick Commands

```bash
# Development
sail up -d                    # Start containers
sail artisan serve            # Not needed with Sail
sail npm run dev              # Vite dev server

# Database
sail artisan migrate          # Run migrations
sail artisan db:seed          # Seed data

# Testing
sail artisan test --compact   # Run tests
sail composer pint            # Format code
```

---

*This document consolidates: codebase_analysis.md, CODE_REVIEW_REPORT.md, DATA_REFERENCE.md, UI_DESIGN_SYSTEM.md, WS_TRACKER_DOCS.md*
