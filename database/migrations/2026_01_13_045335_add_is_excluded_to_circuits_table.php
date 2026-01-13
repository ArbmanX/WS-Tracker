<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds exclusion flag for circuits that should be ignored in reporting.
     * Some circuits from the API are not relevant to our tracking needs.
     */
    public function up(): void
    {
        Schema::table('circuits', function (Blueprint $table) {
            $table->boolean('is_excluded')->default(false)
                ->after('planned_units_sync_enabled')
                ->comment('User-set flag to exclude from reporting/aggregates');
            $table->string('exclusion_reason')->nullable()
                ->after('is_excluded')
                ->comment('Optional reason why circuit is excluded');
            $table->foreignId('excluded_by')
                ->nullable()
                ->after('exclusion_reason')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who marked circuit as excluded');
            $table->timestamp('excluded_at')->nullable()
                ->after('excluded_by')
                ->comment('When circuit was marked as excluded');

            $table->index('is_excluded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circuits', function (Blueprint $table) {
            $table->dropIndex(['is_excluded']);
            $table->dropConstrainedForeignId('excluded_by');
            $table->dropColumn(['is_excluded', 'exclusion_reason', 'excluded_at']);
        });
    }
};
