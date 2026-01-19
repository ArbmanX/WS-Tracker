<?php

use App\Models\Region;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->transformer = new CircuitTransformer;

    // Create test regions
    Region::factory()->create(['name' => 'Central', 'code' => 'CEN']);
    Region::factory()->create(['name' => 'Lancaster', 'code' => 'LAN']);
    Region::factory()->create(['name' => 'Lehigh', 'code' => 'LEH']);

    // Clear cache after creating regions
    Cache::forget('regions.by_name');
});

it('transforms api row to circuit format', function () {
    $apiRow = [
        'SS_JOBGUID' => '{14A9372D-531F-4CE9-9906-657A6C965CC0}',
        'SS_WO' => '2025-1930',
        'SS_EXT' => '@',
        'SS_TITLE' => 'HATFIELD 69/12 KV LINE',
        'WSREQ_STATUS' => 'ACTIV',
        'REGION' => 'Lehigh',
        'VEGJOB_LENGTH' => 14.93,
        'VEGJOB_LENGTHCOMP' => 4.38,
        'VEGJOB_PRCENT' => 35,
        'VEGJOB_FORESTER' => 'John Doe',
        'SS_EDITDATE' => now(),
    ];

    $result = $this->transformer->transform($apiRow);

    expect($result['job_guid'])->toBe('{14A9372D-531F-4CE9-9906-657A6C965CC0}')
        ->and($result['work_order'])->toBe('2025-1930')
        ->and($result['extension'])->toBe('@')
        ->and($result['title'])->toBe('HATFIELD 69/12 KV LINE')
        ->and($result['api_status'])->toBe('ACTIV')
        ->and($result['region_id'])->not->toBeNull()
        ->and($result['total_miles'])->toBe(14.93)
        ->and($result['miles_planned'])->toBe(4.38)
        ->and($result['percent_complete'])->toBe(35.0);
});

it('resolves region name to id', function () {
    $apiRow = [
        'SS_JOBGUID' => '{TEST}',
        'SS_WO' => '2025-0001',
        'REGION' => 'Central',
    ];

    $result = $this->transformer->transform($apiRow);
    $centralRegion = Region::where('name', 'Central')->first();

    expect($result['region_id'])->toBe($centralRegion->id)
        ->and($result)->not->toHaveKey('region_name');
});

it('normalizes empty extension to @', function () {
    $apiRows = [
        ['SS_JOBGUID' => '{1}', 'SS_WO' => '2025-1', 'SS_EXT' => ''],
        ['SS_JOBGUID' => '{2}', 'SS_WO' => '2025-2', 'SS_EXT' => null],
        ['SS_JOBGUID' => '{3}', 'SS_WO' => '2025-3', 'SS_EXT' => '@'],
    ];

    foreach ($apiRows as $row) {
        $result = $this->transformer->transform($row);
        expect($result['extension'])->toBe('@');
    }
});

it('preserves split extension values', function () {
    $apiRow = [
        'SS_JOBGUID' => '{1}',
        'SS_WO' => '2025-1234',
        'SS_EXT' => 'A',
    ];

    $result = $this->transformer->transform($apiRow);

    expect($result['extension'])->toBe('A');
});

it('transforms collection of api rows', function () {
    $apiRows = collect([
        ['SS_JOBGUID' => '{1}', 'SS_WO' => '2025-1', 'REGION' => 'Central'],
        ['SS_JOBGUID' => '{2}', 'SS_WO' => '2025-2', 'REGION' => 'Lancaster'],
    ]);

    $result = $this->transformer->transformCollection($apiRows);

    expect($result)->toHaveCount(2)
        ->and($result[0]['job_guid'])->toBe('{1}')
        ->and($result[1]['job_guid'])->toBe('{2}');
});

it('extracts planner from SS_TAKENBY with valid WS username format', function () {
    $apiRow = [
        'SS_TAKENBY' => 'ASPLUNDH\\cnewcombe',
    ];

    $planners = $this->transformer->extractPlanners($apiRow);

    expect($planners)->toHaveCount(1)
        ->and($planners[0])->toBe('ASPLUNDH\\cnewcombe');
});

it('identifies split children correctly', function () {
    $parentRow = ['SS_JOBGUID' => '{1}', 'SS_WO' => '2025-1', 'SS_EXT' => '@'];
    $childRowA = ['SS_JOBGUID' => '{2}', 'SS_WO' => '2025-1', 'SS_EXT' => 'A'];
    $childRowB = ['SS_JOBGUID' => '{3}', 'SS_WO' => '2025-1', 'SS_EXT' => 'B'];

    expect($this->transformer->isSplitChild($parentRow))->toBeFalse()
        ->and($this->transformer->isSplitChild($childRowA))->toBeTrue()
        ->and($this->transformer->isSplitChild($childRowB))->toBeTrue();
});

it('gets parent work order for splits', function () {
    $childRow = ['SS_JOBGUID' => '{1}', 'SS_WO' => '2025-1234', 'SS_EXT' => 'A'];
    $parentRow = ['SS_JOBGUID' => '{2}', 'SS_WO' => '2025-1234', 'SS_EXT' => '@'];

    expect($this->transformer->getParentWorkOrder($childRow))->toBe('2025-1234')
        ->and($this->transformer->getParentWorkOrder($parentRow))->toBeNull();
});

