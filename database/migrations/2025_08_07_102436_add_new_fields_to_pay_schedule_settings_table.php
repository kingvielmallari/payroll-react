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
        Schema::table('pay_schedule_settings', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pay_schedule_settings', function (Blueprint $table) {
            $table->dropColumn([
                'pay_type',
                'cutoff_start_day',
                'cutoff_end_day', 
                'pay_day',
                'cutoff_start_day_1',
                'cutoff_end_day_1',
                'pay_day_1',
                'cutoff_start_day_2',
                'cutoff_end_day_2',
                'pay_day_2',
                'holiday_weekend_rule'
            ]);
        });
    }
};
