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
        Schema::table('no_work_suspended_settings', function (Blueprint $table) {
            // Add new simple pay fields only if they don't exist
            if (!Schema::hasColumn('no_work_suspended_settings', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('detailed_reason');
            }
            if (!Schema::hasColumn('no_work_suspended_settings', 'pay_percentage')) {
                $table->integer('pay_percentage')->nullable()->after('is_paid'); // 25, 50, 75, 100
            }
            if (!Schema::hasColumn('no_work_suspended_settings', 'pay_applicable_to')) {
                $table->enum('pay_applicable_to', ['all', 'with_benefits', 'without_benefits'])->nullable()->after('pay_percentage');
            }
            if (!Schema::hasColumn('no_work_suspended_settings', 'status')) {
                $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft')->after('pay_applicable_to');
            }
        });

        Schema::table('no_work_suspended_settings', function (Blueprint $table) {
            // Remove old complex fields that exist
            $existingColumns = Schema::getColumnListing('no_work_suspended_settings');
            $columnsToRemove = array_intersect([
                'pay_rule',
                'custom_pay_rate',
                'scope',
                'affected_departments',
                'affected_positions',
                'affected_employees',
                'allow_makeup_work',
                'makeup_deadline',
                'makeup_instructions',
                'declared_by',
                'declaration_date',
                'official_memo',
                'is_active'
            ], $existingColumns);

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('no_work_suspended_settings', function (Blueprint $table) {
            // Remove new fields
            $table->dropColumn(['is_paid', 'pay_percentage', 'pay_applicable_to', 'status']);

            // Restore old fields
            $table->enum('pay_rule', ['no_pay', 'half_pay', 'full_pay', 'custom_rate'])->default('no_pay');
            $table->decimal('custom_pay_rate', 4, 2)->nullable();
            $table->enum('scope', ['company_wide', 'department', 'position', 'specific_employees'])->default('company_wide');
            $table->json('affected_departments')->nullable();
            $table->json('affected_positions')->nullable();
            $table->json('affected_employees')->nullable();
            $table->boolean('allow_makeup_work')->default(false);
            $table->date('makeup_deadline')->nullable();
            $table->text('makeup_instructions')->nullable();
            $table->string('declared_by')->nullable();
            $table->timestamp('declaration_date')->nullable();
            $table->text('official_memo')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }
};
