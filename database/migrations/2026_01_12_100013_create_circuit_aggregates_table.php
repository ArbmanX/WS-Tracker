<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Daily aggregate snapshots per circuit.
     * This replaces storing individual planned units - we only store totals.
     * is_rollup indicates end-of-day consolidated records vs intraday updates.
     */
    public function up(): void
    {
        Schema::create('circuit_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuit_id')->constrained()->onDelete('cascade');
            $table->date('aggregate_date');
            $table->boolean('is_rollup')->default(false)->comment('True for EOD consolidated record');

            // Normalized metrics for efficient querying/filtering
            $table->integer('total_units')->default(0);
            $table->decimal('total_linear_ft', 12, 2)->default(0);
            $table->decimal('total_acres', 12, 4)->default(0);
            $table->integer('total_trees')->default(0);

            // Permission status counts
            $table->integer('units_approved')->default(0);
            $table->integer('units_refused')->default(0);
            $table->integer('units_pending')->default(0);

            // JSONB for flexible breakdowns that don't need filtering
            $table->jsonb('unit_counts_by_type')->nullable()
                ->comment('{"SPM": 12, "HCB": 5, ...}');
            $table->jsonb('linear_ft_by_type')->nullable()
                ->comment('{"SPM": 1200.5, "MPM": 800.0, ...}');
            $table->jsonb('acres_by_type')->nullable()
                ->comment('{"HCB": 1.5, "BRUSH": 0.8, ...}');
            $table->jsonb('planner_distribution')->nullable()
                ->comment('{"user_id_1": {"units": 50, ...}, ...}');

            $table->timestamps();

            // Each circuit has one record per date (with is_rollup flag for multiple)
            $table->unique(['circuit_id', 'aggregate_date', 'is_rollup'], 'circuit_aggregates_unique');
            $table->index('aggregate_date');
            $table->index(['circuit_id', 'aggregate_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_aggregates');
    }
};
