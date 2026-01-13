<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Daily productivity aggregates per planner.
     * Tracks what each planner accomplished across all circuits per day.
     */
    public function up(): void
    {
        Schema::create('planner_daily_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->date('aggregate_date');

            // Activity metrics
            $table->integer('circuits_worked')->default(0)->comment('Distinct circuits with work');
            $table->integer('total_units_assessed')->default(0);

            // Volume metrics
            $table->decimal('total_linear_ft', 12, 2)->default(0);
            $table->decimal('total_acres', 12, 4)->default(0);
            $table->integer('total_trees')->default(0);

            // Permission results
            $table->integer('units_approved')->default(0);
            $table->integer('units_refused')->default(0);
            $table->integer('units_pending')->default(0);

            // Breakdown by unit type
            $table->jsonb('unit_counts_by_type')->nullable()
                ->comment('{"SPM": 12, "HCB": 5, ...}');

            // Which circuits contributed
            $table->jsonb('circuit_breakdown')->nullable()
                ->comment('[{"circuit_id": 1, "units": 20}, ...]');

            $table->timestamps();

            $table->unique(['user_id', 'aggregate_date']);
            $table->index('aggregate_date');
            $table->index(['region_id', 'aggregate_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planner_daily_aggregates');
    }
};
