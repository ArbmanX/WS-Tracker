<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Weekly regional rollups for high-level dashboards.
     * Work week is Saturday to Saturday (week_ending is always a Saturday).
     * Aggregates computed from regional_daily_aggregates.
     * Only includes non-excluded circuits in totals.
     */
    public function up(): void
    {
        Schema::create('regional_weekly_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->date('week_ending')->comment('Saturday date that ends this work week');
            $table->date('week_starting')->comment('Sunday date that starts this work week');

            // Circuit counts by status (end of week snapshot)
            $table->integer('active_circuits')->default(0);
            $table->integer('qc_circuits')->default(0);
            $table->integer('closed_circuits')->default(0);
            $table->integer('total_circuits')->default(0);
            $table->integer('excluded_circuits')->default(0)->comment('Circuits marked as excluded');

            // Miles progress (end of week snapshot, excludes excluded circuits)
            $table->decimal('total_miles', 12, 2)->default(0);
            $table->decimal('miles_planned', 12, 2)->default(0);
            $table->decimal('miles_remaining', 12, 2)->default(0);
            $table->decimal('avg_percent_complete', 5, 2)->default(0);

            // Weekly production totals
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
            $table->integer('total_planner_days')->default(0)->comment('Sum of days worked by all planners');

            // Detailed breakdowns
            $table->jsonb('unit_counts_by_type')->nullable();
            $table->jsonb('status_breakdown')->nullable()
                ->comment('{"ACTIV": 10, "QC": 3, ...}');
            $table->jsonb('daily_breakdown')->nullable()
                ->comment('Daily totals for the week');

            $table->timestamps();

            $table->unique(['region_id', 'week_ending']);
            $table->index('week_ending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regional_weekly_aggregates');
    }
};
