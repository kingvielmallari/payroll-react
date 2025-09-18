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
            $table->boolean('requires_perfect_attendance')->default(false)->after('benefit_eligibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->dropColumn('requires_perfect_attendance');
        });
    }
};
