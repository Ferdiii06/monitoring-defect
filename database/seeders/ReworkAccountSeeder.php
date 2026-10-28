<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ReworkAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'reworkA', 'pin' => '123456'],
            ['name' => 'reworkB', 'pin' => '123456'],
        ];

        foreach ($accounts as $account) {
            User::firstOrCreate(
                ['name' => $account['name']],
                [
                    'email'    => strtolower($account['name']) . '@example.com',
                    'password' => Hash::make($account['pin']),
                    'role'     => 'Rework',
                    'pin'      => $account['pin'],
                ]
            );
        }
    }
}
