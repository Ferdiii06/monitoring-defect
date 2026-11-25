<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarType;

class CarTypeController extends Controller
{
    public function index()
    {
        $carTypes = CarType::withCount('carlines')->orderBy('name')->get();
        return view('master.car_types', compact('carTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'scan_format' => 'nullable|string|in:assy_family,family_assy',
        ]);

        $name = trim($validated['name']);
        $exists = CarType::whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])->exists();
        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => "Jenis mobil '{$name}' sudah terdaftar.",
            ]);
        }

        $validated['name'] = $name;
        $validated['scan_format'] = filled($validated['scan_format'] ?? null) ? $validated['scan_format'] : null;

        CarType::create($validated);

        return redirect()->route('admin.master.car_types.index')->with('success', 'Jenis Mobil berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $carType = CarType::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'scan_format' => 'nullable|string|in:assy_family,family_assy',
        ]);

        $name = trim($validated['name']);
        $exists = CarType::where('id', '!=', $id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])
            ->exists();
        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => "Jenis mobil '{$name}' sudah terdaftar.",
            ]);
        }

        $validated['name'] = $name;
        $validated['scan_format'] = filled($validated['scan_format'] ?? null) ? $validated['scan_format'] : null;

        $carType->update($validated);

        return redirect()->route('admin.master.car_types.index')->with('success', 'Jenis Mobil berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $carType = CarType::findOrFail($id);
        $carType->delete();

        return redirect()->route('admin.master.car_types.index')->with('success', 'Jenis Mobil berhasil dihapus!');
    }
}
