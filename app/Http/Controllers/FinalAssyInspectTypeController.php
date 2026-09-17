<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinalAssyInspectType;

class FinalAssyInspectTypeController extends Controller
{
    public function index()
    {
        $types = FinalAssyInspectType::orderBy('name')->get();
        return view('master.final_assy_inspect_types', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:final_assy_inspect_types,name',
        ]);

        FinalAssyInspectType::create($validated);

        return redirect()->route('admin.master.final_assy_inspect_types.index')->with('success', 'Jenis Final Assy Inspect berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $type = FinalAssyInspectType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:final_assy_inspect_types,name,' . $id,
        ]);

        $type->update($validated);

        return redirect()->route('admin.master.final_assy_inspect_types.index')->with('success', 'Jenis Final Assy Inspect berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $type = FinalAssyInspectType::findOrFail($id);
        $type->delete();

        return redirect()->route('admin.master.final_assy_inspect_types.index')->with('success', 'Jenis Final Assy Inspect berhasil dihapus!');
    }
}
