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
        Schema::table('circuits', function (Blueprint $table) {
            // Index for the "needs sync" query pattern in SyncCircuitAggregatesJob
            // Filters by: planned_units_sync_enabled=true, is_excluded=false, api_status IN (...), last_planned_units_synced_at < X
            $table->index('last_planned_units_synced_at', 'circuits_last_pu_synced_at_idx');
            $table->index(['planned_units_sync_enabled', 'is_excluded', 'api_status'], 'circuits_sync_filter_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circuits', function (Blueprint $table) {
            $table->dropIndex('circuits_last_pu_synced_at_idx');
            $table->dropIndex('circuits_sync_filter_idx');
        });
    }
};
