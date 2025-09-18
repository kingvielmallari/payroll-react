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
        // Update existing 'benefit' records to 'incentives'
        DB::table('allowance_bonus_settings')
            ->where('type', 'benefit')
            ->update(['type' => 'incentives']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the change - update 'incentives' back to 'benefit'
        DB::table('allowance_bonus_settings')
            ->where('type', 'incentives')
            ->update(['type' => 'benefit']);
    }
};
