# WS-Tracker Project Context

> **Purpose:** This document provides context for AI assistants working on this project.
> **Last Updated:** January 2026
> **Status:** Active Development

---

## Project Overview

**WS-Tracker** is a vegetation maintenance admin dashboard for PPL Electric Utilities circuit tracking. It syncs data from the WorkStudio GIS application and provides management-level views of planning progress.

### Key Distinction
This application is a **management dashboard**, NOT a planner-facing tool. Planners use WorkStudio directly. WS-Tracker:
- Displays **aggregated** data only (no individual unit records)
- Tracks planner productivity over time
- Provides multi-level reporting (global → regional → circuit → planner)
- Enables workflow stage management via drag-drop Kanban board

---

## Tech Stack

| Layer | Technology | Version |
|-------|------------|---------|
| **Backend** | Laravel | 12.x |
| **Frontend** | Livewire | 3.x |
| **CSS Framework** | Tailwind CSS | 4.x |
| **Component Library** | DaisyUI | 5.x |
| **Database** | PostgreSQL | 16+ |
| **Testing** | Pest | 4.x |
| **Environment** | Laravel Sail | Available but optional |

### Key Packages
- `spatie/laravel-permission` - Role management (Admin, Manager, Supervisor, Planner)
- `spatie/laravel-activitylog` - Audit logging
- `livewire-sortable` - Drag-drop functionality
- `apexcharts` - Dashboard charts

---

## Architecture Decision: Aggregate-Only Storage

### The Decision
Store **only aggregates** from planned units, NOT individual unit records.

### Why
1. **Separation of concerns** - Planners use WorkStudio for unit-level work
2. **Performance** - 95%+ reduction in database storage and query complexity
3. **Dashboard focus** - Management needs totals, not granular unit data

### What This Means
```
REMOVED:
❌ planned_units table (individual records)
❌ planned_unit_snapshots table

ADDED:
✅ circuit_aggregates table (totals per circuit per day)
✅ planner_daily_aggregates table (planner productivity tracking)
✅ regional_daily_aggregates table (regional rollups)
```

---

## Data Hierarchy

```
Global (All Regions)
  └── Regional (Per PPL Region)
        └── Circuit (Per Work Order)
              └── Planner (Per Forester)
```

### Metrics Tracked at Each Level
| Metric | Global | Regional | Circuit | Planner |
|--------|--------|----------|---------|---------|
| Total Miles | ✅ | ✅ | ✅ | ✅ |
| Miles Planned | ✅ | ✅ | ✅ | ✅ |
| Unit Count (by type) | ✅ | ✅ | ✅ | ✅ |
| Linear Feet | ✅ | ✅ | ✅ | ✅ |
| Acres | ✅ | ✅ | ✅ | ✅ |
| Tree Count | ✅ | ✅ | ✅ | ✅ |
| Permission Status Counts | ✅ | ✅ | ✅ | ✅ |

---

## Unit Types

### Categories (from WorkStudio)
| Category | Code | Measurement | Description |
|----------|------|-------------|-------------|
| Line Trimming | VLG | Linear Feet | Vegetation Line/Length |
| Brush/Herbicide | VAR | Acres | Variable Area |
| Tree Removal | VCT | Tree Count | Vegetation Count/Tree (sized by DBH) |
| No Work | VNW | None | Flags only |
| Sensitive | VSA | None | Customer flags |

### Unit Type Examples
```
VLG (15 units): SPM, SPB, SPE, MPM, MPB, MPE, MPME, MPBE, MST, OHT, SIDET, SOHT, STB, STM, TTS
VAR (9 units):  HCB, BRUSH, BRUSHTRIM, MOW, HERBNA, HERBA, HERBS, Herb-Sub, T&M
VCT (16 units): REM612-REM36, REM>30, ASH612-ASH36, BTR, VPS
VNW (2 units):  NW, NOT
VSA (1 unit):   SENSI
```

