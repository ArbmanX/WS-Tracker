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
        Schema::table('planner_daily_aggregates', function (Blueprint $table) {
            $table->jsonb('circuits_list')->nullable()->after('circuit_breakdown')
                ->comment('Array of circuit IDs worked on this day');
        });

        Schema::table('regional_daily_aggregates', function (Blueprint $table) {
            $table->integer('total_planners')->default(0)->after('active_planners')
                ->comment('Total unique planners in the region');
            $table->jsonb('permission_counts')->nullable()->after('status_breakdown')
                ->comment('{"approved": 100, "refused": 10, "pending": 5}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planner_daily_aggregates', function (Blueprint $table) {
            $table->dropColumn('circuits_list');
        });

        Schema::table('regional_daily_aggregates', function (Blueprint $table) {
            $table->dropColumn(['total_planners', 'permission_counts']);
        });
    }
};
