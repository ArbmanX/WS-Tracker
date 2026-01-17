# Database Schema Reference

## Overview

WS-Tracker uses PostgreSQL with JSONB support for flexible data storage. The database contains 29 tables organized into several functional areas.

## Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           CORE DOMAIN                                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  regions ─────────────────────┐                                             │
│  └── id, name, code           │                                             │
│                               │                                             │
│  circuits ◄───────────────────┤                                             │
│  ├── region_id (FK)           │                                             │
│  ├── parent_circuit_id (FK)───┤ (self-referential for splits)               │
│  ├── job_guid (unique)        │                                             │
│  ├── work_order, extension    │                                             │
│  ├── api_status               │                                             │
│  ├── user_modified_fields     │ (JSONB - tracks user edits)                │
│  └── is_excluded              │                                             │
│                               │                                             │
│  circuit_ui_states ───────────┤                                             │
│  ├── circuit_id (FK, unique)  │ (one-to-one with circuits)                  │
│  ├── workflow_stage           │                                             │
│  └── stage_position           │                                             │
│                               │                                             │
│  circuit_user ────────────────┤ (many-to-many pivot)                        │
│  ├── circuit_id (FK)          │                                             │
│  ├── user_id (FK)             │                                             │
│  └── assignment_source        │                                             │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                         AGGREGATES & SNAPSHOTS                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  circuit_aggregates                                                         │
│  ├── circuit_id (FK)                                                        │
│  ├── aggregate_date                                                         │
│  ├── total_units, total_linear_ft, total_acres, total_trees                 │
│  ├── units_approved, units_pending, units_refused                           │
│  ├── unit_counts_by_type (JSONB)                                            │
│  ├── linear_ft_by_type (JSONB)                                              │
│  ├── planner_distribution (JSONB)                                           │
│  └── is_rollup (boolean for EOD consolidation)                              │
│                                                                              │
│  circuit_snapshots                                                          │
│  ├── circuit_id (FK)                                                        │
│  ├── snapshot_type (daily|status_change|manual)                             │
│  ├── snapshot_date                                                          │
│  └── metrics_json (JSONB)                                                   │
│                                                                              │
│  planned_units_snapshots                                                    │
│  ├── circuit_id (FK)                                                        │
│  ├── work_order                                                             │
│  ├── snapshot_trigger                                                       │
│  ├── content_hash (for deduplication)                                       │
│  └── raw_json (JSONB - full planned units data)                             │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                        REGIONAL AGGREGATES                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  regional_daily_aggregates                                                  │
│  ├── region_id (FK)                                                         │
│  ├── aggregate_date                                                         │
│  ├── active_circuits, qc_circuits, closed_circuits                          │
│  ├── total_miles, miles_planned                                             │
│  └── status_breakdown (JSONB)                                               │
│                                                                              │
│  regional_weekly_aggregates                                                 │
│  ├── region_id (FK)                                                         │
│  ├── week_starting, week_ending                                             │
│  ├── excluded_circuits                                                      │
│  └── daily_breakdown (JSONB)                                                │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                        PLANNER AGGREGATES                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  planner_daily_aggregates                                                   │
│  ├── user_id (FK)                                                           │
│  ├── region_id (FK)                                                         │
│  ├── aggregate_date                                                         │
│  ├── circuits_worked                                                        │
│  ├── total_units_assessed                                                   │
│  └── circuit_breakdown (JSONB)                                              │
│                                                                              │
│  planner_weekly_aggregates                                                  │
│  ├── user_id (FK)                                                           │
│  ├── region_id (FK)                                                         │
│  ├── week_starting, week_ending                                             │
│  ├── days_worked                                                            │
│  └── daily_breakdown (JSONB)                                                │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                            USERS & AUTH                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  users                                                                      │
│  ├── id, name, email                                                        │
│  ├── ws_user_guid, ws_username                                              │
│  ├── is_ws_linked                                                           │
│  ├── two_factor_secret, two_factor_recovery_codes                           │
│  └── default_region_id (FK)                                                 │
│                                                                              │
│  user_ws_credentials                                                        │
│  ├── user_id (FK, unique)                                                   │
│  ├── encrypted_username                                                     │
│  ├── encrypted_password                                                     │
│  └── is_valid                                                               │
│                                                                              │
│  user_regions (pivot)                                                       │
│  ├── user_id (FK)                                                           │
│  └── region_id (FK)                                                         │
│                                                                              │
│  unlinked_planners                                                          │
│  ├── ws_user_guid, ws_username                                              │
│  ├── circuit_count                                                          │
│  └── linked_to_user_id (FK, when linked)                                    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Core Tables

