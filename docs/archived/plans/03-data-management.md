# Phase 3: Data Management Dashboard (Sudo Admin)

**Status:** Draft
**Branch:** `feature/data-management`
**Depends On:** Phase 1 (App Shell Layout), Phase 2.5 (User Onboarding)
**Estimated Files:** 18 new, 4 modified, 0 removed
**Access:** Sudo Admin only (permission: `manage-data`)

---

## Overview

Administrative dashboard for sudo admins to control, inspect, and manage the data that flows through WS-Tracker. This is the "source of truth" control panel before data is displayed on user-facing dashboards.

### Goals
1. View and search raw circuit data from API syncs
2. Exclude/include circuits from dashboard calculations
3. Override/edit circuit data with audit trail
4. Manage API endpoint configurations
5. View and filter sync logs
6. Manage data sources and sync schedules
7. Build with permissions infrastructure for future role expansion

### Non-Goals (Future Phases)
- Bulk import/export
- API webhook management
- Automated data validation rules
- Role-based data access (beyond sudo_admin)

---

## Permission Structure

### Current Phase (Sudo Admin Only)

```php
// All data management features gated by role
if (!auth()->user()->hasRole('sudo_admin')) {
    abort(403);
}
```

### Future Permission Expansion

Designed for future granular permissions:

| Permission | Description | Future Roles |
|------------|-------------|--------------|
| `data.view` | View raw data, sync logs | Admin, Supervisor |
| `data.exclude` | Exclude/include circuits | Admin |
| `data.edit` | Override circuit values | Sudo Admin |
| `data.endpoints` | Manage API endpoints | Sudo Admin |
| `data.sync` | Trigger manual syncs | Admin |
| `data.delete` | Permanently delete records | Sudo Admin |

```php
// Future implementation pattern
@can('data.edit')
    <button>Edit Circuit</button>
@endcan
```

---

## Page Structure

```
/admin/data-management
├── /circuits          # Circuit data browser
├── /circuits/{id}     # Single circuit detail/edit
├── /sync-logs         # Sync history and logs
├── /endpoints         # API endpoint configuration
└── /exclusions        # Excluded circuits list
```

---

## Component Specifications

### 1. Data Management Layout

Tabbed interface within the app shell.

