# WS-Tracker Project Context

> **Purpose:** This document provides context for AI assistants working on this project.
> **Last Updated:** January 13, 2026
> **Status:** Active Development - Phases 0, 1A, 1B Complete (Environment, Database, API Services)
> **Latest Review:** See `CODE_REVIEW_REPORT.md` for recent code quality assessment

---

## Current Development Status

### What's Built

#### Phase 0: Environment Setup ✅
- **PostgreSQL** - Configured with proper credentials
- **config/workstudio.php** - Complete API configuration
- **NPM packages** - livewire-sortable, apexcharts installed
- **Spatie packages** - laravel-permission, laravel-activitylog published

#### Phase 1A: Database Foundation ✅
- **PHP Enums** - 6 enums (WorkflowStage, AssignmentSource, SnapshotType, SyncType, SyncStatus, SyncTrigger)
- **PostgreSQL Enums** - Type-safe enum columns at database level
- **All Migrations** - 21 migrations covering all tables
- **Core Models** - 14 models (Region, Circuit, CircuitAggregate, etc.) with relationships
- **Factories** - 7 factories for testing
- **Seeders** - Reference data (4 regions, 44 unit types, 4 roles, 14 permissions)

#### Phase 1B: API Service Layer ✅
- **WorkStudioApiService** - HTTP client with Laravel's native retry logic, exponential backoff, and health checks
- **Http::workstudio() Macro** - Centralized HTTP configuration (timeouts, SSL, connect timeout) in WorkStudioServiceProvider
- **ApiCredentialManager** - Credential rotation and failure tracking
- **DDOTableTransformer** - Generic DDOTable response parsing
- **CircuitTransformer** - Circuit field mapping with date parsing
- **PlannedUnitAggregateTransformer** - Aggregate-only transformation
- **AggregateCalculationService** - Compute aggregates from raw API data
- **AggregateStorageService** - Persist aggregates to database
- **AggregateDiffService** - Compare old vs new for change detection
- **AggregateQueryService** - Query interface for all hierarchy levels
- **WorkStudioServiceProvider** - Registered in bootstrap/providers.php

#### Testing ✅ (Partial)
- **138 tests passing** (4 PostgreSQL-specific skipped on SQLite)
- **Coverage includes:** Models, services, transformers, migrations
- **Laravel Pint** - Code formatted and passing

#### UI Prototype (Partial)
- **UI Prototype** - Full Kanban dashboard with mock data (ready for backend wiring)
- **PPL Brand Themes** - Custom DaisyUI themes (ppl-light, ppl-dark)
- **CircuitDashboard Component** - Livewire component with filters, stats, Kanban board, charts
- **UnitType Model** - Complete with 44 unit types and cached lookups

### What's Remaining
- **Phase 1C:** Sync jobs and scheduling (SyncCircuitsJob, SyncPlannedUnitsJob)
- **Phase 1D:** Theme system refinements
- **Phase 1E:** Wire dashboard to real data
- **Phase 1F:** Charts with ApexCharts
- **Phase 1G:** Admin features (sync control, unlinked planners)
- **Phase 1H:** Browser tests and polish

### To View the UI Prototype
```bash
npm run build   # or npm run dev
php artisan serve
# Navigate to /dashboard (requires login)
```

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
- **Timeouts:** 60s request, 10s connect, 5 retries with exponential backoff (max 30s)

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

## Service Layer Architecture ✅ BUILT

