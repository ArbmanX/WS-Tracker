<?php

use App\Models\ApiStatusConfig;
use App\Models\Circuit;
use App\Models\PermissionStatus;
use App\Models\Region;
use App\Models\UnitType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
})->skip(fn () => DB::connection()->getDriverName() !== 'pgsql', 'PostgreSQL-specific test');

it('seeds all reference data', function () {
    $this->seed();

    expect(Region::count())->toBe(4)
        ->and(PermissionStatus::count())->toBeGreaterThanOrEqual(3)
        ->and(ApiStatusConfig::count())->toBe(4)
        ->and(UnitType::count())->toBeGreaterThan(40);
});

it('creates correct indexes on circuits table', function () {
    $indexes = DB::select("
        SELECT indexname FROM pg_indexes
        WHERE tablename = 'circuits'
        AND schemaname = 'public'
    ");

    $indexNames = collect($indexes)->pluck('indexname')->toArray();

    // Check for expected indexes (names may vary)
    expect($indexNames)->toContain('circuits_job_guid_unique')
        ->and($indexNames)->toContain('circuits_work_order_index');
})->skip(fn () => DB::connection()->getDriverName() !== 'pgsql', 'PostgreSQL-specific test');

it('creates correct foreign keys on circuits table', function () {
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

    expect($fkColumns)->toContain('region_id')
        ->and($fkColumns)->toContain('parent_circuit_id');
})->skip(fn () => DB::connection()->getDriverName() !== 'pgsql', 'PostgreSQL-specific test');

it('creates circuit_aggregates with jsonb columns', function () {
    $columns = DB::select("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'circuit_aggregates'
        AND data_type = 'jsonb'
    ");

    $jsonbColumns = collect($columns)->pluck('column_name')->toArray();

    expect($jsonbColumns)->toContain('unit_counts_by_type')
        ->and($jsonbColumns)->toContain('linear_ft_by_type')
        ->and($jsonbColumns)->toContain('acres_by_type')
        ->and($jsonbColumns)->toContain('planner_distribution');
})->skip(fn () => DB::connection()->getDriverName() !== 'pgsql', 'PostgreSQL-specific test');

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
