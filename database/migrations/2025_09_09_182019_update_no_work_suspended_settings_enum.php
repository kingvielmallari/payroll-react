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
        // First update any existing 'no_work' records to 'suspended'
        DB::table('no_work_suspended_settings')
            ->where('type', 'no_work')
            ->update(['type' => 'suspended']);

        // Then alter the table to remove 'no_work' from enum and change default
        DB::statement("ALTER TABLE no_work_suspended_settings MODIFY COLUMN type ENUM('suspended', 'partial_suspension') DEFAULT 'suspended'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original enum with 'no_work'
        DB::statement("ALTER TABLE no_work_suspended_settings MODIFY COLUMN type ENUM('no_work', 'suspended', 'partial_suspension') DEFAULT 'no_work'");
    }
};
