# Phase 1H: Testing & Polish

> **Goal:** Complete test coverage, code formatting, and final polish.
> **Estimated Time:** 2 days
> **Dependencies:** All previous phases complete

---

## Status: Not Started

| Item | Status | Notes |
|------|--------|-------|
| Unit tests | Needed | Models, services, helpers |
| Feature tests | Needed | Livewire components, jobs |
| Browser tests | Needed | Pest 4 browser testing |
| Code formatting | Needed | Laravel Pint |
| Performance audit | Needed | N+1 queries, indexes |

---

## Testing Target

**Goal:** 90%+ code coverage with comprehensive tests for all critical paths.

---

## Checklist

### Unit Tests
- [ ] UnitType model tests
- [ ] Circuit model tests
- [ ] Region model tests
- [ ] DateParser helper tests
- [ ] DDOTableTransformer tests
- [ ] CircuitTransformer tests
- [ ] PlannedUnitAggregateTransformer tests
- [ ] AggregateCalculationService tests
- [ ] ApiCredentialManager tests

### Feature Tests
- [ ] Authentication flow tests
- [ ] Dashboard component tests
- [ ] Filter panel tests
- [ ] Workflow drag-drop tests
- [ ] Chart component tests
- [ ] Admin sync control tests
- [ ] Unlinked planners tests
- [ ] User management tests (sudo admin)
- [ ] SyncCircuitsJob tests
- [ ] SyncCircuitAggregatesJob tests
- [ ] CreateDailySnapshotsJob tests

### Browser Tests (Pest 4)
- [ ] Login flow
- [ ] Dashboard filter/sort
- [ ] Drag-drop workflow
- [ ] Theme switching
- [ ] Admin sync control
- [ ] Chart rendering

### Code Quality
- [ ] Run `vendor/bin/pint --dirty`
- [ ] Run `vendor/bin/pint` (full)
- [ ] Check for N+1 queries
- [ ] Add missing database indexes
- [ ] Review error handling

### Documentation
- [ ] Update CLAUDE.md if needed
- [ ] Add inline documentation where complex
- [ ] Verify all tests pass

---

## Test File Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── RoleAccessTest.php
│   ├── Dashboard/
│   │   ├── CircuitDashboardTest.php
│   │   ├── FilterPanelTest.php
│   │   └── WorkflowDragDropTest.php
│   ├── Charts/
│   │   ├── MilesByRegionChartTest.php
│   │   ├── PermissionStatusChartTest.php
│   │   └── PlannerProgressChartTest.php
│   ├── Admin/
│   │   ├── SyncControlTest.php
│   │   ├── SyncHistoryTest.php
│   │   ├── UnlinkedPlannersTest.php
│   │   └── AccessControlTest.php
│   ├── Sync/
│   │   ├── SyncCircuitsJobTest.php
│   │   ├── SyncCircuitAggregatesJobTest.php
│   │   └── CreateDailySnapshotsJobTest.php
│   └── Database/
│       └── MigrationTest.php
├── Unit/
│   ├── Models/
│   │   ├── UnitTypeTest.php
│   │   ├── CircuitTest.php
│   │   └── RegionTest.php
│   ├── Transformers/
│   │   ├── DDOTableTransformerTest.php
│   │   ├── CircuitTransformerTest.php
│   │   └── PlannedUnitAggregateTransformerTest.php
│   ├── Services/
│   │   ├── ApiCredentialManagerTest.php
│   │   └── AggregateCalculationServiceTest.php
│   └── Helpers/
│       └── DateParserTest.php
└── Browser/
    ├── DashboardFlowTest.php
    ├── ThemeSwitchingTest.php
    ├── DragDropTest.php
    └── AdminPanelTest.php
```

---

## Example Tests

### Unit Test: UnitType Model

```php
// tests/Unit/Models/UnitTypeTest.php
<?php

use App\Models\UnitType;

