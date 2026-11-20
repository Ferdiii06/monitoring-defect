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
        Schema::table('assy_numbers', function (Blueprint $table) {
            $table->string('family', 255)->nullable();
            $table->string('conveyor', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assy_numbers', function (Blueprint $table) {
            $table->dropColumn(['family', 'conveyor']);
        });
    }
};
