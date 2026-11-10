<?php

namespace App\Http\Controllers;

use App\Imports\NameplateImport;
use App\Models\Nameplate;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class NameplateController extends Controller
{
    /**
     * Tampilkan data master nameplate dengan pencarian dan filter server-side.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $selectedSource = $request->input('source');
        $selectedManual = $request->input('manual_edit');

        $query = Nameplate::query();

        if ($search !== '') {
            $normalizedSearch = Nameplate::normalizeCode($search) ?? $search;
            $term = '%' . strtolower($search) . '%';
            $normTerm = '%' . strtolower($normalizedSearch) . '%';

            $query->where(function ($q) use ($term, $normTerm) {
                $q->whereRaw('LOWER(nameplate_code) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(nameplate_code) LIKE ?', [$normTerm])
                  ->orWhereRaw('LOWER(assy_number) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(serial_number) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(keterangan) LIKE ?', [$term]);
            });
        }

        if ($selectedSource && in_array($selectedSource, [Nameplate::SOURCE_IMPORT, Nameplate::SOURCE_MANUAL], true)) {
            $query->where('source', $selectedSource);
        }

        if ($selectedManual !== null && $selectedManual !== '') {
            $query->where('is_manual_edit', $selectedManual === '1');
        }

        $nameplates = $query->orderBy('updated_at', 'desc')->paginate(20)->withQueryString();

        return view('master.nameplates', compact('nameplates', 'search', 'selectedSource', 'selectedManual'));
    }

    /**
     * Tambah record nameplate baru secara manual.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nameplate_code' => 'required|string|max:100',
            'assy_number'    => 'nullable|string|max:50',
            'serial_number'  => 'nullable|string|max:20',
            'keterangan'     => 'nullable|string|max:500',
        ]);

        $normCode = Nameplate::normalizeCode($validated['nameplate_code']);
        if ($normCode === null) {
            return redirect()->back()->withInput()->withErrors([
                'nameplate_code' => 'Kode nameplate tidak valid.',
            ]);
        }

        if (Nameplate::where('nameplate_code', $normCode)->exists()) {
            return redirect()->back()->withInput()->withErrors([
                'nameplate_code' => "Kode nameplate '{$normCode}' sudah terdaftar.",
            ]);
        }

        $extracted = Nameplate::extractFromCode($normCode);

        Nameplate::create([
            'nameplate_code'  => $normCode,
            'assy_number'     => filled($validated['assy_number'] ?? null) ? trim($validated['assy_number']) : $extracted['assy_number'],
            'suffix'          => $extracted['suffix'],
            'date_code'       => $extracted['date_code'],
            'production_date' => $extracted['production_date'],
            'serial_number'   => filled($validated['serial_number'] ?? null) ? trim($validated['serial_number']) : $extracted['serial_number'],
            'keterangan'      => filled($validated['keterangan'] ?? null) ? trim($validated['keterangan']) : null,
            'source'          => Nameplate::SOURCE_MANUAL,
            'is_manual_edit'  => true,
            'raw_payload'     => null,
        ]);

        return redirect()->route('admin.master.nameplates.index')->with('success', 'Nameplate berhasil ditambahkan!');
    }

    /**
     * Update data nameplate (otomatis menandai is_manual_edit = true).
     */
    public function update(Request $request, $id)
    {
        $nameplate = Nameplate::findOrFail($id);

        $validated = $request->validate([
            'nameplate_code' => 'required|string|max:100',
            'assy_number'    => 'nullable|string|max:50',
            'serial_number'  => 'nullable|string|max:20',
            'keterangan'     => 'nullable|string|max:500',
        ]);

        $normCode = Nameplate::normalizeCode($validated['nameplate_code']);
        if ($normCode === null) {
            return redirect()->back()->withInput()->withErrors([
                'nameplate_code' => 'Kode nameplate tidak valid.',
            ]);
        }

        $exists = Nameplate::where('id', '!=', $id)
            ->where('nameplate_code', $normCode)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->withErrors([
                'nameplate_code' => "Kode nameplate '{$normCode}' sudah digunakan di data lain.",
            ]);
        }

        $extracted = Nameplate::extractFromCode($normCode);

        $nameplate->update([
            'nameplate_code'  => $normCode,
            'assy_number'     => filled($validated['assy_number'] ?? null) ? trim($validated['assy_number']) : $extracted['assy_number'],
            'suffix'          => $extracted['suffix'] ?? $nameplate->suffix,
            'date_code'       => $extracted['date_code'] ?? $nameplate->date_code,
            'production_date' => $extracted['production_date'] ?? $nameplate->production_date,
            'serial_number'   => filled($validated['serial_number'] ?? null) ? trim($validated['serial_number']) : $extracted['serial_number'],
            'keterangan'      => filled($validated['keterangan'] ?? null) ? trim($validated['keterangan']) : null,
            'is_manual_edit'  => true,
        ]);

        return redirect()->route('admin.master.nameplates.index')->with('success', 'Nameplate berhasil diperbarui!');
    }

    /**
     * Hapus nameplate.
     */
    public function destroy($id)
    {
        $nameplate = Nameplate::findOrFail($id);
        $nameplate->delete();

        return redirect()->route('admin.master.nameplates.index')->with('success', 'Nameplate berhasil dihapus!');
    }

    /**
     * Import nameplate dari file CSV / Excel (HTScan JAI).
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ], [
            'file.required' => 'File CSV/Excel wajib diunggah.',
            'file.mimes'    => 'Format file harus berupa CSV atau Excel (.xlsx/.xls).',
            'file.max'      => 'Ukuran file maksimal adalah 10MB.',
        ]);

        $import = new NameplateImport();
        Excel::import($import, $request->file('file'));

        $added   = $import->getAddedCount();
        $updated = $import->getUpdatedCount();
        $skipped = $import->getSkippedCount();
        $failed  = $import->getFailedCount();
        $errors  = $import->getFailedRows();
        $unmatched = $import->getUnmatchedPatternCount();

        session()->flash('nameplate_import_summary', [
            'added'             => $added,
            'updated'           => $updated,
            'skipped'           => $skipped,
            'unmatched_pattern' => $unmatched,
            'failed'            => $failed,
            'errors'            => $errors,
        ]);

        $msgParts = ["Import selesai: {$added} data baru ditambahkan", "{$updated} diperbarui"];
        if ($skipped > 0) {
            $msgParts[] = "{$skipped} dilewati (karena pernah diedit manual)";
        }
        if ($failed > 0) {
            $msgParts[] = "{$failed} baris gagal/tidak valid";
        }

        $message = implode(', ', $msgParts) . '.';

        return redirect()->route('admin.master.nameplates.index')->with('success', $message);
    }
}

