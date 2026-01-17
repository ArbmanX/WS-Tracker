<?php

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Jobs\SyncCircuitAggregatesJob;
use App\Models\Circuit;
use App\Models\PlannedUnitsSnapshot;
use App\Models\Region;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\WorkStudio\Aggregation\AggregateCalculationService;
use App\Services\WorkStudio\Aggregation\AggregateDiffService;
use App\Services\WorkStudio\Aggregation\AggregateStorageService;
use App\Services\WorkStudio\PlannedUnitsSnapshotService;
use App\Services\WorkStudio\WorkStudioApiService;
use Illuminate\Support\Facades\Event;

describe('SyncCircuitAggregatesJob Configuration', function () {
    test('job has correct retry configuration', function () {
        $job = new SyncCircuitAggregatesJob;

        expect($job->tries)->toBe(3);
        expect($job->timeout)->toBe(1800);
    });

    test('job returns correct tags', function () {
        $job = new SyncCircuitAggregatesJob(['ACTIV', 'QC']);

        expect($job->tags())->toContain('sync', 'aggregates', 'statuses:ACTIV,QC');
    });

    test('job includes circuit count tag when circuit ids provided', function () {
        $job = new SyncCircuitAggregatesJob(circuitIds: [1, 2, 3]);

        expect($job->tags())->toContain('circuit_count:3');
    });
});

