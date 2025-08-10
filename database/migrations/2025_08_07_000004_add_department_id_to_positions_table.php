<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Position;
use App\Models\Department;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('department_id')->after('id')->nullable()->constrained()->onDelete('restrict');
        });
        
        // Assign existing positions to the first department
        $firstDepartment = Department::first();
        if ($firstDepartment) {
            Position::whereNull('department_id')->update(['department_id' => $firstDepartment->id]);
        }
        
        // Make department_id required after updating existing records
        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
