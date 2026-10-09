<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AreaDitemukan;

class AreaDitemukanController extends Controller
{
    public function index()
    {
        $areas = AreaDitemukan::orderBy('name')->get();
        return view('master.area_ditemukans', compact('areas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:area_ditemukans,name',
        ]);

        AreaDitemukan::create($validated);

        return redirect()->route('admin.master.area_ditemukans.index')->with('success', 'Area Ditemukan berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $area = AreaDitemukan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:area_ditemukans,name,' . $id,
        ]);

        $area->update($validated);

        return redirect()->route('admin.master.area_ditemukans.index')->with('success', 'Area Ditemukan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $area = AreaDitemukan::findOrFail($id);
        $area->delete();

        return redirect()->route('admin.master.area_ditemukans.index')->with('success', 'Area Ditemukan berhasil dihapus!');
    }
}
