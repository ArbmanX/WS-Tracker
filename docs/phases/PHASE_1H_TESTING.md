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
- [ ] <!--ns65t-Cg-tV5BHy7VS9tn--> UnitType model tests
- [ ] <!--drAwR-blhkKzfcoQdqc1W--> Circuit model tests
- [ ] <!--ZXGNrxnjQcarjwPSHm86o--> Region model tests
- [ ] <!--WlqTYa0R1zCVe-yFtAVkj--> DateParser helper tests
- [ ] <!--3F4et655vBZfXxftvq-87--> DDOTableTransformer tests
- [ ] <!--Eg4gh7H5W5CXQzOxWH3Px--> CircuitTransformer tests
- [ ] <!--l_3A_z4q45pZ2dhKfjOic--> PlannedUnitAggregateTransformer tests
- [ ] <!--LYWM_fP4OXMz_6_pn5xqL--> AggregateCalculationService tests
- [ ] <!--6ZCqYz6zLq9fq_LS83kGk--> ApiCredentialManager tests
  # Feature Tests
- [ ] <!--BucyznQjUyuaQZyqqaQFB--> Authentication flow tests
- [ ] <!--FvD_jAtqf_fLNeQmsZUGY--> Dashboard component tests
- [ ] <!--Rgv474c06va9zxegyT_Fn--> Filter panel tests
- [ ] <!--4WAv4p4ocpO2sR0OnT-9_--> Workflow drag-drop tests
- [ ] <!--wUVgfMINB8fruyE0--HC8--> Chart component tests
- [ ] <!--qALyWvhuM6b269HDoHy9j--> Admin sync control tests
- [ ] <!--tGVVs6rGxUFX5QPpzA5Md--> Unlinked planners tests
- [ ] <!--RYqH8EqPEXS2PFw9ODMJ0--> User management tests (sudo admin)
- [ ] <!--53rHPuMJ_ntfOws5OEAE3--> SyncCircuitsJob tests
- [ ] <!--WaMda96-51KO8K8dZsqgM--> SyncCircuitAggregatesJob tests
- [ ] <!--7Xy4yqSYXORft8bUIR9Ri--> CreateDailySnapshotsJob tests
  # Browser Tests (Pest 4)
- [ ] <!--P5pvqjLqylE0R8PfCs69---> Login flow
- [ ] <!--ITiN_R72taLwKnAPnnd-5--> Dashboard filter/sort
- [ ] <!--TZOzNWtTCarWXMbLIjq61--> Drag-drop workflow
- [ ] <!--_0UeZ_orvAQVWcuNOGvWt--> Theme switching
- [ ] <!--S7-pf-eqM5GlJc0eSoVQn--> Admin sync control
- [ ] <!--_dTPmhxhpp8OmAPpfRB7m--> Chart rendering
  # Code Quality
- [ ] <!--hH-JjZmmSsgKWTBiO_vE_--> Run `vendor/bin/pint --dirty`
- [ ] <!--PeZhuFUUfqtfynq3ajHS7--> Run `vendor/bin/pint` (full)
- [ ] <!--VDjUgSZDF4r4EBhI6bS3I--> Check for N+1 queries
- [ ] <!--ye7O4z6MwRU_sUHfsDjxb--> Add missing database indexes
- [ ] <!--ratWQvp7yOKn0yWkHPfek--> Review error handling
  # Documentation
- [ ] <!--AKeORLz16PPfWlao5xiU3--> Update CLAUDE.md if needed
- [ ] <!--Ci0oBn7EHmsmOEqAhWjUC--> Add inline documentation where complex
- [ ] <!--KknIAx-ph_6B8IgRYIU-f--> Verify all tests pass
  -
   Test File Structure
  `
  sts/
  ─ Feature/
    ├── Auth/
    │   ├── LoginTest.php
    │   └── RoleAccessTest.php
    ├── Dashboard/
    │   ├── CircuitDashboardTest.php
    │   ├── FilterPanelTest.php
    │   └── WorkflowDragDropTest.php
    ├── Charts/
    │   ├── MilesByRegionChartTest.php
    │   ├── PermissionStatusChartTest.php
    │   └── PlannerProgressChartTest.php
    ├── Admin/
    │   ├── SyncControlTest.php
    │   ├── SyncHistoryTest.php
    │   ├── UnlinkedPlannersTest.php
    │   └── AccessControlTest.php
    ├── Sync/
    │   ├── SyncCircuitsJobTest.php
    │   ├── SyncCircuitAggregatesJobTest.php
    │   └── CreateDailySnapshotsJobTest.php
    └── Database/
        └── MigrationTest.php
  ─ Unit/
    ├── Models/
    │   ├── UnitTypeTest.php
    │   ├── CircuitTest.php
    │   └── RegionTest.php
    ├── Transformers/
    │   ├── DDOTableTransformerTest.php
    │   ├── CircuitTransformerTest.php
    │   └── PlannedUnitAggregateTransformerTest.php
    ├── Services/
    │   ├── ApiCredentialManagerTest.php
    │   └── AggregateCalculationServiceTest.php
    └── Helpers/
        └── DateParserTest.php
  ─ Browser/
    ├── DashboardFlowTest.php
    ├── ThemeSwitchingTest.php
    ├── DragDropTest.php
    └── AdminPanelTest.php
  `
  -
   Example Tests
  # Unit Test: UnitType Model
  `php
   tests/Unit/Models/UnitTypeTest.php
  php
  e App\Models\UnitType;
  scribe('UnitType Model', function () {
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
  ;
  `
  # Feature Test: Workflow Drag-Drop
  `php
   tests/Feature/Dashboard/WorkflowDragDropTest.php
  php
  e App\Models\User;
  e App\Models\Circuit;
  e App\Models\CircuitUiState;
  e App\Livewire\Dashboard\CircuitDashboard;
  e App\Enums\WorkflowStage;
  e Livewire\Livewire;
  scribe('Workflow Drag-Drop', function () {
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
  ;
  `
  # Browser Test: Dashboard Flow (Pest 4)
  `php
   tests/Browser/DashboardFlowTest.php
  php
  e App\Models\User;
  e App\Models\Circuit;
  e App\Models\Region;
  scribe('Dashboard Flow', function () {
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
  ;
  `
  # Browser Test: Theme Switching
  `php
   tests/Browser/ThemeSwitchingTest.php
  php
  e App\Models\User;
  scribe('Theme Switching', function () {
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
  ;
  `
  -
   Running Tests
  # Run All Tests
  `bash
  il artisan test --compact
  `
  # Run Specific File
  `bash
  il artisan test --compact tests/Feature/Dashboard/CircuitDashboardTest.php
  `
  # Run With Filter
  `bash
  il artisan test --compact --filter="moves circuit to new stage"
  `
  # Run With Coverage
  `bash
  il artisan test --coverage --min=90
  `
  # Run Browser Tests
  `bash
  il artisan test --compact tests/Browser/
  `
  # Run Pint
  `bash
  Check only
  ndor/bin/pint --test
  Fix all
  ndor/bin/pint
  Fix dirty files only
  ndor/bin/pint --dirty
  `
  -
   Performance Checklist
  # Database
