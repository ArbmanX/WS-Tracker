# WS-Tracker Data Reference

> **Purpose:** Complete reference of all data displayed in the WS-Tracker dashboard with examples.
> **Last Updated:** January 2026

---

## Table of Contents

1. [Circuit Data](#circuit-data)
2. [Circuit Card Display](#circuit-card-display)
3. [Aggregate Data](#aggregate-data)
4. [Region Data](#region-data)
5. [Planner Data](#planner-data)
6. [Permission Status Data](#permission-status-data)
7. [Unit Type Data](#unit-type-data)
8. [Workflow Stage Data](#workflow-stage-data)
9. [Sync Log Data](#sync-log-data)
10. [Chart Data Examples](#chart-data-examples)
11. [Dashboard Statistics](#dashboard-statistics)

---

## Circuit Data

### Source
- **API Endpoint:** WorkStudio GETVIEWDATA (Vegetation Assessments view)
- **Database Table:** `circuits`

### Fields Displayed

| Field | Display Name | Example Value | Location |
|-------|--------------|---------------|----------|
| `work_order` | WO# | `2025-1234` | Card header |
| `extension` | Extension | `A`, `B`, `@` | Card header (appended) |
| `title` | Circuit Title | `PALMYRA-CAMPBELLTOWN 69KV` | Card subtitle |
| `region.name` | Region | `Central` | Badge on card |
| `total_miles` | Total Miles | `12.50` | Stats row |
| `miles_planned` | Miles Planned | `8.75` | Stats row |
| `percent_complete` | Progress | `70%` | Progress bar |
| `total_acres` | Total Acres | `245.30` | Detail view |
| `api_modified_date` | Last Modified | `01/10` | Card footer |
| `api_status` | API Status | `ACTIV` | Badge/indicator |

### Example Circuit Record

```json
{
  "id": 1,
  "job_guid": "A856F956-88DF-4807-90E2-7E12C25B5B32",
  "work_order": "2025-1234",
  "extension": "A",
  "parent_circuit_id": null,
  "region_id": 1,
  "title": "PALMYRA-CAMPBELLTOWN 69KV",
  "contractor": "Asplundh Tree Expert",
  "cycle_type": "Cycle Maintenance - Trim",
  "total_miles": 12.50,
  "miles_planned": 8.75,
  "percent_complete": 70.00,
  "total_acres": 245.30,
  "start_date": "2025-01-02",
  "api_modified_date": "2025-01-10",
  "api_status": "ACTIV",
  "last_synced_at": "2025-01-10T16:30:00Z",
  "created_at": "2025-01-02T08:00:00Z",
  "updated_at": "2025-01-10T16:30:00Z"
}
```

### Display Example

```
┌─────────────────────────────────────────────┐
│ 2025-1234A                        [Central] │
│ PALMYRA-CAMPBELLTOWN 69KV                   │
│                                             │
│ Progress                              70%   │
│ ████████████████████░░░░░░░░░░░░░░░░░░░░░░ │
│                                             │
│ 8.75 / 12.50 mi                      01/10 │
│                                             │
│ ○○ PL  ○ JS                                 │
│ ▼ 2 split assessments                       │
└─────────────────────────────────────────────┘
```

---

## Circuit Card Display

### Compact Card (Kanban Board)

| Element | Data Source | Format |
|---------|-------------|--------|
| Header | `work_order` + `extension` | `2025-1234A` |
| Subtitle | `title` (truncated) | `PALMYRA-CAMPBELLTOWN...` |
| Region Badge | `region.name` | Colored badge |
| Progress Bar | `percent_complete` | 0-100% fill |
| Progress Text | `percent_complete` | `70%` |
| Miles | `miles_planned` / `total_miles` | `8.75 / 12.50 mi` |
| Date | `api_modified_date` | `MM/DD` format |
| Planners | `planners[].name` initials | Avatar circles |
| Split Count | `children.count()` | `▼ 2 split assessments` |

### Expanded Card (Detail View)

Additional fields shown in detail view:

| Field | Display Name | Example |
|-------|--------------|---------|
| `contractor` | Contractor | `Asplundh Tree Expert` |
| `cycle_type` | Cycle Type | `Cycle Maintenance - Trim` |
| `total_acres` | Total Acres | `245.30 ac` |
| `start_date` | Start Date | `Jan 2, 2025` |
| `api_status` | Status | `In Progress` |
| `last_synced_at` | Last Sync | `Jan 10, 2025 4:30 PM` |

---

## Aggregate Data

### Source
- **Computed from:** WorkStudio Planned Units API
- **Database Table:** `circuit_aggregates`

### Fields Displayed

| Field | Display Name | Example Value | Location |
|-------|--------------|---------------|----------|
| `total_units` | Total Units | `156` | Stats panel |
| `total_linear_ft` | Total Linear Feet | `12,450.75` | Stats panel |
| `total_acres` | Total Acres | `45.25` | Stats panel |
| `total_trees` | Total Trees | `23` | Stats panel |
| `units_approved` | Approved | `89` | Permission chart |
| `units_refused` | Refused | `12` | Permission chart |
| `units_pending` | Pending | `55` | Permission chart |
| `unit_counts_by_type` | By Type | See below | Type breakdown |

### Example Circuit Aggregate Record

```json
{
  "id": 1,
  "circuit_id": 1,
  "aggregate_date": "2025-01-10",
  "is_rollup": false,

  "total_units": 156,
  "total_linear_ft": 12450.75,
  "total_acres": 45.25,
  "total_trees": 23,

  "units_approved": 89,
  "units_refused": 12,
  "units_pending": 55,

  "unit_counts_by_type": {
    "SPM": 45,
    "SPB": 32,
    "HCB": 28,
    "BRUSH": 15,
    "REM612": 8,
    "REM1218": 10,
    "REM1824": 5,
    "ASH612": 6,
    "ASH1218": 4,
    "NW": 3
  },

  "linear_ft_by_type": {
    "SPM": 5420.50,
    "SPB": 3890.25,
    "MPM": 2140.00,
    "STM": 1000.00
  },

  "acres_by_type": {
    "HCB": 25.50,
    "BRUSH": 12.75,
    "MOW": 7.00
  },

  "planner_distribution": {
    "Paul Longenecker": 85,
    "John Smith": 45,
    "Jane Doe": 26
  },

  "created_at": "2025-01-10T16:30:00Z",
  "updated_at": "2025-01-10T16:30:00Z"
}
```

### Display Example (Stats Panel)

```
┌─────────────────────────────────────────────────────────┐
│  Circuit Aggregates                      Jan 10, 2025   │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  156 Units    12,450.75 ft    45.25 ac    23 trees     │
│                                                         │
│  Permission Status                                      │
│  ┌─────────────────────────────────────────────────┐   │
│  │ ████████████████░░░░░░░░░░████                  │   │
│  │ Approved: 89 (57%)  Pending: 55 (35%)  Refused: 12 │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  By Unit Type                                           │
│  SPM:      45  │████████████████████│                  │
│  SPB:      32  │██████████████│                        │
│  HCB:      28  │████████████│                          │
│  BRUSH:    15  │██████│                                │
│  Removals: 33  │██████████████│                        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## Region Data

### Source
- **Database Table:** `regions`
- **Seeded Values:** PPL Electric service regions

### Fields

| Field | Display Name | Example |
|-------|--------------|---------|
| `name` | Region Name | `Central` |
| `code` | Code | `CENTRAL` |

### All Regions

| ID | Name | Code | Color Class |
|----|------|------|-------------|
| 1 | Central | CENTRAL | `badge-primary` |
| 2 | Lancaster | LANCASTER | `badge-secondary` |
| 3 | Lehigh | LEHIGH | `badge-accent` |
| 4 | Harrisburg | HARRISBURG | `badge-neutral` |

### Display Example (Filter Dropdown)

```
┌─────────────────────────────────┐
│ Region Filter           ▼      │
├─────────────────────────────────┤
│ ○ All Regions                   │
│ ● Central           (45)       │
│ ○ Lancaster         (32)       │
│ ○ Lehigh            (28)       │
│ ○ Harrisburg        (19)       │
└─────────────────────────────────┘
```

---

## Planner Data

### Source
- **Database Table:** `users` (with role `planner`)
- **Pivot Table:** `circuit_user`

### Fields Displayed

| Field | Display Name | Example |
|-------|--------------|---------|
| `name` | Full Name | `Paul Longenecker` |
| `api_username` | WS Username | `ASPLUNDH\plongenecker` |
| `email` | Email | `plongenecker@asplundh.com` |
| Initials | Avatar | `PL` |

### Example Planner Record

```json
{
  "id": 5,
  "name": "Paul Longenecker",
  "email": "plongenecker@asplundh.com",
  "api_username": "ASPLUNDH\\plongenecker",
  "role": "planner",
  "theme_preference": "ppl-brand",
  "ws_credentials_verified_at": "2025-01-05T10:00:00Z",
  "created_at": "2025-01-01T08:00:00Z"
}
```

### Display Example (Planner Filter)

```
┌─────────────────────────────────┐
│ Planner Filter          ▼      │
├─────────────────────────────────┤
│ ○ All Planners                  │
│ ● Paul Longenecker     (28)    │
│ ○ John Smith           (22)    │
│ ○ Jane Doe             (15)    │
│ ○ Mike Johnson         (12)    │
└─────────────────────────────────┘
```

### Display Example (Planner Avatars on Card)

```
┌────────────┐
│ ○○ PL JS   │  ← Two planners: Paul Longenecker, John Smith
└────────────┘
```

---

## Permission Status Data

### Source
- **Database Table:** `permission_statuses`
- **API Field:** `VEGUNIT_PERMSTAT`

### All Permission Statuses

| ID | Name | Code | Color | Display |
|----|------|------|-------|---------|
| 1 | Pending | `` (empty) | `warning` | Yellow |
| 2 | Approved | `Approved` | `success` | Green |
| 3 | Refused | `Refused` | `error` | Red |

### Display Example (Donut Chart)

```
        ┌─────────────┐
       /               \
      │    ┌─────┐      │
      │    │ 156 │      │     Legend:
      │    │Total│      │     ● Approved: 89 (57%)
      │    └─────┘      │     ● Pending:  55 (35%)
       \               /      ● Refused:  12 (8%)
        └─────────────┘
```

---

## Unit Type Data

### Source
- **Database Table:** `unit_types`
- **Reference:** `docs/UnitTypes.json`

### Categories

| Category | Code | Description | Measurement |
|----------|------|-------------|-------------|
| Line Trimming | VLG | Vegetation trimming along lines | Linear feet |
| Brush/Herbicide | VAR | Area-based brush clearing | Acres |
| Tree Removal | VCT | Individual tree removal | Tree count (DBH) |
| No Work | VNW | Stations requiring no work | Flag only |
| Sensitive | VSA | Customer-sensitive areas | Flag only |

### Example Unit Types (Partial List)

| Code | Name | Category | Measurement | DBH Range |
|------|------|----------|-------------|-----------|
| SPM | Single Phase - Manual | VLG | linear_ft | - |
| SPB | Single Phase - Bucket | VLG | linear_ft | - |
| SPE | Single Phase - Elevated | VLG | linear_ft | - |
| MPM | Multi Phase - Manual | VLG | linear_ft | - |
| HCB | Hand Cut Brush | VAR | acres | - |
| BRUSH | Brush | VAR | acres | - |
| MOW | Mow | VAR | acres | - |
| REM612 | Removal 6-12" | VCT | tree_count | 6-12" |
| REM1218 | Removal 12-18" | VCT | tree_count | 12-18" |
| REM1824 | Removal 18-24" | VCT | tree_count | 18-24" |
| REM2430 | Removal 24-30" | VCT | tree_count | 24-30" |
| REM36 | Removal 30-36" | VCT | tree_count | 30-36" |
| REM>30 | Removal >30" | VCT | tree_count | >30" |
| ASH612 | Ash 6-12" | VCT | tree_count | 6-12" |
| NW | No Work | VNW | none | - |
| SENSI | Sensitive | VSA | none | - |

### Display Example (Unit Type Breakdown)

```
┌──────────────────────────────────────────────────────┐
│ Unit Type Breakdown                                  │
├──────────────────────────────────────────────────────┤
│                                                      │
│ LINE TRIMMING (VLG)                    77 units     │
│   SPM - Single Phase Manual       45   █████████    │
│   SPB - Single Phase Bucket       32   ██████       │
│                                                      │
│ BRUSH/HERBICIDE (VAR)                  43 units     │
│   HCB - Hand Cut Brush            28   ██████       │
│   BRUSH - Brush                   15   ███          │
│                                                      │
│ TREE REMOVAL (VCT)                     33 units     │
│   REM612 - Removal 6-12"           8   ██           │
│   REM1218 - Removal 12-18"        10   ██           │
│   REM1824 - Removal 18-24"         5   █            │
│   ASH612 - Ash 6-12"               6   █            │
│   ASH1218 - Ash 12-18"             4   █            │
│                                                      │
│ NO WORK (VNW)                           3 units     │
│   NW - No Work                     3   █            │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

## Workflow Stage Data

### Source
- **Database Table:** `circuit_ui_states`
- **Enum:** `App\Enums\WorkflowStage`

### All Workflow Stages

| Value | Display Name | Description | Column Color |
|-------|--------------|-------------|--------------|
| `active` | Active | Currently being planned | `primary` |
| `pending_permissions` | Pending Permissions | 100% planned, awaiting approvals | `warning` |
| `qc` | QC | In quality control review | `info` |
| `rework` | Rework | Failed QC, needs corrections | `error` |
| `closed` | Closed | Completed and archived | `neutral` |

### Display Example (Kanban Board)

```
┌────────────┬──────────────────┬──────────┬──────────┬──────────┐
│   Active   │ Pending Perms    │    QC    │  Rework  │  Closed  │
│    (45)    │      (12)        │   (8)    │   (3)    │   (56)   │
├────────────┼──────────────────┼──────────┼──────────┼──────────┤
│ ┌────────┐ │ ┌──────────────┐ │ ┌──────┐ │ ┌──────┐ │ ┌──────┐ │
│ │2025-123│ │ │ 2025-456     │ │ │2025- │ │ │2025- │ │ │2025- │ │
│ │PALMYRA │ │ │ LANCASTER... │ │ │789   │ │ │012   │ │ │345   │ │
│ │  70%   │ │ │    100%      │ │ │ 95%  │ │ │ 88%  │ │ │100%  │ │
│ └────────┘ │ └──────────────┘ │ └──────┘ │ └──────┘ │ └──────┘ │
│ ┌────────┐ │ ┌──────────────┐ │          │          │          │
│ │2025-124│ │ │ 2025-457     │ │          │          │          │
│ │...     │ │ │ ...          │ │          │          │          │
│ └────────┘ │ └──────────────┘ │          │          │          │
└────────────┴──────────────────┴──────────┴──────────┴──────────┘
```

---

## Sync Log Data

### Source
- **Database Table:** `sync_logs`

### Fields Displayed

| Field | Display Name | Example |
|-------|--------------|---------|
| `sync_type` | Type | `Circuit List` |
| `status` | Status | `Completed` |
| `triggered_by` | Trigger | `Scheduled` |
| `triggered_by_user.name` | Triggered By | `System` or `John Admin` |
| `circuits_processed` | Circuits | `124` |
| `units_processed` | Units | `1,456` |
| `started_at` | Started | `Jan 10, 4:30 AM` |
| `completed_at` | Completed | `Jan 10, 4:32 AM` |
| `error_message` | Error | `API timeout` (if failed) |

### Example Sync Log Record

```json
{
  "id": 42,
  "sync_type": "circuit_list",
  "status": "completed",
  "triggered_by": "scheduled",
  "triggered_by_user_id": null,
  "credential_user_id": 5,
  "circuits_processed": 124,
  "units_processed": 0,
  "error_message": null,
  "warning_message": null,
  "started_at": "2025-01-10T04:30:00Z",
  "completed_at": "2025-01-10T04:32:15Z"
}
```

### Display Example (Sync History Table)

```
┌────────────────────────────────────────────────────────────────────────┐
│ Sync History                                                           │
├────────┬───────────┬───────────┬──────────┬──────────┬────────────────┤
│ Type   │ Status    │ Trigger   │ Circuits │ Duration │ Time           │
├────────┼───────────┼───────────┼──────────┼──────────┼────────────────┤
│ Circuit│ ✓ Complete│ Scheduled │ 124      │ 2m 15s   │ Jan 10, 4:30 AM│
│ Circuit│ ✓ Complete│ Manual    │ 45       │ 0m 58s   │ Jan 9, 2:15 PM │
│ Circuit│ ✗ Failed  │ Scheduled │ 0        │ 1m 00s   │ Jan 9, 4:30 AM │
│        │           │           │          │          │ API unavailable│
│ Circuit│ ✓ Complete│ Scheduled │ 122      │ 2m 08s   │ Jan 8, 4:30 PM │
└────────┴───────────┴───────────┴──────────┴──────────┴────────────────┘
```

---

## Chart Data Examples

### Miles by Region (Bar Chart)

```json
{
  "categories": ["Central", "Lancaster", "Lehigh", "Harrisburg"],
  "series": [
    {
      "name": "Total Miles",
      "data": [450.25, 380.50, 295.75, 220.00]
    },
    {
      "name": "Miles Planned",
      "data": [315.50, 298.25, 210.00, 175.50]
    }
  ]
}
```

**Display:**
```
Miles by Region
                              Total    Planned
Central      ████████████████████   450.25  315.50
Lancaster    ████████████████       380.50  298.25
Lehigh       ████████████           295.75  210.00
Harrisburg   ██████████             220.00  175.50
```

### Permission Status (Donut Chart)

```json
{
  "labels": ["Approved", "Pending", "Refused"],
  "series": [2450, 1820, 340],
  "total": 4610
}
```

**Display:**
```
Permission Status
        ┌─────────────┐
       /    ████      \      ● Approved: 2,450 (53%)
      │   ████████     │     ● Pending:  1,820 (40%)
      │  ████4,610████ │     ● Refused:    340 (7%)
      │   ████████     │
       \    ████      /
        └─────────────┘
```

### Planner Progress (Line/Area Chart)

```json
{
  "categories": ["Jan 1", "Jan 2", "Jan 3", "Jan 4", "Jan 5", "Jan 6", "Jan 7"],
  "series": [
    {
      "name": "Units Assessed",
      "data": [45, 52, 38, 67, 55, 0, 0]
    },
    {
      "name": "Linear Feet",
      "data": [1250, 1480, 980, 1890, 1520, 0, 0]
    }
  ]
}
```

**Display:**
```
Planner Progress (Last 7 Days)

 70 │                    ●
    │                   ╱ ╲
 50 │    ●─────●       ╱   ●
    │   ╱       ╲     ╱
 30 │  ╱         ●───╱
    │ ●
 10 │
    └───────────────────────
      M   T   W   T   F   S   S

    ─── Units    ─── Linear Ft (÷100)
```

---

## Dashboard Statistics

### Stats Panel Cards

| Stat | Calculation | Example Display |
|------|-------------|-----------------|
| Total Circuits | `Circuit::count()` | `124 Circuits` |
| Total Miles | `Circuit::sum('total_miles')` | `1,346.50 mi` |
| Miles Planned | `Circuit::sum('miles_planned')` | `999.25 mi` |
| Overall Progress | `miles_planned / total_miles * 100` | `74.2%` |
| Total Units | `CircuitAggregate::sum('total_units')` | `4,610 Units` |
| Approved Units | `CircuitAggregate::sum('units_approved')` | `2,450 (53%)` |

### Display Example (Stats Panel)

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Dashboard Overview                           │
├──────────────────┬──────────────────┬──────────────────────────────┤
│   124 Circuits   │  1,346.50 mi     │      Overall Progress        │
│   Active: 45     │  Planned: 999.25 │  ██████████████████░░░ 74.2% │
│   QC: 8          │  Remaining: 347  │                              │
│   Closed: 56     │                  │                              │
├──────────────────┴──────────────────┴──────────────────────────────┤
│                                                                     │
│  4,610 Total Units                                                  │
│  ├─ 2,450 Approved (53%)  ██████████████████████████░░░░░░░░░░░░░ │
│  ├─ 1,820 Pending (40%)   ████████████████████░░░░░░░░░░░░░░░░░░░ │
│  └─   340 Refused (7%)    ███░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Filter Panel Data

### Available Filters

| Filter | Type | Options |
|--------|------|---------|
| Region | Select | All, Central, Lancaster, Lehigh, Harrisburg |
| Planner | Select | All, (list of planners with assigned circuits) |
| Date From | Date | Date picker |
| Date To | Date | Date picker |
| Status | Multi-select | Active, Pending, QC, Rework, Closed |

### Display Example

```
┌─────────────────────────────────────────────────────────────────┐
│ Filters                                              [Clear All]│
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ Region          Planner           Date Range                    │
│ ┌───────────┐   ┌───────────┐    ┌──────────┐ ┌──────────┐    │
│ │ Central ▼ │   │ All     ▼ │    │ 01/01/25 │ │ 01/31/25 │    │
│ └───────────┘   └───────────┘    └──────────┘ └──────────┘    │
│                                                                 │
│ Status: [✓ Active] [✓ Pending] [✓ QC] [  Rework] [  Closed]   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

Showing 45 of 124 circuits
```

---

## Unlinked Planners Data

### Source
- **Database Table:** `unlinked_planners`

### Fields Displayed

| Field | Display Name | Example |
|-------|--------------|---------|
| `api_username` | WS Username | `ASPLUNDH\newplanner` |
| `first_seen_at` | First Seen | `Jan 5, 2025` |
| `last_seen_at` | Last Seen | `Jan 10, 2025` |
| `occurrence_count` | Occurrences | `28` |
| `is_resolved` | Status | `Unresolved` |

### Example Record

```json
{
  "id": 3,
  "api_username": "ASPLUNDH\\newplanner",
  "linked_user_id": null,
  "first_seen_at": "2025-01-05T08:00:00Z",
  "last_seen_at": "2025-01-10T16:30:00Z",
  "occurrence_count": 28,
  "is_resolved": false,
  "resolved_by_user_id": null,
  "resolved_at": null
}
```

### Display Example

```
┌────────────────────────────────────────────────────────────────────┐
│ Unlinked Planners                                    3 unresolved  │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│ ASPLUNDH\newplanner                                                │
│ First seen: Jan 5 • Last seen: Jan 10 • 28 occurrences            │
│ [Link to User ▼] [Ignore]                                         │
│                                                                    │
│ ASPLUNDH\contractor1                                               │
│ First seen: Jan 8 • Last seen: Jan 10 • 12 occurrences            │
│ [Link to User ▼] [Ignore]                                         │
│                                                                    │
│ ASPLUNDH\tempuser                                                  │
│ First seen: Jan 10 • Last seen: Jan 10 • 3 occurrences            │
│ [Link to User ▼] [Ignore]                                         │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

---

## API Response Examples

### Circuit List Response (DDOTable Format)

```json
{
  "Protocol": "DATASET",
  "DataSet": {
    "Heading": [
      "SS_JOBGUID",
      "SS_WO",
      "SS_EXT",
      "REGION",
      "SS_TITLE",
      "VEGJOB_LENGTH",
      "VEGJOB_LENGTHCOMP",
      "VEGJOB_PRCENT",
      "VEGJOB_PROJACRES",
      "SS_EDITDATE",
      "WSREQ_STATUS",
      "SS_TAKENBY",
      "VEGJOB_CONTRACTOR",
      "VEGJOB_CYCLETYPE"
    ],
    "Data": [
      [
        "A856F956-88DF-4807-90E2-7E12C25B5B32",
        "2025-1234",
        "A",
        "Central",
        "PALMYRA-CAMPBELLTOWN 69KV",
        12.50,
        8.75,
        70.00,
        245.30,
        "/Date(2025-01-10)/",
        "ACTIV",
        "ASPLUNDH\\plongenecker",
        "Asplundh Tree Expert",
        "Cycle Maintenance - Trim"
      ]
    ]
  }
}
```

### Planned Units Response (DDOTable Format)

```json
{
  "Protocol": "DATASET",
  "DataSet": {
    "Heading": [
      "SSUNITS_OBJECTID",
      "SS_WO",
      "VEGUNIT_UNIT",
      "VEGUNIT_PERMSTAT",
      "VEGUNIT_FORESTER",
      "JOBVEGETATIONUNITS_LENGTHWRK",
      "JOBVEGETATIONUNITS_ACRES",
      "JOBVEGETATIONUNITS_NUMTREES",
      "VEGJOB_REGION"
    ],
    "Data": [
      [
        "UNIT-001",
        "2025-1234",
        "SPM",
        "Approved",
        "Paul Longenecker",
        125.50,
        0,
        0,
        "Central"
      ],
      [
        "UNIT-002",
        "2025-1234",
        "HCB",
        "",
        "Paul Longenecker",
        0,
        2.75,
        0,
        "Central"
      ],
      [
        "UNIT-003",
        "2025-1234",
        "REM1218",
        "Refused",
        "John Smith",
        0,
        0,
        1,
        "Central"
      ]
    ]
  }
}
```

---

## Summary

This document provides a complete reference for all data displayed in the WS-Tracker dashboard. Each section includes:

- **Source:** Where the data comes from
- **Fields:** What is displayed
- **Examples:** Real-world data examples
- **Display:** How it appears in the UI

Use this as a reference when:
- Building UI components
- Writing tests with realistic data
- Creating factories and seeders
- Debugging data display issues
