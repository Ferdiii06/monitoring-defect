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
        Schema::create('defects', function (Blueprint $table) {
            $table->id();
            $table->dateTime('waktu');
            $table->string('user_name');
            $table->string('jenis_assy'); // Final Assy, Pre Assy
            $table->string('line_conveyor');
            $table->string('conveyor');
            $table->string('jenis_defect');
            $table->string('jenis_sub_defect');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defects');
    }
};
