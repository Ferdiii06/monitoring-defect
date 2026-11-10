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
            $table->string('kategori_report', 30)->default('belum_diklasifikasi')->after('tipe');
        });

        // 1. Salin nilai klasifikasi pelaporan saat ini ke kategori_report
        // REWORK: area rework/produksi -> kategori_report = 'assembly', tipe = 'rework'
        DB::table('area_ditemukans')
            ->where('name', 'REWORK')
            ->update([
                'tipe' => 'rework',
                'kategori_report' => 'assembly',
            ]);

        // Area inspeksi bawaan (CK, V1, V2, FC, WP, RI) -> kategori_report = 'inspeksi'
        DB::table('area_ditemukans')
            ->whereIn('name', ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'])
            ->update([
                'kategori_report' => 'inspeksi',
            ]);

        // Non-REWORK lainnya: jika tipe saat ini adalah inspeksi/assembly, salin ke kategori_report
        DB::table('area_ditemukans')
            ->where('name', '!=', 'REWORK')
            ->whereIn('tipe', ['inspeksi', 'assembly', 'belum_diklasifikasi'])
            ->update([
                'kategori_report' => DB::raw('tipe'),
            ]);

        // 2. Kembalikan tipe sistem operasional seluruh area non-REWORK ke nilai aslinya: 'internal_defect'
        DB::table('area_ditemukans')
            ->where('name', '!=', 'REWORK')
            ->update([
                'tipe' => 'internal_defect',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan tipe dari nilai kategori_report bila non-rework
        DB::table('area_ditemukans')
            ->where('name', '!=', 'REWORK')
            ->update([
                'tipe' => DB::raw('kategori_report'),
            ]);

        Schema::table('area_ditemukans', function (Blueprint $table) {
            $table->dropColumn('kategori_report');
        });
    }
};

