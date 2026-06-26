<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with static mock data.
     */
    public function index()
    {
        if (!session('logged_in')) {
            return redirect('/');
        }

        $totalDefect = 1250;
        $defectToday = 72;
        $activeUsers = 12;
        $totalUsers = 45;

        // Static recent defects logs (matching screenshot exactly)
        $recentDefects = [
            (object)[
                'waktu' => '2026-06-26 18:45:21',
                'user_name' => 'Budi Santoso',
                'jenis_assy' => 'Final Assy',
                'line_conveyor' => '1',
                'jenis_defect' => 'INSERT CIRCUIT',
                'jenis_sub_defect' => 'CROSS CIRCUIT',
                'quantity' => 1,
            ],
            (object)[
                'waktu' => '2026-06-26 16:30:10',
                'user_name' => 'Siti Nurhaliza',
                'jenis_assy' => 'Pre Assy',
                'line_conveyor' => '2',
                'jenis_defect' => 'CORE',
                'jenis_sub_defect' => 'FRAYING',
                'quantity' => 1,
            ],
            (object)[
                'waktu' => '2026-06-26 16:15:32',
                'user_name' => 'Ahmad Fauzi',
                'jenis_assy' => 'Pre Assy',
                'line_conveyor' => '3',
                'jenis_defect' => 'TERMINAL',
                'jenis_sub_defect' => 'TERGORES',
                'quantity' => 1,
            ],
            (object)[
                'waktu' => '2026-06-26 16:05:11',
                'user_name' => 'Dewi Lestari',
                'jenis_assy' => 'Final Assy',
                'line_conveyor' => '4',
                'jenis_defect' => 'MISSING PART',
                'jenis_sub_defect' => 'MISSING CLIP',
                'quantity' => 1,
            ],
        ];

        return view('dashboard', compact(
            'totalDefect',
            'defectToday',
            'activeUsers',
            'totalUsers',
            'recentDefects'
        ));
    }
}
