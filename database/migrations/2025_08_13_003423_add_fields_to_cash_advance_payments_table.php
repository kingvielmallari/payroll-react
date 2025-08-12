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
        Schema::table('cash_advance_payments', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->after('cash_advance_id')->nullable(); // For backward compatibility
            $table->string('payment_method')->after('payment_date')->default('payroll_deduction');
            $table->string('reference_number')->after('payment_method')->nullable();
            $table->foreignId('recorded_by')->after('reference_number')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_advance_payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->dropColumn(['amount', 'payment_method', 'reference_number', 'recorded_by']);
        });
    }
};
