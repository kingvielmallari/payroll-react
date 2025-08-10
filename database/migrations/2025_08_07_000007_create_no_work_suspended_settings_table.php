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
        Schema::create('no_work_suspended_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Typhoon, Flood, System Maintenance, etc.
            $table->string('code')->unique(); 
            $table->text('description')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->time('time_from')->nullable(); // For partial day suspensions
            $table->time('time_to')->nullable();
            
            // Type and Reason
            $table->enum('type', ['no_work', 'suspended', 'partial_suspension'])->default('no_work');
            $table->enum('reason', ['weather', 'system_maintenance', 'emergency', 'government_order', 'other'])->default('other');
            $table->text('detailed_reason')->nullable();
            
            // Pay Rules
            $table->enum('pay_rule', ['no_pay', 'half_pay', 'full_pay', 'custom_rate'])->default('no_pay');
            $table->decimal('custom_pay_rate', 4, 2)->nullable(); // For custom rate (e.g., 0.50 = 50%)
            
            // Scope of Application
            $table->enum('scope', ['company_wide', 'department', 'position', 'specific_employees'])->default('company_wide');
            $table->json('affected_departments')->nullable(); // Department IDs
            $table->json('affected_positions')->nullable(); // Position IDs
            $table->json('affected_employees')->nullable(); // Employee IDs
            
            // Makeup Work Options
            $table->boolean('allow_makeup_work')->default(false);
            $table->date('makeup_deadline')->nullable();
            $table->text('makeup_instructions')->nullable();
            
            // Official Declaration
            $table->string('declared_by')->nullable(); // Who declared it
            $table->timestamp('declaration_date')->nullable();
            $table->text('official_memo')->nullable(); // Link to official memo/document
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('no_work_suspended_settings');
    }
};
