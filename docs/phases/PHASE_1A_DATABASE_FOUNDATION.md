# Phase 1A: Database Foundation

> **Goal:** Create all database tables with proper relationships, enums, and indexes.
> **Estimated Time:** 2-3 days
> **Dependencies:** Phase 0 complete (PostgreSQL configured)

---

## Status: 10% Complete

| Item                      | Status | Notes                                        |
| ------------------------- | ------ | -------------------------------------------- |
| PostgreSQL enums          | Needed | workflow_stage, sync_type, etc.              |
| regions table             | Needed | 4 PPL regions                                |
| unit_types table          | Done   | 44 unit types seeded                         |
| permission_statuses table | Needed | Approved, Refused, Pending                   |
| api_status_configs table  | Needed | ACTIV, QC, REWORK, CLOSE                     |
| Spatie permissions        | Needed | Publish and migrate                          |
| User extensions           | Needed | WS credential columns                        |
| circuits table            | Needed | Core domain table                            |
| Aggregate tables          | Needed | circuit_aggregates, planner_daily_aggregates |

---

## Migration Order

Create migrations in this exact order to respect dependencies:

### Phase 1: Foundation (No Dependencies)

```
2026_01_XX_000001_create_postgres_enums.php
2026_01_XX_000002_create_regions_table.php
2026_01_XX_000003_create_permission_statuses_table.php
2026_01_XX_000004_create_api_status_configs_table.php
(unit_types already exists: 2026_01_11_200958)
```

### Phase 2: Spatie Permissions

```
2026_01_XX_000005_create_permission_tables.php (vendor publish)
```

### Phase 3: User Extensions

```
2026_01_XX_000006_add_ws_columns_to_users_table.php
2026_01_XX_000007_create_user_ws_credentials_table.php
2026_01_XX_000008_create_user_regions_table.php
2026_01_XX_000009_create_unlinked_planners_table.php
```

### Phase 4: Core Domain

```
2026_01_XX_000010_create_circuits_table.php
2026_01_XX_000011_create_circuit_ui_states_table.php
2026_01_XX_000012_create_circuit_user_table.php
2026_01_XX_000013_create_circuit_snapshots_table.php
```

### Phase 5: Aggregates

```
2026_01_XX_000014_create_circuit_aggregates_table.php
2026_01_XX_000015_create_planner_daily_aggregates_table.php
2026_01_XX_000016_create_regional_daily_aggregates_table.php
```

### Phase 6: Sync Management

```
2026_01_XX_000017_create_sync_logs_table.php
```

---

## Checklist

### Enums

- [ ] Create PostgreSQL enum migration
  
  ```bash
  sail artisan make:migration create_postgres_enums --no-interaction
  ```
  
  Enums to create:
  
  - `workflow_stage`: active, pending_permissions, qc, rework, closed
  - `assignment_source`: api_sync, manual
  - `snapshot_type`: daily, status_change, manual
  - `sync_type`: circuit_list, aggregates, full
  - `sync_status`: started, completed, failed, warning
  - `sync_trigger`: scheduled, manual, workflow_event

### Migrations

- [ ] Create regions table migration
- [ ] Create permission_statuses table migration
- [ ] Create api_status_configs table migration
- [ ] Publish Spatie permission tables
- [ ] Create user extension migration (add WS columns)
- [ ] Create user_ws_credentials table migration
- [ ] Create user_regions pivot migration
- [ ] Create unlinked_planners table migration
- [ ] Create circuits table migration (with JSONB)
- [ ] Create circuit_ui_states table migration
- [ ] Create circuit_user pivot migration
- [ ] Create circuit_snapshots table migration
- [ ] Create circuit_aggregates table migration
- [ ] Create planner_daily_aggregates table migration
- [ ] Create regional_daily_aggregates table migration
- [ ] Create sync_logs table migration

### Models

- [ ] Create Region model with relationships
- [ ] Create PermissionStatus model
- [ ] Create ApiStatusConfig model
- [ ] Create UserWsCredential model (with encryption)
- [ ] Create UnlinkedPlanner model
- [ ] Create Circuit model with all relationships
- [ ] Create CircuitUiState model
- [ ] Create CircuitSnapshot model
- [ ] Create CircuitAggregate model
- [ ] Create PlannerDailyAggregate model
- [ ] Create RegionalDailyAggregate model
- [ ] Create SyncLog model
- [ ] Extend User model with HasRoles trait

### Enums (PHP)

- [ ] Create WorkflowStage enum
- [ ] Create AssignmentSource enum
- [ ] Create SnapshotType enum
- [ ] Create SyncType enum
- [ ] Create SyncStatus enum
- [ ] Create SyncTrigger enum

### Factories

- [ ] Create RegionFactory
- [ ] Create CircuitFactory
- [ ] Create CircuitUiStateFactory
- [ ] Create CircuitSnapshotFactory
- [ ] Create CircuitAggregateFactory
- [ ] Create PlannerDailyAggregateFactory
- [ ] Create SyncLogFactory

### Seeders

- [ ] Create RolesAndPermissionsSeeder
- [ ] Create RegionsSeeder (Central, Lancaster, Lehigh, Harrisburg)
- [ ] Create ApiStatusConfigsSeeder
- [ ] Create PermissionStatusesSeeder
- [ ] Update DatabaseSeeder with call order

### Final Steps