describe('UnitType Model', function () {
    it('returns codes for a given category', function () {
        UnitType::factory()->create(['code' => 'SPM', 'category' => UnitType::CATEGORY_VLG]);
        UnitType::factory()->create(['code' => 'HCB', 'category' => UnitType::CATEGORY_VAR]);

        $vlgCodes = UnitType::codesForCategory(UnitType::CATEGORY_VLG);

        expect($vlgCodes)->toContain('SPM');
        expect($vlgCodes)->not->toContain('HCB');
    });

    it('returns codes for a given measurement type', function () {
        UnitType::factory()->create([
            'code' => 'SPM',
            'measurement_type' => UnitType::MEASUREMENT_LINEAR_FT,
        ]);

        $codes = UnitType::codesForMeasurement(UnitType::MEASUREMENT_LINEAR_FT);

        expect($codes)->toContain('SPM');
    });

    it('caches unit types by code', function () {
        $unitType = UnitType::factory()->create(['code' => 'SPM']);

        $cached = UnitType::findByCode('SPM');

        expect($cached->id)->toBe($unitType->id);
    });

    it('returns human-readable category name', function () {
        $unitType = UnitType::factory()->create(['category' => UnitType::CATEGORY_VLG]);

        expect($unitType->category_name)->toBe('Line Trimming');
    });
});
```

### Feature Test: Workflow Drag-Drop

```php
// tests/Feature/Dashboard/WorkflowDragDropTest.php
<?php

use App\Models\User;
use App\Models\Circuit;
use App\Models\CircuitUiState;
use App\Livewire\Dashboard\CircuitDashboard;
use App\Enums\WorkflowStage;
use Livewire\Livewire;

describe('Workflow Drag-Drop', function () {
    it('moves circuit to new stage', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $circuit = Circuit::factory()
            ->has(CircuitUiState::factory()->state([
                'workflow_stage' => WorkflowStage::Active,
            ]))
            ->create();

        Livewire::actingAs($admin)
            ->test(CircuitDashboard::class)
            ->call('handleCircuitMoved', $circuit->id, 'qc', 1)
            ->assertDispatched('circuit-updated');

        expect($circuit->fresh()->uiState->workflow_stage)->toBe(WorkflowStage::Qc);
    });

    it('creates snapshot on stage change', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $circuit = Circuit::factory()
            ->has(CircuitUiState::factory())
            ->create();

        Livewire::actingAs($admin)
            ->test(CircuitDashboard::class)
            ->call('handleCircuitMoved', $circuit->id, 'pending_permissions', 0);

        expect($circuit->snapshots)
            ->toHaveCount(1)
            ->first()->snapshot_type->toBe(\App\Enums\SnapshotType::StatusChange);
    });

    it('denies stage change for planners', function () {
        $planner = User::factory()->create();
        $planner->assignRole('planner');

        $circuit = Circuit::factory()
            ->has(CircuitUiState::factory())
            ->create();

        Livewire::actingAs($planner)
            ->test(CircuitDashboard::class)
            ->call('handleCircuitMoved', $circuit->id, 'qc', 1)
            ->assertForbidden();
    });
});
```

### Browser Test: Dashboard Flow (Pest 4)

```php
// tests/Browser/DashboardFlowTest.php
<?php

use App\Models\User;
use App\Models\Circuit;
use App\Models\Region;

describe('Dashboard Flow', function () {
    it('loads dashboard and displays circuits', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $circuit = Circuit::factory()->create();

        $page = visit('/dashboard')->actingAs($user);

        $page->assertSee('Circuit Dashboard')
            ->assertSee($circuit->work_order)
            ->assertNoJavascriptErrors();
    });

    it('filters circuits by region', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $central = Region::factory()->create(['name' => 'Central']);
        $lancaster = Region::factory()->create(['name' => 'Lancaster']);

        $circuit1 = Circuit::factory()->create(['region_id' => $central->id]);
        $circuit2 = Circuit::factory()->create(['region_id' => $lancaster->id]);

        $page = visit('/dashboard')->actingAs($user);

        $page->select('regionFilter', $central->id)
            ->waitFor('[wire\\:key="circuit-' . $circuit1->id . '"]')
            ->assertSee($circuit1->work_order)
            ->assertDontSee($circuit2->work_order);
    });

    it('drags circuit between columns', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $circuit = Circuit::factory()
            ->has(CircuitUiState::factory()->state(['workflow_stage' => 'active']))
            ->create();

        $page = visit('/dashboard')->actingAs($user);

        $page->drag(
            '[wire\\:sortable\\.item="' . $circuit->id . '"]',
            '[wire\\:sortable-group="qc"]'
        )
        ->waitFor('.toast-success')
        ->assertNoJavascriptErrors();

        expect($circuit->fresh()->uiState->workflow_stage->value)->toBe('qc');
    });
});
```

### Browser Test: Theme Switching

```php
// tests/Browser/ThemeSwitchingTest.php
<?php

use App\Models\User;