- [ ] <!--5V3MC5LMwntYmf4gf0hG6--> Review all queries in Livewire components
- [ ] <!--rITl5lw41PnDxVSaMCOnI--> Add `->with()` for eager loading where needed
- [ ] <!--XHb0h7zqjUZjgWApOR1wu--> Check for N+1 queries using Laravel Debugbar
- [ ] <!--W6j653gwhVZC46z3JSQ1u--> Verify indexes exist for common queries:
  ```sql
  -- circuits
  CREATE INDEX IF NOT EXISTS idx_circuits_region_status ON circuits (region_id, api_status);
  CREATE INDEX IF NOT EXISTS idx_circuits_modified ON circuits (api_modified_date DESC);
  -- circuit_aggregates
  CREATE INDEX IF NOT EXISTS idx_aggregates_circuit_date ON circuit_aggregates (circuit_id, aggregate_date);
  -- circuit_ui_states
  CREATE INDEX IF NOT EXISTS idx_ui_states_workflow ON circuit_ui_states (workflow_stage, column_position);
  ```
  # Caching
- [ ] <!--5ydfo6UQ4AQctWDWtgPs6--> Verify UnitType caching works
- [ ] <!--wvSiqv83wB_BjMhaYpdoD--> Consider caching region list
- [ ] <!--F-kHDODoaDTDntTeedQzW--> Cache chart data with appropriate TTL
  # Frontend
- [ ] <!--GZ6dl2iWpHEEQtOvLy5QJ--> Verify Vite builds without errors
- [ ] <!--kT-JZXrk0lb7QCerQr8lI--> Check bundle size
- [ ] <!--7K8DlFuj9l086QZW_CNui--> Verify lazy loading where appropriate
  -
   Final Checklist
  # Before Marking Complete
- [ ] <!--fwI2735t6RYa8UylpVIQ1--> All tests pass: `sail artisan test --compact`
- [ ] <!--WBqU5aKHzceEKhjnKIad---> Coverage meets target: `sail artisan test --coverage --min=90`
- [ ] <!--9L4eGogO1WV24BWNe3pD9--> No Pint errors: `vendor/bin/pint --test`
- [ ] <!--fsqFYMnmK0HG522SWxzIe--> No N+1 queries in critical paths
- [ ] <!--KHIdRwlMFK_ts_T3E028j--> All migrations run cleanly: `sail artisan migrate:fresh --seed`
- [ ] <!---z6q8XgSWpEpxr5GtO8HQ--> Manual smoke test of critical flows:
  - [ ] <!--lf4Na4m68ImjaUQlrUHmp--> Login as each role type
  - [ ] <!--HUYY3YivaSDKh50ZW7p7D--> View dashboard
  - [ ] <!--xMpBA9WBYCOhpDnp0oWxz--> Filter/sort circuits
  - [ ] <!--ZX49hEX9722xHlnOqioGP--> Drag-drop workflow (admin)
  - [ ] <!--xxKG-uOyk03HHMOKZDFHv--> Force sync (sudo admin)
  - [ ] <!--BKjMGaduUujPvLmMGqU8c--> Link planner (admin)
- [ ] <!--xHdD9KGUxrjvAR1QazzgA--> Review any TODO comments in code
  -
   Completion Criteria
  ase 1H is complete when:
   All tests pass with 90%+ coverage
   Laravel Pint shows no formatting issues
   No N+1 queries in dashboard or admin flows
   All browser tests pass
   Manual smoke testing passes for all user roles
  -
   Project Completion
  ce Phase 1H is complete, the WS-Tracker MVP is ready for deployment. The following features will be functional:
  User authentication with 2FA
  Role-based access control (sudo_admin, admin, planner, general_foreman)
  Circuit dashboard with Kanban workflow
  Filtering by region, planner, date range
  Drag-drop workflow stage changes
  Chart visualizations
  Admin sync control
  Unlinked planner management
  Scheduled data sync from WorkStudio API
  # Future Enhancements (Post-MVP)
  Work Jobs dashboard (General Foreman view)
  Planner-specific dashboard
  Map visualization with unit geometries
  Station "No Work" footage calculations
  Server monitoring dashboard