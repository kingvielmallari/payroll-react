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
        Schema::create('employer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('registered_business_name')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->string('rdo_code')->nullable();
            $table->string('sss_employer_number')->nullable();
            $table->string('philhealth_employer_number')->nullable();
            $table->string('hdmf_employer_number')->nullable();
            $table->text('registered_address')->nullable();
            $table->string('postal_zip_code')->nullable();
            $table->string('landline_mobile')->nullable();
            $table->string('office_business_email')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_designation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employer_settings');
    }
};
