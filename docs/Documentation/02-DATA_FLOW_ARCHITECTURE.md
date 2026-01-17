# Data Flow & Architecture

## Overview

WS-Tracker follows a data pipeline architecture where data flows from the external WorkStudio API into local storage, gets transformed and aggregated, and is presented to users through a Livewire dashboard.

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            WORKSTUDIO API                                    │
│                    (GeoDigital DDOProtocol API)                             │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 │ HTTP/HTTPS (Basic Auth)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        API SERVICE LAYER                                     │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  WorkStudioApiService                                                │    │
│  │  - healthCheck()                                                     │    │
│  │  - getCircuitsByStatus(status)                                       │    │
│  │  - getPlannedUnits(workOrder)                                        │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  ApiCredentialManager                                                │    │
│  │  - Manages system and user credentials                               │    │
│  │  - Validates and tracks credential usage                             │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 │ DDOTable Response
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        TRANSFORMER LAYER                                     │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  DDOTableTransformer                                                 │    │
│  │  - Converts DDOTable format to key-value collections                 │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  CircuitTransformer                                                  │    │
│  │  - Maps API fields to Circuit model fields                           │    │
│  │  - Handles region lookup and date parsing                            │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  PlannedUnitAggregateTransformer                                     │    │
│  │  - Aggregates individual units into circuit totals                   │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 │ Transformed Data
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          SYNC LAYER                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  CircuitSyncService                                                  │    │
│  │  - Creates/updates circuits with user-priority logic                 │    │
│  │  - Preserves user-modified fields during normal sync                 │    │
│  │  - Tracks planner assignments                                        │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  AggregateCalculationService                                         │    │
│  │  - Calculates aggregate metrics from planned units                   │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  AggregateStorageService                                             │    │
│  │  - Stores circuit aggregates with change detection                   │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  PlannedUnitsSnapshotService                                         │    │
│  │  - Creates milestone snapshots when thresholds are reached           │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        DATABASE LAYER                                        │
│  PostgreSQL with JSONB support                                              │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────────────────┐    │
│  │   circuits     │  │ circuit_       │  │ circuit_snapshots          │    │
│  │                │  │ aggregates     │  │                            │    │
│  └───────┬────────┘  └───────┬────────┘  └────────────────────────────┘    │
│          │                   │                                              │
│  ┌───────┴────────┐  ┌───────┴────────────┐  ┌────────────────────────┐    │
│  │ circuit_user   │  │ planner_daily_     │  │ planned_units_         │    │
│  │ (pivot)        │  │ aggregates         │  │ snapshots              │    │
│  └────────────────┘  └────────────────────┘  └────────────────────────┘    │
│  ┌────────────────┐  ┌────────────────────┐  ┌────────────────────────┐    │
│  │ sync_logs      │  │ regional_daily_    │  │ regional_weekly_       │    │
│  │                │  │ aggregates         │  │ aggregates             │    │
│  └────────────────┘  └────────────────────┘  └────────────────────────┘    │
└────────────────────────────────┬────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                      PRESENTATION LAYER                                      │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  Livewire Components                                                 │    │
│  │  - CircuitDashboard (Kanban view)                                    │    │
│  │  - Settings components                                               │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  Blade Views with Flux UI Components                                 │    │
│  │  - DaisyUI 5 for styling                                             │    │
│  │  - Tailwind CSS 4                                                    │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Sync Architecture

### Job-Based Processing

The application uses Laravel Jobs for all sync operations:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           QUEUE JOBS                                         │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  SyncCircuitsJob                                                            │
│  ├── Fetches circuits from WorkStudio API by status                        │
│  ├── Uses CircuitSyncService for create/update logic                        │
│  ├── Respects user-modified fields (user-priority sync)                     │
│  └── Logs results to sync_logs table                                        │
│                                                                              │
│  SyncCircuitAggregatesJob                                                   │
│  ├── Fetches planned units for each circuit                                 │
│  ├── Calculates aggregates via AggregateCalculationService                 │
│  ├── Stores if changes detected via AggregateDiffService                    │
│  ├── Creates milestone snapshots when progress thresholds hit               │
│  └── Rate-limited to avoid API overload                                     │
│                                                                              │
│  CreateDailySnapshotsJob                                                    │
│  ├── Creates daily snapshots for all active circuits                        │
│  ├── Skips circuits that already have today's snapshot                      │
│  └── Captures current state for historical tracking                         │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### User-Priority Sync Pattern

The application implements a "user-priority" sync pattern that preserves user modifications:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     USER-PRIORITY SYNC FLOW                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. Circuit Fields are Categorized:                                         │
│     ├── API_ONLY_FIELDS: Always synced from API                             │
│     │   (job_guid, work_order, api_status, api_modified_date)               │
│     │                                                                        │
│     └── SYNCABLE_FIELDS: User-modifiable                                    │
│         (title, contractor, total_miles, miles_planned, etc.)               │
│                                                                              │
│  2. During Sync:                                                            │
│     ├── If field NOT user-modified → Update from API                        │
│     ├── If field IS user-modified → Preserve user value (skip)              │
│     └── If --force flag → Overwrite everything                              │
│                                                                              │
│  3. User Modification Tracking:                                             │
│     ├── user_modified_fields (JSONB): Tracks which fields were modified    │
│     ├── last_user_modified_at: When last user edit occurred                 │
│     └── last_user_modified_by: Who made the edit                            │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Data Transformation Pipeline

