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
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->boolean('payslip_sent')->default(false);
            $table->timestamp('payslip_sent_at')->nullable();
            $table->string('payslip_sent_to_email')->nullable();
            $table->unsignedBigInteger('payslip_sent_by')->nullable();
            $table->integer('payslip_send_count')->default(0);
            $table->timestamp('payslip_last_sent_at')->nullable();

            $table->foreign('payslip_sent_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->dropForeign(['payslip_sent_by']);
            $table->dropColumn([
                'payslip_sent',
                'payslip_sent_at',
                'payslip_sent_to_email',
                'payslip_sent_by',
                'payslip_send_count',
                'payslip_last_sent_at'
            ]);
        });
    }
};
