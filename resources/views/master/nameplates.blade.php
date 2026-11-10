@extends('layouts.app')

@section('title', 'Master Nameplate (HTScan JAI)')

@section('content')
<div class="space-y-6" x-data="{ addModal: false, importModal: false, editModal: false, editData: {} }">
    <!-- Header Section -->
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">Master Nameplate (HTScan JAI)</h1>
            <p class="text-xs font-semibold text-gray-500 mt-1">
                Katalog master barcode/QR code nameplate hasil integrasi HTScan JAI dan input manual.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <button type="button" @click="importModal = true"
                    class="px-4 py-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition-all shadow-xs flex items-center space-x-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                <span>Import File (CSV/XLSX)</span>
            </button>
            <button type="button" @click="addModal = true"
                    class="px-4 py-2.5 rounded-lg bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold transition-all shadow-xs flex items-center space-x-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Nameplate</span>
            </button>
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

    <!-- Import Summary Card if available -->
    @if(session('nameplate_import_summary'))
        @php $summary = session('nameplate_import_summary'); @endphp
        <div class="p-5 rounded-lg bg-white border border-gray-200 shadow-sm space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <span class="text-xs font-bold text-gray-800 uppercase tracking-wider">Laporan Hasil Impor Terakhir</span>
                <span class="text-[11px] text-gray-400 font-mono">HTScan JAI</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="p-3 rounded-lg bg-emerald-50/60 border border-emerald-100">
                    <span class="block text-[10px] font-bold text-emerald-700 uppercase">Ditambahkan</span>
                    <span class="text-lg font-black text-emerald-900 font-mono">{{ $summary['added'] ?? 0 }}</span>
                </div>
                <div class="p-3 rounded-lg bg-blue-50/60 border border-blue-100">
                    <span class="block text-[10px] font-bold text-blue-700 uppercase">Diperbarui</span>
                    <span class="text-lg font-black text-blue-900 font-mono">{{ $summary['updated'] ?? 0 }}</span>
                </div>
                <div class="p-3 rounded-lg bg-purple-50/60 border border-purple-100">
                    <span class="block text-[10px] font-bold text-purple-700 uppercase">Dilewati (Manual)</span>
                    <span class="text-lg font-black text-purple-900 font-mono">{{ $summary['skipped'] ?? 0 }}</span>
                    <span class="text-[10px] text-purple-600 block mt-0.5">Tidak ditimpa</span>
                </div>
                <div class="p-3 rounded-lg bg-amber-50/60 border border-amber-100">
                    <span class="block text-[10px] font-bold text-amber-700 uppercase">Non-Pola Standar</span>
                    <span class="text-lg font-black text-amber-900 font-mono">{{ $summary['unmatched_pattern'] ?? 0 }}</span>
                    <span class="text-[10px] text-amber-600 block mt-0.5">Tersimpan</span>
                </div>
                <div class="p-3 rounded-lg bg-red-50/60 border border-red-100">
                    <span class="block text-[10px] font-bold text-red-700 uppercase">Gagal / Invalid</span>
                    <span class="text-lg font-black text-red-900 font-mono">{{ $summary['failed'] ?? 0 }}</span>
                </div>
            </div>

            @if(!empty($summary['errors']))
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <span class="block text-xs font-bold text-red-700 mb-1">Rincian Baris Gagal (Maks 10 baris pertama):</span>
                    <div class="max-h-36 overflow-y-auto space-y-1">
                        @foreach(array_slice($summary['errors'], 0, 10) as $err)
                            <div class="text-[11px] font-mono text-gray-700 bg-gray-50 px-2 py-1 rounded">
                                Baris {{ $err['row'] ?? '-' }}: [{{ $err['code'] ?? '-' }}] - {{ $err['reason'] ?? '-' }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Filters & Table Section -->
    <section class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.master.nameplates.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative min-w-[240px]">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode, assy, serial..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000]">
                </div>

                <!-- Source Filter -->
                <div class="relative min-w-[140px]">
                    <select name="source" onchange="this.form.submit()"
                            class="w-full appearance-none pl-4 pr-10 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                        <option value="">Semua Sumber</option>
                        <option value="import" {{ $selectedSource === 'import' ? 'selected' : '' }}>Import HTScan</option>
                        <option value="manual" {{ $selectedSource === 'manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <!-- Manual Edit Filter -->
                <div class="relative min-w-[150px]">
                    <select name="manual_edit" onchange="this.form.submit()"
                            class="w-full appearance-none pl-4 pr-10 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                        <option value="">Semua Status Edit</option>
                        <option value="1" {{ $selectedManual === '1' ? 'selected' : '' }}>Diedit Manual</option>
                        <option value="0" {{ $selectedManual === '0' ? 'selected' : '' }}>Asli / Belum Diedit</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                @if($search || $selectedSource || $selectedManual !== null)
                    <a href="{{ route('admin.master.nameplates.index') }}" class="text-xs text-gray-400 hover:text-[#8b0000] font-semibold transition-colors flex items-center space-x-1 pl-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>

            <button type="submit" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-all">
                Terapkan
            </button>
        </form>

        <!-- Responsive Table -->
        <div class="overflow-x-auto min-h-[350px] pb-4">
            <table class="w-full min-w-[950px] text-left border-collapse">
                <thead>
                    <tr class="border-b border-border bg-gray-50/70 sticky top-0 z-10">
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 pl-3">Kode Nameplate</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Nomor Assy</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center">Suffix</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Tgl Produksi</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 font-mono text-center">Serial</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center">Sumber</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center">Status Edit</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Keterangan / Extra</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center pr-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nameplates as $np)
                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-red-50/20 transition-colors odd:bg-white even:bg-gray-50/40">
                            <td class="py-3.5 text-xs text-gray-900 font-mono font-bold px-4 pl-3">
                                {{ $np->nameplate_code }}
                                @if($np->formatted_code !== $np->nameplate_code)
                                    <span class="block text-[10px] text-gray-400 font-normal">({{ $np->formatted_code }})</span>
                                @endif
                            </td>
                            <td class="py-3.5 text-xs text-gray-800 font-mono font-semibold px-4">
                                {{ $np->assy_number ?? '-' }}
                            </td>
                            <td class="py-3.5 text-xs text-gray-700 px-4 text-center font-bold">
                                {{ $np->suffix ?? '-' }}
                            </td>
                            <td class="py-3.5 text-xs text-gray-700 px-4">
                                @if($np->production_date)
                                    {{ $np->production_date->format('d M Y') }}
                                    <span class="text-[10px] text-gray-400 block font-mono">({{ $np->date_code }})</span>
                                @else
                                    <span class="text-gray-400 font-mono">{{ $np->date_code ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 text-xs text-gray-800 font-mono font-bold text-center px-4">
                                {{ $np->serial_number ?? '-' }}
                            </td>
                            <td class="py-3.5 text-xs text-center px-4">
                                @if($np->source === 'import')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border border-emerald-200 bg-emerald-50 text-emerald-700">
                                        Import HTScan
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border border-blue-200 bg-blue-50 text-blue-700">
                                        Manual
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 text-xs text-center px-4">
                                @if($np->is_manual_edit)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border border-purple-200 bg-purple-50 text-purple-700">
                                        Diedit Manual
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] text-gray-400">
                                        Asli
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 text-xs text-gray-600 px-4">
                                {{ $np->keterangan ?? '-' }}
                                @if(!empty($np->raw_payload))
                                    <span class="text-[10px] font-mono text-gray-400 block truncate max-w-xs" title="{{ json_encode($np->raw_payload) }}">
                                        Extra: {{ count($np->raw_payload) }} kolom
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 text-center px-4 pr-3">
                                <div class="inline-flex items-center space-x-1.5">
                                    <button type="button"
                                            @click="editData = {
                                                id: {{ $np->id }},
                                                code: '{{ addslashes($np->nameplate_code) }}',
                                                assy: '{{ addslashes($np->assy_number ?? '') }}',
                                                serial: '{{ addslashes($np->serial_number ?? '') }}',
                                                keterangan: '{{ addslashes($np->keterangan ?? '') }}'
                                            }; editModal = true;"
                                            class="px-2.5 py-1 rounded bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold transition-all">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.master.nameplates.destroy', $np->id) }}" method="POST"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus nameplate ini?');"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded bg-red-50 hover:bg-red-100 text-brand text-xs font-bold transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-xs text-gray-400 font-medium">
                                Tidak ada data nameplate yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($nameplates->hasPages())
            <div class="mt-4 pt-4 border-t border-gray-100">
                {{ $nameplates->links() }}
            </div>
        @endif
    </section>

    <!-- Modal Tambah Nameplate Manual -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4" @click.away="addModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-900">Tambah Nameplate Manual</h3>
                <button type="button" @click="addModal = false" class="text-gray-400 hover:text-gray-600 text-sm">✕</button>
            </div>

            <form action="{{ route('admin.master.nameplates.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Kode Nameplate <span class="text-brand">*</span></label>
                    <input type="text" name="nameplate_code" required placeholder="Contoh: 82111VU020  A2607060008"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono font-bold focus:ring-1 focus:ring-[#8b0000]">
                    <span class="text-[10px] text-gray-400 mt-0.5 block">Kode otomatis diekstrak jika cocok dengan format standar.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Assy (Opsional Override)</label>
                    <input type="text" name="assy_number" placeholder="82111-VU020"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono focus:ring-1 focus:ring-[#8b0000]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Serial Number (Opsional Override)</label>
                    <input type="text" name="serial_number" placeholder="0008"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono focus:ring-1 focus:ring-[#8b0000]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan (Opsional)</label>
                    <textarea name="keterangan" rows="2" placeholder="Catatan tambahan..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-[#8b0000]"></textarea>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#8b0000] text-white text-xs font-bold hover:bg-[#600000]">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Nameplate -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4" @click.away="editModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-900">Edit Nameplate</h3>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600 text-sm">✕</button>
            </div>

            <form :action="'{{ url('/master/nameplates') }}/' + editData.id" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Kode Nameplate <span class="text-brand">*</span></label>
                    <input type="text" name="nameplate_code" x-model="editData.code" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono font-bold focus:ring-1 focus:ring-[#8b0000]">
                    <span class="text-[10px] text-purple-600 mt-0.5 block font-semibold">*Perubahan manual akan mengunci record agar tidak tertimpa impor otomatis.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Assy</label>
                    <input type="text" name="assy_number" x-model="editData.assy"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono focus:ring-1 focus:ring-[#8b0000]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Serial Number</label>
                    <input type="text" name="serial_number" x-model="editData.serial"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono focus:ring-1 focus:ring-[#8b0000]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan</label>
                    <textarea name="keterangan" rows="2" x-model="editData.keterangan"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-[#8b0000]"></textarea>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#8b0000] text-white text-xs font-bold hover:bg-[#600000]">
                        Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import File -->
    <div x-show="importModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4" @click.away="importModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-900">Import File Nameplate (HTScan JAI)</h3>
                <button type="button" @click="importModal = false" class="text-gray-400 hover:text-gray-600 text-sm">✕</button>
            </div>

            <form action="{{ route('admin.master.nameplates.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Pilih File CSV / Excel (.xlsx, .xls)</label>
                    <input type="file" name="file" required accept=".csv,.xlsx,.xls,.txt"
                           class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#8b0000] file:text-white hover:file:bg-[#600000]">
                    <p class="text-[11px] text-gray-500 mt-1">Ukuran maksimal file 10MB.</p>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-[11px] text-gray-600 space-y-1">
                    <div class="font-bold text-gray-800">Petunjuk Format File:</div>
                    <ul class="list-disc pl-4 space-y-0.5">
                        <li>Kolom utama: <code>nameplate_code</code> (atau <code>code</code> / <code>nameplate</code>).</li>
                        <li>Kolom tambahan akan disimpan otomatis ke <code>raw_payload</code> tanpa merusak struktur database.</li>
                        <li>Baris yang pernah diedit manual tidak akan ditimpa oleh file impor.</li>
                    </ul>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="importModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800">
                        Unggah &amp; Proses
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