describe('SyncCircuitAggregatesJob Execution', function () {
    test('creates sync log when starting', function () {
        Event::fake();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);

        $calculationService = mock(AggregateCalculationService::class);
        $storageService = mock(AggregateStorageService::class);
        $diffService = mock(AggregateDiffService::class);
        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        $job = new SyncCircuitAggregatesJob(['ACTIV'], SyncTrigger::Manual);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        expect(SyncLog::count())->toBe(1);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_type)->toBe(SyncType::Aggregates);
        expect($syncLog->sync_trigger)->toBe(SyncTrigger::Manual);
        expect($syncLog->api_status_filter)->toBe('ACTIV');
    });

    test('fails when API health check fails', function () {
        Event::fake();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(false);

        $calculationService = mock(AggregateCalculationService::class);
        $storageService = mock(AggregateStorageService::class);
        $diffService = mock(AggregateDiffService::class);
        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        $job = new SyncCircuitAggregatesJob(['ACTIV']);

        expect(fn () => $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService))
            ->toThrow(RuntimeException::class, 'WorkStudio API is unavailable');

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Failed);
    });

    test('completes successfully when no circuits need sync', function () {
        Event::fake();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);

        $calculationService = mock(AggregateCalculationService::class);
        $storageService = mock(AggregateStorageService::class);
        $diffService = mock(AggregateDiffService::class);
        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        // No circuits exist
        $job = new SyncCircuitAggregatesJob(['ACTIV']);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Completed);
        expect($syncLog->circuits_processed)->toBe(0);
    });

    test('processes circuits when changes detected', function () {
        Event::fake();

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'ACTIV',
            'planned_units_sync_enabled' => true,
            'is_excluded' => false,
            'last_planned_units_synced_at' => null,
        ]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getPlannedUnits')->andReturn(collect());

        $calculationService = mock(AggregateCalculationService::class);
        $calculationService->shouldReceive('calculateForCircuit')
            ->once()
            ->andReturn([
                'circuit_id' => $circuit->id,
                'aggregate_date' => now()->toDateString(),
                'total_units' => 10,
            ]);

        $storageService = mock(AggregateStorageService::class);
        $storageService->shouldReceive('storeCircuitAggregate')->once();

        $diffService = mock(AggregateDiffService::class);
        $diffService->shouldReceive('compare')
            ->andReturn(['has_changes' => true]);

        $snapshotService = mock(PlannedUnitsSnapshotService::class);
        $snapshotService->shouldReceive('createSnapshotIfNeeded')->andReturnNull();

        $job = new SyncCircuitAggregatesJob(circuitIds: [$circuit->id]);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Completed);
        expect($syncLog->circuits_processed)->toBe(1);
        expect($syncLog->aggregates_created)->toBe(1);

        // Circuit should have updated sync timestamp
        $circuit->refresh();
        expect($circuit->last_planned_units_synced_at)->not->toBeNull();
    });

    test('skips storage when no changes detected', function () {
        Event::fake();

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'ACTIV',
            'planned_units_sync_enabled' => true,
            'is_excluded' => false,
            'last_planned_units_synced_at' => null,
        ]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);

        $calculationService = mock(AggregateCalculationService::class);
        $calculationService->shouldReceive('calculateForCircuit')
            ->andReturn(['circuit_id' => $circuit->id]);

        $storageService = mock(AggregateStorageService::class);
        $storageService->shouldNotReceive('storeCircuitAggregate');

        $diffService = mock(AggregateDiffService::class);
        $diffService->shouldReceive('compare')
            ->andReturn(['has_changes' => false]);

        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        $job = new SyncCircuitAggregatesJob(circuitIds: [$circuit->id]);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->circuits_processed)->toBe(1);
        expect($syncLog->aggregates_created)->toBe(0);
    });

    test('creates snapshot when needed', function () {
        Event::fake();

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'ACTIV',
            'planned_units_sync_enabled' => true,
            'is_excluded' => false,
            'last_planned_units_synced_at' => null,
            'percent_complete' => 75,
        ]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getPlannedUnits')->once()->andReturn(collect([
            ['UnitType' => 'SPM', 'LinearFt' => 100],
        ]));

        $calculationService = mock(AggregateCalculationService::class);
        $calculationService->shouldReceive('calculateForCircuit')
            ->andReturn(['circuit_id' => $circuit->id]);

        $storageService = mock(AggregateStorageService::class);
        $storageService->shouldReceive('storeCircuitAggregate');

        $diffService = mock(AggregateDiffService::class);
        $diffService->shouldReceive('compare')
            ->andReturn(['has_changes' => true]);

        $snapshotService = mock(PlannedUnitsSnapshotService::class);
        $snapshotService->shouldReceive('createSnapshotIfNeeded')
            ->once()
            ->andReturn(PlannedUnitsSnapshot::factory()->make(['circuit_id' => $circuit->id]));

        $job = new SyncCircuitAggregatesJob(circuitIds: [$circuit->id]);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Completed);
    });

    test('stores triggered_by user when provided', function () {
        Event::fake();

        $user = User::factory()->create();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);

        $calculationService = mock(AggregateCalculationService::class);
        $storageService = mock(AggregateStorageService::class);
        $diffService = mock(AggregateDiffService::class);
        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        $job = new SyncCircuitAggregatesJob(
            apiStatuses: ['ACTIV'],
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: $user->id
        );
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->triggered_by)->toBe($user->id);
    });

    test('stores context with circuit ids and api statuses', function () {
        Event::fake();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);

        $calculationService = mock(AggregateCalculationService::class);
        $storageService = mock(AggregateStorageService::class);
        $diffService = mock(AggregateDiffService::class);
        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        $job = new SyncCircuitAggregatesJob(
            apiStatuses: ['ACTIV', 'QC'],
            circuitIds: [1, 2, 3]
        );
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->context_json['circuit_ids'])->toBe([1, 2, 3]);
        expect($syncLog->context_json['api_statuses'])->toBe(['ACTIV', 'QC']);
    });
});

