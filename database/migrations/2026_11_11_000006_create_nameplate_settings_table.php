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
        Schema::create('nameplate_settings', function (Blueprint $table) {
            $table->id();
            $table->string('carline', 50)->unique();
            $table->string('name', 100);
            $table->string('set', 30);
            $table->string('date_format', 20)->default('YYMMDD');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nameplate_settings');
    }
};

