<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Defect;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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

        // 2. Seed Defect Records matching the system style
        Defect::truncate();

        $defects = [
            [
                'waktu' => Carbon::create(2026, 6, 26, 18, 45, 21),
                'user_name' => 'Budi Santoso',
                'jenis_assy' => 'Final Assy',
                'line_conveyor' => 'Toyota',
                'conveyor' => '664W-C5',
                'jenis_defect' => 'INSERT CIRCUIT',
                'jenis_sub_defect' => 'CROSS CIRCUIT',
                'quantity' => 1,
            ],
            [
                'waktu' => Carbon::create(2026, 6, 26, 16, 30, 10),
                'user_name' => 'Siti Nurhaliza',
                'jenis_assy' => 'Pre Assy',
                'line_conveyor' => 'Toyota',
                'conveyor' => '664W-C5',
                'jenis_defect' => 'CORE',
                'jenis_sub_defect' => 'FRAYING',
                'quantity' => 1,
            ],
            [
                'waktu' => Carbon::create(2026, 6, 26, 16, 15, 32),
                'user_name' => 'Ahmad Fauzi',
                'jenis_assy' => 'Pre Assy',
                'line_conveyor' => 'Nissan',
                'conveyor' => 'P33A-B1.BAT',
                'jenis_defect' => 'TERMINAL',
                'jenis_sub_defect' => 'TERGORES',
                'quantity' => 1,
            ],
            [
                'waktu' => Carbon::create(2026, 6, 26, 16, 05, 11),
                'user_name' => 'Dewi Lestari',
                'jenis_assy' => 'Final Assy',
                'line_conveyor' => 'Nissan',
                'conveyor' => 'P33A-B1.BAT',
                'jenis_defect' => 'MISSING PART',
                'jenis_sub_defect' => 'MISSING CLIP',
                'quantity' => 1,
            ],
        ];

        foreach ($defects as $defect) {
            Defect::create($defect);
        }

        // 3. Seed Activity Log Records
        \App\Models\ActivityLog::truncate();
        $logs = [
            [
                'waktu' => Carbon::create(2026, 6, 26, 16, 30, 10),
                'user_name' => 'Siti Nurhaliza',
                'jenis_aksi' => 'Create Report',
                'aktivitas' => 'Melaporkan defect Pre Assy - Toyota - Jumlah 15',
                'jenis_defect' => 'CORE',
                'ip_address' => '10.62.231.23',
            ],
            [
                'waktu' => Carbon::create(2026, 6, 26, 16, 30, 10),
                'user_name' => 'Siti Nurhaliza',
                'jenis_aksi' => 'Delete Report',
                'aktivitas' => 'Menghapus report defect Final Assy - Nissan',
                'jenis_defect' => 'INSERT CIRCUIT',
                'ip_address' => '10.62.231.23',
            ],
            [
                'waktu' => Carbon::create(2026, 6, 26, 16, 30, 10),
                'user_name' => 'Siti Nurhaliza',
                'jenis_aksi' => 'Update Report',
                'aktivitas' => 'Mengubah jumlah defect menjadi 20',
                'jenis_defect' => 'TERMINAL',
                'ip_address' => '10.62.231.23',
            ],
            [
                'waktu' => Carbon::create(2026, 6, 26, 16, 30, 10),
                'user_name' => 'Siti Nurhaliza',
                'jenis_aksi' => 'Create Account',
                'aktivitas' => 'Membuat akun baru - User: Andi Saputra',
                'jenis_defect' => 'none',
                'ip_address' => '10.62.231.23',
            ],
        ];

        foreach ($logs as $log) {
            \App\Models\ActivityLog::create($log);
        }
    }
}