```
app/Services/WorkStudio/
├── WorkStudioApiService.php           # ✅ HTTP client, auth, native retry with exponential backoff
├── ApiCredentialManager.php           # ✅ Credential rotation, success/failure tracking
├── Contracts/
│   └── WorkStudioApiInterface.php     # ✅ Service contract
├── Aggregation/
│   ├── AggregateCalculationService.php   # ✅ Compute aggregates from API
│   ├── AggregateStorageService.php       # ✅ Persist to database
│   ├── AggregateDiffService.php          # ✅ Compare for changes
│   └── AggregateQueryService.php         # ✅ Query interface
└── Transformers/
    ├── DDOTableTransformer.php        # ✅ Parse DDOTable format
    ├── CircuitTransformer.php         # ✅ Circuit field mapping
    └── PlannedUnitAggregateTransformer.php  # ✅ Aggregate-only transform
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

## Brand Colors & Themes

### PPL Electric Utilities
- Primary: `#1882C5` (Cyan Cornflower Blue) - Actions, links, primary buttons
- Secondary: `#28317E` (St. Patrick's Blue) - Headers, emphasis

### Asplundh (Contractor)
- Accent: `#E27434` (Red Damask) - Warnings, highlights
- Neutral: `#00598D` (Orient Blue) - Secondary elements

### DaisyUI Theme Configuration
Custom themes defined in `resources/css/app.css`:
- `ppl-light` - Light mode with PPL brand colors
- `ppl-dark` - Dark mode with adjusted PPL colors
- `light` / `dark` - Standard DaisyUI themes

**Theme Switching:** Available in the dashboard header dropdown. Uses `data-theme` attribute on `<html>` element.

```css
/* Example: Apply PPL light theme */
<html data-theme="ppl-light">
```

---

## File Structure Reference

```
app/
├── Enums/                    # ✅ BUILT - 6 PHP enums
│   ├── WorkflowStage.php
│   ├── AssignmentSource.php
│   ├── SnapshotType.php
│   ├── SyncType.php
│   ├── SyncStatus.php
│   └── SyncTrigger.php
├── Jobs/                     # [PLANNED] SyncCircuitsJob, SyncPlannedUnitsJob
├── Livewire/
│   ├── Dashboard/
│   │   └── CircuitDashboard.php    # ✅ BUILT - Main dashboard with mock data
│   ├── Charts/               # [PLANNED] ApexCharts wrappers
│   ├── Admin/                # [PLANNED] SyncControl, UnlinkedPlanners
│   ├── Settings/             # ✅ BUILT - Profile, Password, Appearance, 2FA
│   └── Actions/              # ✅ BUILT - Logout
├── Models/                   # ✅ BUILT - 14 models
│   ├── User.php              # ✅ Extended with HasRoles, WS relationships
│   ├── UnitType.php          # ✅ 44 unit types with cached lookups
│   ├── Region.php            # ✅ 4 PPL regions
│   ├── Circuit.php           # ✅ Core domain model
│   ├── CircuitUiState.php    # ✅ Workflow stage management
│   ├── CircuitSnapshot.php   # ✅ Point-in-time snapshots
│   ├── CircuitAggregate.php  # ✅ Daily aggregates per circuit
│   ├── PlannerDailyAggregate.php   # ✅ Planner productivity
│   ├── RegionalDailyAggregate.php  # ✅ Regional rollups
│   ├── SyncLog.php           # ✅ Sync operation logging
│   ├── PermissionStatus.php  # ✅ Approved/Refused/Pending
│   ├── ApiStatusConfig.php   # ✅ ACTIV/QC/REWORK/CLOSE config
│   ├── UserWsCredential.php  # ✅ Encrypted WS credentials
│   └── UnlinkedPlanner.php   # ✅ Planners not yet linked to users
├── Policies/                 # [PLANNED] CircuitPolicy
└── Services/WorkStudio/      # ✅ BUILT - API integration (see Service Layer section)

resources/
├── css/
│   └── app.css               # ✅ BUILT - PPL brand themes (ppl-light, ppl-dark)
├── js/
│   └── app.js                # ✅ BUILT - livewire-sortable, ApexCharts
└── views/
    ├── livewire/dashboard/
    │   └── circuit-dashboard.blade.php  # ✅ BUILT - Full Kanban UI
    ├── components/layouts/   # ✅ BUILT - App layout with Flux sidebar
    └── dashboard.blade.php   # ✅ BUILT - Uses CircuitDashboard component

database/
├── migrations/               # ✅ BUILT - 21 migrations (all complete)
├── seeders/                  # ✅ BUILT - 5 seeders
│   ├── DatabaseSeeder.php
│   ├── UnitTypesSeeder.php   # 44 unit types
│   ├── RegionsSeeder.php     # 4 PPL regions
│   ├── RolesAndPermissionsSeeder.php  # 4 roles, 14 permissions
│   ├── ApiStatusConfigsSeeder.php
│   └── PermissionStatusesSeeder.php
└── factories/                # ✅ BUILT - 7 factories
    ├── RegionFactory.php
    ├── CircuitFactory.php
    ├── CircuitUiStateFactory.php
    ├── CircuitSnapshotFactory.php
    ├── CircuitAggregateFactory.php
    ├── PlannerDailyAggregateFactory.php
    └── SyncLogFactory.php

tests/                        # ✅ 138 tests passing (4 PostgreSQL-specific skipped on SQLite)
├── Feature/
│   ├── Auth/                 # ✅ Authentication, registration, 2FA
│   ├── Settings/             # ✅ Profile, password, 2FA settings
│   ├── Models/
│   │   ├── CircuitTest.php   # ✅ 11 tests - relationships, scopes
│   │   └── UnitTypeTest.php  # ✅ 10 tests - caching, lookups
│   ├── Database/
│   │   └── MigrationTest.php # ✅ 8 tests - schema validation
│   └── Services/WorkStudio/
│       ├── ApiCredentialManagerTest.php            # ✅ Credential rotation
│       ├── Transformers/                           # ✅ All transformer tests
│       └── Aggregation/                            # ✅ All aggregation service tests
└── Unit/
    └── Services/WorkStudio/Transformers/           # ✅ DDOTableTransformerTest

docs/                         # Planning documents
├── IMPLEMENTATION_PLAN.md    # Detailed phase-by-phase implementation
├── IMPLEMENTATION_PHASES.md  # Phase breakdown
├── phases/                   # Individual phase documentation
│   └── PHASE_1A_DATABASE_FOUNDATION.md
├── project-context.md        # This file
├── WS_TRACKER_DOCS.md        # API documentation
├── UnitTypes.json            # Unit type reference
├── PlannedUnits.json         # Sample API response
└── UI_DESIGN_SYSTEM.md       # ✅ BUILT - Complete DaisyUI component reference
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
| `CODE_REVIEW_REPORT.md` | docs/ | **NEW** - Code quality assessment & recommendations |
| `IMPLEMENTATION_PLAN.md` | docs/ | Detailed phase-by-phase implementation |
| `IMPLEMENTATION_PHASES.md` | docs/ | Phase breakdown with task lists |
| `UI_DESIGN_SYSTEM.md` | docs/ | **NEW** - DaisyUI component library & patterns |
| `UI_MIGRATION_PLAN.md` | docs/ | **NEW** - Flux UI → DaisyUI migration guide |
| `WS_TRACKER_DOCS.md` | docs/ | API documentation & endpoints |
| `UnitTypes.json` | docs/ | Complete unit type reference (44 units) |
| `PlannedUnits.json` | docs/ | Sample API response |
| `codebase_analysis.md` | docs/ | Comprehensive codebase analysis |
| `.claude/agents/DaisyUI.md` | .claude/ | DaisyUI 5 design agent guidelines |

---
<!-- TODO -->
## Known Technical Debt

From the latest code review (`CODE_REVIEW_REPORT.md`):

| Priority | Issue | Location |
|----------|-------|----------|
| High | SSL verification disabled (`verify => false`) | `WorkStudioServiceProvider.php` |
| High | No tests for `WorkStudioApiService` retry logic | `tests/` |
| Medium | Default credentials in config file | `config/workstudio.php` |
| Medium | `$currentUserId` singleton state (potential leak) | `WorkStudioApiService.php` |

---

## Questions to Ask Before Making Changes

1. Does this change affect the aggregate-only architecture?


2. Should this data be stored or computed on-demand?


3. Is this a management view or a planner tool? (WS-Tracker is management only)


4. Does this need time-series tracking?


5. What hierarchy level does this affect?
