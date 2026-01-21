# WorkStudio API Field Analysis - Active Circuits

**Generated:** 2026-01-21
**Total API Columns:** 48
**Sample Size:** 3 circuits from 176 total active circuits

---

## API Field Inventory

### Currently Mapped & Stored in DB

| API Field | DB Column | Type | Example Value | Notes |
|-----------|-----------|------|---------------|-------|
| `SS_JOBGUID` | `job_guid` | varchar | `{8037BB16-E838-4B74-B5C4-9DBF9ED7202E}` | Unique identifier |
| `SS_WO` | `work_order` | varchar(20) | `2025-2082` | Work order number |
| `SS_EXT` | `extension` | varchar(5) | `@` | Split indicator |
| `SS_TITLE` | `title` | varchar | `QUARRYVILLE 69/12 KV 56-02 LINE` | Circuit name |
| `WSREQ_STATUS` | `api_status` | varchar(20) | `ACTIV` | Status code |
| `SS_JOBTYPE` | `job_type` | varchar | `Assessment Dx` | Job type |
| `VEGJOB_CYCLETYPE` | `cycle_type` | varchar(100) | `Cycle Maintenance - Trim` | Cycle type |
| `REGION` | `region_id` | bigint | (resolved from name) | Region lookup |
| `VEGJOB_LENGTH` | `total_miles` | decimal(10,2) | `57.79` | **Total circuit miles** |
| `VEGJOB_LENGTHCOMP` | `miles_planned` | decimal(10,2) | `33.89` | **Completed/planned miles** |
| `VEGJOB_PRCENT` | `percent_complete` | decimal(5,2) | `62` | Completion percentage |
| `VEGJOB_PROJACRES` | `projected_acres` | decimal | `0.51` | Projected acres |
| `UNITCOUNTS_LENGTHWRK` | `total_linear_ft` | decimal | `4.05` | Work linear feet (miles?) |
| `UNITCOUNTS_NUMTREES` | `total_trees` | integer | `32` | Total tree count |
| `VEGJOB_FORESTER` | `forester_name` | varchar | `Derek Cinicola` | Forester name |
| `SS_ASSIGNEDTO` | `assigned_to` | varchar | (often empty) | Assigned user |
| `SS_TAKENBY` | `taken_by` | varchar | `ASPLUNDH\amiller` | **Planner username** |
| `VEGJOB_CONTRACTOR` | `contractor` | varchar(50) | `ASPLUNDH` | Contractor name |
| `VEGJOB_GF` | `general_foreman` | varchar | (often empty) | General foreman |
| `SS_EDITDATE` | `api_modified_date` | date | `2026-01-20T21:48:22.216000Z` | **CRITICAL: Last sync date** |
| `WPStartDate_Assessment_Xrefs_WP_STARTDATE` | `work_plan_start_date` | date | `2026-01-01T05:00:00.000000Z` | Work plan start |
| `VEGJOB_LINENAME` | `line_name` | varchar | `QUARRYVILLE 69/12 KV 56-02 LINE` | Line name |
| `VEGJOB_CIRCCOMNTS` | `comments` | text | (often empty) | Circuit comments |
| `VEGJOB_COSTMETHOD` | `cost_method` | varchar | `UC` | Cost method |

### Stored in `api_data_json` (JSONB)

These fields are captured but not as dedicated columns:

| API Field | Example Value | Notes |
|-----------|---------------|-------|
| `SS_TAKENBY` | `ASPLUNDH\amiller` | Planner identifier |
| `SS_ASSIGNEDTO` | (empty) | Assigned user |
| `VEGJOB_FORESTER` | `Derek Cinicola` | Forester |
| `VEGJOB_SERVCOMP` | `Distribution` | Service company |
| `VEGJOB_OPCO` | `PPL` | Operating company |
| `SS_READONLY` | `false` | Read-only flag |
| `SS_ITEMTYPELIST` | `UNIT` | Item types |
| `WSREQ_VERSION` | `77` | Version number |
| `WSREQ_SYNCHVERSN` | `68` | **Sync version - useful for staleness** |
| `WSREQ_COORDSYS` | `GPS Latitude/Longitude` | Coordinate system |
| `WSREQ_SKETCHLEFT/TOP/BOTM/RITE` | coordinates | Bounding box |

### NOT Currently Stored (Available in API)

