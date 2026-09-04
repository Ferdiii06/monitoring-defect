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
        // Check if admin already exists to prevent duplicate key
        if (!User::where('name', 'Admin QA')->exists()) {
            User::create([
                'name' => 'Admin QA',
                'email' => 'admin@example.com',
                'password' => Hash::make('123456'), // 6-digit PIN as password
            ]);
        }
    }
}

