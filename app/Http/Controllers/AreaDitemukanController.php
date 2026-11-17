<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AreaDitemukan;
use App\Models\Defect;
use Illuminate\Validation\Rule;

class AreaDitemukanController extends Controller
{
    public function index()
    {
        $areas = AreaDitemukan::orderBy('name')->get();

        $defectCounts = Defect::whereNotNull('area_ditemukan')
            ->where('area_ditemukan', '!=', '')
            ->selectRaw('area_ditemukan, count(*) as total')
            ->groupBy('area_ditemukan')
            ->pluck('total', 'area_ditemukan');

        foreach ($areas as $area) {
            $area->defects_count = (int) $defectCounts->get($area->name, 0);
        }

        return view('master.area_ditemukans', compact('areas'));
    }

    public function store(Request $request)
    {
        $data = $this->validateAreaInput($request);

        AreaDitemukan::create($data);

        return redirect()->route('admin.master.area_ditemukans.index')->with('success', 'Area Ditemukan berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $area = AreaDitemukan::findOrFail($id);
        $defectsCount = Defect::where('area_ditemukan', $area->name)->count();

        $data = $this->validateAreaInput($request, (int) $id);

        if ($defectsCount > 0 && trim($data['name']) !== $area->name) {
            return redirect()->back()->withInput()->withErrors([
                'name' => 'Nama area tidak dapat diubah karena sudah digunakan pada ' . $defectsCount . ' data defect.',
            ]);
        }

        if ($defectsCount > 0) {
            $data['name'] = $area->name;
        }

        $area->update($data);

        return redirect()->route('admin.master.area_ditemukans.index')->with('success', 'Area Ditemukan berhasil diperbarui!');
    }

    /**
     * Validasi Kategori Area dan Tipe Area (Batch 1: Tipe hanya assembly_defect / internal_defect).
     */
    private function validateAreaInput(Request $request, ?int $id = null): array
    {
        $uniqueRule = $id ? 'unique:area_ditemukans,name,' . $id : 'unique:area_ditemukans,name';

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
            'name'     => ['required', 'string', 'max:255', $uniqueRule],
            'kategori' => ['nullable', 'string', Rule::in($allowedKategori)],
            'tipe'     => $isPreAssy
                ? ['nullable', 'string', Rule::in($allowedTipe)]
                : ['required', 'string', Rule::in($allowedTipe)],
        ];

        $messages = [
            'name.required' => 'Nama area wajib diisi.',
            'name.unique'   => 'Nama area sudah digunakan.',
            'name.max'      => 'Nama area maksimal 255 karakter.',
            'kategori.in'   => 'Kategori area harus Final Assy atau Pre Assy.',
            'tipe.required' => 'Tipe area wajib dipilih untuk Final Assy.',
            'tipe.in'       => 'Tipe area yang dipilih tidak valid. Pilih Internal Defect atau Assembly Defect.',
        ];

        $validated = $request->validate($rules, $messages);

        $kategori = $validated['kategori'] ?? AreaDitemukan::KATEGORI_FINAL_ASSY;

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

    public function destroy($id)
    {
        $area = AreaDitemukan::findOrFail($id);
        $defectsCount = Defect::where('area_ditemukan', $area->name)->count();

        if ($defectsCount > 0) {
            return redirect()->back()->withErrors([
                'error' => 'Area ditemukan tidak dapat dihapus karena sudah digunakan pada ' . $defectsCount . ' data defect.',
            ]);
        }

        $area->delete();

        return redirect()->route('admin.master.area_ditemukans.index')->with('success', 'Area Ditemukan berhasil dihapus!');
    }
}
