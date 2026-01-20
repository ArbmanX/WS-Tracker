<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('analytics_settings', function (Blueprint $table) {
            // Sync configuration settings
            $table->boolean('planned_units_sync_enabled')->default(true)->after('selected_contractors');
            $table->integer('sync_interval_hours')->default(12)->after('planned_units_sync_enabled');
            $table->integer('aggregates_retention_days')->default(90)->after('sync_interval_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_settings', function (Blueprint $table) {
            $table->dropColumn([
                'planned_units_sync_enabled',
                'sync_interval_hours',
                'aggregates_retention_days',
            ]);
        });
    }
};
