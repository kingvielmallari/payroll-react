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
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('payroll_frequency', ['weekly', 'bi_weekly', 'semi_monthly', 'monthly'])->default('semi_monthly');
            $table->json('payroll_periods'); // Store period configurations
            $table->integer('pay_delay_days')->default(0); // Days after period end to pay
            $table->boolean('adjust_for_weekends')->default(true); // Move pay date if weekend
            $table->boolean('adjust_for_holidays')->default(true); // Move pay date if holiday
            $table->enum('weekend_adjustment', ['before', 'after'])->default('before'); // Move to Friday or Monday
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
