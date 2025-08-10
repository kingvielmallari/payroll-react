<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pay_schedule_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Weekly, Semi Monthly, Monthly
            $table->string('code')->unique(); // weekly, semi_monthly, monthly
            $table->text('description')->nullable();
            
            // Schedule Configuration
            $table->json('cutoff_periods'); // Store period configurations
            $table->integer('pay_day_offset')->default(0); // Days after period end
            $table->enum('pay_day_type', ['fixed', 'weekday'])->default('fixed'); // Fixed date or specific weekday
            $table->integer('pay_day_weekday')->nullable(); // 1=Monday, 7=Sunday (if pay_day_type = weekday)
            
            // Holiday & Weekend Adjustments
            $table->boolean('move_if_holiday')->default(true);
            $table->boolean('move_if_weekend')->default(true);
            $table->enum('move_direction', ['before', 'after'])->default('before'); // Move before or after holiday/weekend
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_default')->default(false); // Cannot be deleted
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_schedule_settings');
    }
};
