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
            // Add new foreign key column
            $table->unsignedBigInteger('employment_type_id')->nullable()->after('employment_type');
            $table->foreign('employment_type_id')->references('id')->on('employment_types')->onDelete('set null');

            // Keep the old employment_type column for now (we'll migrate data first)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['employment_type_id']);
            $table->dropColumn('employment_type_id');
        });
    }
};
