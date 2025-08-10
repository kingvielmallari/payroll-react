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
        Schema::table('payroll_schedule_settings', function (Blueprint $table) {
            // Add new flexible configuration fields
            
            // Weekly configuration
            $table->string('weekly_start_day')->nullable(); // 'monday', 'tuesday', etc.
            $table->string('weekly_end_day')->nullable(); // 'friday', 'sunday', etc.
            $table->string('weekly_pay_day')->nullable(); // 'friday', 'next_monday', etc.
            
            // Semi-monthly configuration
            $table->json('semi_monthly_config')->nullable(); // Store both periods config
            
            // Monthly configuration  
            $table->integer('monthly_start_day')->default(1); // Usually 1st day
            $table->integer('monthly_end_day')->default(-1); // -1 means last day of month
            $table->integer('monthly_pay_day')->default(-1); // -1 means last day of month
            
            // Holiday/Weekend handling
            $table->enum('holiday_handling', ['before', 'after', 'same_day'])->default('before');
            $table->boolean('skip_weekends')->default(true);
            $table->boolean('skip_holidays')->default(true);
            
            // Additional configuration
            $table->json('working_days')->nullable(); // Store which days are working days
            $table->text('special_rules')->nullable(); // Any special handling rules
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_schedule_settings', function (Blueprint $table) {
            $table->dropColumn([
                'weekly_start_day',
                'weekly_end_day', 
                'weekly_pay_day',
                'semi_monthly_config',
                'monthly_start_day',
                'monthly_end_day',
                'monthly_pay_day',
                'holiday_handling',
                'skip_weekends',
                'skip_holidays',
                'working_days',
                'special_rules'
            ]);
        });
    }
};
