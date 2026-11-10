@extends('layouts.app')

@section('title', 'Master Format Kanban')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">Master Format Kanban</h1>
            <p class="text-xs font-semibold text-gray-500 mt-1">
                Atur urutan komponen dan karakter pemisah (delimiter) kode e-Kanban / barcode Kanban.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.master.assy_numbers.index') }}" 
               class="px-4 py-2 rounded-lg bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition-all shadow-xs border border-gray-200 flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Kembali ke Master Kanban</span>
            </a>

            <form action="{{ route('admin.master.kanban_format.reset') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan urutan format Kanban ke default bawaan sistem?');">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-all shadow-xs flex items-center space-x-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Reset ke Default</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center space-x-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-brand text-xs font-semibold space-y-1">
            <div class="font-bold">Terjadi kesalahan validasi:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Container with Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="kanbanReorder()">
        <!-- Configuration Form -->
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Urutan Komponen Kanban</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Tentukan urutan (1, 2, 3) dan delimiter pemisah tiap komponen.</p>
                </div>
                <span class="text-xs font-mono font-bold text-gray-400">Total 3 Komponen</span>
            </div>

            <form action="{{ route('admin.master.kanban_format.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <template x-for="(comp, idx) in components" :key="comp.key">
                        <div class="p-4 rounded-lg border transition-all"
                             :class="comp.seq == 1 ? 'border-red-200 bg-red-50/10' : 'border-gray-200 bg-gray-50/30'">
                            <input type="hidden" :name="'components[' + idx + '][component_key]'" :value="comp.key">

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <!-- Info Komponen -->
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#8b0000] text-white flex items-center justify-center font-bold text-xs shrink-0 font-mono shadow-xs"
                                         x-text="comp.seq">
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900" x-text="comp.label"></div>
                                        <div class="text-[11px] font-mono text-gray-400" x-text="'Key: ' + comp.key"></div>
                                    </div>
                                </div>

                                <!-- Kontrol Urutan & Delimiter -->
                                <div class="flex flex-wrap items-center gap-3">
                                    <!-- Sequence Input / Controls -->
                                    <div class="flex items-center space-x-1">
                                        <button type="button"
                                                @click="moveUp(idx)"
                                                :disabled="idx === 0"
                                                class="w-7 h-7 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-xs font-bold text-gray-700 transition-colors"
                                                title="Pindah ke Atas">
                                            ▲
                                        </button>
                                        <button type="button"
                                                @click="moveDown(idx)"
                                                :disabled="idx === components.length - 1"
                                                class="w-7 h-7 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-xs font-bold text-gray-700 transition-colors"
                                                title="Pindah ke Bawah">
                                            ▼
                                        </button>
                                        <input type="number"
                                               :name="'components[' + idx + '][sequence]'"
                                               x-model.number="comp.seq"
                                               @change="onManualSeqChange()"
                                               min="1" max="3"
                                               class="w-12 text-center py-1 border border-gray-300 rounded text-xs font-bold font-mono focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                                    </div>

                                    <!-- Delimiter Select -->
                                    <div class="flex items-center space-x-1.5">
                                        <label class="text-[11px] font-semibold text-gray-500">Pemisah:</label>
                                        <select :name="'components[' + idx + '][delimiter]'"
                                                x-model="comp.delimiter"
                                                @change="updatePreview()"
                                                class="py-1 px-2 border border-gray-300 rounded text-xs font-semibold bg-white focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                                            <option value=".">Titik (.)</option>
                                            <option value=" ">Spasi ( )</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold transition-all shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Simpan Urutan Format</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview Card & Guidelines -->
        <div class="space-y-6">
            <!-- Live Preview Card -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-2">Contoh Hasil Format</h3>
                <div class="p-3 bg-gray-900 rounded-lg border border-gray-800">
                    <span class="block text-[10px] font-mono text-gray-400 uppercase tracking-widest mb-1">Preview String Barcode:</span>
                    <span class="block text-sm font-mono font-bold text-emerald-400 break-all select-all tracking-wide"
                          x-text="previewString"></span>
                </div>

                <div class="mt-4 space-y-2 text-xs text-gray-600">
                    <div class="font-bold text-gray-800">Struktur Contoh:</div>
                    <div class="flex items-center justify-between text-[11px] py-1 border-b border-gray-100">
                        <span class="text-gray-500">Nomor Assy (10 char):</span>
                        <span class="font-mono font-bold text-gray-800">82111-VU020</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-1 border-b border-gray-100">
                        <span class="text-gray-500">Konveyor (Alfanumerik):</span>
                        <span class="font-mono font-bold text-gray-800">C7</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-1">
                        <span class="text-gray-500">Serial Number (3 digit):</span>
                        <span class="font-mono font-bold text-gray-800">010</span>
                    </div>
                </div>
            </div>

            <!-- Guidelines Card -->
            <div class="bg-red-50/40 border border-red-100 rounded-lg p-5">
                <div class="flex items-center space-x-2 text-brand font-bold text-xs mb-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Ketentuan Format Kanban</span>
                </div>
                <ul class="text-[11px] text-gray-600 space-y-1.5 list-disc pl-4 leading-relaxed">
                    <li>Urutan harus merupakan kombinasi angka <strong>1, 2, dan 3</strong> tanpa boleh ada duplikasi nomor urut.</li>
                    <li>Pemisah (delimiter) menentukan karakter penghubung antar-komponen saat scanning.</li>
                    <li>Sistem memiliki mekanisme <em>fallback</em>: jika kode yang di-scan memakai urutan barcode lama, sistem tetap otomatis dapat membacanya.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function kanbanReorder() {
    return {
        components: [
            @foreach($components as $c)
            {
                key: '{{ $c->component_key }}',
                label: '{{ $c->label }}',
                seq: {{ $c->sequence }},
                delimiter: '{{ $c->delimiter }}'
            },
            @endforeach
        ],
        sampleData: {
            'assy_number': '82111-VU020',
            'conveyor': 'C7',
            'serial': '010'
        },
        previewString: '{{ $samplePreview }}',

        init() {
            this.sortBySeq();
            this.updatePreview();
        },

        sortBySeq() {
            this.components.sort((a, b) => a.seq - b.seq);
        },

        moveUp(idx) {
            if (idx <= 0) return;
            const tempSeq = this.components[idx].seq;
            this.components[idx].seq = this.components[idx - 1].seq;
            this.components[idx - 1].seq = tempSeq;
            this.sortBySeq();
            this.updatePreview();
        },

        moveDown(idx) {
            if (idx >= this.components.length - 1) return;
            const tempSeq = this.components[idx].seq;
            this.components[idx].seq = this.components[idx + 1].seq;
            this.components[idx + 1].seq = tempSeq;
            this.sortBySeq();
            this.updatePreview();
        },

        onManualSeqChange() {
            this.sortBySeq();
            this.updatePreview();
        },

        updatePreview() {
            const sorted = [...this.components].sort((a, b) => a.seq - b.seq);
            let parts = [];
            for (let i = 0; i < sorted.length; i++) {
                parts.push(this.sampleData[sorted[i].key] || 'XXX');
                if (i < sorted.length - 1) {
                    parts.push(sorted[i].delimiter);
                }
            }
            this.previewString = parts.join('');
        }
    };
}
</script>
@endsection

