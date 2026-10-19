<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Defect;
use App\Models\User;
use App\Models\DpmTarget;
use App\Services\ExternalApiService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        try {
            ExternalApiService::syncFromApi();
        } catch (\Exception $e) {
            Log::warning('Dashboard: External API sync skipped - ' . $e->getMessage());
        }

        // Hitung statistik dari database
        $totalDefect = Defect::sum('quantity');
        $defectToday = Defect::whereDate('waktu', Carbon::today())->sum('quantity');

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

        // Mengambil 4 data defect terbaru secara riil
        $recentDefects = Defect::orderBy('waktu', 'desc')->take(4)->get();

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay();
        $startOfWeek = Carbon::now()->startOfWeek()->startOfDay();

        // 1 DB query untuk mengambil semua data defect bulan ini untuk analitik chart
        $monthRecords = Defect::where('waktu', '>=', $startOfMonth)
            ->get(['waktu', 'jenis_assy', 'quantity', 'inspect_quantity']);

        $finalAssyChart = $this->buildAssyChartData('Final Assy', $monthRecords, $startOfWeek);
        $preAssyChart = $this->buildAssyChartData('Pre Assy', $monthRecords, $startOfWeek);

        // Opsi dropdown filter untuk Dashboard Analytics
        $finalAssyConveyors = \App\Models\Carline::whereNotNull('car_type_id')
            ->distinct()
            ->orderBy('name')
            ->pluck('name');

        $preAssyCarlines = \App\Models\Carline::whereNull('car_type_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('dashboard', compact(
            'totalDefect',
            'defectToday',
            'activeUsers',
            'totalUsers',
            'recentDefects',
            'finalAssyChart',
            'preAssyChart',
            'finalAssyConveyors',
            'preAssyCarlines'
        ));
    }

    /**
     * API Live Polling: Dashboard Charts Data Real-time (Final Assy & Pre Assy)
     */
    public function apiCharts()
    {
        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay();
        $startOfWeek = Carbon::now()->startOfWeek()->startOfDay();

        $monthRecords = Defect::where('waktu', '>=', $startOfMonth)
            ->get(['waktu', 'jenis_assy', 'quantity', 'inspect_quantity']);

        return response()->json([
            'success'   => true,
            'finalAssy' => $this->buildAssyChartData('Final Assy', $monthRecords, $startOfWeek),
            'preAssy'   => $this->buildAssyChartData('Pre Assy', $monthRecords, $startOfWeek),
        ]);
    }

    /**
     * Helper kalkulasi data chart Quantity Inspect vs Quantity Defect
     */
    protected function buildAssyChartData($jenisAssy, $monthRecords, $startOfWeek)
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

        $allTime = Defect::where('jenis_assy', $jenisAssy);
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
            'total_inspect' => (int) (clone $allTime)->sum('inspect_quantity'),
            'total_defect'  => (int) (clone $allTime)->sum('quantity'),
            'today_inspect' => (int) $todayRecords->sum('inspect_quantity'),
            'today_defect'  => (int) $todayRecords->sum('quantity'),
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

        $assy = strtolower((string) $request->input('assy'));

        $data = [];

        if ($assy === 'final') {
            $data['dpm_final'] = $this->buildDpmTrends('Final Assy', $startDate, $endDate, $groupBy, $conveyor, null);
            $data['pareto_final'] = $this->buildPareto('Final Assy', $startDate, $endDate, $conveyor, null);
        } elseif ($assy === 'pre') {
            $data['dpm_pre'] = $this->buildDpmTrends('Pre Assy', $startDate, $endDate, $groupBy, null, $carlineId);
            $data['pareto_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId);
        } else {
            $data['dpm_final'] = $this->buildDpmTrends('Final Assy', $startDate, $endDate, $groupBy, $conveyor, null);
            $data['pareto_final'] = $this->buildPareto('Final Assy', $startDate, $endDate, $conveyor, null);
            $data['dpm_pre'] = $this->buildDpmTrends('Pre Assy', $startDate, $endDate, $groupBy, null, $carlineId);
            $data['pareto_pre'] = $this->buildPareto('Pre Assy', $startDate, $endDate, null, $carlineId);
        }

        return response()->json($data);
    }

    /**
     * Hitung tren DPM agnostik database (agregasi SQL per hari, grouping lanjut di PHP).
     */
    private function buildDpmTrends(string $jenisAssy, Carbon $startDate, Carbon $endDate, string $groupBy, ?string $conveyor, $carlineId): array
    {
        $query = Defect::query()
            ->selectRaw('DATE(waktu) as bucket_date, SUM(quantity) as total_defect, SUM(inspect_quantity) as total_inspect')
            ->whereBetween('waktu', [$startDate, $endDate])
            ->where('jenis_assy', $jenisAssy);

        if ($jenisAssy === 'Final Assy' && !empty($conveyor)) {
            $query->where('conveyor', $conveyor);
        } elseif ($jenisAssy === 'Pre Assy' && !empty($carlineId)) {
            $query->where('carline_id', $carlineId);
        }

        $rawRecords = $query->groupBy(DB::raw('DATE(waktu)'))
            ->orderBy('bucket_date', 'asc')
            ->get();

        // 1 query untuk mengambil semua target DPM relevan untuk assy ini
        $targets = DpmTarget::where('jenis_assy', $jenisAssy)
            ->where('period_start', '<=', $endDate->format('Y-m-d'))
            ->where('period_end', '>=', $startDate->format('Y-m-d'))
            ->get();

        $grouped = [];

        foreach ($rawRecords as $rec) {
            $date = Carbon::parse($rec->bucket_date);

            if ($groupBy === 'week') {
                $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
                $key = $startOfWeek->format('Y-m-d');
                $label = $startOfWeek->format('Y-m-d');
            } elseif ($groupBy === 'month') {
                $key = $date->format('Y-m');
                $label = $date->format('Y-m');
            } else {
                $key = $date->format('Y-m-d');
                $label = $date->format('Y-m-d');
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'label'        => $label,
                    'total_defect' => 0,
                    'total_inspect'=> 0,
                ];
            }

            $grouped[$key]['total_defect'] += (int) $rec->total_defect;
            $grouped[$key]['total_inspect'] += (int) $rec->total_inspect;
        }

        $points = [];
        foreach ($grouped as $key => $bucket) {
            $totalDefect = $bucket['total_defect'];
            $totalInspect = $bucket['total_inspect'];
            $dpm = null;

            if ($totalInspect > 0) {
                $dpm = round(($totalDefect / $totalInspect) * 1000000, 2);
            }

            // Tentukan tanggal acuan: tanggal paling awal dalam rentang filter yang masuk bucket
            if ($groupBy === 'week') {
                $monday = Carbon::parse($key)->startOfDay();
                $refDate = $monday->lt($startDate) ? $startDate->format('Y-m-d') : $monday->format('Y-m-d');
            } elseif ($groupBy === 'month') {
                $firstDay = Carbon::parse($key . '-01')->startOfDay();
                $refDate = $firstDay->lt($startDate) ? $startDate->format('Y-m-d') : $firstDay->format('Y-m-d');
            } else {
                $refDate = $key;
            }

            // Cocokkan DpmTarget di memori PHP (tanpa query per titik)
            $targetVal = null;
            foreach ($targets as $t) {
                $pStart = $t->period_start instanceof \DateTimeInterface
                    ? $t->period_start->format('Y-m-d')
                    : substr((string) $t->period_start, 0, 10);
                $pEnd = $t->period_end instanceof \DateTimeInterface
                    ? $t->period_end->format('Y-m-d')
                    : substr((string) $t->period_end, 0, 10);

                if ($pStart <= $refDate && $pEnd >= $refDate) {
                    $targetVal = (float) $t->target_dpm;
                    break;
                }
            }

            $points[] = [
                'label'        => $bucket['label'],
                'total_defect' => $totalDefect,
                'total_inspect'=> $totalInspect,
                'dpm'          => $dpm,
                'target'       => $targetVal,
            ];
        }

        return $points;
    }

    /**
     * Hitung data Pareto sub-defect (Top 10 & persentase kumulatif).
     */
    private function buildPareto(string $jenisAssy, Carbon $startDate, Carbon $endDate, ?string $conveyor, $carlineId): array
    {
        $query = Defect::query()
            ->select(['jenis_sub_defect', 'quantity'])
            ->whereBetween('waktu', [$startDate, $endDate])
            ->where('jenis_assy', $jenisAssy)
            ->where(function ($q) {
                $q->whereNull('jenis_defect')->orWhere('jenis_defect', '!=', 'Zero Defect');
            })
            ->where('quantity', '>', 0);

        if ($jenisAssy === 'Final Assy' && !empty($conveyor)) {
            $query->where('conveyor', $conveyor);
        } elseif ($jenisAssy === 'Pre Assy' && !empty($carlineId)) {
            $query->where('carline_id', $carlineId);
        }

        $records = $query->get();

        $subDefectTotals = [];
        $grandTotal = 0;

        foreach ($records as $r) {
            $rawName = $r->jenis_sub_defect ?? '-';
            $normalizedName = trim(strtoupper($rawName));
            if ($normalizedName === '' || $normalizedName === '-') {
                $normalizedName = 'LAIN-LAIN';
            }
            $qty = (int) $r->quantity;

            if (!isset($subDefectTotals[$normalizedName])) {
                $subDefectTotals[$normalizedName] = 0;
            }
            $subDefectTotals[$normalizedName] += $qty;
            $grandTotal += $qty;
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
                'total'              => $total,
                'cumulative_percent' => $cumPercent,
            ];
        }

        return $pareto;
    }
}