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
        Schema::create('payroll_schedule_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('pay_type', ['weekly', 'semi_monthly', 'monthly'])->unique();
            $table->string('cutoff_description'); // e.g., "Monday to Sunday", "1st to 15th / 16th to 30th/31st"
            $table->integer('cutoff_start_day')->nullable(); // For weekly: 1=Mon, 7=Sun; For monthly: day of month
            $table->integer('cutoff_end_day')->nullable(); // For weekly: day of week; For monthly: day of month
            $table->integer('payday_offset_days')->default(0); // Days after cutoff end to pay (e.g., 5 for "Next Friday")
            $table->string('payday_description'); // e.g., "Next Friday", "15th & 30th/31st", "30th/31st"
            $table->text('notes')->nullable(); // Additional notes or rules
            $table->boolean('is_active')->default(true);
            $table->json('cutoff_rules')->nullable(); // Store complex rules as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_schedule_settings');
    }
};
