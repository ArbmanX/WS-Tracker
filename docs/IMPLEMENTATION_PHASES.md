# WS-Tracker Implementation Phases

> **Project:** Vegetation Maintenance Admin Dashboard for PPL Circuit Tracking
> **Stack:** Laravel 12, Livewire 3, Tailwind CSS v4, DaisyUI 5.x, PostgreSQL, Pest 4
> **Total Estimated Time:** 16-19 days

---

## Quick Links

| Phase | Name | Est. Time | Status |
|-------|------|-----------|--------|
| [0](./phases/PHASE_0_ENVIRONMENT_SETUP.md) | Environment Setup | 1 day | Partial |
| [1A](./phases/PHASE_1A_DATABASE_FOUNDATION.md) | Database Foundation | 2-3 days | 10% |
| [1B](./phases/PHASE_1B_API_SERVICE_LAYER.md) | API Service Layer | 2 days | Not Started |
| [1C](./phases/PHASE_1C_SYNC_JOBS.md) | Sync Jobs | 2 days | Not Started |
| [1D](./phases/PHASE_1D_THEME_SYSTEM.md) | Theme System | 1 day | Not Started |
| [1E](./phases/PHASE_1E_DASHBOARD_UI.md) | Dashboard UI | 4 days | Not Started |
| [1F](./phases/PHASE_1F_CHARTS.md) | Charts & Visualization | 2 days | Not Started |
| [1G](./phases/PHASE_1G_ADMIN_FEATURES.md) | Admin Features | 2 days | Not Started |
| [1H](./phases/PHASE_1H_TESTING.md) | Testing & Polish | 2 days | Not Started |

---

## Phase Dependencies

```
Phase 0: Environment Setup
    │
    └──► Phase 1A: Database Foundation
            │
            ├──► Phase 1B: API Service Layer
            │        │
            │        └──► Phase 1C: Sync Jobs
            │                 │
            │                 └──► Phase 1E: Dashboard UI
            │                          │
            │                          └──► Phase 1F: Charts
            │                                   │
            │                                   └──► Phase 1H: Testing
            │
            └──► Phase 1D: Theme System (can run parallel with 1B/1C)
                     │
                     └──► Phase 1E: Dashboard UI

Phase 1G: Admin Features
    │
    └──► Requires: Phase 1C + Phase 1A
```

---

## Architecture Overview

### Key Decision: Aggregate-Only Storage

This dashboard stores **aggregated data only** - NOT individual planned unit records.

**Why?**
- 95%+ reduction in database size
- Faster dashboard queries (pre-computed totals)
- Simpler sync logic
- Management-focused (not planner tool)

**How?**
```
WorkStudio API (individual units)
        │
        ▼
AggregateCalculationService
        │
        ▼
circuit_aggregates table (daily totals)
```

### Database Schema Summary

| Table | Purpose |
|-------|---------|
| `regions` | PPL regions (Central, Lancaster, Lehigh, Harrisburg) |
| `unit_types` | 44 vegetation work unit types |
| `circuits` | Circuit/assessment data from API |
| `circuit_ui_states` | Workflow stage (Active→QC→Rework→Closed) |
| `circuit_aggregates` | Daily circuit totals (units, feet, acres, permissions) |
| `planner_daily_aggregates` | Planner productivity tracking |
| `regional_daily_aggregates` | Regional rollups |
| `sync_logs` | Sync audit trail |

### User Roles

| Role | Dashboard | Workflow | Sync | Users |
|------|-----------|----------|------|-------|
| `sudo_admin` | All | Edit | Control | Manage |
| `admin` | All | Edit | View | - |
| `planner` | Assigned | View | - | - |
| `general_foreman` | Assigned | View | - | - |

---

## Current Progress

### Completed
- [x] Laravel 12 foundation
- [x] Authentication with Fortify 2FA
- [x] DaisyUI 5.x + Tailwind v4
- [x] UnitType model and seeder (44 unit types)
- [x] NPM packages (livewire-sortable, apexcharts)
- [x] Spatie packages installed

### In Progress
- [ ] Database migrations (1/17 complete)
- [ ] config/workstudio.php (file exists, needs content)

### Not Started
- [ ] Remaining 16 migrations
- [ ] API service layer
- [ ] Sync jobs
- [ ] Dashboard components
- [ ] Admin features
- [ ] Test suite

---

## Getting Started

### Start Development Environment

```bash
# With Sail (recommended)
sail up -d
sail artisan migrate:fresh --seed

# Without Sail
php artisan migrate:fresh --seed
npm run dev
```

### Run Tests

```bash
sail artisan test --compact
```

### Code Formatting

```bash
vendor/bin/pint --dirty
```

---

## Reference Documents

### Primary (Use These)
- [`docs/IMPLEMENTATION_PLAN.md`](./IMPLEMENTATION_PLAN.md) - Complete implementation details
- [`docs/project-context.md`](./project-context.md) - Quick reference
- [`docs/UnitTypes.json`](./UnitTypes.json) - Unit type definitions
- [`docs/WS_TRACKER_DOCS.md`](./WS_TRACKER_DOCS.md) - API documentation

### UI Mockups
- [`docs/diagrams/DASHBOARD_MOCKUP.md`](./diagrams/DASHBOARD_MOCKUP.md) - Dashboard wireframe
- [`docs/diagrams/ADMIN_PANEL_MOCKUP.md`](./diagrams/ADMIN_PANEL_MOCKUP.md) - Admin panel wireframe

### Archived (Outdated)
- [`docs/archived/ARCHITECTURE.md`](./archived/ARCHITECTURE.md) - Has outdated storage approach
- [`docs/archived/unit-types-analysis.md`](./archived/unit-types-analysis.md) - Superseded by UnitTypes.json

---

## Key Configuration

### Environment Variables

```env
# Database
DB_CONNECTION=pgsql
DB_DATABASE=ws_tracker

# WorkStudio API
WORKSTUDIO_BASE_URL=https://ppl02.geodigital.com:8372/ddoprotocol/
WORKSTUDIO_TIMEOUT=60
WORKSTUDIO_SERVICE_USERNAME=
WORKSTUDIO_SERVICE_PASSWORD=
```

### Sync Schedule

| Status | Frequency | Time (ET) |
|--------|-----------|-----------|
| ACTIV | 2x daily | 4:30 AM, 4:30 PM |
| QC/REWORK/CLOSE | Weekly | Monday 4:30 AM |

---

## Implementation Order

### Week 1
1. **Phase 0** - Complete environment setup
2. **Phase 1A** - Create all migrations, models, seeders
3. **Phase 1D** - Theme system (parallel)

### Week 2
4. **Phase 1B** - API service layer
5. **Phase 1C** - Sync jobs

### Week 3
6. **Phase 1E** - Dashboard UI components

### Week 4
7. **Phase 1F** - Charts
8. **Phase 1G** - Admin features
9. **Phase 1H** - Testing & polish

---

## Success Criteria

MVP is complete when:
- [ ] All 9 phases marked complete
- [ ] 90%+ test coverage
- [ ] Laravel Pint clean
- [ ] No N+1 queries
- [ ] Manual smoke test passes for all roles
- [ ] Scheduled sync runs successfully

---

## Support

For questions about this implementation plan:
1. Review the detailed phase documents
2. Check `docs/project-context.md` for architecture decisions
3. Search docs with `search-docs` MCP tool for Laravel ecosystem help
