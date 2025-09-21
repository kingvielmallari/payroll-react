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
        Schema::table('no_work_suspended_settings', function (Blueprint $table) {
            // Drop the old pay_percentage column if it exists
            if (Schema::hasColumn('no_work_suspended_settings', 'pay_percentage')) {
                $table->dropColumn('pay_percentage');
            }

            // Add the new pay_rule column
            if (!Schema::hasColumn('no_work_suspended_settings', 'pay_rule')) {
                $table->enum('pay_rule', ['full', 'half'])->default('full')->after('is_paid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('no_work_suspended_settings', function (Blueprint $table) {
            // Remove pay_rule column
            if (Schema::hasColumn('no_work_suspended_settings', 'pay_rule')) {
                $table->dropColumn('pay_rule');
            }

            // Add back pay_percentage column
            if (!Schema::hasColumn('no_work_suspended_settings', 'pay_percentage')) {
                $table->integer('pay_percentage')->nullable()->after('is_paid');
            }
        });
    }
};
