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
        Schema::table('time_logs', function (Blueprint $table) {
            $table->decimal('regular_overtime_hours', 8, 2)->default(0)->after('overtime_hours');
            $table->decimal('night_diff_overtime_hours', 8, 2)->default(0)->after('regular_overtime_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropColumn(['regular_overtime_hours', 'night_diff_overtime_hours']);
        });
    }
};
