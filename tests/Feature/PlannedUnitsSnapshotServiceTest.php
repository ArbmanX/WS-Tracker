<?php

use App\Enums\SnapshotTrigger;
use App\Models\Circuit;
use App\Models\PlannedUnitsSnapshot;
use App\Models\User;
use App\Services\WorkStudio\PlannedUnitsSnapshotService;
use App\Services\WorkStudio\Transformers\PlannedUnitsNormalizer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->normalizer = new PlannedUnitsNormalizer;
    $this->service = new PlannedUnitsSnapshotService($this->normalizer);
});

describe('PlannedUnitsNormalizer', function () {
    it('normalizes raw API data into expected structure', function () {
        $rawData = collect([
            [
                'VEGJOB_REGION' => 'Lehigh',
                'VEGJOB_CYCLETYPE' => 'Cycle Maintenance - Trim',
                'SS_WO' => '2025-1234',
                'SS_EXT' => '@',
                'VEGSTAT_LINENAME' => 'TEST LINE',
                'VEGUNIT_FORESTER' => 'John Doe',
                'VEGJOB_CONTRACTOR' => 'Asplundh',
                'SSUNITS_OBJECTID' => '{TEST-GUID-1}',
                'STATIONS_STATNAME' => '110',
                'VEGUNIT_UNIT' => 'SPM',
                'UNITS_DESCRIPTIO' => 'Single Phase Manual',
                'VEGUNIT_PERMSTAT' => 'Approved',
                'JOBVEGETATIONUNITS_NUMTREES' => 5,
                'JOBVEGETATIONUNITS_LENGTHWRK' => 123.45,
                'JOBVEGETATIONUNITS_ACRES' => 0.5,
                'VEGUNIT_ASSLAT' => 40.123,
                'VEGUNIT_ASSLONG' => -75.456,
            ],
        ]);

        $result = $this->normalizer->normalize($rawData);

        expect($result)->toHaveKeys(['meta', 'summary', 'units'])
            ->and($result['meta'])->toHaveKeys(['region', 'cycle_type', 'forester', 'captured_at'])
            ->and($result['meta']['region'])->toBe('Lehigh')
            ->and($result['summary']['total_units'])->toBe(1)
            ->and($result['summary']['total_trees'])->toBe(5)
            ->and($result['units'])->toHaveCount(1)
            ->and($result['units'][0]['type'])->toBe('SPM')
            ->and($result['units'][0]['permission'])->toBe('Approved');
    });

    it('excludes geometry from normalized output', function () {
        $rawData = collect([
            [
                'SSUNITS_OBJECTID' => '{TEST-GUID}',
                'STATIONS_STATNAME' => '110',
                'VEGUNIT_UNIT' => 'SPM',
                'SSUNITS_GEOMETRY' => ['type' => 'LineString', 'coordinates' => [[1, 2], [3, 4]]],
            ],
        ]);

        $result = $this->normalizer->normalize($rawData);

        expect($result['units'][0])->not->toHaveKey('geometry');
    });

    it('generates consistent hash for same content', function () {
        $data1 = ['units' => [['id' => 'a', 'type' => 'SPM'], ['id' => 'b', 'type' => 'HCB']]];
        $data2 = ['units' => [['id' => 'b', 'type' => 'HCB'], ['id' => 'a', 'type' => 'SPM']]];

        $hash1 = $this->normalizer->generateHash($data1);
        $hash2 = $this->normalizer->generateHash($data2);

        // Hashes should be the same regardless of unit order
        expect($hash1)->toBe($hash2);
    });

    it('computes summary statistics correctly', function () {
        $rawData = collect([
            [
                'SSUNITS_OBJECTID' => '{1}',
                'VEGUNIT_PERMSTAT' => 'Approved',
                'VEGUNIT_UNIT' => 'SPM',
                'JOBVEGETATIONUNITS_NUMTREES' => 10,
                'JOBVEGETATIONUNITS_LENGTHWRK' => 100,
            ],
            [
                'SSUNITS_OBJECTID' => '{2}',
                'VEGUNIT_PERMSTAT' => 'Denied',
                'VEGUNIT_UNIT' => 'HCB',
                'JOBVEGETATIONUNITS_NUMTREES' => 5,
                'JOBVEGETATIONUNITS_LENGTHWRK' => 50,
            ],
            [
                'SSUNITS_OBJECTID' => '{3}',
                'VEGUNIT_PERMSTAT' => 'Approved',
                'VEGUNIT_UNIT' => 'SPM',
                'JOBVEGETATIONUNITS_NUMTREES' => 3,
                'JOBVEGETATIONUNITS_LENGTHWRK' => 25,
            ],
        ]);

        $result = $this->normalizer->normalize($rawData);

        expect($result['summary']['total_units'])->toBe(3)
            ->and($result['summary']['total_trees'])->toBe(18)
            ->and($result['summary']['total_linear_ft'])->toBe(175.0)
            ->and($result['summary']['by_permission'])->toBe(['Approved' => 2, 'Denied' => 1])
            ->and($result['summary']['by_unit_type'])->toBe(['SPM' => 2, 'HCB' => 1]);
    });
});

