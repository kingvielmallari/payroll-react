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
        Schema::create('pagibig_tax_table', function (Blueprint $table) {
            $table->id();
            $table->decimal('range_start', 10, 2);
            $table->decimal('range_end', 10, 2)->nullable(); // NULL for "above" ranges
            $table->decimal('employee_share', 5, 2); // Percentage (e.g., 2.00 for 2%)
            $table->decimal('employer_share', 5, 2); // Percentage (e.g., 2.00 for 2%)
            $table->decimal('total_contribution', 5, 2); // Percentage (e.g., 4.00 for 4%)
            $table->decimal('min_contribution', 8, 2)->default(0); // Minimum contribution amount
            $table->decimal('max_contribution', 8, 2); // Maximum contribution amount
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagibig_tax_table');
    }
};
