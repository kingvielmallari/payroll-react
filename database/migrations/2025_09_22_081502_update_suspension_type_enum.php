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
        // First, update existing 'suspended' records to 'full_day_suspension'
        DB::statement("UPDATE no_work_suspended_settings SET type = 'partial_suspension' WHERE type = 'suspended'");

        // Then alter the enum to include the new values
        DB::statement("ALTER TABLE no_work_suspended_settings MODIFY COLUMN type ENUM('full_day_suspension', 'partial_suspension') DEFAULT 'full_day_suspension'");

        // Now update records to use the proper full_day_suspension type
        // (You can manually change specific records to partial_suspension as needed)
        DB::statement("UPDATE no_work_suspended_settings SET type = 'full_day_suspension' WHERE type = 'partial_suspension'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to old enum
        DB::statement("UPDATE no_work_suspended_settings SET type = 'suspended' WHERE type IN ('full_day_suspension', 'partial_suspension')");
        DB::statement("ALTER TABLE no_work_suspended_settings MODIFY COLUMN type ENUM('suspended', 'partial_suspension') DEFAULT 'suspended'");
    }
};
