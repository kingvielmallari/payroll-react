<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            // MySQL doesn't support modifying ENUM directly, so we need to use raw SQL
            DB::statement("ALTER TABLE allowance_bonus_settings MODIFY COLUMN calculation_type ENUM('percentage', 'fixed_amount', 'daily_rate_multiplier', 'automatic')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            // Revert back to original enum values
            DB::statement("ALTER TABLE allowance_bonus_settings MODIFY COLUMN calculation_type ENUM('percentage', 'fixed_amount', 'daily_rate_multiplier')");
        });
    }
};
