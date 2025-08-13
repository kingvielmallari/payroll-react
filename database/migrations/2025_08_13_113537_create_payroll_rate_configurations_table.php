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
        Schema::create('payroll_rate_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('type_name'); // regular_workday, rest_day, special_holiday, etc.
            $table->string('display_name'); // Display name for UI
            $table->decimal('regular_rate_multiplier', 5, 4)->default(1.0000); // Regular hours multiplier
            $table->decimal('overtime_rate_multiplier', 5, 4)->default(1.2500); // OT hours multiplier
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique('type_name');
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_rate_configurations');
    }
};
