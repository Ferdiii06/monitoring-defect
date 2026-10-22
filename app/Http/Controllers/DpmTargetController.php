<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DpmTarget;
use App\Models\Carline;
use App\Support\ConveyorGroup;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DpmTargetController extends Controller
{
    public function index()
    {
        $finalAssyTargets = DpmTarget::where('jenis_assy', 'Final Assy')
            ->orderBy('period_start', 'desc')
            ->get();

        $preAssyTargets = DpmTarget::where('jenis_assy', 'Pre Assy')
            ->with('carline')
            ->orderBy('period_start', 'desc')
            ->get();

        $finalAssyConveyors = ConveyorGroup::availableGroups();
        $preAssyCarlines = Carline::whereNull('car_type_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('master.dpm_targets', compact(
            'finalAssyTargets',
            'preAssyTargets',
            'finalAssyConveyors',
            'preAssyCarlines'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateDpmTarget($request);

        DpmTarget::create($validated);

        return redirect()->route('admin.master.dpm_targets.index')
            ->with('success', 'Target DPM berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $target = DpmTarget::findOrFail($id);

        $validated = $this->validateDpmTarget($request, $target->id);

        $target->update($validated);

        return redirect()->route('admin.master.dpm_targets.index')
            ->with('success', 'Target DPM berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $target = DpmTarget::findOrFail($id);
        $target->delete();

        return redirect()->route('admin.master.dpm_targets.index')
            ->with('success', 'Target DPM berhasil dihapus!');
    }

    protected function validateDpmTarget(Request $request, ?int $ignoreId = null): array
    {
        $rawCarlineId = $request->input('carline_id');
        $carlineId = ($rawCarlineId !== '' && $rawCarlineId !== null) ? (int) $rawCarlineId : null;

        $rawConveyorGroup = $request->input('conveyor_group');
        $conveyorGroup = ($rawConveyorGroup !== '' && $rawConveyorGroup !== null) ? trim((string) $rawConveyorGroup) : null;

        $validator = Validator::make($request->all(), [
            'jenis_assy'   => 'required|string|in:Final Assy,Pre Assy',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'target_dpm'   => 'required|numeric|min:0',
        ], [
            'jenis_assy.required'        => 'Jenis Assy wajib dipilih.',
            'jenis_assy.in'              => 'Jenis Assy harus berupa Final Assy atau Pre Assy.',
            'period_start.required'      => 'Tanggal mulai periode wajib diisi.',
            'period_start.date'          => 'Format tanggal mulai tidak valid.',
            'period_end.required'        => 'Tanggal akhir periode wajib diisi.',
            'period_end.date'            => 'Format tanggal akhir tidak valid.',
            'period_end.after_or_equal'  => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
            'target_dpm.required'        => 'Target DPM wajib diisi.',
            'target_dpm.numeric'         => 'Target DPM harus berupa angka.',
            'target_dpm.min'             => 'Target DPM tidak boleh bernilai negatif.',
        ]);

        $validator->after(function ($validator) use ($request, $ignoreId, $carlineId, $conveyorGroup) {
            $jenisAssy = $request->input('jenis_assy');
            $start = $request->input('period_start');
            $end = $request->input('period_end');

            if (!$jenisAssy || !$start || !$end) {
                return;
            }

            if ($start > $end) {
                return;
            }

            // Cross validation per jenis assy
            if ($jenisAssy === 'Final Assy') {
                if ($carlineId !== null) {
                    $validator->errors()->add('carline_id', 'Carline tidak boleh diisi untuk Final Assy.');
                    return;
                }
                if ($conveyorGroup !== null && !in_array($conveyorGroup, ConveyorGroup::availableGroups(), true)) {
                    $validator->errors()->add('conveyor_group', 'Grup konveyor tidak valid.');
                    return;
                }
            } elseif ($jenisAssy === 'Pre Assy') {
                if ($conveyorGroup !== null) {
                    $validator->errors()->add('conveyor_group', 'Grup konveyor tidak boleh diisi untuk Pre Assy.');
                    return;
                }
                if ($carlineId !== null) {
                    $isValidCarline = Carline::whereNull('car_type_id')->where('id', $carlineId)->exists();
                    if (!$isValidCarline) {
                        $validator->errors()->add('carline_id', 'Carline tidak valid untuk Pre Assy.');
                        return;
                    }
                }
            }

            // Overlap check per (jenis_assy, carline_id, conveyor_group)
            $conflictingQuery = DpmTarget::where('jenis_assy', $jenisAssy)
                ->where('period_start', '<=', $end)
                ->where('period_end', '>=', $start)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId));

            if ($carlineId !== null) {
                $conflictingQuery->where('carline_id', $carlineId);
            } else {
                $conflictingQuery->whereNull('carline_id');
            }

            if ($conveyorGroup !== null) {
                $conflictingQuery->where('conveyor_group', $conveyorGroup);
            } else {
                $conflictingQuery->whereNull('conveyor_group');
            }

            $conflicting = $conflictingQuery->with('carline')->first();

            if ($conflicting) {
                $startFmt = Carbon::parse($conflicting->period_start)->translatedFormat('d M Y');
                $endFmt = Carbon::parse($conflicting->period_end)->translatedFormat('d M Y');
                $scopeLabel = $conveyorGroup
                    ? " (Konveyor {$conveyorGroup})"
                    : ($conflicting->carline ? " (Carline {$conflicting->carline->name})" : " (Default)");

                $validator->errors()->add(
                    'period_start',
                    "Rentang tanggal bertabrakan dengan target {$conflicting->jenis_assy}{$scopeLabel} yang sudah ada ({$startFmt} s/d {$endFmt})."
                );
            }
        });

        $validated = $validator->validate();

        return [
            'jenis_assy'     => $validated['jenis_assy'],
            'period_start'   => $validated['period_start'],
            'period_end'     => $validated['period_end'],
            'target_dpm'     => $validated['target_dpm'],
            'carline_id'     => $carlineId,
            'conveyor_group' => $conveyorGroup,
        ];
    }
}
