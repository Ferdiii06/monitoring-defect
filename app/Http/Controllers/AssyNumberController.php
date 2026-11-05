<?php

namespace App\Http\Controllers;

use App\Imports\AssyNumberImport;
use App\Models\AssyNumber;
use App\Models\Carline;
use App\Support\KanbanCodeParser;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AssyNumberController extends Controller
{
    /**
     * Display a listing of assy numbers with search and carline filter.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $selectedCarline = $request->input('carline_id');

        $query = AssyNumber::with(['carline.carType']);

        if ($search !== '') {
            $normalizedSearch = KanbanCodeParser::normalizeAssyNumber($search);
            $query->where(function ($q) use ($search, $normalizedSearch) {
                $term = '%' . strtolower($search) . '%';
                $q->whereRaw('LOWER(assy_number) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(keterangan) LIKE ?', [$term])
                  ->orWhereHas('carline', function ($sub) use ($term) {
                      $sub->whereRaw('LOWER(name) LIKE ?', [$term]);
                  });

                if ($normalizedSearch !== null) {
                    $q->orWhereRaw("REPLACE(REPLACE(UPPER(assy_number), '-', ''), ' ', '') LIKE ?", ['%' . $normalizedSearch . '%']);
                }
            });
        }

        if ($selectedCarline) {
            $query->where('carline_id', $selectedCarline);
        }

        $assyNumbers = $query->orderBy('assy_number')->paginate(20)->withQueryString();
        $carlines = Carline::with('carType')->orderBy('name')->get();

        return view('master.assy_numbers', compact('assyNumbers', 'carlines', 'search', 'selectedCarline'));
    }

    /**
     * Store a newly created assy number.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'assy_number' => 'required|string|max:255',
            'carline_id'  => 'nullable|exists:carlines,id',
            'keterangan'  => 'nullable|string|max:255',
        ]);

        $assy = trim($validated['assy_number']);
        $normalized = KanbanCodeParser::normalizeAssyNumber($assy);

        // Check if already exists by normalized assy
        $exists = AssyNumber::where('assy_number', $assy)
            ->orWhereRaw("REPLACE(REPLACE(UPPER(assy_number), '-', ''), ' ', '') = ?", [$normalized])
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->withErrors([
                'assy_number' => "Assy number '{$assy}' sudah terdaftar di master data.",
            ]);
        }

        AssyNumber::create([
            'assy_number' => $assy,
            'carline_id'  => $validated['carline_id'] ?? null,
            'keterangan'  => filled($validated['keterangan'] ?? null) ? trim($validated['keterangan']) : null,
        ]);

        return redirect()->route('admin.master.assy_numbers.index')->with('success', 'Assy Number berhasil ditambahkan!');
    }

    /**
     * Update an existing assy number.
     */
    public function update(Request $request, $id)
    {
        $assyNumber = AssyNumber::findOrFail($id);

        $validated = $request->validate([
            'assy_number' => 'required|string|max:255',
            'carline_id'  => 'nullable|exists:carlines,id',
            'keterangan'  => 'nullable|string|max:255',
        ]);

        $assy = trim($validated['assy_number']);
        $normalized = KanbanCodeParser::normalizeAssyNumber($assy);

        // Check uniqueness excluding current record
        $exists = AssyNumber::where('id', '!=', $id)
            ->where(function ($q) use ($assy, $normalized) {
                $q->where('assy_number', $assy)
                  ->orWhereRaw("REPLACE(REPLACE(UPPER(assy_number), '-', ''), ' ', '') = ?", [$normalized]);
            })
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->withErrors([
                'assy_number' => "Assy number '{$assy}' sudah terdaftar di data lain.",
            ]);
        }

        $assyNumber->update([
            'assy_number' => $assy,
            'carline_id'  => $validated['carline_id'] ?? null,
            'keterangan'  => filled($validated['keterangan'] ?? null) ? trim($validated['keterangan']) : null,
        ]);

        return redirect()->route('admin.master.assy_numbers.index')->with('success', 'Assy Number berhasil diperbarui!');
    }

    /**
     * Delete an assy number.
     */
    public function destroy($id)
    {
        $assyNumber = AssyNumber::findOrFail($id);
        $assyNumber->delete();

        return redirect()->route('admin.master.assy_numbers.index')->with('success', 'Assy Number berhasil dihapus!');
    }

    /**
     * Import assy numbers from Excel / CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $import = new AssyNumberImport();
        Excel::import($import, $request->file('file'));

        $success = $import->getSuccessCount();
        $skipped = $import->getSkippedCount();
        $errors  = $import->getSkippedRows();

        session()->flash('import_summary', [
            'success' => $success,
            'skipped' => $skipped,
            'errors'  => $errors,
        ]);

        $message = "Import selesai: {$success} baris berhasil diproses";
        if ($skipped > 0) {
            $message .= ", {$skipped} baris dilewati.";
        } else {
            $message .= ".";
        }

        return redirect()->route('admin.master.assy_numbers.index')->with('success', $message);
    }
}
