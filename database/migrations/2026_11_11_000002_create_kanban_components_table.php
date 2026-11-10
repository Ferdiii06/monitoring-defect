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
        Schema::create('kanban_components', function (Blueprint $table) {
            $table->id();
            $table->string('component_key', 50)->unique();
            $table->string('label', 100);
            $table->unsignedInteger('sequence');
            $table->string('delimiter', 10)->default('.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_components');
    }
};

