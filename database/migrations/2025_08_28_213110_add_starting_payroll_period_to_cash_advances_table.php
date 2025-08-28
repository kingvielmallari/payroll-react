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
        Schema::table('cash_advances', function (Blueprint $table) {
            // Add starting payroll period field (1=current, 2=next, 3=2nd next, 4=3rd next)
            $table->integer('starting_payroll_period')
                ->default(1)
                ->after('payroll_id')
                ->comment('Which payroll period to start deductions: 1=current, 2=next, 3=2nd next, 4=3rd next');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_advances', function (Blueprint $table) {
            $table->dropColumn('starting_payroll_period');
        });
    }
};
