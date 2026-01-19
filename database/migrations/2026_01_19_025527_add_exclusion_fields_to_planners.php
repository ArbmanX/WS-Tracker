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
        // Add exclusion fields to users table (for linked planners)
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_excluded_from_analytics')->default(false)->after('dashboard_preferences');
            $table->string('exclusion_reason')->nullable()->after('is_excluded_from_analytics');
            $table->foreignId('excluded_by')->nullable()->constrained('users')->nullOnDelete()->after('exclusion_reason');
            $table->timestamp('excluded_at')->nullable()->after('excluded_by');

            $table->index('is_excluded_from_analytics');
        });

        // Add exclusion fields to unlinked_planners table
        Schema::table('unlinked_planners', function (Blueprint $table) {
            $table->boolean('is_excluded_from_analytics')->default(false)->after('linked_at');
            $table->string('exclusion_reason')->nullable()->after('is_excluded_from_analytics');
            $table->foreignId('excluded_by')->nullable()->constrained('users')->nullOnDelete()->after('exclusion_reason');
            $table->timestamp('excluded_at')->nullable()->after('excluded_by');

            $table->index('is_excluded_from_analytics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_excluded_from_analytics']);
            $table->dropConstrainedForeignId('excluded_by');
            $table->dropColumn(['is_excluded_from_analytics', 'exclusion_reason', 'excluded_at']);
        });

        Schema::table('unlinked_planners', function (Blueprint $table) {
            $table->dropIndex(['is_excluded_from_analytics']);
            $table->dropConstrainedForeignId('excluded_by');
            $table->dropColumn(['is_excluded_from_analytics', 'exclusion_reason', 'excluded_at']);
        });
    }
};
