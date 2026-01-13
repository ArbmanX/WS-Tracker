<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Daily regional rollups for high-level dashboards.
     * Pre-computed totals per region per day.
     */
    public function up(): void
    {
        Schema::create('regional_daily_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->date('aggregate_date');

            // Circuit counts by status
            $table->integer('active_circuits')->default(0);
            $table->integer('qc_circuits')->default(0);
            $table->integer('closed_circuits')->default(0);
            $table->integer('total_circuits')->default(0);

            // Progress metrics
            $table->decimal('total_miles', 12, 2)->default(0);
            $table->decimal('miles_planned', 12, 2)->default(0);
            $table->decimal('avg_percent_complete', 5, 2)->default(0);

            // Volume metrics
            $table->integer('total_units')->default(0);
            $table->decimal('total_linear_ft', 12, 2)->default(0);
            $table->decimal('total_acres', 12, 4)->default(0);
            $table->integer('total_trees')->default(0);

            // Permission summary
            $table->integer('units_approved')->default(0);
            $table->integer('units_refused')->default(0);
            $table->integer('units_pending')->default(0);

            // Planner activity
            $table->integer('active_planners')->default(0);

            // Detailed breakdowns
            $table->jsonb('unit_counts_by_type')->nullable();
            $table->jsonb('status_breakdown')->nullable()
                ->comment('{"ACTIV": 10, "QC": 3, ...}');

            $table->timestamps();

            $table->unique(['region_id', 'aggregate_date']);
            $table->index('aggregate_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regional_daily_aggregates');
    }
};
