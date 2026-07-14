<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Defect;
use App\Models\ActivityLog;
use App\Services\ExternalApiService;
use Carbon\Carbon;
use App\Exports\FinalAssyExport;
use App\Exports\PreAssyExport;
use App\Exports\LogSystemExport;
use App\Exports\RecentDefectsExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Display the Final Assy defect report with filters and database pagination.
     */
    public function index(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        // Sync data dari API eksternal ke database lokal
        try {
            ExternalApiService::syncFromApi();
        } catch (\Exception $e) {
            \Log::warning('FinalAssy: External API sync skipped - ' . $e->getMessage());
        }

        // 1. Buat Query awal
        $query = Defect::where('jenis_assy', 'Final Assy');

        // 2. Ambil parameter filter
        $dateRange = $request->input('date_range');
        $selectedDefect = $request->input('defect');
        $selectedLine = $request->input('line');
        $selectedConveyor = $request->input('conveyor');

        // 3. Terapkan Filter Tanggal
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $query->whereBetween('waktu', [$startDate, $endDate]);
            } else if (count($dates) === 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();
                $query->whereBetween('waktu', [$startDate, $endDate]);
            }
        }

        // Terapkan Filter Jenis Defect
        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }

        // Terapkan Filter Line (Mobil)
        if ($selectedLine && $selectedLine !== 'all') {
            $query->where('jenis_mobil', $selectedLine);
        }

        // Terapkan Filter Konveyor
        if ($selectedConveyor && $selectedConveyor !== 'all') {
            $query->where('conveyor', $selectedConveyor);
        }

        // 4. Ambil opsi filter unik langsung dari DB
        $defectOptions = [
            'INSER CIRCUIT',
            'DAMAGE/DEFORM/BROKEN PART',
            'MISSING PART',
            'DIMENSON DEFECT',
            'HALF LOCK / INCOMPLETE DOCKING',
            'WRONG PART',
            'TAPING DEFECT',
            'WRONG ORIENTATION PART',
            'CUTTING - CRIMPING PRE ASSY DEFECT',
            'INJECTION GROMMET / SISUI DEFECT',
            'OTHERS'
        ];

        $lineOptions = ['TOYOTA', 'NISSAN', 'MAZDA'];

        // 5. Paginate Data (10 baris per halaman)
        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return view('final_assy', [
            'records' => $records,
            'defectOptions' => $defectOptions,
            'lineOptions' => $lineOptions,
            'dateRange' => $dateRange,
            'selectedDefect' => $selectedDefect,
            'selectedLine' => $selectedLine,
            'selectedConveyor' => $selectedConveyor,
            'currentPage' => $records->currentPage(),
            'totalPages' => $records->lastPage(),
            'totalItems' => $records->total(),
            'allFilteredRecords' => $query->orderBy('waktu', 'desc')->get()
        ]);
    }

    /**
     * Display the Pre Assy defect report with filters and database pagination.
     */
    public function preAssy(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        // Sync data dari API eksternal ke database lokal
        try {
            ExternalApiService::syncFromApi();
        } catch (\Exception $e) {
            \Log::warning('PreAssy: External API sync skipped - ' . $e->getMessage());
        }

        // 1. Buat Query awal
        $query = Defect::where('jenis_assy', 'Pre Assy');

        // 2. Ambil parameter filter
        $dateRange = $request->input('date_range');
        $selectedDefect = $request->input('defect');
        $selectedLine = $request->input('line');
        $selectedConveyor = $request->input('conveyor');

        // 3. Terapkan Filter Tanggal
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $query->whereBetween('waktu', [$startDate, $endDate]);
            } else if (count($dates) === 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();
                $query->whereBetween('waktu', [$startDate, $endDate]);
            }
        }

        // Terapkan Filter Jenis Defect
        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }

        // Terapkan Filter Line (Mobil)
        if ($selectedLine && $selectedLine !== 'all') {
            $query->where('jenis_mobil', $selectedLine);
        }

        // Terapkan Filter Konveyor
        if ($selectedConveyor && $selectedConveyor !== 'all') {
            $query->where('conveyor', $selectedConveyor);
        }

        // 4. Ambil opsi filter unik langsung dari DB
        $defectOptions = [
            'CORE',
            'TERMINAL',
            'FRONT CRIMPING',
            'REAR  CRIMPING',
            'INSULATION',
            'SEAL SUMBER',
            'CRIMPING',
            'LAIN-LAIN'
        ];

        $lineOptions = ['TOYOTA', 'NISSAN', 'MAZDA'];

        // 5. Paginate Data (10 baris per halaman)
        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return view('pre_assy', [
            'records' => $records,
            'defectOptions' => $defectOptions,
            'lineOptions' => $lineOptions,
            'dateRange' => $dateRange,
            'selectedDefect' => $selectedDefect,
            'selectedLine' => $selectedLine,
            'selectedConveyor' => $selectedConveyor,
            'currentPage' => $records->currentPage(),
            'totalPages' => $records->lastPage(),
            'totalItems' => $records->total(),
            'allFilteredRecords' => $query->orderBy('waktu', 'desc')->get()
        ]);
    }

    /**
     * Display the System Log report with filters and database pagination.
     */
    public function logSystem(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        // Sync data dari API eksternal ke database lokal
        try {
            ExternalApiService::syncFromApi();
        } catch (\Exception $e) {
            \Log::warning('LogSystem: External API sync skipped - ' . $e->getMessage());
        }

        // 1. Buat Query awal
        $query = ActivityLog::query();

        // 2. Ambil parameter filter
        $dateRange = $request->input('date_range');
        $selectedAction = $request->input('action');
        $selectedDefect = $request->input('defect');

        // 3. Terapkan Filter Tanggal
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $query->whereBetween('waktu', [$startDate, $endDate]);
            } else if (count($dates) === 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();
                $query->whereBetween('waktu', [$startDate, $endDate]);
            }
        }

        // Terapkan Filter Jenis Aksi
        if ($selectedAction && $selectedAction !== 'all') {
            $query->where('jenis_aksi', $selectedAction);
        }

        // Terapkan Filter Jenis Defect
        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }

        // 4. Ambil opsi filter unik langsung dari DB
        $actionOptions = ActivityLog::distinct()
            ->pluck('jenis_aksi')
            ->sort()
            ->values()
            ->toArray();

        $defectOptions = ActivityLog::whereNotNull('jenis_defect')
            ->distinct()
            ->pluck('jenis_defect')
            ->sort()
            ->values()
            ->toArray();

        // 5. Paginate Data (10 baris per halaman)
        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return view('log_system', [
            'records' => $records,
            'actionOptions' => $actionOptions,
            'defectOptions' => $defectOptions,
            'dateRange' => $dateRange,
            'selectedAction' => $selectedAction,
            'selectedDefect' => $selectedDefect,
            'currentPage' => $records->currentPage(),
            'totalPages' => $records->lastPage(),
            'totalItems' => $records->total(),
            'allFilteredRecords' => $query->orderBy('waktu', 'desc')->get()
        ]);
    }

    /**
     * Export Final Assy defects to Excel.
     */
    public function exportFinalAssy(Request $request)
    {
        return Excel::download(new FinalAssyExport($request), 'report_final_assy_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Pre Assy defects to Excel.
     */
    public function exportPreAssy(Request $request)
    {
        return Excel::download(new PreAssyExport($request), 'report_pre_assy_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export System Logs to Excel.
     */
    public function exportLogSystem(Request $request)
    {
        return Excel::download(new LogSystemExport($request), 'log_system_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Display all recent defects (both Pre Assy and Final Assy) with filters and pagination.
     */
    public function recentDefects(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        // Sync data dari API eksternal ke database lokal
        try {
            ExternalApiService::syncFromApi();
        } catch (\Exception $e) {
            \Log::warning('RecentDefects: External API sync skipped - ' . $e->getMessage());
        }

        // 1. Buat Query awal
        $query = Defect::query();

        // 2. Ambil parameter filter
        $dateRange = $request->input('date_range');
        $selectedDefect = $request->input('defect');
        $selectedLine = $request->input('line');
        $selectedConveyor = $request->input('conveyor');
        $selectedAssy = $request->input('jenis_assy');

        // 3. Terapkan Filter Tanggal
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $query->whereBetween('waktu', [$startDate, $endDate]);
            } else if (count($dates) === 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();
                $query->whereBetween('waktu', [$startDate, $endDate]);
            }
        }

        // Terapkan Filter Jenis Assy
        if ($selectedAssy && $selectedAssy !== 'all') {
            $query->where('jenis_assy', $selectedAssy);
        }

        // Terapkan Filter Jenis Defect
        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }

        // Terapkan Filter Line (Mobil)
        if ($selectedLine && $selectedLine !== 'all') {
            $query->where('jenis_mobil', $selectedLine);
        }

        // Terapkan Filter Konveyor
        if ($selectedConveyor && $selectedConveyor !== 'all') {
            $query->where('conveyor', $selectedConveyor);
        }

        // 4. Ambil opsi filter unik langsung dari DB
        $preAssyDefects = [
            'CORE',
            'TERMINAL',
            'FRONT CRIMPING',
            'REAR  CRIMPING',
            'INSULATION',
            'SEAL SUMBER',
            'CRIMPING',
            'LAIN-LAIN'
        ];

        $finalAssyDefects = [
            'INSER CIRCUIT',
            'DAMAGE/DEFORM/BROKEN PART',
            'MISSING PART',
            'DIMENSON DEFECT',
            'HALF LOCK / INCOMPLETE DOCKING',
            'WRONG PART',
            'TAPING DEFECT',
            'WRONG ORIENTATION PART',
            'CUTTING - CRIMPING PRE ASSY DEFECT',
            'INJECTION GROMMET / SISUI DEFECT',
            'OTHERS'
        ];

        if ($selectedAssy === 'Pre Assy') {
            $defectOptions = $preAssyDefects;
        } elseif ($selectedAssy === 'Final Assy') {
            $defectOptions = $finalAssyDefects;
        } else {
            $defectOptions = array_merge($finalAssyDefects, $preAssyDefects);
        }

        $lineOptions = ['TOYOTA', 'NISSAN', 'MAZDA'];

        // 5. Paginate Data (10 baris per halaman)
        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return view('recent_defects', [
            'records' => $records,
            'defectOptions' => $defectOptions,
            'lineOptions' => $lineOptions,
            'dateRange' => $dateRange,
            'selectedDefect' => $selectedDefect,
            'selectedLine' => $selectedLine,
            'selectedConveyor' => $selectedConveyor,
            'selectedAssy' => $selectedAssy,
            'currentPage' => $records->currentPage(),
            'totalPages' => $records->lastPage(),
            'totalItems' => $records->total(),
            'allFilteredRecords' => $query->orderBy('waktu', 'desc')->get()
        ]);
    }

    /**
     * Export Recent defects to Excel.
     */
    public function exportRecentDefects(Request $request)
    {
        return Excel::download(new RecentDefectsExport($request), 'report_recent_defects_' . now()->format('Y-m-d') . '.xlsx');
    }
}
