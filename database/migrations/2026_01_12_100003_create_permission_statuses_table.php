<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Permission statuses from WorkStudio VEGUNIT_PERMSTAT field.
     * Used for aggregating permission counts per circuit.
     */
    public function up(): void
    {
        Schema::create('permission_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('Display name (e.g., Approved)');
            $table->string('code', 50)->unique()->comment('API value (may be empty string)');
            $table->string('color', 20)->default('neutral')->comment('DaisyUI color class');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_statuses');
    }
};
