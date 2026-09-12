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
use App\Models\CarType;
use App\Models\DefectType;
use App\Models\InspectProcessType;

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

    /**
     * Show form to create a new defect report.
     */
    public function createInputDefect(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $type = $request->input('type', 'Final Assy');

        return view('input_defect', [
            'type'               => $type,
            'carTypes'           => CarType::with('carlines')->orderBy('name')->get(),
            'defectTypesFinal'   => DefectType::with('subDefectTypes')->where('type', 'Final Assy')->orderBy('name')->get(),
            'defectTypesPre'     => DefectType::with('subDefectTypes')->where('type', 'Pre Assy')->orderBy('name')->get(),
            'inspectProcessTypes' => InspectProcessType::orderBy('name')->get(),
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

        $validated = $request->validate([
            'type'                     => 'required|string|in:Final Assy,Pre Assy',
            'jenis_mobil'              => 'required|string|max:255',
            'conveyor'                 => 'nullable|string|max:255',
            'line'                     => 'nullable|string|max:255',
            'waktu_input'              => 'required|date',
            'jenis_defect'             => 'nullable|string|max:255',
            'sub_defect'               => 'nullable|string|max:255',
            'jumlah'                   => 'required|integer|min:1',
            'inspect_quantity'         => 'nullable|integer|min:0',
            'end_number'               => 'nullable|string|max:255',
            'specification'            => 'nullable|string|max:255',
            'actual'                   => 'nullable|string|max:255',
            'area_ditemukan'           => 'nullable|string|max:255',
            'job_station'              => 'nullable|string|max:255',
            'keterangan'               => 'nullable|string',
            'no_terminal'              => 'nullable|string|max:255',
            'no_mesin'                 => 'nullable|string|max:255',
            // Kolom baru master data
            'carline_id'               => 'nullable|exists:carlines,id',
            'inspect_process_type_id'  => 'nullable|exists:inspect_process_types,id',
            'defect_type_id'           => 'nullable|exists:defect_types,id',
            'sub_defect_type_id'       => 'nullable|exists:sub_defect_types,id',
            'ditemukan_oleh'           => 'nullable|in:Inspektor,Operator',
            'pattern'                  => 'nullable|string|max:255',
        ]);

        $userName = session('user_name', 'Operator');
        $shift = session('current_shift', '1A');

        $defect = Defect::create([
            'waktu'                    => Carbon::parse($validated['waktu_input']),
            'user_name'                => $userName,
            'shift'                    => $shift,
            'jenis_assy'               => $validated['type'],
            'line_conveyor'            => $validated['line'] ?? null,
            'jenis_mobil'              => $validated['jenis_mobil'],
            'conveyor'                 => $validated['conveyor'] ?? null,
            'jenis_defect'             => $validated['jenis_defect'] ?? null,
            'jenis_sub_defect'         => $validated['sub_defect'] ?? null,
            'quantity'                 => $validated['jumlah'],
            'inspect_quantity'         => $validated['inspect_quantity'] ?? null,
            'end_number'               => $validated['end_number'] ?? null,
            'specification'            => $validated['specification'] ?? null,
            'actual'                   => $validated['actual'] ?? null,
            'area_ditemukan'           => $validated['area_ditemukan'] ?? null,
            'job_station'              => $validated['job_station'] ?? null,
            'keterangan'               => $validated['keterangan'] ?? null,
            'no_terminal'              => $validated['no_terminal'] ?? null,
            'no_mesin'                 => $validated['no_mesin'] ?? null,
            // Kolom baru master data
            'carline_id'               => $validated['carline_id'] ?? null,
            'inspect_process_type_id'  => $validated['inspect_process_type_id'] ?? null,
            'defect_type_id'           => $validated['defect_type_id'] ?? null,
            'sub_defect_type_id'       => $validated['sub_defect_type_id'] ?? null,
            'ditemukan_oleh'           => $validated['ditemukan_oleh'] ?? null,
            'pattern'                  => $validated['pattern'] ?? null,
        ]);

        $conveyor = $validated['conveyor'] ?? ($defect->carline->name ?? '-');
        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => $userName,
            'jenis_aksi'   => 'Create Report',
            'aktivitas'    => "Melaporkan defect {$validated['type']} - {$validated['jenis_mobil']} ({$conveyor}) - Jumlah {$validated['jumlah']}",
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

        $myDefects = Defect::where('user_name', session('user_name'))
            ->orderBy('waktu', 'desc')
            ->take(10)
            ->get();

        return view('operator_home', compact('myDefects'));
    }

    /**
     * Show form to edit an existing defect report.
     */
    public function editInputDefect($id)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $defect = Defect::findOrFail($id);

        if ($defect->user_name !== session('user_name')) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit laporan ini.');
        }

        return view('input_defect', [
            'type'               => $defect->jenis_assy,
            'defect'             => $defect,
            'carTypes'           => CarType::with('carlines')->orderBy('name')->get(),
            'defectTypesFinal'   => DefectType::with('subDefectTypes')->where('type', 'Final Assy')->orderBy('name')->get(),
            'defectTypesPre'     => DefectType::with('subDefectTypes')->where('type', 'Pre Assy')->orderBy('name')->get(),
            'inspectProcessTypes' => InspectProcessType::orderBy('name')->get(),
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

        if ($defect->user_name !== session('user_name')) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan ini.');
        }

        $validated = $request->validate([
            'type'                     => 'required|string|in:Final Assy,Pre Assy',
            'jenis_mobil'              => 'required|string|max:255',
            'conveyor'                 => 'nullable|string|max:255',
            'line'                     => 'nullable|string|max:255',
            'waktu_input'              => 'required|date',
            'jenis_defect'             => 'nullable|string|max:255',
            'sub_defect'               => 'nullable|string|max:255',
            'jumlah'                   => 'required|integer|min:1',
            'inspect_quantity'         => 'nullable|integer|min:0',
            'end_number'               => 'nullable|string|max:255',
            'specification'            => 'nullable|string|max:255',
            'actual'                   => 'nullable|string|max:255',
            'area_ditemukan'           => 'nullable|string|max:255',
            'job_station'              => 'nullable|string|max:255',
            'keterangan'               => 'nullable|string',
            'no_terminal'              => 'nullable|string|max:255',
            'no_mesin'                 => 'nullable|string|max:255',
            'carline_id'               => 'nullable|exists:carlines,id',
            'inspect_process_type_id'  => 'nullable|exists:inspect_process_types,id',
            'defect_type_id'           => 'nullable|exists:defect_types,id',
            'sub_defect_type_id'       => 'nullable|exists:sub_defect_types,id',
            'ditemukan_oleh'           => 'nullable|in:Inspektor,Operator',
            'pattern'                  => 'nullable|string|max:255',
        ]);

        $defect->update([
            'waktu'                    => Carbon::parse($validated['waktu_input']),
            'jenis_assy'               => $validated['type'],
            'line_conveyor'            => $validated['line'] ?? null,
            'jenis_mobil'              => $validated['jenis_mobil'],
            'conveyor'                 => $validated['conveyor'] ?? null,
            'jenis_defect'             => $validated['jenis_defect'] ?? null,
            'jenis_sub_defect'         => $validated['sub_defect'] ?? null,
            'quantity'                 => $validated['jumlah'],
            'inspect_quantity'         => $validated['inspect_quantity'] ?? null,
            'end_number'               => $validated['end_number'] ?? null,
            'specification'            => $validated['specification'] ?? null,
            'actual'                   => $validated['actual'] ?? null,
            'area_ditemukan'           => $validated['area_ditemukan'] ?? null,
            'job_station'              => $validated['job_station'] ?? null,
            'keterangan'               => $validated['keterangan'] ?? null,
            'no_terminal'              => $validated['no_terminal'] ?? null,
            'no_mesin'                 => $validated['no_mesin'] ?? null,
            'carline_id'               => $validated['carline_id'] ?? null,
            'inspect_process_type_id'  => $validated['inspect_process_type_id'] ?? null,
            'defect_type_id'           => $validated['defect_type_id'] ?? null,
            'sub_defect_type_id'       => $validated['sub_defect_type_id'] ?? null,
            'ditemukan_oleh'           => $validated['ditemukan_oleh'] ?? null,
            'pattern'                  => $validated['pattern'] ?? null,
        ]);

        $conveyor = $validated['conveyor'] ?? ($defect->carline->name ?? '-');
        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Operator'),
            'jenis_aksi'   => 'Update Report',
            'aktivitas'    => "Mengubah laporan defect {$validated['type']} - {$validated['jenis_mobil']} ({$conveyor}) - Jumlah {$validated['jumlah']}",
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

        if ($defect->user_name !== session('user_name')) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus laporan ini.');
        }

        $type = $defect->jenis_assy;
        $line = $defect->line_conveyor;

        $defect->delete();

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Operator'),
            'jenis_aksi'   => 'Delete Report',
            'aktivitas'    => "Menghapus laporan defect {$type} - {$line}",
            'jenis_defect' => $defect->jenis_defect,
            'ip_address'   => request()->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('operator.home')->with('success', 'Laporan defect berhasil dihapus!');
    }

    /**
     * Show form for admin to edit any defect report.
     */
    public function adminEditReport($id)
    {
        $defect = Defect::findOrFail($id);

        return view('input_defect', [
            'type'               => $defect->jenis_assy,
            'defect'             => $defect,
            'backRoute'          => route('recent_defects.index'),
            'carTypes'           => CarType::with('carlines')->orderBy('name')->get(),
            'defectTypesFinal'   => DefectType::with('subDefectTypes')->where('type', 'Final Assy')->orderBy('name')->get(),
            'defectTypesPre'     => DefectType::with('subDefectTypes')->where('type', 'Pre Assy')->orderBy('name')->get(),
            'inspectProcessTypes' => InspectProcessType::orderBy('name')->get(),
        ]);
    }

    /**
     * Update defect report by admin.
     */
    public function adminUpdateReport(Request $request, $id)
    {
        $defect = Defect::findOrFail($id);

        $validated = $request->validate([
            'type'                     => 'required|string|in:Final Assy,Pre Assy',
            'jenis_mobil'              => 'required|string|max:255',
            'conveyor'                 => 'nullable|string|max:255',
            'line'                     => 'nullable|string|max:255',
            'waktu_input'              => 'required|date',
            'jenis_defect'             => 'nullable|string|max:255',
            'sub_defect'               => 'nullable|string|max:255',
            'jumlah'                   => 'required|integer|min:1',
            'inspect_quantity'         => 'nullable|integer|min:0',
            'end_number'               => 'nullable|string|max:255',
            'specification'            => 'nullable|string|max:255',
            'actual'                   => 'nullable|string|max:255',
            'area_ditemukan'           => 'nullable|string|max:255',
            'job_station'              => 'nullable|string|max:255',
            'keterangan'               => 'nullable|string',
            'no_terminal'              => 'nullable|string|max:255',
            'no_mesin'                 => 'nullable|string|max:255',
            'carline_id'               => 'nullable|exists:carlines,id',
            'inspect_process_type_id'  => 'nullable|exists:inspect_process_types,id',
            'defect_type_id'           => 'nullable|exists:defect_types,id',
            'sub_defect_type_id'       => 'nullable|exists:sub_defect_types,id',
            'ditemukan_oleh'           => 'nullable|in:Inspektor,Operator',
            'pattern'                  => 'nullable|string|max:255',
        ]);

        $defect->update([
            'waktu'                    => Carbon::parse($validated['waktu_input']),
            'jenis_assy'               => $validated['type'],
            'line_conveyor'            => $validated['line'] ?? null,
            'jenis_mobil'              => $validated['jenis_mobil'],
            'conveyor'                 => $validated['conveyor'] ?? null,
            'jenis_defect'             => $validated['jenis_defect'] ?? null,
            'jenis_sub_defect'         => $validated['sub_defect'] ?? null,
            'quantity'                 => $validated['jumlah'],
            'inspect_quantity'         => $validated['inspect_quantity'] ?? null,
            'end_number'               => $validated['end_number'] ?? null,
            'specification'            => $validated['specification'] ?? null,
            'actual'                   => $validated['actual'] ?? null,
            'area_ditemukan'           => $validated['area_ditemukan'] ?? null,
            'job_station'              => $validated['job_station'] ?? null,
            'keterangan'               => $validated['keterangan'] ?? null,
            'no_terminal'              => $validated['no_terminal'] ?? null,
            'no_mesin'                 => $validated['no_mesin'] ?? null,
            'carline_id'               => $validated['carline_id'] ?? null,
            'inspect_process_type_id'  => $validated['inspect_process_type_id'] ?? null,
            'defect_type_id'           => $validated['defect_type_id'] ?? null,
            'sub_defect_type_id'       => $validated['sub_defect_type_id'] ?? null,
            'ditemukan_oleh'           => $validated['ditemukan_oleh'] ?? null,
            'pattern'                  => $validated['pattern'] ?? null,
        ]);

        $conveyor = $validated['conveyor'] ?? ($defect->carline->name ?? '-');
        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name'),
            'jenis_aksi'   => 'Update Report (Admin)',
            'aktivitas'    => "Mengubah laporan defect {$validated['type']} - {$validated['jenis_mobil']} ({$conveyor}) - Jumlah {$validated['jumlah']}",
            'jenis_defect' => $validated['jenis_defect'] ?? null,
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('recent_defects.index')->with('success', 'Laporan defect berhasil diperbarui oleh Admin!');
    }

    /**
     * Delete defect report by admin.
     */
    public function adminDestroyReport(Request $request, $id)
    {
        $defect = Defect::findOrFail($id);

        $type = $defect->jenis_assy;
        $line = $defect->line_conveyor;
        $defectType = $defect->jenis_defect;

        $defect->delete();

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin'),
            'jenis_aksi'   => 'Delete Report (Admin)',
            'aktivitas'    => "Menghapus laporan defect {$type} - {$line}",
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
        $defects = Defect::orderBy('waktu', 'desc')->take(4)->get();
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
        $query = Defect::where('jenis_assy', 'Final Assy');

        $dateRange        = $request->input('date_range');
        $selectedDefect   = $request->input('defect');
        $selectedLine     = $request->input('line');
        $selectedConveyor = $request->input('conveyor');

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

        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => $records->items(),
            'total'   => $records->total()
        ]);
    }

    /**
     * API Live Polling: Pre Assy Defects with active filters
     */
    public function preAssyLive(Request $request)
    {
        $query = Defect::where('jenis_assy', 'Pre Assy');

        $dateRange        = $request->input('date_range');
        $selectedDefect   = $request->input('defect');
        $selectedLine     = $request->input('line');
        $selectedConveyor = $request->input('conveyor');

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

        $records = $query->orderBy('waktu', 'desc')->paginate(10)->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => $records->items(),
            'total'   => $records->total()
        ]);
    }

    /**
     * API Live Polling: Recent Defects with active filters
     */
    public function recentDefectsLive(Request $request)
    {
        $query = Defect::query();

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
}



