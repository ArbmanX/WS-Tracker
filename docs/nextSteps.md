## mmediate Next Step: Complete Phase 0

Current State

config/workstudio.php is EMPTY (needs content)

- Timezone needs to be set to America/New_York
- Spatie Permission tables need publishing
- 5 migrations exist, 15+ more needed

---
Step-by-Step Action Plan

Step 1: Populate config/workstudio.php

This is the first thing to do - the API configuration file is empty:

```php
<?php

return [
'base_url' => env('WORKSTUDIO_BASE_URL', 'https://ppl02.geodigital.com:8372/ddoprotocol/'),
'timeout' => env('WORKSTUDIO_TIMEOUT', 60),
'max_retries' => 5,

'views' => [
'vegetation_assessments' => '{A856F956-88DF-4807-90E2-7E12C25B5B32}',
'work_jobs' => '{546D9963-9242-4945-8A74-15CA83CDA537}',
'planned_units' => '{985AECEF-D75B-40F3-9F9B-37F21C63FF4A}',

],

'statuses' => [
'new' => ['value' => 'SA', 'caption' => 'New'],
'active' => ['value' => 'ACTIV', 'caption' => 'In Progress'],
'qc' => ['value' => 'QC', 'caption' => 'QC'],
'rework' => ['value' => 'REWRK', 'caption' => 'Rework'],
'deferral' => ['value' => 'DEF', 'caption' => 'Deferral'],
'closed' => ['value' => 'CLOSE', 'caption' => 'Closed'],

],

'service_account' => [
'username' => env('WORKSTUDIO_SERVICE_USERNAME'),
'password' => env('WORKSTUDIO_SERVICE_PASSWORD'),

],

'sync' => [
'rate_limit_delay' => 500000,
'calls_before_delay' => 5,

],
];
```

## Step 2: Set Timezone in config/app.php

  Change:
  'timezone' => 'UTC',
  To:
  'timezone' => 'America/New_York',

## Step 3: Publish Spatie Permission Tables

sail artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

## Step 4: Add Environment Variables to .env

- WORKSTUDIO_BASE_URL=<https://ppl02.geodigital.com:8372/ddoprotocol/>
- WORKSTUDIO_TIMEOUT=60
- WORKSTUDIO_SERVICE_USERNAME=
- WORKSTUDIO_SERVICE_PASSWORD=

---
***Then: Phase 1A - Database Foundation***

### Migration Order (15 migrations to create)
┌─────┬────────────────────────────────────────┬─────────────────────────────┐
│  #  │               Migration                │           Purpose           │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 1   │ create_postgres_enums                  │ PostgreSQL enum types       │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 2   │ create_regions_table                   │ PPL regions                 │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 3   │ create_permission_statuses_table       │ Approved/Pending/Refused    │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 4   │ create_api_status_configs_table        │ Sync configuration          │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 5   │ (Spatie) create_permission_tables      │ Roles & permissions         │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 6   │ add_ws_columns_to_users_table          │ User WS credential tracking │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 7   │ create_user_ws_credentials_table       │ Encrypted credentials       │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 8   │ create_user_regions_table              │ User-region pivot           │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 9   │ create_unlinked_planners_table         │ Unmatched API usernames     │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 10  │ create_circuits_table                  │ Core table                  │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 11  │ create_circuit_ui_states_table         │ Workflow stages             │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 12  │ create_circuit_user_table              │ Planner assignments         │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 13  │ create_circuit_snapshots_table         │ Historical tracking         │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 14  │ create_circuit_aggregates_table        │ Daily totals                │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 15  │ create_planner_daily_aggregates_table  │ Planner productivity        │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 16  │ create_regional_daily_aggregates_table │ Regional rollups            │
├─────┼────────────────────────────────────────┼─────────────────────────────┤
│ 17  │ create_sync_logs_table                 │ Sync audit trail            │
└─────┴────────────────────────────────────────┴─────────────────────────────┘
---

★ Insight ─────────────────────────────────────
Migration Order Matters: In Laravel with PostgreSQL, we create enums first, then reference tables
(regions, permission_statuses), then core domain tables (circuits), then aggregate tables. This
respects foreign key dependencies.
─────────────────────────────────────────────────

-                   ]
