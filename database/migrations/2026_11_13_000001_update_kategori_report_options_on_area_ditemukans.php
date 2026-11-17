<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\AreaDitemukan;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pemetaan opsi kategori report baru pada Master Area Ditemukan:
     * - Pre Assy  -> Ditemukan oleh Operator ('operator'), Ditemukan oleh Inspektor ('inspektor')
     * - Final Assy -> Inspeksi (Internal Defect) ('inspeksi'), Assembly (Assembly Defect) ('assembly')
     * - Rework    -> Rework ('rework')
     */
    public function up(): void
    {
        // 1. Pemetaan data Rework: kategori_report menjadi 'rework'
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('kategori', AreaDitemukan::KATEGORI_REWORK)
                  ->orWhere('tipe', AreaDitemukan::KATEGORI_REWORK)
                  ->orWhere('name', 'REWORK');
            })
            ->update([
                'kategori'        => AreaDitemukan::KATEGORI_REWORK,
                'tipe'            => AreaDitemukan::KATEGORI_REWORK,
                'kategori_report' => AreaDitemukan::REPORT_REWORK,
            ]);

        // 2. Pemetaan 5 area Pre Assy bawaan ke 'operator'
        $preAssyAreas = ['Manual Crimping', 'Join Crimping', 'Wonder', 'Cutting', 'Raychem'];
        DB::table('area_ditemukans')
            ->whereIn('name', $preAssyAreas)
            ->update([
                'kategori'        => AreaDitemukan::KATEGORI_PRE_ASSY,
                'tipe'            => AreaDitemukan::KATEGORI_PRE_ASSY,
                'kategori_report' => AreaDitemukan::REPORT_OPERATOR,
            ]);

        // 3. Pemetaan area Pre Assy lainnya:
        // - Jika sebelumnya 'assembly' -> petakan ke 'operator'
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)
                  ->orWhere('tipe', AreaDitemukan::KATEGORI_PRE_ASSY);
            })
            ->where('kategori_report', 'assembly')
            ->update([
                'kategori_report' => AreaDitemukan::REPORT_OPERATOR,
            ]);

        // - Jika sebelumnya 'inspeksi' / 'internal_defect' -> petakan ke 'inspektor'
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)
                  ->orWhere('tipe', AreaDitemukan::KATEGORI_PRE_ASSY);
            })
            ->whereIn('kategori_report', ['inspeksi', 'internal_defect', 'internal'])
            ->update([
                'kategori_report' => AreaDitemukan::REPORT_INSPEKTOR,
            ]);

        // - Jika sebelumnya belum diklasifikasi atau null -> default ke 'operator'
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)
                  ->orWhere('tipe', AreaDitemukan::KATEGORI_PRE_ASSY);
            })
            ->where(function ($q) {
                $q->whereNull('kategori_report')
                  ->orWhere('kategori_report', '')
                  ->orWhere('kategori_report', AreaDitemukan::REPORT_BELUM_DIKLASIFIKASI);
            })
            ->update([
                'kategori_report' => AreaDitemukan::REPORT_OPERATOR,
            ]);

        // 4. Area Final Assy yang belum memiliki kategori_report -> default 'inspeksi'
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('kategori', AreaDitemukan::KATEGORI_FINAL_ASSY)
                  ->orWhere('tipe', AreaDitemukan::KATEGORI_FINAL_ASSY);
            })
            ->where(function ($q) {
                $q->whereNull('kategori_report')
                  ->orWhere('kategori_report', '')
                  ->orWhere('kategori_report', AreaDitemukan::REPORT_BELUM_DIKLASIFIKASI);
            })
            ->update([
                'kategori_report' => AreaDitemukan::REPORT_INSPEKSI,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan Rework ke 'assembly'
        DB::table('area_ditemukans')
            ->where('kategori_report', AreaDitemukan::REPORT_REWORK)
            ->update([
                'kategori_report' => AreaDitemukan::REPORT_ASSEMBLY,
            ]);

        // Kembalikan Pre Assy 'operator' ke 'belum_diklasifikasi'
        DB::table('area_ditemukans')
            ->where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)
            ->where('kategori_report', AreaDitemukan::REPORT_OPERATOR)
            ->update([
                'kategori_report' => AreaDitemukan::REPORT_BELUM_DIKLASIFIKASI,
            ]);

        // Kembalikan Pre Assy 'inspektor' ke 'inspeksi'
        DB::table('area_ditemukans')
            ->where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)
            ->where('kategori_report', AreaDitemukan::REPORT_INSPEKTOR)
            ->update([
                'kategori_report' => AreaDitemukan::REPORT_INSPEKSI,
            ]);
    }
};
