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
            $table->enum('semi_monthly_distribution', ['first_cutoff', 'second_cutoff', 'split_50_50'])->default('split_50_50')->after('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->dropColumn('semi_monthly_distribution');
        });
    }
};