| API Field | Example Value | Potential Use |
|-----------|---------------|---------------|
| `VEGJOB_SERVCOMP` | `Distribution` | Service company categorization |
| `VEGJOB_OPCO` | `PPL` | Operating company |
| `SS_READONLY` | `false` | Read-only indicator |
| `WSREQ_READONLY` | `false` | Another read-only flag |
| `WSREQ_SYNCHSTATE` | `false` | **Sync state - could indicate pending changes** |
| `WSREQ_JOBGUID` | Same as SS_JOBGUID | Duplicate |
| `WSREQ_WO` | Same as SS_WO | Duplicate |
| `WSREQ_EXT` | Same as SS_EXT | Duplicate |
| `WSREQ_JOBTYPE` | Same as SS_JOBTYPE | Duplicate |
| `WSREQ_TAKEN` | `true` | Is circuit taken? |
| `WSREQ_TAKENBY` | Same as SS_TAKENBY | Duplicate |
| `WSREQ_BOUNDSGEOM` | GeoJSON polygon | Geographic boundary |
| `WSREQ_ASSIGNEDTO` | Same as SS_ASSIGNEDTO | Duplicate |
| `WSREQ_VERSION` | `77` | Version number |
| `WSREQ_SYNCHVERSN` | `68` | **Sync version** |
| `WSREQ_ESTMINS` | `0` | Estimated minutes |
| `WSREQ_MINSLEFT` | `0` | Minutes remaining |
| `WSREQ_TRAVELMINS` | `0` | Travel minutes |

---

## Key Fields for Planner Analytics

### Critical for Mileage Tracking

| Field | API Name | DB Status | Priority |
|-------|----------|-----------|----------|
| **Total Miles** | `VEGJOB_LENGTH` | ✅ Stored as `total_miles` | High |
| **Completed Miles** | `VEGJOB_LENGTHCOMP` | ✅ Stored as `miles_planned` | **CRITICAL** |
| **Percent Complete** | `VEGJOB_PRCENT` | ✅ Stored as `percent_complete` | High |
| **Last Modified** | `SS_EDITDATE` | ✅ Stored as `api_modified_date` | **CRITICAL** |
| **Planner Username** | `SS_TAKENBY` | ✅ In api_data_json | **CRITICAL** |

### Recommended for Data Freshness Validation

| Field | Purpose | Current Storage |
|-------|---------|-----------------|
| `SS_EDITDATE` | When assessment was last modified | ✅ `api_modified_date` |
| `WSREQ_VERSION` | Version counter | In api_data_json |
| `WSREQ_SYNCHVERSN` | Sync version counter | In api_data_json |
| `WSREQ_SYNCHSTATE` | Pending sync flag | ❌ Not stored |

---

## Data Quality Observations

### Planner Identification (`SS_TAKENBY`)

- Format: `CONTRACTOR\username` (e.g., `ASPLUNDH\amiller`)
- Consistent across all sampled circuits
- Domain is `ASPLUNDH` for contractor planners
- Can filter by domain to focus on specific contractors

### Last Modified Date (`SS_EDITDATE`)

- Full ISO 8601 timestamp with microseconds
- Example: `2026-01-20T21:48:22.216000Z`
- **This is the key field for data freshness validation**
- Recent dates (within last 24-48 hours) indicate active syncing

### Mileage Fields

- `VEGJOB_LENGTH` = Total circuit miles (e.g., 57.79)
- `VEGJOB_LENGTHCOMP` = Completed/planned miles (e.g., 33.89)
- **Weekly delta = Current `VEGJOB_LENGTHCOMP` - Previous week's `VEGJOB_LENGTHCOMP`**

---

## Recommendations

### Fields to Add as Direct DB Columns

1. **`WSREQ_VERSION`** - Useful for tracking changes
2. **`WSREQ_SYNCHVERSN`** - Can compare version vs sync version for staleness
3. **Consider extracting `taken_by` to dedicated column** - Currently in api_data_json

### Schema Changes Suggested

```sql
-- Add version tracking columns
ALTER TABLE circuits ADD COLUMN ws_version integer DEFAULT 0;
ALTER TABLE circuits ADD COLUMN ws_sync_version integer DEFAULT 0;

-- Ensure api_modified_date is datetime (not just date) for precision
-- Current: date type
-- Recommended: timestamp for full precision
```

### For Weekly Snapshots (Prompt 08)

The `circuit_mile_snapshots` table should capture:
- `circuit_id`
- `snapshot_date` (Friday date)
- `completed_miles` (from `VEGJOB_LENGTHCOMP`)
- `total_miles` (from `VEGJOB_LENGTH`)
- `percent_complete` (from `VEGJOB_PRCENT`)
- `assessment_last_modified` (from `SS_EDITDATE`)
- `planner_identifier` (from `SS_TAKENBY`)
- `is_stale` (calculated: last_modified > 5 days ago)

---

## Domain Filter Note

Per prompt requirements, filter circuits where `SS_TAKENBY` domain is `ASPLUNDH`:
- Username format: `ASPLUNDH\cnewcombe`
- Extract domain: `explode('\\', $takenBy)[0]`
- This filter should be configurable in admin settings
