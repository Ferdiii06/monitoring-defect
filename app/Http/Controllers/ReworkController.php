<?php

namespace App\Http\Controllers;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\SubDefectType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReworkController extends Controller
{
    /**
     * Display a grouped list of rework defect harnesses for the logged in rework user,
     * along with the In form.
     */
    public function index(Request $request)
    {
        $userName = session('user_name');

        $paginatedGroups = Defect::where('jenis_assy', 'Final Assy')
            ->whereNotNull('rework_group_id')
            ->where('user_name', $userName)
            ->select(
                'rework_group_id',
                DB::raw('MAX(COALESCE(waktu_in, waktu)) as latest_time'),
                DB::raw('MAX(id) as max_id')
            )
            ->groupBy('rework_group_id')
            ->orderByDesc('latest_time')
            ->orderByDesc('max_id')
            ->paginate(15);

        $groupIds = $paginatedGroups->pluck('rework_group_id')->filter()->all();

        $defectsByGroup = collect();
        if (!empty($groupIds)) {
            $defectsByGroup = Defect::whereIn('rework_group_id', $groupIds)
                ->with(['defectType', 'subDefectType'])
                ->orderByDesc('waktu_in')
                ->orderBy('id')
                ->get()
                ->groupBy('rework_group_id');
        }

        $finalAssySubDefects = SubDefectType::whereHas('defectType', function ($q) {
                $q->where('type', 'Final Assy');
            })
            ->with('defectType:id,name')
            ->select('id', 'defect_type_id', 'name', 'code')
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        return view('rework.index', compact('paginatedGroups', 'defectsByGroup', 'finalAssySubDefects'));
    }

    /**
     * Handle harness check-in (In) with one or more defects.
     */
    public function storeIn(Request $request)
    {
        $request->merge([
            'kanban_assy' => is_string($request->kanban_assy) ? trim($request->kanban_assy) : $request->kanban_assy,
            'nameplate'   => is_string($request->nameplate) ? strtoupper(trim($request->nameplate)) : $request->nameplate,
        ]);

        $validated = $request->validate([
            'kanban_assy'      => 'required|string|max:255',
            'nameplate'        => 'required|string|max:255',
            'sub_defect_ids'   => 'required|array|min:1',
            'sub_defect_ids.*' => 'required|integer|exists:sub_defect_types,id',
        ], [
            'kanban_assy.required'    => 'Kanban Assy wajib diisi.',
            'nameplate.required'      => 'Nameplate wajib diisi.',
            'sub_defect_ids.required' => 'Minimal satu defect harus dipilih.',
            'sub_defect_ids.min'      => 'Minimal satu defect harus dipilih.',
            'sub_defect_ids.*.exists' => 'Defect yang dipilih tidak valid.',
        ]);

        $uniqueIds = array_unique($validated['sub_defect_ids']);
        $subDefects = SubDefectType::with('defectType')
            ->whereIn('id', $uniqueIds)
            ->get()
            ->keyBy('id');

        if ($subDefects->count() !== count($uniqueIds)) {
            return back()->withErrors(['sub_defect_ids' => 'Defect yang dipilih tidak valid.'])->withInput();
        }

        foreach ($subDefects as $subDefect) {
            if (!$subDefect->defectType || $subDefect->defectType->type !== 'Final Assy') {
                return back()->withErrors(['sub_defect_ids' => 'Defect harus berasal dari tipe Final Assy.'])->withInput();
            }
        }

        $groupId = (string) Str::uuid();
        $now = now();
        $userName = session('user_name');
        $shift = session('current_shift', '1A');

        $reworkArea = Schema::hasTable('area_ditemukans')
            ? AreaDitemukan::where('tipe', 'rework')->value('name')
            : null;

        foreach ($validated['sub_defect_ids'] as $subDefectId) {
            $sub = $subDefects->get($subDefectId);
            if (!$sub) {
                continue;
            }

            Defect::create([
                'rework_group_id'      => $groupId,
                'waktu'                => $now,
                'waktu_in'             => $now,
                'waktu_out'            => null,
                'status_rework'        => 'Berjalan',
                'kanban_assy'          => $validated['kanban_assy'],
                'nameplate_lama'       => $validated['nameplate'],
                'nameplate_baru'       => null,
                'user_name'            => $userName,
                'shift'                => $shift,
                'jenis_assy'           => 'Final Assy',
                'quantity'             => 1,
                'defect_type_id'       => $sub->defect_type_id,
                'sub_defect_type_id'   => $sub->id,
                'jenis_defect'         => $sub->defectType?->name,
                'jenis_sub_defect'     => $sub->name,
                'area_ditemukan'       => $reworkArea,
                'conveyor'             => null,
                'carline_id'           => null,
            ]);
        }

        return redirect()->route('rework.index')->with('success', 'Rework harness berhasil dimulai (In).');
    }

    /**
     * Handle harness completion (Out).
     */
    public function storeOut(Request $request, string $groupId)
    {
        $defects = Defect::where('rework_group_id', $groupId)->get();

        if ($defects->isEmpty()) {
            abort(404, 'Data harness tidak ditemukan.');
        }

        // Hanya pemilik group yang boleh menyelesaikan
        if ($defects->first()->user_name !== session('user_name')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyelesaikan rework harness ini.');
        }

        // Group yang sudah selesai tidak bisa di-Out ulang
        if ($defects->first()->status_rework === 'Selesai') {
            return redirect()->route('rework.index')->with('error', 'Harness ini sudah selesai di-rework.');
        }

        $waktuOut = now();
        $waktuIn = $defects->first()->waktu_in ? Carbon::parse($defects->first()->waktu_in) : $waktuOut;
        $durasiMenit = (int) max(0, round($waktuIn->diffInSeconds($waktuOut) / 60));

        Defect::where('rework_group_id', $groupId)->update([
            'waktu_out'           => $waktuOut,
            'status_rework'       => 'Selesai',
            'durasi_rework_menit' => $durasiMenit,
        ]);

        return redirect()->route('rework.index')->with('success', 'Rework harness berhasil diselesaikan (Out). Durasi: ' . $durasiMenit . ' menit.');
    }
}
