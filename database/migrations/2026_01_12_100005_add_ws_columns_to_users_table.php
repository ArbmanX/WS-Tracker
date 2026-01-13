<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Extends users table with WorkStudio-specific columns.
     * ws_user_guid links to WorkStudio's user identifier.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ws_user_guid')->nullable()->after('email')->comment('WorkStudio user GUID');
            $table->string('ws_username')->nullable()->after('ws_user_guid')->comment('WorkStudio username');
            $table->boolean('is_ws_linked')->default(false)->after('ws_username')->comment('Has active WS link');
            $table->timestamp('ws_linked_at')->nullable()->after('is_ws_linked');
            $table->foreignId('default_region_id')
                ->nullable()
                ->after('ws_linked_at')
                ->constrained('regions')
                ->nullOnDelete();

            $table->index('ws_user_guid');
            $table->index('ws_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_region_id']);
            $table->dropIndex(['ws_user_guid']);
            $table->dropIndex(['ws_username']);
            $table->dropColumn([
                'ws_user_guid',
                'ws_username',
                'is_ws_linked',
                'ws_linked_at',
                'default_region_id',
            ]);
        });
    }
};