describe('SyncCircuitAggregatesJob Error Handling', function () {
    test('completes with warning when some circuits fail', function () {
        Event::fake();

        $region = Region::factory()->create();
        $circuit1 = Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'ACTIV',
            'planned_units_sync_enabled' => true,
            'is_excluded' => false,
            'last_planned_units_synced_at' => null,
        ]);
        $circuit2 = Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'ACTIV',
            'planned_units_sync_enabled' => true,
            'is_excluded' => false,
            'last_planned_units_synced_at' => null,
        ]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getPlannedUnits')->andReturn(collect());

        $calculationService = mock(AggregateCalculationService::class);
        $calculationService->shouldReceive('calculateForCircuit')
            ->once()
            ->ordered()
            ->andReturn(['circuit_id' => $circuit1->id]);
        $calculationService->shouldReceive('calculateForCircuit')
            ->once()
            ->ordered()
            ->andThrow(new Exception('Calculation failed'));

        $storageService = mock(AggregateStorageService::class);
        $storageService->shouldReceive('storeCircuitAggregate');

        $diffService = mock(AggregateDiffService::class);
        $diffService->shouldReceive('compare')
            ->andReturn(['has_changes' => true]);

        $snapshotService = mock(PlannedUnitsSnapshotService::class);
        $snapshotService->shouldReceive('createSnapshotIfNeeded')->andReturnNull();

        $job = new SyncCircuitAggregatesJob(circuitIds: [$circuit1->id, $circuit2->id]);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Warning);
        expect($syncLog->error_message)->toContain('1 circuits failed');
    });

    test('stores error details in sync log on failure', function () {
        Event::fake();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andThrow(new RuntimeException('Connection error'));

        $calculationService = mock(AggregateCalculationService::class);
        $storageService = mock(AggregateStorageService::class);
        $diffService = mock(AggregateDiffService::class);
        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        $job = new SyncCircuitAggregatesJob(['ACTIV']);

        try {
            $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);
        } catch (RuntimeException $e) {
            // Expected
        }

        $syncLog = SyncLog::first();
        expect($syncLog->error_details['exception'])->toBe('RuntimeException');
        expect($syncLog->error_details)->toHaveKey('trace');
    });
});

describe('SyncCircuitAggregatesJob Circuit Selection', function () {
    test('filters circuits by api status', function () {
        Event::fake();

        $region = Region::factory()->create();

        // Create circuit with ACTIV status that should be synced
        $activeCircuit = Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'ACTIV',
            'planned_units_sync_enabled' => true,
            'is_excluded' => false,
            'last_planned_units_synced_at' => null,
        ]);

        // Create circuit with CLOSE status that should NOT be synced
        Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'CLOSE',
            'planned_units_sync_enabled' => true,
            'is_excluded' => false,
            'last_planned_units_synced_at' => null,
        ]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getPlannedUnits')->andReturn(collect());

        $calculationService = mock(AggregateCalculationService::class);
        $calculationService->shouldReceive('calculateForCircuit')
            ->once()
            ->andReturn(['circuit_id' => $activeCircuit->id]);

        $storageService = mock(AggregateStorageService::class);
        $storageService->shouldReceive('storeCircuitAggregate');

        $diffService = mock(AggregateDiffService::class);
        $diffService->shouldReceive('compare')
            ->andReturn(['has_changes' => true]);

        $snapshotService = mock(PlannedUnitsSnapshotService::class);
        $snapshotService->shouldReceive('createSnapshotIfNeeded')->andReturnNull();

        $job = new SyncCircuitAggregatesJob(['ACTIV']);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->circuits_processed)->toBe(1);
    });

    test('excludes excluded circuits', function () {
        Event::fake();

        $region = Region::factory()->create();

        // Create excluded circuit
        Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'ACTIV',
            'planned_units_sync_enabled' => true,
            'is_excluded' => true,
            'last_planned_units_synced_at' => null,
        ]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);

        $calculationService = mock(AggregateCalculationService::class);
        $storageService = mock(AggregateStorageService::class);
        $diffService = mock(AggregateDiffService::class);
        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        $job = new SyncCircuitAggregatesJob(['ACTIV']);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->circuits_processed)->toBe(0);
    });

    test('excludes circuits with sync disabled', function () {
        Event::fake();

        $region = Region::factory()->create();

        // Create circuit with sync disabled
        Circuit::factory()->create([
            'region_id' => $region->id,
            'api_status' => 'ACTIV',
            'planned_units_sync_enabled' => false,
            'is_excluded' => false,
            'last_planned_units_synced_at' => null,
        ]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);

        $calculationService = mock(AggregateCalculationService::class);
        $storageService = mock(AggregateStorageService::class);
        $diffService = mock(AggregateDiffService::class);
        $snapshotService = mock(PlannedUnitsSnapshotService::class);

        $job = new SyncCircuitAggregatesJob(['ACTIV']);
        $job->handle($api, $calculationService, $storageService, $diffService, $snapshotService);

        $syncLog = SyncLog::first();
        expect($syncLog->circuits_processed)->toBe(0);
    });
});
