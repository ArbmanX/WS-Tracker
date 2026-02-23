<?php

use App\Models\ApiStatusConfig;
use App\Models\Circuit;
use App\Models\PermissionStatus;
use App\Models\Region;
use App\Models\UnitType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function migrationTestDriver(): string
{
    return DB::connection()->getDriverName();
}

it('creates all required tables', function () {
    $tables = [
        'users',
        'regions',
        'permission_statuses',
        'api_status_configs',
        'unit_types',
        'user_ws_credentials',
        'user_regions',
        'unlinked_planners',
        'circuits',
        'circuit_ui_states',
        'circuit_user',
        'circuit_snapshots',
        'circuit_aggregates',
        'planner_daily_aggregates',
        'regional_daily_aggregates',
        'sync_logs',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Table {$table} should exist");
    }
});

it('creates postgresql enum types', function () {
    if (migrationTestDriver() === 'pgsql') {
        $enums = [
            'workflow_stage',
            'assignment_source',
            'snapshot_type',
            'sync_type',
            'sync_status',
            'sync_trigger',
        ];

        foreach ($enums as $enum) {
            $exists = DB::select('SELECT EXISTS (SELECT 1 FROM pg_type WHERE typname = ?)', [$enum]);
            expect($exists[0]->exists)->toBeTrue("Enum {$enum} should exist");
        }

        return;
    }

    // Non-PostgreSQL fallback: verify enum-backed columns exist.
    expect(Schema::hasColumns('circuit_ui_states', ['workflow_stage']))->toBeTrue();
    expect(Schema::hasColumns('circuit_user', ['assignment_source']))->toBeTrue();
    expect(Schema::hasColumns('circuit_snapshots', ['snapshot_type']))->toBeTrue();
    expect(Schema::hasColumns('sync_logs', ['sync_type', 'sync_status', 'sync_trigger']))->toBeTrue();
});

it('seeds all reference data', function () {
    $this->seed();

    expect(Region::count())->toBe(4)
        ->and(PermissionStatus::count())->toBeGreaterThanOrEqual(3)
        ->and(ApiStatusConfig::count())->toBe(4)
        ->and(UnitType::count())->toBeGreaterThan(40);
});

it('creates correct indexes on circuits table', function () {
    $driver = migrationTestDriver();

    if ($driver === 'pgsql') {
        $indexes = DB::select("
            SELECT indexname FROM pg_indexes
            WHERE tablename = 'circuits'
            AND schemaname = 'public'
        ");
        $indexNames = collect($indexes)->pluck('indexname')->toArray();
    } elseif ($driver === 'sqlite') {
        $indexes = DB::select("PRAGMA index_list('circuits')");
        $indexNames = collect($indexes)->pluck('name')->toArray();
    } else {
        $indexes = DB::select("SHOW INDEX FROM circuits");
        $indexNames = collect($indexes)->pluck('Key_name')->toArray();
    }

    // Check for expected indexes (names may vary)
    expect($indexNames)->toContain('circuits_job_guid_unique')
        ->and($indexNames)->toContain('circuits_work_order_index');
});

it('creates correct foreign keys on circuits table', function () {
    $driver = migrationTestDriver();

    if ($driver === 'sqlite') {
        $foreignKeys = DB::select("PRAGMA foreign_key_list('circuits')");
        $fkColumns = collect($foreignKeys)->pluck('from')->toArray();
    } else {
        $foreignKeys = DB::select("
            SELECT
                tc.constraint_name,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_name = 'circuits'
        ");

        $fkColumns = collect($foreignKeys)->pluck('column_name')->toArray();
    }

    expect($fkColumns)->toContain('region_id')
        ->and($fkColumns)->toContain('parent_circuit_id');
});

it('creates circuit_aggregates with jsonb columns', function () {
    $jsonColumns = ['unit_counts_by_type', 'linear_ft_by_type', 'acres_by_type', 'planner_distribution'];

    expect(Schema::hasColumns('circuit_aggregates', $jsonColumns))->toBeTrue();

    if (migrationTestDriver() === 'pgsql') {
        $columns = DB::select("
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_name = 'circuit_aggregates'
            AND data_type = 'jsonb'
        ");
        $typedColumns = collect($columns)->pluck('column_name')->toArray();

        expect($typedColumns)->toContain('unit_counts_by_type')
            ->and($typedColumns)->toContain('linear_ft_by_type')
            ->and($typedColumns)->toContain('acres_by_type')
            ->and($typedColumns)->toContain('planner_distribution');
    }
});

it('can create and query circuits with relationships', function () {
    $this->seed(\Database\Seeders\RegionsSeeder::class);

    $region = Region::first();
    $circuit = Circuit::factory()->forRegion($region)->create();

    expect($circuit)->toBeInstanceOf(Circuit::class)
        ->and($circuit->region->id)->toBe($region->id)
        ->and(Circuit::where('region_id', $region->id)->count())->toBe(1);
});

it('enforces unique constraints correctly', function () {
    $this->seed(\Database\Seeders\RegionsSeeder::class);

    $region = Region::first();
    $circuit = Circuit::factory()->forRegion($region)->create();

    // Attempting to create circuit with same job_guid should fail
    expect(fn () => Circuit::factory()->create([
        'job_guid' => $circuit->job_guid,
        'region_id' => $region->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
