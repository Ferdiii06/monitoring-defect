<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OperatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operators = [
            ['name' => 'AINUR NURIN', 'pin' => '011435'],
            ['name' => 'ANI ASTUTIK', 'pin' => '000229'],
            ['name' => 'DAWIN MURAI AINI', 'pin' => '000689'],
            ['name' => 'DWI ASTUTIK', 'pin' => '000609'],
            ['name' => 'DWI SHOFIYANINGSIH', 'pin' => '000950'],
            ['name' => 'ENDAH ISWATI', 'pin' => '000954'],
            ['name' => 'IDA MARIYANTI', 'pin' => '000475'],
            ['name' => 'IRMAWATI', 'pin' => '002020'],
            ['name' => 'KARMIATIN', 'pin' => '002495'],
            ['name' => 'KHOIROTUN MUNJILAH', 'pin' => '000481'],
            ['name' => 'KHUROTIN SOLIKAH', 'pin' => '000413'],
            ['name' => 'KRISTIYA RAHAYU', 'pin' => '000415'],
            ['name' => 'LASIATI', 'pin' => '000153'],
            ['name' => 'LIFFA ANI WULANDARI', 'pin' => '003903'],
            ['name' => 'MAULIDIYAH', 'pin' => '000486'],
            ['name' => 'NITA ERFANA', 'pin' => '000426'],
            ['name' => 'NOVI RIA', 'pin' => '000492'],
            ['name' => 'SETIA NINGSIH', 'pin' => '000917'],
            ['name' => 'SRI HANDAYANI', 'pin' => '000723'],
            ['name' => 'SULISWANTI', 'pin' => '002842'],
            ['name' => 'TITIK INDRAWATI', 'pin' => '012056'],
            ['name' => 'TITIK ZUBAIDAH', 'pin' => '003362'],
            ['name' => 'WHIWIN FALUPHI', 'pin' => '000173'],
            ['name' => 'ZENY EKA FARISTA', 'pin' => '000542'],
            ['name' => 'AGUSTIN INDRIANI', 'pin' => '005818'],
            ['name' => 'ANI MURTI MARANTA', 'pin' => '002204'],
            ['name' => 'ANI SULISTYANA', 'pin' => '000676'],
            ['name' => 'ANIK JUMAWATI', 'pin' => '000677'],
            ['name' => 'DIAN RACHMAWATI', 'pin' => '000261'],
            ['name' => 'RUTYANING RAHAYU', 'pin' => '000197'],
            ['name' => 'SITI MURSIDAH', 'pin' => '002194'],
            ['name' => 'SRI WINARNI', 'pin' => '000355'],
            ['name' => 'SUPARTININGTYAS', 'pin' => '000525'],
            ['name' => 'SUYANTI', 'pin' => '000926'],
            ['name' => 'TRI WULANDARI', 'pin' => '002196'],
            ['name' => 'WINARNI', 'pin' => '000051'],
            ['name' => 'YULIANI', 'pin' => '000449'],
            ['name' => 'YULIATI', 'pin' => '000450'],
        ];

        foreach ($operators as $operator) {
            if (!User::where('name', $operator['name'])->exists()) {
                User::forceCreate([
                    'name'     => $operator['name'],
                    'pin'      => $operator['pin'],
                    'shift'    => null,
                    'role'     => 'User',
                    'email'    => null,
                    'password' => bcrypt($operator['pin']),
                ]);
            }
        }
    }
}
