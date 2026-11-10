<?php

namespace Database\Seeders;

use App\Models\AreaDitemukan;
use Illuminate\Database\Seeder;

class AreaDitemukanClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Pemetaan kategori report Final Assy:
     * - Area inspeksi (CK, V1, V2, FC, WP, RI) -> Internal (inspeksi)
     * - Area rework / produksi (REWORK, dll)   -> Assembly (assembly)
     * - Tipe sistem operasional: REWORK -> rework, lainnya -> internal_defect.
     */
    public function run(): void
    {
        // 1. Area inspeksi bawaan -> kategori_report 'inspeksi'
        AreaDitemukan::whereIn('name', ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'])
            ->update([
                'tipe'            => AreaDitemukan::TIPE_INTERNAL_DEFECT,
                'kategori_report' => AreaDitemukan::REPORT_INSPEKSI,
            ]);

        // 2. Area rework / produksi -> kategori_report 'assembly'
        AreaDitemukan::where('name', 'REWORK')
            ->update([
                'tipe'            => AreaDitemukan::TIPE_REWORK,
                'kategori_report' => AreaDitemukan::REPORT_ASSEMBLY,
            ]);

        // 3. Area lain jika ada -> fallback ke 'belum_diklasifikasi'
        AreaDitemukan::whereNotIn('name', ['CK', 'V1', 'V2', 'FC', 'WP', 'RI', 'REWORK'])
            ->where(function ($q) {
                $q->whereNull('kategori_report')
                  ->orWhere('kategori_report', '');
            })
            ->update([
                'tipe'            => AreaDitemukan::TIPE_INTERNAL_DEFECT,
                'kategori_report' => AreaDitemukan::REPORT_BELUM_DIKLASIFIKASI,
            ]);
    }
}
