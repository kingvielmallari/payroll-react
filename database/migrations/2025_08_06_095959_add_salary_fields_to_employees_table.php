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
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('weekly_rate', 10, 2)->nullable()->after('daily_rate');
            $table->decimal('semi_monthly_rate', 10, 2)->nullable()->after('weekly_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['weekly_rate', 'semi_monthly_rate']);
        });
    }
};