```
┌─────────────────────────────────────────────────────────────────┐
│ Data Management                              [Sudo Admin Only]  │
├─────────────────────────────────────────────────────────────────┤
│ [Circuits] [Sync Logs] [Endpoints] [Exclusions]                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                     [Tab Content Area]                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**DaisyUI Components:**
- `tabs`, `tab`, `tab-content`
- `badge badge-warning` for sudo indicator

---

### 2. Circuit Data Browser (`/admin/data-management/circuits`)

Searchable, filterable table of all circuits with raw data access.

**Features:**
- Search by work order, title, job GUID
- Filter by region, status, excluded state
- Sort by any column
- View raw API JSON
- Quick actions (exclude, edit, view history)

**Layout:**
```
┌─────────────────────────────────────────────────────────────────┐
│ Search: [_______________] Region: [All ▼] Status: [All ▼]      │
│ □ Show excluded  □ Show deleted  [Reset Filters]               │
├─────────────────────────────────────────────────────────────────┤
│ ☑ │ Work Order │ Title        │ Region  │ Status │ Actions     │
├───┼────────────┼──────────────┼─────────┼────────┼─────────────┤
│ □ │ 12345-001  │ Main St Cir  │ Lehigh  │ Active │ [⋮]         │
│ □ │ 12345-002  │ Oak Ave Cir  │ Lehigh  │ QC     │ [⋮]         │
│ □ │ 67890-001  │ Pine Rd Cir  │ Central │ Closed │ [⋮]         │
├───┴────────────┴──────────────┴─────────┴────────┴─────────────┤
│ Showing 1-25 of 239 circuits          [< 1 2 3 ... 10 >]       │
├─────────────────────────────────────────────────────────────────┤
│ Selected: 0  [Bulk Exclude] [Bulk Include] [Export Selected]   │
└─────────────────────────────────────────────────────────────────┘
```

**Row Actions Menu:**
- View Details
- View Raw JSON
- Edit Data
- Exclude/Include
- View History
- View Planned Units

---

### 3. Circuit Detail/Edit Modal (`CircuitDetailModal`)

Slide-out panel or modal for viewing and editing circuit data.

**Tabs:**
1. **Overview** - Key fields with edit capability
2. **Raw Data** - JSON viewer with syntax highlighting
3. **History** - Audit log of changes
4. **Planned Units** - Link to planned units data

**Edit Mode:**
```
┌─────────────────────────────────────────────────────────────────┐
│ Circuit: 12345-001 - Main St Circuit            [✕]            │
├─────────────────────────────────────────────────────────────────┤
│ [Overview] [Raw Data] [History] [Planned Units]                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ Work Order:    12345-001      (from API - read only)           │
│ Title:         [Main St Circuit_______] ✏️ (editable)          │
│ Region:        [Lehigh ▼] ✏️                                   │
│ Total Miles:   [125.5____] ✏️                                  │
│ Miles Planned: 45.2           (from API - read only)           │
│ Status:        [Active ▼] ✏️                                   │
│                                                                 │
│ ⚠️ Modified fields will be marked as user-overridden           │
│    and won't be updated by API syncs.                          │
│                                                                 │
│ Edit Reason: [Required when saving changes___________]         │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                              [Cancel] [Save Changes]            │
└─────────────────────────────────────────────────────────────────┘
```

**Edit Behavior:**
- Editable fields stored in `user_modified_fields` JSON
- API sync skips user-modified fields
- Audit trail via `activity_log` table
- Reason required for all edits

---

### 4. Raw JSON Viewer (`components/ui/json-viewer.blade.php`)

Syntax-highlighted JSON display with copy functionality.

```html
@props([
    'data' => [],
    'expanded' => false,
    'maxHeight' => '400px',
])
```

**Features:**
- Collapsible nested objects
- Copy to clipboard button
- Search within JSON
- Dark/light theme aware

**DaisyUI + Custom:**
- `mockup-code` for container
- Custom syntax highlighting CSS

---

### 5. Exclusion Manager (`/admin/data-management/exclusions`)

Dedicated view for managing excluded circuits.

**Layout:**
```
┌─────────────────────────────────────────────────────────────────┐
│ Excluded Circuits                              [+ Exclude New]  │
├─────────────────────────────────────────────────────────────────┤
│ │ Work Order │ Title       │ Excluded By │ Date    │ Reason    │
│ │ 12345-003  │ Test Cir    │ John Smith  │ Jan 15  │ Duplicate │
│ │ 99999-001  │ Demo Data   │ Jane Doe    │ Jan 10  │ Test data │
├─────────────────────────────────────────────────────────────────┤
│ Total: 5 excluded circuits                    [Include All]    │
└─────────────────────────────────────────────────────────────────┘
```

**Exclusion Flow:**
1. Select circuit(s) to exclude
2. Provide reason (required)
3. Circuit's `is_excluded` flag set to true
4. Circuit excluded from all dashboard aggregates
5. Logged in activity_log

---

### 6. Sync Logs Viewer (`/admin/data-management/sync-logs`)

Enhanced sync history with filtering and detail view.

**Layout:**
```
┌─────────────────────────────────────────────────────────────────┐
│ Sync Logs                                                       │
├─────────────────────────────────────────────────────────────────┤
│ Type: [All ▼] Status: [All ▼] Date: [Last 7 days ▼] [Search]   │
├─────────────────────────────────────────────────────────────────┤
│ │ Time       │ Type        │ Status  │ Processed │ Duration   │
│ │ 10:30 AM   │ circuit_list│ ✓ Done  │ 239       │ 12s        │
│ │ 10:00 AM   │ aggregates  │ ✓ Done  │ 4 regions │ 3s         │
│ │ 09:30 AM   │ circuit_list│ ✗ Failed│ 0         │ 45s        │
├─────────────────────────────────────────────────────────────────┤
│                                      [< 1 2 3 ... 10 >]        │
└─────────────────────────────────────────────────────────────────┘
```

**Click Row → Detail Panel:**
```
┌─────────────────────────────────────────────────────────────────┐
│ Sync Log #1234                                          [✕]    │
├─────────────────────────────────────────────────────────────────┤
│ Type:        circuit_list                                       │
│ Status:      Failed                                             │
│ Trigger:     Scheduled                                          │
│ Started:     Jan 17, 2026 09:30:00 AM                          │
│ Duration:    45 seconds                                         │
│ Region:      All                                                │
│                                                                 │
│ Error Message:                                                  │
│ ┌─────────────────────────────────────────────────────────────┐│
│ │ Connection timeout after 30s: Unable to reach WorkStudio   ││
│ │ API at ppl02.geodigital.com:8372                          ││
│ └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│ Error Details (JSON):                                          │
│ ┌─────────────────────────────────────────────────────────────┐│
│ │ { "exception": "...", "trace": [...] }                     ││
│ └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│                                           [Retry Sync]         │
└─────────────────────────────────────────────────────────────────┘
```

---

### 7. API Endpoint Manager (`/admin/data-management/endpoints`)

Configure API endpoints and sync settings.

**Layout:**
```
┌─────────────────────────────────────────────────────────────────┐
│ API Endpoints                                    [+ Add New]    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ WorkStudio - Circuit List                              [Edit]   │
│ ├─ URL: https://ppl02.geodigital.com:8372/ddoprotocol          │
│ ├─ Protocol: GETVIEWDATA                                        │
│ ├─ Status: ● Active                                             │
│ └─ Last Success: 10:30 AM today                                │
│                                                                 │
│ WorkStudio - Planned Units                             [Edit]   │
│ ├─ URL: https://ppl02.geodigital.com:8372/ddoprotocol          │
│ ├─ Protocol: GETPLANNEDUNITS                                    │
│ ├─ Status: ● Active                                             │
│ └─ Last Success: 10:30 AM today                                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Edit Endpoint Modal:**
```
┌─────────────────────────────────────────────────────────────────┐
│ Edit Endpoint: WorkStudio - Circuit List                [✕]    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ Name:           [WorkStudio - Circuit List___________]         │
│ Base URL:       [https://ppl02.geodigital.com:8372___]         │
│ Protocol:       [GETVIEWDATA_________________________]         │
│ Timeout (sec):  [30_____]                                      │
│                                                                 │
│ View Definition GUID:                                          │
│ [________________________________________]                      │
│                                                                 │
│ Authentication:                                                 │
│ ○ None                                                         │
│ ● User Credentials (from user_ws_credentials)                  │
│ ○ API Key                                                      │
│                                                                 │
│ Status:         [● Active ▼]                                   │
│                                                                 │
│                              [Test Connection]                  │
├─────────────────────────────────────────────────────────────────┤
│                              [Cancel] [Save Changes]            │
└─────────────────────────────────────────────────────────────────┘
```

