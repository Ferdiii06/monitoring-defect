<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\InspectProcessType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AreaDitemukanController extends Controller
{
    public function index(Request $request)
    {
        $kategori = $request->query('kategori');

        $query = AreaDitemukan::orderBy('name');
        if ($kategori && in_array($kategori, [AreaDitemukan::KATEGORI_FINAL_ASSY, AreaDitemukan::KATEGORI_PRE_ASSY], true)) {
            $query->where('kategori', $kategori);
        }

        $areas = $query->get();

        $defectCounts = Defect::whereNotNull('area_ditemukan')
            ->where('area_ditemukan', '!=', '')
            ->selectRaw('area_ditemukan, jenis_assy, count(*) as total')
            ->groupBy('area_ditemukan', 'jenis_assy')
            ->get();

        $countsMap = [];
        foreach ($defectCounts as $row) {
            $countsMap[$row->area_ditemukan . '|' . $row->jenis_assy] = (int) $row->total;
        }

        // Hitung juga data lama Pre Assy yang hanya punya inspect_process_type_id
        $legacyPreCounts = Defect::where('jenis_assy', AreaDitemukan::KATEGORI_PRE_ASSY)
            ->where(function ($q) {
                $q->whereNull('area_ditemukan')->orWhere('area_ditemukan', '');
            })
            ->whereNotNull('inspect_process_type_id')
            ->with('inspectProcessType')
            ->get()
            ->groupBy(fn ($d) => $d->inspectProcessType?->name)
            ->map->count();

        foreach ($areas as $area) {
            $count = $countsMap[$area->name . '|' . $area->kategori] ?? 0;
            if ($area->kategori === AreaDitemukan::KATEGORI_PRE_ASSY) {
                $count += (int) ($legacyPreCounts->get($area->name) ?? 0);
            }
            $area->defects_count = $count;
        }

        return view('master.area_ditemukans', compact('areas', 'kategori'));
    }

    public function store(Request $request)
    {
        $data = $this->validateAreaInput($request);

        AreaDitemukan::create($data);

        $redirectParams = $request->query('kategori') ? ['kategori' => $request->query('kategori')] : [];
        return redirect()->route('admin.master.area_ditemukans.index', $redirectParams)
            ->with('success', 'Area Ditemukan berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $area = AreaDitemukan::findOrFail($id);
        $data = $this->validateAreaInput($request, (int) $id);

        $oldName = $area->name;
        $newName = trim($data['name']);
        $isRenamed = ($newName !== $oldName);

        DB::transaction(function () use ($area, $data, $oldName, $newName, $isRenamed) {
            if ($isRenamed) {
                // Update defects di kategori yang sama
                Defect::where('area_ditemukan', $oldName)
                    ->where('jenis_assy', $area->kategori)
                    ->update(['area_ditemukan' => $newName]);
            }

            $area->update($data);
        });

        $redirectParams = $request->query('kategori') ? ['kategori' => $request->query('kategori')] : [];
        return redirect()->route('admin.master.area_ditemukans.index', $redirectParams)
            ->with('success', 'Area Ditemukan berhasil diperbarui!');
    }

    /**
     * Validasi Kategori Area dan Tipe Area (Batch 1: Tipe hanya assembly_defect / internal_defect).
     */
    private function validateAreaInput(Request $request, ?int $id = null): array
    {
        $allowedKategori = [
            AreaDitemukan::KATEGORI_FINAL_ASSY,
            AreaDitemukan::KATEGORI_PRE_ASSY,
        ];

        $allowedTipe = [
            AreaDitemukan::TIPE_INTERNAL_DEFECT,
            AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ];

        $kategoriInput = $request->input('kategori', AreaDitemukan::KATEGORI_FINAL_ASSY);
        $isPreAssy = ($kategoriInput === AreaDitemukan::KATEGORI_PRE_ASSY);

        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', Rule::in($allowedKategori)],
            'tipe'     => $isPreAssy
                ? ['nullable', 'string', Rule::in($allowedTipe)]
                : ['required', 'string', Rule::in($allowedTipe)],
        ];

        $messages = [
            'name.required' => 'Nama area wajib diisi.',
            'name.max'      => 'Nama area maksimal 255 karakter.',
            'kategori.in'   => 'Kategori area harus Final Assy atau Pre Assy.',
            'tipe.required' => 'Tipe area wajib dipilih untuk Final Assy.',
            'tipe.in'       => 'Tipe area yang dipilih tidak valid. Pilih Internal Defect atau Assembly Defect.',
        ];

        $validated = $request->validate($rules, $messages);

        $name = trim($validated['name']);
        $kategori = $validated['kategori'] ?? AreaDitemukan::KATEGORI_FINAL_ASSY;

        $duplicateQuery = AreaDitemukan::where('kategori', $kategori)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')]);
        if ($id) {
            $duplicateQuery->where('id', '!=', $id);
        }
        if ($duplicateQuery->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'Nama area sudah digunakan pada kategori ' . $kategori . '.',
            ]);
        }

        // Pre Assy: tipe tidak dipakai dan selalu diset internal_defect agar konsisten di DB
        if ($kategori === AreaDitemukan::KATEGORI_PRE_ASSY) {
            $tipe = AreaDitemukan::TIPE_INTERNAL_DEFECT;
        } else {
            // Final Assy: tipe wajib dipilih oleh admin
            $tipe = $validated['tipe'];
        }

        return [
            'name'     => trim($validated['name']),
            'kategori' => $kategori,
            'tipe'     => $tipe,
        ];
    }

    public function destroy(Request $request, $id)
    {
        $area = AreaDitemukan::findOrFail($id);
        $defectsCount = Defect::where('area_ditemukan', $area->name)
            ->where('jenis_assy', $area->kategori)
            ->count();

        if ($area->kategori === AreaDitemukan::KATEGORI_PRE_ASSY) {
            $legacyProcessIds = InspectProcessType::where('name', $area->name)->pluck('id');
            if ($legacyProcessIds->isNotEmpty()) {
                $legacyCount = Defect::where('jenis_assy', AreaDitemukan::KATEGORI_PRE_ASSY)
                    ->where(function ($q) {
                        $q->whereNull('area_ditemukan')->orWhere('area_ditemukan', '');
                    })
                    ->whereIn('inspect_process_type_id', $legacyProcessIds)
                    ->count();
                $defectsCount += $legacyCount;
            }
        }

        if ($defectsCount > 0) {
            return redirect()->back()->withErrors([
                'error' => 'Area ditemukan tidak dapat dihapus karena sudah digunakan pada ' . $defectsCount . ' data defect ' . $area->kategori . '. Silakan gunakan tombol Nonaktifkan.',
            ]);
        }

        $area->delete();

        $redirectParams = $request->query('kategori') ? ['kategori' => $request->query('kategori')] : [];
        return redirect()->route('admin.master.area_ditemukans.index', $redirectParams)
            ->with('success', 'Area Ditemukan berhasil dihapus!');
    }

    public function toggleStatus(Request $request, $id)
    {
        $area = AreaDitemukan::findOrFail($id);
        $area->is_active = !$area->is_active;
        $area->save();

        $redirectParams = $request->query('kategori') ? ['kategori' => $request->query('kategori')] : [];
        $statusText = $area->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.master.area_ditemukans.index', $redirectParams)
            ->with('success', "Area Ditemukan \"{$area->name}\" berhasil {$statusText}!");
    }
}
