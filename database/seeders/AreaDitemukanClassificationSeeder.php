<?php

namespace Database\Seeders;

use App\Models\AreaDitemukan;
use Illuminate\Database\Seeder;

class AreaDitemukanClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Revisi IDRECT Batch 1:
     * - Final Assy: 6 area bawaan bertipe internal_defect
     * - Pre Assy: 5 area bawaan bertipe internal_defect
     */
    public function run(): void
    {
        // 1. 6 Area Final Assy (internal_defect)
        $finalAssyAreas = ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'];
        foreach ($finalAssyAreas as $areaName) {
            AreaDitemukan::updateOrCreate(
                ['name' => $areaName],
                [
                    'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
                    'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
                ]
            );
        }

        // 2. 5 Area Pre Assy (internal_defect)
        $preAssyAreas = ['Manual Crimping', 'Join Crimping', 'Wonder', 'Cutting', 'Raychem'];
        foreach ($preAssyAreas as $areaName) {
            AreaDitemukan::updateOrCreate(
                ['name' => $areaName],
                [
                    'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
                    'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
                ]
            );
        }

        // 3. Hapus area REWORK jika ada dan tidak dipakai defect
        $rework = AreaDitemukan::where('name', 'REWORK')->first();
        if ($rework && $rework->defects()->count() === 0) {
            $rework->delete();
        }
    }
}
