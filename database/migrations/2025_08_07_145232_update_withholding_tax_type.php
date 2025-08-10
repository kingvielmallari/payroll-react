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
        // Update Withholding Tax type from 'tax' to 'government'
        DB::table('deduction_tax_settings')
            ->where('code', 'withholding_tax')
            ->update(['type' => 'government']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback Withholding Tax type from 'government' to 'tax'
        DB::table('deduction_tax_settings')
            ->where('code', 'withholding_tax')
            ->update(['type' => 'tax']);
    }
};
