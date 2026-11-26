@extends('layouts.app')

@section('title', 'Master No Mesin Pre Assy')

@section('content')
<div x-data="{ editModal: false, editId: null, editName: '', importModal: false }">
    <!-- Header -->
    <header class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 leading-tight">Master No Mesin (Pre Assy)</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola data master nomor mesin khusus Pre Assy per Carline.</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="text-right">
                <span class="block text-sm font-bold text-gray-900">{{ session('user_name', 'Admin QA') }}</span>
                <span class="block text-xs font-semibold text-gray-400">Administrator</span>
            </div>
            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 border border-gray-200">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
            </div>
        </div>
    </header>

    <!-- Alerts -->
    @if (session('success'))
        <div class="mb-6 bg-green-50 text-green-700 text-sm font-semibold p-4 rounded-lg border border-green-200 flex items-center space-x-2 shadow-sm">
            <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-6 bg-amber-50 text-amber-800 text-sm font-semibold p-4 rounded-lg border border-amber-200 flex items-center space-x-2 shadow-sm">
            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if (session('import_summary') && !empty(session('import_summary')['errors']))
        <div class="mb-6 bg-amber-50 text-amber-900 text-xs p-4 rounded-lg border border-amber-200 shadow-sm">
            <p class="font-bold mb-2">
                Ringkasan Import: {{ session('import_summary')['success'] }} berhasil, {{ session('import_summary')['skipped'] }} baris dilewati:
            </p>
            <ul class="list-disc pl-5 space-y-1 max-h-40 overflow-y-auto">
                @foreach (session('import_summary')['errors'] as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="mb-6 bg-red-50 text-[#8b0000] text-xs font-semibold p-4 rounded-lg border border-red-200">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Selector Carline Pre Assy -->
    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm mb-6">
        <form method="GET" action="{{ route('admin.master.pre_assy_machines.index') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center space-x-3">
                <label for="selectCarline" class="text-xs font-bold text-gray-700 uppercase tracking-wider shrink-0">Pilih Carline Pre Assy:</label>
                <select id="selectCarline" name="carline_id" onchange="this.form.submit()"
                        class="border border-gray-200 rounded-lg px-3.5 py-2 text-sm font-bold text-gray-900 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white cursor-pointer min-w-[200px]">
                    @forelse($carlines as $cl)
                        <option value="{{ $cl->id }}" {{ (string)$selectedCarlineId === (string)$cl->id ? 'selected' : '' }}>
                            {{ $cl->name }}
                        </option>
                    @empty
                        <option value="">Belum ada carline Pre Assy</option>
                    @endforelse
                </select>
            </div>
            @if($selectedCarline)
                <span class="text-xs text-gray-500 font-medium">
                    Menampilkan mesin untuk carline: <strong class="text-gray-900">{{ $selectedCarline->name }}</strong>
                </span>
            @endif
        </form>
    </div>

    <!-- Layout Grid: Form Tambah (Kiri) + Tabel List (Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Tambah Card -->
        <section class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm h-fit">
            <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-gray-100">
                <svg class="w-5 h-5 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                <h2 class="text-sm font-bold text-gray-900">Tambah No Mesin</h2>
            </div>

            @if($selectedCarline)
                <form action="{{ route('admin.master.pre_assy_machines.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="carline_id" value="{{ $selectedCarline->id }}">

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Carline Terpilih</label>
                        <div class="p-2.5 rounded-lg bg-gray-50 border border-gray-200 text-xs font-bold text-gray-800">
                            {{ $selectedCarline->name }}
                        </div>
                    </div>

                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                            Nomor / Nama Mesin <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" placeholder="Contoh: MC-01, BON-02" value="{{ old('name') }}" required
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white font-mono uppercase">
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked class="rounded border-gray-300 text-[#8b0000] focus:ring-[#8b0000]">
                        <label for="is_active" class="text-xs font-semibold text-gray-700 cursor-pointer">Status Aktif (Tampil di form operator)</label>
                    </div>

                    <button type="submit" class="w-full bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold py-3 rounded-lg transition duration-200 shadow-sm flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Simpan No Mesin</span>
                    </button>
                </form>
            @else
                <p class="text-xs text-gray-400 italic">Pilih atau buat carline Pre Assy terlebih dahulu.</p>
            @endif
        </section>

        <!-- Tabel List Card -->
        <section class="lg:col-span-2 bg-white border border-gray-100 rounded-xl p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">
                        Daftar No Mesin - {{ $selectedCarline?->name ?? 'Belum Dipilih' }} ({{ count($machines) }})
                    </h2>
                    @if($selectedCarline)
                        <button type="button" @click="importModal = true"
                            class="inline-flex items-center space-x-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3 py-2 rounded-lg transition duration-200 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            <span>Import Excel</span>
                        </button>
                    @endif
                </div>

                <div class="overflow-x-auto min-h-[300px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50/50">
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider py-3 px-4 pl-2 w-14">No</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider py-3 px-4">Nomor Mesin</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider py-3 px-4 text-center w-28">Status</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider py-3 px-4 text-center pr-2 w-44">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($machines as $index => $machine)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 text-xs text-gray-500 px-4 pl-2 font-medium">{{ $index + 1 }}</td>
                                    <td class="py-3.5 text-sm font-bold text-gray-900 px-4">
                                        <span class="inline-block bg-gray-100 text-gray-800 text-xs font-mono font-bold px-2.5 py-1 rounded-md tracking-wider">
                                            {{ $machine->name }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 text-center px-4">
                                        @if($machine->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-green-50 text-green-700 border border-green-200">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 text-center px-4 pr-2">
                                        <div class="inline-flex items-center space-x-1.5">
                                            <!-- Toggle Status -->
                                            <form action="{{ route('admin.master.pre_assy_machines.toggle', $machine->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        title="{{ $machine->is_active ? 'Nonaktifkan mesin' : 'Aktifkan mesin' }}"
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold transition-colors {{ $machine->is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                                                    {{ $machine->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>

                                            <!-- Tombol Edit Modal -->
                                            <button type="button" 
                                                @click="editModal = true; editId = {{ $machine->id }}; editName = '{{ addslashes($machine->name) }}'"
                                                class="inline-flex items-center px-2.5 py-1 rounded bg-[#8b0000] hover:bg-red-900 text-white text-xs font-semibold transition-colors">
                                                Edit
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('admin.master.pre_assy_machines.destroy', $machine->id) }}" method="POST" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus nomor mesin {{ addslashes($machine->name) }}?');" 
                                                  class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-2 py-1 rounded bg-gray-100 hover:bg-red-50 text-gray-600 hover:text-red-700 text-xs font-semibold transition-colors">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-xs text-gray-400">
                                        Belum ada data nomor mesin untuk carline ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- Edit Modal (Alpine.js) -->
    <div x-show="editModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-gray-100"
             @click.away="editModal = false">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-900">Edit Nomor Mesin</h3>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('/master/pre-assy-machines') }}/' + editId" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="edit_name" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                        Nomor / Nama Mesin <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_name" name="name" x-model="editName" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white font-mono uppercase">
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="editModal = false" class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg transition duration-200">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold py-2.5 rounded-lg transition duration-200 shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Excel -->
    @if($selectedCarline)
    <div x-show="importModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
        <div @click.away="importModal = false" class="bg-white rounded-xl shadow-xl border border-gray-100 w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-900">Import Excel No Mesin</h3>
                <button type="button" @click="importModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('admin.master.pre_assy_machines.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="carline_id" value="{{ $selectedCarline->id }}">

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Carline Tujuan</label>
                    <div class="p-2.5 rounded-lg bg-gray-50 border border-gray-200 text-xs font-bold text-gray-800">
                        {{ $selectedCarline->name }}
                    </div>
                </div>

                <div class="flex items-center justify-between bg-blue-50 border border-blue-100 rounded-lg p-3">
                    <span class="text-xs text-blue-800 font-medium">Gunakan template resmi (kolom: <strong>No Mesin</strong>).</span>
                    <a href="{{ route('admin.master.pre_assy_machines.template') }}"
                       class="text-xs font-bold text-blue-700 hover:text-blue-900 underline shrink-0 ml-2">
                        Unduh Template
                    </a>
                </div>

                <div>
                    <label for="importFile" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                        Pilih File Excel (.xlsx / .xls) <span class="text-red-500">*</span>
                    </label>
                    <input type="file" id="importFile" name="file" accept=".xlsx,.xls" required
                        class="w-full border border-gray-200 rounded-lg p-2.5 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                    <p class="text-[11px] text-gray-400 mt-1">Maksimal 2 MB dan 1.000 baris data per file.</p>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="importModal = false" class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-lg transition duration-200">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 rounded-lg transition duration-200 shadow-sm">
                        Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
