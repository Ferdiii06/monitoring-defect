<?php

namespace App\Http\Controllers;

use App\Models\AssyNumber;
use App\Models\Carline;
use App\Models\CarType;
use App\Models\Defect;
use App\Models\DpmTarget;
use App\Models\PreAssyMachine;
use Illuminate\Http\Request;

class CarlineController extends Controller
{
    public function index(Request $request)
    {
        $carTypes = CarType::orderBy('name')->get();
        $selectedCarType = $request->input('car_type_id');
        $selectedArea = $request->input('area', 'all');

        $query = Carline::with(['carType', 'machines']);

        if ($selectedArea === 'Pre Assy') {
            $query->preAssy();
        } elseif ($selectedArea === 'Final Assy') {
            $query->finalAssy();
        }

        if ($selectedCarType && $selectedArea !== 'Pre Assy') {
            $query->where('car_type_id', $selectedCarType);
        }

        $carlines = $query->orderBy('name')->paginate(20)->withQueryString();

        $usedCarlineIds = Defect::whereNotNull('carline_id')->pluck('carline_id')
            ->merge(PreAssyMachine::pluck('carline_id'))
            ->merge(DpmTarget::whereNotNull('carline_id')->pluck('carline_id'))
            ->merge(AssyNumber::whereNotNull('carline_id')->pluck('carline_id'))
            ->unique()
            ->all();

        return view('master.carlines', compact(
            'carlines',
            'carTypes',
            'selectedCarType',
            'selectedArea',
            'usedCarlineIds'
        ));
    }

    public function store(Request $request)
    {
        $jenisAssy = $request->input('jenis_assy', Carline::AREA_FINAL_ASSY);

        $rules = [
            'jenis_assy' => 'required|in:' . Carline::AREA_FINAL_ASSY . ',' . Carline::AREA_PRE_ASSY,
            'name'       => 'required|string|max:255',
        ];

        if ($jenisAssy === Carline::AREA_FINAL_ASSY) {
            $rules['car_type_id'] = 'required|exists:car_types,id';
        } else {
            $rules['car_type_id'] = 'nullable';
        }

        $validated = $request->validate($rules, [
            'car_type_id.required' => 'Jenis mobil wajib dipilih untuk area Final Assy.',
        ]);

        $name = trim($validated['name']);

        // Nama unik per jenis_assy (case-insensitive)
        $exists = Carline::where('jenis_assy', $jenisAssy)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => "Nama carline/konveyor '{$name}' sudah digunakan untuk area {$jenisAssy}."]);
        }

        Carline::create([
            'name'        => $name,
            'jenis_assy'  => $jenisAssy,
            'car_type_id' => ($jenisAssy === Carline::AREA_FINAL_ASSY) ? $validated['car_type_id'] : null,
        ]);

        return redirect()->route('admin.master.carlines.index', ['area' => $jenisAssy])
            ->with('success', 'Carline berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $carline = Carline::findOrFail($id);

        $newJenisAssy = $request->input('jenis_assy', $carline->jenis_assy);

        $rules = [
            'jenis_assy' => 'required|in:' . Carline::AREA_FINAL_ASSY . ',' . Carline::AREA_PRE_ASSY,
            'name'       => 'required|string|max:255',
        ];

        if ($newJenisAssy === Carline::AREA_FINAL_ASSY) {
            $rules['car_type_id'] = 'required|exists:car_types,id';
        } else {
            $rules['car_type_id'] = 'nullable';
        }

        $validated = $request->validate($rules, [
            'car_type_id.required' => 'Jenis mobil wajib dipilih untuk area Final Assy.',
        ]);

        // Edit: jenis_assy TIDAK boleh diubah jika carline sudah dipakai di defects, pre_assy_machines, dpm_targets, atau assy_numbers
        if ($newJenisAssy !== $carline->jenis_assy) {
            $isUsedInDefect = Defect::where('carline_id', $carline->id)->orWhere('conveyor', $carline->name)->exists();
            $isUsedInMachine = PreAssyMachine::where('carline_id', $carline->id)->exists();
            $isUsedInTarget = DpmTarget::where('carline_id', $carline->id)->exists();
            $isUsedInAssy = AssyNumber::where('carline_id', $carline->id)->exists();

            if ($isUsedInDefect || $isUsedInMachine || $isUsedInTarget || $isUsedInAssy) {
                $used = [];
                if ($isUsedInDefect) $used[] = 'data defect';
                if ($isUsedInMachine) $used[] = 'data nomor mesin';
                if ($isUsedInTarget) $used[] = 'target DPM';
                if ($isUsedInAssy) $used[] = 'data assy number';

                return redirect()->back()
                    ->withInput()
                    ->withErrors(['jenis_assy' => "Area carline tidak dapat diubah karena carline '{$carline->name}' sudah digunakan pada " . implode(', ', $used) . "."]);
            }
        }

        $name = trim($validated['name']);

        // Nama unik per jenis_assy (case-insensitive, abaikan diri sendiri)
        $exists = Carline::where('jenis_assy', $newJenisAssy)
            ->where('id', '!=', $carline->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => "Nama carline/konveyor '{$name}' sudah digunakan untuk area {$newJenisAssy}."]);
        }

        $carline->update([
            'name'        => $name,
            'jenis_assy'  => $newJenisAssy,
            'car_type_id' => ($newJenisAssy === Carline::AREA_FINAL_ASSY) ? $validated['car_type_id'] : null,
        ]);

        return redirect()->route('admin.master.carlines.index', ['area' => $newJenisAssy])
            ->with('success', 'Carline berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $carline = Carline::findOrFail($id);

        $isUsedInDefect = Defect::where('carline_id', $carline->id)
            ->orWhere('conveyor', $carline->name)
            ->exists();

        if ($isUsedInDefect) {
            return redirect()->back()->withErrors([
                'error' => "Carline/Konveyor '{$carline->name}' tidak dapat dihapus karena masih digunakan di data defect."
            ]);
        }

        $isUsedInTarget = DpmTarget::where('carline_id', $carline->id)->exists();

        if ($isUsedInTarget) {
            return redirect()->back()->withErrors([
                'error' => "Carline/Konveyor '{$carline->name}' tidak dapat dihapus karena masih digunakan di target DPM/PPM."
            ]);
        }

        $isUsedInAssy = AssyNumber::where('carline_id', $carline->id)->exists();

        if ($isUsedInAssy) {
            return redirect()->back()->withErrors([
                'error' => "Carline/Konveyor '{$carline->name}' tidak dapat dihapus karena masih digunakan di data assy number."
            ]);
        }

        $isUsedInMachine = PreAssyMachine::where('carline_id', $carline->id)->exists();

        if ($isUsedInMachine) {
            return redirect()->back()->withErrors([
                'error' => "Carline/Konveyor '{$carline->name}' tidak dapat dihapus karena masih memiliki relasi ke data nomor mesin Pre Assy."
            ]);
        }

        $carline->delete();

        return redirect()->route('admin.master.carlines.index')
            ->with('success', 'Carline berhasil dihapus!');
    }
}
