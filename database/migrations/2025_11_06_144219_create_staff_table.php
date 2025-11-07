<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { /** * Run the migrations. */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
        $table->id();

        $table->foreignId('manager_id')->constrained('managers')->onDelete('cascade');
        $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
        $table->boolean('is_mailable')->default(true);

        $table->string('created_by');
        $table->string('updated_by')->nullable();
        $table->string('deleted_by')->nullable();

        $table->timestamps(); $table->softDeletes();

        $table->unique(['manager_id', 'employee_id', 'deleted_at']); });
    }

    /** * Reverse the migrations. */ public function down(): void {
        // Drop foreign key constraints before dropping the table
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropForeign(['report_id']); });

        Schema::dropIfExists('staffs');
    }
};

