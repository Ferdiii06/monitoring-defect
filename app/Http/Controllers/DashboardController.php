<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Defect;
use App\Models\User;
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

        return view('dashboard', compact(
            'totalDefect',
            'defectToday',
            'activeUsers',
            'totalUsers',
            'recentDefects',
            'finalAssyChart',
            'preAssyChart'
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
}