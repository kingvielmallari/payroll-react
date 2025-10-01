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
        Schema::table('paid_leave_settings', function (Blueprint $table) {
            // Add simplified fields
            $table->integer('total_days')->default(1)->after('code'); // Number of paid leave days
            $table->integer('limit_quantity')->default(1)->after('total_days'); // Limit quantity
            $table->enum('limit_period', ['monthly', 'quarterly', 'annually'])->default('monthly')->after('limit_quantity'); // Limit period
            $table->enum('applicable_to', ['with_benefits', 'without_benefits', 'both'])->default('with_benefits')->after('limit_period'); // Applicable to
            $table->decimal('pay_percentage', 5, 2)->default(100.00)->after('applicable_to'); // Pay rule percentage
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paid_leave_settings', function (Blueprint $table) {
            $table->dropColumn(['total_days', 'limit_quantity', 'limit_period', 'applicable_to', 'pay_percentage']);
        });
    }
};
