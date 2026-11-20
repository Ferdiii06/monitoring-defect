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
            AreaDitemukan::firstOrCreate(
                ['name' => $areaName, 'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY],
                [
                    'tipe'      => AreaDitemukan::TIPE_INTERNAL_DEFECT,
                    'is_active' => true,
                ]
            );
        }

        // 2. 16 Area Pre Assy (internal_defect) dari inspect_process_types
        $preAssyAreas = [
            'Cutting', 'Manual Crimping', 'Joint Crimping', 'Bonder',
            'Heatshrink', 'Reychern', 'Solder', 'La Term. WP', 'A/B',
            'Jam Twist', 'WP', 'MN20', 'EJ30', 'MN10', 'TP25', 'EJ35'
        ];
        foreach ($preAssyAreas as $areaName) {
            AreaDitemukan::firstOrCreate(
                ['name' => $areaName, 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
                [
                    'tipe'      => AreaDitemukan::TIPE_INTERNAL_DEFECT,
                    'is_active' => true,
                ]
            );
        }

        // Hapus area legacy (Wonder, Join Crimping lama, Raychem lama) jika tidak dipakai defects
        $legacyPreNames = ['Wonder', 'Join Crimping', 'Raychem'];
        foreach ($legacyPreNames as $legacyName) {
            $legacy = AreaDitemukan::where('name', $legacyName)->where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)->first();
            if ($legacy && $legacy->defects()->where('jenis_assy', 'Pre Assy')->count() === 0) {
                $legacy->delete();
            }
        }

        // 3. Hapus area REWORK jika ada dan tidak dipakai defect
        $rework = AreaDitemukan::where('name', 'REWORK')->first();
        if ($rework && $rework->defects()->count() === 0) {
            $rework->delete();
        }
    }
}
