<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds miles tracking to planner daily aggregates.
     * Tracks each planner's contribution to circuit miles progress per day.
     */
    public function up(): void
    {
        Schema::table('planner_daily_aggregates', function (Blueprint $table) {
            $table->decimal('miles_planned', 10, 2)->default(0)
                ->after('total_acres')
                ->comment('Miles worth of work planned by this planner today');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planner_daily_aggregates', function (Blueprint $table) {
            $table->dropColumn('miles_planned');
        });
    }
};
