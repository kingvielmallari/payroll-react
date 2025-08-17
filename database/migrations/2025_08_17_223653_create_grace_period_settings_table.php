<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grace_period_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('late_grace_minutes')->default(0)->comment('Minutes before deducting from working hours');
            $table->integer('undertime_grace_minutes')->default(0)->comment('Minutes before deducting for early time out');
            $table->integer('overtime_threshold_minutes')->default(0)->comment('Minutes before counting as overtime');
            $table->boolean('is_active')->default(true)->comment('Whether this setting is active');
            $table->timestamps();
        });

        // Insert single default settings record - all zeros for fresh install
        DB::table('grace_period_settings')->insert([
            'id' => 1,
            'late_grace_minutes' => 0,
            'undertime_grace_minutes' => 0,
            'overtime_threshold_minutes' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grace_period_settings');
    }
};
