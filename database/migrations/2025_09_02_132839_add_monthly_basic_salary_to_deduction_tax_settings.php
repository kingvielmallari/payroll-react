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
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            $table->boolean('apply_to_monthly_basic_salary')->default(false)->after('apply_to_net_pay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            $table->dropColumn('apply_to_monthly_basic_salary');
        });
    }
};
