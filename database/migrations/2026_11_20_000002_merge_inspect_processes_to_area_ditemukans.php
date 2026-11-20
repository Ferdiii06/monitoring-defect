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
        // 1. Ubah unique index pada area_ditemukans dari unique(name) menjadi unique(name, kategori)
        Schema::table('area_ditemukans', function (Blueprint $table) {
            $table->dropUnique('area_ditemukans_name_unique');
            $table->unique(['name', 'kategori'], 'area_ditemukans_name_kategori_unique');
        });

        // 2. Rename nama dengan ejaan berbeda jika belum terkunci oleh defect
        // a. "Join Crimping" -> "Joint Crimping"
        $joinLocked = DB::table('defects')->where('area_ditemukan', 'Join Crimping')->exists();
        if (!$joinLocked) {
            DB::table('area_ditemukans')
                ->where('name', 'Join Crimping')
                ->where('kategori', 'Pre Assy')
                ->update(['name' => 'Joint Crimping', 'updated_at' => now()]);
        }

        // b. "Raychem" -> "Reychern"
        $raychemLocked = DB::table('defects')->where('area_ditemukan', 'Raychem')->exists();
        if (!$raychemLocked) {
            DB::table('area_ditemukans')
                ->where('name', 'Raychem')
                ->where('kategori', 'Pre Assy')
                ->update(['name' => 'Reychern', 'updated_at' => now()]);
        }

        // c. Hapus "Wonder" jika ada di Pre Assy dan tidak dipakai defect
        $wonderLocked = DB::table('defects')->where('area_ditemukan', 'Wonder')->exists();
        if (!$wonderLocked) {
            DB::table('area_ditemukans')
                ->where('name', 'Wonder')
                ->where('kategori', 'Pre Assy')
                ->delete();
        }

        // 3. Salin nama dari tabel inspect_process_types ke area_ditemukans (kategori: Pre Assy) secara idempotent tanpa hardcode
        if (Schema::hasTable('inspect_process_types')) {
            $processNames = DB::table('inspect_process_types')->pluck('name');

            foreach ($processNames as $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }

                $exists = DB::table('area_ditemukans')
                    ->where('name', $name)
                    ->where('kategori', 'Pre Assy')
                    ->exists();

                if (!$exists) {
                    DB::table('area_ditemukans')->insert([
                        'name'            => $name,
                        'kategori'        => 'Pre Assy',
                        'tipe'            => 'internal_defect',
                        'kategori_report' => 'belum_diklasifikasi',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Sebelum mengembalikan unique(name), tangani nama kembar lintas kategori (mis. WP)
        // Hapus area Pre Assy yang kembar dengan Final Assy jika belum dipakai oleh data defect
        $finalAssyNames = DB::table('area_ditemukans')
            ->where('kategori', 'Final Assy')
            ->pluck('name')
            ->toArray();

        foreach ($finalAssyNames as $name) {
            $preArea = DB::table('area_ditemukans')
                ->where('name', $name)
                ->where('kategori', 'Pre Assy')
                ->first();

            if ($preArea) {
                $hasDefects = DB::table('defects')
                    ->where('area_ditemukan', $name)
                    ->where('jenis_assy', 'Pre Assy')
                    ->exists();

                if (!$hasDefects) {
                    DB::table('area_ditemukans')->where('id', $preArea->id)->delete();
                } else {
                    // Jika ada defect, bedakan namanya agar unique(name) tidak error
                    DB::table('area_ditemukans')
                        ->where('id', $preArea->id)
                        ->update(['name' => $name . ' (Pre Assy)']);
                }
            }
        }

        // 2. Kembalikan unique index dari unique(name, kategori) ke unique(name)
        Schema::table('area_ditemukans', function (Blueprint $table) {
            $table->dropUnique('area_ditemukans_name_kategori_unique');
            $table->unique('name', 'area_ditemukans_name_unique');
        });
    }
};