---

### 8. Activity Log Viewer (`components/dashboard/admin/activity-log.blade.php`)

Reusable audit trail component.

```html
@props([
    'subject' => null,     // Model instance (Circuit, User, etc.)
    'limit' => 10,
    'showUser' => true,
])
```

**Display:**
```
Activity Log
├─ Jan 17, 10:30 AM - John Smith
│  Changed total_miles from 125.5 to 130.0
│  Reason: "Corrected measurement error"
│
├─ Jan 16, 2:15 PM - System (API Sync)
│  Updated api_status from "Active" to "QC"
│
└─ Jan 15, 9:00 AM - Jane Doe
   Excluded circuit
   Reason: "Duplicate entry"
```

---

## Livewire Components

### Main Components

| Component | Route | Purpose |
|-----------|-------|---------|
| `DataManagement\Index` | `/admin/data-management` | Tab container |
| `DataManagement\CircuitBrowser` | `/admin/data-management/circuits` | Circuit table |
| `DataManagement\CircuitDetail` | Modal/Panel | View/edit circuit |
| `DataManagement\SyncLogs` | `/admin/data-management/sync-logs` | Sync history |
| `DataManagement\Endpoints` | `/admin/data-management/endpoints` | API config |
| `DataManagement\Exclusions` | `/admin/data-management/exclusions` | Excluded list |

