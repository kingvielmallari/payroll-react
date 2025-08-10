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
            $table->enum('day_schedule', ['monday_friday', 'monday_saturday', 'monday_sunday', 'tuesday_saturday', 'sunday_thursday', 'custom'])
                  ->default('monday_friday')
                  ->after('pay_schedule')
                  ->comment('Employee work day schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('day_schedule');
        });
    }
};
