<?php

namespace App\Http\Controllers;

use App\Models\AreaDitemukan;
use App\Models\CarType;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\SubDefectType;
use App\Support\RoleRouteHelper;
use App\Support\SubDefectHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
                ->with(['defectType', 'subDefectType', 'carline.carType'])
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

        $areas = AreaDitemukan::orderBy('name')->get();
        $carTypes = CarType::with(['carlines' => fn($q) => $q->orderBy('name')])->orderBy('name')->get();

        return view('rework.index', compact('paginatedGroups', 'defectsByGroup', 'finalAssySubDefects', 'isAdmin', 'areas', 'carTypes'));
    }

    /**
     * Handle harness check-in (In) with one or more defects.
     * Only accessible by Rework role.
     */
    /**
     * Handle harness check-in (In) with one or more defects.
     * Supports both Live mode (status OPEN) and Susulan / Kertas Manual mode (status CLOSE).
     * Only accessible by Rework role.
     */
    public function storeIn(Request $request)
    {
        if (session('user_role') !== 'Rework') {
            return RoleRouteHelper::redirectForRole();
        }

        $isSusulan = $request->input('mode_input') === 'susulan';

        $request->merge([
            'kanban_assy' => is_string($request->kanban_assy) ? trim($request->kanban_assy) : $request->kanban_assy,
            'nameplate'   => is_string($request->nameplate) ? strtoupper(trim($request->nameplate)) : $request->nameplate,
        ]);

        $rules = [
            'kanban_assy'          => 'required|string|max:255',
            'nameplate'            => 'required|string|max:255',
            'sub_defect_ids'       => 'required|array|min:1',
            'sub_defect_ids.*'     => 'required|integer|exists:sub_defect_types,id',
            'nameplate_baru'       => 'nullable|string|max:255',
            'no_defective_tag'     => 'nullable|string|max:255',
            'ujung'                => 'nullable|string|max:255',
            'status_4m'            => 'nullable|string|max:50',
            'area_ditemukan'       => 'nullable|string|max:255',
            'posisi_detail_rework' => 'nullable|string',
            'fc_h'                 => 'nullable|string|max:255',
            'fc_w'                 => 'nullable|string|max:255',
            'rc_h'                 => 'nullable|string|max:255',
            'rc_w'                 => 'nullable|string|max:255',
            'no_box'               => 'nullable|string|max:255',
        ];

        $messages = [
            'kanban_assy.required'    => 'Kanban Assy wajib diisi.',
            'nameplate.required'      => 'Nameplate wajib diisi.',
            'sub_defect_ids.required' => 'Minimal satu defect harus dipilih.',
            'sub_defect_ids.min'      => 'Minimal satu defect harus dipilih.',
            'sub_defect_ids.*.exists' => 'Defect yang dipilih tidak valid.',
        ];

        if ($isSusulan) {
            $rules['shift'] = 'required|in:1A,1B,2A,2B';
            $rules['tanggal'] = 'required|date';
            $rules['jam_in'] = 'required';
            $rules['jam_out'] = 'required';

            $messages['shift.required']   = 'Shift wajib dipilih di mode susulan.';
            $messages['shift.in']         = 'Shift harus salah satu dari: 1A, 1B, 2A, atau 2B.';
            $messages['tanggal.required'] = 'Tanggal kejadian rework wajib diisi.';
            $messages['tanggal.date']     = 'Format tanggal kejadian tidak valid.';
            $messages['jam_in.required']  = 'Jam In wajib diisi.';
            $messages['jam_out.required'] = 'Jam Out wajib diisi.';
        } else {
            $rules['shift'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules, $messages);

        // Validasi waktu untuk mode susulan
        $waktuIn = null;
        $waktuOut = null;
        $durasiMenit = null;
        $shift = null;

        if ($isSusulan) {
            $tanggal = $validated['tanggal'];
            $tanggalOut = $request->filled('tanggal_out') ? $request->input('tanggal_out') : $tanggal;
            $jamIn = $validated['jam_in'];
            $jamOut = $validated['jam_out'];
            $shift = $validated['shift'];

            try {
                $waktuIn = Carbon::parse($tanggal . ' ' . $jamIn);
            } catch (\Throwable $e) {
                return back()->withErrors(['jam_in' => 'Format tanggal atau jam In tidak valid.'])->withInput();
            }

            try {
                $waktuOut = Carbon::parse($tanggalOut . ' ' . $jamOut);
            } catch (\Throwable $e) {
                return back()->withErrors(['jam_out' => 'Format tanggal atau jam Out tidak valid.'])->withInput();
            }

            // Jika Out < In (dan tanggal_out tidak ditentukan eksplisit), tambah 1 hari untuk shift malam (lintas tengah malam)
            if (!$request->filled('tanggal_out') && $waktuOut->lt($waktuIn)) {
                $waktuOut = $waktuOut->copy()->addDay();
            }

            $now = now();
            if ($waktuIn->gt($now)) {
                return back()->withErrors(['jam_in' => 'Waktu In tidak boleh di masa depan.'])->withInput();
            }

            if ($waktuOut->gt($now)) {
                return back()->withErrors(['jam_out' => 'Waktu Out tidak boleh di masa depan.'])->withInput();
            }

            // Durasi minimal 1 menit (Out harus setelah In)
            $diffInMinutes = $waktuIn->diffInMinutes($waktuOut, false);
            if ($diffInMinutes < 1) {
                return back()->withErrors(['jam_out' => 'Waktu Out harus setelah waktu In (durasi minimal 1 menit).'])->withInput();
            }

            // Durasi maksimal 24 jam (1440 menit)
            if ($diffInMinutes > 1440) {
                return back()->withErrors(['jam_out' => 'Durasi rework tidak boleh lebih dari 24 jam.'])->withInput();
            }

            // Waktu In tidak boleh lebih dari 7 hari ke belakang
            if ($waktuIn->lt($now->copy()->subDays(7)->startOfDay())) {
                return back()->withErrors(['tanggal' => 'Waktu In tidak boleh lebih dari 7 hari ke belakang.'])->withInput();
            }

            $durasiMenit = (int) $diffInMinutes;
        } else {
            $now = now();
            $waktuIn = $now;
            $waktuOut = null;
            $durasiMenit = null;
            $shift = $request->filled('shift') ? $request->input('shift') : session('current_shift', '1A');
        }

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

        // Master area rework check: gunakan area yang dipilih jika ada, fallback ke default rework area
        $reworkArea = $request->filled('area_ditemukan')
            ? trim((string) $request->input('area_ditemukan'))
            : AreaDitemukan::getDefaultReworkAreaName();

        if (!$reworkArea) {
            return back()
                ->withErrors([
                    'area_ditemukan' => 'Area bertipe Rework belum diatur. Hubungi admin untuk menambahkannya di Master Data > Area Ditemukan.',
                ])
                ->withInput();
        }

        [$resolvedConveyor, $resolvedCarlineId, $resolvedJenisMobil] = $this->resolveConveyorAndCarline($request);

        $groupId = (string) Str::uuid();
        $userName = session('user_name');
        $nameplateBaru = ($isSusulan && $request->filled('nameplate_baru'))
            ? strtoupper(trim((string) $request->input('nameplate_baru')))
            : null;

        foreach ($validated['sub_defect_ids'] as $subDefectId) {
            $sub = $subDefects->get($subDefectId);
            if (!$sub) {
                continue;
            }

            Defect::create([
                'rework_group_id'      => $groupId,
                'waktu'                => $waktuIn,
                'waktu_in'             => $waktuIn,
                'waktu_out'            => $waktuOut,
                'durasi_rework_menit'  => $durasiMenit,
                'status_rework'        => $isSusulan ? 'CLOSE' : 'OPEN',
                'is_susulan'           => $isSusulan,
                'kanban_assy'          => $validated['kanban_assy'],
                'nameplate_lama'       => $validated['nameplate'],
                'nameplate_baru'       => $nameplateBaru,
                'no_defective_tag'     => $request->input('no_defective_tag') ?: null,
                'ujung'                => $request->input('ujung') ?: null,
                'status_4m'            => $request->input('status_4m') ?: 'NO',
                'area_ditemukan'       => $reworkArea,
                'posisi_detail_rework' => $request->input('posisi_detail_rework') ?: null,
                'fc_h'                 => $request->input('fc_h') ?: null,
                'fc_w'                 => $request->input('fc_w') ?: null,
                'rc_h'                 => $request->input('rc_h') ?: null,
                'rc_w'                 => $request->input('rc_w') ?: null,
                'no_box'               => $request->input('no_box') ?: null,
                'user_name'            => $userName,
                'shift'                => $shift,
                'jenis_assy'           => 'Final Assy',
                'quantity'             => 1,
                'defect_type_id'       => $sub->defect_type_id,
                'sub_defect_type_id'   => $sub->id,
                'jenis_defect'         => $sub->defectType?->name,
                'jenis_sub_defect'     => $sub->name,
                'conveyor'             => $resolvedConveyor,
                'carline_id'           => $resolvedCarlineId,
                'jenis_mobil'          => $resolvedJenisMobil,
            ]);
        }

        if ($isSusulan) {
            $msg = sprintf(
                'Rework susulan berhasil disimpan (CLOSE). Waktu In: %s, Out: %s, Durasi: %d menit.',
                $waktuIn->format('d/m/Y H:i'),
                $waktuOut->format('d/m/Y H:i'),
                $durasiMenit
            );
            return redirect()->route('rework.index')->with('success', $msg);
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
        if (in_array($defects->first()->status_rework, ['Selesai', 'CLOSE'], true)) {
            return redirect()->route('rework.index')->with('error', 'Harness ini sudah selesai di-rework.');
        }

        $validated = $request->validate([
            'nameplate_baru' => 'nullable|string|max:255',
        ]);

        $waktuOut = now();
        $waktuIn = $defects->first()->waktu_in ? Carbon::parse($defects->first()->waktu_in) : $waktuOut;
        $durasiMenit = (int) max(0, round($waktuIn->diffInSeconds($waktuOut) / 60));

        $updateData = [
            'waktu_out'           => $waktuOut,
            'status_rework'       => 'CLOSE',
            'durasi_rework_menit' => $durasiMenit,
        ];

        if ($request->filled('nameplate_baru')) {
            $updateData['nameplate_baru'] = strtoupper(trim((string) $request->input('nameplate_baru')));
        }

        Defect::where('rework_group_id', $groupId)->update($updateData);

        return redirect()->route('rework.index')->with('success', 'Rework harness berhasil diselesaikan (Out). Durasi: ' . $durasiMenit . ' menit.');
    }

    /**
     * Show edit form for a rework group.
     * Accessible by group owner or Administrator.
     */
    public function edit(Request $request, string $groupId)
    {
        $defects = Defect::where('rework_group_id', $groupId)
            ->with(['defectType', 'subDefectType', 'carline.carType'])
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

        $returnUrl = $isAdmin ? $this->sanitizeAdminReturnUrl($request->input('return_url')) : null;
        $areas = AreaDitemukan::orderBy('name')->get();
        $carTypes = CarType::with(['carlines' => fn($q) => $q->orderBy('name')])->orderBy('name')->get();

        return view('rework.edit', compact('defects', 'first', 'groupId', 'finalAssySubDefects', 'currentDefects', 'isAdmin', 'returnUrl', 'areas', 'carTypes'));
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
            'kanban_assy'          => 'required|string|max:255',
            'nameplate'            => 'required|string|max:255',
            'sub_defect_ids'       => 'required|array|min:1',
            'sub_defect_ids.*'     => 'required|integer|exists:sub_defect_types,id',
            'nameplate_baru'       => 'nullable|string|max:255',
            'no_defective_tag'     => 'nullable|string|max:255',
            'ujung'                => 'nullable|string|max:255',
            'status_4m'            => 'nullable|string|max:50',
            'area_ditemukan'       => 'nullable|string|max:255',
            'posisi_detail_rework' => 'nullable|string',
            'fc_h'                 => 'nullable|string|max:255',
            'fc_w'                 => 'nullable|string|max:255',
            'rc_h'                 => 'nullable|string|max:255',
            'rc_w'                 => 'nullable|string|max:255',
            'no_box'               => 'nullable|string|max:255',
            'shift'                => 'nullable|string|max:50',
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

        // Resolusi area rework deterministik: dari input jika filled, atau first area_ditemukan, atau default rework area
        $reworkArea = $request->filled('area_ditemukan')
            ? trim((string) $request->input('area_ditemukan'))
            : ($first->area_ditemukan ?: AreaDitemukan::getDefaultReworkAreaName());

        if (!$reworkArea) {
            return back()
                ->withErrors([
                    'area_ditemukan' => 'Area bertipe Rework belum diatur. Hubungi admin untuk menambahkannya di Master Data > Area Ditemukan.',
                ])
                ->withInput();
        }

        $hasConveyorOrCarlineInput = $request->has('conveyor') || $request->has('carline') || $request->has('carline_id') || $request->has('jenis_mobil');
        if ($hasConveyorOrCarlineInput) {
            [$resolvedConveyor, $resolvedCarlineId, $resolvedJenisMobil] = $this->resolveConveyorAndCarline($request);
        } else {
            $resolvedConveyor = $first->conveyor;
            $resolvedCarlineId = $first->carline_id;
            $resolvedJenisMobil = $first->jenis_mobil;
        }

        DB::transaction(function () use ($existingDefects, $groupId, $validated, $subDefects, $first, $reworkArea, $request, $resolvedConveyor, $resolvedCarlineId, $resolvedJenisMobil) {
            $kanban = $validated['kanban_assy'];
            $nameplate = $validated['nameplate'];
            $newSubIds = array_map('intval', $validated['sub_defect_ids']);

            $template = $first;
            $existingBySubId = $existingDefects->keyBy('sub_defect_type_id');
            $existingSubIds = $existingBySubId->keys()->map(fn($k) => (int) $k)->all();

            $sharedUpdates = [
                'kanban_assy'          => $kanban,
                'nameplate_lama'       => $nameplate,
                'area_ditemukan'       => $reworkArea,
                'nameplate_baru'       => $request->has('nameplate_baru') ? ($request->input('nameplate_baru') ?: null) : $template->nameplate_baru,
                'no_defective_tag'     => $request->has('no_defective_tag') ? ($request->input('no_defective_tag') ?: null) : $template->no_defective_tag,
                'ujung'                => $request->has('ujung') ? ($request->input('ujung') ?: null) : $template->ujung,
                'status_4m'            => $request->has('status_4m') ? ($request->input('status_4m') ?: 'NO') : ($template->status_4m ?: 'NO'),
                'posisi_detail_rework' => $request->has('posisi_detail_rework') ? ($request->input('posisi_detail_rework') ?: null) : $template->posisi_detail_rework,
                'fc_h'                 => $request->has('fc_h') ? ($request->input('fc_h') ?: null) : $template->fc_h,
                'fc_w'                 => $request->has('fc_w') ? ($request->input('fc_w') ?: null) : $template->fc_w,
                'rc_h'                 => $request->has('rc_h') ? ($request->input('rc_h') ?: null) : $template->rc_h,
                'rc_w'                 => $request->has('rc_w') ? ($request->input('rc_w') ?: null) : $template->rc_w,
                'no_box'               => $request->has('no_box') ? ($request->input('no_box') ?: null) : $template->no_box,
                'shift'                => $request->has('shift') ? ($request->input('shift') ?: $template->shift) : $template->shift,
                'conveyor'             => $resolvedConveyor,
                'carline_id'           => $resolvedCarlineId,
                'jenis_mobil'          => $resolvedJenisMobil,
            ];

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
                    $existingBySubId[$keepSubId]->update($sharedUpdates);
                }
            }

            // 3. Defect baru (ada di newSubIds tapi tidak ada di existing)
            $toAddSubIds = array_diff($newSubIds, $existingSubIds);
            foreach ($toAddSubIds as $addSubId) {
                $sub = $subDefects->get($addSubId);
                if (!$sub) {
                    continue;
                }

                Defect::create(array_merge($sharedUpdates, [
                    'rework_group_id'      => $groupId,
                    'waktu'                => $template->waktu,
                    'waktu_in'             => $template->waktu_in,
                    'waktu_out'            => $template->waktu_out,
                    'durasi_rework_menit'  => $template->durasi_rework_menit,
                    'status_rework'        => $template->status_rework,
                    'is_susulan'           => (bool) $template->is_susulan,
                    'user_name'            => $template->user_name, // Akun pembuat tetap pemilik asli
                    'jenis_assy'           => 'Final Assy',
                    'quantity'             => 1,
                    'defect_type_id'       => $sub->defect_type_id,
                    'sub_defect_type_id'   => $sub->id,
                    'jenis_defect'         => $sub->defectType?->name,
                    'jenis_sub_defect'     => $sub->name,
                ]));
            }
        });

        $returnUrl = $isAdmin ? $this->sanitizeAdminReturnUrl($request->input('return_url')) : null;

        if ($isAdmin) {
            if ($returnUrl) {
                return redirect()->to($returnUrl)->with('success', 'Riwayat rework harness berhasil diperbarui.');
            }
            return redirect()->route('admin.rework_history.index')->with('success', 'Riwayat rework harness berhasil diperbarui.');
        }

        return redirect()->route('rework.index')->with('success', 'Riwayat rework harness berhasil diperbarui.');
    }

    /**
     * Validate and sanitize return_url parameter for administrator.
     * Only accepts relative path strictly starting with '/admin/rework-history' (no scheme, no //, no host).
     */
    protected function sanitizeAdminReturnUrl(?string $returnUrl): ?string
    {
        if ($returnUrl === null || trim($returnUrl) === '') {
            return null;
        }

        $trimmed = trim($returnUrl);

        // Reject absolute URLs with schemes (e.g. http:, https:, javascript:, data:)
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $trimmed)) {
            return null;
        }

        // Reject protocol-relative '//' or Windows '\\'
        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '\\\\') || str_starts_with($trimmed, '/\\') || str_starts_with($trimmed, '\\/')) {
            return null;
        }

        // Must be a relative path strictly starting with '/admin/rework-history' or '/recent-defects'
        $allowedPrefixes = ['/admin/rework-history', '/recent-defects'];
        $matchedPrefix = null;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($trimmed, $prefix)) {
                $matchedPrefix = $prefix;
                break;
            }
        }

        if ($matchedPrefix === null) {
            return null;
        }

        // Must be exactly the allowed prefix, or followed by '?' or '/'
        $after = substr($trimmed, strlen($matchedPrefix));
        if ($after !== '' && !str_starts_with($after, '?') && !str_starts_with($after, '/')) {
            return null;
        }

        // Double check with parse_url that no host or scheme is present
        $parsed = parse_url($trimmed);
        if ($parsed === false || isset($parsed['scheme']) || isset($parsed['host'])) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Resolve and validate conveyor and carline selection.
     * Returns an array with [conveyor, carline_id, jenis_mobil] or throws ValidationException.
     *
     * @return array{0: ?string, 1: ?int, 2: ?string}
     * @throws ValidationException
     */
    protected function resolveConveyorAndCarline(Request $request): array
    {
        $rawConveyor = $request->input('conveyor');
        $rawCarline = $request->input('carline') ?: $request->input('jenis_mobil');
        $rawCarlineId = $request->input('carline_id');

        $conveyor = filled($rawConveyor) ? trim((string) $rawConveyor) : null;
        $carline = filled($rawCarline) ? trim((string) $rawCarline) : null;
        $carlineId = filled($rawCarlineId) ? (int) $rawCarlineId : null;

        // Jika tidak ada satu pun yang diisi
        if ($conveyor === null && $carline === null && $carlineId === null) {
            return [null, null, null];
        }

        // Jika carline_id diisi langsung
        if ($carlineId !== null) {
            $carlineRecord = Carline::with('carType')->find($carlineId);
            if (!$carlineRecord || $carlineRecord->car_type_id === null) {
                throw ValidationException::withMessages([
                    'carline_id' => 'Konveyor yang dipilih tidak valid untuk Final Assy.',
                ]);
            }
            if ($conveyor !== null && $carlineRecord->name !== $conveyor) {
                throw ValidationException::withMessages([
                    'conveyor' => 'Konveyor tidak sesuai dengan Jenis Mobil yang dipilih.',
                ]);
            }
            if ($carline !== null && $carlineRecord->carType?->name !== $carline) {
                throw ValidationException::withMessages([
                    'carline' => 'Jenis Mobil tidak sesuai dengan Konveyor yang dipilih.',
                ]);
            }
            return [$carlineRecord->name, $carlineRecord->id, $carlineRecord->carType?->name];
        }

        // Jika conveyor diisi
        if ($conveyor !== null) {
            $carlineQuery = Carline::whereNotNull('car_type_id')->where('name', $conveyor)->with('carType');
            if ($carline !== null) {
                $carlineQuery->whereHas('carType', function ($q) use ($carline) {
                    $q->where('name', $carline);
                });
            }
            $carlineRecord = $carlineQuery->first();

            if (!$carlineRecord) {
                if ($carline !== null) {
                    throw ValidationException::withMessages([
                        'conveyor' => 'Konveyor tidak sesuai dengan Jenis Mobil yang dipilih.',
                    ]);
                }
                throw ValidationException::withMessages([
                    'conveyor' => 'Konveyor tidak valid untuk Final Assy.',
                ]);
            }

            return [$carlineRecord->name, $carlineRecord->id, $carlineRecord->carType?->name];
        }

        // Jika hanya carline yang diisi (tanpa conveyor)
        if ($carline !== null) {
            $carTypeExists = CarType::where('name', $carline)->exists();
            if (!$carTypeExists) {
                throw ValidationException::withMessages([
                    'carline' => 'Jenis Mobil yang dipilih tidak valid.',
                ]);
            }
            return [null, null, $carline];
        }

        return [null, null, null];
    }
}
