<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Logs all sync operations for debugging and monitoring.
     * Tracks what was synced, how long it took, and any errors.
     */
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type', 20);
            $table->string('sync_status', 20)->default('started');
            $table->string('sync_trigger', 20)->default('scheduled');

            // Scope of sync
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('api_status_filter', 50)->nullable()->comment('ACTIV, QC, etc.');

            // Timing
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            // Results
            $table->integer('circuits_processed')->default(0);
            $table->integer('circuits_created')->default(0);
            $table->integer('circuits_updated')->default(0);
            $table->integer('aggregates_created')->default(0);

            // Error tracking
            $table->text('error_message')->nullable();
            $table->jsonb('error_details')->nullable();

            // Who triggered (null for scheduled)
            $table->foreignId('triggered_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Full context for debugging
            $table->jsonb('context_json')->nullable()
                ->comment('API params, filters, etc.');

            $table->timestamps();

            $table->index('sync_type');
            $table->index('sync_status');
            $table->index('started_at');
            $table->index(['sync_type', 'started_at']);
        });

        // Add check constraints (PostgreSQL only)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE sync_logs
                ADD CONSTRAINT sync_logs_sync_type_check
                CHECK (sync_type IN ('circuit_list', 'aggregates', 'full'))
            ");

            DB::statement("
                ALTER TABLE sync_logs
                ADD CONSTRAINT sync_logs_sync_status_check
                CHECK (sync_status IN ('started', 'completed', 'failed', 'warning'))
            ");

            DB::statement("
                ALTER TABLE sync_logs
                ADD CONSTRAINT sync_logs_sync_trigger_check
                CHECK (sync_trigger IN ('scheduled', 'manual', 'workflow_event'))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