- [ ] Run migrations: `sail artisan migrate:fresh --seed`
- [ ] Verify: `sail artisan tinker` → `Region::count()`

---

## File Structure

```
app/
├── Enums/
│   ├── WorkflowStage.php
│   ├── AssignmentSource.php
│   ├── SnapshotType.php
│   ├── SyncType.php
│   ├── SyncStatus.php
│   └── SyncTrigger.php
├── Models/
│   ├── Region.php
│   ├── PermissionStatus.php
│   ├── ApiStatusConfig.php
│   ├── UnitType.php                # Already exists
│   ├── User.php                    # Extend with HasRoles
│   ├── UserWsCredential.php
│   ├── UnlinkedPlanner.php
│   ├── Circuit.php
│   ├── CircuitUiState.php
│   ├── CircuitSnapshot.php
│   ├── CircuitAggregate.php
│   ├── PlannerDailyAggregate.php
│   ├── RegionalDailyAggregate.php
│   └── SyncLog.php

database/
├── factories/
│   ├── RegionFactory.php
│   ├── CircuitFactory.php
│   ├── CircuitUiStateFactory.php
│   ├── CircuitSnapshotFactory.php
│   ├── CircuitAggregateFactory.php
│   ├── PlannerDailyAggregateFactory.php
│   └── SyncLogFactory.php
├── migrations/
│   └── (17 migration files)
└── seeders/
    ├── DatabaseSeeder.php
    ├── RolesAndPermissionsSeeder.php
    ├── RegionsSeeder.php
    ├── ApiStatusConfigsSeeder.php
    ├── PermissionStatusesSeeder.php
    └── UnitTypesSeeder.php         # Already exists
```

---

## Key Tables Schema

### circuits (Core Domain)

```php
Schema::create('circuits', function (Blueprint $table) {
    $table->id();
    $table->string('job_guid')->unique();
    $table->string('work_order')->index();
    $table->string('extension')->nullable();
    $table->foreignId('parent_circuit_id')->nullable()->constrained('circuits');
    $table->foreignId('region_id')->constrained();
    $table->string('title');
    $table->string('contractor')->nullable();
    $table->string('cycle_type')->nullable();
    $table->decimal('total_miles', 10, 2)->default(0);
    $table->decimal('miles_planned', 10, 2)->default(0);
    $table->decimal('percent_complete', 5, 2)->default(0);
    $table->decimal('total_acres', 10, 2)->default(0);
    $table->date('start_date')->nullable();
    $table->date('api_modified_date');
    $table->string('api_status');
    $table->jsonb('api_data_json')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamp('last_planned_units_synced_at')->nullable();
    $table->boolean('planned_units_sync_enabled')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['region_id', 'api_status']);
    $table->index(['api_modified_date']);
});
```

### circuit_aggregates (Replaces planned_units)

```php
Schema::create('circuit_aggregates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('circuit_id')->constrained()->onDelete('cascade');
    $table->date('aggregate_date');
    $table->boolean('is_rollup')->default(false);

    // Normalized metrics
    $table->integer('total_units')->default(0);
    $table->decimal('total_linear_ft', 12, 2)->default(0);
    $table->decimal('total_acres', 12, 4)->default(0);
    $table->integer('total_trees')->default(0);

    // Permission counts
    $table->integer('units_approved')->default(0);
    $table->integer('units_refused')->default(0);
    $table->integer('units_pending')->default(0);

    // JSONB for flexible breakdowns
    $table->jsonb('unit_counts_by_type')->nullable();
    $table->jsonb('linear_ft_by_type')->nullable();
    $table->jsonb('acres_by_type')->nullable();
    $table->jsonb('planner_distribution')->nullable();

    $table->timestamps();

    $table->unique(['circuit_id', 'aggregate_date', 'is_rollup']);
});
```

---

## Seeder Data

### Regions

```php
['name' => 'Central', 'code' => 'CENTRAL'],
['name' => 'Lancaster', 'code' => 'LANCASTER'],
['name' => 'Lehigh', 'code' => 'LEHIGH'],
['name' => 'Harrisburg', 'code' => 'HARRISBURG'],
```

### Roles & Permissions

```php
// Roles
'sudo_admin', 'admin', 'planner', 'general_foreman'

// Permissions
'view-all-circuits', 'view-assigned-circuits', 'change-workflow-stage',
'force-sync', 'manage-users', 'link-planners', 'view-sync-logs',
'manage-settings', 'hide-circuits'
```

### Permission Statuses

```php
['name' => 'Pending', 'code' => '', 'color' => 'warning'],
['name' => 'Approved', 'code' => 'Approved', 'color' => 'success'],
['name' => 'Refused', 'code' => 'Refused', 'color' => 'error'],
```

---

## Testing Requirements

```php
// tests/Unit/Models/CircuitTest.php
it('has a region relationship', function () {...});
it('has a ui state relationship', function () {...});
it('has planners through pivot', function () {...});
it('has children for split assessments', function () {...});

// tests/Unit/Models/UnitTypeTest.php
it('caches unit types by code', function () {...});
it('returns codes for category', function () {...});

// tests/Feature/Database/MigrationTest.php
it('runs all migrations successfully', function () {...});
it('seeds all reference data', function () {...});
it('creates correct indexes', function () {...});
```

---

## Next Phase

Once all items are checked, proceed to **[Phase 1B: API Service Layer](./PHASE_1B_API_SERVICE_LAYER.md)**.
