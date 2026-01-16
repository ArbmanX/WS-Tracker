<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('planned_units_snapshots', function (Blueprint $table) {
            $table->id();

            // Circuit relationship
            $table->foreignId('circuit_id')
                ->constrained()
                ->cascadeOnDelete();

            // Denormalized for easy querying
            $table->string('work_order', 20)->index();

            // Snapshot trigger type
            $table->string('snapshot_trigger', 30);

            // State at time of snapshot
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->string('api_status', 20)->nullable();

            // Deduplication hash (SHA-256 = 64 chars)
            $table->string('content_hash', 64)->index();

            // Quick stats without parsing JSON
            $table->unsignedInteger('unit_count')->default(0);
            $table->unsignedInteger('total_trees')->default(0);
            $table->decimal('total_linear_ft', 12, 2)->default(0);
            $table->decimal('total_acres', 10, 4)->default(0);

            // The actual snapshot data (normalized structure)
            $table->jsonb('raw_json');

            // Who triggered this snapshot (for manual snapshots)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Soft deletes - only sudo_admin can delete
            $table->softDeletes();

            // Composite index for timeline queries
            $table->index(['work_order', 'created_at']);

            // Index for checking duplicates
            $table->index(['circuit_id', 'content_hash']);

            // Index for filtering by trigger type
            $table->index(['circuit_id', 'snapshot_trigger']);
        });

        // Add GIN index for JSONB searches (PostgreSQL specific)
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX idx_snapshots_units_gin ON planned_units_snapshots USING GIN ((raw_json->\'units\'))');
            DB::statement('CREATE INDEX idx_snapshots_summary_gin ON planned_units_snapshots USING GIN ((raw_json->\'summary\'))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planned_units_snapshots');
    }
};
