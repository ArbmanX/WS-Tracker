# WS-Tracker Implementation Plan

> **Generated:** January 2026
> **Stack:** Laravel 12, Livewire 3, Tailwind CSS v4, DaisyUI 5.x, PostgreSQL, Pest 4
> **Purpose:** Vegetation maintenance admin dashboard for PPL circuit tracking

---

## Table of Contents

1. [Confirmed Decisions](#confirmed-decisions)
2. [Brand Colors & Theme System](#brand-colors--theme-system)
3. [Package Installation](#package-installation)
4. [Database Schema](#database-schema)
5. [Migration Order](#migration-order)
6. [API Service Layer](#api-service-layer)
7. [Sync Strategy](#sync-strategy)
8. [Livewire Components](#livewire-components)
9. [Implementation Phases](#implementation-phases)
10. [File Structure](#file-structure)
11. [Testing Strategy](#testing-strategy)

---

## Confirmed Decisions

| Decision | Choice | Notes |
|----------|--------|-------|
| **Database** | PostgreSQL | JSONB support for aggregate breakdowns |
| **Data Storage** | **Aggregates Only** | NO individual unit records stored; compute totals at sync time |
| **Role Management** | Spatie Permissions | 4 roles, 9 permissions, pivot tables |
| **WS Credentials** | Hybrid | Service account for auto-sync, optional user creds |
| **Snapshots** | Daily at sync | Aggregate tables track daily totals per circuit/planner/region |
| **Themes** | All three | System detection + selectable + PPL brand |
| **Drag-Drop** | livewire-sortable | Official Livewire npm package |
| **Charts** | ApexCharts | Via Alpine.js integration |
| **Timezone** | America/New_York | Eastern Time for scheduler |
| **Split Assessments** | Parent draggable | Children follow, aggregate on parent |
| **Environment** | Laravel Sail | Containers available but optional |

> **Key Architecture Decision:** This is a management dashboard, NOT a planner tool. We store only aggregated data from planned units (totals, counts) rather than individual unit records. See `FinalDraft/project-context.md` for full rationale.

---

## Brand Colors & Theme System

### PPL Electric Utilities Colors

| Color Name | Hex | RGB | Pantone | Usage |
|------------|-----|-----|---------|-------|
| **Cyan Cornflower Blue** | `#1882C5` | 24, 130, 197 | Process Blue | Primary actions, links |
| **St. Patrick's Blue** | `#28317E` | 40, 49, 126 | Reflex Blue | Headers, emphasis |

**Sources:**
- [PPL Brand Standards 2021](https://www.pplweb.com/wp-content/uploads/2021/08/PPL-Corp_External-Brand-Guidelines_2021.pdf)
- [PPL Logo Colors](https://www.schemecolor.com/ppl-logo-colors.php)

### Asplundh Colors (Contractor Branding)

| Color Name | Hex | RGB | Usage |
|------------|-----|-----|-------|
| **Orient Blue** | `#00598D` | 0, 89, 141 | Secondary elements |
| **Red Damask** | `#E27434` | 226, 116, 52 | Accent, warnings |
| **White** | `#FFFFFF` | 255, 255, 255 | Backgrounds |

**Source:** [Asplundh Brand - Brandfetch](https://brandfetch.com/asplundh.com)

### Theme Configuration

#### Custom PPL Brand Theme (CSS)

```css
/* resources/css/themes/ppl-brand.css */
[data-theme="ppl-brand"] {
  color-scheme: light;

  /* Base colors */
  --color-base-100: oklch(100% 0 0);
  --color-base-200: oklch(98% 0.005 250);
  --color-base-300: oklch(95% 0.01 250);
  --color-base-content: oklch(25% 0.02 250);

  /* PPL Primary - Cyan Cornflower Blue #1882C5 */
  --color-primary: oklch(55% 0.15 230);
  --color-primary-content: oklch(98% 0.01 230);

  /* PPL Secondary - St. Patrick's Blue #28317E */
  --color-secondary: oklch(35% 0.15 270);
  --color-secondary-content: oklch(95% 0.02 270);

  /* Asplundh Accent - Red Damask #E27434 */
  --color-accent: oklch(65% 0.18 50);
  --color-accent-content: oklch(20% 0.05 50);

  /* Neutral - Asplundh Orient Blue #00598D */
  --color-neutral: oklch(40% 0.12 240);
  --color-neutral-content: oklch(95% 0.01 240);

  /* Semantic colors */
  --color-info: oklch(65% 0.15 230);
  --color-info-content: oklch(25% 0.05 230);
  --color-success: oklch(70% 0.17 145);
  --color-success-content: oklch(25% 0.05 145);
  --color-warning: oklch(80% 0.16 85);
  --color-warning-content: oklch(30% 0.08 85);
  --color-error: oklch(65% 0.2 25);
  --color-error-content: oklch(95% 0.02 25);

  /* Radii and sizing */
  --radius-selector: 0.375rem;
  --radius-field: 0.25rem;
  --radius-box: 0.5rem;
  --border: 1px;
  --depth: 1;
  --noise: 0;
}
```

#### Theme Switcher Alpine Component

```javascript
// resources/js/theme-switcher.js
document.addEventListener('alpine:init', () => {
  Alpine.data('themeSwitcher', () => ({
    theme: localStorage.getItem('theme') || 'system',
    themes: ['light', 'dark', 'ppl-brand', 'corporate', 'business', 'dim'],

    init() {
      this.applyTheme();
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (this.theme === 'system') this.applyTheme();
      });
    },

    setTheme(newTheme) {
      this.theme = newTheme;
      localStorage.setItem('theme', newTheme);
      this.applyTheme();
      // Optionally sync to server
      if (typeof Livewire !== 'undefined') {
        Livewire.dispatch('theme-changed', { theme: newTheme });
      }
    },

    applyTheme() {
      const html = document.documentElement;
      if (this.theme === 'system') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        html.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
      } else {
        html.setAttribute('data-theme', this.theme);
      }
    },

    get currentTheme() {
      return this.theme === 'system'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : this.theme;
    }
  }));
});
```

---

## Package Installation

### Composer Packages

```bash
# Core packages
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog

# Optional but recommended
composer require spatie/laravel-responsecache
composer require spatie/laravel-health

# Publish configs and migrations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

### NPM Packages

```bash
# Drag and drop
npm install livewire-sortable

# Charts
npm install apexcharts

# Import in resources/js/app.js
# import 'livewire-sortable';
# import ApexCharts from 'apexcharts';
# window.ApexCharts = ApexCharts;
```

### PostgreSQL Setup

```bash
# .env configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ws_tracker
DB_USERNAME=ws_tracker
DB_PASSWORD=your_secure_password

# Create database
sudo -u postgres createuser ws_tracker
sudo -u postgres createdb ws_tracker -O ws_tracker
sudo -u postgres psql -c "ALTER USER ws_tracker WITH PASSWORD 'your_secure_password';"
```

---

## Database Schema

### Enums (PostgreSQL Native)

```sql
-- Run via migration or raw SQL
CREATE TYPE workflow_stage AS ENUM ('active', 'pending_permissions', 'qc', 'rework', 'closed');
CREATE TYPE assignment_source AS ENUM ('api_sync', 'manual');
CREATE TYPE snapshot_type AS ENUM ('daily', 'status_change', 'manual');
CREATE TYPE sync_type AS ENUM ('circuit_list', 'aggregates', 'full');
CREATE TYPE sync_status AS ENUM ('started', 'completed', 'failed', 'warning');
CREATE TYPE sync_trigger AS ENUM ('scheduled', 'manual', 'workflow_event');
```

### Unit Type Categories (Reference Data)

Unit categories and measurement types are stored in the `unit_types` table (not as PostgreSQL enums) for flexibility:

| Category | Code | Measurement | Description |
|----------|------|-------------|-------------|
| Line Trimming | VLG | linear_ft | Vegetation Line/Length |
| Brush/Herbicide | VAR | acres | Variable Area |
| Tree Removal | VCT | tree_count | Vegetation Count/Tree (DBH sizing) |
| No Work | VNW | none | Flags only |
| Sensitive | VSA | none | Customer flags |

See `app/Models/UnitType.php` for constants and cached lookup methods.

### Core Tables Summary

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `regions` | PPL regions | id, name, code |
| `unit_types` | Work unit types (reference) | code, name, category, measurement_type, dbh_min, dbh_max |
| `permission_statuses` | Permission states | id, name, code, color |
| `api_status_configs` | API filter config | status_code, filter_value, is_sync_enabled |
| `circuits` | Circuit/assessment data | job_guid, work_order, region_id, api_data_json (JSONB) |
| `circuit_ui_states` | UI workflow state | circuit_id, workflow_stage, column_position, is_hidden |
| `circuit_snapshots` | Historical tracking | circuit_id, snapshot_date, miles_planned, snapshot_type |
| `circuit_user` | Planner assignments | circuit_id, user_id, assignment_source |
| `user_ws_credentials` | Encrypted WS creds | user_id, ws_username, ws_password |
| `user_regions` | Region assignments | user_id, region_id |
| `unlinked_planners` | Unmatched API users | api_username, linked_user_id |
| `circuit_aggregates` | **Daily circuit totals** | circuit_id, aggregate_date, total_units, total_linear_ft, total_acres, units_approved/refused/pending, unit_counts_by_type (JSONB) |
| `planner_daily_aggregates` | **Planner productivity** | user_id, region_id, aggregate_date, total_units_assessed, circuits_worked |
| `regional_daily_aggregates` | **Regional rollups** | region_id, aggregate_date, total_circuits, total_planners, permission_counts (JSONB) |
| `sync_logs` | Sync audit trail | sync_type, status, circuits_processed |

> **Note:** Individual planned unit records are NOT stored. Only aggregates are computed during API sync and stored for dashboard queries. See `FinalDraft/project-context.md` for architecture rationale.

### Users Table Extensions

Add to existing users table:

```php
// Migration: add_ws_columns_to_users_table
$table->string('api_username', 100)->nullable()->unique();
$table->timestamp('ws_credentials_verified_at')->nullable();
$table->timestamp('ws_credentials_last_used_at')->nullable();
$table->timestamp('ws_credentials_failed_at')->nullable();
$table->integer('ws_credentials_fail_count')->default(0);
$table->boolean('requires_credential_setup')->default(true);
```

### Key Indexes (PostgreSQL Optimized)

```sql
-- JSONB indexes for API data queries
CREATE INDEX idx_circuits_api_data ON circuits USING gin (api_data_json jsonb_path_ops);
CREATE INDEX idx_planned_units_api_data ON planned_units USING gin (api_data_json jsonb_path_ops);

-- Composite indexes for common queries
CREATE INDEX idx_circuits_region_status ON circuits (region_id, api_status);
CREATE INDEX idx_circuits_active ON circuits (region_id, api_modified_date)
  WHERE api_status = 'ACTIV' AND deleted_at IS NULL;
CREATE INDEX idx_ui_states_workflow ON circuit_ui_states (workflow_stage, column_position);
CREATE INDEX idx_snapshots_circuit_date ON circuit_snapshots (circuit_id, snapshot_date DESC);
```

---

## Migration Order

### Phase 1: Foundation (No Dependencies)

```
2026_01_10_000001_create_regions_table.php
2026_01_10_000002_create_unit_types_table.php
2026_01_10_000003_create_permission_statuses_table.php
2026_01_10_000004_create_api_status_configs_table.php
```

### Phase 2: Spatie Permissions

```
2026_01_10_000005_create_permission_tables.php (vendor publish)
```

### Phase 3: User Extensions

```
2026_01_10_000006_add_ws_columns_to_users_table.php
2026_01_10_000007_create_user_ws_credentials_table.php
2026_01_10_000008_create_user_regions_table.php
2026_01_10_000009_create_unlinked_planners_table.php
```

### Phase 4: Core Domain

```
2026_01_10_000010_create_circuits_table.php
2026_01_10_000011_create_circuit_ui_states_table.php
2026_01_10_000012_create_circuit_user_table.php
2026_01_10_000013_create_circuit_snapshots_table.php
```

### Phase 5: Aggregate Tables (Replaces Planned Units)

> **Architecture Change:** Individual planned unit records are NOT stored. Only aggregates are computed during API sync.

```
2026_01_10_000014_create_circuit_aggregates_table.php
2026_01_10_000015_create_planner_daily_aggregates_table.php
2026_01_10_000016_create_regional_daily_aggregates_table.php
```

#### circuit_aggregates Schema
```sql
CREATE TABLE circuit_aggregates (
    id BIGSERIAL PRIMARY KEY,
    circuit_id BIGINT NOT NULL REFERENCES circuits(id) ON DELETE CASCADE,
    aggregate_date DATE NOT NULL,
    is_rollup BOOLEAN DEFAULT FALSE,

    -- Normalized metrics (fast filtering)
    total_units INTEGER DEFAULT 0,
    total_linear_ft DECIMAL(12,2) DEFAULT 0,
    total_acres DECIMAL(12,4) DEFAULT 0,
    total_trees INTEGER DEFAULT 0,

    -- Permission counts
    units_approved INTEGER DEFAULT 0,
    units_refused INTEGER DEFAULT 0,
    units_pending INTEGER DEFAULT 0,

    -- JSONB for flexible breakdowns
    unit_counts_by_type JSONB,      -- {"SPM": 10, "HCB": 5}
    linear_ft_by_type JSONB,
    acres_by_type JSONB,
    planner_distribution JSONB,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE (circuit_id, aggregate_date, is_rollup)
);
```

#### planner_daily_aggregates Schema
```sql
CREATE TABLE planner_daily_aggregates (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id),
    region_id BIGINT NOT NULL REFERENCES regions(id),
    aggregate_date DATE NOT NULL,

    circuits_worked INTEGER DEFAULT 0,
    total_units_assessed INTEGER DEFAULT 0,
    total_linear_ft DECIMAL(12,2) DEFAULT 0,
    total_acres DECIMAL(12,4) DEFAULT 0,

    units_approved INTEGER DEFAULT 0,
    units_refused INTEGER DEFAULT 0,
    units_pending INTEGER DEFAULT 0,

    unit_counts_by_type JSONB,
    circuits_list JSONB,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE (user_id, region_id, aggregate_date)
);
```

### Phase 6: Sync Management

```
2026_01_10_000017_create_sync_logs_table.php
```

### Phase 7: Seeders

```php
// database/seeders/DatabaseSeeder.php
$this->call([
    RolesAndPermissionsSeeder::class,
    RegionsSeeder::class,
    ApiStatusConfigsSeeder::class,
    UnitTypesSeeder::class,
    PermissionStatusesSeeder::class,
]);
```

---

## API Service Layer

### Directory Structure

```
app/
├── Services/
│   └── WorkStudio/
│       ├── WorkStudioApiService.php      # HTTP client, auth, retry
│       ├── ApiCredentialManager.php      # Credential rotation
│       ├── Contracts/
│       │   └── WorkStudioApiInterface.php
│       ├── Aggregation/                  # NEW: Aggregate-only processing
│       │   ├── AggregateCalculationService.php   # Compute aggregates from raw API
│       │   ├── AggregateStorageService.php       # Persist to database
│       │   ├── AggregateDiffService.php          # Compare old vs new
│       │   └── AggregateQueryService.php         # Query interface for hierarchy
│       └── Transformers/
│           ├── DDOTableTransformer.php   # Generic DDOTable → Collection
│           ├── CircuitTransformer.php    # Circuit field mapping
│           └── PlannedUnitAggregateTransformer.php  # Returns aggregates, NOT records
```

### Aggregation Service Layer

The aggregation services compute totals from raw API data during sync:

```php
// app/Services/WorkStudio/Aggregation/AggregateCalculationService.php

class AggregateCalculationService
{
    public function __construct(
        private UnitType $unitTypes
    ) {}

    public function computeFromApiResponse(Collection $rawUnits): array
    {
        $linearFtCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_LINEAR_FT);
        $acresCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_ACRES);
        $treeCountCodes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_TREE_COUNT);

        return [
            'total_units' => $rawUnits->count(),
            'total_linear_ft' => $rawUnits
                ->whereIn('VEGUNIT_UNIT', $linearFtCodes)
                ->sum('JOBVEGETATIONUNITS_LENGTHWRK'),
            'total_acres' => $rawUnits
                ->whereIn('VEGUNIT_UNIT', $acresCodes)
                ->sum('JOBVEGETATIONUNITS_ACRES'),
            'total_trees' => $rawUnits
                ->whereIn('VEGUNIT_UNIT', $treeCountCodes)
                ->sum('JOBVEGETATIONUNITS_NUMTREES'),
            'units_approved' => $rawUnits->where('VEGUNIT_PERMSTAT', 'Approved')->count(),
            'units_refused' => $rawUnits->where('VEGUNIT_PERMSTAT', 'Refused')->count(),
            'units_pending' => $rawUnits->where('VEGUNIT_PERMSTAT', 'Pending')->count(),
            'unit_counts_by_type' => $rawUnits->countBy('VEGUNIT_UNIT')->toArray(),
            'planner_distribution' => $rawUnits->countBy('VEGUNIT_FORESTER')->toArray(),
        ];
    }
}
```

### WorkStudioApiService

```php
<?php

namespace App\Services\WorkStudio;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class WorkStudioApiService
{
    private const BASE_URL = 'https://ppl02.geodigital.com:8372/ddoprotocol/';
    private const TIMEOUT = 60;
    private const MAX_RETRIES = 5;

    public function __construct(
        private ApiCredentialManager $credentialManager
    ) {}

    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(10)
                ->get('https://ppl02.geodigital.com:8372');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getViewData(string $viewGuid, array $filter, ?int $userId = null): array
    {
        $credentials = $this->credentialManager->getCredentials($userId);

        return $this->executeWithRetry(function () use ($viewGuid, $filter, $credentials) {
            return Http::timeout(self::TIMEOUT)
                ->withBasicAuth($credentials['username'], $credentials['password'])
                ->post(self::BASE_URL, [
                    'protocol' => 'GETVIEWDATA',
                    'ViewDefinitionGuid' => $viewGuid,
                    'ViewFilter' => $filter,
                    'ResultFormat' => 'DDOTable',
                ]);
        }, $credentials['user_id']);
    }

    private function executeWithRetry(callable $request, ?int $userId): array
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < self::MAX_RETRIES) {
            try {
                $response = $request();

                if ($response->status() === 401) {
                    $this->credentialManager->markFailed($userId);
                    throw new \Exception('Authentication failed');
                }

                $this->credentialManager->markSuccess($userId);
                return $response->json();

            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;
                sleep(pow(2, $attempts)); // Exponential backoff
            }
        }

        throw $lastException;
    }
}
```

### View GUIDs

```php
// config/workstudio.php
return [
    'base_url' => env('WORKSTUDIO_BASE_URL', 'https://ppl02.geodigital.com:8372/ddoprotocol/'),
    'timeout' => env('WORKSTUDIO_TIMEOUT', 60),

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
];
```

---

## Sync Strategy

### Scheduled Jobs

```php
// routes/console.php or app/Console/Kernel.php

use App\Jobs\SyncCircuitsJob;

// ACTIV - twice daily on weekdays (Eastern Time)
Schedule::job(new SyncCircuitsJob(['ACTIV']))
    ->weekdays()
    ->timezone('America/New_York')
    ->at('04:30');

Schedule::job(new SyncCircuitsJob(['ACTIV']))
    ->weekdays()
    ->timezone('America/New_York')
    ->at('16:30');

// QC, REWORK, CLOSE - weekly on Monday
Schedule::job(new SyncCircuitsJob(['QC', 'REWRK', 'CLOSE']))
    ->weekdays()
    ->mondays()
    ->timezone('America/New_York')
    ->at('04:30');
```

### SyncCircuitsJob

```php
<?php

namespace App\Jobs;

use App\Models\Circuit;
use App\Models\SyncLog;
use App\Services\WorkStudio\WorkStudioApiService;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCircuitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private array $statuses,
        private ?int $triggeredByUserId = null,
        private string $triggerType = 'scheduled'
    ) {}

    public function handle(
        WorkStudioApiService $api,
        CircuitTransformer $transformer
    ): void {
        $syncLog = SyncLog::create([
            'sync_type' => 'circuit_list',
            'status' => 'started',
            'triggered_by' => $this->triggerType,
            'triggered_by_user_id' => $this->triggeredByUserId,
            'started_at' => now(),
        ]);

        try {
            if (!$api->healthCheck()) {
                throw new \Exception('WorkStudio API is unavailable');
            }

            $processedCount = 0;

            foreach ($this->statuses as $status) {
                $response = $api->getViewData(
                    config('workstudio.views.vegetation_assessments'),
                    [
                        'FilterName' => 'By Job Status',
                        'FilterValue' => $status,
                        'PersistFilter' => true,
                    ]
                );

                $circuits = $transformer->transformCollection($response);

                foreach ($circuits as $circuitData) {
                    $this->upsertCircuit($circuitData);
                    $processedCount++;

                    // Rate limiting: pause every 5 circuits
                    if ($processedCount % 5 === 0) {
                        usleep(500000); // 0.5 second delay
                    }
                }
            }

            // Create daily snapshots for non-closed circuits
            $this->createDailySnapshots();

            $syncLog->update([
                'status' => 'completed',
                'circuits_processed' => $processedCount,
                'completed_at' => now(),
            ]);

            // Broadcast sync complete event
            event(new \App\Events\SyncCompletedEvent());

        } catch (\Exception $e) {
            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    private function upsertCircuit(array $data): void
    {
        Circuit::updateOrCreate(
            ['job_guid' => $data['job_guid']],
            $data
        );
    }

    private function createDailySnapshots(): void
    {
        Circuit::whereNotIn('api_status', ['CLOSE'])
            ->whereNull('deleted_at')
            ->each(function (Circuit $circuit) {
                $circuit->snapshots()->create([
                    'miles_planned' => $circuit->miles_planned,
                    'percent_complete' => $circuit->percent_complete,
                    'total_acres' => $circuit->total_acres,
                    'api_status' => $circuit->api_status,
                    'workflow_stage' => $circuit->uiState?->workflow_stage ?? 'active',
                    'snapshot_type' => 'daily',
                    'snapshot_date' => today(),
                ]);
            });
    }
}
```

---

## Livewire Components

### Component Hierarchy

```
app/Livewire/
├── Dashboard/
│   ├── CircuitDashboard.php          # Main dashboard container
│   ├── WorkflowBoard.php             # Kanban-style board
│   ├── WorkflowColumn.php            # Single column (Active, QC, etc.)
│   ├── CircuitCard.php               # Draggable circuit card
│   ├── FilterPanel.php               # Region, planner, date filters
│   └── StatsPanel.php                # Summary statistics
├── Charts/
│   ├── MilesByRegionChart.php        # ApexCharts wrapper
│   ├── PlannerProgressChart.php
│   └── PermissionStatusChart.php
├── Admin/
│   ├── SyncControl.php               # Force sync UI
│   ├── UnlinkedPlanners.php          # Link API users to local users
│   └── UserManagement.php
└── Settings/
    └── ThemeSelector.php             # Theme preference
```

### CircuitDashboard Component

```php
<?php

namespace App\Livewire\Dashboard;

use App\Models\Circuit;
use App\Models\Region;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class CircuitDashboard extends Component
{
    public ?int $regionFilter = null;
    public ?string $plannerFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    #[On('circuit-moved')]
    public function handleCircuitMoved(int $circuitId, string $newStage, int $newPosition): void
    {
        $circuit = Circuit::findOrFail($circuitId);

        $this->authorize('change-workflow-stage', $circuit);

        $circuit->uiState->update([
            'workflow_stage' => $newStage,
            'column_position' => $newPosition,
        ]);

        // Create status change snapshot
        $circuit->snapshots()->create([
            'miles_planned' => $circuit->miles_planned,
            'percent_complete' => $circuit->percent_complete,
            'api_status' => $circuit->api_status,
            'workflow_stage' => $newStage,
            'snapshot_type' => 'status_change',
            'snapshot_date' => today(),
        ]);

        $this->dispatch('circuit-updated', circuitId: $circuitId);
    }

    #[Computed]
    public function circuits(): \Illuminate\Support\Collection
    {
        return Circuit::query()
            ->with(['region', 'uiState', 'planners'])
            ->visibleToUser(auth()->user())
            ->when($this->regionFilter, fn($q) => $q->where('region_id', $this->regionFilter))
            ->when($this->plannerFilter, fn($q) => $q->whereHas('planners',
                fn($pq) => $pq->where('users.id', $this->plannerFilter)))
            ->when($this->dateFrom, fn($q) => $q->where('api_modified_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('api_modified_date', '<=', $this->dateTo))
            ->orderBy('api_modified_date', 'desc')
            ->get()
            ->groupBy(fn($c) => $c->uiState?->workflow_stage ?? 'active');
    }

    #[Computed]
    public function regions(): \Illuminate\Support\Collection
    {
        return Region::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.dashboard.circuit-dashboard');
    }
}
```

### WorkflowColumn Blade View

```blade
{{-- resources/views/livewire/dashboard/workflow-column.blade.php --}}
<div class="flex flex-col h-full">
    <div class="flex items-center justify-between p-3 bg-base-200 rounded-t-lg">
        <h3 class="font-semibold text-base-content">
            {{ $title }}
            <span class="badge badge-neutral ml-2">{{ $circuits->count() }}</span>
        </h3>
    </div>

    <div
        class="flex-1 overflow-y-auto p-2 bg-base-100 rounded-b-lg min-h-[400px]"
        wire:sortable="updateOrder"
        wire:sortable-group="{{ $stage }}"
    >
        @forelse($circuits as $circuit)
            <div
                wire:key="circuit-{{ $circuit->id }}"
                wire:sortable.item="{{ $circuit->id }}"
                class="mb-2"
            >
                <livewire:dashboard.circuit-card
                    :circuit="$circuit"
                    :key="'card-'.$circuit->id"
                />
            </div>
        @empty
            <div class="text-center text-base-content/50 py-8">
                No circuits in this stage
            </div>
        @endforelse
    </div>
</div>
```

### CircuitCard Component

```blade
{{-- resources/views/livewire/dashboard/circuit-card.blade.php --}}
<div
    wire:sortable.handle
    class="card bg-base-100 shadow-sm border border-base-300 cursor-move hover:shadow-md transition-shadow"
>
    <div class="card-body p-3">
        {{-- Header --}}
        <div class="flex justify-between items-start">
            <div>
                <h4 class="font-medium text-sm">{{ $circuit->work_order }}{{ $circuit->extension }}</h4>
                <p class="text-xs text-base-content/70 truncate max-w-[200px]">
                    {{ $circuit->title }}
                </p>
            </div>
            <span class="badge badge-sm {{ $this->statusBadgeClass }}">
                {{ $circuit->region->name }}
            </span>
        </div>

        {{-- Progress Bar --}}
        <div class="mt-2">
            <div class="flex justify-between text-xs mb-1">
                <span>Progress</span>
                <span>{{ number_format($circuit->percent_complete, 0) }}%</span>
            </div>
            <progress
                class="progress progress-primary w-full h-2"
                value="{{ $circuit->percent_complete }}"
                max="100"
            ></progress>
        </div>

        {{-- Stats Row --}}
        <div class="flex justify-between text-xs text-base-content/70 mt-2">
            <span>{{ number_format($circuit->miles_planned, 2) }} / {{ number_format($circuit->total_miles, 2) }} mi</span>
            <span>{{ $circuit->api_modified_date->format('m/d') }}</span>
        </div>

        {{-- Planner --}}
        @if($circuit->planners->isNotEmpty())
            <div class="flex items-center gap-1 mt-2">
                <div class="avatar-group -space-x-3">
                    @foreach($circuit->planners->take(3) as $planner)
                        <div class="avatar placeholder">
                            <div class="bg-neutral text-neutral-content rounded-full w-6">
                                <span class="text-xs">{{ substr($planner->name, 0, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Split Assessment Indicator --}}
        @if($circuit->children->isNotEmpty())
            <div class="mt-2">
                <button
                    wire:click="toggleChildren"
                    class="btn btn-xs btn-ghost gap-1"
                >
                    <flux:icon name="chevron-down" class="w-3 h-3" />
                    {{ $circuit->children->count() }} split assessments
                </button>
            </div>
        @endif
    </div>
</div>
```

---

## Implementation Phases

### Phase 0: Environment Setup (Day 1)

- [x] <!--GBeqidW6Qf0ru36FVbXut--> Install PostgreSQL locally
- [x] <!--NiXLoAgb08bNxVoNokH9J--> Configure `.env` for PostgreSQL
- [x] <!--nWdRUpqf1dswp50JZU9Wv--> Install composer packages (spatie/laravel-permission, spatie/laravel-activitylog)
- [x] <!--Qnng9qyYsh05c-E_KY3_3--> Install npm packages (livewire-sortable, apexcharts)
- [x] <!--7xwTnhUke8d7L3JJmOdR3--> Set timezone to `America/New_York` in `config/app.php`
- [x] <!--tU6RAPwymp31GcHK7eDVW--> Create `config/workstudio.php`
### Phase 1A: Database Foundation (Days 2-3) ✅ COMPLETE
- [x] <!--rRxRdVgBnkrMNwqVwinE6--> Create all migrations in order
- [x] <!--QTGoojU6eYrJqpRPEgebW--> Create Eloquent models with relationships
- [x] <!--p7KaOL62E0fJsbiudFhSK--> Create factories for testing
- [x] <!--AncrwZz_H3v_XlMhAEHd_--> Create seeders (roles, permissions, regions, statuses)
- [x] <!--rZ1ekjG4-lefSR1JxjyhP--> Run migrations and seeders
- [x] <!--lxkY1RthkSsquTo-veRAP--> Add `HasRoles` trait to User model

### Phase 1B: API Service Layer (Days 4-5) ✅ COMPLETE
- [x] <!--n0d9vsdSC0U7DngAJFdij--> Create `WorkStudioApiService` with health check and retry logic
- [x] <!--e106u2DQeKs1HzMWoa-i8--> Create `ApiCredentialManager` for credential rotation
- [x] <!--GdniQILdZCYQAAbDRGj6o--> Create `DDOTableTransformer` for generic response parsing
- [x] <!--o6umuaXVGrppqD9mCLXX---> Create `CircuitTransformer` with field mappings
- [x] <!--a95DV8RZZyS370MKpb8N9--> Create `PlannedUnitAggregateTransformer`
- [x] <!--lD8kDzYZUOLSYIwsAwSMC--> Write Pest tests for transformers
### Phase 1C: Sync Jobs (Days 6-7)
- [ ] <!--np2tDpwuyhaXirq8hkfuZ--> Create `SyncCircuitsJob`
- [ ] <!--hhRR26qyv2PknZU31b4Jy--> Create `SyncPlannedUnitsJob`
- [ ] <!--H1R2aAMnf6YWrQSr-V7MJ--> Implement daily snapshot creation
- [ ] <!--_sZIyRA9iqHf3sKvZfVhB--> Configure scheduled jobs in `routes/console.php`
- [x] <!--dIqDVVcNWL2SP1Jstpecm--> Create `SyncLog` tracking (model exists)
- [ ] <!--xgP65faPw0RqnCS2xaG5b--> Write Pest tests for sync jobs

### Phase 1D: Theme System (Day 8)
- [ ] <!--jkpBFJstV9CvVzZ9yUNEP--> Create `ppl-brand` theme CSS
- [ ] <!--QtF3L9fnn_6G_zHV5_gqr--> Create theme switcher Alpine component
- [ ] <!--cx24NXG7fJv4tQfRniONl--> Add theme selector to user settings
- [ ] <!--XMn-cMlTvHrUvr_KgEpG4--> Implement system preference detection
- [ ] <!--F2DcFhaaw8-dvsXey8GNP--> Test dark mode compatibility

### Phase 1E: Dashboard UI (Days 9-12)
- [ ] <!--QZNmmFo3PLkgQjzoXMMRh--> Create `CircuitDashboard` component
- [ ] <!--N9gox48lGx8nZ8ME8rtaE--> Create `WorkflowColumn` component
- [ ] <!--d1j5i7pTsfbcpaMlkuf13--> Create `CircuitCard` component with drag handle
- [ ] <!--nH_nVhzTqECDjNZvLfTiH--> Implement drag-drop with livewire-sortable
- [ ] <!--jetp3al8LMpTffv3rXGSp--> Create `FilterPanel` component
- [ ] <!--bXI1oyf2lcBHYd7X-0Qq0--> Create `StatsPanel` component
- [ ] <!--yy6KXXbImkpa8LkimS6NO--> Implement role-based visibility
- [ ] <!--8Es1gEhmcnvtv9h9t6tbW--> Write Pest Livewire tests

### Phase 1F: Charts & Visualization (Days 13-14)
- [ ] <!--UlRHTX0jIL6YOF_uF6pIx--> Create ApexCharts Alpine wrapper
- [ ] <!--Ndg95il4NSEp-b8QijL6U--> Create `MilesByRegionChart` component
- [ ] <!--pvY4_z-N1aZvzwAD18M41--> Create `PlannerProgressChart` component
- [ ] <!--teVikk4GvT7cMSlwc1GN4--> Create `PermissionStatusChart` component
- [ ] <!--RorU_yyx3-U8ZXyp4_7w---> Implement Saturday aggregate calculations

### Phase 1G: Admin Features (Day 15)
- [ ] <!--UAEiDP3pfKLwA7CHe8nXM--> Create force sync UI
- [ ] <!--Bf8ac8UbbRz1qhmQQBQoJ--> Create unlinked planners management
- [ ] <!--8OSd7tTDcjtugG3pXVRmL--> Add sync history viewer
- [ ] <!--Oq4Tt6auOAtFZmgqpB206--> Implement permission gates

### Phase 1H: Testing & Polish (Days 16-17) - Partial
- [x] <!--iriweTrnQtSqi-3MYRSOl--> Core Pest test coverage (138 tests passing)
- [x] <!--VEBuQkdtmyQ64WCsnfVHV--> Run `vendor/bin/pint` for code style
- [ ] <!--xXRPoLUzR7EfvmAgYG07E--> Test all role-based access
- [ ] <!--Mlch7VlpupJDy71AuHdQ8--> Performance testing with sample data
- [ ] <!--M7coASNRXguNmHa6YX-w---> Browser testing with Pest 4
  -
   File Structure
  `
  p/
  ─ Enums/
    ├── WorkflowStage.php
    ├── AssignmentSource.php
    ├── SnapshotType.php
    ├── SyncType.php
    ├── SyncStatus.php
    └── SizeUnit.php
  ─ Events/
    ├── SyncCompletedEvent.php
    ├── PlannedUnitsSyncedEvent.php
    └── CircuitWorkflowChangedEvent.php
  ─ Jobs/
    ├── SyncCircuitsJob.php
    └── SyncPlannedUnitsJob.php
  ─ Livewire/
    ├── Dashboard/
    │   ├── CircuitDashboard.php
    │   ├── WorkflowBoard.php
    │   ├── WorkflowColumn.php
    │   ├── CircuitCard.php
    │   ├── FilterPanel.php
    │   └── StatsPanel.php
    ├── Charts/
    │   ├── MilesByRegionChart.php
    │   ├── PlannerProgressChart.php
    │   └── PermissionStatusChart.php
    ├── Admin/
    │   ├── SyncControl.php
    │   └── UnlinkedPlanners.php
    └── Settings/
        └── ThemeSelector.php
  ─ Models/
    ├── Circuit.php
    ├── CircuitUiState.php
    ├── CircuitSnapshot.php
    ├── CircuitAggregate.php          # Daily circuit totals (replaces PlannedUnit)
    ├── PlannerDailyAggregate.php     # Planner productivity tracking
    ├── RegionalDailyAggregate.php    # Regional rollups
    ├── Region.php
    ├── UnitType.php                  # Reference table with cached lookups
    ├── PermissionStatus.php
    ├── ApiStatusConfig.php
    ├── UnlinkedPlanner.php
    ├── UserWsCredential.php
    └── SyncLog.php
  ─ Policies/
    └── CircuitPolicy.php
  ─ Services/
    └── WorkStudio/
        ├── WorkStudioApiService.php
        ├── ApiCredentialManager.php
        ├── Contracts/
        │   └── WorkStudioApiInterface.php
        ├── Aggregation/                      # NEW: Aggregate processing
        │   ├── AggregateCalculationService.php
        │   ├── AggregateStorageService.php
        │   ├── AggregateDiffService.php
        │   └── AggregateQueryService.php
        └── Transformers/
            ├── DDOTableTransformer.php
            ├── CircuitTransformer.php
            └── PlannedUnitAggregateTransformer.php
  ─ Support/
    └── Helpers/
        └── DateParser.php
  nfig/
  ─ workstudio.php
  tabase/
  ─ factories/
    ├── CircuitFactory.php
    ├── CircuitAggregateFactory.php
    ├── PlannerDailyAggregateFactory.php
    └── RegionFactory.php
  ─ migrations/
    └── (17 migration files)
  ─ seeders/
    ├── DatabaseSeeder.php
    ├── RolesAndPermissionsSeeder.php
    ├── RegionsSeeder.php
    ├── ApiStatusConfigsSeeder.php
    ├── UnitTypesSeeder.php
    └── PermissionStatusesSeeder.php
  sources/
  ─ css/
    └── themes/
        └── ppl-brand.css
  ─ js/
    ├── app.js
    └── theme-switcher.js
  ─ views/
    └── livewire/
        ├── dashboard/
        │   ├── circuit-dashboard.blade.php
        │   ├── workflow-board.blade.php
        │   ├── workflow-column.blade.php
        │   ├── circuit-card.blade.php
        │   ├── filter-panel.blade.php
        │   └── stats-panel.blade.php
        ├── charts/
        │   └── apex-chart.blade.php
        └── admin/
            ├── sync-control.blade.php
            └── unlinked-planners.blade.php
  sts/
  ─ Feature/
    ├── Dashboard/
    │   ├── CircuitDashboardTest.php
    │   └── WorkflowDragDropTest.php
    ├── Sync/
    │   ├── SyncCircuitsJobTest.php
    │   └── SyncAggregatesJobTest.php
    └── Auth/
        └── RoleAccessTest.php
  ─ Unit/
    ├── Transformers/
    │   ├── CircuitTransformerTest.php
    │   └── PlannedUnitAggregateTransformerTest.php
    ├── Services/
    │   └── ApiCredentialManagerTest.php
    ├── Aggregation/
    │   ├── AggregateCalculationServiceTest.php
    │   └── AggregateQueryServiceTest.php
    └── Models/
        └── UnitTypeTest.php
  `
  -
   Testing Strategy
  # Key Test Categories
   **Unit Tests** - Transformers, helpers, date parsing
   **Feature Tests** - Livewire components, authorization, sync jobs
   **Browser Tests** (Pest 4) - Drag-drop, theme switching, charts
  # Example Tests
  `php
   tests/Feature/Dashboard/CircuitDashboardTest.php
  php
  e App\Livewire\Dashboard\CircuitDashboard;
  e App\Models\Circuit;
  e App\Models\User;
  e Livewire\Livewire;
  ('shows circuits for admin users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $circuit = Circuit::factory()->create();
    Livewire::actingAs($admin)
        ->test(CircuitDashboard::class)
        ->assertSee($circuit->work_order);
  ;
  ('filters circuits by region', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $circuit1 = Circuit::factory()->forRegion('Central')->create();
    $circuit2 = Circuit::factory()->forRegion('Lancaster')->create();
    Livewire::actingAs($admin)
        ->test(CircuitDashboard::class)
        ->set('regionFilter', $circuit1->region_id)
        ->assertSee($circuit1->work_order)
        ->assertDontSee($circuit2->work_order);
  ;
  ('allows workflow stage changes for authorized users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $circuit = Circuit::factory()->create();
    Livewire::actingAs($admin)
        ->test(CircuitDashboard::class)
        ->call('handleCircuitMoved', $circuit->id, 'qc', 1)
        ->assertDispatched('circuit-updated');
    expect($circuit->fresh()->uiState->workflow_stage)->toBe('qc');
  ;
  `
  -
   Next Steps
  ter reviewing this plan:
   **Approve or request changes** to any section
   I can then **generate the actual migration files and models**
   We'll implement phase by phase with tests
  uld you like me to proceed with generating the migration files and models, or do you want to adjust any part of this plan first?