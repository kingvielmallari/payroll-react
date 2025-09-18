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
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            // Update frequency enum to only allow per_payroll, monthly, quarterly, annually
            $table->enum('frequency', ['per_payroll', 'monthly', 'quarterly', 'annually'])->default('per_payroll')->change();

            // Add distribution method for when frequency is not per_payroll
            $table->enum('distribution_method', ['all_payrolls', 'first_payroll', 'last_payroll', 'equally_distributed'])->default('all_payrolls')->after('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->dropColumn('distribution_method');
            // Note: Cannot revert enum change easily, would need to recreate column
        });
    }
};
