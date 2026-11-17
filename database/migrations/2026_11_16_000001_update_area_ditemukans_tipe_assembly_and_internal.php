<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Revisi IDRECT:
     * Area Ditemukan dipisah jadi tipe 'assembly_defect' dan 'internal_defect'.
     */
    public function up(): void
    {
        // 1. Harmonisasi nilai tipe di area_ditemukans
        // Area inspeksi Final Assy default ke internal_defect
        DB::table('area_ditemukans')
            ->whereIn('name', ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'])
            ->update([
                'tipe' => 'internal_defect',
            ]);

        // Area dengan kategori_report assembly atau tipe assembly dijadikan assembly_defect
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('kategori_report', 'assembly')
                  ->orWhere('tipe', 'assembly')
                  ->orWhere('tipe', 'assembly_defect');
            })
            ->where('name', '!=', 'REWORK')
            ->update([
                'tipe' => 'assembly_defect',
            ]);

        // Area dengan kategori_report inspeksi atau tipe internal_defect / Final Assy dijadikan internal_defect
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('kategori_report', 'inspeksi')
                  ->orWhere('tipe', 'internal_defect')
                  ->orWhere('tipe', 'Final Assy')
                  ->orWhere('tipe', 'belum_diklasifikasi');
            })
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
        DB::table('area_ditemukans')
            ->where('tipe', 'assembly_defect')
            ->update([
                'tipe' => 'assembly',
            ]);
    }
};
