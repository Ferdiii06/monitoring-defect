<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Batch 1: Normalisasi Area Ditemukan:
     * - kategori: hanya 'Final Assy' atau 'Pre Assy' (kategori 'Rework' dihapus)
     * - tipe: hanya 'assembly_defect' atau 'internal_defect'
     * - area REWORK: dihapus jika tidak dipakai defect, atau diubah ke Final Assy + internal_defect
     */
    public function up(): void
    {
        if (!Schema::hasTable('area_ditemukans')) {
            return;
        }

        // 1. Tangani area bernama 'REWORK'
        $reworkDefectCount = 0;
        if (Schema::hasTable('defects')) {
            $reworkDefectCount = DB::table('defects')
                ->where('area_ditemukan', 'REWORK')
                ->count();
        }

        if ($reworkDefectCount === 0) {
            DB::table('area_ditemukans')
                ->where('name', 'REWORK')
                ->delete();
        } else {
            DB::table('area_ditemukans')
                ->where('name', 'REWORK')
                ->update([
                    'kategori' => 'Final Assy',
                    'tipe'     => 'internal_defect',
                ]);
        }

        // 2. Normalisasi area lain yang memiliki kategori 'Rework'
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('kategori', 'Rework')
                  ->orWhere('kategori', 'rework');
            })
            ->update([
                'kategori' => 'Final Assy',
                'tipe'     => 'internal_defect',
            ]);

        // 3. Normalisasi Pre Assy: semua area Pre Assy wajib kategori 'Pre Assy' dan tipe 'internal_defect'
        $preAssyNames = ['Manual Crimping', 'Join Crimping', 'Wonder', 'Bonder', 'Cutting', 'Raychem'];
        DB::table('area_ditemukans')
            ->where(function ($q) use ($preAssyNames) {
                $q->where('kategori', 'Pre Assy')
                  ->orWhere('tipe', 'Pre Assy')
                  ->orWhere('tipe', 'pre_assy')
                  ->orWhereIn('name', $preAssyNames);
            })
            ->update([
                'kategori' => 'Pre Assy',
                'tipe'     => 'internal_defect',
            ]);

        // 4. Normalisasi Final Assy default: CK, V1, V2, FC, WP, RI -> internal_defect
        $defaultInternalFinal = ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'];
        DB::table('area_ditemukans')
            ->whereIn('name', $defaultInternalFinal)
            ->update([
                'kategori' => 'Final Assy',
                'tipe'     => 'internal_defect',
            ]);

        // 5. Normalisasi tipe-tipe legacy lainnya
        // Nilai yang menandakan assembly -> 'assembly_defect'
        DB::table('area_ditemukans')
            ->where('kategori', '!=', 'Pre Assy')
            ->where(function ($q) {
                $q->where('tipe', 'assembly')
                  ->orWhere('tipe', 'assembly_defect');
            })
            ->update([
                'kategori' => 'Final Assy',
                'tipe'     => 'assembly_defect',
            ]);

        // Sisa area lainnya: pastikan kategori valid ('Final Assy' atau 'Pre Assy')
        DB::table('area_ditemukans')
            ->whereNotIn('kategori', ['Final Assy', 'Pre Assy'])
            ->update([
                'kategori' => 'Final Assy',
            ]);

        // Sisa area lainnya: pastikan tipe valid ('assembly_defect' atau 'internal_defect')
        DB::table('area_ditemukans')
            ->whereNotIn('tipe', ['assembly_defect', 'internal_defect'])
            ->update([
                'tipe' => 'internal_defect',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data normalization is irreversible / idempotent.
    }
};
