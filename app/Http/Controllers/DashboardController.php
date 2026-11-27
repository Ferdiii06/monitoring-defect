<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Defect;
use App\Models\User;
use App\Models\DpmTarget;
use App\Models\AreaDitemukan;
use App\Support\ConveyorGroup;
use App\Support\SubDefectHelper;
use App\Services\ExternalApiService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $rawMode = strtolower((string) $request->query('mode', 'total'));
        $currentMode = match ($rawMode) {
            'final', 'pre', 'total' => $rawMode,
            'internal', 'rework'    => 'final',
            'all'                   => 'total',
            default                 => 'total',
        };
        $mappedMode = $currentMode;

        try {
            ExternalApiService::syncFromApi();
        } catch (\Exception $e) {
            Log::warning('Dashboard: External API sync skipped - ' . $e->getMessage());
        }

        // Query metrik defect hanya membaca data operator (rework_group_id IS NULL)
        $baseDefectQuery = Defect::operatorInput();
        if ($mappedMode === 'final') {
            $baseDefectQuery->where('jenis_assy', 'Final Assy');
        } elseif ($mappedMode === 'pre') {
            $baseDefectQuery->where('jenis_assy', 'Pre Assy');
        }

        // Hitung statistik dari database
        $defectBulanIni = (int) Defect::defectBulanIni($mappedMode)->sum('quantity');
        $defectToday = (clone $baseDefectQuery)->whereDate('waktu', Carbon::today())->sum('quantity');

        // 🆕 Active Users & Total Users - diambil dari endpoint qa-backend
        $totalUsers = 0;
        $activeUsers = 0;

        try {
            $apiUrl = config('services.external_api.url');
            $response = Http::timeout(5)->get($apiUrl . '/users');

            if ($response->successful()) {
                $users = $response->json('data');
                
                if (!empty($users) && is_array($users)) {
                    if (isset($users['message']) || (isset($users[0]) && !is_array($users[0]))) {
                        $users = [];
                    }
                    $totalUsers = count($users);

                    $activeThreshold = Carbon::now('UTC')->subMinutes(5);
                    $activeUsers = collect($users)->filter(function ($user) use ($activeThreshold) {
                        if (!is_array($user) || empty($user['last_active_at'])) {
                            return false;
                        }
                        try {
                            $lastActive = Carbon::parse($user['last_active_at'], 'UTC');
                            return $lastActive->greaterThan($activeThreshold);
                        } catch (\Exception $e) {
                            return false;
                        }
                    })->count();
                }
            } else {
                Log::warning('Dashboard: Failed to fetch users from qa-backend, status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::warning('Dashboard: Error fetching active users - ' . $e->getMessage());
        }

        // Mengambil 4 data defect terbaru secara riil (hanya input operator)
        $recentDefects = (clone $baseDefectQuery)->orderBy('waktu', 'desc')->take(4)->get();

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay();
        $startOfWeek = Carbon::now()->startOfWeek()->startOfDay();

        // 1 DB query untuk mengambil semua data defect operator bulan ini untuk analitik chart
        $monthRecords = (clone $baseDefectQuery)
            ->where('waktu', '>=', $startOfMonth)
            ->get(['waktu', 'jenis_assy', 'quantity', 'inspect_quantity', 'area_ditemukan', 'ditemukan_oleh']);

        $finalAssyChart = $this->buildAssyChartData('Final Assy', $monthRecords, $startOfWeek, $mappedMode);
        $preAssyChart = $this->buildAssyChartData('Pre Assy', $monthRecords, $startOfWeek, $mappedMode);

        // Opsi dropdown filter untuk Dashboard Analytics: grup unik dari gabungan master dan defects Final Assy
        $finalAssyConveyors = ConveyorGroup::availableGroups();

        $preAssyCarlines = \App\Models\Carline::preAssy()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('dashboard', compact(
            'defectBulanIni',
            'defectToday',
            'activeUsers',
            'totalUsers',
            'recentDefects',
            'finalAssyChart',
            'preAssyChart',
            'finalAssyConveyors',
            'preAssyCarlines',
            'currentMode'
        ));
    }

    /**
     * API Live Polling: Dashboard Charts Data Real-time (Final Assy & Pre Assy)
     */
    public function apiCharts(Request $request = null)
    {
        $request = $request ?? request();
        $mode = strtolower((string) $request->input('mode', 'total'));
        $currentMode = in_array($mode, ['total', 'final', 'pre'], true) ? $mode : 'total';

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay();
        $startOfWeek = Carbon::now()->startOfWeek()->startOfDay();

        $baseDefectQuery = Defect::operatorInput();
        if ($currentMode === 'final') {
            $baseDefectQuery->where('jenis_assy', 'Final Assy');
        } elseif ($currentMode === 'pre') {
            $baseDefectQuery->where('jenis_assy', 'Pre Assy');
        }

        $monthRecords = (clone $baseDefectQuery)
            ->where('waktu', '>=', $startOfMonth)
            ->get(['waktu', 'jenis_assy', 'quantity', 'inspect_quantity', 'area_ditemukan', 'ditemukan_oleh']);

        $data = [
            'success'   => true,
            'finalAssy' => $this->buildAssyChartData('Final Assy', $monthRecords, $startOfWeek, $currentMode),
            'preAssy'   => $this->buildAssyChartData('Pre Assy', $monthRecords, $startOfWeek, $currentMode),
        ];

        return response()->json($data);
    }

    /**
     * Helper kalkulasi data chart Quantity Inspect vs Quantity Defect
     */
    protected function buildAssyChartData($jenisAssy, $monthRecords, $startOfWeek, string $mode = 'total')
    {
        $records = $monthRecords->where('jenis_assy', $jenisAssy);

        // 1. Hari Ini (24 Jam)
        $todayInspect = array_fill(0, 24, 0);
        $todayDefect = array_fill(0, 24, 0);
        $todayRecords = $records->filter(fn($r) => Carbon::parse($r->waktu)->isToday());
        foreach ($todayRecords as $r) {
            $h = (int) Carbon::parse($r->waktu)->format('H');
            $todayInspect[$h] += (int) ($r->inspect_quantity ?? 0);
            $todayDefect[$h] += (int) ($r->quantity ?? 0);
        }

        // 2. Minggu Ini (Senin - Minggu)
        $weekInspect = array_fill(0, 7, 0);
        $weekDefect = array_fill(0, 7, 0);
        for ($i = 0; $i < 7; $i++) {
            $dayDate = $startOfWeek->copy()->addDays($i)->format('Y-m-d');
            $dayRecords = $records->filter(fn($r) => Carbon::parse($r->waktu)->format('Y-m-d') === $dayDate);
            $weekInspect[$i] = (int) $dayRecords->sum('inspect_quantity');
            $weekDefect[$i] = (int) $dayRecords->sum('quantity');
        }

        // 3. Bulan Ini (Group per 5 hari)
        $monthRanges = [
            [1, 5], [6, 10], [11, 15], [16, 20], [21, 25], [26, 31]
        ];
        $monthInspect = [];
        $monthDefect = [];
        foreach ($monthRanges as $range) {
            $rangeRecords = $records->filter(function ($r) use ($range) {
                $d = Carbon::parse($r->waktu)->day;
                return $d >= $range[0] && $d <= $range[1];
            });
            $monthInspect[] = (int) $rangeRecords->sum('inspect_quantity');
            $monthDefect[] = (int) $rangeRecords->sum('quantity');
        }

        $allTime = Defect::operatorInput()->where('jenis_assy', $jenisAssy);

        return [
            'today' => [
                'inspect' => $todayInspect,
                'defect'  => $todayDefect,
            ],
            'week' => [
                'inspect' => $weekInspect,
                'defect'  => $weekDefect,
            ],
            'month' => [
                'inspect' => $monthInspect,
                'defect'  => $monthDefect,
            ],
            'total_inspect'    => (int) (clone $allTime)->sum('inspect_quantity'),
            'total_defect'     => (int) (clone $allTime)->sum('quantity'),
            'today_inspect'    => (int) $todayRecords->sum('inspect_quantity'),
            'today_defect'     => (int) $todayRecords->sum('quantity'),
            'total_harness'    => null,
            'today_harness'    => null,
            'dpm_rework'       => null,
            'today_dpm_rework' => null,
            'internal_defect'  => null,
            'rework_defect'    => null,
            'rework_harness'   => null,
            'internal_percent' => null,
            'rework_percent'   => null,
        ];
    }

    /**
     * Endpoint Analytics khusus Admin Dashboard (DPM & Pareto)
     */
    public function analytics(Request $request)
    {
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::today()->subDays(29)->startOfDay();

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $groupBy = strtolower((string) $request->input('group_by', 'day'));
        if (!in_array($groupBy, ['day', 'week', 'month'], true)) {
            $groupBy = 'day';
        }

        $conveyor = $request->input('conveyor');
        if ($conveyor === 'all' || $conveyor === '') {
            $conveyor = null;
        }

        $carlineId = $request->input('carline_id');
        if ($carlineId === 'all' || $carlineId === '') {
            $carlineId = null;
        }

        $paretoBy = strtolower((string) $request->input('pareto_by', 'sub_defect'));
        $data = [];

        // Resolusi mode: 'total' | 'final' | 'pre'
        $rawMode = strtolower((string) $request->input('mode', ''));
        $mode = match ($rawMode) {
            'all'          => 'all',
            'rework'       => 'rework',
            'pre'          => 'pre',
            'final'        => 'final',
            'total'        => 'total',
            'internal'     => 'final',
            default        => ($rawMode === '' ? 'final' : 'total'),
        };

        // Jika parameter assy dikirim, kembalikan hanya data assy yang diminta
        if ($request->filled('assy')) {
            $assyParam = strtolower((string) $request->input('assy'));
            if ($assyParam === 'final') {
                $effConveyor = ($mode === 'total' || $mode === 'all') ? null : $conveyor;
                $data['dpm_final'] = $this->buildDpmTrends('Final Assy', $startDate, $endDate, $groupBy, $effConveyor, null);
                if ($mode === 'all') {
                    $paretoCombined = $this->buildParetoCombined($startDate, $endDate, $paretoBy);
                    $data['pareto_combined'] = $paretoCombined;
                    $data['pareto_final'] = $paretoCombined;
                    $data['pareto_defect_combined'] = $this->buildParetoCombined($startDate, $endDate, 'defect');
                    $data['pareto_defect_final'] = $data['pareto_defect_combined'];
                } else {
                    $data['pareto_final'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyor, null, $paretoBy);
                    $data['pareto_defect_final'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyor, null, 'defect');
                }
                $data['pareto_final_internal'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyor, null, $paretoBy, 'internal');
                $data['pareto_defect_final_internal'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyor, null, 'defect', 'internal');
                $data['pareto_final_assembly'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyor, null, $paretoBy, 'assembly');
                $data['pareto_defect_final_assembly'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyor, null, 'defect', 'assembly');
                $data['top5_area_final'] = $this->buildTop5AreaFinal($startDate, $endDate, $effConveyor);
                return response()->json($data);
            } elseif ($assyParam === 'pre') {
                $data['dpm_pre'] = $this->buildDpmTrends('Pre Assy', $startDate, $endDate, $groupBy, null, $carlineId);
                $data['pareto_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, $paretoBy);
                $data['pareto_defect_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, 'defect');
                $data['pareto_pre_operator'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, $paretoBy, 'operator');
                $data['pareto_defect_pre_operator'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, 'defect', 'operator');
                $data['pareto_pre_inspektor'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, $paretoBy, 'inspektor');
                $data['pareto_defect_pre_inspektor'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, 'defect', 'inspektor');
                return response()->json($data);
            }
        }

        $effConveyorFinal = ($mode === 'total' || $mode === 'all') ? null : $conveyor;
        $data['pareto_final_internal'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyorFinal, null, $paretoBy, 'internal');
        $data['pareto_defect_final_internal'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyorFinal, null, 'defect', 'internal');
        $data['pareto_final_assembly'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyorFinal, null, $paretoBy, 'assembly');
        $data['pareto_defect_final_assembly'] = $this->buildPareto('Final Assy', $startDate, $endDate, $effConveyorFinal, null, 'defect', 'assembly');

        $data['pareto_pre_operator'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, $paretoBy, 'operator');
        $data['pareto_defect_pre_operator'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, 'defect', 'operator');
        $data['pareto_pre_inspektor'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, $paretoBy, 'inspektor');
        $data['pareto_defect_pre_inspektor'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, 'defect', 'inspektor');

        if ($mode === 'all') {
            $effConveyor = null;
            $data['dpm_final'] = $this->buildDpmTrends('Final Assy', $startDate, $endDate, $groupBy, $effConveyor, null);
            $data['dpm_pre'] = $this->buildDpmTrends('Pre Assy', $startDate, $endDate, $groupBy, null, $carlineId);
            $paretoCombined = $this->buildParetoCombined($startDate, $endDate, $paretoBy);
            $data['pareto_combined'] = $paretoCombined;
            $data['pareto_final'] = $paretoCombined;
            $data['pareto_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, $paretoBy);
            $data['top5_area_final'] = $this->buildTop5AreaFinal($startDate, $endDate, $effConveyor);
            $data['pareto_defect_combined'] = $this->buildParetoCombined($startDate, $endDate, 'defect');
            $data['pareto_defect_final'] = $data['pareto_defect_combined'];
            $data['pareto_defect_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, 'defect');
        } elseif ($mode === 'total') {
            $data['dpm_final'] = $this->buildDpmTrends('Final Assy', $startDate, $endDate, $groupBy, null, null);
            $data['dpm_pre'] = $this->buildDpmTrends('Pre Assy', $startDate, $endDate, $groupBy, null, null);
            $data['pareto_final'] = $this->buildPareto('Final Assy', $startDate, $endDate, null, null, $paretoBy);
            $data['pareto_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, null, $paretoBy);
            $data['top5_area_final'] = $this->buildTop5AreaFinal($startDate, $endDate, null);
            $data['pareto_defect_final'] = $this->buildPareto('Final Assy', $startDate, $endDate, null, null, 'defect');
            $data['pareto_defect_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, null, 'defect');
        } elseif ($mode === 'pre') {
            $data['dpm_pre'] = $this->buildDpmTrends('Pre Assy', $startDate, $endDate, $groupBy, null, $carlineId);
            $data['pareto_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, $paretoBy);
            $data['pareto_defect_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, 'defect');
        } else {
            // Mode 'final' / 'internal' / default
            $data['dpm_final'] = $this->buildDpmTrends('Final Assy', $startDate, $endDate, $groupBy, $conveyor, null);
            $data['dpm_pre'] = $this->buildDpmTrends('Pre Assy', $startDate, $endDate, $groupBy, null, $carlineId);
            $data['pareto_final'] = $this->buildPareto('Final Assy', $startDate, $endDate, $conveyor, null, $paretoBy);
            $data['pareto_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, $paretoBy);
            $data['top5_area_final'] = $this->buildTop5AreaFinal($startDate, $endDate, $conveyor);
            $data['pareto_defect_final'] = $this->buildPareto('Final Assy', $startDate, $endDate, $conveyor, null, 'defect');
            $data['pareto_defect_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId, 'defect');
        }

        return response()->json($data);
    }

    /**
     * Endpoint API: Top 5 Area Ditemukan (Final Assy)
     */
    public function apiTop5AreaFinal(Request $request)
    {
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::today()->subDays(29)->startOfDay();

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $conveyor = $request->input('conveyor');
        if ($conveyor === 'all' || $conveyor === '') {
            $conveyor = null;
        }

        $items = $this->buildTop5AreaFinal($startDate, $endDate, $conveyor);

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * Agregasi database Top 5 Area Ditemukan (Final Assy, Zero Defect dikecualikan, hanya data operator)
     */
    public function buildTop5AreaFinal(Carbon $startDate, Carbon $endDate, ?string $conveyor): array
    {
        $query = Defect::operatorInput()
            ->where('jenis_assy', 'Final Assy')
            ->actualDefect()
            ->whereBetween('waktu', [$startDate, $endDate])
            ->whereNotNull('area_ditemukan')
            ->where('area_ditemukan', '!=', '');

        if (!empty($conveyor)) {
            $matching = $this->getConveyorNamesByGroup($conveyor);
            if (!empty($matching)) {
                $query->whereIn('conveyor', $matching);
            } else {
                $query->where('conveyor', $conveyor);
            }
        }

        $records = $query->selectRaw("area_ditemukan as name, SUM(quantity) as total")
            ->groupBy('area_ditemukan')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        return $records->map(function ($r) {
            return [
                'name'  => $r->name,
                'total' => (int) $r->total,
            ];
        })->values()->all();
    }

    /**
     * Endpoint Drilldown Pareto:
     * - Level 2: Breakdown per Carline (Defect -> Carline)
     * - Level 3: Breakdown per Area Ditemukan (Defect & Carline -> Area Ditemukan)
     * - Fallback legacy: Breakdown per Conveyor/Carline untuk Sub Defect tertentu
     */
    public function paretoDrilldown(Request $request)
    {
        $rawDefect = trim((string) $request->input('defect', ''));
        $rawSubDefect = trim((string) $request->input('sub_defect', ''));
        $targetCarline = trim((string) $request->input('carline', ''));
        $level = (int) $request->input('level', 0);

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::today()->subDays(29)->startOfDay();

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $jenisAssy = (string) $request->input('jenis_assy', 'Final Assy');
        $category = strtolower(trim((string) $request->input('category', '')));
        $rawParetoBy = strtolower(trim((string) $request->input('pareto_by', 'defect')));
        $paretoBy = in_array($rawParetoBy, ['defect', 'sub_defect'], true) ? $rawParetoBy : 'defect';
        $hasExplicitParetoBy = $request->filled('pareto_by');

        $applyCategoryFilter = function ($q) use ($category, &$jenisAssy) {
            if ($category === 'internal') {
                $jenisAssy = 'Final Assy';
                $q->where('defects.jenis_assy', 'Final Assy');
                $assemblyNames = AreaDitemukan::assemblyNames();
                $q->where(function ($sub) use ($assemblyNames) {
                    $sub->whereNull('defects.area_ditemukan')
                        ->orWhere('defects.area_ditemukan', '')
                        ->orWhereNotIn('defects.area_ditemukan', $assemblyNames);
                });
            } elseif ($category === 'assembly') {
                $jenisAssy = 'Final Assy';
                $q->where('defects.jenis_assy', 'Final Assy');
                $assemblyNames = AreaDitemukan::assemblyNames();
                $q->whereIn('defects.area_ditemukan', $assemblyNames);
            } elseif ($category === 'operator') {
                $jenisAssy = 'Pre Assy';
                $q->where('defects.jenis_assy', 'Pre Assy')
                  ->where('defects.ditemukan_oleh', 'Operator');
            } elseif ($category === 'inspektor') {
                $jenisAssy = 'Pre Assy';
                $q->where('defects.jenis_assy', 'Pre Assy')
                  ->where('defects.ditemukan_oleh', 'Inspektor');
            }
        };

        // LEVEL 3: Breakdown per Area Ditemukan (Defect + Carline -> Area Ditemukan)
        if ($targetCarline !== '' || $level === 3 || $request->input('metric') === 'area_ditemukan') {
            $targetDefect = $rawDefect !== '' ? $rawDefect : $rawSubDefect;
            $cleanDefect = preg_replace('/^\[(Final[\s+]+Pre|Final|Pre)\]\s*/i', '', $targetDefect);

            $query = Defect::operatorInput()
                ->leftJoin('carlines', 'defects.carline_id', '=', 'carlines.id')
                ->whereBetween('defects.waktu', [$startDate, $endDate])
                ->where(function ($q) {
                    $q->whereNull('defects.jenis_defect')->orWhere('defects.jenis_defect', '!=', 'Zero Defect');
                })
                ->where('defects.quantity', '>', 0);

            if ($category !== '') {
                $applyCategoryFilter($query);
            } else {
                if ($jenisAssy === 'Pre Assy') {
                    $query->where('defects.jenis_assy', 'Pre Assy');
                } elseif ($jenisAssy === 'Final Assy') {
                    $query->where('defects.jenis_assy', 'Final Assy');
                }
            }

            if ($cleanDefect !== '') {
                $query->where(function ($q) use ($cleanDefect, $paretoBy, $hasExplicitParetoBy) {
                    if ($hasExplicitParetoBy && $paretoBy === 'sub_defect') {
                        $q->where('defects.jenis_sub_defect', $cleanDefect)
                          ->orWhereRaw('UPPER(TRIM(defects.jenis_sub_defect)) = ?', [strtoupper($cleanDefect)]);
                    } elseif ($hasExplicitParetoBy && $paretoBy === 'defect') {
                        $q->where('defects.jenis_defect', $cleanDefect)
                          ->orWhereRaw('UPPER(TRIM(defects.jenis_defect)) = ?', [strtoupper($cleanDefect)]);
                    } else {
                        $q->where('defects.jenis_defect', $cleanDefect)
                          ->orWhereRaw('UPPER(TRIM(defects.jenis_defect)) = ?', [strtoupper($cleanDefect)])
                          ->orWhere('defects.jenis_sub_defect', $cleanDefect)
                          ->orWhereRaw('UPPER(TRIM(defects.jenis_sub_defect)) = ?', [strtoupper($cleanDefect)]);
                    }
                });
            }

            if ($targetCarline === 'Tanpa Carline' || $targetCarline === 'Tanpa Conveyor' || $targetCarline === 'Pre: Tanpa Carline' || $targetCarline === 'Final: Tanpa Conveyor') {
                $query->where(function ($q) {
                    $q->whereNull('defects.carline_id')
                      ->where(function ($sq) {
                          $sq->whereNull('defects.conveyor')->orWhere('defects.conveyor', '')->orWhere('defects.conveyor', '-');
                      });
                });
            } elseif ($targetCarline !== '') {
                $cleanCarline = preg_replace('/^(Pre:\s*|Final:\s*)/i', '', $targetCarline);
                $query->where(function ($q) use ($cleanCarline) {
                    $q->where('carlines.name', $cleanCarline)
                      ->orWhereRaw('UPPER(TRIM(carlines.name)) = ?', [strtoupper($cleanCarline)])
                      ->orWhere('defects.conveyor', $cleanCarline)
                      ->orWhereRaw('UPPER(TRIM(defects.conveyor)) = ?', [strtoupper($cleanCarline)]);
                });
            }

            $records = $query->selectRaw("COALESCE(NULLIF(TRIM(defects.area_ditemukan), ''), 'Tidak Diketahui') as area_name, SUM(defects.quantity) as total")
                ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(defects.area_ditemukan), ''), 'Tidak Diketahui')"))
                ->orderByDesc('total')
                ->take(10)
                ->get();

            $items = $records->map(fn($r) => ['name' => $r->area_name, 'total' => (int) $r->total])->values()->all();

            return response()->json([
                'defect'     => $cleanDefect,
                'carline'    => $targetCarline,
                'metric'     => 'area_ditemukan',
                'level'      => 3,
                'category'   => $category ?: null,
                'items'      => $items,
            ]);
        }

        // LEVEL 2: Breakdown per Carline bila parameter defect terisi atau level 2 eksplisit
        if ($rawDefect !== '' || $level === 2) {
            $targetDefect = $rawDefect !== '' ? $rawDefect : $rawSubDefect;
            $cleanDefect = preg_replace('/^\[(Final[\s+]+Pre|Final|Pre)\]\s*/i', '', $targetDefect);

            $query = Defect::operatorInput()
                ->leftJoin('carlines', 'defects.carline_id', '=', 'carlines.id')
                ->whereBetween('defects.waktu', [$startDate, $endDate])
                ->where(function ($q) {
                    $q->whereNull('defects.jenis_defect')->orWhere('defects.jenis_defect', '!=', 'Zero Defect');
                })
                ->where('defects.quantity', '>', 0);

            if ($category !== '') {
                $applyCategoryFilter($query);
                if ($jenisAssy === 'Pre Assy') {
                    $selectExpr = "COALESCE(carlines.name, 'Tanpa Carline') as name";
                } else {
                    $selectExpr = "COALESCE(NULLIF(TRIM(defects.conveyor), ''), carlines.name, 'Tanpa Conveyor') as name";
                }
            } else {
                if ($jenisAssy === 'Pre Assy') {
                    $query->where('defects.jenis_assy', 'Pre Assy');
                    $selectExpr = "COALESCE(carlines.name, 'Tanpa Carline') as name";
                } elseif ($jenisAssy === 'Final Assy') {
                    $query->where('defects.jenis_assy', 'Final Assy');
                    $selectExpr = "COALESCE(NULLIF(TRIM(defects.conveyor), ''), carlines.name, 'Tanpa Conveyor') as name";
                } else {
                    $selectExpr = "CASE WHEN defects.jenis_assy = 'Pre Assy' THEN CASE WHEN carlines.name IS NOT NULL AND carlines.name != '' THEN 'Pre: ' || carlines.name ELSE 'Pre: Tanpa Carline' END ELSE CASE WHEN defects.conveyor IS NOT NULL AND defects.conveyor != '' THEN 'Final: ' || defects.conveyor ELSE 'Final: Tanpa Conveyor' END END as name";
                }
            }

            $query->where(function ($q) use ($cleanDefect, $paretoBy) {
                if ($paretoBy === 'sub_defect') {
                    $q->where('defects.jenis_sub_defect', $cleanDefect)
                      ->orWhereRaw('UPPER(TRIM(defects.jenis_sub_defect)) = ?', [strtoupper($cleanDefect)]);
                } else {
                    $q->where('defects.jenis_defect', $cleanDefect)
                      ->orWhereRaw('UPPER(TRIM(defects.jenis_defect)) = ?', [strtoupper($cleanDefect)]);
                }
            });

            $records = $query->selectRaw("{$selectExpr}, SUM(defects.quantity) as total")
                ->groupBy(DB::raw(str_replace(' as name', '', $selectExpr)))
                ->orderByDesc('total')
                ->take(10)
                ->get();

            $items = $records->map(fn($r) => ['name' => $r->name, 'total' => (int) $r->total])->values()->all();

            return response()->json([
                'defect'     => $cleanDefect,
                'metric'     => 'carline',
                'level'      => 2,
                'category'   => $category ?: null,
                'items'      => $items,
            ]);
        }

        // LEGACY FALLBACK: Sub Defect Breakdown
        $subDefect = preg_replace('/^\[(Final[\s+]+Pre|Final|Pre)\]\s*/i', '', $rawSubDefect);
        if ($subDefect === '') {
            return response()->json([
                'sub_defect' => '',
                'metric'     => 'conveyor',
                'items'      => [],
            ]);
        }

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::today()->subDays(29)->startOfDay();

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $jenisAssy = (string) $request->input('jenis_assy', 'Final Assy');

        $targetNormalized = SubDefectHelper::normalize($subDefect);

        $rawMode = strtolower((string) $request->input('mode', ''));
        if ($rawMode === 'all' || $rawMode === 'total') {
            $records = Defect::operatorInput()
                ->whereBetween('waktu', [$startDate, $endDate])
                ->where(function ($q) {
                    $q->whereNull('jenis_defect')->orWhere('jenis_defect', '!=', 'Zero Defect');
                })
                ->where('quantity', '>', 0)
                ->with('carline:id,name')
                ->select(['id', 'jenis_assy', 'conveyor', 'carline_id', 'area_ditemukan', 'jenis_sub_defect', 'quantity'])
                ->get();

            $filtered = $records->filter(function ($d) use ($targetNormalized) {
                return SubDefectHelper::normalize($d->jenis_sub_defect) === $targetNormalized;
            });

            $totals = [];
            foreach ($filtered as $d) {
                $qty = (int) $d->quantity;
                if ($d->jenis_assy === 'Pre Assy') {
                    $carline = $d->carline?->name ? trim($d->carline->name) : '';
                    $label = $carline !== '' ? "Pre: {$carline}" : 'Pre: Tanpa Carline';
                } else {
                    $conveyor = trim((string) $d->conveyor);
                    $label = ($conveyor !== '' && $conveyor !== '-') ? "Final: {$conveyor}" : 'Final: Tanpa Conveyor';
                }
                $totals[$label] = ($totals[$label] ?? 0) + $qty;
            }

            arsort($totals);
            $top5 = array_slice($totals, 0, 5, true);

            $items = [];
            foreach ($top5 as $name => $total) {
                $items[] = ['name' => $name, 'total' => $total];
            }

            return response()->json([
                'sub_defect' => $targetNormalized,
                'metric'     => 'conveyor_carline',
                'items'      => $items,
            ]);
        }

        $query = Defect::operatorInput()
            ->whereBetween('waktu', [$startDate, $endDate])
            ->where(function ($q) {
                $q->whereNull('jenis_defect')->orWhere('jenis_defect', '!=', 'Zero Defect');
            })
            ->where('quantity', '>', 0);

        if ($jenisAssy === 'Pre Assy') {
            $query->where('jenis_assy', 'Pre Assy');
        } elseif ($jenisAssy === 'Final Assy') {
            $query->where('jenis_assy', 'Final Assy');
        }

        // Filter sub defect name (case-insensitive & trimmed, menangani LAIN-LAIN)
        if ($targetNormalized === 'LAIN-LAIN') {
            $query->where(function ($q) {
                $q->whereNull('jenis_sub_defect')
                  ->orWhere('jenis_sub_defect', '')
                  ->orWhere('jenis_sub_defect', '-')
                  ->orWhereRaw('UPPER(TRIM(jenis_sub_defect)) = ?', ['LAIN-LAIN']);
            });
        } else {
            $query->where(function ($q) use ($subDefect) {
                $q->where('jenis_sub_defect', $subDefect)
                  ->orWhereRaw('UPPER(TRIM(jenis_sub_defect)) = ?', [strtoupper($subDefect)]);
            });
        }

        if ($jenisAssy === 'Pre Assy') {
            $defects = $query->with('carline:id,name')->select(['id', 'carline_id', 'quantity'])->get();
            $totals = [];
            foreach ($defects as $d) {
                $carlineName = $d->carline?->name ?: 'Tanpa Carline';
                $totals[$carlineName] = ($totals[$carlineName] ?? 0) + (int) $d->quantity;
            }
            arsort($totals);
            $top5 = array_slice($totals, 0, 5, true);

            $items = [];
            foreach ($top5 as $name => $total) {
                $items[] = ['name' => $name, 'total' => $total];
            }

            return response()->json([
                'sub_defect' => $subDefect,
                'metric'     => 'carline',
                'items'      => $items,
            ]);
        }

        // Final Assy, Rework, or Gabungan -> Conveyor breakdown
        $defects = $query->select(['conveyor', 'area_ditemukan', 'quantity'])->get();
        $totals = [];
        foreach ($defects as $d) {
            $conveyorName = trim((string) $d->conveyor);
            if ($conveyorName === '' || $conveyorName === '-') {
                $conveyorName = filled($d->area_ditemukan) ? $d->area_ditemukan : 'Tanpa Conveyor';
            }
            $totals[$conveyorName] = ($totals[$conveyorName] ?? 0) + (int) $d->quantity;
        }
        arsort($totals);
        $top5 = array_slice($totals, 0, 5, true);

        $items = [];
        foreach ($top5 as $name => $total) {
            $items[] = ['name' => $name, 'total' => $total];
        }

        return response()->json([
            'sub_defect' => $subDefect,
            'metric'     => 'conveyor',
            'items'      => $items,
        ]);
    }

    /**
     * Hitung tren DPM agnostik database (agregasi SQL per hari, grouping lanjut di PHP).
     */
    private function buildDpmTrends(string $jenisAssy, Carbon $startDate, Carbon $endDate, string $groupBy, ?string $conveyor, $carlineId): array
    {
        $conveyorGroup = !empty($conveyor) ? ConveyorGroup::of($conveyor) : null;
        $isFilterActive = ($jenisAssy === 'Final Assy' && !empty($conveyor)) || ($jenisAssy === 'Pre Assy' && !empty($carlineId));

        $baseQuery = Defect::operatorInput()
            ->whereBetween('waktu', [$startDate, $endDate])
            ->where('jenis_assy', $jenisAssy);

        if ($jenisAssy === 'Final Assy' && !empty($conveyor)) {
            $matchingConveyors = $this->getConveyorNamesByGroup($conveyor);
            if (!empty($matchingConveyors)) {
                $baseQuery->whereIn('conveyor', $matchingConveyors);
            } else {
                $baseQuery->where('conveyor', $conveyor);
            }
        } elseif ($jenisAssy === 'Pre Assy' && !empty($carlineId)) {
            $baseQuery->where('carline_id', $carlineId);
        }

        // 1. Total inspect_quantity per hari (dari seluruh data operator termasuk Zero Defect)
        $rawInspect = (clone $baseQuery)
            ->selectRaw('DATE(waktu) as bucket_date, SUM(inspect_quantity) as total_inspect')
            ->groupBy(DB::raw('DATE(waktu)'))
            ->get();

        // 2. Defect aktual breakdown per hari
        if ($jenisAssy === 'Final Assy') {
            $rawDefects = (clone $baseQuery)
                ->actualDefect()
                ->selectRaw('DATE(waktu) as bucket_date, area_ditemukan, SUM(quantity) as total_qty')
                ->groupBy(DB::raw('DATE(waktu)'), 'area_ditemukan')
                ->get();
        } else {
            $rawDefects = (clone $baseQuery)
                ->actualDefect()
                ->selectRaw('DATE(waktu) as bucket_date, ditemukan_oleh, SUM(quantity) as total_qty')
                ->groupBy(DB::raw('DATE(waktu)'), 'ditemukan_oleh')
                ->get();
        }

        $targets = DpmTarget::where('jenis_assy', $jenisAssy)
            ->where('period_start', '<=', $endDate->format('Y-m-d'))
            ->where('period_end', '>=', $startDate->format('Y-m-d'))
            ->get();

        // Bentuk daftar bucket lengkap dari startDate sampai endDate secara kronologis
        $grouped = [];

        if ($groupBy === 'week') {
            $curr = $startDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $last = $endDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            while ($curr->lte($last)) {
                $key = $curr->format('Y-m-d');
                $grouped[$key] = [
                    'label'            => $key,
                    'total_defect'     => 0,
                    'total_inspect'    => 0,
                    'internal_defect'  => 0,
                    'assembly_defect'  => 0,
                    'inspektor_defect' => 0,
                    'operator_defect'  => 0,
                ];
                $curr->addWeek();
            }
        } elseif ($groupBy === 'month') {
            $curr = $startDate->copy()->startOfMonth()->startOfDay();
            $last = $endDate->copy()->startOfMonth()->startOfDay();
            while ($curr->lte($last)) {
                $key = $curr->format('Y-m');
                $grouped[$key] = [
                    'label'            => $key,
                    'total_defect'     => 0,
                    'total_inspect'    => 0,
                    'internal_defect'  => 0,
                    'assembly_defect'  => 0,
                    'inspektor_defect' => 0,
                    'operator_defect'  => 0,
                ];
                $curr->addMonthNoOverflow();
            }
        } else {
            $curr = $startDate->copy()->startOfDay();
            $last = $endDate->copy()->startOfDay();
            while ($curr->lte($last)) {
                $key = $curr->format('Y-m-d');
                $grouped[$key] = [
                    'label'            => $key,
                    'total_defect'     => 0,
                    'total_inspect'    => 0,
                    'internal_defect'  => 0,
                    'assembly_defect'  => 0,
                    'inspektor_defect' => 0,
                    'operator_defect'  => 0,
                ];
                $curr->addDay();
            }
        }

        // Helper untuk key bucket dari tanggal
        $resolveBucketKey = function (Carbon $date) use ($groupBy) {
            if ($groupBy === 'week') {
                return $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            } elseif ($groupBy === 'month') {
                return $date->format('Y-m');
            }
            return $date->format('Y-m-d');
        };

        // Agregasikan inspect
        foreach ($rawInspect as $rec) {
            $key = $resolveBucketKey(Carbon::parse($rec->bucket_date));
            if (isset($grouped[$key])) {
                $grouped[$key]['total_inspect'] += (int) $rec->total_inspect;
            }
        }

        // Agregasikan defect
        if ($jenisAssy === 'Final Assy') {
            $assemblyNames = AreaDitemukan::assemblyNames();
            foreach ($rawDefects as $rec) {
                $key = $resolveBucketKey(Carbon::parse($rec->bucket_date));
                if (isset($grouped[$key])) {
                    $qty = (int) $rec->total_qty;
                    $area = trim((string) $rec->area_ditemukan);
                    if ($area !== '' && in_array($area, $assemblyNames, true)) {
                        $grouped[$key]['assembly_defect'] += $qty;
                    } else {
                        $grouped[$key]['internal_defect'] += $qty;
                    }
                    $grouped[$key]['total_defect'] += $qty;
                }
            }
        } else {
            // Pre Assy: klasifikasi berdasarkan ditemukan_oleh ('Inspektor' vs 'Operator')
            foreach ($rawDefects as $rec) {
                $key = $resolveBucketKey(Carbon::parse($rec->bucket_date));
                if (isset($grouped[$key])) {
                    $qty = (int) $rec->total_qty;
                    $ditemukanOleh = trim((string) $rec->ditemukan_oleh);
                    if ($ditemukanOleh === 'Inspektor') {
                        $grouped[$key]['inspektor_defect'] += $qty;
                    } elseif ($ditemukanOleh === 'Operator') {
                        $grouped[$key]['operator_defect'] += $qty;
                    }
                    $grouped[$key]['total_defect'] += $qty;
                }
            }
        }

        // Batasi jumlah bucket maksimal 400
        if (count($grouped) > 400) {
            $grouped = array_slice($grouped, -400, null, true);
        }

        // Helper untuk ekstrak dan mencocokkan target
        $extractDates = function ($t) {
            $pStart = $t->period_start instanceof \DateTimeInterface
                ? $t->period_start->format('Y-m-d')
                : substr((string) $t->period_start, 0, 10);
            $pEnd = $t->period_end instanceof \DateTimeInterface
                ? $t->period_end->format('Y-m-d')
                : substr((string) $t->period_end, 0, 10);
            return [$pStart, $pEnd];
        };

        $matchesScope = function ($t, bool $isSpecific) use ($jenisAssy, $conveyorGroup, $carlineId) {
            if ($isSpecific) {
                if ($jenisAssy === 'Final Assy') {
                    return $t->conveyor_group === $conveyorGroup;
                } else {
                    return (int) $t->carline_id === (int) $carlineId;
                }
            }
            return $t->carline_id === null && $t->conveyor_group === null;
        };

        $findTargetAtRefDate = function (bool $isSpecific, string $refDate) use ($targets, $extractDates, $matchesScope) {
            return $targets->first(function ($t) use ($extractDates, $matchesScope, $isSpecific, $refDate) {
                if (!$matchesScope($t, $isSpecific)) {
                    return false;
                }
                [$pStart, $pEnd] = $extractDates($t);
                return $pStart <= $refDate && $pEnd >= $refDate;
            });
        };

        $findTargetIntersecting = function (bool $isSpecific, string $bucketStart, string $bucketEnd) use ($targets, $extractDates, $matchesScope) {
            return $targets->filter(function ($t) use ($extractDates, $matchesScope, $isSpecific, $bucketStart, $bucketEnd) {
                if (!$matchesScope($t, $isSpecific)) {
                    return false;
                }
                [$pStart, $pEnd] = $extractDates($t);
                return $pStart <= $bucketEnd && $pEnd >= $bucketStart;
            })->sortByDesc(function ($t) use ($extractDates) {
                [$pStart] = $extractDates($t);
                return $pStart;
            })->first();
        };

        $points = [];
        foreach ($grouped as $key => $bucket) {
            $totalDefect = $bucket['total_defect'];
            $totalInspect = $bucket['total_inspect'];
            $internalDefect = $bucket['internal_defect'];
            $assemblyDefect = $bucket['assembly_defect'];
            $inspektorDefect = $bucket['inspektor_defect'];
            $operatorDefect = $bucket['operator_defect'];

            $dpm = null;
            $dpmInternal = null;
            $dpmAssembly = null;
            $dpmInspektor = null;
            $dpmOperator = null;

            if ($totalInspect > 0) {
                $dpm = round(($totalDefect / $totalInspect) * 1000000, 2);
                if ($jenisAssy === 'Final Assy') {
                    $dpmInternal = round(($internalDefect / $totalInspect) * 1000000, 2);
                    $dpmAssembly = round(($assemblyDefect / $totalInspect) * 1000000, 2);
                } else {
                    $dpmInspektor = round(($inspektorDefect / $totalInspect) * 1000000, 2);
                    $dpmOperator = round(($operatorDefect / $totalInspect) * 1000000, 2);
                }
            }

            // Target matching
            if ($groupBy === 'week') {
                $monday = Carbon::parse($key)->startOfDay();
                $sunday = $monday->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
                $refDate = $monday->lt($startDate) ? $startDate->format('Y-m-d') : $monday->format('Y-m-d');
                $bucketStart = $refDate;
                $bucketEnd = $sunday->gt($endDate) ? $endDate->format('Y-m-d') : $sunday->format('Y-m-d');
            } elseif ($groupBy === 'month') {
                $firstDay = Carbon::parse($key . '-01')->startOfDay();
                $lastDay = $firstDay->copy()->endOfMonth()->startOfDay();
                $refDate = $firstDay->lt($startDate) ? $startDate->format('Y-m-d') : $firstDay->format('Y-m-d');
                $bucketStart = $refDate;
                $bucketEnd = $lastDay->gt($endDate) ? $endDate->format('Y-m-d') : $lastDay->format('Y-m-d');
            } else {
                $refDate = $key;
                $bucketStart = $key;
                $bucketEnd = $key;
            }

            $matchedTarget = null;
            if ($isFilterActive) {
                $matchedTarget = $findTargetAtRefDate(true, $refDate) ?: $findTargetAtRefDate(false, $refDate);
                if (!$matchedTarget && $groupBy !== 'day') {
                    $matchedTarget = $findTargetIntersecting(true, $bucketStart, $bucketEnd) ?: $findTargetIntersecting(false, $bucketStart, $bucketEnd);
                }
            } else {
                $matchedTarget = $findTargetAtRefDate(false, $refDate);
                if (!$matchedTarget && $groupBy !== 'day') {
                    $matchedTarget = $findTargetIntersecting(false, $bucketStart, $bucketEnd);
                }
            }

            $targetVal = $matchedTarget ? (float) $matchedTarget->target_dpm : null;

            $point = [
                'label'                 => $bucket['label'],
                'total_defect'          => $totalDefect,
                'total_inspect'         => $totalInspect,
                'dpm'                   => $dpm,
                'target'                => $targetVal,
                'internal_defect'       => $internalDefect,
                'assembly_defect'       => $assemblyDefect,
                'dpm_internal'          => $dpmInternal,
                'dpm_assembly'          => $dpmAssembly,
                'inspektor_defect'      => $inspektorDefect,
                'operator_defect'       => $operatorDefect,
                'dpm_inspektor'         => $dpmInspektor,
                'dpm_operator'          => $dpmOperator,
                // legacy backward-compatibility
                'def_internal'          => ($jenisAssy === 'Final Assy' ? $internalDefect : $totalDefect),
                'def_rework'            => 0,
                'harness_rework'        => 0,
                'total_defect_internal' => ($jenisAssy === 'Final Assy' ? $internalDefect : $totalDefect),
                'total_defect_rework'   => 0,
                'total_harness_rework'  => 0,
            ];

            $points[] = $point;
        }

        return $points;
    }

    /**
     * Hitung data Pareto (Defect atau sub-defect, Top 10 & persentase kumulatif).
     * Hanya membaca data operator (rework_group_id IS NULL).
     * Mendukung pemisahan kategori ('internal', 'assembly', 'operator', 'inspektor')
     * dengan persentase kumulatif dihitung mandiri dari total kategorinya sendiri.
     */
    private function buildPareto(string $jenisAssy, Carbon $startDate, Carbon $endDate, ?string $conveyor, $carlineId, string $paretoBy = 'defect', ?string $category = null): array
    {
        $query = Defect::operatorInput()
            ->actualDefect()
            ->whereBetween('waktu', [$startDate, $endDate])
            ->where('jenis_assy', $jenisAssy);

        if ($jenisAssy === 'Final Assy' && !empty($conveyor)) {
            $matchingConveyors = $this->getConveyorNamesByGroup($conveyor);
            if (!empty($matchingConveyors)) {
                $query->whereIn('conveyor', $matchingConveyors);
            } else {
                $query->where('conveyor', $conveyor);
            }
        } elseif ($jenisAssy === 'Pre Assy' && !empty($carlineId)) {
            $query->where('carline_id', $carlineId);
        }

        $assemblyNames = ($jenisAssy === 'Final Assy') ? AreaDitemukan::assemblyNames() : [];

        if ($paretoBy === 'defect') {
            $records = $query->select(['jenis_defect', 'quantity', 'area_ditemukan', 'ditemukan_oleh'])->get();

            $totals = [];
            $internal = [];
            $assembly = [];
            $inspektor = [];
            $operator = [];
            $grandTotal = 0;

            foreach ($records as $r) {
                $raw = trim((string) $r->jenis_defect);
                $name = ($raw !== '' && $raw !== '-') ? $raw : 'LAIN-LAIN';
                $qty = (int) $r->quantity;

                if (!isset($totals[$name])) {
                    $totals[$name] = 0;
                    $internal[$name] = 0;
                    $assembly[$name] = 0;
                    $inspektor[$name] = 0;
                    $operator[$name] = 0;
                }

                $totals[$name] += $qty;
                $grandTotal += $qty;

                if ($jenisAssy === 'Final Assy') {
                    $area = trim((string) $r->area_ditemukan);
                    if ($area !== '' && in_array($area, $assemblyNames, true)) {
                        $assembly[$name] += $qty;
                    } else {
                        $internal[$name] += $qty;
                    }
                } else {
                    $dit = trim((string) $r->ditemukan_oleh);
                    if ($dit === 'Inspektor') {
                        $inspektor[$name] += $qty;
                    } elseif ($dit === 'Operator') {
                        $operator[$name] += $qty;
                    }
                }
            }

            if ($category !== null) {
                $targetTotals = match ($category) {
                    'internal'  => $internal,
                    'assembly'  => $assembly,
                    'inspektor' => $inspektor,
                    'operator'  => $operator,
                    default     => $totals,
                };
                $grandCategoryTotal = array_sum($targetTotals);
                $filtered = array_filter($targetTotals, fn($v) => $v > 0);
                arsort($filtered);
                $top10 = array_slice($filtered, 0, 10, true);
                $pareto = [];
                $runningSum = 0;

                foreach ($top10 as $name => $total) {
                    $runningSum += $total;
                    $cumPercent = $grandCategoryTotal > 0 ? round(($runningSum / $grandCategoryTotal) * 100, 2) : 0.0;
                    $pareto[] = [
                        'name'               => $name,
                        'key'                => $name,
                        'total'              => $total,
                        'cumulative_percent' => $cumPercent,
                        'internal_qty'       => $internal[$name] ?? 0,
                        'assembly_qty'       => $assembly[$name] ?? 0,
                        'inspektor_qty'      => $inspektor[$name] ?? 0,
                        'operator_qty'       => $operator[$name] ?? 0,
                        'rework_qty'         => 0,
                    ];
                }

                return $pareto;
            }

            arsort($totals);
            $top10 = array_slice($totals, 0, 10, true);
            $pareto = [];
            $runningSum = 0;

            foreach ($top10 as $name => $total) {
                $runningSum += $total;
                $cumPercent = $grandTotal > 0 ? round(($runningSum / $grandTotal) * 100, 2) : 0.0;
                $pareto[] = [
                    'name'               => $name,
                    'key'                => $name,
                    'total'              => $total,
                    'cumulative_percent' => $cumPercent,
                    'internal_qty'       => $internal[$name] ?? 0,
                    'assembly_qty'       => $assembly[$name] ?? 0,
                    'inspektor_qty'      => $inspektor[$name] ?? 0,
                    'operator_qty'       => $operator[$name] ?? 0,
                    'rework_qty'         => 0,
                ];
            }

            return $pareto;
        }

        // Pareto by sub_defect
        $records = $query->select(['jenis_sub_defect', 'quantity', 'area_ditemukan', 'ditemukan_oleh'])->get();

        $subDefectTotals = [];
        $subDefectInternal = [];
        $subDefectAssembly = [];
        $subDefectInspektor = [];
        $subDefectOperator = [];
        $grandTotal = 0;

        foreach ($records as $r) {
            $normalizedName = SubDefectHelper::normalize($r->jenis_sub_defect);
            $qty = (int) $r->quantity;

            if (!isset($subDefectTotals[$normalizedName])) {
                $subDefectTotals[$normalizedName] = 0;
                $subDefectInternal[$normalizedName] = 0;
                $subDefectAssembly[$normalizedName] = 0;
                $subDefectInspektor[$normalizedName] = 0;
                $subDefectOperator[$normalizedName] = 0;
            }

            $subDefectTotals[$normalizedName] += $qty;
            $grandTotal += $qty;

            if ($jenisAssy === 'Final Assy') {
                $area = trim((string) $r->area_ditemukan);
                if ($area !== '' && in_array($area, $assemblyNames, true)) {
                    $subDefectAssembly[$normalizedName] += $qty;
                } else {
                    $subDefectInternal[$normalizedName] += $qty;
                }
            } else {
                $dit = trim((string) $r->ditemukan_oleh);
                if ($dit === 'Inspektor') {
                    $subDefectInspektor[$normalizedName] += $qty;
                } elseif ($dit === 'Operator') {
                    $subDefectOperator[$normalizedName] += $qty;
                }
            }
        }

        if ($category !== null) {
            $targetTotals = match ($category) {
                'internal'  => $subDefectInternal,
                'assembly'  => $subDefectAssembly,
                'inspektor' => $subDefectInspektor,
                'operator'  => $subDefectOperator,
                default     => $subDefectTotals,
            };
            $grandCategoryTotal = array_sum($targetTotals);
            $filtered = array_filter($targetTotals, fn($v) => $v > 0);
            arsort($filtered);
            $top10 = array_slice($filtered, 0, 10, true);
            $pareto = [];
            $runningSum = 0;

            foreach ($top10 as $name => $total) {
                $runningSum += $total;
                $cumPercent = $grandCategoryTotal > 0 ? round(($runningSum / $grandCategoryTotal) * 100, 2) : 0.0;
                $pareto[] = [
                    'name'               => $name,
                    'key'                => $name,
                    'total'              => $total,
                    'cumulative_percent' => $cumPercent,
                    'internal_qty'       => $subDefectInternal[$name] ?? 0,
                    'assembly_qty'       => $subDefectAssembly[$name] ?? 0,
                    'inspektor_qty'      => $subDefectInspektor[$name] ?? 0,
                    'operator_qty'       => $subDefectOperator[$name] ?? 0,
                    'rework_qty'         => 0,
                ];
            }

            return $pareto;
        }

        arsort($subDefectTotals);
        $top10 = array_slice($subDefectTotals, 0, 10, true);
        $pareto = [];
        $runningSum = 0;

        foreach ($top10 as $name => $total) {
            $runningSum += $total;
            $cumPercent = $grandTotal > 0 ? round(($runningSum / $grandTotal) * 100, 2) : 0.0;
            $pareto[] = [
                'name'               => $name,
                'key'                => $name,
                'total'              => $total,
                'cumulative_percent' => $cumPercent,
                'internal_qty'       => $subDefectInternal[$name] ?? 0,
                'assembly_qty'       => $subDefectAssembly[$name] ?? 0,
                'inspektor_qty'      => $subDefectInspektor[$name] ?? 0,
                'operator_qty'       => $subDefectOperator[$name] ?? 0,
                'rework_qty'         => 0,
            ];
        }

        return $pareto;
    }



    /**
     * Hitung data Pareto gabungan (Defect atau sub-defect, Pre Assy + Final Assy, Top 10 & persentase kumulatif).
     * Hanya membaca data operator (rework_group_id IS NULL).
     */
    private function buildParetoCombined(Carbon $startDate, Carbon $endDate, string $paretoBy = 'defect'): array
    {
        $assemblyNames = AreaDitemukan::assemblyNames();

        $query = Defect::operatorInput()
            ->actualDefect()
            ->whereBetween('waktu', [$startDate, $endDate]);

        if ($paretoBy === 'defect') {
            $records = $query->select(['jenis_defect', 'quantity', 'jenis_assy', 'area_ditemukan', 'ditemukan_oleh'])->get();

            $totals = [];
            $finalQty = [];
            $preQty = [];
            $finalInternal = [];
            $finalAssembly = [];
            $preInspektor = [];
            $preOperator = [];
            $grandTotal = 0;

            foreach ($records as $r) {
                $raw = trim((string) $r->jenis_defect);
                $name = ($raw !== '' && $raw !== '-') ? $raw : 'LAIN-LAIN';
                $qty = (int) $r->quantity;

                if (!isset($totals[$name])) {
                    $totals[$name] = 0;
                    $finalQty[$name] = 0;
                    $preQty[$name] = 0;
                    $finalInternal[$name] = 0;
                    $finalAssembly[$name] = 0;
                    $preInspektor[$name] = 0;
                    $preOperator[$name] = 0;
                }

                $totals[$name] += $qty;
                $grandTotal += $qty;

                if ($r->jenis_assy === 'Final Assy') {
                    $finalQty[$name] += $qty;
                    $area = trim((string) $r->area_ditemukan);
                    if ($area !== '' && in_array($area, $assemblyNames, true)) {
                        $finalAssembly[$name] += $qty;
                    } else {
                        $finalInternal[$name] += $qty;
                    }
                } else {
                    $preQty[$name] += $qty;
                    $dit = trim((string) $r->ditemukan_oleh);
                    if ($dit === 'Inspektor') {
                        $preInspektor[$name] += $qty;
                    } elseif ($dit === 'Operator') {
                        $preOperator[$name] += $qty;
                    }
                }
            }

            arsort($totals);
            $top10 = array_slice($totals, 0, 10, true);
            $pareto = [];
            $runningSum = 0;

            foreach ($top10 as $name => $total) {
                $runningSum += $total;
                $cumPercent = $grandTotal > 0 ? round(($runningSum / $grandTotal) * 100, 2) : 0.0;

                $fQty = $finalQty[$name] ?? 0;
                $pQty = $preQty[$name] ?? 0;

                if ($fQty > 0 && $pQty > 0) {
                    $areaTag = 'Final+Pre';
                } elseif ($fQty > 0) {
                    $areaTag = 'Final';
                } elseif ($pQty > 0) {
                    $areaTag = 'Pre';
                } else {
                    $areaTag = 'Final';
                }

                $pareto[] = [
                    'name'               => $name,
                    'key'                => $name,
                    'label'              => "[{$areaTag}] {$name}",
                    'area_tag'           => $areaTag,
                    'total'              => $total,
                    'cumulative_percent' => $cumPercent,
                    'final_qty'          => $fQty,
                    'pre_qty'            => $pQty,
                    'final_internal_qty' => $finalInternal[$name] ?? 0,
                    'final_assembly_qty' => $finalAssembly[$name] ?? 0,
                    'pre_inspektor_qty'  => $preInspektor[$name] ?? 0,
                    'pre_operator_qty'   => $preOperator[$name] ?? 0,
                    'internal_qty'       => $total,
                    'rework_qty'         => 0,
                ];
            }

            return $pareto;
        }

        // Sub defect combined
        $records = $query->select(['jenis_sub_defect', 'quantity', 'jenis_assy', 'area_ditemukan', 'ditemukan_oleh'])->get();

        $subDefectTotals = [];
        $subDefectFinal = [];
        $subDefectPre = [];
        $subDefectFinalInternal = [];
        $subDefectFinalAssembly = [];
        $subDefectPreInspektor = [];
        $subDefectPreOperator = [];
        $grandTotal = 0;

        foreach ($records as $r) {
            $normalizedName = SubDefectHelper::normalize($r->jenis_sub_defect);
            $qty = (int) $r->quantity;

            if (!isset($subDefectTotals[$normalizedName])) {
                $subDefectTotals[$normalizedName] = 0;
                $subDefectFinal[$normalizedName] = 0;
                $subDefectPre[$normalizedName] = 0;
                $subDefectFinalInternal[$normalizedName] = 0;
                $subDefectFinalAssembly[$normalizedName] = 0;
                $subDefectPreInspektor[$normalizedName] = 0;
                $subDefectPreOperator[$normalizedName] = 0;
            }

            $subDefectTotals[$normalizedName] += $qty;
            $grandTotal += $qty;

            if ($r->jenis_assy === 'Final Assy') {
                $subDefectFinal[$normalizedName] += $qty;
                $area = trim((string) $r->area_ditemukan);
                if ($area !== '' && in_array($area, $assemblyNames, true)) {
                    $subDefectFinalAssembly[$normalizedName] += $qty;
                } else {
                    $subDefectFinalInternal[$normalizedName] += $qty;
                }
            } else {
                $subDefectPre[$normalizedName] += $qty;
                $dit = trim((string) $r->ditemukan_oleh);
                if ($dit === 'Inspektor') {
                    $subDefectPreInspektor[$normalizedName] += $qty;
                } elseif ($dit === 'Operator') {
                    $subDefectPreOperator[$normalizedName] += $qty;
                }
            }
        }

        arsort($subDefectTotals);
        $top10 = array_slice($subDefectTotals, 0, 10, true);
        $pareto = [];
        $runningSum = 0;

        foreach ($top10 as $name => $total) {
            $runningSum += $total;
            $cumPercent = $grandTotal > 0 ? round(($runningSum / $grandTotal) * 100, 2) : 0.0;

            $finalQ = $subDefectFinal[$name] ?? 0;
            $preQ = $subDefectPre[$name] ?? 0;

            if ($finalQ > 0 && $preQ > 0) {
                $areaTag = 'Final+Pre';
            } elseif ($finalQ > 0) {
                $areaTag = 'Final';
            } elseif ($preQ > 0) {
                $areaTag = 'Pre';
            } else {
                $areaTag = 'Final';
            }

            $pareto[] = [
                'name'               => $name,
                'key'                => $name,
                'label'              => "[{$areaTag}] {$name}",
                'area_tag'           => $areaTag,
                'total'              => $total,
                'cumulative_percent' => $cumPercent,
                'final_qty'          => $finalQ,
                'pre_qty'            => $preQ,
                'final_internal_qty' => $subDefectFinalInternal[$name] ?? 0,
                'final_assembly_qty' => $subDefectFinalAssembly[$name] ?? 0,
                'pre_inspektor_qty'  => $subDefectPreInspektor[$name] ?? 0,
                'pre_operator_qty'   => $subDefectPreOperator[$name] ?? 0,
                'internal_qty'       => $total,
                'rework_qty'         => 0,
            ];
        }

        return $pareto;
    }

    /**
     * Cari semua nama konveyor dari gabungan master dan data defects yang memiliki grup yang sama.
     */
    protected function getConveyorNamesByGroup(string $groupKey): array
    {
        return ConveyorGroup::resolveNamesForGroup($groupKey);
    }
}