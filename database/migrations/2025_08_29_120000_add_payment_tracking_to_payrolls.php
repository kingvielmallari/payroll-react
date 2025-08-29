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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('status');
            $table->json('payment_proof_files')->nullable()->after('is_paid');
            $table->text('payment_notes')->nullable()->after('payment_proof_files');
            $table->foreignId('marked_paid_by')->nullable()->constrained('users')->onDelete('set null')->after('payment_notes');
            $table->timestamp('marked_paid_at')->nullable()->after('marked_paid_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['marked_paid_by']);
            $table->dropColumn([
                'is_paid',
                'payment_proof_files',
                'payment_notes',
                'marked_paid_by',
                'marked_paid_at'
            ]);
        });
    }
};
