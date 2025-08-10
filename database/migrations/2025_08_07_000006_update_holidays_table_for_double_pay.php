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
        Schema::table('holidays', function (Blueprint $table) {
            $table->boolean('is_double_pay')->default(false)->after('rate_multiplier');
            $table->decimal('double_pay_rate', 4, 2)->default(2.00)->after('is_double_pay'); // Default 200%
            $table->enum('pay_rule', ['regular_rate', 'holiday_rate', 'double_pay', 'no_pay'])->default('holiday_rate')->after('double_pay_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn(['is_double_pay', 'double_pay_rate', 'pay_rule']);
        });
    }
};
