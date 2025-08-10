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
        Schema::table('employees', function (Blueprint $table) {
            // Remove the regularization_date column
            $table->dropColumn('regularization_date');
            
            // Add new columns
            $table->integer('paid_leaves')->default(15)->after('hire_date');
            $table->enum('benefits_status', ['with_benefits', 'without_benefits'])
                  ->default('with_benefits')->after('paid_leaves');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Remove the new columns
            $table->dropColumn(['paid_leaves', 'benefits_status']);
            
            // Add back the regularization_date column
            $table->date('regularization_date')->nullable()->after('hire_date');
        });
    }
};
