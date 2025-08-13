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
        Schema::table('payroll_rate_configurations', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        // Mark default system configurations as non-deletable
        $systemTypes = [
            'Regular Workday',
            'Rest Day',
            'Special (Non-working) Holiday',
            'Regular Holiday',
            'Rest Day + Regular Holiday',
            'Rest Day + Special Holiday'
        ];

        foreach ($systemTypes as $displayName) {
            \App\Models\PayrollRateConfiguration::where('display_name', $displayName)
                ->update(['is_system' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_rate_configurations', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
