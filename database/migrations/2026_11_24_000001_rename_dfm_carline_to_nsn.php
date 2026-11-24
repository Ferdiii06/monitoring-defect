<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Cek apakah sudah ada carline bernama 'NSN' (case-insensitive)
        $nsnExists = DB::table('carlines')
            ->whereRaw('LOWER(name) = ?', ['nsn'])
            ->exists();

        if ($nsnExists) {
            Log::warning("Migration rename_dfm_carline_to_nsn: Carline 'NSN' sudah ada. Update dilewati.");
            return;
        }

        // 2. Update idempotent: UPDATE carlines SET name='NSN' WHERE name='DFM' AND car_type_id IS NULL
        DB::table('carlines')
            ->where('name', 'DFM')
            ->whereNull('car_type_id')
            ->update([
                'name'       => 'NSN',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mengembalikan 'NSN' ke 'DFM' hanya untuk id yang diubah migration ini (id: 66)
        DB::table('carlines')
            ->where('id', 66)
            ->where('name', 'NSN')
            ->whereNull('car_type_id')
            ->update([
                'name'       => 'DFM',
                'updated_at' => now(),
            ]);
    }
};
