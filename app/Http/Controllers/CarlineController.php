<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarType;
use App\Models\Carline;

class CarlineController extends Controller
{
    public function index(Request $request)
    {
        $carTypes = CarType::orderBy('name')->get();
        $selectedCarType = $request->input('car_type_id');

        $query = Carline::with('carType');
        if ($selectedCarType) {
            $query->where('car_type_id', $selectedCarType);
        }

        $carlines = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('master.carlines', compact('carlines', 'carTypes', 'selectedCarType'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_type_id' => 'required|exists:car_types,id',
            'name' => 'required|string|max:255',
        ]);

        Carline::create($validated);

        return redirect()->route('admin.master.carlines.index')->with('success', 'Carline berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $carline = Carline::findOrFail($id);

        $validated = $request->validate([
            'car_type_id' => 'required|exists:car_types,id',
            'name' => 'required|string|max:255',
        ]);

        $carline->update($validated);

        return redirect()->route('admin.master.carlines.index')->with('success', 'Carline berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $carline = Carline::findOrFail($id);
        $carline->delete();

        return redirect()->route('admin.master.carlines.index')->with('success', 'Carline berhasil dihapus!');
    }
}
