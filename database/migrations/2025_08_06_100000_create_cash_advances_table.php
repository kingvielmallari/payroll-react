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
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('reference_number')->unique();
            $table->decimal('requested_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->decimal('outstanding_balance', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'fully_paid', 'cancelled'])->default('pending');
            $table->integer('installments')->default(1); // Number of payroll periods to deduct
            $table->decimal('installment_amount', 10, 2)->nullable(); // Amount per deduction
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable(); // Admin remarks
            $table->date('requested_date');
            $table->date('approved_date')->nullable();
            $table->date('first_deduction_date')->nullable(); // When deductions should start
            $table->foreignId('requested_by')->constrained('users'); // Employee who requested
            $table->foreignId('approved_by')->nullable()->constrained('users'); // Admin who approved
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['employee_id', 'status']);
            $table->index('reference_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_advances');
    }
};