### Reference Files
- `FinalDraft/UnitTypes.json` - Complete unit type reference with categories
- `FinalDraft/UnitList.json` - Raw WorkStudio export

---

## Database Schema (Key Tables)

### circuits
Core circuit/assessment data synced from WorkStudio API.
```sql
- id, job_guid (unique), work_order, extension
- region_id (FK), title, api_status
- miles_planned, total_miles, percent_complete
- api_data_json (JSONB - raw API data)
- api_modified_date, deleted_at
```

### circuit_aggregates
Daily aggregate snapshots per circuit (replaces planned_units).
```sql
- circuit_id, aggregate_date, is_rollup
- total_units, total_linear_ft, total_acres, total_trees
- units_approved, units_refused, units_pending
- unit_counts_by_type (JSONB), planner_distribution (JSONB)
```

### planner_daily_aggregates
Planner productivity tracking.
```sql
- user_id, region_id, aggregate_date
- circuits_worked, total_units_assessed
- total_linear_ft, total_acres
- units_approved, units_refused, units_pending
- unit_counts_by_type (JSONB)
```

### unit_types
Reference table for vegetation work unit types.
```sql
- code (unique), name, category, measurement_type
- dbh_min, dbh_max (for tree removals)
- species (for ash removals), sort_order, is_active
```

---

## API Integration

### WorkStudio API
- **Base URL:** `https://ppl02.geodigital.com:8372/ddoprotocol/`
- **Auth:** Basic authentication (service account + optional user credentials)
- **Format:** DDOTable (proprietary JSON format)

### Key View GUIDs
```php
'vegetation_assessments' => '{A856F956-88DF-4807-90E2-7E12C25B5B32}'
'work_jobs'              => '{546D9963-9242-4945-8A74-15CA83CDA537}'
'planned_units'          => '{985AECEF-D75B-40F3-9F9B-37F21C63FF4A}'
```

### Sync Strategy
- **ACTIV circuits:** Twice daily (4:30 AM, 4:30 PM Eastern)
- **QC/REWORK/CLOSE:** Weekly (Monday 4:30 AM)
- Aggregates computed during sync, not at query time

---

## Key Models

### UnitType (app/Models/UnitType.php)
Reference model with cached lookups for aggregation.

**Key Methods:**
```php
UnitType::findByCode('SPM')           // Cached lookup
UnitType::codesForCategory('VLG')     // Get all line trimming codes
UnitType::codesForMeasurement('acres') // Get all acre-based codes
UnitType::aggregationGroups()          // Dashboard groupings
```

**Scopes:**
```php
UnitType::lineTrimming()  // VLG units
UnitType::brushArea()     // VAR units
UnitType::treeRemoval()   // VCT units
UnitType::workUnits()     // Excludes NW, NOT, SENSI
```

---

## Service Layer Architecture

```
app/Services/WorkStudio/
├── WorkStudioApiService.php           # HTTP client, auth, retry
├── ApiCredentialManager.php           # Credential rotation
├── Aggregation/
│   ├── AggregateCalculationService.php   # Compute aggregates from API
│   ├── AggregateStorageService.php       # Persist to database
│   ├── AggregateDiffService.php          # Compare for changes
│   └── AggregateQueryService.php         # Query interface
└── Transformers/
    ├── DDOTableTransformer.php        # Parse DDOTable format
    ├── CircuitTransformer.php         # Circuit field mapping
    └── PlannedUnitAggregateTransformer.php  # Aggregate-only transform
```

---

## Workflow Stages

Circuits move through workflow stages (UI-only, not in API):

```
Active → Pending Permissions → QC → Rework → Closed
```

- Stages managed via `circuit_ui_states` table
- Drag-drop reordering within and between columns
- Snapshots created on stage changes

---

## Permission Statuses

From WorkStudio `VEGUNIT_PERMSTAT` field:
- **Approved** - Permission granted
- **Refused** - Permission denied
- **Pending** - Awaiting response
- (Others may exist - captured in JSONB)

