<?php

namespace App\Http\Controllers;

use App\Exports\NameplateSettingsExport;
use App\Exports\NameplateSettingsTemplateExport;
use App\Http\Requests\StoreNameplateSettingRequest;
use App\Http\Requests\UpdateNameplateSettingRequest;
use App\Imports\NameplateSettingsImport;
use App\Models\ActivityLog;
use App\Models\NameplateSetting;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class NameplateSettingController extends Controller
{
    /**
     * Display a listing of carline nameplate settings.
     */
    public function index(Request $request)
    {
        $search  = trim((string) $request->input('search', ''));
        $sortBy  = (string) $request->input('sort_by', 'carline');
        $order   = strtolower((string) $request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->input('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $settings = NameplateSetting::query()
            ->search($search)
            ->sortBy($sortBy, $order)
            ->paginate($perPage)
            ->withQueryString();

        return view('master.nameplate_settings', compact(
            'settings',
            'search',
            'sortBy',
            'order',
            'perPage'
        ));
    }

    /**
     * Store a newly created nameplate setting.
     */
    public function store(StoreNameplateSettingRequest $request)
    {
        $validated = $request->validated();

        $setting = NameplateSetting::create($validated);

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin QA'),
            'jenis_aksi'   => 'Create Nameplate Setting',
            'aktivitas'    => 'Menambah nameplate setting carline: ' . $setting->carline . ' (' . $setting->name . ')',
            'jenis_defect' => 'none',
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('admin.master.nameplate_settings.index')
            ->with('success', 'Data Nameplate Setting berhasil ditambahkan.');
    }

    /**
     * Update an existing nameplate setting.
     */
    public function update(UpdateNameplateSettingRequest $request, $id)
    {
        $setting = NameplateSetting::findOrFail($id);
        $validated = $request->validated();

        $setting->update($validated);

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin QA'),
            'jenis_aksi'   => 'Update Nameplate Setting',
            'aktivitas'    => 'Mengubah nameplate setting carline: ' . $setting->carline . ' (' . $setting->name . ')',
            'jenis_defect' => 'none',
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('admin.master.nameplate_settings.index')
            ->with('success', 'Data Nameplate Setting berhasil diperbarui.');
    }

    /**
     * Download Excel of filtered or all nameplate settings.
     */
    public function export(Request $request)
    {
        $search   = $request->input('search');
        $sortBy   = $request->input('sort_by', 'carline');
        $order    = $request->input('order', 'asc');
        $fileName = 'nameplate_setting_' . date('Ymd') . '.xlsx';

        return Excel::download(new NameplateSettingsExport($search, $sortBy, $order), $fileName);
    }

    /**
     * Download sample Excel template.
     */
    public function template()
    {
        return Excel::download(new NameplateSettingsTemplateExport(), 'nameplate_settings_template.xlsx');
    }

    /**
     * Import nameplate settings from Excel / CSV with upsert.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048|mimes:xlsx,xls,csv,txt',
        ], [
            'file.required' => 'File Excel/CSV wajib diunggah.',
            'file.mimes'    => 'Format file harus berupa Excel (.xlsx/.xls) atau CSV.',
            'file.max'      => 'Ukuran file maksimal adalah 2 MB.',
        ]);

        $import = new NameplateSettingsImport();
        Excel::import($import, $request->file('file'));

        $added   = $import->getAddedCount();
        $updated = $import->getUpdatedCount();
        $failed  = $import->getFailedCount();
        $errors  = $import->getFailedRows();

        session()->flash('nameplate_setting_import_summary', [
            'added'   => $added,
            'updated' => $updated,
            'failed'  => $failed,
            'errors'  => $errors,
        ]);

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin QA'),
            'jenis_aksi'   => 'Import Nameplate Setting',
            'aktivitas'    => "Import file nameplate setting: {$added} ditambah, {$updated} diperbarui, {$failed} gagal.",
            'jenis_defect' => 'none',
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        $msgParts = ["Import selesai: {$added} data ditambahkan", "{$updated} diperbarui"];
        if ($failed > 0) {
            $msgParts[] = "{$failed} baris gagal";
        }
        $message = implode(', ', $msgParts) . '.';

        return redirect()->route('admin.master.nameplate_settings.index')->with('success', $message);
    }

    /**
     * Test Scan Barcode simulation (Stateless, no DB persist).
     */
    public function testScan(Request $request)
    {
        $barcode = trim((string) $request->input('barcode', ''));

        if ($barcode === '') {
            return response()->json([
                'success' => false,
                'message' => 'Barcode tidak boleh kosong.',
            ], 422);
        }

        $rules = [
            'assy_no'        => $request->input('assy_no'),
            'suffix'         => $request->input('suffix'),
            'level'          => $request->input('level'),
            'date_field'     => $request->input('date_field') ?? $request->input('date'),
            'date_format'    => $request->input('date_format', 'YYMMDD'),
            'sequence_label' => $request->input('sequence_label'),
        ];

        $result = NameplateSetting::extractBarcode($barcode, $rules);

        return response()->json($result);
    }
}
