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
        Schema::table('system_licenses', function (Blueprint $table) {
            // Remove subscription_plan_id foreign key constraint and column
            $table->dropConstrainedForeignId('subscription_plan_id');

            // Add new columns for plan information
            $table->json('plan_info')->nullable()->after('license_key');
            $table->timestamp('countdown_started_at')->nullable()->after('activated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_licenses', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['plan_info', 'countdown_started_at']);

            // Re-add subscription_plan_id
            $table->foreignId('subscription_plan_id')->nullable()->constrained();
        });
    }
};
