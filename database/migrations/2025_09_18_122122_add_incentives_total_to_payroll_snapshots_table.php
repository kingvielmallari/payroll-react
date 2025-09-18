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
            $table->decimal('incentives_total', 10, 2)->nullable()->after('bonuses_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_snapshots', function (Blueprint $table) {
            $table->dropColumn('incentives_total');
        });
    }
};
