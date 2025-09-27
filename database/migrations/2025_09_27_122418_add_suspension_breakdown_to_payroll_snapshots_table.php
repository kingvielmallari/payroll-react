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
            // Add suspension_breakdown column after overtime_breakdown
            $table->json('suspension_breakdown')->nullable()->after('overtime_breakdown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_snapshots', function (Blueprint $table) {
            $table->dropColumn('suspension_breakdown');
        });
    }
};
