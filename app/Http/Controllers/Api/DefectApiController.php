<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Defect;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DefectApiController extends Controller
{
    /**
     * Store a newly created defect from external API (Flutter).
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'waktu' => 'nullable|date',
            'user_name' => 'required|string|max:255',
            'jenis_assy' => 'required|string|in:Final Assy,Pre Assy',
            'line_conveyor' => 'required|string|max:255',
            'konveyor' => 'required|string|max:255',
            'jenis_defect' => 'required|string|max:255',
            'jenis_sub_defect' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // 2. Simpan Data Defect ke Database MySQL
        $defect = Defect::create([
            'waktu' => isset($validated['waktu']) ? Carbon::parse($validated['waktu']) : now(),
            'user_name' => $validated['user_name'],
            'jenis_assy' => $validated['jenis_assy'],
            'line_conveyor' => $validated['line_conveyor'],
            'konveyor' => $validated['konveyor'],
            'jenis_defect' => $validated['jenis_defect'],
            'jenis_sub_defect' => $validated['jenis_sub_defect'],
            'quantity' => $validated['quantity'],
        ]);

        // 3. Catat Log Aktivitas Otomatis
        ActivityLog::create([
            'waktu' => now(),
            'user_name' => $validated['user_name'],
            'jenis_aksi' => 'Create Report',
            'aktivitas' => "Melaporkan defect {$validated['jenis_assy']} - {$validated['line_conveyor']} - Jumlah {$validated['quantity']}",
            'jenis_defect' => $validated['jenis_defect'],
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        // 4. Kirim Response Berhasil (JSON)
        return response()->json([
            'success' => true,
            'message' => 'Laporan defect berhasil disimpan!',
            'data' => $defect
        ], 201);
    }

    /**
     * Delete an external defect by its external_id.
     */
    public function deleteExternal(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $success = \App\Services\ExternalApiService::deleteByExternalId($validated['id']);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan defect berhasil dihapus lokal.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Laporan defect tidak ditemukan atau gagal dihapus.'
        ], 404);
    }

    /**
     * Get updated dashboard statistics.
     */
    public function getStats()
{
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
        }
    } catch (\Exception $e) {
        Log::warning('getStats: Error fetching active users - ' . $e->getMessage());
    }

        return response()->json([
            'totalDefect' => (int) \App\Models\Defect::sum('quantity'),
            'defectToday' => (int) \App\Models\Defect::whereDate('waktu', \Carbon\Carbon::today())->sum('quantity'),
            'activeUsers' => $activeUsers,
            'totalUsers'  => $totalUsers,
        ]);
    }
}
