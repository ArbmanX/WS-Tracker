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
     * Point-in-time snapshots of circuit state for historical tracking.
     * Created daily, on status changes, or manually.
     */
    public function up(): void
    {
        Schema::create('circuit_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuit_id')->constrained()->onDelete('cascade');
            $table->string('snapshot_type', 20)->default('daily');
            $table->date('snapshot_date');

            // Captured metrics at snapshot time
            $table->decimal('miles_planned', 10, 2)->default(0);
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->string('api_status', 20);
            $table->string('workflow_stage', 30);

            // Aggregate summary at this point
            $table->integer('total_units')->default(0);
            $table->decimal('total_linear_ft', 12, 2)->default(0);
            $table->decimal('total_acres', 12, 4)->default(0);
            $table->integer('total_trees')->default(0);
            $table->integer('units_approved')->default(0);
            $table->integer('units_refused')->default(0);
            $table->integer('units_pending')->default(0);

            // Full breakdown stored as JSON
            $table->jsonb('metrics_json')->nullable()->comment('Complete metrics breakdown');

            // Who triggered this snapshot (null for automated)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['circuit_id', 'snapshot_type', 'snapshot_date'], 'circuit_snapshots_unique');
            $table->index(['snapshot_date', 'snapshot_type']);
        });

        // Add check constraints (PostgreSQL only)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE circuit_snapshots
                ADD CONSTRAINT circuit_snapshots_snapshot_type_check
                CHECK (snapshot_type IN ('daily', 'status_change', 'manual'))
            ");

            DB::statement("
                ALTER TABLE circuit_snapshots
                ADD CONSTRAINT circuit_snapshots_workflow_stage_check
                CHECK (workflow_stage IN ('active', 'pending_permissions', 'qc', 'rework', 'closed'))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_snapshots');
    }
};
