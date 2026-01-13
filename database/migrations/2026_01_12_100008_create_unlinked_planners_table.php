<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks planners from WorkStudio API that aren't linked to system users.
     * Admins can manually link these to user accounts for proper attribution.
     */
    public function up(): void
    {
        Schema::create('unlinked_planners', function (Blueprint $table) {
            $table->id();
            $table->string('ws_user_guid')->unique()->comment('WorkStudio user GUID');
            $table->string('ws_username')->comment('WorkStudio username');
            $table->string('display_name')->nullable()->comment('Name from API if available');
            $table->integer('circuit_count')->default(0)->comment('Number of circuits with work');
            $table->timestamp('first_seen_at')->comment('When first encountered in sync');
            $table->timestamp('last_seen_at')->comment('Most recent sync appearance');
            $table->foreignId('linked_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User account if manually linked');
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->index('ws_username');
            $table->index('linked_to_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unlinked_planners');
    }
};