### Supporting Components

| Component | Purpose |
|-----------|---------|
| `DataManagement\CircuitSearch` | Search/filter form |
| `DataManagement\ExclusionModal` | Exclude circuit dialog |
| `DataManagement\EndpointEditor` | Edit endpoint form |
| `DataManagement\JsonViewer` | Raw data display |

---

## Folder Structure

```
resources/views/
├── components/
│   ├── ui/
│   │   ├── json-viewer.blade.php           # NEW
│   │   ├── data-table.blade.php            # NEW (sortable, paginated)
│   │   ├── filter-bar.blade.php            # NEW (reusable filters)
│   │   └── activity-log.blade.php          # NEW
│   └── dashboard/
│       └── admin/
│           ├── circuit-row.blade.php       # NEW
│           ├── sync-log-row.blade.php      # NEW
│           └── endpoint-card.blade.php     # NEW
│
├── livewire/
│   └── data-management/
│       ├── index.blade.php                 # NEW: Tab container
│       ├── circuit-browser.blade.php       # NEW
│       ├── circuit-detail.blade.php        # NEW
│       ├── sync-logs.blade.php             # NEW
│       ├── endpoints.blade.php             # NEW
│       └── exclusions.blade.php            # NEW

app/Livewire/
└── DataManagement/
    ├── Index.php                           # NEW
    ├── CircuitBrowser.php                  # NEW
    ├── CircuitDetail.php                   # NEW
    ├── SyncLogs.php                        # NEW
    ├── Endpoints.php                       # NEW
    └── Exclusions.php                      # NEW

app/
├── Services/
│   └── CircuitDataService.php              # NEW: Data manipulation logic
└── Policies/
    └── CircuitPolicy.php                   # NEW: Authorization (future)
```

---

## Files Summary

### Files to Create (18)

| File | Purpose |
|------|---------|
| `resources/views/components/ui/json-viewer.blade.php` | JSON display |
| `resources/views/components/ui/data-table.blade.php` | Sortable table |
| `resources/views/components/ui/filter-bar.blade.php` | Filter controls |
| `resources/views/components/ui/activity-log.blade.php` | Audit trail |
| `resources/views/components/dashboard/admin/circuit-row.blade.php` | Table row |
| `resources/views/components/dashboard/admin/sync-log-row.blade.php` | Log row |
| `resources/views/components/dashboard/admin/endpoint-card.blade.php` | Endpoint display |
| `app/Livewire/DataManagement/Index.php` | Tab container |
| `app/Livewire/DataManagement/CircuitBrowser.php` | Circuit table |
| `app/Livewire/DataManagement/CircuitDetail.php` | Detail/edit |
| `app/Livewire/DataManagement/SyncLogs.php` | Sync history |
| `app/Livewire/DataManagement/Endpoints.php` | API config |
| `app/Livewire/DataManagement/Exclusions.php` | Excluded list |
| `resources/views/livewire/data-management/*.blade.php` | Views (6 files) |
| `app/Services/CircuitDataService.php` | Business logic |
| `tests/Feature/DataManagement/*.php` | Feature tests |

### Files to Modify (4)

| File | Change |
|------|--------|
| `routes/web.php` | Add data management routes |
| Sidebar navigation | Add Data Management link (sudo only) |
| `app/Models/Circuit.php` | Add edit methods, scopes |
| `app/Models/User.php` | Add permission helper methods |

---

## Data Flow

### Circuit Edit Flow

```
User clicks "Edit" on circuit
         ↓
CircuitDetail modal opens
         ↓
User modifies fields, enters reason
         ↓
Save clicked
         ↓
┌─────────────────────────────────────┐
│ CircuitDataService::updateCircuit() │
│ ├─ Validate changes                 │
│ ├─ Update circuit record            │
│ ├─ Mark fields in user_modified_fields │
│ ├─ Log to activity_log              │
│ └─ Dispatch event (optional)        │
└─────────────────────────────────────┘
         ↓
UI updates, success toast shown
```

