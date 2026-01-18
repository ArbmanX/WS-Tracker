# Future Phases Context Document

**Purpose:** Quick reference for continuing planning sessions. Contains rough specs and design patterns established in earlier phases.

**Completed Plans:**
- Phase 1: App Shell Layout (`01-app-shell-layout.md`) - Approved
- Phase 2: Overview Dashboard (`02-overview-dashboard.md`) - Draft
- Phase 2.5: User Onboarding (`02.5-user-onboarding.md`) - Draft
- Phase 3: Data Management (`03-data-management.md`) - Draft

---

## Established Design Patterns

### Tech Stack
- **UI:** DaisyUI 5 + Tailwind CSS 4 (NO Flux)
- **Backend:** Laravel 12 + Livewire 3
- **State:** Alpine.js primary, Livewire for server sync
- **Colors:** DaisyUI semantic only (primary, base-*, success, error, etc.)
- **Default Theme:** `corporate` (configurable via `config/themes.php`)

### Responsive Breakpoints
| Breakpoint | Width | Behavior |
|------------|-------|----------|
| Mobile | <768px | Drawer sidebar, limited features, vertical layouts |
| Tablet | 768-1024px | Collapsed sidebar (icons), most features |
| Desktop | >1024px | Full sidebar, all features |

### Component Conventions
- **Location:** `resources/views/components/`
  - `ui/` - Reusable atomic components
  - `layouts/` - Page layouts
  - `dashboard/assessments/` - Assessment-specific
  - `dashboard/work-jobs/` - Future Work Jobs mirror
  - `dashboard/admin/` - Admin-only components

### Livewire Conventions
- **Location:** `app/Livewire/`
  - `Assessments/Dashboard/` - Assessment dashboards
  - `DataManagement/` - Admin data tools
  - `Onboarding/` - Onboarding wizard
  - `Admin/` - Admin pages (sync, users)

### State Management
- **Alpine store:** `$store.dashboard` for global UI state
- **localStorage:** Immediate persistence for preferences
- **Database sync:** Debounced background sync via Livewire
- **User preferences:** `users.dashboard_preferences` JSON column

### Permission Levels
| Role | Access |
|------|--------|
| `user` | Basic dashboard, own data |
| `planner` | + Assigned circuits, planning tools |
| `admin` | + Sync controls, all regions |
| `sudo_admin` | + User management, data management, endpoints |

---

## Phase 4: Analytics Dashboard

**Route:** `/assessments/analytics/{region?}`
**Access:** All authenticated users
**Depends On:** Phase 2 (Overview Dashboard)

### Purpose
Charts and visualizations for a single region showing trends over time, planner performance, and circuit breakdowns.

