<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Unit types represent the different vegetation work units in WorkStudio.
     * Categories determine the measurement type:
     * - VLG: linear_ft (line trimming)
     * - VAR: acres (brush/herbicide)
     * - VCT: tree_count (tree removals)
     * - VNW/VSA: none (no work/flags)
     */
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('WorkStudio unit code (e.g., SPM, HCB)');
            $table->string('name', 100)->comment('Full description');
            $table->string('category', 10)->comment('VLG, VAR, VCT, VNW, VSA');
            $table->string('measurement_type', 20)->comment('linear_ft, acres, tree_count, none');
            $table->decimal('dbh_min', 5, 1)->nullable()->comment('Min DBH inches for tree removals');
            $table->decimal('dbh_max', 5, 1)->nullable()->comment('Max DBH inches for tree removals');
            $table->string('species', 50)->nullable()->comment('Tree species if applicable (e.g., ash)');
            $table->integer('sort_order')->default(0)->comment('Display order in UI');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for common queries
            $table->index('category');
            $table->index('measurement_type');
            $table->index(['category', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
