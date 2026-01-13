<?php

use App\Services\WorkStudio\Transformers\PlannedUnitAggregateTransformer;

beforeEach(function () {
    $this->transformer = new PlannedUnitAggregateTransformer;
    $this->seed(\Database\Seeders\UnitTypesSeeder::class);
});

it('returns empty aggregate for empty collection', function () {
    $result = $this->transformer->transformToAggregate(collect());

    expect($result['total_units'])->toBe(0)
        ->and($result['total_linear_ft'])->toBe(0)
        ->and($result['total_acres'])->toBe(0)
        ->and($result['total_trees'])->toBe(0)
        ->and($result['units_approved'])->toBe(0)
        ->and($result['unit_counts_by_type'])->toBe([])
        ->and($result['planner_distribution'])->toBe([]);
});

it('calculates total units correctly', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'SPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 100],
        ['VEGUNIT_UNIT' => 'HCB', 'JOBVEGETATIONUNITS_ACRES' => 0.5],
        ['VEGUNIT_UNIT' => 'REM1218', 'JOBVEGETATIONUNITS_NUMTREES' => 5],
    ]);

    $result = $this->transformer->transformToAggregate($units);

    expect($result['total_units'])->toBe(3);
});

it('sums linear feet for VLG category units', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'SPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 100.5],
        ['VEGUNIT_UNIT' => 'MPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 50.25],
        ['VEGUNIT_UNIT' => 'HCB', 'JOBVEGETATIONUNITS_LENGTHWRK' => 0, 'JOBVEGETATIONUNITS_ACRES' => 1],
    ]);

    $result = $this->transformer->transformToAggregate($units);

    // SPM and MPM are VLG (linear ft), HCB is VAR (acres)
    expect($result['total_linear_ft'])->toBe(150.75);
});

it('sums acres for VAR category units', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'HCB', 'JOBVEGETATIONUNITS_ACRES' => 0.5],
        ['VEGUNIT_UNIT' => 'BRUSH', 'JOBVEGETATIONUNITS_ACRES' => 1.25],
        ['VEGUNIT_UNIT' => 'SPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 100],
    ]);

    $result = $this->transformer->transformToAggregate($units);

    expect($result['total_acres'])->toBe(1.75);
});

it('sums trees for VCT category units', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'REM1218', 'JOBVEGETATIONUNITS_NUMTREES' => 3],
        ['VEGUNIT_UNIT' => 'REM1824', 'JOBVEGETATIONUNITS_NUMTREES' => 5],
        ['VEGUNIT_UNIT' => 'SPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 100],
    ]);

    $result = $this->transformer->transformToAggregate($units);

    expect($result['total_trees'])->toEqual(8);
});

it('counts permission statuses correctly', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'SPM', 'VEGUNIT_PERMSTAT' => 'Approved'],
        ['VEGUNIT_UNIT' => 'MPM', 'VEGUNIT_PERMSTAT' => 'Approved'],
        ['VEGUNIT_UNIT' => 'HCB', 'VEGUNIT_PERMSTAT' => 'Refused'],
        ['VEGUNIT_UNIT' => 'REM1218', 'VEGUNIT_PERMSTAT' => 'Pending'],
        ['VEGUNIT_UNIT' => 'BRUSH', 'VEGUNIT_PERMSTAT' => ''], // Empty = pending
    ]);

    $result = $this->transformer->transformToAggregate($units);

    expect($result['units_approved'])->toBe(2)
        ->and($result['units_refused'])->toBe(1)
        ->and($result['units_pending'])->toBe(2);
});

it('builds unit counts by type', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'SPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 100],
        ['VEGUNIT_UNIT' => 'SPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 50],
        ['VEGUNIT_UNIT' => 'MPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 75],
        ['VEGUNIT_UNIT' => 'HCB', 'JOBVEGETATIONUNITS_ACRES' => 1],
    ]);

    $result = $this->transformer->transformToAggregate($units);

    expect($result['unit_counts_by_type'])->toBe([
        'SPM' => 2,
        'MPM' => 1,
        'HCB' => 1,
    ]);
});

