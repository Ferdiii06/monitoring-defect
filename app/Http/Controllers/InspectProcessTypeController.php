<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InspectProcessType;

class InspectProcessTypeController extends Controller
{
    public function index()
    {
        $processes = InspectProcessType::orderBy('name')->get();
        return view('master.inspect_process_types', compact('processes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inspect_process_types,name',
        ]);

        InspectProcessType::create($validated);

        return redirect()->route('admin.master.inspect_process_types.index')->with('success', 'Jenis Inspect Proses berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $process = InspectProcessType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inspect_process_types,name,' . $id,
        ]);

        $process->update($validated);

        return redirect()->route('admin.master.inspect_process_types.index')->with('success', 'Jenis Inspect Proses berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $process = InspectProcessType::findOrFail($id);
        $process->delete();

        return redirect()->route('admin.master.inspect_process_types.index')->with('success', 'Jenis Inspect Proses berhasil dihapus!');
    }
}
