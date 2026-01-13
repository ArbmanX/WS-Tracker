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
     * UI state for circuits - workflow stage, display order, etc.
     * Separate from circuits to keep API data clean.
     */
    public function up(): void
    {
        Schema::create('circuit_ui_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuit_id')->unique()->constrained()->onDelete('cascade');

            // Using native PostgreSQL enum type
            $table->string('workflow_stage', 30)->default('active');
            $table->integer('stage_position')->default(0)->comment('Order within the stage column');
            $table->timestamp('stage_changed_at')->nullable();
            $table->foreignId('stage_changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Display options
            $table->boolean('is_hidden')->default(false)->comment('Hidden from default views');
            $table->boolean('is_pinned')->default(false)->comment('Pinned to top of column');
            $table->string('custom_color', 20)->nullable()->comment('Override default color');
            $table->text('notes')->nullable()->comment('Internal notes about this circuit');

            $table->timestamps();

            $table->index(['workflow_stage', 'stage_position']);
            $table->index('is_hidden');
        });

        // Add check constraint for workflow_stage (PostgreSQL only)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE circuit_ui_states
                ADD CONSTRAINT circuit_ui_states_workflow_stage_check
                CHECK (workflow_stage IN ('active', 'pending_permissions', 'qc', 'rework', 'closed'))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_ui_states');
    }
};
