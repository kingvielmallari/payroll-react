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
        Schema::create('sss_tax_table', function (Blueprint $table) {
            $table->id();
            $table->decimal('range_start', 10, 2);
            $table->decimal('range_end', 10, 2)->nullable(); // NULL for the highest bracket (above)
            $table->decimal('employee_share', 8, 2);
            $table->decimal('employer_share', 8, 2);
            $table->decimal('total_contribution', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add indexes for efficient range queries
            $table->index(['range_start', 'range_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sss_tax_table');
    }
};
