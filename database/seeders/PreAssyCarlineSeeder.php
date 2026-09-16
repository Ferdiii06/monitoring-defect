<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Carline;

class PreAssyCarlineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $preAssyCarlines = [
            '852W/853W',
            '909W/910W',
            'Battery Cable',
            'DFM',
            'J72A',
            'GT43',
            '664W',
            'Toyota Gedung C',
        ];

        foreach ($preAssyCarlines as $name) {
            Carline::firstOrCreate(
                [
                    'name' => $name,
                    'car_type_id' => null,
                ]
            );
        }
    }
}
