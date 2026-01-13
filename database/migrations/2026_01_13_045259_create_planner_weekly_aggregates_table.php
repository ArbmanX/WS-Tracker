<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Weekly planner productivity rollups.
     * Work week is Saturday to Saturday (week_ending is always a Saturday).
     * Aggregates computed from planner_daily_aggregates.
     */
    public function up(): void
    {
        Schema::create('planner_weekly_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->date('week_ending')->comment('Saturday date that ends this work week');
            $table->date('week_starting')->comment('Sunday date that starts this work week');

            // Activity metrics
            $table->integer('days_worked')->default(0)->comment('Number of days with activity');
            $table->integer('circuits_worked')->default(0)->comment('Distinct circuits with work');
            $table->integer('total_units_assessed')->default(0);

            // Volume metrics
            $table->decimal('total_linear_ft', 12, 2)->default(0);
            $table->decimal('total_acres', 12, 4)->default(0);
            $table->integer('total_trees')->default(0);
            $table->decimal('miles_planned', 10, 2)->default(0);

            // Permission results
            $table->integer('units_approved')->default(0);
            $table->integer('units_refused')->default(0);
            $table->integer('units_pending')->default(0);

            // Breakdown by unit type
            $table->jsonb('unit_counts_by_type')->nullable()
                ->comment('{"SPM": 12, "HCB": 5, ...}');

            // Daily breakdown for the week
            $table->jsonb('daily_breakdown')->nullable()
                ->comment('[{"date": "2026-01-06", "units": 20}, ...]');

            $table->timestamps();

            $table->unique(['user_id', 'week_ending']);
            $table->index('week_ending');
            $table->index(['region_id', 'week_ending']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planner_weekly_aggregates');
    }
};
