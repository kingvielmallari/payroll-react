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
        Schema::table('cash_advances', function (Blueprint $table) {
            $table->decimal('interest_rate', 5, 2)->default(0)->after('installment_amount'); // Interest rate percentage
            $table->decimal('interest_amount', 10, 2)->default(0)->after('interest_rate'); // Calculated interest amount
            $table->decimal('total_amount', 10, 2)->default(0)->after('interest_amount'); // Principal + Interest
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_advances', function (Blueprint $table) {
            $table->dropColumn(['interest_rate', 'interest_amount', 'total_amount']);
        });
    }
};