### circuits

Primary table for vegetation assessment circuits (work orders).

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| job_guid | varchar | No | WorkStudio unique identifier (unique) |
| work_order | varchar(20) | No | Work order number (e.g., "2025-1930") |
| extension | varchar(5) | Yes | Split indicator ("@" for main, "A/B/C" for splits) |
| parent_circuit_id | bigint | Yes | FK to circuits (for split assessments) |
| region_id | bigint | No | FK to regions |
| title | varchar | No | Full circuit/line name |
| contractor | varchar(50) | Yes | Assigned contractor |
| cycle_type | varchar(100) | Yes | Cycle type from API |
| total_miles | decimal(10,2) | No | Total circuit miles (default 0) |
| miles_planned | decimal(10,2) | No | Miles planned (default 0) |
| percent_complete | decimal(5,2) | No | Completion percentage (default 0) |
| total_acres | decimal(10,2) | No | Total acres (default 0) |
| start_date | date | Yes | Start date |
| api_modified_date | date | No | Last modified date from API |
| api_status | varchar(20) | No | API status (ACTIV, QC, REWRK, CLOSE) |
| api_data_json | jsonb | Yes | Raw API response for reference |
| user_modified_fields | jsonb | Yes | Tracks user-modified fields |
| last_user_modified_at | timestamp | Yes | When last user edit occurred |
| last_user_modified_by | bigint | Yes | FK to users |
| last_synced_at | timestamp | Yes | Last sync timestamp |
| last_planned_units_synced_at | timestamp | Yes | Last planned units sync |
| planned_units_sync_enabled | boolean | No | Enable sync (default true) |
| is_excluded | boolean | No | Exclude from reporting (default false) |
| exclusion_reason | varchar | Yes | Reason for exclusion |
| excluded_by | bigint | Yes | FK to users |
| excluded_at | timestamp | Yes | When excluded |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |
| deleted_at | timestamp | Yes | Soft delete timestamp |

**Indexes:**
- `circuits_job_guid_unique` - Unique on job_guid
- `circuits_work_order_index` - Index on work_order
- `circuits_region_id_api_status_index` - Composite index
- `circuits_api_modified_date_index` - Index on api_modified_date
- `circuits_api_status_deleted_at_index` - Composite index
- `circuits_last_user_modified_at_index` - Index for user-priority sync
- `circuits_is_excluded_index` - Index on is_excluded

---

### circuit_aggregates

Daily aggregate snapshots per circuit.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| circuit_id | bigint | No | FK to circuits (CASCADE delete) |
| aggregate_date | date | No | Date of aggregate |
| is_rollup | boolean | No | True for EOD consolidated record |
| total_miles | decimal(10,2) | No | Circuit total miles snapshot |
| miles_planned | decimal(10,2) | No | Miles planned as of date |
| miles_remaining | decimal(10,2) | No | Miles remaining |
| total_units | integer | No | Total planned units (default 0) |
| total_linear_ft | decimal(12,2) | No | Total linear feet (default 0) |
| total_acres | decimal(12,4) | No | Total acres (default 0) |
| total_trees | integer | No | Total trees (default 0) |
| units_approved | integer | No | Approved units (default 0) |
| units_refused | integer | No | Refused units (default 0) |
| units_pending | integer | No | Pending units (default 0) |
| unit_counts_by_type | jsonb | Yes | `{"SPM": 12, "HCB": 5, ...}` |
| linear_ft_by_type | jsonb | Yes | `{"SPM": 1200.5, ...}` |
| acres_by_type | jsonb | Yes | `{"HCB": 1.5, ...}` |
| planner_distribution | jsonb | Yes | `{"user_id": {"units": 50}, ...}` |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

**Indexes:**
- `circuit_aggregates_unique` - Unique on (circuit_id, aggregate_date, is_rollup)
- `circuit_aggregates_aggregate_date_index` - Index on aggregate_date
- `circuit_aggregates_circuit_id_aggregate_date_index` - Composite index

---

### circuit_snapshots