it('stores api data json for extra fields', function () {
    $apiRow = [
        'SS_JOBGUID' => '{1}',
        'SS_WO' => '2025-1',
        'VEGJOB_SERVCOMP' => 'Distribution',
        'VEGJOB_OPCO' => 'PPL',
        'SS_READONLY' => false,
        'WSREQ_VERSION' => 27,
    ];

    $result = $this->transformer->transform($apiRow);

    expect($result['api_data_json'])->toBeArray()
        ->and($result['api_data_json']['VEGJOB_SERVCOMP'])->toBe('Distribution')
        ->and($result['api_data_json']['VEGJOB_OPCO'])->toBe('PPL')
        ->and($result['api_data_json']['WSREQ_VERSION'])->toBe(27);
});

it('clears region cache', function () {
    // Prime the cache
    $this->transformer->transform(['SS_JOBGUID' => '{1}', 'SS_WO' => '2025-1', 'REGION' => 'Central']);
    expect(Cache::has('regions.by_name'))->toBeTrue();

    // Clear it
    $this->transformer->clearRegionCache();
    expect(Cache::has('regions.by_name'))->toBeFalse();
});

it('handles missing numeric fields gracefully', function () {
    $apiRow = [
        'SS_JOBGUID' => '{1}',
        'SS_WO' => '2025-1',
        // Missing all numeric fields
    ];

    $result = $this->transformer->transform($apiRow);

    expect($result)->not->toHaveKey('total_miles')
        ->and($result)->not->toHaveKey('miles_planned')
        ->and($result)->not->toHaveKey('percent_complete');
});

it('handles non-numeric values in numeric fields', function () {
    $apiRow = [
        'SS_JOBGUID' => '{1}',
        'SS_WO' => '2025-1',
        'VEGJOB_LENGTH' => 'invalid',
        'VEGJOB_LENGTHCOMP' => null,
    ];

    $result = $this->transformer->transform($apiRow);

    expect($result['total_miles'])->toEqual(0.0)
        ->and($result['miles_planned'])->toEqual(0.0);
});

it('ignores SS_TAKENBY without valid WS username format', function () {
    // Display names without backslash should be ignored
    $apiRow = [
        'SS_TAKENBY' => 'Chris Newcombe', // Display name, not WS username
    ];

    $planners = $this->transformer->extractPlanners($apiRow);

    expect($planners)->toBeEmpty();
});

it('ignores SS_ASSIGNEDTO and VEGJOB_FORESTER for planner extraction', function () {
    // Only SS_TAKENBY with valid format should be used
    $apiRow = [
        'SS_ASSIGNEDTO' => 'ASPLUNDH\\other',
        'VEGJOB_FORESTER' => 'CONTRACTOR\\forester',
    ];

    $planners = $this->transformer->extractPlanners($apiRow);

    expect($planners)->toBeEmpty();
});

it('stores planner fields in api_data_json', function () {
    $apiRow = [
        'SS_JOBGUID' => '{1}',
        'SS_WO' => '2025-1',
        'SS_TAKENBY' => 'ASPLUNDH\\cnewcombe',
        'SS_ASSIGNEDTO' => 'Admin',
        'VEGJOB_FORESTER' => 'Chris Newcombe',
    ];

    $result = $this->transformer->transform($apiRow);

    expect($result['api_data_json'])->toBeArray()
        ->and($result['api_data_json']['SS_TAKENBY'])->toBe('ASPLUNDH\\cnewcombe')
        ->and($result['api_data_json']['SS_ASSIGNEDTO'])->toBe('Admin')
        ->and($result['api_data_json']['VEGJOB_FORESTER'])->toBe('Chris Newcombe');
});

it('excludes specified foresters from planner extraction', function () {
    $apiRow = [
        'SS_TAKENBY' => 'CONTRACTOR\\Derek Cinicola', // Excluded forester in WS format
    ];

    // Add the WS-formatted name to excluded list for this test
    $transformer = new CircuitTransformer;
    $reflection = new ReflectionClass($transformer);
    $prop = $reflection->getProperty('excludedForesters');
    $prop->setValue($transformer, collect(['CONTRACTOR\\Derek Cinicola']));

    $planners = $transformer->extractPlanners($apiRow);

    expect($planners)->toBeEmpty();
});

it('handles empty SS_TAKENBY gracefully', function () {
    $apiRow = [
        'SS_TAKENBY' => '',
    ];

    $planners = $this->transformer->extractPlanners($apiRow);

    expect($planners)->toBeEmpty();
});

it('validates WS username format correctly', function () {
    // Valid formats
    expect($this->transformer->isValidWsUsername('ASPLUNDH\\cnewcombe'))->toBeTrue()
        ->and($this->transformer->isValidWsUsername('CONTRACTOR\\user123'))->toBeTrue()
        ->and($this->transformer->isValidWsUsername('ABC\\xyz'))->toBeTrue();

    // Invalid formats
    expect($this->transformer->isValidWsUsername('John Doe'))->toBeFalse()
        ->and($this->transformer->isValidWsUsername('cnewcombe'))->toBeFalse()
        ->and($this->transformer->isValidWsUsername('\\username'))->toBeFalse()  // Empty contractor
        ->and($this->transformer->isValidWsUsername('CONTRACTOR\\'))->toBeFalse() // Empty username
        ->and($this->transformer->isValidWsUsername(''))->toBeFalse();
});
