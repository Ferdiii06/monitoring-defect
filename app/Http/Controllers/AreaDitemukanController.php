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
        $validated = $request->validate([
            'name'            => 'required|string|max:255|unique:area_ditemukans,name',
            'tipe'            => ['nullable', Rule::in([
                AreaDitemukan::TIPE_INTERNAL_DEFECT,
                AreaDitemukan::TIPE_REWORK,
                AreaDitemukan::TIPE_INSPEKSI,
                AreaDitemukan::TIPE_ASSEMBLY,
                AreaDitemukan::TIPE_BELUM_DIKLASIFIKASI,
            ])],
            'kategori_report' => ['nullable', Rule::in([
                AreaDitemukan::REPORT_BELUM_DIKLASIFIKASI,
                AreaDitemukan::REPORT_INSPEKSI,
                AreaDitemukan::REPORT_ASSEMBLY,
                AreaDitemukan::REPORT_REWORK,
            ])],
        ]);

        $inputTipe = $validated['tipe'] ?? AreaDitemukan::TIPE_INTERNAL_DEFECT;
        $inputKat  = $validated['kategori_report'] ?? null;

        if ($inputKat === null) {
            if (in_array($inputTipe, [AreaDitemukan::TIPE_INSPEKSI, AreaDitemukan::TIPE_ASSEMBLY, AreaDitemukan::TIPE_BELUM_DIKLASIFIKASI], true)) {
                $inputKat = $inputTipe;
                $inputTipe = AreaDitemukan::TIPE_INTERNAL_DEFECT;
            } elseif ($inputTipe === AreaDitemukan::TIPE_REWORK) {
                $inputKat = AreaDitemukan::REPORT_REWORK;
            } else {
                $inputKat = AreaDitemukan::REPORT_BELUM_DIKLASIFIKASI;
            }
        }

        $validated['tipe'] = $inputTipe;
        $validated['kategori_report'] = $inputKat;

        AreaDitemukan::create($validated);

        return redirect()->route('admin.master.area_ditemukans.index')->with('success', 'Area Ditemukan berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $area = AreaDitemukan::findOrFail($id);
        $defectsCount = Defect::where('area_ditemukan', $area->name)->count();

        $validated = $request->validate([
            'name'            => 'required|string|max:255|unique:area_ditemukans,name,' . $id,
            'tipe'            => ['nullable', Rule::in([
                AreaDitemukan::TIPE_INTERNAL_DEFECT,
                AreaDitemukan::TIPE_REWORK,
                AreaDitemukan::TIPE_INSPEKSI,
                AreaDitemukan::TIPE_ASSEMBLY,
                AreaDitemukan::TIPE_BELUM_DIKLASIFIKASI,
            ])],
            'kategori_report' => ['nullable', Rule::in([
                AreaDitemukan::REPORT_BELUM_DIKLASIFIKASI,
                AreaDitemukan::REPORT_INSPEKSI,
                AreaDitemukan::REPORT_ASSEMBLY,
                AreaDitemukan::REPORT_REWORK,
            ])],
        ]);

        $inputTipe = $validated['tipe'] ?? $area->tipe;
        $inputKat  = $validated['kategori_report'] ?? null;

        if ($inputKat === null) {
            if (in_array($inputTipe, [AreaDitemukan::TIPE_INSPEKSI, AreaDitemukan::TIPE_ASSEMBLY, AreaDitemukan::TIPE_BELUM_DIKLASIFIKASI], true)) {
                $inputKat = $inputTipe;
                $inputTipe = AreaDitemukan::TIPE_INTERNAL_DEFECT;
            } elseif ($inputTipe === AreaDitemukan::TIPE_REWORK) {
                $inputKat = AreaDitemukan::REPORT_REWORK;
            } else {
                $inputKat = $area->kategori_report ?? AreaDitemukan::REPORT_BELUM_DIKLASIFIKASI;
            }
        }

        $validated['tipe'] = $inputTipe;
        $validated['kategori_report'] = $inputKat;

        if ($defectsCount > 0 && trim($validated['name']) !== $area->name) {
            return redirect()->back()->withErrors([
                'name' => 'Nama area tidak dapat diubah karena sudah digunakan pada ' . $defectsCount . ' data defect.',
            ]);
        }

        if ($defectsCount > 0) {
            $validated['name'] = $area->name;
        }

        $area->update($validated);

        return redirect()->route('admin.master.area_ditemukans.index')->with('success', 'Area Ditemukan berhasil diperbarui!');
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