### Exclusion Flow

```
User selects circuit(s), clicks "Exclude"
         ↓
Exclusion modal opens
         ↓
User enters reason
         ↓
Confirm clicked
         ↓
┌─────────────────────────────────────┐
│ Circuit::exclude()                  │
│ ├─ Set is_excluded = true           │
│ ├─ Set exclusion_reason             │
│ ├─ Set excluded_by, excluded_at     │
│ ├─ Log to activity_log              │
│ └─ Recalculate aggregates (queued)  │
└─────────────────────────────────────┘
         ↓
Circuit removed from dashboard stats
```

---

## Acceptance Criteria

### Access Control
- [ ] Only sudo_admin can access data management pages
- [ ] 403 shown for non-sudo users
- [ ] Permission checks on all actions
- [ ] Audit log records who made changes

### Circuit Browser
- [ ] All circuits displayed with pagination
- [ ] Search by work order, title, GUID works
- [ ] Filter by region, status, excluded state
- [ ] Sorting works on all columns
- [ ] Bulk selection works
- [ ] Row actions menu functional

### Circuit Edit
- [ ] Modal/panel opens with current data
- [ ] Editable fields clearly marked
- [ ] Read-only fields (API sourced) indicated
- [ ] Reason required for changes
- [ ] Changes saved to user_modified_fields
- [ ] Activity log entry created
- [ ] Success/error feedback shown

### Exclusions
- [ ] Can exclude single circuit
- [ ] Can bulk exclude multiple circuits
- [ ] Reason required
- [ ] Can include (un-exclude) circuits
- [ ] Excluded circuits list shows all excluded
- [ ] Aggregates recalculated after exclusion

### Sync Logs
- [ ] All sync logs displayed
- [ ] Filter by type, status, date range
- [ ] Click row shows detail panel
- [ ] Failed syncs show error details
- [ ] Can retry failed syncs

### Endpoints
- [ ] All configured endpoints displayed
- [ ] Can edit endpoint configuration
- [ ] Test connection button works
- [ ] Can enable/disable endpoints

### Raw Data
- [ ] JSON viewer displays formatted data
- [ ] Syntax highlighting works
- [ ] Copy to clipboard works
- [ ] Collapsible sections work

---

## Security Considerations

1. **Role Check:** All routes/actions check `sudo_admin` role
2. **Audit Trail:** Every change logged with user, timestamp, reason
3. **No Hard Deletes:** Use soft deletes, exclusions instead
4. **Input Validation:** All user inputs validated
5. **CSRF Protection:** All forms protected
6. **Rate Limiting:** Bulk operations throttled

---

## Future Expansion Notes

### Adding New Permissions

When ready to expand beyond sudo_admin:

```php
// 1. Add permissions to seeder
Permission::create(['name' => 'data.view']);
Permission::create(['name' => 'data.edit']);

// 2. Assign to roles
$adminRole->givePermissionTo('data.view');

// 3. Update component checks
@can('data.edit')
    // Edit button
@endcan

// 4. Update middleware/policies
public function update(User $user, Circuit $circuit): bool
{
    return $user->can('data.edit');
}
```

### Adding New Data Types

Structure supports adding Work Jobs data management:

```
app/Livewire/DataManagement/
├── Circuits/           # Rename existing
│   ├── Browser.php
│   └── Detail.php
└── WorkJobs/           # Future
    ├── Browser.php
    └── Detail.php
```

---

## Decisions Made

| Decision | Choice |
|----------|--------|
| Access level | Sudo Admin only (initially) |
| Edit tracking | `user_modified_fields` JSON + activity_log |
| Exclusion method | Soft flag, not delete |
| UI pattern | Tabbed interface with slide-out details |
| JSON display | Custom component with syntax highlighting |
| Bulk operations | Supported with confirmation |

---

## Approval Status

**Status:** Draft - Awaiting Review

**To approve:** Reply "Approved" after reviewing.
