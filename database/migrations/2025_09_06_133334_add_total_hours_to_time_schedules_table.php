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
        Schema::table('time_schedules', function (Blueprint $table) {
            $table->decimal('total_hours', 5, 2)->after('break_end')->nullable()->comment('Total working hours excluding break time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_schedules', function (Blueprint $table) {
            $table->dropColumn('total_hours');
        });
    }
};
