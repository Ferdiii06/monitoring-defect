<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DefectType;
use App\Models\SubDefectType;

class DefectTypeController extends Controller
{
    public function index(Request $request)
    {
        $filterType = $request->input('type'); // Final Assy / Pre Assy

        $query = DefectType::with('subDefectTypes');
        if ($filterType) {
            $query->where('type', $filterType);
        }

        $defectTypes = $query->orderBy('type')->orderBy('name')->get();

        return view('master.defect_types', compact('defectTypes', 'filterType'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Final Assy,Pre Assy',
        ]);

        DefectType::create($validated);

        return redirect()->route('admin.master.defect_types.index', ['type' => $validated['type']])->with('success', 'Defect Type berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $defectType = DefectType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Final Assy,Pre Assy',
        ]);

        $defectType->update($validated);

        return redirect()->route('admin.master.defect_types.index', ['type' => $validated['type']])->with('success', 'Defect Type berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $defectType = DefectType::findOrFail($id);
        $type = $defectType->type;
        $defectType->delete();

        return redirect()->route('admin.master.defect_types.index', ['type' => $type])->with('success', 'Defect Type berhasil dihapus!');
    }

    public function storeSub(Request $request, $defectTypeId)
    {
        $defectType = DefectType::findOrFail($defectTypeId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $defectType->subDefectTypes()->create($validated);

        return redirect()->route('admin.master.defect_types.index', ['type' => $defectType->type])->with('success', 'Sub-Defect Type berhasil ditambahkan!');
    }

    public function destroySub($id)
    {
        $sub = SubDefectType::with('defectType')->findOrFail($id);
        $type = $sub->defectType->type ?? null;
        $sub->delete();

        return redirect()->route('admin.master.defect_types.index', ['type' => $type])->with('success', 'Sub-Defect Type berhasil dihapus!');
    }
}