describe('Theme Switching', function () {
    it('switches themes via dropdown', function () {
        $user = User::factory()->create(['theme_preference' => 'light']);

        $page = visit('/dashboard')->actingAs($user);

        $page->assertAttribute('html', 'data-theme', 'light')
            ->click('[data-testid="theme-switcher"]')
            ->click('[data-theme-option="dark"]')
            ->assertAttribute('html', 'data-theme', 'dark')
            ->assertNoJavascriptErrors();
    });

    it('persists theme across page loads', function () {
        $user = User::factory()->create(['theme_preference' => 'ppl-brand']);

        $page = visit('/dashboard')->actingAs($user);

        $page->assertAttribute('html', 'data-theme', 'ppl-brand');

        $page = visit('/settings');

        $page->assertAttribute('html', 'data-theme', 'ppl-brand');
    });

    it('respects system preference when auto', function () {
        $user = User::factory()->create(['theme_preference' => 'system']);

        $page = visit('/dashboard')
            ->actingAs($user)
            ->setColorScheme('dark');

        $page->assertAttribute('html', 'data-theme', 'dark');
    });
});
```

---

## Running Tests

### Run All Tests
```bash
sail artisan test --compact
```

### Run Specific File
```bash
sail artisan test --compact tests/Feature/Dashboard/CircuitDashboardTest.php
```

### Run With Filter
```bash
sail artisan test --compact --filter="moves circuit to new stage"
```

### Run With Coverage
```bash
sail artisan test --coverage --min=90
```

### Run Browser Tests
```bash
sail artisan test --compact tests/Browser/
```

### Run Pint
```bash
# Check only
vendor/bin/pint --test

# Fix all
vendor/bin/pint

# Fix dirty files only
vendor/bin/pint --dirty
```

---

## Performance Checklist

### Database
- [ ] Review all queries in Livewire components
- [ ] Add `->with()` for eager loading where needed
- [ ] Check for N+1 queries using Laravel Debugbar
- [ ] Verify indexes exist for common queries:
  ```sql
  -- circuits
  CREATE INDEX IF NOT EXISTS idx_circuits_region_status ON circuits (region_id, api_status);
  CREATE INDEX IF NOT EXISTS idx_circuits_modified ON circuits (api_modified_date DESC);

  -- circuit_aggregates
  CREATE INDEX IF NOT EXISTS idx_aggregates_circuit_date ON circuit_aggregates (circuit_id, aggregate_date);

  -- circuit_ui_states
  CREATE INDEX IF NOT EXISTS idx_ui_states_workflow ON circuit_ui_states (workflow_stage, column_position);
  ```

### Caching
- [ ] Verify UnitType caching works
- [ ] Consider caching region list
- [ ] Cache chart data with appropriate TTL

### Frontend
- [ ] Verify Vite builds without errors
- [ ] Check bundle size
- [ ] Verify lazy loading where appropriate

---

## Final Checklist

### Before Marking Complete
- [ ] All tests pass: `sail artisan test --compact`
- [ ] Coverage meets target: `sail artisan test --coverage --min=90`
- [ ] No Pint errors: `vendor/bin/pint --test`
- [ ] No N+1 queries in critical paths
- [ ] All migrations run cleanly: `sail artisan migrate:fresh --seed`
- [ ] Manual smoke test of critical flows:
  - [ ] Login as each role type
  - [ ] View dashboard
  - [ ] Filter/sort circuits
  - [ ] Drag-drop workflow (admin)
  - [ ] Force sync (sudo admin)
  - [ ] Link planner (admin)
- [ ] Review any TODO comments in code

---

## Completion Criteria

Phase 1H is complete when:
1. All tests pass with 90%+ coverage
2. Laravel Pint shows no formatting issues
3. No N+1 queries in dashboard or admin flows
4. All browser tests pass
5. Manual smoke testing passes for all user roles

---

## Project Completion

Once Phase 1H is complete, the WS-Tracker MVP is ready for deployment. The following features will be functional:

- User authentication with 2FA
- Role-based access control (sudo_admin, admin, planner, general_foreman)
- Circuit dashboard with Kanban workflow
- Filtering by region, planner, date range
- Drag-drop workflow stage changes
- Chart visualizations
- Admin sync control
- Unlinked planner management
- Scheduled data sync from WorkStudio API

### Future Enhancements (Post-MVP)
- Work Jobs dashboard (General Foreman view)
- Planner-specific dashboard
- Map visualization with unit geometries
- Station "No Work" footage calculations
- Server monitoring dashboard
