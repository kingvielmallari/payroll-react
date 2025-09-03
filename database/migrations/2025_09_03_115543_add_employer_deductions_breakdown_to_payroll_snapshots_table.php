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
        Schema::table('payroll_snapshots', function (Blueprint $table) {
            $table->json('employer_deductions_breakdown')->nullable()->after('deductions_breakdown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_snapshots', function (Blueprint $table) {
            $table->dropColumn('employer_deductions_breakdown');
        });
    }
};
