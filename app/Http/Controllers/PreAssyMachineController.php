<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Carline;
use App\Models\PreAssyMachine;

class PreAssyMachineController extends Controller
{
    /**
     * Tampilkan daftar master No Mesin Pre Assy per Carline.
     */
    public function index(Request $request)
    {
        $carlines = Carline::preAssy()->orderBy('name')->get();

        $selectedCarlineId = $request->input('carline_id');
        if (!$selectedCarlineId && $carlines->isNotEmpty()) {
            $selectedCarlineId = $carlines->first()->id;
        }

        $selectedCarline = $selectedCarlineId ? $carlines->firstWhere('id', (int) $selectedCarlineId) : null;

        $machines = $selectedCarlineId
            ? PreAssyMachine::where('carline_id', $selectedCarlineId)->orderBy('name')->get()
            : collect([]);

        return view('master.pre_assy_machines', compact('carlines', 'selectedCarlineId', 'selectedCarline', 'machines'));
    }

    /**
     * Simpan No Mesin baru untuk Carline yang dipilih.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'carline_id' => 'required|exists:carlines,id',
            'name'       => [
                'required',
                'string',
                'max:255',
                Rule::unique('pre_assy_machines')->where(fn ($query) => $query->where('carline_id', $request->input('carline_id'))),
            ],
            'is_active'  => 'nullable|boolean',
        ], [
            'name.unique' => 'Nomor mesin ini sudah terdaftar pada carline yang dipilih.',
        ]);

        PreAssyMachine::create([
            'carline_id' => $validated['carline_id'],
            'name'       => trim($validated['name']),
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.master.pre_assy_machines.index', ['carline_id' => $validated['carline_id']])
            ->with('success', 'Nomor Mesin berhasil ditambahkan!');
    }

    /**
     * Update data No Mesin.
     */
    public function update(Request $request, $id)
    {
        $machine = PreAssyMachine::findOrFail($id);

        $validated = $request->validate([
            'name'      => [
                'required',
                'string',
                'max:255',
                Rule::unique('pre_assy_machines')
                    ->where(fn ($query) => $query->where('carline_id', $machine->carline_id))
                    ->ignore($id),
            ],
            'is_active' => 'nullable|boolean',
        ], [
            'name.unique' => 'Nomor mesin ini sudah terdaftar pada carline yang dipilih.',
        ]);

        $machine->update([
            'name'      => trim($validated['name']),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $machine->is_active,
        ]);

        return redirect()
            ->route('admin.master.pre_assy_machines.index', ['carline_id' => $machine->carline_id])
            ->with('success', 'Nomor Mesin berhasil diperbarui!');
    }

    /**
     * Toggle status aktif/nonaktif mesin.
     */
    public function toggleStatus($id)
    {
        $machine = PreAssyMachine::findOrFail($id);
        $machine->update(['is_active' => !$machine->is_active]);

        $statusText = $machine->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('admin.master.pre_assy_machines.index', ['carline_id' => $machine->carline_id])
            ->with('success', "Nomor Mesin {$machine->name} berhasil {$statusText}!");
    }

    /**
     * Hapus No Mesin dari master data.
     */
    public function destroy($id)
    {
        $machine = PreAssyMachine::findOrFail($id);
        $carlineId = $machine->carline_id;
        $machine->delete();

        return redirect()
            ->route('admin.master.pre_assy_machines.index', ['carline_id' => $carlineId])
            ->with('success', 'Nomor Mesin berhasil dihapus!');
    }
}
