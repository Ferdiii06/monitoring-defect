<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DpmTarget;
use App\Models\Carline;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DpmTargetController extends Controller
{
    public function index()
    {
        $finalAssyTargets = DpmTarget::with('carline')
            ->where('jenis_assy', 'Final Assy')
            ->orderBy('period_start', 'desc')
            ->get();

        $preAssyTargets = DpmTarget::with('carline')
            ->where('jenis_assy', 'Pre Assy')
            ->orderBy('period_start', 'desc')
            ->get();

        $carlines = Carline::with('carType')->orderBy('name')->get();

        return view('master.dpm_targets', compact('finalAssyTargets', 'preAssyTargets', 'carlines'));
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
        if ($request->has('carline_id') && ($request->input('carline_id') === '' || $request->input('carline_id') === null)) {
            $request->merge(['carline_id' => null]);
        }

        $validator = Validator::make($request->all(), [
            'jenis_assy'   => 'required|string|in:Final Assy,Pre Assy',
            'carline_id'   => 'nullable|exists:carlines,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'target_dpm'   => 'required|numeric|min:0',
        ], [
            'jenis_assy.required'        => 'Jenis Assy wajib dipilih.',
            'jenis_assy.in'              => 'Jenis Assy harus berupa Final Assy atau Pre Assy.',
            'carline_id.exists'          => 'Carline yang dipilih tidak valid.',
            'period_start.required'      => 'Tanggal mulai periode wajib diisi.',
            'period_start.date'          => 'Format tanggal mulai tidak valid.',
            'period_end.required'        => 'Tanggal akhir periode wajib diisi.',
            'period_end.date'            => 'Format tanggal akhir tidak valid.',
            'period_end.after_or_equal'  => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
            'target_dpm.required'        => 'Target DPM wajib diisi.',
            'target_dpm.numeric'         => 'Target DPM harus berupa angka.',
            'target_dpm.min'             => 'Target DPM tidak boleh bernilai negatif.',
        ]);

        $validator->after(function ($validator) use ($request, $ignoreId) {
            $jenisAssy = $request->input('jenis_assy');
            $carlineId = $request->input('carline_id') ? (int) $request->input('carline_id') : null;
            $start = $request->input('period_start');
            $end = $request->input('period_end');

            if (!$jenisAssy || !$start || !$end) {
                return;
            }

            if ($start > $end) {
                return;
            }

            $conflictingQuery = DpmTarget::where('jenis_assy', $jenisAssy)
                ->where('period_start', '<=', $end)
                ->where('period_end', '>=', $start)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId));

            if ($carlineId) {
                $conflictingQuery->where('carline_id', $carlineId);
            } else {
                $conflictingQuery->whereNull('carline_id');
            }

            $conflicting = $conflictingQuery->with('carline')->first();

            if ($conflicting) {
                $startFmt = Carbon::parse($conflicting->period_start)->translatedFormat('d M Y');
                $endFmt = Carbon::parse($conflicting->period_end)->translatedFormat('d M Y');
                $scopeText = $conflicting->carline ? "Carline {$conflicting->carline->name}" : "Semua Carline";
                $validator->errors()->add(
                    'period_start',
                    "Rentang tanggal bertabrakan dengan target {$conflicting->jenis_assy} ({$scopeText}) yang sudah ada ({$startFmt} s/d {$endFmt})."
                );
            }
        });

        return $validator->validate();
    }
}
