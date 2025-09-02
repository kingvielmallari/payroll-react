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
        Schema::create('philhealth_tax_table', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_salary', 15, 2); // Minimum salary range
            $table->decimal('max_salary', 15, 2)->nullable(); // Maximum salary range (null for unlimited)
            $table->decimal('ee_percentage', 5, 2); // Employee percentage (e.g., 2.50 for 2.5%)
            $table->decimal('er_percentage', 5, 2); // Employer percentage (e.g., 2.50 for 2.5%)
            $table->decimal('min_contribution', 10, 2)->default(0); // Minimum contribution amount
            $table->decimal('max_contribution', 10, 2)->nullable(); // Maximum contribution amount
            $table->boolean('is_active')->default(true);
            $table->date('effective_date'); // When this rate takes effect
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('philhealth_tax_table');
    }
};
