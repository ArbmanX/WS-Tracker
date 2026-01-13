<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table linking circuits to planners (users).
     * A circuit can have multiple planners, tracked from API or manual assignment.
     */
    public function up(): void
    {
        Schema::create('circuit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuit_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Assignment tracking
            $table->string('assignment_source', 20)->default('api_sync');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // For unlinked planners from API
            $table->string('ws_user_guid')->nullable()->comment('WorkStudio GUID if not yet linked');

            $table->timestamps();

            $table->unique(['circuit_id', 'user_id']);
            $table->index('assignment_source');
        });

        // Add check constraint for assignment_source (PostgreSQL only)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE circuit_user
                ADD CONSTRAINT circuit_user_assignment_source_check
                CHECK (assignment_source IN ('api_sync', 'manual'))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_user');
    }
};
