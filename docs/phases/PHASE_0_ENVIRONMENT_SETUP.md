# Phase 0: Environment Setup

> **Goal:** Establish a fully functional local development environment with all dependencies configured.
> **Estimated Time:** 1 day
> **Dependencies:** None (foundation phase)

---

## Status: Partially Complete

| Item | Status | Notes |
|------|--------|-------|
| PostgreSQL database | Needed | `ws_tracker` database with proper user |
| `.env` configured | Needed | DB_CONNECTION=pgsql |
| Timezone setting | Needed | `config/app.php` → America/New_York |
| `config/workstudio.php` | Partial | File exists but empty |
| NPM packages | Done | livewire-sortable, apexcharts installed |
| Spatie packages | Partial | Installed, needs publishing |

---

## Checklist

### Database Setup
- [ ] Create PostgreSQL database and user
  ```bash
  sudo -u postgres createuser ws_tracker
  sudo -u postgres createdb ws_tracker -O ws_tracker
  sudo -u postgres psql -c "ALTER USER ws_tracker WITH PASSWORD 'your_secure_password';"
  ```
- [ ] Update `.env` with PostgreSQL credentials
  ```env
  DB_CONNECTION=pgsql
  DB_HOST=127.0.0.1
  DB_PORT=5432
  DB_DATABASE=ws_tracker
  DB_USERNAME=ws_tracker
  DB_PASSWORD=your_secure_password
  ```

### Laravel Configuration
- [ ] Set timezone in `config/app.php`
  ```php
  'timezone' => 'America/New_York',
  ```
- [ ] Complete `config/workstudio.php` with API settings (see template below)
- [ ] Add WorkStudio environment variables to `.env`
  ```env
  WORKSTUDIO_BASE_URL=https://ppl02.geodigital.com:8372/ddoprotocol/
  WORKSTUDIO_TIMEOUT=60
  WORKSTUDIO_SERVICE_USERNAME=
  WORKSTUDIO_SERVICE_PASSWORD=
  ```

### Package Publishing
- [ ] Publish Spatie Permission config and migrations
  ```bash
  sail artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
  ```
- [ ] Publish Spatie Activitylog migrations
  ```bash
  sail artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
  ```

### Frontend Assets
- [ ] Verify NPM packages installed
  ```bash
  sail npm list livewire-sortable apexcharts
  ```
- [ ] Update `resources/js/app.js` if needed
  ```javascript
  import 'livewire-sortable';
  import ApexCharts from 'apexcharts';
  window.ApexCharts = ApexCharts;
  ```

### Verification
- [ ] Verify PostgreSQL connection
  ```bash
  sail artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected!';"
  ```
- [ ] Verify config loads
  ```bash
  sail artisan tinker --execute="echo config('workstudio.base_url');"
  ```
- [ ] Run build
  ```bash
  sail npm run build
  ```

---

## Files to Create/Modify

### `config/workstudio.php` (Complete Content)

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WorkStudio API Configuration
    |--------------------------------------------------------------------------
    */

    'base_url' => env('WORKSTUDIO_BASE_URL', 'https://ppl02.geodigital.com:8372/ddoprotocol/'),
    'timeout' => env('WORKSTUDIO_TIMEOUT', 60),
    'max_retries' => 5,

    /*
    |--------------------------------------------------------------------------
    | View GUIDs
    |--------------------------------------------------------------------------
    | These are the WorkStudio view definition GUIDs for each data type.
    */

    'views' => [
        'vegetation_assessments' => '{A856F956-88DF-4807-90E2-7E12C25B5B32}',
        'work_jobs' => '{546D9963-9242-4945-8A74-15CA83CDA537}',
        'planned_units' => '{985AECEF-D75B-40F3-9F9B-37F21C63FF4A}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Mappings
    |--------------------------------------------------------------------------
    | Maps status codes to filter values and display captions.
    */

    'statuses' => [
        'new' => ['value' => 'SA', 'caption' => 'New'],
        'active' => ['value' => 'ACTIV', 'caption' => 'In Progress'],
        'qc' => ['value' => 'QC', 'caption' => 'QC'],
        'rework' => ['value' => 'REWRK', 'caption' => 'Rework'],
        'deferral' => ['value' => 'DEF', 'caption' => 'Deferral'],
        'closed' => ['value' => 'CLOSE', 'caption' => 'Closed'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Account
    |--------------------------------------------------------------------------
    | Fallback credentials when user credentials are unavailable.
    */

    'service_account' => [
        'username' => env('WORKSTUDIO_SERVICE_USERNAME'),
        'password' => env('WORKSTUDIO_SERVICE_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'rate_limit_delay' => 500000, // microseconds (0.5 seconds)
        'calls_before_delay' => 5,
    ],
];
```

---

## Testing Requirements

```bash
# Manual verification
sail artisan tinker --execute="DB::connection()->getPdo();"
sail artisan tinker --execute="config('workstudio.base_url');"
sail artisan config:cache && sail artisan config:show workstudio
```

---

## Next Phase

Once all items are checked, proceed to **[Phase 1A: Database Foundation](./PHASE_1A_DATABASE_FOUNDATION.md)**.
