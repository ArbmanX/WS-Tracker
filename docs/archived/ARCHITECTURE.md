# WorkStudio Admin Dashboard - Architecture & Planning Document

> This document captures all architectural decisions, database structures, and implementation plans for the vegetation maintenance admin dashboard.

---

## Table of Contents

1. [Overview](#overview)
2. [Tech Stack](#tech-stack)
3. [Domain Context](#domain-context)
4. [Database Schema](#database-schema)
5. [API Integration](#api-integration)
6. [Sync Strategy](#sync-strategy)
7. [Caching Strategy](#caching-strategy)
8. [Real-Time Events](#real-time-events)
9. [User Roles & Permissions](#user-roles--permissions)
10. [Key Decisions Summary](#key-decisions-summary)

---

## Overview

### Purpose

Build an admin dashboard that integrates with GeoDigital WorkStudio API to provide:
- Better visualization of vegetation assessment circuits
- Intuitive filtering and sorting
- Drag-drop workflow management
- Historical progression tracking
- Permission status monitoring for planned units

### Phase 1 Scope

**1A: Circuit List Dashboard**
- Pull and cache circuit/assessment data from API
- Filter by: region, planner, modified date, scope year, program type
- Sort options for key columns, modified date
- Drag-drop circuits between workflow columns: Active → QC → Rework
- UI state changes persist for all users (stored separately from API data)
- Compile total miles per region and total planned miles to visualize per region. 
- Track miles planned per planner and keep historical data.
- Visualize: total miles planned per planner. 
- Visualize: progress bars, basic charts for miles/acres

**1B: Planned Units Aggregation**
- Pull planned units for circuits in "Active" state
- Store and display: total unit count, unique unit type counts, permission status breakdown
- Visualize permission progress (e.g., 50 approved / 50 pending out of 100 total)

### Future Phases (Not in Scope Yet)

- Work Jobs dashboard (for General Foremans)
- Planner-specific dashboard for permission tracking and customer interactions (this might be its own app using REQUESTJOB endpoint)
- Count of unique properties that have work and how much of the k
- Station "No Work" footage calculations for completion tracking
- REQUESTJOB integration for full assessment details

---

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Livewire 3, Alpine.js (bundled) |
| UI Components | Daisy UI (https://daisyui.com/docs/install/laravel/), Tailwind CSS v4 |
| Icons | wireui/heroicons |
| Real-Time | Laravel Reverb |
| Testing | Pest 4 (with pest-plugin-livewire) |
| Code Style | Laravel Pint |
| MCP Server | Laravel Boost (`php artisan boost:mcp`) |
| Cache | Redis (recommended) or Database driver |
| Monitor | Laravel Pulse |
| Logs | Spatie laravel-activity-log |
| Cache Helper | Spatie laravel-responsecache |
| Health | Spatie laravel-health |
| Backup | Spatie laravel-backup |
| Graphs & Charts | asantibanez/livewire-charts |

### Application Directory: `WS-Tracker`

### App Structure

```
app/
├── Actions/Auth/           # Auth actions (Logout, etc.)
├── Http/Controllers/       # Standard controllers
├── Livewire/
│   ├── Auth/              # Login, Register, ForgotPassword, etc.
│   ├── Concerns/          # Traits (HasToast)
│   ├── Forms/Auth/        # Form objects (LoginForm, RegisterForm)
│   ├── Settings/          # Account settings
│   ├── Dashboard.php
│   └── Home.php
├── Models/
├── Providers/
└── Support/               # Support classes (Toast)
```

### Commands

```bash
composer run dev          # Start all services (server, queue, logs, vite)
composer run test         # Run tests
php artisan test          # Run tests
vendor/bin/pint --dirty   # Format changed files
php artisan reverb:start  # Start WebSocket server
```

---

## Domain Context

### Business Overview

Vegetation maintenance contractor for PPL (utility company). WorkStudio by GeoDigital Solutions tracks all vegetation work on power line circuits.

### Workflow States

```
Assessment Created → Planner Takes Ownership → Planning (0-100%) →
 → QC → [REWORK loop] → CLOSED
```

### Data Hierarchy

```
Circuit (Assessment)
  └── Stations (pole-to-pole spans)
        ├── State: "No Work" OR has units attached
        ├── Can have multiple property owners
        └── Units (work items: brush, trim, removal, herbicide)
              ├── Has size (sqft for polygons, linear ft for lines, DBH for removals)
              ├── Has permission status (blank=pending, approved, etc.)
              └── Belongs to one station, one property owner
```

### Split Assessments

- Large circuits can be split into 2-5+ smaller assessments
- Main assessment + children share the same Work Order (WO#)
- Extensions differentiate them (e.g., 2025-1234-A, 2025-1234-B)
- Planned Units API returns all units when filtered by WO# only (no extension)
- Dashboard shows main assessment with expandable children, aggregated totals on main

### Key Data Points

**From Circuit List:**
- Total miles, start date, miles planned, total acres
- Region, planner (SS_TakenBy), modified date, status

**From Planned Units:**
- Total unit count, count by unit type
- Permission status breakdown (pending, approved, etc.)
- Planner per unit (for multi-planner tracking)

---

## Database Schema

### Core Tables

#### circuits

Primary table for assessment/circuit data from API.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Internal ID |
| job_guid | string unique | From API (SS_JOBGUID) - indexed |
| work_order | string | WO# - indexed |
| extension | string nullable | For split assessments (@, A, B, etc.) |
| parent_circuit_id | bigint FK nullable | Self-reference for splits |
| region_id | bigint FK | Reference to regions table |
| title | string | Circuit title |
| contractor | string nullable | Contractor name (VEGJOB_CONTRACTOR) |
| cycle_type | string nullable | Cycle type (VEGJOB_CYCLETYPE) |
| total_miles | decimal(10,2) | Total circuit miles |
| miles_planned | decimal(10,2) | Miles completed planning |
| percent_complete | decimal(5,2) | Planning progress |
| total_acres | decimal(10,2) | Total acres |
| start_date | date nullable | Assessment start date |
| api_modified_date | date | Modified date from API (date only) |
| api_status | string | Status from API (ACTIV, QC, REWORK, CLOSE) |
| api_data_json | json | Full API response backup |
| last_synced_at | timestamp | Last circuit list sync |
| last_planned_units_synced_at | timestamp nullable | Last planned units sync |
| planned_units_sync_enabled | boolean default true | False when closed |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp nullable | Soft delete |

**Indexes:** job_guid (unique), work_order, region_id, api_status, api_modified_date

#### circuit_ui_states

UI-only workflow state, separate from API data.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| circuit_id | bigint FK unique | One state per circuit |
| workflow_stage | enum | active, pending_permissions, qc, rework, closed |
| column_position | integer | Order within stage column |
| is_hidden | boolean default false | Sudo admin soft-hide from UI |
| hidden_by_user_id | bigint FK nullable | Who hid it |
| hidden_at | timestamp nullable | When hidden |
| created_at | timestamp | |
| updated_at | timestamp | |

**Workflow Stage Enum Values:**
- `active` - Default for new circuits
- `pending_permissions` - 100% planned, awaiting permissions
- `qc` - In quality control
- `rework` - Failed QC, needs rework
- `closed` - Completed

#### regions

Dynamic lookup table for regions (can add new via API sync).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| name | string | Display name (Central, Lancaster, etc.) |
| code | string unique | For API matching |
| created_at | timestamp | |
| updated_at | timestamp | |

**Seed Values:** Central, Lancaster, Lehigh, Harrisburg (add others as discovered)

### User & Assignment Tables

#### users (extend existing)

| Column | Type | Description |
|--------|------|-------------|
| ...existing... | | |
| api_username | string nullable unique | e.g., ASPLUNDH\jsmith - for linking SS_TakenBy |
| role | enum | sudo_admin, admin, planner, general_foreman |
| ws_credentials_verified_at | timestamp nullable | When credentials were last verified |
| ws_credentials_last_used_at | timestamp nullable | Last time credentials used for API call |
| ws_credentials_failed_at | timestamp nullable | Last credential failure |
| ws_credentials_fail_count | integer default 0 | Consecutive failures (reset on success) |
| requires_credential_setup | boolean default true | Prompt user to set up WS credentials |

#### user_ws_credentials

Encrypted WorkStudio credentials for API authentication.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| user_id | bigint FK unique | One credential set per user |
| ws_username | text | Encrypted with Laravel Crypt |
| ws_password | text | Encrypted with Laravel Crypt |
| created_at | timestamp | |
| updated_at | timestamp | |

**Security Notes:**
- Credentials encrypted at rest using Laravel's `Crypt` facade
- Decrypted only when making API calls
- Separate table allows tighter access control and audit

#### circuit_user (pivot)

Many-to-many: circuits ↔ users (planners assigned to circuits).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| circuit_id | bigint FK | |
| user_id | bigint FK | |
| assignment_source | enum | api_sync, manual |
| assigned_at | timestamp | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Unique Constraint:** (circuit_id, user_id)

#### user_regions (pivot)

Planners/GFs assigned to view specific regions.

| Column | Type | Description |
|--------|------|-------------|
| user_id | bigint FK | |
| region_id | bigint FK | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### unlinked_planners

Track API usernames that don't match local users (for admin resolution).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| api_username | string unique | e.g., ASPLUNDH\jsmith |
| linked_user_id | bigint FK nullable | Null until admin links |
| first_seen_at | timestamp | |
| last_seen_at | timestamp | Updated each sync |
| occurrence_count | integer | How many circuits/units reference this |
| is_resolved | boolean default false | True once linked or ignored |
| resolved_by_user_id | bigint FK nullable | |
| resolved_at | timestamp nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

### Historical Tracking

#### circuit_snapshots

Daily + event-based snapshots for progression tracking.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| circuit_id | bigint FK | Indexed |
| miles_planned | decimal(10,2) | |
| percent_complete | decimal(5,2) | |
| total_acres | decimal(10,2) | |
| api_status | string | |
| workflow_stage | string | Capture UI state too |
| snapshot_type | enum | daily, status_change, manual |
| snapshot_date | date | Indexed |
| created_at | timestamp | |

**Index:** (circuit_id, snapshot_date)

**Snapshot Strategy (Hybrid):**
- Daily snapshots at sync time for all non-closed circuits
- Event snapshots when status or key values change
- Never update/delete - insert only

### Planned Units Tables

#### planned_units

Unit data from Planned Units API.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| circuit_id | bigint FK | Main assessment only - indexed |
| unit_guid | string unique | From API (SSUNITS_OBJECTID) - indexed |
| unit_type_id | bigint FK | Reference to unit_types |
| description | string | Unit description (UNITS_DESCRIPTIO) |
| unit_code | string | Short code (SPM, HCB, etc.) |
| length_work | decimal(12,2) nullable | Linear feet (for line units) |
| acres | decimal(12,4) nullable | Acres (for polygon units) |
| num_trees | integer nullable | Tree count (for removals) |
| permission_status_id | bigint FK | Reference to permission_statuses |
| planner_api_username | string | Raw from API (VEGUNIT_FORESTER) |
| planner_user_id | bigint FK nullable | Linked if match found |
| assessed_date | date nullable | When unit was assessed |
| station_name | string nullable | Station reference |
| species | string nullable | Tree species |
| geometry_json | json nullable | GeoJSON for future map display |
| api_data_json | json | Full unit data backup |
| last_synced_at | timestamp | |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp nullable | Soft delete |

**Indexes:**
- `unit_guid` (unique)
- `(circuit_id, permission_status_id)`

#### planned_unit_snapshots

Aggregated unit counts for historical tracking.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| circuit_id | bigint FK | Indexed |
| snapshot_date | date | Indexed |
| total_units | integer | |
| units_by_type | json | {"brush": 45, "trim": 30, ...} |
| units_by_permission | json | {"approved": 50, "pending": 50} |
| units_by_planner | json | {"jsmith": 40, "bjones": 35, ...} |
| created_at | timestamp | |

**Index:** (circuit_id, snapshot_date)

#### unit_types

Dynamic lookup for unit types.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| name | string | Hand Cut Brush, Trim, Removal, etc. |
| code | string unique | HCB, TRIM, REMOVAL - for API matching |
| size_unit | enum | sqft, linear_ft, dbh |
| is_active | boolean default true | For filtering |
| created_at | timestamp | |
| updated_at | timestamp | |

#### permission_statuses

Dynamic lookup for permission statuses.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| name | string | Pending, Approved, Denied, etc. |
| code | string unique | Matches API values (blank = pending) |
| display_order | integer | For UI sorting |
| color | string nullable | Hex or Tailwind class for badges |
| created_at | timestamp | |
| updated_at | timestamp | |

### Sync Management

#### api_status_configs

Configure which statuses to sync from API.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| status_code | string unique | ACTIV, QC, REWORK, CLOSED |
| filter_value | string | Value sent to API |
| filter_caption | string | In Progress, Quality Control, etc. |
| is_sync_enabled | boolean default true | Include in scheduled sync? |
| sync_priority | integer | Order of API calls |
| display_order | integer | For UI if showing status filters |
| created_at | timestamp | |
| updated_at | timestamp | |

#### sync_logs

Track all sync operations.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| sync_type | enum | circuit_list, planned_units, full |
| status | enum | started, completed, failed, warning |
| triggered_by | enum | scheduled, manual, workflow_event |
| triggered_by_user_id | bigint FK nullable | Who triggered (for manual) |
| credential_user_id | bigint FK nullable | Whose credentials were used |
| credential_rotated | boolean default false | Was this a rotation pick? |
| circuits_processed | integer default 0 | |
| units_processed | integer default 0 | |
| error_message | text nullable | |
| warning_message | text nullable | For timeout warnings, etc. |
| started_at | timestamp | |
| completed_at | timestamp nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## API Integration

### WorkStudio API Base URLs

| Environment | Base URL |
|-------------|----------|
| Distribution | `https://ppl02.geodigital.com:8372/ddoprotocol/` |
| Transmission | `https://ppl01.geodigital.com:8380/ddoprotocol/` |

Both environments have identical data structures - URLs can be swapped as needed. Primary focus is Distribution.

### Authentication & Credential Management

#### Overview

Users authenticate to the dashboard using their WorkStudio credentials. Each user's API calls use their own credentials, providing audit trails on the WS side.

#### User Onboarding Flow

1. Admin creates user account with temporary password
2. User logs in with temporary password
3. System prompts user to set up WS credentials (admin can enable/disable this prompt)
4. User enters WS username and password
5. System validates credentials against WS API
6. On success: credentials encrypted and stored, `ws_credentials_verified_at` set
7. On failure: user can retry or skip (account flagged as needing verification)

#### Credential Storage

**Service Account (Fallback)** - stored in `.env`:
```
WORKSTUDIO_SERVICE_USERNAME=ASPLUNDH\serviceaccount
WORKSTUDIO_SERVICE_PASSWORD=password
```

**User Credentials** - stored in `user_ws_credentials` table:
- Encrypted using Laravel's `Crypt` facade
- Separate table for tighter access control

#### Credential Usage Strategy

| Sync Type | Credential Used |
|-----------|-----------------|
| Circuit List (automated) | Rotate through verified users' credentials |
| Circuit List (manual by admin) | The admin's own credentials |
| Planned Units | Planner assigned to that circuit |

#### Credential Rotation (Circuit List Sync)

For automated daily syncs, rotate through users with verified credentials:
1. Get all users where `ws_credentials_verified_at` is not null
2. Order by `ws_credentials_last_used_at` ascending (least recently used first)
3. Pick next credential in rotation
4. Update `ws_credentials_last_used_at` after use
5. Track `credential_user_id` in `sync_logs`

#### Planned Units - Credential Selection

Priority order when fetching planned units for a circuit:

1. **Try each assigned planner** (from `circuit_user` pivot)
   - Try all until one works
2. **If all planners fail or have no credentials** → Fall back to credential rotation pool
3. **If rotation pool empty** → Use service account from `.env`

#### Credential Failure Handling

When credentials fail (401 response):
- Increment `ws_credentials_fail_count` on user
- Set `ws_credentials_failed_at` timestamp
- After 3 consecutive failures: flag account for re-verification
- Log warning, try next credential in rotation

#### Health Check

Base URL `https://ppl02.geodigital.com:8372` returns `200 OK` when server is up.
- Check before sync attempts
- If down: log warning, retry later
- No automated syncing on weekends

#### Timeouts

- **Default timeout:** 60 seconds
- **On timeout:** Log warning, retry with next credential
- Future: sudo admin dashboard will show server monitoring and logs

### Endpoints & View GUIDs

| View | GUID | Purpose |
|------|------|---------|
| Vegetation Assessments | `{A856F956-88DF-4807-90E2-7E12C25B5B32}` | Circuit list |
| Work Jobs | `{546D9963-9242-4945-8A74-15CA83CDA537}` | Future: work job list |
| Planned Units | `{985AECEF-D75B-40F3-9F9B-37F21C63FF4A}` | Unit details |


### Error Responses

| Scenario | Response |
|----------|----------|
| Bad credentials | HTTP 401: `<HTML><BODY><B>401 Unauthorized</B></BODY></HTML>` |
| Bad protocol | HTTP 200: `{"Protocol":"ERROR","errorMessage":"Server received an invalid protocol..."}` |
| Server down | Connection timeout / no response |

### Field Mappings

#### Circuit List (Vegetation Assessments)

| API Column | Model Field | Notes |
|------------|-------------|-------|
| `SS_JOBGUID` | `job_guid` | Primary identifier (unique) |
| `SS_WO` | `work_order` | Work order number |
| `SS_EXT` | `extension` | For split assessments (@, A, B, etc.) |
| `REGION` | `region_id` | Lookup/create in regions table |
| `SS_TITLE` | `title` | Circuit title |
| `VEGJOB_LENGTH` | `total_miles` | Total circuit miles |
| `VEGJOB_LENGTHCOMP` | `miles_planned` | Miles completed |
| `VEGJOB_PRCENT` | `percent_complete` | Planning progress |
| `VEGJOB_PROJACRES` | `total_acres` | Projected acres |
| `WPStartDate_Assessment_Xrefs_WP_STARTDATE` | `start_date` | Parse `/Date(...)` format |
| `SS_EDITDATE` | `api_modified_date` | Last modified (ignore time) |
| `WSREQ_STATUS` | `api_status` | ACTIV, QC, REWORK, CLOSE |
| `SS_TAKENBY` | planner link | e.g., `ASPLUNDH\plongenecker` |
| `VEGJOB_CONTRACTOR` | `contractor` | Store for reference |
| `VEGJOB_CYCLETYPE` | `cycle_type` | Cycle Maintenance - Trim, etc. |

**Ignored columns:** All `WSREQ_*` columns, `VEGJOB_FORESTER`, `VEGJOB_GF`, `SS_READONLY`, etc.

#### Planned Units

| API Column | Model Field | Notes |
|------------|-------------|-------|
| `SSUNITS_OBJECTID` | `unit_guid` | Primary identifier |
| `SS_WO` | circuit link | Link to parent circuit by WO |
| `UNITS_DESCRIPTIO` | `description` | "Single Phase - Manual", etc. |
| `VEGUNIT_UNIT` | `unit_code` | "SPM", "HCB", etc. |
| `VEGUNIT_PERMSTAT` | `permission_status_id` | "Approved", blank=Pending |
| `VEGUNIT_FORESTER` | `planner_api_username` | Planner name for this unit |
| `JOBVEGETATIONUNITS_LENGTHWRK` | `length_work` | Linear feet (for line units) |
| `JOBVEGETATIONUNITS_ACRES` | `acres` | Acres (for polygon units) |
| `JOBVEGETATIONUNITS_NUMTREES` | `num_trees` | Tree count (for removals) |
| `VEGUNIT_ASSDDATE` | `assessed_date` | When unit was assessed |
| `VEGJOB_REGION` | reference | Region (from circuit) |
| `STATIONS_STATNAME` | `station_name` | Station reference |
| `VEGUNIT_SPECIES` | `species` | Tree species |
| `SSUNITS_GEOMETRY` | `geometry_json` | Store for future map display |

**Date Parsing Rules:**
- Parse `/Date(YYYY-MM-DD)/` and `/Date(YYYY-MM-DDTHH:mm:ss.sssZ)/` formats
- Ignore time portion, store date only
- Ignore dates before 2010 (treat as null)

### Response Transformation Strategy

**Approach: Dedicated Transformer Classes (Option D)**

Separation of concerns — API service handles HTTP, transformer handles data shape.

```
app/
├── Services/
│   └── WorkStudio/
│       ├── WorkStudioApiService.php    # HTTP client, auth, retry logic
│       ├── ApiCredentialManager.php    # Handle per-user credentials
│       └── Transformers/
│           ├── DDOTableTransformer.php # Generic DDOTable → Collection
│           ├── CircuitTransformer.php  # Circuit-specific field mapping
│           └── PlannedUnitTransformer.php
```

**Benefits:**
- Store raw response in `api_data_json`, transform on demand
- Selectively extract only needed fields
- Clean field name mapping (SS_JOBGUID → job_guid)
- Easy to adjust when API changes field names

---

## Sync Strategy

### Scheduled Sync

**Schedule:** Weekdays only (Mon-Fri)

| Status | Frequency | Schedule |
|--------|-----------|----------|
| `ACTIV` | Twice daily | 4:30 AM and 4:30 PM |
| `QC` | Weekly | Monday 4:30 AM |
| `REWORK` | Weekly | Monday 4:30 AM |
| `CLOSE` | Weekly | Monday 4:30 AM |

```php
// routes/console.php or scheduler

// ACTIV - twice daily on weekdays
$schedule->job(new SyncCircuitsJob(['ACTIV']))
    ->weekdays()
    ->at('04:30');

$schedule->job(new SyncCircuitsJob(['ACTIV']))
    ->weekdays()
    ->at('16:30');

// QC, REWORK, CLOSE - weekly on Monday
$schedule->job(new SyncCircuitsJob(['QC', 'REWORK', 'CLOSE']))
    ->weekdays()
    ->mondays()
    ->at('04:30');
```

### Force Sync (Sudo Admin)

Sudo admin can trigger manual sync with selectable status checklist:
- [ ] ACTIV (In Progress)
- [ ] QC (Quality Control)
- [ ] REWORK
- [ ] CLOSE (Closed)

### Sync Flow

```
1. Create sync_log (status: started, type: scheduled)

2. Health check: ping base URL
   └── If down → log warning, exit

3. FOR EACH enabled status in api_status_configs (by priority):
   │
   │  RETRY WRAPPER (max 5 attempts, exponential backoff):
   │  ├── Call GETVIEWDATA with status filter
   │  ├── Delay after every 5 consecutive calls
   │  ├── If fail → retry up to 5x
   │  └── If still fails → ABORT ENTIRE SYNC, log error, exit
   │
   └── FOR EACH circuit in response:
       │
       ├── Lookup by job_guid
       │   ├── EXISTS: Compare api_modified_date
       │   │   ├── Changed → Update circuit, create snapshot
       │   │   └── Unchanged → Skip (touch last_synced_at only)
       │   │
       │   └── NEW: Insert circuit + circuit_ui_state (stage: active)
       │
       ├── Sync planner (SS_TakenBy) → circuit_user pivot
       │   └── If no matching user → upsert unlinked_planners
       │
       └── If workflow_stage = 'pending_permissions':
           └── Queue: SyncPlannedUnitsJob

4. Create daily snapshots for all non-closed circuits

5. Update sync_log (status: completed)

6. Clear cache, pre-warm common queries

7. Broadcast: SyncCompletedEvent → all connected clients refresh
```

### Planned Units Sync (Event-Triggered)
*spatie laravel-backup*

**Triggers:**
- Circuit moved to `pending_permissions` (UI action)
- Scheduled sync finds circuit in `pending_permissions`
- Admin force sync

**Skip if:**
- Circuit api_status = CLOSED AND workflow_stage = closed
- Circuit marked closed by sudo admin

**Process:**
```js

1. Call GETVIEWDATA for Planned Units
   └── FilterName: "WO#", FilterValue: "{work_order}" (no extension)

2. FOR EACH unit in response:
   ├── Upsert by unit_guid
   ├── Link planner_user_id if api_username matches
   │   └── Else flag in unlinked_planners
   └── Link/create unit_type and permission_status

3. Create planned_unit_snapshot (aggregate counts)

4. Update circuit.last_planned_units_synced_at

5. Broadcast: PlannedUnitsSyncedEvent(circuit_id)
```

### Sync Failure Handling

- Retry each status API call up to 5 times with exponential backoff
- Add delay after every 5 consecutive API calls
- If any status fails after 5 retries → abort entire sync
- Log error details in sync_log
- Do NOT proceed with partial data

---

## Caching Strategy

### Cache Driver

**Recommended:** Redis (supports tags properly)
**Fallback:** Database cache driver
*spatie laravel-responsecache*

### Cache Structure

```php
// Circuit list cache (by filter combinations)
cache()->tags(['circuits'])
    ->remember("circuits:region:{$regionId}:status:{$status}", $ttl, $callback);

// Single circuit with relationships
cache()->tags(['circuits', "circuit:{$id}"])
    ->remember("circuit:{$id}:full", $ttl, $callback);

// Dashboard aggregates
cache()->tags(['dashboard'])
    ->remember("dashboard:region:{$regionId}:totals", $ttl, $callback);
```

### Cache Invalidation

| Event | Action |
|-------|--------|
| Scheduled sync completes | Flush all tags, pre-warm |
| Circuit workflow stage changed | Flush `circuits` tag |
| Admin force sync | Flush all, rebuild |
| User filters/sorts | No invalidation (read-only) |

### Pre-Warming

After sync, pre-warm cache for:
- All regions circuit lists
- Common filter combinations
- Dashboard aggregate totals

---

## Real-Time Events

### Laravel Reverb Setup

```bash
php artisan install:broadcasting
php artisan reverb:start
```

### Broadcast Events

| Event | Channel | Triggers |
|-------|---------|----------|
| `SyncCompletedEvent` | `dashboard` | Full dashboard refresh |
| `PlannedUnitsSyncedEvent` | `circuit.{id}` | Refresh unit aggregates |
| `CircuitWorkflowChangedEvent` | `dashboard` | Move circuit card (no full refresh) |
| `CircuitUpdatedEvent` | `circuit.{id}` | Refresh single circuit card |

### Channel Authorization

```php
// routes/channels.php

// All authenticated users can listen to dashboard
Broadcast::channel('dashboard', fn ($user) => true);

// Circuit-specific - check user can view this circuit
Broadcast::channel('circuit.{circuitId}', function ($user, $circuitId) {
    return $user->canViewCircuit($circuitId);
});
```

### Frontend Integration

```php
// Livewire component
protected $listeners = [
    'echo:dashboard,SyncCompletedEvent' => 'refreshDashboard',
    'echo:dashboard,CircuitWorkflowChangedEvent' => 'handleWorkflowChange',
];

// Or Alpine + Echo
Echo.channel('dashboard')
    .listen('SyncCompletedEvent', (e) => {
        Livewire.dispatch('refresh-dashboard');
    });
```

---

## User Roles & Permissions

### Role Definitions

*spatie laravel-permissions package*

| Role | Description |
|------|-------------|
| `sudo_admin` | Force sync, permanent delete, see all data, configure settings |
| `admin` | Manage workflow states, view all regions |
| `planner` | View only their circuits or assigned regions |
| `general_foreman` | View only their circuits or assigned regions |

### Access Control

| Action | sudo_admin | admin | planner | general_foreman |
|--------|------------|-------|---------|-----------------|
| View all circuits | ✓ | ✓ | - | - |
| View assigned circuits | ✓ | ✓ | ✓ | ✓ |
| View by region assignment | ✓ | ✓ | ✓ | ✓ |
| Change workflow stage | ✓ | ✓ | - | - |
| Force sync | ✓ | - | - | - |
| Hide circuit (soft) | ✓ | - | - | - |
| Link unlinked planners | ✓ | ✓ | - | - |
| Manage users | ✓ | - | - | - |
| Configure settings | ✓ | - | - | - |

### Circuit Visibility Logic

```php
// Planner sees circuit if:
// - They are assigned to it (circuit_user pivot)
// - OR they are assigned to its region (user_regions pivot)
// - OR they have planned units on it (planner_user_id in planned_units)

// General Foreman sees circuit if:
// - They are assigned to it
// - OR they are assigned to its region

// Supervison sees circuit if:
// - owned by anyone
// - and can see every region

// SUDO Admin has complete access. 

```

---

## Key Decisions Summary

| Topic | Decision |
|-------|----------|
| Application folder | `WS-Tracker` |
| UI Components | Daisy UI |
| Circuit identity | `job_guid` is unique; WO# shared across splits with different extensions |
| Regions | Dynamic lookup table (new regions can appear from API) |
| UI vs API status | Separate fields: `api_status` (from API) + `workflow_stage` (UI-only) |
| Historical snapshots | Hybrid: daily snapshots + event-based on changes |
| Planner linking | Link to local users table; flag unlinked for admin resolution |
| Split assessments | Show main with expandable children, aggregated totals on main |
| Initial workflow stage | Always `active` for new circuits |
| Cache warming | Pre-warm after sync |
| Sync schedule | ACTIV twice daily (4:30 AM/PM); QC/REWORK weekly (Monday) |
| Sync failure | Retry 5x per call, then abort entire sync |
| API rate limiting | Delay after every 5 consecutive calls |
| Real-time | Laravel Reverb with channel-based events |
| User auth | Standalone, admin-provisioned accounts with WS credential verification |
| Refresh strategy | Polling + event-triggered via Reverb |
| Response transformation | Dedicated Transformer classes (Option D) |
| Timeout | 60 seconds, log warning on timeout |
| Date parsing | Ignore time portion; ignore dates before 2010 |

---

## Implementation Roadmap

### Phase 1A: API Service Foundation

- [ ] Create `WorkStudioApiService` base class (HTTP client, retry logic, health check)
- [ ] Create `ApiCredentialManager` for credential rotation and per-user credentials
- [ ] Create `DDOTableTransformer` for generic DDOTable → Collection parsing
- [ ] Create `CircuitTransformer` with field mappings
- [ ] Create `PlannedUnitTransformer` with field mappings
- [ ] Create database migrations for all tables
- [ ] Create Eloquent models with relationships

### Phase 1B: Sync Jobs

- [ ] Create `SyncCircuitsJob` with status filtering
- [ ] Create `SyncPlannedUnitsJob` for individual circuits
- [ ] Implement scheduled sync (ACTIV daily, others weekly)
- [ ] Implement force sync for sudo admin with status checklist
- [ ] Implement snapshot creation logic

### Phase 1C: Dashboard UI

- [ ] Create circuit list Livewire component
- [ ] Implement filtering (region, planner, date)
- [ ] Implement sorting
- [ ] Implement drag-drop workflow columns
- [ ] Create planned units aggregation display
- [ ] Implement charts/visualizations

### Future Phases

- [ ] Work Jobs dashboard (General Foreman view)
- [ ] Planner dashboard for permission tracking
- [ ] Station "No Work" footage calculations
- [ ] Server monitoring for sudo admin
- [ ] Map display with unit geometries

