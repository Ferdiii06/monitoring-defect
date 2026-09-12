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
        Schema::create('car_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('carlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_type_id')->constrained('car_types')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('defect_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('Final Assy'); // Final Assy atau Pre Assy
            $table->timestamps();
        });

        Schema::create('sub_defect_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defect_type_id')->constrained('defect_types')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('inspect_process_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_defect_types');
        Schema::dropIfExists('defect_types');
        Schema::dropIfExists('carlines');
        Schema::dropIfExists('car_types');
        Schema::dropIfExists('inspect_process_types');
    }
};
