<?php

namespace App\Http\Controllers;

use App\Imports\PreAssyMachineImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Models\Carline;
use App\Models\PreAssyMachine;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            'carline_id' => $this->preAssyCarlineRules(),
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

    /**
     * Import No Mesin Pre Assy dari file Excel (.xlsx / .xls).
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'carline_id' => $this->preAssyCarlineRules(),
            'file'       => 'required|file|mimes:xlsx,xls|max:2048',
        ], [
            'file.mimes' => 'Format file harus .xlsx atau .xls.',
            'file.max'   => 'Ukuran file maksimal 2 MB.',
        ]);

        $carlineId = (int) $validated['carline_id'];

        try {
            $import = new PreAssyMachineImport($carlineId);
            Excel::import($import, $request->file('file'));

            $success = $import->getSuccessCount();
            $skipped = $import->getSkippedCount();
            $errors  = $import->getSkippedRows();

            session()->flash('import_summary', [
                'success' => $success,
                'skipped' => $skipped,
                'errors'  => $errors,
            ]);

            $message = "Import selesai: {$success} nomor mesin berhasil ditambahkan";
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati";
            }
            $message .= '.';

            return redirect()
                ->route('admin.master.pre_assy_machines.index', ['carline_id' => $carlineId])
                ->with($success > 0 ? 'success' : 'warning', $message);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.master.pre_assy_machines.index', ['carline_id' => $carlineId])
                ->withErrors(['file' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Import Master No Mesin Pre Assy gagal: ' . $e->getMessage(), [
                'exception'  => $e,
                'carline_id' => $carlineId,
                'file'       => $request->file('file')?->getClientOriginalName(),
            ]);

            return redirect()
                ->route('admin.master.pre_assy_machines.index', ['carline_id' => $carlineId])
                ->withErrors(['file' => 'Gagal memproses file Excel. Periksa format file atau hubungi pengembang.']);
        }
    }

    /**
     * Unduh template Excel untuk import No Mesin Pre Assy.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'No Mesin');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getColumnDimension('A')->setWidth(25);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template_no_mesin_pre_assy.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Aturan validasi carline_id khusus Pre Assy.
     */
    private function preAssyCarlineRules(): array
    {
        return [
            'required',
            Rule::exists('carlines', 'id'),
            function ($attribute, $value, $fail) {
                if (!Carline::preAssy()->whereKey($value)->exists()) {
                    $fail('Carline yang dipilih bukan carline Pre Assy.');
                }
            },
        ];
    }
}
