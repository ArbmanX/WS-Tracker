<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add credential tracking columns for WorkStudio API authentication.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('ws_credentials_fail_count')->default(0)->after('default_region_id');
            $table->timestamp('ws_credentials_last_used_at')->nullable()->after('ws_credentials_fail_count');
            $table->timestamp('ws_credentials_failed_at')->nullable()->after('ws_credentials_last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ws_credentials_fail_count',
                'ws_credentials_last_used_at',
                'ws_credentials_failed_at',
            ]);
        });
    }
};
