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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->enum('type', ['regular', 'special_non_working', 'special_working']);
            $table->decimal('rate_multiplier', 4, 2)->default(1.00); // 1.00 = 100%, 2.00 = 200%
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(false); // For annual holidays
            $table->boolean('is_active')->default(true);
            $table->year('year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
