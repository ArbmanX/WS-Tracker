<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Core domain table for vegetation assessment circuits.
     * Each circuit represents a work order from WorkStudio.
     * Split assessments have parent_circuit_id pointing to the original.
     */
    public function up(): void
    {
        Schema::create('circuits', function (Blueprint $table) {
            $table->id();
            $table->string('job_guid')->unique()->comment('WorkStudio Job GUID');
            $table->string('work_order', 20)->index()->comment('e.g., 2025-1930');
            $table->string('extension', 5)->nullable()->comment('@ for main, A/B/C for splits');
            $table->foreignId('parent_circuit_id')
                ->nullable()
                ->constrained('circuits')
                ->nullOnDelete()
                ->comment('Parent circuit for split assessments');
            $table->foreignId('region_id')->constrained();
            $table->string('title')->comment('Full circuit/line name');
            $table->string('contractor', 50)->nullable()->comment('Assigned contractor');
            $table->string('taken_by', 100)->nullable()->comment('Planner identifier from SS_TAKENBY (format: CONTRACTOR\\username)');
            $table->string('cycle_type', 100)->nullable()->comment('Cycle type from API');

            // Metrics
            $table->decimal('total_miles', 10, 2)->default(0);
            $table->decimal('miles_planned', 10, 2)->default(0);
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->decimal('total_acres', 10, 2)->default(0);

            // Dates
            $table->date('start_date')->nullable();
            $table->timestamp('api_modified_at')->nullable()->comment('Last modified timestamp from API (full precision)');

            // API status and raw data
            $table->string('api_status', 20)->comment('ACTIV, QC, REWORK, CLOSE');
            $table->jsonb('api_data_json')->nullable()->comment('Raw API response for reference');

            // WorkStudio version tracking for change/staleness detection
            $table->integer('ws_version')->nullable()->default(0)->comment('WSREQ_VERSION from API');
            $table->integer('ws_sync_version')->nullable()->default(0)->comment('WSREQ_SYNCHVERSN from API');

            // User modification tracking for smart sync
            // Format: {"field_name": {"modified_at": "...", "modified_by": 123, "original_value": "..."}}
            $table->jsonb('user_modified_fields')->nullable()->comment('Tracks user-modified fields');
            $table->timestamp('last_user_modified_at')->nullable();
            $table->foreignId('last_user_modified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Sync tracking
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_planned_units_synced_at')->nullable();
            $table->boolean('planned_units_sync_enabled')->default(true);

            // Exclusion tracking
            $table->boolean('is_excluded')->default(false)
                ->comment('User-set flag to exclude from reporting/aggregates');
            $table->string('exclusion_reason')->nullable()
                ->comment('Optional reason why circuit is excluded');
            $table->foreignId('excluded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who marked circuit as excluded');
            $table->timestamp('excluded_at')->nullable()
                ->comment('When circuit was marked as excluded');

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['region_id', 'api_status']);
            $table->index('api_modified_at');
            $table->index(['api_status', 'deleted_at']);
            $table->index('last_user_modified_at');
            $table->index('is_excluded');

            // Planner analytics indexes
            $table->index('taken_by');
            $table->index(['taken_by', 'api_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuits');
    }
};
