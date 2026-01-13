<?php

use App\Models\UnitType;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Seed unit types for each test
    $this->seed(\Database\Seeders\UnitTypesSeeder::class);
});

it('finds unit types by code', function () {
    $unitType = UnitType::findByCode('SPM');

    expect($unitType)->toBeInstanceOf(UnitType::class)
        ->and($unitType->code)->toBe('SPM')
        ->and($unitType->category)->toBe('VLG');
});

it('returns null for invalid code', function () {
    $unitType = UnitType::findByCode('INVALID');

    expect($unitType)->toBeNull();
});

it('caches unit types by code', function () {
    // Clear cache
    UnitType::clearCache();

    // First call populates cache
    UnitType::findByCode('SPM');

    // Verify cache was set
    expect(Cache::has('unit_types.by_code'))->toBeTrue();

    // Second call should use cache
    $unitType = UnitType::findByCode('HCB');
    expect($unitType->code)->toBe('HCB');
});

it('returns codes for category', function () {
    $vlgCodes = UnitType::codesForCategory('VLG');
    $varCodes = UnitType::codesForCategory('VAR');
    $vctCodes = UnitType::codesForCategory('VCT');

    expect($vlgCodes)->toBeArray()
        ->and($vlgCodes)->toContain('SPM', 'MPM', 'SPB')
        ->and($varCodes)->toBeArray()
        ->and($varCodes)->toContain('HCB', 'BRUSH')
        ->and($vctCodes)->toBeArray()
        ->and($vctCodes)->toContain('REM612', 'REM1218');
});

it('returns codes for measurement type', function () {
    $linearFtCodes = UnitType::codesForMeasurement('linear_ft');
    $acresCodes = UnitType::codesForMeasurement('acres');
    $treeCodes = UnitType::codesForMeasurement('tree_count');

    expect($linearFtCodes)->toBeArray()
        ->and($linearFtCodes)->not->toBeEmpty()
        ->and($acresCodes)->toBeArray()
        ->and($acresCodes)->not->toBeEmpty()
        ->and($treeCodes)->toBeArray()
        ->and($treeCodes)->not->toBeEmpty();
});

it('scopes to line trimming units', function () {
    $lineUnits = UnitType::lineTrimming()->get();

    expect($lineUnits)->each(fn ($unit) => $unit->category->toBe('VLG'));
});

it('scopes to brush area units', function () {
    $brushUnits = UnitType::brushArea()->get();

    expect($brushUnits)->each(fn ($unit) => $unit->category->toBe('VAR'));
});

it('scopes to tree removal units', function () {
    $treeUnits = UnitType::treeRemoval()->get();

    expect($treeUnits)->each(fn ($unit) => $unit->category->toBe('VCT'));
});

it('scopes to work units excluding no-work', function () {
    $workUnits = UnitType::workUnits()->get();

    $noWorkCodes = ['NW', 'NOT', 'SENSI'];
    expect($workUnits)->each(
        fn ($unit) => $unit->code->not->toBeIn($noWorkCodes)
    );
});

it('returns aggregation groups for dashboard', function () {
    $groups = UnitType::aggregationGroups();

    expect($groups)->toBeArray()
        ->and($groups)->toHaveKeys(['trim_line', 'brush_area', 'tree_removal']);
});
