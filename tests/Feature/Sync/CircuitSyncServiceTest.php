<?php

use App\Models\Circuit;
use App\Models\Region;
use App\Models\User;
use App\Services\WorkStudio\Sync\CircuitSyncService;

beforeEach(function () {
    $this->region = Region::factory()->create(['name' => 'Test Region']);
    $this->user = User::factory()->create(); // Create a user for modification tracking
    $this->syncService = app(CircuitSyncService::class);
});

describe('syncCircuit', function () {
    it('creates a new circuit from API data', function () {
        $apiData = [
            'job_guid' => 'test-guid-123',
            'work_order' => '2026-0001',
            'extension' => '@',
            'title' => 'Test Circuit',
            'api_status' => 'ACTIV',
            'api_modified_date' => now()->subDay(),
            'region_id' => $this->region->id,
            'total_miles' => 10.5,
            'percent_complete' => 25.0,
        ];

        $circuit = $this->syncService->syncCircuit($apiData);

        expect($circuit)->toBeInstanceOf(Circuit::class)
            ->and($circuit->job_guid)->toBe('test-guid-123')
            ->and($circuit->work_order)->toBe('2026-0001')
            ->and($circuit->title)->toBe('Test Circuit')
            ->and($circuit->last_synced_at)->not->toBeNull();

        // Check UI state was created (need to refresh to load relationship)
        $circuit->refresh();
        expect($circuit->uiState)->not->toBeNull();
    });

    it('updates existing circuit with API data', function () {
        $circuit = Circuit::factory()->create([
            'job_guid' => 'existing-guid',
            'work_order' => '2026-0002',
            'title' => 'Original Title',
            'total_miles' => 10.0,
            'region_id' => $this->region->id,
        ]);

        $apiData = [
            'job_guid' => 'existing-guid',
            'work_order' => '2026-0002',
            'title' => 'Updated Title',
            'total_miles' => 15.0,
            'api_status' => 'ACTIV',
        ];

        $updatedCircuit = $this->syncService->syncCircuit($apiData);

        expect($updatedCircuit->id)->toBe($circuit->id)
            ->and($updatedCircuit->title)->toBe('Updated Title')
            ->and((float) $updatedCircuit->total_miles)->toBe(15.0);
    });

    it('preserves user-modified fields during normal sync', function () {
        $circuit = Circuit::factory()->create([
            'job_guid' => 'user-modified-guid',
            'title' => 'User Modified Title',
            'total_miles' => 10.0,
            'region_id' => $this->region->id,
        ]);

        // Mark title as user-modified with a valid user ID
        $circuit->markFieldAsUserModified('title', $this->user->id);
        $circuit->save();

        $apiData = [
            'job_guid' => 'user-modified-guid',
            'title' => 'API Title', // This should NOT overwrite
            'total_miles' => 20.0, // This should update
            'api_status' => 'ACTIV',
        ];

        $updatedCircuit = $this->syncService->syncCircuit($apiData, forceOverwrite: false);

        // Title should be preserved (user-modified)
        expect($updatedCircuit->title)->toBe('User Modified Title')
            // total_miles should be updated (not user-modified)
            ->and((float) $updatedCircuit->total_miles)->toBe(20.0);

        // Check that preserved fields were tracked
        $results = $this->syncService->getResults();
        expect($results['user_preserved_fields'])->toHaveKey('user-modified-guid');
    });

    it('overwrites user-modified fields with force overwrite', function () {
        $circuit = Circuit::factory()->create([
            'job_guid' => 'force-overwrite-guid',
            'title' => 'User Modified Title',
            'region_id' => $this->region->id,
        ]);

        // Mark title as user-modified with a valid user ID
        $circuit->markFieldAsUserModified('title', $this->user->id);
        $circuit->save();

        $apiData = [
            'job_guid' => 'force-overwrite-guid',
            'title' => 'API Forced Title',
            'api_status' => 'ACTIV',
        ];

        $updatedCircuit = $this->syncService->syncCircuit($apiData, forceOverwrite: true);

        // Title should be overwritten
        expect($updatedCircuit->title)->toBe('API Forced Title')
            // User modification tracking should be cleared
            ->and($updatedCircuit->hasUserModifications())->toBeFalse();
    });

    it('always updates API-only fields regardless of user modifications', function () {
        $circuit = Circuit::factory()->create([
            'job_guid' => 'api-only-guid',
            'api_status' => 'ACTIV',
            'region_id' => $this->region->id,
        ]);

        $apiData = [
            'job_guid' => 'api-only-guid',
            'api_status' => 'QC', // API-only field, always updated
        ];

        $updatedCircuit = $this->syncService->syncCircuit($apiData);

        expect($updatedCircuit->api_status)->toBe('QC');
    });

    it('does not update when no changes detected', function () {
        $circuit = Circuit::factory()->create([
            'job_guid' => 'no-change-guid',
            'title' => 'Same Title',
            'api_status' => 'ACTIV',
            'total_miles' => 10.0,
            'region_id' => $this->region->id,
        ]);

        $apiData = [
            'job_guid' => 'no-change-guid',
            'title' => 'Same Title',
            'api_status' => 'ACTIV',
            'total_miles' => 10.0,
        ];

        $this->syncService->syncCircuit($apiData);

        $results = $this->syncService->getResults();
        expect($results['unchanged'])->toBe(1);
    });
});

