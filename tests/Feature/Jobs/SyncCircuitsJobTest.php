<?php

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Events\SyncCompletedEvent;
use App\Events\SyncFailedEvent;
use App\Events\SyncStartedEvent;
use App\Jobs\SyncCircuitAggregatesJob;
use App\Jobs\SyncCircuitsJob;
use App\Models\Circuit;
use App\Models\Region;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\WorkStudio\Sync\CircuitSyncService;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use App\Services\WorkStudio\WorkStudioApiService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

describe('SyncCircuitsJob Configuration', function () {
    test('job has correct retry configuration', function () {
        $job = new SyncCircuitsJob(['ACTIV']);

        expect($job->tries)->toBe(3);
        expect($job->timeout)->toBe(600);
    });

    test('job returns correct tags', function () {
        $job = new SyncCircuitsJob(['ACTIV', 'QC']);

        expect($job->tags())->toContain('sync', 'circuits', 'statuses:ACTIV,QC');
    });

    test('job includes force-overwrite tag when enabled', function () {
        $job = new SyncCircuitsJob(['ACTIV'], forceOverwrite: true);

        expect($job->tags())->toContain('force-overwrite');
    });

    test('job includes preserve-user-changes tag when not force overwriting', function () {
        $job = new SyncCircuitsJob(['ACTIV'], forceOverwrite: false);

        expect($job->tags())->toContain('preserve-user-changes');
    });
});

describe('SyncCircuitsJob Execution', function () {
    test('creates sync log when starting', function () {
        Event::fake();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect());

        $transformer = mock(CircuitTransformer::class);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 0,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV'], SyncTrigger::Manual);
        $job->handle($api, $transformer, $syncService);

        expect(SyncLog::count())->toBe(1);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_type)->toBe(SyncType::CircuitList);
        expect($syncLog->sync_trigger)->toBe(SyncTrigger::Manual);
        expect($syncLog->api_status_filter)->toBe('ACTIV');
    });

    test('dispatches SyncStartedEvent when starting', function () {
        Event::fake([SyncStartedEvent::class, SyncCompletedEvent::class]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect());

        $transformer = mock(CircuitTransformer::class);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 0,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV']);
        $job->handle($api, $transformer, $syncService);

        Event::assertDispatched(SyncStartedEvent::class);
    });

    test('fails when API health check fails', function () {
        Event::fake([SyncStartedEvent::class, SyncFailedEvent::class]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(false);

        $transformer = mock(CircuitTransformer::class);
        $syncService = mock(CircuitSyncService::class);

        $job = new SyncCircuitsJob(['ACTIV']);

        expect(fn () => $job->handle($api, $transformer, $syncService))
            ->toThrow(RuntimeException::class, 'WorkStudio API is unavailable');

        Event::assertDispatched(SyncFailedEvent::class);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Failed);
    });

    test('syncs circuits from API for each status', function () {
        Event::fake();

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create(['region_id' => $region->id]);

        $circuitData = [
            [
                'job_guid' => 'guid-1',
                'work_order' => 'WO-001',
                'api_data_json' => [],
            ],
        ];

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')
            ->with('ACTIV', null)
            ->once()
            ->andReturn(collect($circuitData));
        $api->shouldReceive('getCircuitsByStatus')
            ->with('QC', null)
            ->once()
            ->andReturn(collect());

        $transformer = mock(CircuitTransformer::class);
        $transformer->shouldReceive('extractPlanners')
            ->andReturn([]);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('syncCircuit')
            ->with($circuitData[0], false)
            ->once()
            ->andReturn($circuit);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 1,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV', 'QC']);
        $job->handle($api, $transformer, $syncService);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Completed);
        expect($syncLog->circuits_processed)->toBe(1);
    });

    test('syncs planners when present in circuit data', function () {
        Event::fake();

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create(['region_id' => $region->id]);

        $circuitData = [
            [
                'job_guid' => 'guid-1',
                'work_order' => 'WO-001',
                'api_data_json' => ['Planner' => 'jsmith'],
            ],
        ];

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect($circuitData));

        $transformer = mock(CircuitTransformer::class);
        $transformer->shouldReceive('extractPlanners')
            ->with(['Planner' => 'jsmith'])
            ->andReturn(['jsmith']);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('syncCircuit')->andReturn($circuit);
        $syncService->shouldReceive('syncPlanners')
            ->with($circuit, ['jsmith'])
            ->once()
            ->andReturn(['linked' => 1, 'unlinked' => 0]);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 1,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV']);
        $job->handle($api, $transformer, $syncService);
    });

    test('dispatches SyncCompletedEvent on success', function () {
        Event::fake([SyncStartedEvent::class, SyncCompletedEvent::class]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect());

        $transformer = mock(CircuitTransformer::class);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 0,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV']);
        $job->handle($api, $transformer, $syncService);

        Event::assertDispatched(SyncCompletedEvent::class);
    });

    test('dispatches SyncCircuitAggregatesJob after processing circuits', function () {
        Event::fake();
        Queue::fake();

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create(['region_id' => $region->id]);

        $circuitData = [
            [
                'job_guid' => 'guid-1',
                'work_order' => 'WO-001',
                'api_data_json' => [],
            ],
        ];

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect($circuitData));

        $transformer = mock(CircuitTransformer::class);
        $transformer->shouldReceive('extractPlanners')->andReturn([]);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('syncCircuit')->andReturn($circuit);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 1,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV']);
        $job->handle($api, $transformer, $syncService);

        Queue::assertPushed(SyncCircuitAggregatesJob::class);
    });

    test('does not dispatch aggregate job when no circuits processed', function () {
        Event::fake();
        Queue::fake();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect());

        $transformer = mock(CircuitTransformer::class);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 0,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV']);
        $job->handle($api, $transformer, $syncService);

        Queue::assertNotPushed(SyncCircuitAggregatesJob::class);
    });

    test('completes with warning when some circuits fail', function () {
        Event::fake();

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create(['region_id' => $region->id]);

        $circuitData = [
            [
                'job_guid' => 'guid-1',
                'work_order' => 'WO-001',
                'api_data_json' => [],
            ],
            [
                'job_guid' => 'guid-2',
                'work_order' => 'WO-002',
                'api_data_json' => [],
            ],
        ];

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect($circuitData));

        $transformer = mock(CircuitTransformer::class);
        $transformer->shouldReceive('extractPlanners')->andReturn([]);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('syncCircuit')
            ->once()
            ->ordered()
            ->andReturn($circuit);
        $syncService->shouldReceive('syncCircuit')
            ->once()
            ->ordered()
            ->andThrow(new Exception('Circuit sync failed'));
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 1,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV']);
        $job->handle($api, $transformer, $syncService);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Warning);
        expect($syncLog->error_message)->toContain('1 circuits failed');
    });

    test('stores triggered_by user when provided', function () {
        Event::fake();

        $user = User::factory()->create();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect());

        $transformer = mock(CircuitTransformer::class);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 0,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(
            statuses: ['ACTIV'],
            triggerType: SyncTrigger::Manual,
            triggeredByUserId: $user->id
        );
        $job->handle($api, $transformer, $syncService);

        $syncLog = SyncLog::first();
        expect($syncLog->triggered_by)->toBe($user->id);
    });

    test('stores context with force_overwrite setting', function () {
        Event::fake();

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect());

        $transformer = mock(CircuitTransformer::class);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 0,
            'updated' => 0,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV'], forceOverwrite: true);
        $job->handle($api, $transformer, $syncService);

        $syncLog = SyncLog::first();
        expect($syncLog->context_json['force_overwrite'])->toBeTrue();
        expect($syncLog->context_json['statuses'])->toBe(['ACTIV']);
    });

    test('passes force overwrite flag to sync service', function () {
        Event::fake();

        $region = Region::factory()->create();
        $circuit = Circuit::factory()->create(['region_id' => $region->id]);

        $circuitData = [
            [
                'job_guid' => 'guid-1',
                'work_order' => 'WO-001',
                'api_data_json' => [],
            ],
        ];

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andReturn(true);
        $api->shouldReceive('getCircuitsByStatus')->andReturn(collect($circuitData));

        $transformer = mock(CircuitTransformer::class);
        $transformer->shouldReceive('extractPlanners')->andReturn([]);

        $syncService = mock(CircuitSyncService::class);
        $syncService->shouldReceive('syncCircuit')
            ->with($circuitData[0], true) // force overwrite = true
            ->once()
            ->andReturn($circuit);
        $syncService->shouldReceive('getResults')->andReturn([
            'created' => 0,
            'updated' => 1,
            'user_preserved_fields' => [],
        ]);

        $job = new SyncCircuitsJob(['ACTIV'], forceOverwrite: true);
        $job->handle($api, $transformer, $syncService);
    });
});

