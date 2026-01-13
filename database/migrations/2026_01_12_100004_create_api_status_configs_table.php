<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Configuration for API status values (ACTIV, QC, REWORK, CLOSE).
     * Controls sync frequency and behavior per status.
     */
    public function up(): void
    {
        Schema::create('api_status_configs', function (Blueprint $table) {
            $table->id();
            $table->string('api_status', 20)->unique()->comment('Status from WorkStudio API');
            $table->string('display_name', 50)->comment('Human-readable name');
            $table->string('sync_frequency', 20)->default('daily')->comment('daily, weekly, manual');
            $table->boolean('sync_planned_units')->default(true)->comment('Whether to fetch aggregates');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_status_configs');
    }
};
