<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes unique constraint to support per-region aggregates and adds
     * delta tracking columns for the 6.5 miles/week planner target.
     */
    public function up(): void
    {
        Schema::table('planner_weekly_aggregates', function (Blueprint $table) {
            // Drop the old unique constraint that only used user_id + week_ending
            // This was causing failures when planners work in multiple regions
            $table->dropUnique(['user_id', 'week_ending']);

            // Add the correct unique constraint including region_id
            $table->unique(['user_id', 'region_id', 'week_ending'], 'planner_region_week_unique');

            // Add delta tracking columns for miles planned
            // These track weekly progress instead of just cumulative totals
            $table->decimal('miles_planned_start', 10, 2)->default(0)
                ->after('miles_planned')
                ->comment('Cumulative miles at start of week');

            $table->decimal('miles_planned_end', 10, 2)->default(0)
                ->after('miles_planned_start')
                ->comment('Cumulative miles at end of week');

            $table->decimal('miles_delta', 10, 2)->default(0)
                ->after('miles_planned_end')
                ->comment('Miles planned this week (end - start). Target: 6.5 mi/week');

            // Weekly target met flag for quick filtering/display
            $table->boolean('met_weekly_target')->default(false)
                ->after('miles_delta')
                ->comment('True if miles_delta >= 6.5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planner_weekly_aggregates', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['miles_planned_start', 'miles_planned_end', 'miles_delta', 'met_weekly_target']);

            // Restore old unique constraint
            $table->dropUnique('planner_region_week_unique');
            $table->unique(['user_id', 'week_ending']);
        });
    }
};
