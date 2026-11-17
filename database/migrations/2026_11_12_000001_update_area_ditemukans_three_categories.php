<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Poin 5 & 6:
     * - Kategori area ditemukan menjadi 3: Pre Assy, Final Assy, Rework.
     * - Data lama internal_defect dipetakan ke Final Assy.
     * - Data lama rework dipetakan ke Rework.
     * - Seed 5 area Pre Assy secara idempotent: Manual Crimping, Join Crimping, Wonder, Cutting, Raychem.
     */
    public function up(): void
    {
        Schema::table('area_ditemukans', function (Blueprint $table) {
            if (!Schema::hasColumn('area_ditemukans', 'kategori')) {
                $table->string('kategori', 50)->nullable()->after('name');
            }
        });

        // 1. Pemetaan data lama:
        // - Rework
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->where('name', 'REWORK')
                  ->orWhere('tipe', 'rework');
            })
            ->update([
                'kategori' => 'Rework',
                'tipe'     => 'Rework',
            ]);

        // - Final Assy (area stasiun CK, V1, V2, FC, WP, RI dan area internal_defect lainnya)
        DB::table('area_ditemukans')
            ->where(function ($q) {
                $q->whereIn('name', ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'])
                  ->orWhere('tipe', 'internal_defect')
                  ->orWhere('tipe', 'final_assy')
                  ->orWhereNull('kategori');
            })
            ->where('kategori', '!=', 'Rework')
            ->update([
                'kategori' => 'Final Assy',
                'tipe'     => 'Final Assy',
            ]);

        // 2. Seed 5 Area Pre Assy secara Idempotent (Poin 6)
        $preAssyAreas = [
            'Manual Crimping',
            'Join Crimping',
            'Wonder',
            'Cutting',
            'Raychem',
        ];

        foreach ($preAssyAreas as $areaName) {
            $exists = DB::table('area_ditemukans')->where('name', $areaName)->exists();
            if (!$exists) {
                DB::table('area_ditemukans')->insert([
                    'name'            => $areaName,
                    'kategori'        => 'Pre Assy',
                    'tipe'            => 'Pre Assy',
                    'kategori_report' => 'belum_diklasifikasi',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            } else {
                DB::table('area_ditemukans')->where('name', $areaName)->update([
                    'kategori' => 'Pre Assy',
                    'tipe'     => 'Pre Assy',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus area pre assy yang belum dipakai di defects jika rollback
        $preAssyAreas = ['Manual Crimping', 'Join Crimping', 'Wonder', 'Cutting', 'Raychem'];
        DB::table('area_ditemukans')
            ->whereIn('name', $preAssyAreas)
            ->whereNotIn('name', function ($q) {
                $q->select('area_ditemukan')->from('defects')->whereNotNull('area_ditemukan');
            })
            ->delete();

        // Kembalikan tipe
        DB::table('area_ditemukans')
            ->where('kategori', 'Final Assy')
            ->update(['tipe' => 'internal_defect']);

        DB::table('area_ditemukans')
            ->where('kategori', 'Rework')
            ->update(['tipe' => 'rework']);

        if (Schema::hasColumn('area_ditemukans', 'kategori')) {
            Schema::table('area_ditemukans', function (Blueprint $table) {
                $table->dropColumn('kategori');
            });
        }
    }
};