---

## Brand Colors

### PPL Electric Utilities
- Primary: `#1882C5` (Cyan Cornflower Blue)
- Secondary: `#28317E` (St. Patrick's Blue)

### Asplundh (Contractor)
- Accent: `#E27434` (Red Damask)
- Neutral: `#00598D` (Orient Blue)

Custom DaisyUI theme defined in `resources/css/themes/ppl-brand.css`.

---

## File Structure Reference

```
app/
├── Enums/                    # WorkflowStage, SnapshotType, etc.
├── Jobs/                     # SyncCircuitsJob, SyncPlannedUnitsJob
├── Livewire/
│   ├── Dashboard/            # CircuitDashboard, WorkflowColumn, CircuitCard
│   ├── Charts/               # ApexCharts wrappers
│   └── Admin/                # SyncControl, UnlinkedPlanners
├── Models/                   # Circuit, UnitType, User, etc.
├── Policies/                 # CircuitPolicy
└── Services/WorkStudio/      # API integration

database/
├── migrations/               # Schema definitions
├── seeders/                  # UnitTypesSeeder, RegionsSeeder, etc.
└── factories/                # Test factories

FinalDraft/                   # Planning documents
├── IMPLEMENTATION_PLAN.md    # Detailed implementation plan
├── ARCHITECTURE.md           # System architecture
├── UnitTypes.json            # Unit type reference
├── PlannedUnits.json         # Sample API response
└── project-context.md        # This file
```

---

## Testing Strategy

- **Feature Tests:** Livewire components, sync jobs, authorization
- **Unit Tests:** Transformers, services, model methods
- **Browser Tests:** Pest 4 for drag-drop, theme switching

Run tests:
```bash
php artisan test --compact
php artisan test --filter=UnitType
```

---

## Common Tasks

### Add a new unit type
1. Add to `UnitTypesSeeder.php`
2. Run `php artisan db:seed --class=UnitTypesSeeder`
3. Cache clears automatically

### Run sync manually
```php
dispatch(new SyncCircuitsJob(['ACTIV']));
```

### Query aggregates
```php
// Circuit level
CircuitAggregate::where('circuit_id', $id)->latest('aggregate_date')->first();

// Planner productivity (date range)
PlannerDailyAggregate::where('user_id', $userId)
    ->whereBetween('aggregate_date', [$start, $end])
    ->get();
```

---

## Important Conventions

1. **Aggregate at sync time, not query time** - Compute totals when data arrives
2. **JSONB for flexible breakdowns** - Unit counts by type stored as JSON
3. **Normalized columns for filters** - `total_linear_ft`, `units_approved` for WHERE clauses
4. **Cache reference data** - UnitType lookups cached for 1 hour
5. **Eastern timezone** - All scheduling uses `America/New_York`
6. **No individual unit storage** - This is a management dashboard

---

## Related Documentation

| Document | Location | Purpose |
|----------|----------|---------|
| `IMPLEMENTATION_PLAN.md` | FinalDraft/ | Detailed phase-by-phase implementation |
| `ARCHITECTURE.md` | FinalDraft/ | System architecture diagrams |
| `WS_TRACKER_DOCS.md` | FinalDraft/ | Additional technical docs |
| `UnitTypes.json` | FinalDraft/ | Complete unit type reference (44 units) |
| `UnitList.json` | FinalDraft/ | Raw WorkStudio unit export |
| `PlannedUnits.json` | FinalDraft/ | Sample API response |
| `codebase_analysis.md` | Root | Comprehensive codebase analysis |

---

## Questions to Ask Before Making Changes

1. Does this change affect the aggregate-only architecture?
2. Should this data be stored or computed on-demand?
3. Is this a management view or a planner tool? (WS-Tracker is management only)
4. Does this need time-series tracking?
5. What hierarchy level does this affect?