it('builds planner distribution', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'SPM', 'VEGUNIT_FORESTER' => 'John Doe', 'JOBVEGETATIONUNITS_LENGTHWRK' => 100],
        ['VEGUNIT_UNIT' => 'MPM', 'VEGUNIT_FORESTER' => 'John Doe', 'JOBVEGETATIONUNITS_LENGTHWRK' => 50],
        ['VEGUNIT_UNIT' => 'HCB', 'VEGUNIT_FORESTER' => 'Jane Smith', 'JOBVEGETATIONUNITS_ACRES' => 0.5],
    ]);

    $result = $this->transformer->transformToAggregate($units);

    expect($result['planner_distribution'])->toHaveKey('John Doe')
        ->and($result['planner_distribution'])->toHaveKey('Jane Smith')
        ->and($result['planner_distribution']['John Doe']['unit_count'])->toBe(2)
        ->and($result['planner_distribution']['John Doe']['linear_ft'])->toBe(150.0)
        ->and($result['planner_distribution']['Jane Smith']['unit_count'])->toBe(1)
        ->and($result['planner_distribution']['Jane Smith']['acres'])->toBe(0.5);
});

it('transforms units grouped by planner', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'SPM', 'VEGUNIT_FORESTER' => 'John Doe', 'JOBVEGETATIONUNITS_LENGTHWRK' => 100, 'VEGUNIT_PERMSTAT' => 'Approved'],
        ['VEGUNIT_UNIT' => 'HCB', 'VEGUNIT_FORESTER' => 'Jane Smith', 'JOBVEGETATIONUNITS_ACRES' => 0.5, 'VEGUNIT_PERMSTAT' => 'Pending'],
    ]);

    $result = $this->transformer->transformByPlanner($units);

    expect($result)->toHaveKey('John Doe')
        ->and($result)->toHaveKey('Jane Smith')
        ->and($result['John Doe']['total_units'])->toBe(1)
        ->and($result['John Doe']['units_approved'])->toBe(1)
        ->and($result['Jane Smith']['total_units'])->toBe(1)
        ->and($result['Jane Smith']['units_pending'])->toBe(1);
});

it('extracts unique planner names', function () {
    $units = collect([
        ['VEGUNIT_FORESTER' => 'John Doe'],
        ['VEGUNIT_FORESTER' => 'Jane Smith'],
        ['VEGUNIT_FORESTER' => 'John Doe'],
        ['VEGUNIT_FORESTER' => ''],
        ['VEGUNIT_FORESTER' => null],
    ]);

    $planners = $this->transformer->extractPlannerNames($units);

    expect($planners)->toHaveCount(2)
        ->and($planners)->toContain('John Doe')
        ->and($planners)->toContain('Jane Smith');
});

it('gets work order from units collection', function () {
    $units = collect([
        ['SS_WO' => '2025-1234', 'VEGUNIT_UNIT' => 'SPM'],
        ['SS_WO' => '2025-1234', 'VEGUNIT_UNIT' => 'MPM'],
    ]);

    expect($this->transformer->getWorkOrder($units))->toBe('2025-1234');
});

it('gets extension from units collection', function () {
    $unitsMain = collect([['SS_EXT' => '@']]);
    $unitsSplit = collect([['SS_EXT' => 'A']]);
    $unitsEmpty = collect([]);

    expect($this->transformer->getExtension($unitsMain))->toBe('@')
        ->and($this->transformer->getExtension($unitsSplit))->toBe('A')
        ->and($this->transformer->getExtension($unitsEmpty))->toBe('@');
});

it('builds linear ft by type', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'SPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 100.5],
        ['VEGUNIT_UNIT' => 'SPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 50.25],
        ['VEGUNIT_UNIT' => 'MPM', 'JOBVEGETATIONUNITS_LENGTHWRK' => 75.1],
    ]);

    $result = $this->transformer->transformToAggregate($units);

    expect($result['linear_ft_by_type'])->toHaveKey('SPM')
        ->and($result['linear_ft_by_type'])->toHaveKey('MPM')
        ->and($result['linear_ft_by_type']['SPM'])->toBe(150.75)
        ->and($result['linear_ft_by_type']['MPM'])->toBe(75.1);
});

it('builds acres by type', function () {
    $units = collect([
        ['VEGUNIT_UNIT' => 'HCB', 'JOBVEGETATIONUNITS_ACRES' => 0.5],
        ['VEGUNIT_UNIT' => 'HCB', 'JOBVEGETATIONUNITS_ACRES' => 0.25],
        ['VEGUNIT_UNIT' => 'BRUSH', 'JOBVEGETATIONUNITS_ACRES' => 1.0],
    ]);

    $result = $this->transformer->transformToAggregate($units);

    expect($result['acres_by_type'])->toHaveKey('HCB')
        ->and($result['acres_by_type'])->toHaveKey('BRUSH')
        ->and($result['acres_by_type']['HCB'])->toBe(0.75)
        ->and($result['acres_by_type']['BRUSH'])->toBe(1.0);
});
