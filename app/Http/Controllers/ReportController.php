<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Defect;
use App\Models\ActivityLog;
use App\Services\ExternalApiService;
use App\Support\AdminReturnUrl;
use Carbon\Carbon;
use App\Exports\FinalAssyExport;
use App\Exports\PreAssyExport;
use App\Exports\LogSystemExport;
use App\Exports\RecentDefectsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CarType;
use App\Models\Carline;
use App\Models\DefectType;
use App\Models\InspectProcessType;
use App\Models\FinalAssyInspectType;
use App\Models\AreaDitemukan;
use App\Models\PreAssyMachine;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;

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

        // 1. Ambil parameter filter
        $dateRange = $request->input('date_range');
        $selectedDefect = $request->input('defect');
        $selectedLine = $request->input('line');
        $selectedConveyor = $request->input('conveyor');
        $selectedPattern = $request->input('pattern');
        $selectedCategory = strtolower((string) $request->input('category', 'all'));
        if (!in_array($selectedCategory, ['all', 'internal', 'assembly', 'unclassified'], true)) {
            $selectedCategory = 'all';
        }

        // 2. Base query Final Assy dengan filter terpusat
        $baseFinalQuery = Defect::query()->filterFinalAssy($request);

        // 3. Invarian Mutlak Non-Rework:
        // Internal + Assembly (+ data lama tanpa area jika ada) = Total Final Assy Non-Rework (Defect)
        $totalFinalAssyNonRework = (clone $baseFinalQuery)->finalAssyReport()->count();
        $totalZeroDefect = (clone $baseFinalQuery)->finalAssyReport()->where(function ($q) {
            $q->where('jenis_defect', 'Zero Defect')->orWhere('quantity', 0);
        })->count();
        $totalActualDefects = $totalFinalAssyNonRework - $totalZeroDefect;

        $totalInternal = (clone $baseFinalQuery)->finalAssyInternalDefect()->count();
        $totalAssembly = (clone $baseFinalQuery)->finalAssyAssemblyDefect()->count();
        $totalUnclassified = 0;

        // Total PCS (sum quantity) untuk Kartu KPI (Opsi A)
        $pcsFinalAssyNonRework = (int) (clone $baseFinalQuery)->finalAssyReport()->sum('quantity');
        $pcsInternal = (int) (clone $baseFinalQuery)->finalAssyInternalDefect()->sum('quantity');
        $pcsAssembly = (int) (clone $baseFinalQuery)->finalAssyAssemblyDefect()->sum('quantity');
        $pcsUnclassified = 0;
        $totalRework = 0;
        $pcsRework = 0;
        $reworkRecords = collect([]);

        // 4. Query data defect utama sesuai category yang dipilih
        $query = (clone $baseFinalQuery)->with(['finalInspectType', 'areaDitemukanRef']);

        if ($selectedCategory === 'internal') {
            $query->finalAssyInternalDefect();
        } elseif ($selectedCategory === 'assembly') {
            $query->finalAssyAssemblyDefect();
        } elseif ($selectedCategory === 'unclassified') {
            $query->whereRaw('1 = 0');
        } else {
            $query->finalAssyReport();
        }

        // 5. Opsi filter unik
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
            'OTHERS',
            'Zero Defect'
        ];

        $lineOptions = ['TOYOTA', 'NISSAN', 'MAZDA'];

        // 6. Paginate Data (10 baris per halaman)
        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return view('final_assy', [
            'records'                 => $records,
            'defectOptions'           => $defectOptions,
            'lineOptions'             => $lineOptions,
            'dateRange'               => $dateRange,
            'selectedDefect'          => $selectedDefect,
            'selectedLine'            => $selectedLine,
            'selectedConveyor'        => $selectedConveyor,
            'selectedPattern'         => $selectedPattern,
            'selectedCategory'        => $selectedCategory,
            'totalFinalAssyNonRework' => $totalFinalAssyNonRework,
            'totalActualDefects'      => $totalActualDefects,
            'totalZeroDefect'         => $totalZeroDefect,
            'totalInternal'           => $totalInternal,
            'totalAssembly'           => $totalAssembly,
            'totalUnclassified'       => $totalUnclassified,
            'totalRework'             => $totalRework,
            'pcsFinalAssyNonRework'   => $pcsFinalAssyNonRework,
            'pcsInternal'             => $pcsInternal,
            'pcsAssembly'             => $pcsAssembly,
            'pcsUnclassified'         => $pcsUnclassified,
            'pcsRework'               => $pcsRework,
            'reworkRecords'           => $reworkRecords,
            'currentPage'             => $records->currentPage(),
            'totalPages'              => $records->lastPage(),
            'totalItems'              => $records->total(),
            'allFilteredRecords'      => $query->orderBy('waktu', 'desc')->get()
        ]);
    }

    /**
     * Submenu Final Assy: Internal Defect
     */
    public function finalAssyInternal(Request $request)
    {
        $request->merge(['category' => 'internal']);
        return $this->index($request);
    }

    /**
     * Submenu Final Assy: Assembly Defect
     */
    public function finalAssyAssembly(Request $request)
    {
        $request->merge(['category' => 'assembly']);
        return $this->index($request);
    }

    /**
     * Submenu Final Assy: Rework (dialihkan ke menu resmi Riwayat Rework)
     */
    public function finalAssyRework(Request $request)
    {
        return redirect()->route('admin.rework_history.index');
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

        // 1. Ambil parameter filter
        $dateRange = $request->input('date_range');
        $selectedDefect = $request->input('defect');
        $selectedLine = $request->input('line');
        $selectedCarline = $request->input('carline');
        $selectedCategory = strtolower((string) $request->input('category', 'all'));
        if (!in_array($selectedCategory, ['all', 'internal', 'assembly', 'process', 'unknown'], true)) {
            $selectedCategory = 'all';
        }

        // 2. Base query Pre Assy dengan filter bersama
        $basePreQuery = Defect::query()->where('jenis_assy', 'Pre Assy');

        // Terapkan Filter Tanggal
        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $basePreQuery->whereBetween('waktu', [$startDate, $endDate]);
            } else if (count($dates) === 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();
                $basePreQuery->whereBetween('waktu', [$startDate, $endDate]);
            }
        }

        // Terapkan Filter Jenis Defect
        if ($selectedDefect && $selectedDefect !== 'all') {
            $basePreQuery->where('jenis_defect', $selectedDefect);
        }

        // Terapkan Filter Line (Mobil - untuk backward compatibility jika ada data lama)
        if ($selectedLine && $selectedLine !== 'all') {
            $basePreQuery->where('jenis_mobil', $selectedLine);
        }

        // Terapkan Filter Carline (dukung carline_id atau nama carline / conveyor lama)
        if ($selectedCarline && $selectedCarline !== 'all') {
            $basePreQuery->where(function ($q) use ($selectedCarline) {
                if (is_numeric($selectedCarline)) {
                    $q->where('carline_id', $selectedCarline);
                } else {
                    $q->whereHas('carline', function ($sub) use ($selectedCarline) {
                        $sub->where('name', $selectedCarline);
                    })->orWhere('conveyor', $selectedCarline);
                }
            });
        }

        // 3. Invarian Mutlak Pre Assy:
        // Internal Defect (Inspektor) + Assembly Defect (Operator) (+ data lama tanpa penemu jika ada) = Total Defect Pre Assy
        $totalPreAssy = (clone $basePreQuery)->operatorInput()->count();
        $totalZeroDefect = (clone $basePreQuery)->operatorInput()->where(function ($q) {
            $q->where('jenis_defect', 'Zero Defect')->orWhere('quantity', 0);
        })->count();
        $totalDefectPreAssy = $totalPreAssy - $totalZeroDefect;

        $totalInternal = (clone $basePreQuery)->preAssyInspektor()->count();
        $totalAssembly = (clone $basePreQuery)->preAssyOperator()->count();
        $totalProcess = $totalAssembly; // Backward-compatibility
        $totalUnknown = (clone $basePreQuery)->preAssyUnknown()->count();

        // 4. Query data defect utama sesuai category yang dipilih
        $query = (clone $basePreQuery)->operatorInput()->with(['carline', 'inspectProcessType']);

        if ($selectedCategory === 'internal' || $selectedCategory === 'inspektor') {
            $query->preAssyInspektor();
        } elseif ($selectedCategory === 'assembly' || $selectedCategory === 'process' || $selectedCategory === 'operator') {
            $query->preAssyOperator();
        } elseif ($selectedCategory === 'unknown') {
            $query->preAssyUnknown();
        }

        // 5. Opsi filter unik langsung dari DB
        $defectOptions = [
            'CORE',
            'TERMINAL',
            'FRONT CRIMPING',
            'REAR  CRIMPING',
            'INSULATION',
            'SEAL SUMBER',
            'CRIMPING',
            'LAIN-LAIN',
            'Zero Defect'
        ];

        $lineOptions = ['TOYOTA', 'NISSAN', 'MAZDA'];
        $carlineOptions = Carline::preAssy()->orderBy('name')->get();

        // 6. Paginate Data (10 baris per halaman)
        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return view('pre_assy', [
            'records'            => $records,
            'defectOptions'      => $defectOptions,
            'lineOptions'        => $lineOptions,
            'carlineOptions'     => $carlineOptions,
            'dateRange'          => $dateRange,
            'selectedDefect'     => $selectedDefect,
            'selectedLine'       => $selectedLine,
            'selectedCarline'    => $selectedCarline,
            'selectedCategory'   => $selectedCategory,
            'totalPreAssy'       => $totalPreAssy,
            'totalDefectPreAssy' => $totalDefectPreAssy,
            'totalZeroDefect'    => $totalZeroDefect,
            'totalInternal'      => $totalInternal,
            'totalAssembly'      => $totalAssembly,
            'totalProcess'       => $totalProcess,
            'totalUnknown'       => $totalUnknown,
            'currentPage'        => $records->currentPage(),
            'totalPages'         => $records->lastPage(),
            'totalItems'         => $records->total(),
            'allFilteredRecords' => $query->orderBy('waktu', 'desc')->get()
        ]);
    }

    /**
     * Submenu Pre Assy: Internal Defect
     */
    public function preAssyInternalCategory(Request $request)
    {
        $request->merge(['category' => 'internal']);
        return $this->preAssy($request);
    }

    /**
     * Submenu Pre Assy: Assembly Defect
     */
    public function preAssyAssemblyCategory(Request $request)
    {
        $request->merge(['category' => 'assembly']);
        return $this->preAssy($request);
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
     * Endpoint data grafik stacked bar Final Assy (Sumbu Y = SUM(quantity) PCS).
     * Rework strictly excluded. Top 15 defect types + "Lainnya".
     */
    public function finalAssyChartData(Request $request): \Illuminate\Http\JsonResponse
    {
        $baseQuery = Defect::query()->filterFinalAssy($request)
            ->where(function ($q) {
                $q->whereNull('jenis_defect')
                  ->orWhere('jenis_defect', '!=', 'Zero Defect');
            })
            ->where('quantity', '>', 0);

        // Agregasi per jenis_defect untuk kategori non-rework (Internal & Assembly)
        $internalData = (clone $baseQuery)->finalAssyInternalDefect()
            ->selectRaw('jenis_defect, SUM(quantity) as total_pcs, COUNT(*) as total_count')
            ->groupBy('jenis_defect')
            ->get()
            ->keyBy('jenis_defect');

        $assemblyData = (clone $baseQuery)->finalAssyAssemblyDefect()
            ->selectRaw('jenis_defect, SUM(quantity) as total_pcs, COUNT(*) as total_count')
            ->groupBy('jenis_defect')
            ->get()
            ->keyBy('jenis_defect');

        // Kumpulkan semua jenis defect unik
        $allDefects = $internalData->keys()
            ->merge($assemblyData->keys())
            ->unique();

        // Hitung total PCS tiap jenis defect untuk Pareto sorting
        $defectTotals = [];
        foreach ($allDefects as $defectName) {
            $intPcs = (int) ($internalData->get($defectName)?->total_pcs ?? 0);
            $asmPcs = (int) ($assemblyData->get($defectName)?->total_pcs ?? 0);
            $defectTotals[$defectName] = $intPcs + $asmPcs;
        }

        // Urutkan terbesar ke terkecil (Pareto)
        arsort($defectTotals);

        // Batasi 15 teratas + "Lainnya"
        $topLimit = 15;
        $topDefects = array_slice(array_keys($defectTotals), 0, $topLimit);
        $otherDefects = array_slice(array_keys($defectTotals), $topLimit);

        $labels = [];
        $internalPcs = [];
        $internalCounts = [];
        $assemblyPcs = [];
        $assemblyCounts = [];

        foreach ($topDefects as $defectName) {
            $labelName = ($defectName === '' || $defectName === null) ? '-' : (string) $defectName;
            $labels[] = $labelName;

            $iPcs = (int) ($internalData->get($defectName)?->total_pcs ?? 0);
            $iCnt = (int) ($internalData->get($defectName)?->total_count ?? 0);
            $internalPcs[] = $iPcs;
            $internalCounts[] = $iCnt;

            $aPcs = (int) ($assemblyData->get($defectName)?->total_pcs ?? 0);
            $aCnt = (int) ($assemblyData->get($defectName)?->total_count ?? 0);
            $assemblyPcs[] = $aPcs;
            $assemblyCounts[] = $aCnt;
        }

        if (!empty($otherDefects)) {
            $otherIPcs = 0; $otherICnt = 0;
            $otherAPcs = 0; $otherACnt = 0;

            foreach ($otherDefects as $defectName) {
                $otherIPcs += (int) ($internalData->get($defectName)?->total_pcs ?? 0);
                $otherICnt += (int) ($internalData->get($defectName)?->total_count ?? 0);

                $otherAPcs += (int) ($assemblyData->get($defectName)?->total_pcs ?? 0);
                $otherACnt += (int) ($assemblyData->get($defectName)?->total_count ?? 0);
            }

            $labels[] = 'Lainnya';
            $internalPcs[] = $otherIPcs;
            $internalCounts[] = $otherICnt;
            $assemblyPcs[] = $otherAPcs;
            $assemblyCounts[] = $otherACnt;
        }

        $datasets = [
            [
                'label' => 'Internal Defect',
                'backgroundColor' => 'rgba(37, 99, 235, 0.85)', // blue-600
                'borderColor' => '#1d4ed8',
                'borderWidth' => 1,
                'data' => $internalPcs,
                'counts' => $internalCounts,
            ],
            [
                'label' => 'Assembly Defect',
                'backgroundColor' => 'rgba(16, 185, 129, 0.85)', // emerald-500
                'borderColor' => '#059669',
                'borderWidth' => 1,
                'data' => $assemblyPcs,
                'counts' => $assemblyCounts,
            ],
        ];

        return response()->json([
            'labels' => $labels,
            'datasets' => $datasets,
            'summary' => [
                'total_pcs' => array_sum($internalPcs) + array_sum($assemblyPcs),
                'total_count' => array_sum($internalCounts) + array_sum($assemblyCounts),
                'internal_pcs' => array_sum($internalPcs),
                'assembly_pcs' => array_sum($assemblyPcs),
            ],
        ]);
    }

    /**
     * API Chart Data Pre Assy (Stacked PCS per Jenis Defect) untuk submenu All
     */
    public function preAssyChartData(Request $request): \Illuminate\Http\JsonResponse
    {
        $baseQuery = Defect::query()->where('jenis_assy', 'Pre Assy')
            ->where(function ($q) {
                $q->whereNull('jenis_defect')
                  ->orWhere('jenis_defect', '!=', 'Zero Defect');
            })
            ->where('quantity', '>', 0);

        $dateRange        = $request->input('date_range');
        $selectedDefect   = $request->input('defect');
        $selectedLine     = $request->input('line');
        $selectedCarline  = $request->input('carline') ?? $request->input('conveyor');

        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $baseQuery->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
            } else if (count($dates) === 1) {
                $baseQuery->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[0])->endOfDay()]);
            }
        }

        if ($selectedDefect && $selectedDefect !== 'all') {
            $baseQuery->where('jenis_defect', $selectedDefect);
        }

        if ($selectedLine && $selectedLine !== 'all') {
            $baseQuery->where('jenis_mobil', $selectedLine);
        }

        if ($selectedCarline && $selectedCarline !== 'all') {
            $baseQuery->where(function ($q) use ($selectedCarline) {
                if (is_numeric($selectedCarline)) {
                    $q->where('carline_id', $selectedCarline);
                } else {
                    $q->whereHas('carline', function ($sub) use ($selectedCarline) {
                        $sub->where('name', $selectedCarline);
                    })->orWhere('conveyor', $selectedCarline);
                }
            });
        }

        $inspektorData = (clone $baseQuery)->preAssyInspektor()
            ->selectRaw('jenis_defect, SUM(quantity) as total_pcs, COUNT(*) as total_count')
            ->groupBy('jenis_defect')
            ->get()
            ->keyBy('jenis_defect');

        $operatorData = (clone $baseQuery)->preAssyOperator()
            ->selectRaw('jenis_defect, SUM(quantity) as total_pcs, COUNT(*) as total_count')
            ->groupBy('jenis_defect')
            ->get()
            ->keyBy('jenis_defect');

        $allDefects = $inspektorData->keys()
            ->merge($operatorData->keys())
            ->unique();

        $defectTotals = [];
        foreach ($allDefects as $defectName) {
            $iPcs = (int) ($inspektorData->get($defectName)?->total_pcs ?? 0);
            $oPcs = (int) ($operatorData->get($defectName)?->total_pcs ?? 0);
            $defectTotals[$defectName] = $iPcs + $oPcs;
        }

        arsort($defectTotals);

        $topLimit = 15;
        $topDefects = array_slice(array_keys($defectTotals), 0, $topLimit);
        $otherDefects = array_slice(array_keys($defectTotals), $topLimit);

        $labels = [];
        $inspektorPcs = [];
        $inspektorCounts = [];
        $operatorPcs = [];
        $operatorCounts = [];

        foreach ($topDefects as $defectName) {
            $labelName = ($defectName === '' || $defectName === null) ? '-' : (string) $defectName;
            $labels[] = $labelName;

            $inspektorPcs[] = (int) ($inspektorData->get($defectName)?->total_pcs ?? 0);
            $inspektorCounts[] = (int) ($inspektorData->get($defectName)?->total_count ?? 0);

            $operatorPcs[] = (int) ($operatorData->get($defectName)?->total_pcs ?? 0);
            $operatorCounts[] = (int) ($operatorData->get($defectName)?->total_count ?? 0);
        }

        if (!empty($otherDefects)) {
            $otherIPcs = 0; $otherICnt = 0;
            $otherOPcs = 0; $otherOCnt = 0;

            foreach ($otherDefects as $defectName) {
                $otherIPcs += (int) ($inspektorData->get($defectName)?->total_pcs ?? 0);
                $otherICnt += (int) ($inspektorData->get($defectName)?->total_count ?? 0);

                $otherOPcs += (int) ($operatorData->get($defectName)?->total_pcs ?? 0);
                $otherOCnt += (int) ($operatorData->get($defectName)?->total_count ?? 0);
            }

            $labels[] = 'Lainnya';
            $inspektorPcs[] = $otherIPcs;
            $inspektorCounts[] = $otherICnt;
            $operatorPcs[] = $otherOPcs;
            $operatorCounts[] = $otherOCnt;
        }

        $datasets = [
            [
                'label' => 'Ditemukan oleh Inspektor',
                'backgroundColor' => 'rgba(37, 99, 235, 0.85)', // blue-600
                'borderColor' => '#1d4ed8',
                'borderWidth' => 1,
                'data' => $inspektorPcs,
                'counts' => $inspektorCounts,
            ],
            [
                'label' => 'Ditemukan oleh Operator',
                'backgroundColor' => 'rgba(245, 158, 11, 0.85)', // amber-500
                'borderColor' => '#d97706',
                'borderWidth' => 1,
                'data' => $operatorPcs,
                'counts' => $operatorCounts,
            ],
        ];

        return response()->json([
            'labels' => $labels,
            'datasets' => $datasets,
            'summary' => [
                'total_pcs' => array_sum($inspektorPcs) + array_sum($operatorPcs),
                'total_count' => array_sum($inspektorCounts) + array_sum($operatorCounts),
                'inspektor_pcs' => array_sum($inspektorPcs),
                'operator_pcs' => array_sum($operatorPcs),
            ],
        ]);
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

        // 1. Buat Query awal (hanya data defect operator, mengecualikan rework)
        $query = Defect::operatorInput();

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
            'LAIN-LAIN',
            'Zero Defect'
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
            'OTHERS',
            'Zero Defect'
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

    /**
     * Apakah carline Pre Assy memiliki nomor mesin aktif di master data.
     */
    private function isCarlineHasActiveMachines($carlineId): bool
    {
        if (empty($carlineId)) {
            return false;
        }
        return PreAssyMachine::where('carline_id', (int) $carlineId)->where('is_active', true)->exists();
    }

    /**
     * Resolve nilai area_ditemukan dan inspect_process_type_id untuk Pre Assy.
     * Jika Zero Defect: null.
     * Pre Assy baru menyimpan langsung ke area_ditemukan dan inspect_process_type_id diset null.
     */
    private function resolvePreAssyAreaAndProcess(bool $isPreAssy, bool $isZeroDefect, $inspectProcessTypeId, ?string $areaInput = null): array
    {
        if (!$isPreAssy) {
            return [
                'area_ditemukan'          => $isZeroDefect ? null : $areaInput,
                'inspect_process_type_id' => null,
            ];
        }

        if ($isZeroDefect) {
            return [
                'area_ditemukan'          => null,
                'inspect_process_type_id' => null,
            ];
        }

        $areaName = filled($areaInput) ? trim((string) $areaInput) : null;
        $processId = filled($inspectProcessTypeId) ? (int) $inspectProcessTypeId : null;

        if (!$areaName && $processId) {
            $process = InspectProcessType::find($processId);
            $areaName = $process?->name;
        }

        return [
            'area_ditemukan'          => $areaName,
            'inspect_process_type_id' => null,
        ];
    }

    /**
     * Show form to create a new defect report.
     */
    public function createInputDefect(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $type = $request->input('type', 'Final Assy');

        $allAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::active()->orderBy('name')->get() : collect([]);
        $finalAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::finalAssy()->active()->orderBy('name')->get() : collect([]);
        $preAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::preAssy()->active()->orderBy('name')->get() : collect([]);
        $reworkAreas = $finalAreas;

        return view('input_defect', [
            'type'                  => $type,
            'carTypes'              => CarType::with('carlines')->orderBy('name')->get(),
            'preAssyCarlines'       => Carline::preAssy()->with(['machines' => fn($q) => $q->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'defectTypesFinal'      => DefectType::with('subDefectTypes')->where('type', 'Final Assy')->orderBy('name')->get(),
            'defectTypesPre'        => DefectType::with('subDefectTypes')->where('type', 'Pre Assy')->orderBy('name')->get(),
            'inspectProcessTypes'   => InspectProcessType::orderBy('name')->get(),
            'finalAssyInspectTypes' => FinalAssyInspectType::orderBy('name')->get(),
            'areaDitemukanList'     => $allAreas,
            'areaDitemukanFinal'    => $finalAreas->isNotEmpty() ? $finalAreas : $allAreas,
            'areaDitemukanPre'      => $preAreas->isNotEmpty() ? $preAreas : $allAreas,
            'areaDitemukanRework'   => $reworkAreas->isNotEmpty() ? $reworkAreas : $allAreas,
        ]);
    }

    /**
     * Store a newly created defect report in database.
     */
    public function storeInputDefect(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        if ($request->has('final_inspect_type_id') && ($request->input('final_inspect_type_id') === '' || $request->input('final_inspect_type_id') === null)) {
            $request->merge(['final_inspect_type_id' => null]);
        }

        if ($request->has('nameplate_lama')) {
            $request->merge(['nameplate_lama' => filled($request->input('nameplate_lama')) ? trim((string) $request->input('nameplate_lama')) : null]);
        }
        if ($request->has('no_defective_tag')) {
            $request->merge(['no_defective_tag' => filled($request->input('no_defective_tag')) ? trim((string) $request->input('no_defective_tag')) : null]);
        }
        if ($request->has('no_sequence')) {
            $request->merge(['no_sequence' => filled($request->input('no_sequence')) ? trim((string) $request->input('no_sequence')) : null]);
        }

        $type = $request->input('type');
        $isPreAssy = $type === 'Pre Assy';
        $jenisMobil = $request->input('jenis_mobil');
        $conveyor = $request->input('conveyor');
        $isApplicable = $this->isFinalInspectTypeApplicable($type, $jenisMobil, $conveyor);

        $preAssyAreas = Schema::hasTable('area_ditemukans')
            ? AreaDitemukan::preAssy()->active()->pluck('name')->toArray()
            : ['Cutting', 'Manual Crimping', 'Joint Crimping', 'Bonder', 'Heatshrink', 'Reychern', 'Solder', 'La Term. WP', 'A/B', 'Jam Twist', 'WP', 'MN20', 'EJ30', 'MN10', 'TP25', 'EJ35'];

        $finalAssyAreas = Schema::hasTable('area_ditemukans')
            ? AreaDitemukan::finalAssy()->active()->pluck('name')->toArray()
            : ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'];

        $allowedAreas = $isPreAssy ? $preAssyAreas : $finalAssyAreas;

        // Backward compatibility: Jika request lama hanya mengirim inspect_process_type_id
        if ($isPreAssy && empty($request->input('area_ditemukan')) && $request->filled('inspect_process_type_id')) {
            $process = InspectProcessType::find($request->input('inspect_process_type_id'));
            if ($process) {
                $request->merge(['area_ditemukan' => $process->name]);
                if (!in_array($process->name, $allowedAreas)) {
                    $allowedAreas[] = $process->name;
                }
            }
        }

        $isZeroDefect = ($request->input('jenis_defect') === 'Zero Defect');
        $hasActiveMachines = $isPreAssy && $this->isCarlineHasActiveMachines($request->input('carline_id'));

        $areaDitemukanRules = $isZeroDefect
            ? ['nullable', 'string']
            : ['required', 'string', Rule::in($allowedAreas)];

        $validated = $request->validate([
            'type'                     => 'required|string|in:Final Assy,Pre Assy',
            'jenis_mobil'              => 'required_if:type,Final Assy|nullable|string|max:255',
            'conveyor'                 => 'required_if:type,Final Assy|nullable|string|max:255',
            'tanggal'                  => 'required|date',
            'jam'                      => 'required|date_format:H:i',
            'jenis_defect'             => 'nullable|string|max:255',
            'sub_defect'               => 'nullable|string|max:255',
            'jumlah'                   => 'required|integer|min:0',
            'inspect_quantity'         => 'nullable|integer|min:0',
            'end_number'               => ($isPreAssy || $isZeroDefect) ? 'nullable|string|max:255' : 'required|string|max:255',
            'specification'            => 'nullable|string|max:255',
            'actual'                   => 'nullable|string|max:255',
            'area_ditemukan'           => $areaDitemukanRules,
            'job_station'              => 'nullable|string|max:255',
            'keterangan'               => 'nullable|string',
            'no_terminal'              => 'nullable|string|max:255',
            'no_mesin'                 => ($isPreAssy && !$isZeroDefect && $hasActiveMachines) ? 'required|string|max:255' : 'nullable|string|max:255',
            'nameplate_lama'           => 'nullable|string|max:255',
            'no_defective_tag'         => 'nullable|string|max:255',
            'no_sequence'              => 'nullable|string|max:255',
            // Kolom baru master data
            'carline_id'               => 'required_if:type,Pre Assy|nullable|exists:carlines,id',
            'inspect_process_type_id'  => 'nullable|exists:inspect_process_types,id',
            'final_inspect_type_id'    => $this->getFinalInspectTypeValidationRules($isApplicable, $jenisMobil, $conveyor),
            'defect_type_id'           => 'nullable|exists:defect_types,id',
            'sub_defect_type_id'       => 'nullable|exists:sub_defect_types,id',
            'ditemukan_oleh'           => ($isPreAssy && !$isZeroDefect) ? 'required|in:Inspektor,Operator' : 'nullable|in:Inspektor,Operator',
        ], [
            'end_number.required'              => 'Nomor End (#) wajib diisi.',
            'area_ditemukan.required'          => $isPreAssy ? 'Area ditemukan Pre Assy wajib dipilih.' : 'Area ditemukan wajib diisi.',
            'area_ditemukan.in'                => 'Area ditemukan yang dipilih tidak valid.',
            'ditemukan_oleh.required'          => 'Ditemukan oleh wajib dipilih.',
            'ditemukan_oleh.in'                => 'Ditemukan oleh yang dipilih tidak valid.',
            'inspect_process_type_id.required' => 'Area ditemukan Pre Assy wajib dipilih.',
            'no_mesin.required'                => 'Nomor mesin wajib dipilih.',
        ]);

        $userName = session('user_name', 'Operator');
        $shift = session('current_shift', '1A');

        $isPreAssy = $validated['type'] === 'Pre Assy';
        $finalInspectTypeId = (!$isPreAssy && $isApplicable) ? ($validated['final_inspect_type_id'] ?? null) : null;
        $subDefect = $isZeroDefect ? '-' : ($validated['sub_defect'] ?? '-');
        $quantity = $isZeroDefect ? 0 : $validated['jumlah'];

        // Batch 4: end_number hanya untuk Final Assy non-Zero Defect; selain itu paksa null.
        $endNumber = (!$isPreAssy && !$isZeroDefect && filled($validated['end_number'] ?? null))
            ? trim((string) $validated['end_number'])
            : null;

        // Batch 4: specification opsional Final Assy non-Zero Defect; actual dipaksa null jika specification kosong.
        $specification = (!$isPreAssy && !$isZeroDefect && filled($validated['specification'] ?? null))
            ? trim((string) $validated['specification'])
            : null;
        $actual = ($specification !== null && filled($validated['actual'] ?? null))
            ? trim((string) $validated['actual'])
            : null;

        $areaAndProcess = $this->resolvePreAssyAreaAndProcess(
            $isPreAssy,
            $isZeroDefect,
            $validated['inspect_process_type_id'] ?? null,
            $validated['area_ditemukan'] ?? null
        );
        $areaDitemukan = $areaAndProcess['area_ditemukan'];
        $inspectProcessTypeId = $areaAndProcess['inspect_process_type_id'];

        $noMesin = ($isPreAssy && !$isZeroDefect && filled($validated['no_mesin'] ?? null))
            ? trim((string) $validated['no_mesin'])
            : null;

        $noSequence = ($isPreAssy && $isZeroDefect)
            ? null
            : (filled($validated['no_sequence'] ?? null) ? trim((string) $validated['no_sequence']) : null);

        $noDefectiveTag = ($isPreAssy && $isZeroDefect)
            ? null
            : (filled($validated['no_defective_tag'] ?? null) ? trim((string) $validated['no_defective_tag']) : null);

        $nameplateLama = $isPreAssy
            ? null
            : (filled($validated['nameplate_lama'] ?? null) ? trim((string) $validated['nameplate_lama']) : null);

        $defect = Defect::create([
            'waktu'                    => Carbon::parse($validated['tanggal'] . ' ' . $validated['jam']),
            'user_name'                => $userName,
            'shift'                    => $shift,
            'jenis_assy'               => $validated['type'],
            'jenis_mobil'              => $isPreAssy ? null : ($validated['jenis_mobil'] ?? null),
            'conveyor'                 => $isPreAssy ? null : ($validated['conveyor'] ?? null),
            'jenis_defect'             => $validated['jenis_defect'] ?? null,
            'jenis_sub_defect'         => $subDefect,
            'quantity'                 => $quantity,
            'inspect_quantity'         => $validated['inspect_quantity'] ?? null,
            'end_number'               => $endNumber,
            'specification'            => $specification,
            'actual'                   => $actual,
            'area_ditemukan'           => $areaDitemukan,
            'job_station'              => $validated['job_station'] ?? null,
            'keterangan'               => $validated['keterangan'] ?? null,
            'no_terminal'              => $validated['no_terminal'] ?? null,
            'no_mesin'                 => $noMesin,
            'nameplate_lama'           => $nameplateLama,
            'no_defective_tag'         => $noDefectiveTag,
            'no_sequence'              => $noSequence,
            // Kolom baru master data
            'carline_id'               => filled($validated['carline_id'] ?? null) ? $validated['carline_id'] : null,
            'inspect_process_type_id'  => $inspectProcessTypeId,
            'final_inspect_type_id'    => filled($finalInspectTypeId) ? $finalInspectTypeId : null,
            'defect_type_id'           => filled($validated['defect_type_id'] ?? null) ? $validated['defect_type_id'] : null,
            'sub_defect_type_id'       => $isZeroDefect ? null : (filled($validated['sub_defect_type_id'] ?? null) ? $validated['sub_defect_type_id'] : null),
            'ditemukan_oleh'           => ($isPreAssy && !$isZeroDefect) ? ($validated['ditemukan_oleh'] ?? null) : null,
        ]);

        $lineOrCarline = $isPreAssy ? ($defect->carline?->name ?? '-') : ($validated['jenis_mobil'] . ' (' . ($validated['conveyor'] ?? '-') . ')');
        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => $userName,
            'jenis_aksi'   => 'Create Report',
            'aktivitas'    => "Melaporkan defect {$validated['type']} - {$lineOrCarline} - Jumlah {$quantity}",
            'jenis_defect' => $validated['jenis_defect'] ?? null,
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('operator.home')->with('success', 'Laporan defect berhasil disimpan!');
    }

    /**
     * Display Operator Home dashboard with their own submitted defects.
     */
    public function operatorHome(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $myDefects = Defect::with(['finalInspectType', 'carline', 'inspectProcessType'])
            ->where('user_name', session('user_name'))
            ->orderBy('waktu', 'desc')
            ->take(10)
            ->get();

        return view('operator_home', compact('myDefects'));
    }

    /**
     * Show form to edit an existing defect report.
     */
    public function editInputDefect(Request $request, $id)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $defect = Defect::findOrFail($id);

        if ($defect->rework_group_id) {
            $params = ['groupId' => $defect->rework_group_id];
            if ($request->filled('return_url')) {
                $params['return_url'] = $request->input('return_url');
            }
            return redirect()->route('rework.edit', $params);
        }

        if ($defect->user_name !== session('user_name')) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit laporan ini.');
        }

        $allAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::active()->orderBy('name')->get() : collect([]);
        $finalAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::finalAssy()->active()->orderBy('name')->get() : collect([]);
        $preAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::preAssy()->active()->orderBy('name')->get() : collect([]);
        $reworkAreas = $finalAreas;

        return view('input_defect', [
            'type'                  => $defect->jenis_assy,
            'defect'                => $defect,
            'carTypes'              => CarType::with('carlines')->orderBy('name')->get(),
            'preAssyCarlines'       => Carline::preAssy()->with(['machines' => fn($q) => $q->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'defectTypesFinal'      => DefectType::with('subDefectTypes')->where('type', 'Final Assy')->orderBy('name')->get(),
            'defectTypesPre'        => DefectType::with('subDefectTypes')->where('type', 'Pre Assy')->orderBy('name')->get(),
            'inspectProcessTypes'   => InspectProcessType::orderBy('name')->get(),
            'finalAssyInspectTypes' => FinalAssyInspectType::orderBy('name')->get(),
            'areaDitemukanList'     => $allAreas,
            'areaDitemukanFinal'    => $finalAreas->isNotEmpty() ? $finalAreas : $allAreas,
            'areaDitemukanPre'      => $preAreas->isNotEmpty() ? $preAreas : $allAreas,
            'areaDitemukanRework'   => $reworkAreas->isNotEmpty() ? $reworkAreas : $allAreas,
        ]);
    }

    /**
     * Update an existing defect report in database.
     */
    public function updateInputDefect(Request $request, $id)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $defect = Defect::findOrFail($id);

        if ($defect->rework_group_id) {
            return redirect()->back()->with('error', 'Data defect rework tidak dapat diedit melalui form ini. Silakan gunakan form edit rework.');
        }

        if ($defect->user_name !== session('user_name')) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan ini.');
        }

        $defectType = $defect->jenis_assy;
        $type = $request->input('type', $defectType);
        $isPreAssy = $type === 'Pre Assy';

        if ($request->has('final_inspect_type_id') && ($request->input('final_inspect_type_id') === '' || $request->input('final_inspect_type_id') === null)) {
            $request->merge(['final_inspect_type_id' => null]);
        }

        if ($request->has('nameplate_lama')) {
            $request->merge(['nameplate_lama' => filled($request->input('nameplate_lama')) ? trim((string) $request->input('nameplate_lama')) : null]);
        }
        if ($request->has('no_defective_tag')) {
            $request->merge(['no_defective_tag' => filled($request->input('no_defective_tag')) ? trim((string) $request->input('no_defective_tag')) : null]);
        }
        if ($request->has('no_sequence')) {
            $request->merge(['no_sequence' => filled($request->input('no_sequence')) ? trim((string) $request->input('no_sequence')) : null]);
        }

        $jenisMobil = $request->input('jenis_mobil', $defect->jenis_mobil);
        $conveyor = $request->input('conveyor', $defect->conveyor);
        $isApplicable = $this->isFinalInspectTypeApplicable($type, $jenisMobil, $conveyor);

        $preAssyAreas = Schema::hasTable('area_ditemukans')
            ? AreaDitemukan::preAssy()->active()->pluck('name')->toArray()
            : ['Cutting', 'Manual Crimping', 'Joint Crimping', 'Bonder', 'Heatshrink', 'Reychern', 'Solder', 'La Term. WP', 'A/B', 'Jam Twist', 'WP', 'MN20', 'EJ30', 'MN10', 'TP25', 'EJ35'];

        $finalAssyAreas = Schema::hasTable('area_ditemukans')
            ? AreaDitemukan::finalAssy()->active()->pluck('name')->toArray()
            : ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'];

        $allowedAreas = $isPreAssy ? $preAssyAreas : $finalAssyAreas;

        if (!empty($defect->area_ditemukan) && !in_array($defect->area_ditemukan, $allowedAreas)) {
            $allowedAreas[] = $defect->area_ditemukan;
        }
        if ($isPreAssy && !empty($defect->inspectProcessType?->name) && !in_array($defect->inspectProcessType->name, $allowedAreas)) {
            $allowedAreas[] = $defect->inspectProcessType->name;
        }

        // Backward compatibility: Jika request lama hanya mengirim inspect_process_type_id
        if ($isPreAssy && empty($request->input('area_ditemukan')) && $request->filled('inspect_process_type_id')) {
            $process = InspectProcessType::find($request->input('inspect_process_type_id'));
            if ($process) {
                $request->merge(['area_ditemukan' => $process->name]);
                if (!in_array($process->name, $allowedAreas)) {
                    $allowedAreas[] = $process->name;
                }
            }
        }

        $isZeroDefect = ($request->input('jenis_defect') === 'Zero Defect');
        $targetCarlineId = $request->input('carline_id') ?: $defect->carline_id;
        $hasActiveMachines = $isPreAssy && $this->isCarlineHasActiveMachines($targetCarlineId);
        $isOldNoMesinEmpty = empty($defect->no_mesin);

        $noMesinRule = ($isPreAssy && !$isZeroDefect && $hasActiveMachines && !$isOldNoMesinEmpty)
            ? 'required|string|max:255'
            : 'nullable|string|max:255';

        $areaDitemukanRules = $isZeroDefect
            ? ['nullable', 'string']
            : ['required', 'string', Rule::in($allowedAreas)];

        $validated = $request->validate([
            'type'                     => 'nullable|string|in:Final Assy,Pre Assy',
            'jenis_mobil'              => $isPreAssy ? 'nullable|string|max:255' : 'required|string|max:255',
            'conveyor'                 => $isPreAssy ? 'nullable|string|max:255' : 'required|string|max:255',
            'tanggal'                  => 'required|date',
            'jam'                      => 'required|date_format:H:i',
            'jenis_defect'             => 'nullable|string|max:255',
            'sub_defect'               => 'nullable|string|max:255',
            'jumlah'                   => 'required|integer|min:0',
            'inspect_quantity'         => 'nullable|integer|min:0',
            'end_number'               => ($isPreAssy || $isZeroDefect) ? 'nullable|string|max:255' : 'required|string|max:255',
            'specification'            => 'nullable|string|max:255',
            'actual'                   => 'nullable|string|max:255',
            'area_ditemukan'           => $areaDitemukanRules,
            'job_station'              => 'nullable|string|max:255',
            'keterangan'               => 'nullable|string',
            'no_terminal'              => 'nullable|string|max:255',
            'no_mesin'                 => $noMesinRule,
            'nameplate_lama'           => 'nullable|string|max:255',
            'no_defective_tag'         => 'nullable|string|max:255',
            'no_sequence'              => 'nullable|string|max:255',
            'carline_id'               => $isPreAssy ? ($defect->carline_id ? 'required|exists:carlines,id' : 'nullable|exists:carlines,id') : 'nullable|exists:carlines,id',
            'inspect_process_type_id'  => 'nullable|exists:inspect_process_types,id',
            'final_inspect_type_id'    => $this->getFinalInspectTypeValidationRules($isApplicable, $jenisMobil, $conveyor),
            'defect_type_id'           => 'nullable|exists:defect_types,id',
            'sub_defect_type_id'       => 'nullable|exists:sub_defect_types,id',
            'ditemukan_oleh'           => ($isPreAssy && !$isZeroDefect) ? 'required|in:Inspektor,Operator' : 'nullable|in:Inspektor,Operator',
            'pattern'                  => 'nullable|string|max:255',
        ], [
            'end_number.required'              => 'Nomor End (#) wajib diisi.',
            'area_ditemukan.required'          => $isPreAssy ? 'Area ditemukan Pre Assy wajib dipilih.' : 'Area ditemukan wajib diisi.',
            'area_ditemukan.in'                => 'Area ditemukan yang dipilih tidak valid.',
            'ditemukan_oleh.required'          => 'Ditemukan oleh wajib dipilih.',
            'ditemukan_oleh.in'                => 'Ditemukan oleh yang dipilih tidak valid.',
            'inspect_process_type_id.required' => 'Area ditemukan Pre Assy wajib dipilih.',
            'no_mesin.required'                => 'Nomor mesin wajib dipilih.',
        ]);

        $recordType = $validated['type'] ?? $defectType;
        $isPreAssyRecord = $recordType === 'Pre Assy';
        $finalInspectTypeId = $this->resolveFinalInspectTypeIdForUpdate($defect, $validated, $isApplicable);
        $subDefect = $isZeroDefect ? '-' : ($validated['sub_defect'] ?? '-');
        $quantity = $isZeroDefect ? 0 : $validated['jumlah'];

        // Batch 4: end_number hanya untuk Final Assy non-Zero Defect; selain itu paksa null.
        $endNumber = (!$isPreAssyRecord && !$isZeroDefect && filled($validated['end_number'] ?? null))
            ? trim((string) $validated['end_number'])
            : null;

        // Batch 4: specification opsional Final Assy non-Zero Defect; actual dipaksa null jika specification kosong.
        $specification = (!$isPreAssyRecord && !$isZeroDefect && filled($validated['specification'] ?? null))
            ? trim((string) $validated['specification'])
            : null;
        $actual = ($specification !== null && filled($validated['actual'] ?? null))
            ? trim((string) $validated['actual'])
            : null;

        $areaAndProcess = $this->resolvePreAssyAreaAndProcess(
            $isPreAssyRecord,
            $isZeroDefect,
            $validated['inspect_process_type_id'] ?? null,
            $validated['area_ditemukan'] ?? null
        );
        $areaDitemukan = $areaAndProcess['area_ditemukan'];
        $inspectProcessTypeId = $areaAndProcess['inspect_process_type_id'];

        $noMesin = ($isPreAssyRecord && !$isZeroDefect && filled($validated['no_mesin'] ?? null))
            ? trim((string) $validated['no_mesin'])
            : null;

        $noSequence = ($isPreAssyRecord && $isZeroDefect)
            ? null
            : (filled($validated['no_sequence'] ?? null) ? trim((string) $validated['no_sequence']) : null);

        $noDefectiveTag = ($isPreAssyRecord && $isZeroDefect)
            ? null
            : (filled($validated['no_defective_tag'] ?? null) ? trim((string) $validated['no_defective_tag']) : null);

        $nameplateLama = $isPreAssyRecord
            ? null
            : (filled($validated['nameplate_lama'] ?? null) ? trim((string) $validated['nameplate_lama']) : null);

        $defect->update([
            'waktu'                    => Carbon::parse($validated['tanggal'] . ' ' . $validated['jam']),
            'jenis_assy'               => $recordType,
            'jenis_mobil'              => $isPreAssyRecord ? null : ($validated['jenis_mobil'] ?? null),
            'conveyor'                 => $isPreAssyRecord ? (!empty($validated['carline_id']) ? null : $defect->conveyor) : ($validated['conveyor'] ?? null),
            'jenis_defect'             => $validated['jenis_defect'] ?? null,
            'jenis_sub_defect'         => $subDefect,
            'quantity'                 => $quantity,
            'inspect_quantity'         => $validated['inspect_quantity'] ?? null,
            'end_number'               => $endNumber,
            'specification'            => $specification,
            'actual'                   => $actual,
            'area_ditemukan'           => $areaDitemukan,
            'job_station'              => $validated['job_station'] ?? null,
            'keterangan'               => $validated['keterangan'] ?? null,
            'no_terminal'              => $validated['no_terminal'] ?? null,
            'no_mesin'                 => $noMesin,
            'nameplate_lama'           => $nameplateLama,
            'no_defective_tag'         => $noDefectiveTag,
            'no_sequence'              => $noSequence,
            'carline_id'               => filled($validated['carline_id'] ?? null) ? $validated['carline_id'] : null,
            'inspect_process_type_id'  => $inspectProcessTypeId,
            'final_inspect_type_id'    => filled($finalInspectTypeId) ? $finalInspectTypeId : null,
            'defect_type_id'           => filled($validated['defect_type_id'] ?? null) ? $validated['defect_type_id'] : null,
            'sub_defect_type_id'       => $isZeroDefect ? null : (filled($validated['sub_defect_type_id'] ?? null) ? $validated['sub_defect_type_id'] : null),
            'ditemukan_oleh'           => ($isPreAssyRecord && !$isZeroDefect) ? ($validated['ditemukan_oleh'] ?? null) : null,
            'pattern'                  => $validated['pattern'] ?? null,
        ]);

        $lineOrCarline = $isPreAssy ? ($defect->carline?->name ?? $defect->conveyor ?? '-') : (($validated['jenis_mobil'] ?? $defect->jenis_mobil) . ' (' . ($validated['conveyor'] ?? $defect->conveyor ?? '-') . ')');
        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Operator'),
            'jenis_aksi'   => 'Update Report',
            'aktivitas'    => "Mengubah laporan defect {$recordType} - {$lineOrCarline} - Jumlah {$quantity}",
            'jenis_defect' => $validated['jenis_defect'] ?? null,
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('operator.home')->with('success', 'Laporan defect berhasil diperbarui!');
    }

    /**
     * Delete an existing defect report from database.
     */
    public function destroyInputDefect($id)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $defect = Defect::findOrFail($id);

        if ($defect->rework_group_id) {
            return redirect()->back()->with('error', 'Data defect rework tidak dapat diedit melalui form ini. Silakan gunakan form edit rework.');
        }

        if ($defect->user_name !== session('user_name')) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus laporan ini.');
        }

        $type = $defect->jenis_assy;
        $carlineOrLine = $defect->jenis_assy === 'Pre Assy' ? ($defect->carline?->name ?? '-') : ($defect->jenis_mobil . ' (' . ($defect->conveyor ?? '-') . ')');

        $defect->delete();

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Operator'),
            'jenis_aksi'   => 'Delete Report',
            'aktivitas'    => "Menghapus laporan defect {$type} - {$carlineOrLine}",
            'jenis_defect' => $defect->jenis_defect,
            'ip_address'   => request()->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('operator.home')->with('success', 'Laporan defect berhasil dihapus!');
    }

    /**
     * Show form for admin to edit any defect report.
     */
    public function adminEditReport(Request $request, $id)
    {
        $defect = Defect::findOrFail($id);

        if ($defect->rework_group_id) {
            $params = ['groupId' => $defect->rework_group_id];
            if ($request->filled('return_url')) {
                $params['return_url'] = $request->input('return_url');
            }
            return redirect()->route('rework.edit', $params);
        }

        $allAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::active()->orderBy('name')->get() : collect([]);
        $finalAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::finalAssy()->active()->orderBy('name')->get() : collect([]);
        $preAreas = Schema::hasTable('area_ditemukans') ? AreaDitemukan::preAssy()->active()->orderBy('name')->get() : collect([]);
        $reworkAreas = $finalAreas;

        $returnUrl = AdminReturnUrl::sanitize($request->old('return_url', $request->input('return_url')), $request->getBasePath());

        return view('input_defect', [
            'type'                  => $defect->jenis_assy,
            'defect'                => $defect,
            'returnUrl'             => $returnUrl,
            'backRoute'             => $returnUrl ?? route('recent_defects.index'),
            'carTypes'              => CarType::with('carlines')->orderBy('name')->get(),
            'preAssyCarlines'       => Carline::preAssy()->with(['machines' => fn($q) => $q->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'defectTypesFinal'      => DefectType::with('subDefectTypes')->where('type', 'Final Assy')->orderBy('name')->get(),
            'defectTypesPre'        => DefectType::with('subDefectTypes')->where('type', 'Pre Assy')->orderBy('name')->get(),
            'inspectProcessTypes'   => InspectProcessType::orderBy('name')->get(),
            'finalAssyInspectTypes' => FinalAssyInspectType::orderBy('name')->get(),
            'areaDitemukanList'     => $allAreas,
            'areaDitemukanFinal'    => $finalAreas->isNotEmpty() ? $finalAreas : $allAreas,
            'areaDitemukanPre'      => $preAreas->isNotEmpty() ? $preAreas : $allAreas,
            'areaDitemukanRework'   => $reworkAreas->isNotEmpty() ? $reworkAreas : $allAreas,
        ]);
    }

    /**
     * Update defect report by admin.
     */
    public function adminUpdateReport(Request $request, $id)
    {
        $defect = Defect::findOrFail($id);

        if ($defect->rework_group_id) {
            return redirect()->back()->with('error', 'Data defect rework tidak dapat diedit melalui form ini. Silakan gunakan form edit rework.');
        }

        $defectType = $defect->jenis_assy;
        $type = $request->input('type', $defectType);
        $isPreAssy = $type === 'Pre Assy';

        if ($request->has('final_inspect_type_id') && ($request->input('final_inspect_type_id') === '' || $request->input('final_inspect_type_id') === null)) {
            $request->merge(['final_inspect_type_id' => null]);
        }

        if ($request->has('nameplate_lama')) {
            $request->merge(['nameplate_lama' => filled($request->input('nameplate_lama')) ? trim((string) $request->input('nameplate_lama')) : null]);
        }
        if ($request->has('no_defective_tag')) {
            $request->merge(['no_defective_tag' => filled($request->input('no_defective_tag')) ? trim((string) $request->input('no_defective_tag')) : null]);
        }
        if ($request->has('no_sequence')) {
            $request->merge(['no_sequence' => filled($request->input('no_sequence')) ? trim((string) $request->input('no_sequence')) : null]);
        }

        $jenisMobil = $request->input('jenis_mobil', $defect->jenis_mobil);
        $conveyor = $request->input('conveyor', $defect->conveyor);
        $isApplicable = $this->isFinalInspectTypeApplicable($type, $jenisMobil, $conveyor);

        $preAssyAreas = Schema::hasTable('area_ditemukans')
            ? AreaDitemukan::preAssy()->active()->pluck('name')->toArray()
            : ['Cutting', 'Manual Crimping', 'Joint Crimping', 'Bonder', 'Heatshrink', 'Reychern', 'Solder', 'La Term. WP', 'A/B', 'Jam Twist', 'WP', 'MN20', 'EJ30', 'MN10', 'TP25', 'EJ35'];

        $finalAssyAreas = Schema::hasTable('area_ditemukans')
            ? AreaDitemukan::finalAssy()->active()->pluck('name')->toArray()
            : ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'];

        $allowedAreas = $isPreAssy ? $preAssyAreas : $finalAssyAreas;

        if (!empty($defect->area_ditemukan) && !in_array($defect->area_ditemukan, $allowedAreas)) {
            $allowedAreas[] = $defect->area_ditemukan;
        }
        if ($isPreAssy && !empty($defect->inspectProcessType?->name) && !in_array($defect->inspectProcessType->name, $allowedAreas)) {
            $allowedAreas[] = $defect->inspectProcessType->name;
        }

        // Backward compatibility: Jika request lama hanya mengirim inspect_process_type_id
        if ($isPreAssy && empty($request->input('area_ditemukan')) && $request->filled('inspect_process_type_id')) {
            $process = InspectProcessType::find($request->input('inspect_process_type_id'));
            if ($process) {
                $request->merge(['area_ditemukan' => $process->name]);
                if (!in_array($process->name, $allowedAreas)) {
                    $allowedAreas[] = $process->name;
                }
            }
        }

        $isZeroDefect = ($request->input('jenis_defect') === 'Zero Defect');
        $targetCarlineId = $request->input('carline_id') ?: $defect->carline_id;
        $hasActiveMachines = $isPreAssy && $this->isCarlineHasActiveMachines($targetCarlineId);
        $isOldNoMesinEmpty = empty($defect->no_mesin);

        $noMesinRule = ($isPreAssy && !$isZeroDefect && $hasActiveMachines && !$isOldNoMesinEmpty)
            ? 'required|string|max:255'
            : 'nullable|string|max:255';

        $areaDitemukanRules = $isZeroDefect
            ? ['nullable', 'string']
            : ['required', 'string', Rule::in($allowedAreas)];

        $validated = $request->validate([
            'type'                     => 'nullable|string|in:Final Assy,Pre Assy',
            'jenis_mobil'              => $isPreAssy ? 'nullable|string|max:255' : 'required|string|max:255',
            'conveyor'                 => $isPreAssy ? 'nullable|string|max:255' : 'required|string|max:255',
            'tanggal'                  => 'required|date',
            'jam'                      => 'required|date_format:H:i',
            'jenis_defect'             => 'nullable|string|max:255',
            'sub_defect'               => 'nullable|string|max:255',
            'jumlah'                   => 'required|integer|min:0',
            'inspect_quantity'         => 'nullable|integer|min:0',
            'end_number'               => ($isPreAssy || $isZeroDefect) ? 'nullable|string|max:255' : 'required|string|max:255',
            'specification'            => 'nullable|string|max:255',
            'actual'                   => 'nullable|string|max:255',
            'area_ditemukan'           => $areaDitemukanRules,
            'job_station'              => 'nullable|string|max:255',
            'keterangan'               => 'nullable|string',
            'no_terminal'              => 'nullable|string|max:255',
            'no_mesin'                 => $noMesinRule,
            'nameplate_lama'           => 'nullable|string|max:255',
            'no_defective_tag'         => 'nullable|string|max:255',
            'no_sequence'              => 'nullable|string|max:255',
            'carline_id'               => $isPreAssy ? ($defect->carline_id ? 'required|exists:carlines,id' : 'nullable|exists:carlines,id') : 'nullable|exists:carlines,id',
            'inspect_process_type_id'  => 'nullable|exists:inspect_process_types,id',
            'final_inspect_type_id'    => $this->getFinalInspectTypeValidationRules($isApplicable, $jenisMobil, $conveyor),
            'defect_type_id'           => 'nullable|exists:defect_types,id',
            'sub_defect_type_id'       => 'nullable|exists:sub_defect_types,id',
            'ditemukan_oleh'           => ($isPreAssy && !$isZeroDefect) ? 'required|in:Inspektor,Operator' : 'nullable|in:Inspektor,Operator',
            'pattern'                  => 'nullable|string|max:255',
        ], [
            'end_number.required'              => 'Nomor End (#) wajib diisi.',
            'area_ditemukan.required'          => $isPreAssy ? 'Area ditemukan Pre Assy wajib dipilih.' : 'Area ditemukan wajib diisi.',
            'area_ditemukan.in'                => 'Area ditemukan yang dipilih tidak valid.',
            'ditemukan_oleh.required'          => 'Ditemukan oleh wajib dipilih.',
            'ditemukan_oleh.in'                => 'Ditemukan oleh yang dipilih tidak valid.',
            'inspect_process_type_id.required' => 'Area ditemukan Pre Assy wajib dipilih.',
            'no_mesin.required'                => 'Nomor mesin wajib dipilih.',
        ]);

        $recordType = $validated['type'] ?? $defectType;
        $isPreAssyRecord = $recordType === 'Pre Assy';
        $finalInspectTypeId = $this->resolveFinalInspectTypeIdForUpdate($defect, $validated, $isApplicable);
        $subDefect = $isZeroDefect ? '-' : ($validated['sub_defect'] ?? '-');
        $quantity = $isZeroDefect ? 0 : $validated['jumlah'];

        // Batch 4: end_number hanya untuk Final Assy non-Zero Defect; selain itu paksa null.
        $endNumber = (!$isPreAssyRecord && !$isZeroDefect && filled($validated['end_number'] ?? null))
            ? trim((string) $validated['end_number'])
            : null;

        // Batch 4: specification opsional Final Assy non-Zero Defect; actual dipaksa null jika specification kosong.
        $specification = (!$isPreAssyRecord && !$isZeroDefect && filled($validated['specification'] ?? null))
            ? trim((string) $validated['specification'])
            : null;
        $actual = ($specification !== null && filled($validated['actual'] ?? null))
            ? trim((string) $validated['actual'])
            : null;

        $areaAndProcess = $this->resolvePreAssyAreaAndProcess(
            $isPreAssyRecord,
            $isZeroDefect,
            $validated['inspect_process_type_id'] ?? null,
            $validated['area_ditemukan'] ?? null
        );
        $areaDitemukan = $areaAndProcess['area_ditemukan'];
        $inspectProcessTypeId = $areaAndProcess['inspect_process_type_id'];

        $noMesin = ($isPreAssyRecord && !$isZeroDefect && filled($validated['no_mesin'] ?? null))
            ? trim((string) $validated['no_mesin'])
            : null;

        $noSequence = ($isPreAssyRecord && $isZeroDefect)
            ? null
            : (filled($validated['no_sequence'] ?? null) ? trim((string) $validated['no_sequence']) : null);

        $noDefectiveTag = ($isPreAssyRecord && $isZeroDefect)
            ? null
            : (filled($validated['no_defective_tag'] ?? null) ? trim((string) $validated['no_defective_tag']) : null);

        $nameplateLama = $isPreAssyRecord
            ? null
            : (filled($validated['nameplate_lama'] ?? null) ? trim((string) $validated['nameplate_lama']) : null);

        $defect->update([
            'waktu'                    => Carbon::parse($validated['tanggal'] . ' ' . $validated['jam']),
            'jenis_assy'               => $recordType,
            'jenis_mobil'              => $isPreAssyRecord ? null : ($validated['jenis_mobil'] ?? null),
            'conveyor'                 => $isPreAssyRecord ? (!empty($validated['carline_id']) ? null : $defect->conveyor) : ($validated['conveyor'] ?? null),
            'jenis_defect'             => $validated['jenis_defect'] ?? null,
            'jenis_sub_defect'         => $subDefect,
            'quantity'                 => $quantity,
            'inspect_quantity'         => $validated['inspect_quantity'] ?? null,
            'end_number'               => $endNumber,
            'specification'            => $specification,
            'actual'                   => $actual,
            'area_ditemukan'           => $areaDitemukan,
            'job_station'              => $validated['job_station'] ?? null,
            'keterangan'               => $validated['keterangan'] ?? null,
            'no_terminal'              => $validated['no_terminal'] ?? null,
            'no_mesin'                 => $noMesin,
            'nameplate_lama'           => $nameplateLama,
            'no_defective_tag'         => $noDefectiveTag,
            'no_sequence'              => $noSequence,
            'carline_id'               => filled($validated['carline_id'] ?? null) ? $validated['carline_id'] : null,
            'inspect_process_type_id'  => $inspectProcessTypeId,
            'final_inspect_type_id'    => filled($finalInspectTypeId) ? $finalInspectTypeId : null,
            'defect_type_id'           => filled($validated['defect_type_id'] ?? null) ? $validated['defect_type_id'] : null,
            'sub_defect_type_id'       => $isZeroDefect ? null : (filled($validated['sub_defect_type_id'] ?? null) ? $validated['sub_defect_type_id'] : null),
            'ditemukan_oleh'           => ($isPreAssyRecord && !$isZeroDefect) ? ($validated['ditemukan_oleh'] ?? null) : null,
            'pattern'                  => $validated['pattern'] ?? null,
        ]);

        $lineOrCarline = $isPreAssy ? ($defect->carline?->name ?? $defect->conveyor ?? '-') : (($validated['jenis_mobil'] ?? $defect->jenis_mobil) . ' (' . ($validated['conveyor'] ?? $defect->conveyor ?? '-') . ')');
        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin'),
            'jenis_aksi'   => 'Update Report',
            'aktivitas'    => "Mengubah laporan defect {$recordType} - {$lineOrCarline} - Jumlah {$quantity}",
            'jenis_defect' => $validated['jenis_defect'] ?? null,
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        $returnUrl = AdminReturnUrl::toRedirectTarget(
            AdminReturnUrl::sanitize($request->input('return_url'), $request->getBasePath()),
            $request->getBasePath()
        );

        return redirect()->to($returnUrl ?? route('recent_defects.index'))->with('success', 'Laporan defect berhasil diperbarui oleh Admin!');
    }

    /**
     * Delete defect report by admin.
     */
    public function adminDestroyReport(Request $request, $id)
    {
        $defect = Defect::findOrFail($id);

        if ($defect->rework_group_id) {
            $msg = 'Data defect rework tidak dapat dihapus dari menu ini. Silakan gunakan menu Riwayat Rework.';
            if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => $msg,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $type = $defect->jenis_assy;
        $carlineOrLine = $defect->jenis_assy === 'Pre Assy' ? ($defect->carline?->name ?? '-') : ($defect->jenis_mobil . ' (' . ($defect->conveyor ?? '-') . ')');
        $defectType = $defect->jenis_defect;

        $defect->delete();

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin'),
            'jenis_aksi'   => 'Delete Report',
            'aktivitas'    => "Menghapus laporan defect {$type} - {$carlineOrLine}",
            'jenis_defect' => $defectType,
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->back()->with('success', 'Laporan defect berhasil dihapus oleh Admin!');
    }

    /**
     * API Live Polling: Dashboard Recent Defects (4 items)
     */
    public function dashboardRecentDefects(Request $request)
    {
        $defects = Defect::operatorInput()->orderBy('waktu', 'desc')->take(4)->get();
        return response()->json([
            'success' => true,
            'data'    => $defects
        ]);
    }

    /**
     * API Live Polling: Final Assy Defects with active filters
     */
    public function finalAssyLive(Request $request)
    {
        $selectedCategory = strtolower((string) $request->input('category', 'all'));
        if (!in_array($selectedCategory, ['all', 'internal', 'assembly'], true)) {
            $selectedCategory = 'all';
        }
        $query = Defect::with(['finalInspectType', 'areaDitemukanRef']);

        if ($selectedCategory === 'internal') {
            $query->finalAssyInternalDefect();
        } elseif ($selectedCategory === 'assembly') {
            $query->finalAssyAssemblyDefect();
        } elseif ($selectedCategory === 'unclassified') {
            $query->whereRaw('1 = 0');
        } else {
            $query->finalAssyReport();
        }

        $dateRange        = $request->input('date_range');
        $selectedDefect   = $request->input('defect');
        $selectedLine     = $request->input('line');
        $selectedConveyor = $request->input('conveyor');
        $selectedPattern  = $request->input('pattern');

        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $query->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
            } else if (count($dates) === 1) {
                $query->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[0])->endOfDay()]);
            }
        }

        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }
        if ($selectedLine && $selectedLine !== 'all') {
            $query->where('jenis_mobil', $selectedLine);
        }
        if ($selectedConveyor && $selectedConveyor !== 'all') {
            $query->where('conveyor', $selectedConveyor);
        }
        if ($selectedPattern && $selectedPattern !== 'all') {
            $query->where('pattern', $selectedPattern);
        }

        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        $items = collect($records->items())->map(function ($item) {
            $array = $item->toArray();
            // BATCH 2: Evaluasi klasifikasi tipe baru
            $tipeArea = $item->areaDitemukanRef?->tipe ?? AreaDitemukan::resolveTipeByName($item->area_ditemukan);
            if ($item->jenis_defect === 'Zero Defect' || (int)$item->quantity === 0) {
                $array['category_label'] = 'Zero Defect';
                $array['category_badge_class'] = 'border-gray-200 bg-gray-50 text-gray-700';
                $array['area_ditemukan'] = '-';
            } elseif ($tipeArea === AreaDitemukan::TIPE_INTERNAL_DEFECT) {
                $array['category_label'] = 'Internal Defect';
                $array['category_badge_class'] = 'border-blue-200 bg-blue-50 text-blue-700';
            } elseif ($tipeArea === AreaDitemukan::TIPE_ASSEMBLY_DEFECT) {
                $array['category_label'] = 'Assembly Defect';
                $array['category_badge_class'] = 'border-emerald-200 bg-emerald-50 text-emerald-700';
            } else {
                $array['category_label'] = '-';
                $array['category_badge_class'] = 'border-gray-200 bg-gray-50 text-gray-600';
            }
            return $array;
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'total'   => $records->total()
        ]);
    }

    /**
     * API Live Polling: Pre Assy Defects with active filters
     */
    public function preAssyLive(Request $request)
    {
        $selectedCategory = strtolower((string) $request->input('category', 'all'));
        if (!in_array($selectedCategory, ['all', 'internal', 'assembly'], true)) {
            $selectedCategory = 'all';
        }
        $query = Defect::with(['carline', 'inspectProcessType'])->where('jenis_assy', 'Pre Assy')->operatorInput();

        if ($selectedCategory === 'internal' || $selectedCategory === 'inspektor') {
            $query->preAssyInspektor();
        } elseif ($selectedCategory === 'assembly' || $selectedCategory === 'process' || $selectedCategory === 'operator') {
            $query->preAssyOperator();
        } elseif ($selectedCategory === 'unknown') {
            $query->preAssyUnknown();
        }

        $dateRange        = $request->input('date_range');
        $selectedDefect   = $request->input('defect');
        $selectedLine     = $request->input('line');
        $selectedCarline  = $request->input('carline') ?? $request->input('conveyor');

        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $query->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
            } else if (count($dates) === 1) {
                $query->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[0])->endOfDay()]);
            }
        }

        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }
        if ($selectedLine && $selectedLine !== 'all') {
            $query->where('jenis_mobil', $selectedLine);
        }
        if ($selectedCarline && $selectedCarline !== 'all') {
            $query->where(function ($q) use ($selectedCarline) {
                if (is_numeric($selectedCarline)) {
                    $q->where('carline_id', $selectedCarline);
                } else {
                    $q->whereHas('carline', function ($sub) use ($selectedCarline) {
                        $sub->where('name', $selectedCarline);
                    })->orWhere('conveyor', $selectedCarline);
                }
            });
        }

        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        $items = collect($records->items())->map(function ($item) {
            $array = $item->toArray();
            $array['carline_name'] = $item->carline?->name ?? $item->conveyor ?? '-';
            $array['inspect_process_type_name'] = $item->area_ditemukan ?: ($item->inspectProcessType?->name ?? '-');
            if ($item->jenis_defect === 'Zero Defect' || (int)$item->quantity === 0) {
                $array['ditemukan_label'] = 'Zero Defect';
                $array['ditemukan_badge_class'] = 'border-gray-200 bg-gray-50 text-gray-700';
            } elseif ($item->ditemukan_oleh === 'Inspektor') {
                $array['ditemukan_label'] = 'Inspektor';
                $array['ditemukan_badge_class'] = 'border-blue-200 bg-blue-50 text-blue-700';
            } elseif ($item->ditemukan_oleh === 'Operator') {
                $array['ditemukan_label'] = 'Operator';
                $array['ditemukan_badge_class'] = 'border-amber-200 bg-amber-50 text-amber-700';
            } else {
                $array['ditemukan_label'] = '-';
                $array['ditemukan_badge_class'] = 'border-gray-200 bg-gray-50 text-gray-600';
            }
            return $array;
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'total'   => $records->total()
        ]);
    }

    /**
     * API Live Polling: Recent Defects with active filters
     */
    public function recentDefectsLive(Request $request)
    {
        $query = Defect::operatorInput();

        $dateRange        = $request->input('date_range');
        $selectedDefect   = $request->input('defect');
        $selectedLine     = $request->input('line');
        $selectedConveyor = $request->input('conveyor');
        $selectedAssy     = $request->input('jenis_assy');

        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $query->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
            } else if (count($dates) === 1) {
                $query->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[0])->endOfDay()]);
            }
        }

        if ($selectedAssy && $selectedAssy !== 'all') {
            $query->where('jenis_assy', $selectedAssy);
        }
        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }
        if ($selectedLine && $selectedLine !== 'all') {
            $query->where('jenis_mobil', $selectedLine);
        }
        if ($selectedConveyor && $selectedConveyor !== 'all') {
            $query->where('conveyor', $selectedConveyor);
        }

        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => $records->items(),
            'total'   => $records->total()
        ]);
    }

    /**
     * API Live Polling: System Log with active filters (ActivityLog)
     */
    public function logSystemLive(Request $request)
    {
        $query = ActivityLog::query();

        $dateRange      = $request->input('date_range');
        $selectedAction = $request->input('action');
        $selectedDefect = $request->input('defect');

        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $query->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
            } else if (count($dates) === 1) {
                $query->whereBetween('waktu', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[0])->endOfDay()]);
            }
        }

        if ($selectedAction && $selectedAction !== 'all') {
            $query->where('jenis_aksi', $selectedAction);
        }
        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }

        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => $records->items(),
            'total'   => $records->total()
        ]);
    }

    /**
     * Opsi master final_assy_inspect_types yang cocok dengan conveyor MAZDA.
     * Aturan sama persis dengan frontend:
     * - type = Final Assy dan jenis_mobil = MAZDA
     * - conveyor mengandung AB6 atau AB9 (/AB\s*[69]/i)
     * - Filter master: AB code sama, LHD/RHD sama, EXTEND/INPANEL sama (hanya jika disebut di conveyor)
     */
    private function finalInspectTypeOptionsFor(?string $jenisMobil, ?string $conveyor): Collection
    {
        if ($jenisMobil !== 'MAZDA' || empty($conveyor)) {
            return collect();
        }

        if (!preg_match('/AB\s*([69])/i', $conveyor, $abMatch)) {
            return collect();
        }
        $abDigit = $abMatch[1];
        $abPattern = '/AB\s*' . $abDigit . '/i';

        $dir = null;
        if (preg_match('/\b(LHD|RHD)\b/i', $conveyor, $dirMatch)) {
            $dir = strtoupper($dirMatch[1]);
        }

        $hasExtend = (bool) preg_match('/EXTEND/i', $conveyor);
        $hasInpanel = (bool) preg_match('/INPANEL/i', $conveyor);

        return FinalAssyInspectType::all()->filter(function ($item) use ($abPattern, $dir, $hasExtend, $hasInpanel) {
            if (!$item || empty($item->name)) {
                return false;
            }
            $name = $item->name;

            // 1. Kode AB yang sama (AB6 atau AB9)
            if (!preg_match($abPattern, $name)) {
                return false;
            }

            // 2. Arah LHD/RHD yang sama
            if ($dir) {
                if (!preg_match('/\b(LHD|RHD)\b/i', $name, $itemDirMatch)) {
                    return false;
                }
                if (strtoupper($itemDirMatch[1]) !== $dir) {
                    return false;
                }
            }

            // 3. Kata EXTEND/INPANEL yang sama, hanya jika nama konveyor menyebutnya
            if ($hasExtend && !preg_match('/EXTEND/i', $name)) {
                return false;
            }
            if ($hasInpanel && !preg_match('/INPANEL/i', $name)) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * Apakah Quantity Inspect Type wajib & berlaku untuk kombinasi type, jenis_mobil, dan conveyor.
     */
    private function isFinalInspectTypeApplicable(?string $type, ?string $jenisMobil, ?string $conveyor): bool
    {
        if ($type !== 'Final Assy' || $jenisMobil !== 'MAZDA' || empty($conveyor)) {
            return false;
        }

        return $this->finalInspectTypeOptionsFor($jenisMobil, $conveyor)->isNotEmpty();
    }

    /**
     * Aturan validasi untuk final_inspect_type_id.
     */
    private function getFinalInspectTypeValidationRules(bool $isApplicable, ?string $jenisMobil, ?string $conveyor): array
    {
        $rules = [
            Rule::requiredIf(fn() => $isApplicable),
            'nullable',
            'exists:final_assy_inspect_types,id',
        ];

        if ($isApplicable) {
            $validIds = $this->finalInspectTypeOptionsFor($jenisMobil, $conveyor)->pluck('id')->all();
            $rules[] = Rule::in($validIds);
        }

        return $rules;
    }

    /**
     * Hitung nilai final_inspect_type_id yang disimpan pada saat update.
     * Jika tidak applicable dan conveyor tidak berubah dari data tersimpan, pertahankan nilai lama.
     */
    private function resolveFinalInspectTypeIdForUpdate(Defect $defect, array $validated, bool $isApplicable): ?int
    {
        $type = $validated['type'] ?? $defect->jenis_assy;
        if ($type === 'Pre Assy') {
            return null;
        }

        if ($isApplicable) {
            return isset($validated['final_inspect_type_id']) ? (int) $validated['final_inspect_type_id'] : null;
        }

        $conveyorChanged = ($defect->jenis_mobil !== ($validated['jenis_mobil'] ?? null)) ||
                           ($defect->conveyor !== ($validated['conveyor'] ?? null));

        return $conveyorChanged ? null : $defect->final_inspect_type_id;
    }
}