Point-in-time snapshots of circuit state.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| circuit_id | bigint | No | FK to circuits (CASCADE delete) |
| snapshot_type | varchar | No | daily, status_change, or manual |
| snapshot_date | date | No | Date of snapshot |
| miles_planned | decimal(10,2) | Yes | Miles planned at snapshot |
| percent_complete | decimal(5,2) | Yes | Completion percentage |
| api_status | varchar(20) | Yes | API status at snapshot |
| workflow_stage | varchar | Yes | Workflow stage at snapshot |
| total_units | integer | Yes | Total units |
| total_linear_ft | decimal(12,2) | Yes | Total linear feet |
| total_acres | decimal(12,4) | Yes | Total acres |
| total_trees | integer | Yes | Total trees |
| units_approved | integer | Yes | Approved units |
| units_refused | integer | Yes | Refused units |
| units_pending | integer | Yes | Pending units |
| metrics_json | jsonb | Yes | Additional metrics |
| created_by | bigint | Yes | FK to users (SET NULL on delete) |
| notes | text | Yes | Optional notes |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

**Constraints:**
- `circuit_snapshots_snapshot_type_check` - Validates snapshot_type values
- `circuit_snapshots_workflow_stage_check` - Validates workflow_stage values

**Indexes:**
- `circuit_snapshots_unique` - Unique on (circuit_id, snapshot_type, snapshot_date)
- `circuit_snapshots_snapshot_date_snapshot_type_index` - Composite index

---

### circuit_ui_states

UI-specific state for each circuit (workflow stage, position, display options).

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| circuit_id | bigint | No | FK to circuits (unique, CASCADE delete) |
| workflow_stage | varchar | No | active, pending_permissions, qc, rework, closed |
| stage_position | integer | No | Position within stage (default 0) |
| stage_changed_at | timestamp | Yes | When stage last changed |
| stage_changed_by | bigint | Yes | FK to users |
| is_hidden | boolean | No | Hide from display (default false) |
| is_pinned | boolean | No | Pin to top (default false) |
| custom_color | varchar(20) | Yes | Custom highlight color |
| notes | text | Yes | User notes |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

---

### regions

Geographic regions for organizing circuits.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| name | varchar | No | Full name (unique) |
| code | varchar | No | Short code (unique) |
| sort_order | integer | No | Display order (default 0) |
| is_active | boolean | No | Active flag (default true) |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

---

### sync_logs

Audit trail for all sync operations.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| sync_type | varchar | No | circuit_list, aggregates, or full |
| sync_status | varchar | No | started, completed, failed, warning |
| sync_trigger | varchar | No | scheduled, manual, workflow_event |
| region_id | bigint | Yes | FK to regions |
| api_status_filter | varchar | Yes | Status filter used |
| started_at | timestamp | Yes | Start timestamp |
| completed_at | timestamp | Yes | Completion timestamp |
| duration_seconds | integer | Yes | Duration in seconds |
| circuits_processed | integer | Yes | Number processed |
| circuits_created | integer | Yes | Number created |
| circuits_updated | integer | Yes | Number updated |
| aggregates_created | integer | Yes | Aggregates created |
| error_message | text | Yes | Error message if failed |
| error_details | jsonb | Yes | Detailed error info |
| triggered_by | bigint | Yes | FK to users |
| context_json | jsonb | Yes | Additional context |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

---

### users

Application users with WorkStudio integration.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| name | varchar | No | Full name |
| email | varchar | No | Email (unique) |
| email_verified_at | timestamp | Yes | Verification timestamp |
| password | varchar | No | Hashed password |
| remember_token | varchar(100) | Yes | Remember me token |
| two_factor_secret | text | Yes | 2FA secret (encrypted) |
| two_factor_recovery_codes | text | Yes | Recovery codes (encrypted) |
| two_factor_confirmed_at | timestamp | Yes | 2FA confirmed timestamp |
| ws_credentials_fail_count | integer | No | Failed credential attempts |
| ws_credentials_last_used_at | timestamp | Yes | Last credential use |
| ws_credentials_failed_at | timestamp | Yes | Last failure timestamp |
| ws_user_guid | varchar | Yes | WorkStudio user GUID |
| ws_username | varchar | Yes | WorkStudio username |
| is_ws_linked | boolean | No | WorkStudio linked (default false) |
| ws_linked_at | timestamp | Yes | Link timestamp |
| default_region_id | bigint | Yes | FK to regions |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

---

## Lookup/Reference Tables

### unit_types