### Key Features
- Region selector (defaults to user's preferred region)
- Date range picker
- Multiple chart types (line, bar, donut)
- Planner leaderboard
- Export capabilities (future)

### Charts to Include
| Chart | Type | Data Source |
|-------|------|-------------|
| Miles Planned Over Time | Area/Line | `regional_daily_aggregates` |
| Circuit Status Breakdown | Donut | `circuits` by status |
| Completion Trend | Line | `regional_weekly_aggregates` |
| Planner Performance | Horizontal Bar | `planner_weekly_aggregates` |
| Units by Type | Stacked Bar | `unit_counts_by_type` JSON |

### Layout Sketch
```
┌─────────────────────────────────────────────────────────────────┐
│ Analytics: [Lehigh ▼]                    [Date Range Picker]    │
├─────────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────┐  ┌─────────────────────────┐       │
│ │ Miles Planned (Area)    │  │ Status Breakdown (Donut)│       │
│ └─────────────────────────┘  └─────────────────────────┘       │
│ ┌─────────────────────────┐  ┌─────────────────────────┐       │
│ │ Completion Trend (Line) │  │ Top Planners (Bar)      │       │
│ └─────────────────────────┘  └─────────────────────────┘       │
│ ┌───────────────────────────────────────────────────────┐      │
│ │ Units by Type (Stacked Bar)                           │      │
│ └───────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
```

### Components to Create
- `chart-card.blade.php` - Wrapper with loading skeleton
- `date-range-picker.blade.php` - Date selection
- `region-selector.blade.php` - Region dropdown
- `planner-leaderboard.blade.php` - Ranked planner list
- `status-breakdown.blade.php` - Status donut chart

### Notes
- Charts use ApexCharts (already integrated)
- Theme-aware colors via CSS custom properties
- Fixed layout initially, drag-drop arrangement in future phase
- Mobile: Charts stack vertically, simplified views

---

## Phase 5: Kanban Board

**Route:** `/assessments/kanban/{region?}`
**Access:** All authenticated users (actions permission-gated)
**Depends On:** Phase 3 (Data Management for exclusions), Phase 4 (Analytics)

### Purpose
Visual workflow management for circuits within a selected region. Drag-and-drop between workflow stages.

### Workflow Stages (from `circuit_ui_states.workflow_stage`)
| Stage | Description | Color |
|-------|-------------|-------|
| `active` | Currently being planned | primary |
| `pending_permissions` | Waiting for permits | warning |
| `qc` | Quality check | info |
| `rework` | Needs corrections | error |
| `closed` | Completed | success |

### Key Features
- Drag-and-drop between columns
- Circuit cards with expandable details
- Filter by planner, date range
- Assign planner to circuit
- Move circuit to stage (with confirmation)
- Audit trail for stage changes

### Layout Sketch
```
┌─────────────────────────────────────────────────────────────────┐
│ Kanban: [Lehigh ▼]  Planner: [All ▼]  [+ Add Filter]           │
├─────────────────────────────────────────────────────────────────┤
│ Active (12)  │ Pending (5) │ QC (8)    │ Rework (2) │ Closed   │
│ ┌─────────┐  │ ┌─────────┐ │ ┌───────┐ │ ┌───────┐  │ ┌──────┐ │
│ │ Circuit │  │ │ Circuit │ │ │Circuit│ │ │Circuit│  │ │ ...  │ │
│ │ 12345   │  │ │ 67890   │ │ │ ...   │ │ │ ...   │  │ └──────┘ │
│ │ 45% ━━━ │  │ └─────────┘ │ └───────┘ │ └───────┘  │          │
│ │ J.Smith │  │             │           │            │          │
│ └─────────┘  │             │           │            │          │
│ ┌─────────┐  │             │           │            │          │
│ │ Circuit │  │             │           │            │          │
│ │ ...     │  │             │           │            │          │
│ └─────────┘  │             │           │            │          │
└─────────────────────────────────────────────────────────────────┘
```

### Circuit Card Levels
| Level | Content | Trigger |
|-------|---------|---------|
| Minimal | Work order, title, %, planner | Default |
| Expanded | + Miles, units, dates, actions | Click/hover |
| Full Detail | All data, edit options | "View Details" button |

### Components to Create
- `kanban-board.blade.php` - Main board container
- `kanban-column.blade.php` - Stage column
- `circuit-card.blade.php` - Draggable card
- `circuit-card-expanded.blade.php` - Expanded view
- `planner-assign-modal.blade.php` - Assignment dialog
- `stage-move-modal.blade.php` - Confirm stage change

### Mobile Behavior
- Columns become horizontal scrollable tabs
- No drag-and-drop (use action buttons instead)
- "Move to Stage" dropdown on each card
- Simplified card view

### Permissions
| Action | Required Role |
|--------|---------------|
| View Kanban | Any authenticated |
| Drag between stages | `planner`, `admin`, `sudo_admin` |
| Assign planner | `admin`, `sudo_admin` |
| Edit circuit details | Via Data Management only |

### Technical Notes
- Uses Livewire Sortable for drag-and-drop
- Stage changes update `circuit_ui_states` table
- Activity logged for all stage moves
- Optimistic UI updates with rollback on failure

---

## Phase 6: Sync Controls Dashboard

**Route:** `/admin/sync`
**Access:** `admin`, `sudo_admin`
**Existing:** Partially built (`app/Livewire/Admin/SyncControl.php`)

### Purpose
Trigger and monitor data synchronization with WorkStudio API.

### Key Features
- Manual sync trigger (by type, region)
- Sync status indicator (running, idle, error)
- Recent sync history
- Sync schedule display
- Error alerts and retry options

### Layout Sketch
```
┌─────────────────────────────────────────────────────────────────┐
│ Sync Controls                                     [Admin]       │
├─────────────────────────────────────────────────────────────────┤
│ Status: ● Idle (Last sync: 10:30 AM)              [Sync Now ▼] │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ ┌─────────────────────┐  ┌─────────────────────┐               │
│ │ Circuit Sync        │  │ Planned Units Sync  │               │
│ │ ● Active            │  │ ● Active            │               │
│ │ Every 30 min        │  │ On status change    │               │
│ │ [Sync Now]          │  │ [Sync Now]          │               │
│ └─────────────────────┘  └─────────────────────┘               │
│                                                                 │
│ Recent Activity                                                 │
│ ├─ 10:30 AM - Circuit sync completed (239 circuits)            │
│ ├─ 10:00 AM - Aggregates recalculated                          │
│ └─ 09:30 AM - Circuit sync completed (239 circuits)            │
│                                                                 │
│ [View Full Sync History →]                                      │
└─────────────────────────────────────────────────────────────────┘
```

### Sync Types
| Type | Description | Trigger |
|------|-------------|---------|
| `circuit_list` | Fetch all circuits from WS | Scheduled, Manual |
| `planned_units` | Fetch planned units for active circuits | On circuit status change |
| `aggregates` | Recalculate regional/planner stats | After circuit sync |

### Components to Create/Update
- `sync-status-card.blade.php` - Sync type card with status
- `sync-trigger-dropdown.blade.php` - Region/type selector
- `sync-activity-feed.blade.php` - Recent syncs list

### Integration with Data Management
- Link to full sync logs (`/admin/data-management/sync-logs`)
- Link to endpoint configuration (`/admin/data-management/endpoints`)

---

## Phase 7: User Management

**Route:** `/admin/users`
**Access:** `sudo_admin` only
**Existing:** Partially built (`app/Livewire/Admin/UserManagement.php`)

### Purpose
Manage user accounts, roles, and permissions.

### Key Features
- User list with search/filter
- Create new users
- Edit user details
- Assign/remove roles
- Reset passwords
- Link/unlink WorkStudio accounts
- View user activity

### Layout Sketch
```
┌─────────────────────────────────────────────────────────────────┐
│ User Management                              [+ Create User]    │
├─────────────────────────────────────────────────────────────────┤
│ Search: [___________] Role: [All ▼] Status: [All ▼]            │
├─────────────────────────────────────────────────────────────────┤
│ │ Name        │ Email           │ Role       │ WS Link │ Actions│
│ │ John Smith  │ john@email.com  │ Admin      │ ● Yes   │ [⋮]   │
│ │ Jane Doe    │ jane@email.com  │ Planner    │ ● Yes   │ [⋮]   │
│ │ Bob Wilson  │ bob@email.com   │ User       │ ○ No    │ [⋮]   │
├─────────────────────────────────────────────────────────────────┤
│ Showing 1-25 of 42 users                [< 1 2 >]              │
└─────────────────────────────────────────────────────────────────┘
```

### User Detail Panel
```
┌─────────────────────────────────────────────────────────────────┐
│ John Smith                                              [✕]    │
├─────────────────────────────────────────────────────────────────┤
│ [Profile] [Roles] [Activity] [WorkStudio]                      │
├─────────────────────────────────────────────────────────────────┤
│ Profile                                                         │
│ Name:    [John Smith_______________]                           │
│ Email:   [john@email.com___________]                           │
│ Region:  [Lehigh ▼]                                            │
│                                                                 │
│ Roles                                                           │
│ ☑ Admin  ☐ Sudo Admin  ☑ Planner                               │
│                                                                 │
│ Account Actions                                                 │
│ [Reset Password] [Disable Account] [Delete User]               │
├─────────────────────────────────────────────────────────────────┤
│                              [Cancel] [Save Changes]            │
└─────────────────────────────────────────────────────────────────┘
```

### Components to Create/Update
- `user-table.blade.php` - User list with actions
- `user-detail-panel.blade.php` - Edit panel
- `user-create-modal.blade.php` - New user form
- `role-selector.blade.php` - Checkbox role selection
- `ws-link-status.blade.php` - WorkStudio connection status

### Unlinked Planners Integration
- Existing page: `/admin/planners/unlinked`
- Shows WS planners not linked to app users
- Allow creating user from unlinked planner
- Or linking existing user to WS planner

---

## Settings Pages (Phase 8+)

**Routes:** `/settings/*`
**Access:** All authenticated users
**Existing:** Built with Flux, needs migration to DaisyUI

### Pages to Migrate
| Page | Route | Current Status |
|------|-------|----------------|
| Profile | `/settings/profile` | Flux, needs migration |
| Password | `/settings/password` | Flux, needs migration |
| Appearance | `/settings/appearance` | Flux, needs migration (use new theme-picker) |
| Two-Factor | `/settings/two-factor` | Flux, needs migration |

### New Settings to Add
- Dashboard preferences (view mode, default region)
- Notification preferences
- Keyboard shortcuts (future)

---

## Database Tables Reference

### Core Tables
| Table | Purpose |
|-------|---------|
| `users` | User accounts, preferences |
| `regions` | Geographic regions |
| `circuits` | Circuit data from WS |
| `circuit_ui_states` | Workflow stage, UI state |
| `circuit_user` | Planner assignments |

### Aggregate Tables
| Table | Purpose |
|-------|---------|
| `regional_daily_aggregates` | Daily region stats |
| `regional_weekly_aggregates` | Weekly region stats |
| `planner_daily_aggregates` | Daily planner stats |
| `planner_weekly_aggregates` | Weekly planner stats |
| `circuit_aggregates` | Per-circuit rollups |

### Supporting Tables
| Table | Purpose |
|-------|---------|
| `sync_logs` | Sync history |
| `activity_log` | Audit trail |
| `planned_units_snapshots` | Planned unit history |
| `circuit_snapshots` | Circuit state history |

---

## Naming Conventions

### Routes
- Assessments: `/assessments/{page}`
- Admin: `/admin/{page}`
- Settings: `/settings/{page}`
- API: `/api/v1/{resource}`

### Components
- UI components: `kebab-case.blade.php`
- Livewire: `PascalCase.php`
- Views: Match Livewire namespace

### CSS Classes
- Use DaisyUI component classes
- Custom classes: `wst-{component}-{modifier}`
- No raw Tailwind colors

---

## Mobile-First Priorities

### Mobile Must-Haves
- View high-level stats
- Quick region switching
- Assign planner to circuit
- Move circuit to stage
- View sync status

### Mobile Deferred
- Drag-and-drop
- Complex charts
- Data editing
- Bulk operations
- Full tables (use cards instead)

---

## Future Considerations

### Work Jobs Dashboard
- Mirror Assessments structure
- Shared components in `ui/`
- Separate components in `dashboard/work-jobs/`
- Same navigation patterns

### Custom Dashboards
- Widget system with predefined components
- Drag-drop layout (GridStack or similar)
- Save layouts to `dashboard_preferences`
- Share layouts between users (admin feature)

### Real-time Updates
- Livewire polling for status changes
- Consider WebSockets for sync notifications
- Optimistic UI for drag-drop

---

*Last Updated: January 2026*
*Reference this document when starting new planning sessions.*
