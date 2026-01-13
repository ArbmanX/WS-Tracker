<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates PostgreSQL enum types for type-safe columns.
     * These enums mirror the PHP enums in app/Enums.
     */
    public function up(): void
    {
        // Skip for non-PostgreSQL databases (SQLite for tests)
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Workflow stages for circuits
        DB::statement("DO $$ BEGIN
            CREATE TYPE workflow_stage AS ENUM (
                'active',
                'pending_permissions',
                'qc',
                'rework',
                'closed'
            );
        EXCEPTION WHEN duplicate_object THEN NULL;
        END $$");

        // How a planner was assigned to a circuit
        DB::statement("DO $$ BEGIN
            CREATE TYPE assignment_source AS ENUM (
                'api_sync',
                'manual'
            );
        EXCEPTION WHEN duplicate_object THEN NULL;
        END $$");

        // Types of circuit snapshots
        DB::statement("DO $$ BEGIN
            CREATE TYPE snapshot_type AS ENUM (
                'daily',
                'status_change',
                'manual'
            );
        EXCEPTION WHEN duplicate_object THEN NULL;
        END $$");

        // Types of sync operations
        DB::statement("DO $$ BEGIN
            CREATE TYPE sync_type AS ENUM (
                'circuit_list',
                'aggregates',
                'full'
            );
        EXCEPTION WHEN duplicate_object THEN NULL;
        END $$");

        // Status of sync operations
        DB::statement("DO $$ BEGIN
            CREATE TYPE sync_status AS ENUM (
                'started',
                'completed',
                'failed',
                'warning'
            );
        EXCEPTION WHEN duplicate_object THEN NULL;
        END $$");

        // What triggered a sync
        DB::statement("DO $$ BEGIN
            CREATE TYPE sync_trigger AS ENUM (
                'scheduled',
                'manual',
                'workflow_event'
            );
        EXCEPTION WHEN duplicate_object THEN NULL;
        END $$");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for non-PostgreSQL databases
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TYPE IF EXISTS sync_trigger');
        DB::statement('DROP TYPE IF EXISTS sync_status');
        DB::statement('DROP TYPE IF EXISTS sync_type');
        DB::statement('DROP TYPE IF EXISTS snapshot_type');
        DB::statement('DROP TYPE IF EXISTS assignment_source');
        DB::statement('DROP TYPE IF EXISTS workflow_stage');
    }
};
