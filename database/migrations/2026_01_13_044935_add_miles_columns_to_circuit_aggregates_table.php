<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds miles tracking to circuit daily aggregates.
     * Captures point-in-time snapshot of circuit miles progress each day.
     */
    public function up(): void
    {
        Schema::table('circuit_aggregates', function (Blueprint $table) {
            $table->decimal('total_miles', 10, 2)->default(0)
                ->after('is_rollup')
                ->comment('Circuit total miles (snapshot from circuits table)');
            $table->decimal('miles_planned', 10, 2)->default(0)
                ->after('total_miles')
                ->comment('Miles planned as of this date');
            $table->decimal('miles_remaining', 10, 2)->default(0)
                ->after('miles_planned')
                ->comment('Miles remaining (total - planned)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circuit_aggregates', function (Blueprint $table) {
            $table->dropColumn(['total_miles', 'miles_planned', 'miles_remaining']);
        });
    }
};
