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
        $selectedConveyor = $request->input('conveyor');

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

        // Filter by Line (Mobil)
        if ($selectedLine && $selectedLine !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedLine) {
                return (string)$record->line_conveyor === (string)$selectedLine;
            });
        }

        // Filter by Conveyor
        if ($selectedConveyor && $selectedConveyor !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedConveyor) {
                return (string)$record->konveyor === (string)$selectedConveyor;
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
            'selectedConveyor' => $selectedConveyor,
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
        $mobils = ['Toyota', 'Nissan', 'Mazda'];

        // Konveyor placeholder per mobil
        $konveyorByMobil = [
            'Toyota' => [
                '664W-C5', '664W-C5C', '664W-C5A', '664W-C5B', '664W-C5D',
                '711W TNGA-C5', '711W TNGA-C5A', '737W TNGA-C5A', '737W TNGA-C5',
                '738W-C5C', '858W-C5C', '810W-C5', '941W-C5', '023J-C5', '072Y-C5',
                '718W-AB5.HEV', '718W-C4.CONV', '718W-C4.TNGA', '891W/892W-C1.GAS LHD',
                '853W-AT2.HEV LHD', '853W-AT6.GAS LHD', '853W-AT16.GAS LHD',
                '852W-AT19.HEV PHV LHD', '852W-AT2.HEV PHV LHD', '852W-AT19.HEV PHV RHD',
                '852W-AT6.GAS LHD', '909W-AT7.GAS LHD', '909W-AT11.HEV LHD',
                '909W-AT9.GAS LHD', '910W-AT7.GAS LHD', '910W-AT11.HEV LHD',
                '910W-AT9.GAS LHD', '953W-C6.HEV RHD', '953W-C6.HEV LHD',
                '953W ENG NO.3-C9', '898W-AB5.HEV', '898W-C4.CONV', '898W-C4.TNGA'
            ],
            'Nissan' => [
                'P33A-B1.BAT', 'P33A-B1.CELL', 'J32V-B2.LHD', 'J32V-B2.RHD',
                'J42U-B3.EGI', 'J42U-B3.ENGINE', 'J42U-B2.DOOR RH', 'J42U-B2.DOOR LH',
                'P33C-B1.BAT', 'P33C-B1.CELL'
            ],
            'Mazda'  => [
                'J72A-12B.LHD', 'J72A-AB9.RHD', 'J72A-16C.LHD', 'J72K-16C.LHD',
                'J30A-AB6.EXTEND LHD', 'J30A-AB1.INPANEL LHD', 'J30A-AB6.EXTEND RHD', 'J30A-AB1.INPANEL RHD',
                'J69P-AB8.EXTEND LHD', 'J69P-AB8.INPANEL LHD', 'J69P-AB8.EXTEND RHD', 'J69P-AB8.INPANEL RHD',
                'J69P-AB9.EXTEND LHD', 'J69P-AB3.INPANEL LHD'
            ],
        ];
        
        $defects = [
            // 1. INSERT CIRCUIT
            ['jenis' => 'INSERT CIRCUIT', 'sub' => 'CROSS CIRCUIT'],
            ['jenis' => 'INSERT CIRCUIT', 'sub' => 'CIRCUIT NOT INSERTED'],
            ['jenis' => 'INSERT CIRCUIT', 'sub' => 'BENDING INSERT CIRCUIT'],
            ['jenis' => 'INSERT CIRCUIT', 'sub' => 'WRONG CAVITY'],
            ['jenis' => 'INSERT CIRCUIT', 'sub' => 'MISSING CIRCUIT'],
            ['jenis' => 'INSERT CIRCUIT', 'sub' => 'INCOMPLETE INSERT'],

            // 2. DAMAGE / DEFORM / BROKEN PART
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE CLIP'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE CONNECTOR'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE GROMMET'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE / SCRATCH INSULATION'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE PROTECTOR'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE SPACER'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE FUSE'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE BOLT / TORQUE'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE BAR'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE DUST'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE RELAY'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE CAP'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE COVER'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE SEAL RUBBER'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE BRACKET CONNECTOR'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE HOLDER FUSE'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'CUT WIRE'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DAMAGE LEVER'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'BENT TERMINAL'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'DEFORM TERMINAL'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'BROKEN TERMINAL'],
            ['jenis' => 'DAMAGE / DEFORM / BROKEN PART', 'sub' => 'FLAT TERMINAL'],

            // 3. MISSING PART
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING CLIP'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING COVER'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING GREASE'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING GROMMET'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING PROTECTOR'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING SEAL RUBBER'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING SPACER'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING SPOT TAPE'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING FOAM / TAPE'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING TIE BACK'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING TUBE'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING RESISTOR / BUS BAR'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING HOLDER'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING PLUG'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING CAP'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING RELAY'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING FUSE'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING MARKING / STAMP P/O'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING SOLDER'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING COT TUBE'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING BRACKET CONNECTOR'],
            ['jenis' => 'MISSING PART', 'sub' => 'MISSING WASHER HOSE'],

            // 4. DIMENSION DEFECT
            ['jenis' => 'DIMENSION DEFECT', 'sub' => 'DIMENSION BRANCH'],
            ['jenis' => 'DIMENSION DEFECT', 'sub' => 'DIMENSION POINT'],
            ['jenis' => 'DIMENSION DEFECT', 'sub' => 'DIMENSION CLIP'],
            ['jenis' => 'DIMENSION DEFECT', 'sub' => 'DIMENSION PROTECTOR'],
            ['jenis' => 'DIMENSION DEFECT', 'sub' => 'DIMENSION GROMMET'],
            ['jenis' => 'DIMENSION DEFECT', 'sub' => 'DIMENSION SLIT'],
            ['jenis' => 'DIMENSION DEFECT', 'sub' => 'DIMENSION TUBE'],

            // 5. HALF LOCK / INCOMPLETE DOCKING
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'HALF LOCK SPACER / RETAINER'],
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'HALF LOCK DOCKING CONNECTOR'],
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'HALF LOCK DOCKING P/O'],
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'HALF LOCK DOCKING T/TERMINAL'],
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'HALF LOCK COVER F/B'],
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'HALF LOCK PROTECTOR'],
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'HALF LOCK HOLDER FUSE'],
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'HALF LOCK INSERT RELAY'],
            ['jenis' => 'HALF LOCK / INCOMPLETE DOCKING', 'sub' => 'LOOSE TERMINAL'],

            // 6. WRONG PART
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG TERMINAL'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG SHRINK TUBE'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG CIRCUIT'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG CLIP'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG COVER'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG TAPE'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG GROMMET'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG PROTECTOR'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG SEAL RUBBER'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG SPACER / HOLDER'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG FOAM TAPE'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG TUBE'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG RESISTOR / BUS BAR'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG PLUG'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG FUSE'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG RELAY'],
            ['jenis' => 'WRONG PART', 'sub' => 'WRONG CAP'],

            // 7. TAPING DEFECT
            ['jenis' => 'TAPING DEFECT', 'sub' => 'WRONG TAPING METHOD'],
            ['jenis' => 'TAPING DEFECT', 'sub' => 'MISSING TAPING'],
            ['jenis' => 'TAPING DEFECT', 'sub' => 'WRONG SPOT TAPE'],
            ['jenis' => 'TAPING DEFECT', 'sub' => 'WRONG TIE BACK'],
            ['jenis' => 'TAPING DEFECT', 'sub' => 'TAPING WRINKLE'],

            // 8. WRONG ORIENTATION PART
            ['jenis' => 'WRONG ORIENTATION PART', 'sub' => 'ORIENTASI CLIP'],
            ['jenis' => 'WRONG ORIENTATION PART', 'sub' => 'ORIENTASI BRANCH'],
            ['jenis' => 'WRONG ORIENTATION PART', 'sub' => 'ORIENTASI GROMMET'],
            ['jenis' => 'WRONG ORIENTATION PART', 'sub' => 'ORIENTASI COVER CONN'],
            ['jenis' => 'WRONG ORIENTATION PART', 'sub' => 'ORIENTASI CAP'],
            ['jenis' => 'WRONG ORIENTATION PART', 'sub' => 'ORIENTASI TIE BACK'],

            // 9. CUTTING / CRIMPING PRE ASSY DEFECT
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'SALAH STRIPING / PEEL'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'SLIT / PEEL MELINTIR'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'OVER/LIMIT SHRINK TUBE'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'BARE CORE'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'RAMBUT CORE KELUAR'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'CORE BENDING / KNEEL'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'OVER CIRCUIT BONDER'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'MISSING CIRCUIT BONDER'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'SALAH CIRCUIT BONDER'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'SALAH BIND TAPE'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'SALAH CUTTING'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'INSULATION KUPASAN / SCRATCH'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'SALAH PILIHAN DRUM CORE'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'TERKUPAS CORE'],
            ['jenis' => 'CUTTING / CRIMPING PRE ASSY DEFECT', 'sub' => 'CRACK TERMINAL'],

            // 10. INJECTION GROMMET / SILI DEFECT
            ['jenis' => 'INJECTION GROMMET / SILI DEFECT', 'sub' => 'INJECTION GROMMET DEFORM / PEELING'],
            ['jenis' => 'INJECTION GROMMET / SILI DEFECT', 'sub' => 'INJECTION GROMMET LUBANG'],
            ['jenis' => 'INJECTION GROMMET / SILI DEFECT', 'sub' => 'INJECTION GROMMET TIADA MATANG'],
            ['jenis' => 'INJECTION GROMMET / SILI DEFECT', 'sub' => 'BUBBLE DI GROMMET'],

            // 11. OTHERS
            ['jenis' => 'OTHERS', 'sub' => 'FOREIGN MATERIAL'],
            ['jenis' => 'OTHERS', 'sub' => 'CIRCUIT TERPILIT'],
            ['jenis' => 'OTHERS', 'sub' => 'ANTO RECIP PECAH'],
            ['jenis' => 'OTHERS', 'sub' => 'BAND CLIP KEPENDEKAN'],
            ['jenis' => 'OTHERS', 'sub' => 'BAND CLIP KEPANJANGAN'],
        ];

        $records = [];
        
        // Seed first 2 matching the exact screenshots & seeders
        $records[] = (object)[
            'waktu' => '2026-06-26 18:45:21',
            'user_name' => 'Budi Santoso',
            'jenis_assy' => 'Final Assy',
            'line_conveyor' => 'Toyota',
            'konveyor' => '664W-C5',
            'jenis_defect' => 'INSERT CIRCUIT',
            'jenis_sub_defect' => 'CROSS CIRCUIT',
            'quantity' => 1,
        ];

        $records[] = (object)[
            'waktu' => '2026-06-26 16:05:11',
            'user_name' => 'Dewi Lestari',
            'jenis_assy' => 'Final Assy',
            'line_conveyor' => 'Nissan',
            'konveyor' => 'P33A-B1.BAT',
            'jenis_defect' => 'MISSING PART',
            'jenis_sub_defect' => 'MISSING CLIP',
            'quantity' => 1,
        ];

        // Generate 48 more static records deterministically
        for ($i = 1; $i <= 48; $i++) {
            $user = $users[$i % count($users)];
            $mobil = $mobils[$i % count($mobils)];
            $konveyorList = $konveyorByMobil[$mobil];
            $konveyor = $konveyorList[$i % count($konveyorList)];
            $defect = $defects[($i * 7) % count($defects)];
            $qty = ($i % 3) + 1;
            
            $day = 28 - ($i % 8);
            $hour = 8 + ($i % 10);
            $minute = ($i * 12) % 60;
            $second = ($i * 19) % 60;
            
            $waktu = sprintf('2026-06-%02d %02d:%02d:%02d', $day, $hour, $minute, $second);

            $records[] = (object)[
                'waktu' => $waktu,
                'user_name' => $user,
                'jenis_assy' => 'Final Assy',
                'line_conveyor' => $mobil,
                'konveyor' => $konveyor,
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
        $selectedConveyor = $request->input('conveyor');

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

        // Filter by Line (Mobil)
        if ($selectedLine && $selectedLine !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedLine) {
                return (string)$record->line_conveyor === (string)$selectedLine;
            });
        }

        // Filter by Conveyor
        if ($selectedConveyor && $selectedConveyor !== 'all') {
            $filteredRecords = array_filter($filteredRecords, function ($record) use ($selectedConveyor) {
                return (string)$record->konveyor === (string)$selectedConveyor;
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
            'selectedConveyor' => $selectedConveyor,
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
        $mobils = ['Toyota', 'Nissan', 'Mazda'];

        // Konveyor placeholder per mobil
        $konveyorByMobil = [
            'Toyota' => [
                '664W-C5', '664W-C5C', '664W-C5A', '664W-C5B', '664W-C5D',
                '711W TNGA-C5', '711W TNGA-C5A', '737W TNGA-C5A', '737W TNGA-C5',
                '738W-C5C', '858W-C5C', '810W-C5', '941W-C5', '023J-C5', '072Y-C5',
                '718W-AB5.HEV', '718W-C4.CONV', '718W-C4.TNGA', '891W/892W-C1.GAS LHD',
                '853W-AT2.HEV LHD', '853W-AT6.GAS LHD', '853W-AT16.GAS LHD',
                '852W-AT19.HEV PHV LHD', '852W-AT2.HEV PHV LHD', '852W-AT19.HEV PHV RHD',
                '852W-AT6.GAS LHD', '909W-AT7.GAS LHD', '909W-AT11.HEV LHD',
                '909W-AT9.GAS LHD', '910W-AT7.GAS LHD', '910W-AT11.HEV LHD',
                '910W-AT9.GAS LHD', '953W-C6.HEV RHD', '953W-C6.HEV LHD',
                '953W ENG NO.3-C9', '898W-AB5.HEV', '898W-C4.CONV', '898W-C4.TNGA'
            ],
            'Nissan' => [
                'P33A-B1.BAT', 'P33A-B1.CELL', 'J32V-B2.LHD', 'J32V-B2.RHD',
                'J42U-B3.EGI', 'J42U-B3.ENGINE', 'J42U-B2.DOOR RH', 'J42U-B2.DOOR LH',
                'P33C-B1.BAT', 'P33C-B1.CELL'
            ],
            'Mazda'  => [
                'J72A-12B.LHD', 'J72A-AB9.RHD', 'J72A-16C.LHD', 'J72K-16C.LHD',
                'J30A-AB6.EXTEND LHD', 'J30A-AB1.INPANEL LHD', 'J30A-AB6.EXTEND RHD', 'J30A-AB1.INPANEL RHD',
                'J69P-AB8.EXTEND LHD', 'J69P-AB8.INPANEL LHD', 'J69P-AB8.EXTEND RHD', 'J69P-AB8.INPANEL RHD',
                'J69P-AB9.EXTEND LHD', 'J69P-AB3.INPANEL LHD'
            ],
        ];
        
        $defects = [
            // A. CORE
            ['jenis' => 'CORE', 'sub' => 'FRAYING'],
            ['jenis' => 'CORE', 'sub' => 'CUT CORE'],
            ['jenis' => 'CORE', 'sub' => 'TIDAK TERATUR'],
            ['jenis' => 'CORE', 'sub' => 'MAJU'],
            ['jenis' => 'CORE', 'sub' => 'MUNDUR'],
            ['jenis' => 'CORE', 'sub' => 'TIDAK TERCRIMPING'],
            ['jenis' => 'CORE', 'sub' => 'SCRATCH'],

            // B. TERMINAL
            ['jenis' => 'TERMINAL', 'sub' => 'TERGORES'],
            ['jenis' => 'TERMINAL', 'sub' => 'BENT UP'],
            ['jenis' => 'TERMINAL', 'sub' => 'BENT DOWN'],
            ['jenis' => 'TERMINAL', 'sub' => 'MELINTIR'],
            ['jenis' => 'TERMINAL', 'sub' => 'UJUNG TERPOTONG'],
            ['jenis' => 'TERMINAL', 'sub' => 'OPEN/FLARE'],
            ['jenis' => 'TERMINAL', 'sub' => 'DEFORM'],
            ['jenis' => 'TERMINAL', 'sub' => 'BRIDGE TERLALU PANJANG'],
            ['jenis' => 'TERMINAL', 'sub' => 'CANTILEVER RUSAK'],
            ['jenis' => 'TERMINAL', 'sub' => 'LEPAS DARI CIRCUIT'],

            // C. FRONT CRIMPING
            ['jenis' => 'FRONT CRIMPING', 'sub' => 'C/H TERLALU TINGGI'],
            ['jenis' => 'FRONT CRIMPING', 'sub' => 'C/H TERLALU RENDAH'],
            ['jenis' => 'FRONT CRIMPING', 'sub' => 'C/W TERLALU TINGGI'],
            ['jenis' => 'FRONT CRIMPING', 'sub' => 'C/W TERLALU RENDAH'],
            ['jenis' => 'FRONT CRIMPING', 'sub' => 'FLASH'],

            // D. REAR CRIMPING
            ['jenis' => 'REAR CRIMPING', 'sub' => 'C/H TERLALU TINGGI'],
            ['jenis' => 'REAR CRIMPING', 'sub' => 'C/H TERLALU RENDAH'],
            ['jenis' => 'REAR CRIMPING', 'sub' => 'C/W TERLALU TINGGI'],
            ['jenis' => 'REAR CRIMPING', 'sub' => 'C/W TERLALU RENDAH'],
            ['jenis' => 'REAR CRIMPING', 'sub' => 'ADA DI DALAM INSULASI'],
            ['jenis' => 'REAR CRIMPING', 'sub' => 'TIDAK SEIMBANG'],

            // E. INSULATION
            ['jenis' => 'INSULATION', 'sub' => 'TERCRIMPING'],
            ['jenis' => 'INSULATION', 'sub' => 'TERLALU MUNDUR'],
            ['jenis' => 'INSULATION', 'sub' => 'DAMAGE'],
            ['jenis' => 'INSULATION', 'sub' => 'TIDAK RATA'],

            // F. SEAL RUBBER
            ['jenis' => 'SEAL RUBBER', 'sub' => 'TERPOTONG'],
            ['jenis' => 'SEAL RUBBER', 'sub' => 'TERBALIK'],
            ['jenis' => 'SEAL RUBBER', 'sub' => 'TERLALU MUNDUR'],
            ['jenis' => 'SEAL RUBBER', 'sub' => 'TERLALU MAJU'],
            ['jenis' => 'SEAL RUBBER', 'sub' => 'TERCRIMPING'],
            ['jenis' => 'SEAL RUBBER', 'sub' => 'MISSING'],
            ['jenis' => 'SEAL RUBBER', 'sub' => 'SEAL SOBEK'],

            // G. CRIMPING
            ['jenis' => 'CRIMPING', 'sub' => 'FOREIGN MATERIAL'],
            ['jenis' => 'CRIMPING', 'sub' => 'ADA 2 TERMINAL TERCRIMPING'],
            ['jenis' => 'CRIMPING', 'sub' => 'NO CORE'],
            ['jenis' => 'CRIMPING', 'sub' => 'NO STRIPPING'],

            // H. LAIN-LAIN
            ['jenis' => 'LAIN-LAIN', 'sub' => 'LANCE RUSAK'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'STABILIZER RUSAK'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'BELLMOUTH TIDAK STANDART'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'KONDISI CORE BAG.A'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'RESIN MASUK BAG.A'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'RESIN BAREL BAG. B TERBUKA'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'CORE TERLIHAT ATAS SISI C'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'CORE TERLIHAT SAMPING SISI C'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'SISI PUNGGUNG'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'ABNORMAL RESIN'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'PANJANG WELDING N-OK'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'CIRCUIT TIDAK TERBONDER'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'BONDER RETAK'],
            ['jenis' => 'LAIN-LAIN', 'sub' => 'STRIPPING KEPANJANGAN'],
        ];

        $records = [];
        
        // Seed first 2 matching the exact database seeder
        $records[] = (object)[
            'waktu' => '2026-06-26 16:30:10',
            'user_name' => 'Siti Nurhaliza',
            'jenis_assy' => 'Pre Assy',
            'line_conveyor' => 'Toyota',
            'konveyor' => '664W-C5',
            'jenis_defect' => 'CORE',
            'jenis_sub_defect' => 'FRAYING',
            'quantity' => 1,
        ];

        $records[] = (object)[
            'waktu' => '2026-06-26 16:15:32',
            'user_name' => 'Ahmad Fauzi',
            'jenis_assy' => 'Pre Assy',
            'line_conveyor' => 'Nissan',
            'konveyor' => 'P33A-B1.BAT',
            'jenis_defect' => 'TERMINAL',
            'jenis_sub_defect' => 'TERGORES',
            'quantity' => 1,
        ];

        // Generate 48 more static records deterministically
        for ($i = 1; $i <= 48; $i++) {
            $user = $users[$i % count($users)];
            $mobil = $mobils[$i % count($mobils)];
            $konveyorList = $konveyorByMobil[$mobil];
            $konveyor = $konveyorList[$i % count($konveyorList)];
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
                'line_conveyor' => $mobil,
                'konveyor' => $konveyor,
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
