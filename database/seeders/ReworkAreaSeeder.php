<?php

namespace Database\Seeders;

use App\Models\AreaDitemukan;
use Illuminate\Database\Seeder;

class ReworkAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!AreaDitemukan::where('tipe', AreaDitemukan::TIPE_REWORK)->exists()) {
            AreaDitemukan::create([
                'name' => 'REWORK',
                'tipe' => AreaDitemukan::TIPE_REWORK,
            ]);
        }
    }
}
