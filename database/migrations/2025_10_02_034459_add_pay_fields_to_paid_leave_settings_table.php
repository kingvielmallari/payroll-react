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
            $table->enum('pay_rule', ['full', 'half', 'quarter', 'none'])->default('full')->after('pay_percentage');
            $table->enum('pay_applicable_to', ['all', 'with_benefits', 'without_benefits'])->default('all')->after('pay_rule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paid_leave_settings', function (Blueprint $table) {
            $table->dropColumn(['pay_rule', 'pay_applicable_to']);
        });
    }
};
