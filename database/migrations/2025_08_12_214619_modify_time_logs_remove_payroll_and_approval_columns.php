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
            // Remove payroll foreign key constraint and column
            $table->dropForeign(['payroll_id']);
            $table->dropColumn('payroll_id');

            // Remove approval-related columns
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'approved_by', 'approved_at']);

            // Add new column to track creation method
            $table->enum('creation_method', ['manual', 'imported'])->default('manual')->after('log_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            // Add back the removed columns
            $table->foreignId('payroll_id')->nullable()->after('employee_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Remove the creation_method column
            $table->dropColumn('creation_method');
        });
    }
};
