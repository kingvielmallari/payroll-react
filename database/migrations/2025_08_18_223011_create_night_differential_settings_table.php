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
        Schema::create('night_differential_settings', function (Blueprint $table) {
            $table->id();
            $table->time('start_time')->default('22:00:00'); // 10 PM
            $table->time('end_time')->default('05:00:00'); // 5 AM
            $table->decimal('rate_multiplier', 8, 4)->default(1.10); // 10% ND
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default night differential setting
        DB::table('night_differential_settings')->insert([
            'start_time' => '22:00:00',
            'end_time' => '05:00:00',
            'rate_multiplier' => 1.10,
            'description' => 'Standard night differential (10 PM - 5 AM)',
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
        Schema::dropIfExists('night_differential_settings');
    }
};
