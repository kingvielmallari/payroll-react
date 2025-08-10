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
        Schema::create('d_t_r_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('period_type'); // 'cutoff', 'monthly'
            $table->date('period_start');
            $table->date('period_end');
            $table->string('month_year'); // e.g., "MARCH 2025", "APRIL 2025"
            $table->integer('regular_days')->default(0);
            $table->integer('saturday_count')->default(0);
            $table->json('dtr_data'); // Store all the DTR entries
            $table->decimal('total_regular_hours', 8, 2)->default(0);
            $table->decimal('total_overtime_hours', 8, 2)->default(0);
            $table->decimal('total_late_hours', 8, 2)->default(0);
            $table->decimal('total_undertime_hours', 8, 2)->default(0);
            $table->enum('status', ['draft', 'finalized', 'approved'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate DTRs
            $table->unique(['employee_id', 'period_type', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('d_t_r_s');
    }
};