### DDOTable → Circuit Model

```
WorkStudio API Response (DDOTable format):
{
  "Protocol": "DATASET",
  "DataSet": {
    "Heading": ["COLUMN1", "COLUMN2", ...],
    "Data": [
      ["value1", "value2", ...],
      ...
    ]
  }
}
        │
        ▼ DDOTableTransformer

Collection of associative arrays:
[
  {"COLUMN1": "value1", "COLUMN2": "value2", ...},
  ...
]
        │
        ▼ CircuitTransformer

Collection of circuit data:
[
  {
    "job_guid": "...",
    "work_order": "2025-1930",
    "title": "HATFIELD 69/12 KV LINE",
    "region_id": 1,
    "total_miles": 14.93,
    ...
  },
  ...
]
        │
        ▼ CircuitSyncService

Eloquent Circuit models (created/updated in database)
```

### Planned Units → Circuit Aggregates

```
Raw Planned Units Collection:
[
  {"VEGUNIT_UNIT": "SPM", "JOBVEGETATIONUNITS_LENGTHWRK": 150.5, ...},
  {"VEGUNIT_UNIT": "HCB", "JOBVEGETATIONUNITS_ACRES": 0.5, ...},
  ...
]
        │
        ▼ AggregateCalculationService

Aggregate Data:
{
  "circuit_id": 1,
  "aggregate_date": "2026-01-16",
  "total_units": 127,
  "total_linear_ft": 12880.50,
  "total_acres": 1.42,
  "total_trees": 45,
  "units_approved": 89,
  "units_pending": 32,
  "units_refused": 6,
  "unit_counts_by_type": {"SPM": 50, "HCB": 25, ...},
  "linear_ft_by_type": {"SPM": 8000.00, ...},
  "planner_distribution": {"John": {"units": 30, ...}, ...}
}
        │
        ▼ AggregateDiffService (change detection)
        │
        ▼ AggregateStorageService

CircuitAggregate model stored in database
```

## Scheduled Tasks

All scheduled tasks are defined in `routes/console.php`:

| Task | Schedule | Timezone | Description |
|------|----------|----------|-------------|
| Circuit Sync (ACTIV) | 4:30 AM & 4:30 PM | ET | Sync active circuits twice daily |
| Circuit Sync (QC/REWRK/CLOSE) | Monday 4:30 AM | ET | Weekly sync of non-active statuses |
| Aggregate Sync | 5:30 AM & 5:30 PM | ET | Calculate aggregates after circuit sync |
| Daily Snapshots | 5:00 AM | ET | Create daily circuit snapshots |

## Aggregate Hierarchy

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        AGGREGATE HIERARCHY                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  regional_weekly_aggregates                                                 │
│  └── Sum of regional_daily_aggregates for the week                          │
│      └── region_id, week_ending, total metrics                              │
│                                                                              │
│  regional_daily_aggregates                                                  │
│  └── Sum of circuit_aggregates for all circuits in region                   │
│      └── region_id, aggregate_date, total metrics                           │
│                                                                              │
│  planner_weekly_aggregates                                                  │
│  └── Sum of planner_daily_aggregates for the week                           │
│      └── user_id, region_id, week_ending                                    │
│                                                                              │
│  planner_daily_aggregates                                                   │
│  └── Sum of work done by planner on that day                                │
│      └── user_id, region_id, aggregate_date                                 │
│                                                                              │
│  circuit_aggregates                                                         │
│  └── Daily aggregate for single circuit                                     │
│      └── circuit_id, aggregate_date, metrics                                │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Error Handling

### Sync Error Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          ERROR HANDLING                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. API Connection Errors                                                   │
│     ├── Retry with exponential backoff (1s, 2s, 4s... max 30s)              │
│     ├── Max 5 retries per request                                           │
│     └── Log warning on each retry                                           │
│                                                                              │
│  2. Authentication Errors (401)                                             │
│     ├── No retry (immediate failure)                                        │
│     ├── Mark credentials as failed                                          │
│     └── Log error with user context                                         │
│                                                                              │
│  3. Circuit-Level Errors                                                    │
│     ├── Continue processing remaining circuits                              │
│     ├── Collect error details in results array                              │
│     └── Complete sync with "warning" status if partial failure              │
│                                                                              │
│  4. Job Failure (after retries exhausted)                                   │
│     ├── Call failed() method on job                                         │
│     ├── Log critical error with full context                                │
│     └── SyncLog marked as failed                                            │
│                                                                              │
│  5. Sync Logging                                                            │
│     ├── sync_logs table tracks all sync operations                          │
│     ├── Status: started → completed|failed|warning                          │
│     ├── Duration, counts, and error details stored                          │
│     └── Used for monitoring and debugging                                   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Security Considerations

1. **API Credentials**
   - Stored encrypted in `user_ws_credentials` table
   - System credentials in environment variables
   - Never logged or exposed in responses

2. **Data Validation**
   - All API data validated during transformation
   - Type coercion for numeric fields
   - Date parsing with error handling

3. **Access Control**
   - Role-based permissions via Spatie Laravel-Permission
   - Circuit-level exclusion tracking with audit trail
   - Two-factor authentication support
