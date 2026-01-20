<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope_year', 4)->default(date('Y'));
            $table->jsonb('selected_cycle_types')->nullable(); // null = all types
            $table->jsonb('selected_contractors')->nullable(); // null = all contractors
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Insert default settings row (singleton pattern)
        DB::table('analytics_settings')->insert([
            'scope_year' => date('Y'),
            'selected_cycle_types' => null,
            'selected_contractors' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_settings');
    }
};
