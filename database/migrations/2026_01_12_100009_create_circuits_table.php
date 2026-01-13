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
            $table->string('cycle_type', 20)->nullable()->comment('Cycle type from API');

            // Metrics
            $table->decimal('total_miles', 10, 2)->default(0);
            $table->decimal('miles_planned', 10, 2)->default(0);
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->decimal('total_acres', 10, 2)->default(0);

            // Dates
            $table->date('start_date')->nullable();
            $table->date('api_modified_date')->comment('Last modified date from API');

            // API status and raw data
            $table->string('api_status', 20)->comment('ACTIV, QC, REWORK, CLOSE');
            $table->jsonb('api_data_json')->nullable()->comment('Raw API response for reference');

            // Sync tracking
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_planned_units_synced_at')->nullable();
            $table->boolean('planned_units_sync_enabled')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['region_id', 'api_status']);
            $table->index('api_modified_date');
            $table->index(['api_status', 'deleted_at']);
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