Unit type definitions for vegetation work.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| code | varchar | No | Unit code (unique, e.g., "SPM") |
| name | varchar | No | Display name |
| category | varchar | No | Category (trees, brush, etc.) |
| measurement_type | varchar | No | linear_ft, acres, count |
| dbh_min | decimal(5,2) | Yes | Minimum DBH |
| dbh_max | decimal(5,2) | Yes | Maximum DBH |
| species | varchar | Yes | Tree species |
| sort_order | integer | No | Display order |
| is_active | boolean | No | Active flag |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

---

### api_status_configs

Configuration for API status values.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| api_status | varchar | No | Status code (unique) |
| display_name | varchar | No | Display name |
| sync_frequency | varchar | No | Sync frequency setting |
| sync_planned_units | boolean | No | Whether to sync units |
| is_active | boolean | No | Active flag |
| sort_order | integer | No | Display order |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

---

### permission_statuses

Landowner permission status definitions.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint | No | Primary key |
| name | varchar | No | Status name |
| code | varchar | No | Status code (unique) |
| color | varchar(20) | Yes | Display color |
| sort_order | integer | No | Display order |
| is_active | boolean | No | Active flag |
| created_at | timestamp | Yes | Created timestamp |
| updated_at | timestamp | Yes | Updated timestamp |

---

## JSONB Field Schemas

### circuits.user_modified_fields

```json
{
  "title": {
    "modified_at": "2026-01-15T10:30:00Z",
    "modified_by": 1,
    "original_value": "OLD TITLE"
  },
  "contractor": {
    "modified_at": "2026-01-14T14:00:00Z",
    "modified_by": 2,
    "original_value": "Old Contractor"
  }
}
```

### circuit_aggregates.unit_counts_by_type

```json
{
  "SPM": 50,
  "MPM": 30,
  "HCB": 25,
  "SBM": 22
}
```

### circuit_aggregates.planner_distribution

```json
{
  "1": {
    "name": "Derek Cinicola",
    "units": 75,
    "linear_ft": 8200.00,
    "acres": 0.85
  },
  "2": {
    "name": "Paul Longenecker",
    "units": 52,
    "linear_ft": 4680.50,
    "acres": 0.57
  }
}
```

### regional_weekly_aggregates.daily_breakdown

```json
{
  "2026-01-13": {
    "circuits_active": 45,
    "miles_planned": 125.5,
    "units_added": 234
  },
  "2026-01-14": {
    "circuits_active": 47,
    "miles_planned": 128.2,
    "units_added": 156
  }
}
```

---

## Common Queries

### Get active circuits with latest aggregate

```sql
SELECT
    c.work_order,
    c.title,
    c.percent_complete,
    c.total_miles,
    c.miles_planned,
    ca.total_units,
    ca.units_approved,
    ca.aggregate_date
FROM circuits c
LEFT JOIN LATERAL (
    SELECT *
    FROM circuit_aggregates
    WHERE circuit_id = c.id
    ORDER BY aggregate_date DESC
    LIMIT 1
) ca ON true
WHERE c.api_status = 'ACTIV'
  AND c.deleted_at IS NULL
  AND c.is_excluded = false
ORDER BY c.work_order;
```

### Get regional summary

```sql
SELECT
    r.name as region_name,
    COUNT(CASE WHEN c.api_status = 'ACTIV' THEN 1 END) as active_circuits,
    COUNT(CASE WHEN c.api_status = 'QC' THEN 1 END) as qc_circuits,
    SUM(c.total_miles) as total_miles,
    SUM(c.miles_planned) as miles_planned,
    AVG(c.percent_complete) as avg_completion
FROM regions r
LEFT JOIN circuits c ON c.region_id = r.id
    AND c.deleted_at IS NULL
    AND c.is_excluded = false
WHERE r.is_active = true
GROUP BY r.id, r.name
ORDER BY r.sort_order;
```

### Get sync history

```sql
SELECT
    sync_type,
    sync_status,
    sync_trigger,
    started_at,
    duration_seconds,
    circuits_processed,
    circuits_created,
    circuits_updated,
    error_message
FROM sync_logs
WHERE started_at >= NOW() - INTERVAL '7 days'
ORDER BY started_at DESC;
```

### Find circuits with user modifications

```sql
SELECT
    c.work_order,
    c.title,
    c.user_modified_fields,
    c.last_user_modified_at,
    u.name as modified_by
FROM circuits c
JOIN users u ON c.last_user_modified_by = u.id
WHERE c.user_modified_fields IS NOT NULL
  AND c.user_modified_fields != '{}'::jsonb
ORDER BY c.last_user_modified_at DESC;
```