describe('PlannedUnitsSnapshotService', function () {
    it('creates snapshot for circuit with data', function () {
        $circuit = Circuit::factory()->create([
            'percent_complete' => 50,
            'api_status' => 'ACTIV',
        ]);

        $rawData = collect([
            [
                'SSUNITS_OBJECTID' => '{TEST-GUID}',
                'STATIONS_STATNAME' => '110',
                'VEGUNIT_UNIT' => 'SPM',
                'VEGUNIT_PERMSTAT' => 'Approved',
                'JOBVEGETATIONUNITS_NUMTREES' => 5,
            ],
        ]);

        $snapshot = $this->service->createSnapshot(
            $circuit,
            $rawData,
            SnapshotTrigger::Scheduled
        );

        expect($snapshot)->not->toBeNull()
            ->and($snapshot->circuit_id)->toBe($circuit->id)
            ->and($snapshot->work_order)->toBe($circuit->work_order)
            ->and($snapshot->snapshot_trigger)->toBe(SnapshotTrigger::Scheduled)
            ->and($snapshot->unit_count)->toBe(1)
            ->and($snapshot->total_trees)->toBe(5);
    });

    it('skips snapshot for circuit at 0%', function () {
        $circuit = Circuit::factory()->create([
            'percent_complete' => 0,
        ]);

        $rawData = collect([['SSUNITS_OBJECTID' => '{TEST}']]);

        $snapshot = $this->service->createSnapshot(
            $circuit,
            $rawData,
            SnapshotTrigger::Scheduled
        );

        expect($snapshot)->toBeNull();
    });

    it('allows manual snapshot for circuit at 0%', function () {
        $circuit = Circuit::factory()->create([
            'percent_complete' => 0,
        ]);

        $rawData = collect([
            ['SSUNITS_OBJECTID' => '{TEST}', 'VEGUNIT_UNIT' => 'SPM'],
        ]);

        $snapshot = $this->service->createSnapshot(
            $circuit,
            $rawData,
            SnapshotTrigger::Manual,
            User::factory()->create()->id
        );

        expect($snapshot)->not->toBeNull()
            ->and($snapshot->snapshot_trigger)->toBe(SnapshotTrigger::Manual);
    });

    it('skips duplicate snapshot with same content', function () {
        $circuit = Circuit::factory()->create(['percent_complete' => 50]);

        $rawData = collect([
            ['SSUNITS_OBJECTID' => '{TEST}', 'VEGUNIT_UNIT' => 'SPM'],
        ]);

        // Create first snapshot
        $first = $this->service->createSnapshot($circuit, $rawData, SnapshotTrigger::Scheduled);

        // Try to create duplicate
        $duplicate = $this->service->createSnapshot($circuit, $rawData, SnapshotTrigger::Scheduled);

        expect($first)->not->toBeNull()
            ->and($duplicate)->toBeNull()
            ->and(PlannedUnitsSnapshot::where('circuit_id', $circuit->id)->count())->toBe(1);
    });

    it('creates new snapshot when content changes', function () {
        $circuit = Circuit::factory()->create(['percent_complete' => 50]);

        // First snapshot
        $rawData1 = collect([
            ['SSUNITS_OBJECTID' => '{TEST-1}', 'VEGUNIT_UNIT' => 'SPM'],
        ]);
        $first = $this->service->createSnapshot($circuit, $rawData1, SnapshotTrigger::Scheduled);

        // Second snapshot with different data
        $rawData2 = collect([
            ['SSUNITS_OBJECTID' => '{TEST-1}', 'VEGUNIT_UNIT' => 'SPM'],
            ['SSUNITS_OBJECTID' => '{TEST-2}', 'VEGUNIT_UNIT' => 'HCB'],
        ]);
        $second = $this->service->createSnapshot($circuit, $rawData2, SnapshotTrigger::Scheduled);

        expect($first)->not->toBeNull()
            ->and($second)->not->toBeNull()
            ->and($first->id)->not->toBe($second->id)
            ->and(PlannedUnitsSnapshot::where('circuit_id', $circuit->id)->count())->toBe(2);
    });

    it('detects 50% milestone correctly', function () {
        $circuit = Circuit::factory()->create(['percent_complete' => 55]);

        expect($this->service->shouldCreateMilestone50Snapshot($circuit))->toBeTrue();

        // Create a milestone snapshot
        PlannedUnitsSnapshot::factory()
            ->forCircuit($circuit)
            ->milestone50()
            ->create();

        expect($this->service->shouldCreateMilestone50Snapshot($circuit))->toBeFalse();
    });

    it('detects 100% milestone correctly', function () {
        $circuit = Circuit::factory()->create(['percent_complete' => 100]);

        expect($this->service->shouldCreateMilestone100Snapshot($circuit))->toBeTrue();

        // Create a milestone snapshot
        PlannedUnitsSnapshot::factory()
            ->forCircuit($circuit)
            ->milestone100()
            ->create();

        expect($this->service->shouldCreateMilestone100Snapshot($circuit))->toBeFalse();
    });

    it('detects QC status change correctly', function () {
        $circuit = Circuit::factory()->create(['api_status' => 'QC']);

        expect($this->service->shouldCreateQcSnapshot($circuit, 'ACTIV'))->toBeTrue()
            ->and($this->service->shouldCreateQcSnapshot($circuit, 'QC'))->toBeFalse();
    });

    it('compares snapshots and finds differences', function () {
        $circuit = Circuit::factory()->create();

        $older = PlannedUnitsSnapshot::factory()->forCircuit($circuit)->create([
            'raw_json' => [
                'meta' => [],
                'summary' => [],
                'units' => [
                    ['id' => 'a', 'type' => 'SPM', 'trees' => 5],
                    ['id' => 'b', 'type' => 'HCB', 'trees' => 3],
                ],
            ],
        ]);

        $newer = PlannedUnitsSnapshot::factory()->forCircuit($circuit)->create([
            'raw_json' => [
                'meta' => [],
                'summary' => [],
                'units' => [
                    ['id' => 'a', 'type' => 'SPM', 'trees' => 10], // changed
                    ['id' => 'c', 'type' => 'TPM', 'trees' => 2],  // added
                ],
            ],
        ]);

        $diff = $this->service->compareSnapshots($older, $newer);

        expect($diff['added'])->toHaveCount(1)
            ->and($diff['added'][0]['id'])->toBe('c')
            ->and($diff['removed'])->toHaveCount(1)
            ->and($diff['removed'][0]['id'])->toBe('b')
            ->and($diff['changed'])->toHaveCount(1)
            ->and($diff['changed'][0]['id'])->toBe('a');
    });
});

