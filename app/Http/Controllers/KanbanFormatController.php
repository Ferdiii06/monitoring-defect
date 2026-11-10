<?php

namespace App\Http\Controllers;

use App\Models\KanbanComponent;
use App\Support\KanbanCodeParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KanbanFormatController extends Controller
{
    /**
     * Tampilkan halaman konfigurasi urutan format kanban.
     */
    public function index()
    {
        $this->ensureDefaultsExist();

        $components = KanbanComponent::orderBy('sequence', 'asc')->get();
        $samplePreview = $this->generatePreview($components);
        $allowedDelimiters = KanbanComponent::ALLOWED_DELIMITERS;

        return view('master.kanban_format', compact('components', 'samplePreview', 'allowedDelimiters'));
    }

    /**
     * Simpan perubahan urutan dan delimiter format kanban.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'components'                  => 'required|array|size:3',
            'components.*.component_key'  => 'required|string|in:' . implode(',', KanbanComponent::ALLOWED_KEYS),
            'components.*.sequence'       => 'required|integer|min:1|max:3',
            'components.*.delimiter'      => 'present|string|in:., ,spasi',
        ], [
            'components.size' => 'Semua komponen kanban (3 komponen) wajib disertakan.',
            'components.*.sequence.min' => 'Urutan komponen minimal 1.',
            'components.*.sequence.max' => 'Urutan komponen maksimal 3.',
            'components.*.delimiter.in' => 'Pilihan delimiter hanya boleh titik (.) atau spasi.',
        ]);

        $submitted = collect($validated['components']);

        // 1. Validasi: Semua komponen wajib ada (tanpa duplikat key)
        $keys = $submitted->pluck('component_key')->unique()->all();
        if (count($keys) !== count(KanbanComponent::ALLOWED_KEYS) ||
            count(array_diff(KanbanComponent::ALLOWED_KEYS, $keys)) !== 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['components' => 'Setiap komponen (Nomor Assy, Konveyor, Serial Number) harus ada tepat satu kali.']);
        }

        // 2. Validasi Invarian: Sequence harus permutasi 1..n tanpa duplikat
        $sequences = $submitted->pluck('sequence')->map(fn($s) => (int)$s)->sort()->values()->all();
        if ($sequences !== [1, 2, 3]) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['components' => 'Urutan komponen harus berupa permutasi angka 1, 2, dan 3 tanpa ada nilai duplikat.']);
        }

        // 3. Simpan perubahan dalam satu DB transaction
        DB::transaction(function () use ($submitted) {
            foreach ($submitted as $item) {
                $delimiter = ($item['delimiter'] === 'spasi') ? ' ' : $item['delimiter'];

                KanbanComponent::where('component_key', $item['component_key'])->update([
                    'sequence'  => (int) $item['sequence'],
                    'delimiter' => $delimiter,
                ]);
            }
        });

        // 4. Reset cache parser agar regex dibangun ulang
        KanbanCodeParser::clearCache();

        return redirect()->route('admin.master.kanban_format.index')
            ->with('success', 'Urutan dan delimiter format Kanban berhasil disimpan!');
    }

    /**
     * Reset urutan format kanban ke default bawaan sistem.
     */
    public function reset()
    {
        DB::transaction(function () {
            KanbanComponent::where('component_key', KanbanComponent::KEY_ASSY_NUMBER)->update(['sequence' => 1, 'delimiter' => '.']);
            KanbanComponent::where('component_key', KanbanComponent::KEY_CONVEYOR)->update(['sequence' => 2, 'delimiter' => '.']);
            KanbanComponent::where('component_key', KanbanComponent::KEY_SERIAL)->update(['sequence' => 3, 'delimiter' => '.']);
        });

        KanbanCodeParser::clearCache();

        return redirect()->route('admin.master.kanban_format.index')
            ->with('success', 'Format Kanban berhasil dikembalikan ke urutan default!');
    }

    /**
     * Pastikan 3 komponen default tersedia di database.
     */
    protected function ensureDefaultsExist(): void
    {
        if (KanbanComponent::count() < count(KanbanComponent::ALLOWED_KEYS)) {
            $defaults = [
                ['component_key' => KanbanComponent::KEY_ASSY_NUMBER, 'label' => 'Nomor Assy', 'sequence' => 1, 'delimiter' => '.'],
                ['component_key' => KanbanComponent::KEY_CONVEYOR,    'label' => 'Konveyor',   'sequence' => 2, 'delimiter' => '.'],
                ['component_key' => KanbanComponent::KEY_SERIAL,      'label' => 'Serial Number', 'sequence' => 3, 'delimiter' => '.'],
            ];

            foreach ($defaults as $d) {
                KanbanComponent::firstOrCreate(
                    ['component_key' => $d['component_key']],
                    ['label' => $d['label'], 'sequence' => $d['sequence'], 'delimiter' => $d['delimiter']]
                );
            }
        }
    }

    /**
     * Buat contoh string preview format kanban.
     */
    protected function generatePreview($components): string
    {
        $samples = [
            'assy_number' => '82111-VU020',
            'conveyor'    => 'C7',
            'serial'      => '010',
        ];

        $parts = [];
        $total = $components->count();

        foreach ($components as $index => $comp) {
            $parts[] = $samples[$comp->component_key] ?? 'XXX';
            if ($index < $total - 1) {
                $parts[] = $comp->delimiter;
            }
        }

        return implode('', $parts);
    }
}

