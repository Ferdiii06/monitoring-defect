<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('area_ditemukans', function (Blueprint $table) {
            $table->string('tipe', 30)->default('belum_diklasifikasi')->change();
        });

        // Set semua area existing selain REWORK ke 'belum_diklasifikasi'
        DB::table('area_ditemukans')
            ->where('name', '!=', 'REWORK')
            ->update(['tipe' => 'belum_diklasifikasi']);

        // Pastikan REWORK tetap bertipe 'rework'
        DB::table('area_ditemukans')
            ->where('name', 'REWORK')
            ->update(['tipe' => 'rework']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('area_ditemukans')
            ->where('name', '!=', 'REWORK')
            ->update(['tipe' => 'internal_defect']);

        Schema::table('area_ditemukans', function (Blueprint $table) {
            $table->string('tipe', 20)->default('internal_defect')->change();
        });
    }
};