describe('SyncCircuitsJob Failure Handling', function () {
    test('dispatches SyncFailedEvent and rethrows exception', function () {
        Event::fake([SyncStartedEvent::class, SyncFailedEvent::class]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andThrow(new RuntimeException('Connection failed'));

        $transformer = mock(CircuitTransformer::class);
        $syncService = mock(CircuitSyncService::class);

        $job = new SyncCircuitsJob(['ACTIV']);

        expect(fn () => $job->handle($api, $transformer, $syncService))
            ->toThrow(RuntimeException::class, 'Connection failed');

        Event::assertDispatched(SyncFailedEvent::class);

        $syncLog = SyncLog::first();
        expect($syncLog->sync_status)->toBe(SyncStatus::Failed);
        expect($syncLog->error_message)->toBe('Connection failed');
    });

    test('stores error details in sync log', function () {
        Event::fake([SyncStartedEvent::class, SyncFailedEvent::class]);

        $api = mock(WorkStudioApiService::class);
        $api->shouldReceive('healthCheck')->andThrow(new RuntimeException('Test error'));

        $transformer = mock(CircuitTransformer::class);
        $syncService = mock(CircuitSyncService::class);

        $job = new SyncCircuitsJob(['ACTIV']);

        try {
            $job->handle($api, $transformer, $syncService);
        } catch (RuntimeException $e) {
            // Expected
        }

        $syncLog = SyncLog::first();
        expect($syncLog->error_details['exception'])->toBe('RuntimeException');
        expect($syncLog->error_details)->toHaveKey('trace');
    });
});
