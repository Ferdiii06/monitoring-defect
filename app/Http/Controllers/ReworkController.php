<?php

namespace App\Http\Controllers;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\SubDefectType;
use App\Support\RoleRouteHelper;
use App\Support\SubDefectHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReworkController extends Controller
{
    /**
     * Display a grouped list of rework defect harnesses.
     * Rework user: only sees own harnesses.
     * Administrator: sees harnesses from all rework users, without In form and Out buttons.
     */
    public function index(Request $request)
    {
        $isAdmin = session('user_role') === 'Administrator';
        $userName = session('user_name');

        $query = Defect::where('jenis_assy', 'Final Assy')
            ->whereNotNull('rework_group_id');

        if (!$isAdmin) {
            $query->where('user_name', $userName);
        }

        $paginatedGroups = $query
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

        return view('rework.index', compact('paginatedGroups', 'defectsByGroup', 'finalAssySubDefects', 'isAdmin'));
    }

    /**
     * Handle harness check-in (In) with one or more defects.
     * Only accessible by Rework role.
     */
    public function storeIn(Request $request)
    {
        if (session('user_role') !== 'Rework') {
            return RoleRouteHelper::redirectForRole();
        }

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

        // Duplicate check in one harness
        if (count($validated['sub_defect_ids']) !== count(array_unique($validated['sub_defect_ids']))) {
            return back()->withErrors(['sub_defect_ids' => 'Defect tidak boleh duplikat dalam satu harness.'])->withInput();
        }

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

        // Master area rework check: must exist before inserting defects
        $tipeRework = defined(AreaDitemukan::class . '::TIPE_REWORK')
            ? AreaDitemukan::TIPE_REWORK
            : 'rework';

        $reworkArea = AreaDitemukan::where('tipe', $tipeRework)
            ->orderBy('id', 'asc')
            ->value('name');

        if (!$reworkArea) {
            return back()
                ->withErrors([
                    'area_ditemukan' => 'Area bertipe Rework belum diatur. Hubungi admin untuk menambahkannya di Master Data > Area Ditemukan.',
                ])
                ->withInput();
        }

        $groupId = (string) Str::uuid();
        $now = now();
        $userName = session('user_name');
        $shift = session('current_shift', '1A');

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
     * Only accessible by Rework role (and owner of the group).
     */
    public function storeOut(Request $request, string $groupId)
    {
        if (session('user_role') !== 'Rework') {
            return RoleRouteHelper::redirectForRole();
        }

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

    /**
     * Show edit form for a rework group.
     * Accessible by group owner or Administrator.
     */
    public function edit(Request $request, string $groupId)
    {
        $defects = Defect::where('rework_group_id', $groupId)
            ->with(['defectType', 'subDefectType'])
            ->orderBy('id')
            ->get();

        if ($defects->isEmpty()) {
            abort(404, 'Data harness tidak ditemukan.');
        }

        $first = $defects->first();
        $isAdmin = session('user_role') === 'Administrator';
        $isOwner = $first->user_name === session('user_name');

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit harness ini.');
        }

        $finalAssySubDefects = SubDefectType::whereHas('defectType', function ($q) {
                $q->where('type', 'Final Assy');
            })
            ->with('defectType:id,name')
            ->select('id', 'defect_type_id', 'name', 'code')
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        // Map existing defects to structured array for UI chips
        $currentDefects = $defects->map(function ($d) {
            return [
                'id'   => $d->sub_defect_type_id,
                'name' => $d->jenis_sub_defect ?? $d->subDefectType?->name ?? '-',
                'code' => $d->subDefectType?->code ?? SubDefectHelper::deriveCode($d->jenis_sub_defect) ?? '',
            ];
        })->values();

        $returnUrl = $request->input('return_url');

        return view('rework.edit', compact('defects', 'first', 'groupId', 'finalAssySubDefects', 'currentDefects', 'isAdmin', 'returnUrl'));
    }

    /**
     * Update rework group defects, kanban, and nameplate.
     * Retains original in/out time, duration, and owner account.
     */
    public function update(Request $request, string $groupId)
    {
        $existingDefects = Defect::where('rework_group_id', $groupId)->get();

        if ($existingDefects->isEmpty()) {
            abort(404, 'Data harness tidak ditemukan.');
        }

        $first = $existingDefects->first();
        $isAdmin = session('user_role') === 'Administrator';
        $isOwner = $first->user_name === session('user_name');

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit harness ini.');
        }

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

        // Duplicate check in one harness
        if (count($validated['sub_defect_ids']) !== count(array_unique($validated['sub_defect_ids']))) {
            return back()->withErrors(['sub_defect_ids' => 'Defect tidak boleh duplikat dalam satu harness.'])->withInput();
        }

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

        DB::transaction(function () use ($existingDefects, $groupId, $validated, $subDefects, $first) {
            $kanban = $validated['kanban_assy'];
            $nameplate = $validated['nameplate'];
            $newSubIds = array_map('intval', $validated['sub_defect_ids']);

            $template = $first;
            $existingBySubId = $existingDefects->keyBy('sub_defect_type_id');
            $existingSubIds = $existingBySubId->keys()->map(fn($k) => (int) $k)->all();

            // 1. Defect yang dihapus (ada di existing tapi tidak ada di newSubIds)
            $toDeleteSubIds = array_diff($existingSubIds, $newSubIds);
            foreach ($toDeleteSubIds as $delSubId) {
                if (isset($existingBySubId[$delSubId])) {
                    $existingBySubId[$delSubId]->delete();
                }
            }

            // 2. Defect yang tetap (ada di existing dan ada di newSubIds)
            $toKeepSubIds = array_intersect($existingSubIds, $newSubIds);
            foreach ($toKeepSubIds as $keepSubId) {
                if (isset($existingBySubId[$keepSubId])) {
                    $existingBySubId[$keepSubId]->update([
                        'kanban_assy'    => $kanban,
                        'nameplate_lama' => $nameplate,
                    ]);
                }
            }

            // 3. Defect baru (ada di newSubIds tapi tidak ada di existing)
            $toAddSubIds = array_diff($newSubIds, $existingSubIds);
            foreach ($toAddSubIds as $addSubId) {
                $sub = $subDefects->get($addSubId);
                if (!$sub) {
                    continue;
                }

                Defect::create([
                    'rework_group_id'      => $groupId,
                    'waktu'                => $template->waktu,
                    'waktu_in'             => $template->waktu_in,
                    'waktu_out'            => $template->waktu_out,
                    'durasi_rework_menit'  => $template->durasi_rework_menit,
                    'status_rework'        => $template->status_rework,
                    'kanban_assy'          => $kanban,
                    'nameplate_lama'       => $nameplate,
                    'nameplate_baru'       => $template->nameplate_baru,
                    'user_name'            => $template->user_name, // Akun pembuat tetap pemilik asli
                    'shift'                => $template->shift,
                    'jenis_assy'           => 'Final Assy',
                    'quantity'             => 1,
                    'defect_type_id'       => $sub->defect_type_id,
                    'sub_defect_type_id'   => $sub->id,
                    'jenis_defect'         => $sub->defectType?->name,
                    'jenis_sub_defect'     => $sub->name,
                    'area_ditemukan'       => $template->area_ditemukan ?: (Defect::getReworkAreaNames()[0] ?? null),
                    'conveyor'             => $template->conveyor,
                    'carline_id'           => $template->carline_id,
                ]);
            }
        });

        $returnUrl = $request->input('return_url');

        if ($isAdmin) {
            if ($returnUrl && (Str::startsWith($returnUrl, url('/admin/rework-history')) || Str::startsWith($returnUrl, '/admin/rework-history'))) {
                return redirect()->to($returnUrl)->with('success', 'Riwayat rework harness berhasil diperbarui.');
            }
            return redirect()->route('admin.rework_history.index')->with('success', 'Riwayat rework harness berhasil diperbarui.');
        }

        return redirect()->route('rework.index')->with('success', 'Riwayat rework harness berhasil diperbarui.');
    }
}
