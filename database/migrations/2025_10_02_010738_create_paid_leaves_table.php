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
        Schema::create('paid_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('reference_number')->unique();
            $table->enum('leave_type', ['sick_leave', 'vacation_leave', 'emergency_leave', 'maternity_leave', 'paternity_leave', 'bereavement_leave'])->default('sick_leave');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days'); // Total leave days requested
            $table->decimal('daily_rate', 10, 2)->nullable(); // Employee's daily rate for calculation
            $table->decimal('total_amount', 10, 2)->nullable(); // Total paid leave amount
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable(); // Reason for leave
            $table->text('remarks')->nullable(); // Admin remarks
            $table->date('requested_date');
            $table->date('approved_date')->nullable();
            $table->json('leave_days')->nullable(); // Specific dates for leave (in case of non-consecutive days)
            $table->boolean('is_paid')->default(true); // Whether this leave is paid or unpaid
            $table->string('supporting_document')->nullable(); // Path to uploaded document (medical cert, etc.)
            $table->foreignId('requested_by')->constrained('users'); // Employee who requested
            $table->foreignId('approved_by')->nullable()->constrained('users'); // Admin who approved
            $table->timestamps();

            // Indexes for better performance
            $table->index(['employee_id', 'status']);
            $table->index('reference_number');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('leave_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paid_leaves');
    }
};
