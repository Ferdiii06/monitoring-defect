<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReworkHistoryExport;
use App\Http\Controllers\Controller;
use App\Models\Defect;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReworkHistoryController extends Controller
{
    /**
     * Build base filtered query for rework history (shared between index and export).
     */
    public static function baseQuery(Request $request)
    {
        $reworkAreaNames = Defect::getReworkAreaNames();

        $query = Defect::query()
            ->where('jenis_assy', 'Final Assy')
            ->whereNotNull('rework_group_id');

        if (empty($reworkAreaNames)) {
            $query->whereRaw('0 = 1');
        } else {
            $query->whereIn('area_ditemukan', $reworkAreaNames);
        }

        // 1. Date Range Filter (waktu_in with fallback to waktu)
        $dateRange = (string) $request->input('date_range', '');
        if ($dateRange !== '') {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                try {
                    $startDate = Carbon::parse(trim($dates[0]))->startOfDay();
                    $endDate = Carbon::parse(trim($dates[1]))->endOfDay();
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('waktu_in', [$startDate, $endDate])
                          ->orWhere(function ($sub) use ($startDate, $endDate) {
                              $sub->whereNull('waktu_in')->whereBetween('waktu', [$startDate, $endDate]);
                          });
                    });
                } catch (\Throwable $e) {
                    // Invalid date format ignored
                }
            } elseif (count($dates) === 1 && filled(trim($dates[0]))) {
                try {
                    $startDate = Carbon::parse(trim($dates[0]))->startOfDay();
                    $endDate = Carbon::parse(trim($dates[0]))->endOfDay();
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('waktu_in', [$startDate, $endDate])
                          ->orWhere(function ($sub) use ($startDate, $endDate) {
                              $sub->whereNull('waktu_in')->whereBetween('waktu', [$startDate, $endDate]);
                          });
                    });
                } catch (\Throwable $e) {
                    // Invalid date format ignored
                }
            }
        }

        // 2. Search NP (Nameplate Lama) & Kanban Assy Filter
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nameplate_lama', 'like', "%{$search}%")
                  ->orWhere('kanban_assy', 'like', "%{$search}%");
            });
        }

        // 3. Status Filter (Berjalan / Selesai / all)
        $selectedStatus = (string) $request->input('status', 'all');
        if (in_array($selectedStatus, ['Berjalan', 'Selesai'], true)) {
            $query->where('status_rework', $selectedStatus);
        }

        return $query;
    }

    /**
     * Display a paginated, read-only list of rework history grouped by harness (rework_group_id).
     */
    public function index(Request $request)
    {
        $dateRange = (string) $request->input('date_range', '');
        $search = trim((string) $request->input('search', ''));
        $selectedStatus = (string) $request->input('status', 'all');

        $query = static::baseQuery($request);

        // 4. Paginate distinct rework groups (15 items per page)
        $paginatedGroups = $query
            ->select(
                'rework_group_id',
                DB::raw('MAX(COALESCE(waktu_in, waktu)) as latest_time'),
                DB::raw('MAX(id) as max_id')
            )
            ->groupBy('rework_group_id')
            ->orderByDesc('latest_time')
            ->orderByDesc('max_id')
            ->paginate(15)
            ->withQueryString();

        $groupIds = $paginatedGroups->pluck('rework_group_id')->filter()->all();

        // 5. Eager load defect details for groups in current page
        $defectsByGroup = collect();
        if (!empty($groupIds)) {
            $defectsByGroup = Defect::whereIn('rework_group_id', $groupIds)
                ->with(['defectType', 'subDefectType'])
                ->orderByDesc('waktu_in')
                ->orderBy('id')
                ->get()
                ->groupBy('rework_group_id');
        }

        $totalHarnesses = $paginatedGroups->total();

        return view('admin.rework_history', compact(
            'paginatedGroups',
            'defectsByGroup',
            'dateRange',
            'search',
            'selectedStatus',
            'totalHarnesses'
        ));
    }

    /**
     * Export rework history to Excel based on active filters.
     */
    public function export(Request $request)
    {
        return Excel::download(
            new ReworkHistoryExport($request),
            'riwayat_rework_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
