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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('tc_no')->nullable();
            $table->string('full_name')->nullable();
            $table->string('department_name')->nullable();
            $table->string('position_name')->nullable();
            $table->dateTime('date')->nullable();
            $table->dateTime('first_reading')->nullable();
            $table->dateTime('last_reading')->nullable();
            $table->time('working_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
