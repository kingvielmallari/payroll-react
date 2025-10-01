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
            // Drop and recreate the enum with only full and half values
            $table->dropColumn('pay_rule');
        });

        Schema::table('paid_leave_settings', function (Blueprint $table) {
            $table->enum('pay_rule', ['full', 'half'])->default('full')->after('pay_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paid_leave_settings', function (Blueprint $table) {
            // Drop the column
            $table->dropColumn('pay_rule');
        });

        Schema::table('paid_leave_settings', function (Blueprint $table) {
            // Recreate with original values
            $table->enum('pay_rule', ['full', 'half', 'quarter', 'none'])->default('full')->after('pay_percentage');
        });
    }
};
