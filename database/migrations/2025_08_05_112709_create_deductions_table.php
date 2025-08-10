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
        Schema::create('deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('deduction_setting_id')->nullable();
            $table->string('name');
            $table->enum('type', ['sss', 'philhealth', 'pagibig', 'withholding_tax', 'loan', 'cash_advance', 'other']);
            $table->decimal('amount', 10, 2);
            $table->enum('frequency', ['one_time', 'monthly', 'bi_monthly', 'per_payroll']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('installments')->nullable(); // For loans
            $table->integer('remaining_installments')->nullable();
            $table->decimal('balance', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deductions');
    }
};
