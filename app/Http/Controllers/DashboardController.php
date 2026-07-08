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
            return redirect('/');
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
                $totalUsers = count($users);

                $activeThreshold = Carbon::now('UTC')->subMinutes(2);
                $activeUsers = collect($users)->filter(function ($user) use ($activeThreshold) {
                    if (empty($user['last_active_at'])) {
                        return false;
                    }
                    $lastActive = Carbon::parse($user['last_active_at'], 'UTC');
                    return $lastActive->greaterThan($activeThreshold);
                })->count();
            } else {
                Log::warning('Dashboard: Failed to fetch users from qa-backend, status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::warning('Dashboard: Error fetching active users - ' . $e->getMessage());
        }

        // Mengambil 4 data defect terbaru secara riil
        $recentDefects = Defect::orderBy('waktu', 'desc')->take(4)->get();

        // 1. Data Hari Ini (Full 24 Jam)
        $todayData = [];
        for ($i = 0; $i < 24; $i++) {
            $startHour = Carbon::today()->setTime($i, 0, 0);
            $endHour = Carbon::today()->setTime($i, 59, 59);
            $todayData[] = (int) Defect::whereBetween('waktu', [$startHour, $endHour])->sum('quantity');
        }

        // 2. Data Minggu Ini (Senin sampai Minggu)
        $weekData = [];
        $startOfWeek = Carbon::now()->startOfWeek();
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $weekData[] = (int) Defect::whereDate('waktu', $day)->sum('quantity');
        }

        // 3. Data Bulan Ini (Group per 5 hari agar grafik tidak terlalu padat)
        $monthData = [];
        $monthRanges = [
            [1, 5], [6, 10], [11, 15], [16, 20], [21, 25], [26, 31]
        ];
        foreach ($monthRanges as $range) {
            $start = Carbon::now()->startOfMonth()->setDay($range[0])->startOfDay();
            $endDay = min($range[1], Carbon::now()->endOfMonth()->day);
            $end = Carbon::now()->startOfMonth()->setDay($endDay)->endOfDay();
            $monthData[] = (int) Defect::whereBetween('waktu', [$start, $end])->sum('quantity');
        }

        return view('dashboard', compact(
            'totalDefect',
            'defectToday',
            'activeUsers',
            'totalUsers',
            'recentDefects',
            'todayData',
            'weekData',
            'monthData'
        ));
    }
}