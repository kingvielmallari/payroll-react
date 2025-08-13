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
        Schema::table('deductions', function (Blueprint $table) {
            $table->foreignId('cash_advance_id')
                ->nullable()
                ->after('deduction_setting_id')
                ->constrained('cash_advances')
                ->onDelete('cascade')
                ->comment('Link to cash advance if this deduction is for cash advance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deductions', function (Blueprint $table) {
            $table->dropForeign(['cash_advance_id']);
            $table->dropColumn('cash_advance_id');
        });
    }
};
