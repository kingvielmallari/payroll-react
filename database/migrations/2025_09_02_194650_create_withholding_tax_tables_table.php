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
        Schema::create('withholding_tax_tables', function (Blueprint $table) {
            $table->id();
            $table->string('pay_frequency'); // 'daily', 'weekly', 'semi_monthly', 'monthly'
            $table->integer('bracket'); // 1, 2, 3, 4, 5, 6
            $table->decimal('range_start', 12, 2);
            $table->decimal('range_end', 12, 2)->nullable(); // null for "and above" ranges
            $table->decimal('base_tax', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0); // percentage as decimal (15% = 15.00)
            $table->decimal('excess_over', 12, 2)->default(0); // amount to subtract for excess calculation
            $table->timestamps();

            // Add indexes for performance
            $table->index(['pay_frequency', 'range_start', 'range_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_tables');
    }
};