describe('PlannedUnitsSnapshot Model', function () {
    it('can filter units by permission', function () {
        $snapshot = PlannedUnitsSnapshot::factory()->create([
            'raw_json' => [
                'meta' => [],
                'summary' => [],
                'units' => [
                    ['id' => '1', 'permission' => 'Approved'],
                    ['id' => '2', 'permission' => 'Denied'],
                    ['id' => '3', 'permission' => 'Approved'],
                ],
            ],
        ]);

        $approved = $snapshot->getUnitsByPermission('Approved');
        $denied = $snapshot->getUnitsByPermission('Denied');

        expect($approved)->toHaveCount(2)
            ->and($denied)->toHaveCount(1);
    });

    it('can filter units by station', function () {
        $snapshot = PlannedUnitsSnapshot::factory()->create([
            'raw_json' => [
                'meta' => [],
                'summary' => [],
                'units' => [
                    ['id' => '1', 'station' => '110'],
                    ['id' => '2', 'station' => '120'],
                    ['id' => '3', 'station' => '110'],
                ],
            ],
        ]);

        $station110 = $snapshot->getUnitsByStation('110');

        expect($station110)->toHaveCount(2);
    });

    it('uses soft deletes', function () {
        $snapshot = PlannedUnitsSnapshot::factory()->create();

        $snapshot->delete();

        expect($snapshot->trashed())->toBeTrue()
            ->and(PlannedUnitsSnapshot::count())->toBe(0)
            ->and(PlannedUnitsSnapshot::withTrashed()->count())->toBe(1);
    });
});

describe('PlannedUnitsSnapshotPolicy', function () {
    beforeEach(function () {
        $this->seed(RolesAndPermissionsSeeder::class);
    });

    it('allows any authenticated user to view snapshots', function () {
        $user = User::factory()->create();
        $snapshot = PlannedUnitsSnapshot::factory()->create();

        expect($user->can('view', $snapshot))->toBeTrue()
            ->and($user->can('viewAny', PlannedUnitsSnapshot::class))->toBeTrue();
    });

    it('only allows admins to create manual snapshots', function () {
        $regularUser = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        expect($regularUser->can('create', PlannedUnitsSnapshot::class))->toBeFalse()
            ->and($admin->can('create', PlannedUnitsSnapshot::class))->toBeTrue();
    });

    it('prevents all users from updating snapshots', function () {
        $sudoAdmin = User::factory()->create();
        $sudoAdmin->assignRole('sudo_admin');
        $snapshot = PlannedUnitsSnapshot::factory()->create();

        // Even sudo_admin cannot update - snapshots are immutable
        expect($sudoAdmin->can('update', $snapshot))->toBeFalse();
    });

    it('only allows sudo_admin to delete snapshots', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $sudoAdmin = User::factory()->create();
        $sudoAdmin->assignRole('sudo_admin');

        $snapshot = PlannedUnitsSnapshot::factory()->create();

        expect($admin->can('delete', $snapshot))->toBeFalse()
            ->and($sudoAdmin->can('delete', $snapshot))->toBeTrue();
    });
});
