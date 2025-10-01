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
        Schema::table('paid_leaves', function (Blueprint $table) {
            $table->foreignId('leave_setting_id')->nullable()->constrained('paid_leave_settings')->onDelete('set null')->after('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paid_leaves', function (Blueprint $table) {
            $table->dropForeign(['leave_setting_id']);
            $table->dropColumn('leave_setting_id');
        });
    }
};
