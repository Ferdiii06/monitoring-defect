<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Admin User
        if (!User::where('name', 'Admin QA')->exists()) {
            User::create([
                'name' => 'Admin QA',
                'email' => 'admin@example.com',
                'password' => Hash::make('123456'),
                'role' => 'Administrator',
                'pin' => '123456',
            ]);
        }

        // 2. Seed Operators
        $operators = [
            ['name' => 'AINUR NURIN', 'nik' => '11435'],
            ['name' => 'ANI ASTUTIK', 'nik' => '229'],
            ['name' => 'DAWIN MURAI AINI', 'nik' => '689'],
            ['name' => 'DWI ASTUTIK', 'nik' => '609'],
            ['name' => 'DWI SHOFIYANINGSIH', 'nik' => '950'],
            ['name' => 'ENDAH ISWATI', 'nik' => '954'],
            ['name' => 'IDA MARIYANTI', 'nik' => '475'],
            ['name' => 'IRMAWATI', 'nik' => '2020'],
            ['name' => 'KARMIATIN', 'nik' => '2495'],
            ['name' => 'KHOIROTUN MUNJILAH', 'nik' => '481'],
            ['name' => 'KHUROTIN SOLIKAH', 'nik' => '413'],
            ['name' => 'KRISTIYA RAHAYU', 'nik' => '415'],
            ['name' => 'LASIATI', 'nik' => '153'],
            ['name' => 'LIFFA ANI WULANDARI', 'nik' => '3903'],
            ['name' => 'MAULIDIYAH', 'nik' => '486'],
            ['name' => 'NITA ERFANA', 'nik' => '426'],
            ['name' => 'NOVI RIA', 'nik' => '492'],
            ['name' => 'SETIA NINGSIH', 'nik' => '917'],
            ['name' => 'SRI HANDAYANI', 'nik' => '723'],
            ['name' => 'SULISWANTI', 'nik' => '2842'],
            ['name' => 'TITIK INDRAWATI', 'nik' => '12056'],
            ['name' => 'TITIK ZUBAIDAH', 'nik' => '3362'],
            ['name' => 'WHIWIN FALUPHI', 'nik' => '173'],
            ['name' => 'ZENY EKA FARISTA', 'nik' => '542'],
            ['name' => 'AGUSTIN INDRIANI', 'nik' => '5818'],
            ['name' => 'ANI MURTI MARANTA', 'nik' => '2204'],
            ['name' => 'ANI SULISTYANA', 'nik' => '676'],
            ['name' => 'ANIK JUMAWATI', 'nik' => '677'],
            ['name' => 'DIAN RACHMAWATI', 'nik' => '261'],
            ['name' => 'RUTYANING RAHAYU', 'nik' => '197'],
            ['name' => 'SITI MURSIDAH', 'nik' => '2194'],
            ['name' => 'SRI WINARNI', 'nik' => '355'],
            ['name' => 'SUPARTININGTYAS', 'nik' => '525'],
            ['name' => 'SUYANTI', 'nik' => '926'],
            ['name' => 'TRI WULANDARI', 'nik' => '2196'],
            ['name' => 'WINARNI', 'nik' => '51'],
            ['name' => 'YULIANI', 'nik' => '449'],
            ['name' => 'YULIATI', 'nik' => '450'],
        ];

        foreach ($operators as $operator) {
            if (!User::where('name', $operator['name'])->exists()) {
                $pin = str_pad($operator['nik'], 6, '0', STR_PAD_LEFT);
                User::create([
                    'name' => $operator['name'],
                    'email' => strtolower(str_replace(' ', '', $operator['name'])) . '@example.com',
                    'password' => Hash::make($pin),
                    'role' => 'User',
                    'pin' => $pin,
                ]);
            }
        }
    }
}