describe('syncCircuits', function () {
    it('syncs a collection of circuits', function () {
        $circuitData = collect([
            [
                'job_guid' => 'batch-guid-1',
                'work_order' => '2026-0010',
                'title' => 'Batch Circuit 1',
                'api_status' => 'ACTIV',
                'api_modified_date' => now()->subDay(),
                'region_id' => $this->region->id,
            ],
            [
                'job_guid' => 'batch-guid-2',
                'work_order' => '2026-0011',
                'title' => 'Batch Circuit 2',
                'api_status' => 'ACTIV',
                'api_modified_date' => now()->subDay(),
                'region_id' => $this->region->id,
            ],
        ]);

        $results = $this->syncService->syncCircuits($circuitData);

        expect($results['created'])->toBe(2)
            ->and(Circuit::count())->toBe(2);
    });

    it('handles errors gracefully and continues syncing', function () {
        $circuitData = collect([
            [
                // Missing job_guid - will fail
                'work_order' => '2026-0020',
                'title' => 'Invalid Circuit',
            ],
            [
                'job_guid' => 'valid-guid',
                'work_order' => '2026-0021',
                'title' => 'Valid Circuit',
                'api_status' => 'ACTIV',
                'api_modified_date' => now()->subDay(),
                'region_id' => $this->region->id,
            ],
        ]);

        $results = $this->syncService->syncCircuits($circuitData);

        expect($results['errors'])->toHaveCount(1)
            ->and($results['created'])->toBe(1);
    });
});

describe('syncPlanners', function () {
    it('links planners to circuits by ws_username', function () {
        $user = User::factory()->create(['ws_username' => 'jsmith']);
        $circuit = Circuit::factory()->create(['region_id' => $this->region->id]);

        $result = $this->syncService->syncPlanners($circuit, ['jsmith']);

        expect($result['linked'])->toBe(1)
            ->and($circuit->planners)->toHaveCount(1)
            ->and($circuit->planners->first()->id)->toBe($user->id);
    });

    it('tracks unlinked planners for later manual linking', function () {
        $circuit = Circuit::factory()->create(['region_id' => $this->region->id]);

        $result = $this->syncService->syncPlanners($circuit, ['unknown_user']);

        expect($result['unlinked'])->toBe(1);

        $this->assertDatabaseHas('unlinked_planners', [
            'ws_username' => 'unknown_user',
        ]);
    });
});

describe('previewSync', function () {
    it('previews sync changes without modifying data', function () {
        Circuit::factory()->create([
            'job_guid' => 'preview-guid',
            'title' => 'Original',
            'region_id' => $this->region->id,
        ]);

        $circuitData = collect([
            [
                'job_guid' => 'preview-guid',
                'title' => 'Updated',
                'api_status' => 'ACTIV',
            ],
            [
                'job_guid' => 'new-guid',
                'work_order' => '2026-0030',
                'title' => 'New Circuit',
                'api_status' => 'ACTIV',
                'api_modified_date' => now()->subDay(),
                'region_id' => $this->region->id,
            ],
        ]);

        $preview = $this->syncService->previewSync($circuitData);

        expect($preview['would_create'])->toBe(1)
            ->and($preview['would_update'])->toBe(1);

        // Verify no data was actually modified
        expect(Circuit::where('job_guid', 'new-guid')->exists())->toBeFalse()
            ->and(Circuit::where('job_guid', 'preview-guid')->first()->title)->toBe('Original');
    });
});

describe('Circuit model user modification tracking', function () {
    it('tracks field modifications', function () {
        $circuit = Circuit::factory()->create(['region_id' => $this->region->id]);

        $circuit->markFieldAsUserModified('title', $this->user->id);
        $circuit->save();

        expect($circuit->isFieldUserModified('title'))->toBeTrue()
            ->and($circuit->isFieldUserModified('total_miles'))->toBeFalse()
            ->and($circuit->hasUserModifications())->toBeTrue();
    });

    it('clears specific field modifications', function () {
        $circuit = Circuit::factory()->create(['region_id' => $this->region->id]);

        $circuit->markFieldAsUserModified('title', $this->user->id);
        $circuit->markFieldAsUserModified('total_miles', $this->user->id);
        $circuit->save();

        $circuit->clearFieldUserModification('title');
        $circuit->save();

        expect($circuit->isFieldUserModified('title'))->toBeFalse()
            ->and($circuit->isFieldUserModified('total_miles'))->toBeTrue();
    });

    it('clears all modifications', function () {
        $circuit = Circuit::factory()->create(['region_id' => $this->region->id]);

        $circuit->markFieldAsUserModified('title', $this->user->id);
        $circuit->markFieldAsUserModified('total_miles', $this->user->id);
        $circuit->save();

        $circuit->clearAllUserModifications();
        $circuit->save();

        expect($circuit->hasUserModifications())->toBeFalse();
    });

    it('only tracks syncable fields', function () {
        $circuit = Circuit::factory()->create(['region_id' => $this->region->id]);

        // job_guid is an API-only field, should not be tracked
        $circuit->markFieldAsUserModified('job_guid', $this->user->id);
        $circuit->save();

        expect($circuit->isFieldUserModified('job_guid'))->toBeFalse();
    });

    it('scopes to circuits with user modifications', function () {
        $unmodified = Circuit::factory()->create(['region_id' => $this->region->id]);

        $modified = Circuit::factory()->create(['region_id' => $this->region->id]);
        $modified->markFieldAsUserModified('title', $this->user->id);
        $modified->save();

        $withMods = Circuit::withUserModifications()->pluck('id')->toArray();
        $withoutMods = Circuit::withoutUserModifications()->pluck('id')->toArray();

        expect($withMods)->toContain($modified->id)
            ->and($withMods)->not->toContain($unmodified->id)
            ->and($withoutMods)->toContain($unmodified->id)
            ->and($withoutMods)->not->toContain($modified->id);
    });
});
