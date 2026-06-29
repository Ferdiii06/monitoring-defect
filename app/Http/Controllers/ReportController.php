<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display the Final Assy defect report with filters and custom pagination.
     */
    public function index(Request $request)
    {
        if (!session('logged_in')) {
            return redirect('/');
        }

        // 1. Generate 50 realistic static records for Final Assy
        $allRecords = $this->getMockFinalAssyRecords();

        // Extract unique options for filter dropdowns
        $defectOptions = array_unique(array_column($allRecords, 'jenis_defect'));
        sort($defectOptions);

        $lineOptions = array_unique(array_column($allRecords, 'line_conveyor'));
        sort($lineOptions);

        // 2. Extract filter inputs
        $dateRange = $request->input('date_range'); // format "YYYY-MM-DD to YYYY-MM-DD"
        $selectedDefect = $request->input('defect');
        $selectedLine = $request->input('line');

        // 3. Apply Filters
        $filteredRecords = $allRecords;

        // Filter by Date Range
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();

                $filteredRecords = array_filter($filteredRecords, function ($record) use ($startDate, $endDate) {
                    $recordDate = Carbon::parse($record->waktu);
                    return $recordDate->between($startDate, $endDate);
                });
            } else if (count($dates) === 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();

                $filteredRecords = array_filter($filteredRecords, function ($record) use ($startDate, $endDate) {
                    $recordDate = Carbon::parse($record->waktu);
                    return $recordDate->between($startDate, $endDate);
                });
            }
        }

        // Filter by Defect Type
        if ($selectedDefect && $selectedDefect !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedDefect) {
                return $record->jenis_defect === $selectedDefect;
            });
        }

        // Filter by Line
        if ($selectedLine && $selectedLine !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedLine) {
                return (string)$record->line_conveyor === (string)$selectedLine;
            });
        }

        // Re-index array after filtering
        $filteredRecords = array_values($filteredRecords);

        // 4. Custom Pagination Implementation
        $perPage = 10;
        $totalItems = count($filteredRecords);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = (int) $request->input('page', 1);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;

        $paginatedRecords = array_slice($filteredRecords, $offset, $perPage);

        return view('final_assy', [
            'records' => $paginatedRecords,
            'defectOptions' => $defectOptions,
            'lineOptions' => $lineOptions,
            'dateRange' => $dateRange,
            'selectedDefect' => $selectedDefect,
            'selectedLine' => $selectedLine,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'allFilteredRecords' => $filteredRecords // passed for easy JSON/JS export
        ]);
    }

    /**
     * Provide 50 static mock records matching the system style
     */
    private function getMockFinalAssyRecords()
    {
        $users = ['Budi Santoso', 'Dewi Lestari', 'Ahmad Fauzi', 'Siti Nurhaliza', 'Rian Hidayat', 'Dina Lestari', 'Hendra Wijaya', 'Siska Amelia'];
        $lines = ['1', '2', '3', '4', '5'];
        
        $defects = [
            ['jenis' => 'INSERT CIRCUIT', 'sub' => 'CROSS CIRCUIT'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING CLIP'],
            ['jenis' => 'SHORT CIRCUIT', 'sub' => 'EXPOSED WIRE'],
            ['jenis' => 'CONNECTOR CRACK', 'sub' => 'HOUSING DAMAGED'],
            ['jenis' => 'TAPE WRAPPING', 'sub' => 'UNTIDY BINDING'],
            ['jenis' => 'PIN BENT', 'sub' => 'PIN DEFORMED'],
            ['jenis' => 'TERMINAL', 'sub' => 'LOOSE CRIMPING']
        ];

        $records = [];
        
        // Start date: 2026-06-20, End date: 2026-06-29
        // Seed first 2 matching the exact screenshots & seeders
        $records[] = (object)[
            'waktu' => '2026-06-26 18:45:21',
            'user_name' => 'Budi Santoso',
            'jenis_assy' => 'Final Assy',
            'line_conveyor' => '1',
            'jenis_defect' => 'INSERT CIRCUIT',
            'jenis_sub_defect' => 'CROSS CIRCUIT',
            'quantity' => 1,
        ];

        $records[] = (object)[
            'waktu' => '2026-06-26 16:05:11',
            'user_name' => 'Dewi Lestari',
            'jenis_assy' => 'Final Assy',
            'line_conveyor' => '4',
            'jenis_defect' => 'MISSING PART',
            'jenis_sub_defect' => 'MISSING CLIP',
            'quantity' => 1,
        ];

        // Generate 48 more static records deterministically
        for ($i = 1; $i <= 48; $i++) {
            // Pick index based on loop to remain deterministic
            $user = $users[$i % count($users)];
            $line = $lines[($i * 3) % count($lines)];
            $defect = $defects[($i * 7) % count($defects)];
            $qty = ($i % 3) + 1;
            
            // Generate sequential timestamps backwards from 2026-06-28
            $day = 28 - ($i % 8); // spreads over 2026-06-20 to 2026-06-28
            $hour = 8 + ($i % 10);
            $minute = ($i * 12) % 60;
            $second = ($i * 19) % 60;
            
            $waktu = sprintf('2026-06-%02d %02d:%02d:%02d', $day, $hour, $minute, $second);

            $records[] = (object)[
                'waktu' => $waktu,
                'user_name' => $user,
                'jenis_assy' => 'Final Assy',
                'line_conveyor' => $line,
                'jenis_defect' => $defect['jenis'],
                'jenis_sub_defect' => $defect['sub'],
                'quantity' => $qty,
            ];
        }

        // Sort records by waktu DESC
        usort($records, function ($a, $b) {
            return strcmp($b->waktu, $a->waktu);
        });

        return $records;
    }

    /**
     * Display the Pre Assy defect report with filters and custom pagination.
     */
    public function preAssy(Request $request)
    {
        if (!session('logged_in')) {
            return redirect('/');
        }

        // 1. Generate 50 realistic static records for Pre Assy
        $allRecords = $this->getMockPreAssyRecords();

        // Extract unique options for filter dropdowns
        $defectOptions = array_unique(array_column($allRecords, 'jenis_defect'));
        sort($defectOptions);

        $lineOptions = array_unique(array_column($allRecords, 'line_conveyor'));
        sort($lineOptions);

        // 2. Extract filter inputs
        $dateRange = $request->input('date_range'); // format "YYYY-MM-DD to YYYY-MM-DD"
        $selectedDefect = $request->input('defect');
        $selectedLine = $request->input('line');

        // 3. Apply Filters
        $filteredRecords = $allRecords;

        // Filter by Date Range
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();

                $filteredRecords = array_filter($filteredRecords, function ($record) use ($startDate, $endDate) {
                    $recordDate = Carbon::parse($record->waktu);
                    return $recordDate->between($startDate, $endDate);
                });
            } else if (count($dates) === 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();

                $filteredRecords = array_filter($filteredRecords, function ($record) use ($startDate, $endDate) {
                    $recordDate = Carbon::parse($record->waktu);
                    return $recordDate->between($startDate, $endDate);
                });
            }
        }

        // Filter by Defect Type
        if ($selectedDefect && $selectedDefect !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedDefect) {
                return $record->jenis_defect === $selectedDefect;
            });
        }

        // Filter by Line
        if ($selectedLine && $selectedLine !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedLine) {
                return (string)$record->line_conveyor === (string)$selectedLine;
            });
        }

        // Re-index array after filtering
        $filteredRecords = array_values($filteredRecords);

        // 4. Custom Pagination Implementation
        $perPage = 10;
        $totalItems = count($filteredRecords);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = (int) $request->input('page', 1);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;

        $paginatedRecords = array_slice($filteredRecords, $offset, $perPage);

        return view('pre_assy', [
            'records' => $paginatedRecords,
            'defectOptions' => $defectOptions,
            'lineOptions' => $lineOptions,
            'dateRange' => $dateRange,
            'selectedDefect' => $selectedDefect,
            'selectedLine' => $selectedLine,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'allFilteredRecords' => $filteredRecords // passed for easy JSON/JS export
        ]);
    }

    /**
     * Provide 50 static mock records for Pre Assy matching the system style
     */
    private function getMockPreAssyRecords()
    {
        $users = ['Budi Santoso', 'Dewi Lestari', 'Ahmad Fauzi', 'Siti Nurhaliza', 'Rian Hidayat', 'Dina Lestari', 'Hendra Wijaya', 'Siska Amelia'];
        $lines = ['1', '2', '3', '4', '5'];
        
        $defects = [
            ['jenis' => 'CORE', 'sub' => 'FRAYING'],
            ['jenis' => 'TERMINAL', 'sub' => 'TERGORES'],
            ['jenis' => 'INSULATION', 'sub' => 'STRIPPED'],
            ['jenis' => 'WIRE', 'sub' => 'DAMAGED'],
            ['jenis' => 'CRIMPING', 'sub' => 'OVER-CRIMPED'],
            ['jenis' => 'JOINT', 'sub' => 'POOR SOLDER'],
            ['jenis' => 'SHIELDING', 'sub' => 'MISALIGNED']
        ];

        $records = [];
        
        // Seed first 2 matching the exact database seeder
        $records[] = (object)[
            'waktu' => '2026-06-26 16:30:10',
            'user_name' => 'Siti Nurhaliza',
            'jenis_assy' => 'Pre Assy',
            'line_conveyor' => '2',
            'jenis_defect' => 'CORE',
            'jenis_sub_defect' => 'FRAYING',
            'quantity' => 1,
        ];

        $records[] = (object)[
            'waktu' => '2026-06-26 16:15:32',
            'user_name' => 'Ahmad Fauzi',
            'jenis_assy' => 'Pre Assy',
            'line_conveyor' => '3',
            'jenis_defect' => 'TERMINAL',
            'jenis_sub_defect' => 'TERGORES',
            'quantity' => 1,
        ];

        // Generate 48 more static records deterministically
        for ($i = 1; $i <= 48; $i++) {
            $user = $users[$i % count($users)];
            $line = $lines[($i * 4) % count($lines)];
            $defect = $defects[($i * 3) % count($defects)];
            $qty = ($i % 2) + 1;
            
            $day = 28 - ($i % 8);
            $hour = 7 + ($i % 11);
            $minute = ($i * 13) % 60;
            $second = ($i * 23) % 60;
            
            $waktu = sprintf('2026-06-%02d %02d:%02d:%02d', $day, $hour, $minute, $second);

            $records[] = (object)[
                'waktu' => $waktu,
                'user_name' => $user,
                'jenis_assy' => 'Pre Assy',
                'line_conveyor' => $line,
                'jenis_defect' => $defect['jenis'],
                'jenis_sub_defect' => $defect['sub'],
                'quantity' => $qty,
            ];
        }

        // Sort records by waktu DESC
        usort($records, function ($a, $b) {
            return strcmp($b->waktu, $a->waktu);
        });

        return $records;
    }

    /**
     * Display the System Log report with filters and custom pagination.
     */
    public function logSystem(Request $request)
    {
        if (!session('logged_in')) {
            return redirect('/');
        }

        // 1. Generate 50 realistic static records for System Logs
        $allRecords = $this->getMockSystemLogs();

        // Extract unique options for filter dropdowns
        $actionOptions = array_unique(array_column($allRecords, 'jenis_aksi'));
        sort($actionOptions);

        $defectOptions = array_filter(array_unique(array_column($allRecords, 'jenis_defect')), function($val) {
            return $val && $val !== 'none';
        });
        sort($defectOptions);

        // 2. Extract filter inputs
        $dateRange = $request->input('date_range'); // format "YYYY-MM-DD to YYYY-MM-DD"
        $selectedAction = $request->input('action');
        $selectedDefect = $request->input('defect');

        // 3. Apply Filters
        $filteredRecords = $allRecords;

        // Filter by Date Range
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();

                $filteredRecords = array_filter($filteredRecords, function ($record) use ($startDate, $endDate) {
                    $recordDate = Carbon::parse($record->waktu);
                    return $recordDate->between($startDate, $endDate);
                });
            } else if (count($dates) === 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();

                $filteredRecords = array_filter($filteredRecords, function ($record) use ($startDate, $endDate) {
                    $recordDate = Carbon::parse($record->waktu);
                    return $recordDate->between($startDate, $endDate);
                });
            }
        }

        // Filter by Action Type
        if ($selectedAction && $selectedAction !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedAction) {
                return $record->jenis_aksi === $selectedAction;
            });
        }

        // Filter by Defect Type
        if ($selectedDefect && $selectedDefect !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedDefect) {
                return $record->jenis_defect === $selectedDefect;
            });
        }

        // Re-index array after filtering
        $filteredRecords = array_values($filteredRecords);

        // 4. Custom Pagination Implementation
        $perPage = 10;
        $totalItems = count($filteredRecords);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = (int) $request->input('page', 1);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;

        $paginatedRecords = array_slice($filteredRecords, $offset, $perPage);

        return view('log_system', [
            'records' => $paginatedRecords,
            'actionOptions' => $actionOptions,
            'defectOptions' => $defectOptions,
            'dateRange' => $dateRange,
            'selectedAction' => $selectedAction,
            'selectedDefect' => $selectedDefect,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'allFilteredRecords' => $filteredRecords // passed for easy JSON/JS export
        ]);
    }

    /**
     * Provide 50 static mock records for System Logs matching the design
     */
    private function getMockSystemLogs()
    {
        $users = ['Siti Nurhaliza', 'Budi Santoso', 'Ahmad Fauzi', 'Dewi Lestari', 'Hendra Wijaya', 'Admin QA'];
        $ips = ['192.168.1.10', '192.168.1.12', '192.168.1.15', '192.168.1.20'];
        
        $actions = [
            [
                'aksi' => 'Create Report',
                'aktivitas' => 'Melaporkan defect Pre Assy - Line 02 - Jumlah 15',
                'defect' => 'CORE'
            ],
            [
                'aksi' => 'Delete Report',
                'aktivitas' => 'Menghapus report defect Final Assy - Line 01',
                'defect' => 'INSERT CIRCUIT'
            ],
            [
                'aksi' => 'Update Report',
                'aktivitas' => 'Mengubah jumlah defect menjadi 20',
                'defect' => 'TERMINAL'
            ],
            [
                'aksi' => 'Create Account',
                'aktivitas' => 'Membuat akun baru - User: Andi Saputra',
                'defect' => 'none'
            ],
            [
                'aksi' => 'Create Report',
                'aktivitas' => 'Melaporkan defect Final Assy - Line 04 - Jumlah 8',
                'defect' => 'MISSING PART'
            ],
            [
                'aksi' => 'Update Report',
                'aktivitas' => 'Mengubah status defect Pre Assy - Line 03',
                'defect' => 'CORE'
            ],
            [
                'aksi' => 'Create Account',
                'aktivitas' => 'Membuat akun baru - User: Rian Hidayat',
                'defect' => 'none'
            ]
        ];

        $records = [];
        
        // Seed first 5 matching screenshot exactly
        $records[] = (object)[
            'waktu' => '2026-06-26 16:30:10',
            'user_name' => 'Siti Nurhaliza',
            'jenis_aksi' => 'Create Report',
            'aktivitas' => 'Melaporkan defect Pre Assy - Line 02 - Jumlah 15',
            'jenis_defect' => 'CORE',
            'ip_address' => '192.168.1.10',
        ];

        $records[] = (object)[
            'waktu' => '2026-06-26 16:30:10', // exact timestamp for duplicate layout matching
            'user_name' => 'Siti Nurhaliza',
            'jenis_aksi' => 'Delete Report',
            'aktivitas' => 'Menghapus report defect Final Assy - Line 01',
            'jenis_defect' => 'INSERT CIRCUIT',
            'ip_address' => '192.168.1.10',
        ];

        $records[] = (object)[
            'waktu' => '2026-06-26 16:30:10',
            'user_name' => 'Siti Nurhaliza',
            'jenis_aksi' => 'Update Report',
            'aktivitas' => 'Mengubah jumlah defect menjadi 20',
            'jenis_defect' => 'TERMINAL',
            'ip_address' => '192.168.1.10',
        ];

        $records[] = (object)[
            'waktu' => '2026-06-26 16:30:10',
            'user_name' => 'Siti Nurhaliza',
            'jenis_aksi' => 'Create Account',
            'aktivitas' => 'Membuat akun baru - User: Andi Saputra',
            'jenis_defect' => 'none',
            'ip_address' => '192.168.1.10',
        ];

        $records[] = (object)[
            'waktu' => '2026-06-26 16:30:10',
            'user_name' => 'Siti Nurhaliza',
            'jenis_aksi' => 'Create Account',
            'aktivitas' => 'Membuat akun baru - User: Andi Saputra',
            'jenis_defect' => 'none',
            'ip_address' => '192.168.1.10',
        ];

        // Generate 45 more static records
        for ($i = 1; $i <= 45; $i++) {
            $user = $users[$i % count($users)];
            $ip = $ips[$i % count($ips)];
            $action = $actions[($i * 4) % count($actions)];
            
            $day = 28 - ($i % 8);
            $hour = 8 + ($i % 10);
            $minute = ($i * 14) % 60;
            $second = ($i * 27) % 60;
            
            $waktu = sprintf('2026-06-%02d %02d:%02d:%02d', $day, $hour, $minute, $second);

            $records[] = (object)[
                'waktu' => $waktu,
                'user_name' => $user,
                'jenis_aksi' => $action['aksi'],
                'aktivitas' => $action['aktivitas'],
                'jenis_defect' => $action['defect'],
                'ip_address' => $ip,
            ];
        }

        // Sort by waktu DESC
        usort($records, function ($a, $b) {
            return strcmp($b->waktu, $a->waktu);
        });

        return $records;
    }
}
