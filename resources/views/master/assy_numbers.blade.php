<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Assy Number - IDRECT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 h-screen overflow-hidden flex" 
      x-data="{ 
          createModal: false, 
          editModal: false, 
          importModal: false,
          editId: null, 
          editAssyNumber: '', 
          editCarlineId: '', 
          editKeterangan: '' 
      }">

    @include('partials.sidebar')

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto px-10 py-8 flex flex-col justify-start">
        
        <!-- Header -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Master Kanban</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola data master kanban / nomor assy, pemetaan carline / konveyor, dan import Excel/CSV.</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.master.kanban_format.index') }}"
                   class="px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-lg transition duration-200 shadow-sm flex items-center space-x-2 border border-gray-300">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Atur Format Urutan</span>
                </a>
                <button type="button" @click="importModal = true"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition duration-200 shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Import Excel / CSV</span>
                </button>
                <button type="button" @click="createModal = true"
                        class="px-4 py-2.5 bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold rounded-lg transition duration-200 shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Tambah Assy Number</span>
                </button>
            </div>
        </header>

        <!-- Alerts -->
        @if (session('success'))
            <div class="mb-4 bg-green-50 text-green-700 text-sm font-semibold p-4 rounded-lg border border-green-200 flex items-center space-x-2 shadow-sm">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-50 text-[#8b0000] text-xs font-semibold p-4 rounded-lg border border-red-200 shadow-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Ringkasan Import (jika baru selesai import) -->
        @if (session('import_summary'))
            @php $summary = session('import_summary'); @endphp
            <div class="mb-6 p-4 rounded-xl border {{ $summary['skipped'] > 0 ? 'bg-amber-50/70 border-amber-200 text-amber-900' : 'bg-emerald-50/70 border-emerald-200 text-emerald-900' }} shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-lg {{ $summary['skipped'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center font-bold">
                            @if ($summary['skipped'] > 0)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            @endif
                        </div>
                        <div>
                            <h4 class="text-sm font-bold">Ringkasan Hasil Import</h4>
                            <p class="text-xs text-gray-600 mt-0.5">
                                <span class="font-semibold text-emerald-700">{{ $summary['success'] }} baris berhasil diproses</span>
                                @if ($summary['skipped'] > 0)
                                    &bull; <span class="font-semibold text-amber-700">{{ $summary['skipped'] }} baris dilewati</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                @if ($summary['skipped'] > 0 && !empty($summary['errors']))
                    <details class="mt-4 pt-3 border-t border-amber-200/70 text-xs">
                        <summary class="cursor-pointer font-bold text-amber-900 hover:text-amber-950 select-none flex items-center space-x-1">
                            <span>Lihat rincian {{ count($summary['errors']) }} baris yang dilewati</span>
                        </summary>
                        <div class="mt-2 max-h-60 overflow-y-auto rounded-lg border border-amber-200 bg-white shadow-inner">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-amber-100/60 text-amber-900 font-bold sticky top-0">
                                    <tr>
                                        <th class="py-2 px-3 w-16">Baris</th>
                                        <th class="py-2 px-3 w-40">Nomor Assy</th>
                                        <th class="py-2 px-3">Alasan Dilewati</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                                    @foreach ($summary['errors'] as $err)
                                        <tr class="hover:bg-amber-50/50">
                                            <td class="py-2 px-3 text-center text-gray-500 font-mono">{{ $err['row'] ?? '-' }}</td>
                                            <td class="py-2 px-3 font-mono font-bold">{{ $err['assy_number'] ?? '-' }}</td>
                                            <td class="py-2 px-3 text-red-600">{{ $err['reason'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endif
            </div>
        @endif

        <!-- Search & Filter Card -->
        <section class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm mb-6">
            <form method="GET" action="{{ route('admin.master.assy_numbers.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nomor assy, carline, keterangan..."
                               class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                    </div>

                    <!-- Carline Filter Dropdown -->
                    <div class="w-full sm:w-64">
                        <select name="carline_id" 
                                class="w-full border border-gray-200 rounded-lg py-2 px-3 text-xs focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                            <option value="">Semua Carline / Konveyor</option>
                            @foreach ($carlines as $c)
                                <option value="{{ $c->id }}" {{ (string)$selectedCarline === (string)$c->id ? 'selected' : '' }}>
                                    {{ $c->name }} {{ $c->carType ? '('.$c->carType->name.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Submit Filter Button -->
                    <button type="submit" 
                            class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold rounded-lg transition-colors shrink-0">
                        Filter
                    </button>

                    <!-- Reset Filter Button -->
                    @if ($search !== '' || filled($selectedCarline))
                        <a href="{{ route('admin.master.assy_numbers.index') }}" 
                           class="px-3 py-2 text-xs font-semibold text-gray-500 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center justify-center shrink-0">
                            Reset
                        </a>
                    @endif
                </div>

                <div class="text-xs text-gray-400 text-right shrink-0">
                    Total: <span class="font-bold text-gray-700">{{ $assyNumbers->total() }}</span> data
                </div>
            </form>
        </section>

        <!-- Tabel List Card -->
        <section class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm flex flex-col justify-between flex-1">
            <div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nomor Assy</th>
                                <th class="py-3 px-4">Carline / Konveyor</th>
                                <th class="py-3 px-4">Jenis Mobil</th>
                                <th class="py-3 px-4">Keterangan</th>
                                <th class="py-3 px-4 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs">
                            @forelse ($assyNumbers as $index => $item)
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="py-3.5 px-4 text-center font-medium text-gray-400">
                                        {{ $assyNumbers->firstItem() + $index }}
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-900 tracking-wide">
                                        {{ $item->assy_number }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if ($item->carline)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-red-50 text-[#8b0000] border border-red-100">
                                                {{ $item->carline->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-medium text-gray-600">
                                        {{ $item->carline?->carType?->name ?? '—' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-500">
                                        {{ $item->keterangan ?? '—' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button type="button" 
                                                    @click="
                                                        editId = {{ $item->id }}; 
                                                        editAssyNumber = '{{ addslashes($item->assy_number) }}'; 
                                                        editCarlineId = '{{ $item->carline_id ?? '' }}'; 
                                                        editKeterangan = '{{ addslashes($item->keterangan ?? '') }}'; 
                                                        editModal = true;
                                                    " 
                                                    class="text-blue-600 hover:text-blue-800 font-semibold cursor-pointer">
                                                Edit
                                            </button>
                                            <span class="text-gray-300">|</span>
                                            <form action="{{ route('admin.master.assy_numbers.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus nomor assy {{ $item->assy_number }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-semibold cursor-pointer">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-xs text-gray-400 font-medium">
                                        @if ($search !== '' || filled($selectedCarline))
                                            Tidak ada data assy number yang cocok dengan filter pencarian.
                                        @else
                                            Belum ada data assy number. Klik tombol "Tambah Assy Number" atau "Import Excel / CSV" untuk menambahkan.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($assyNumbers->hasPages())
                    <div class="mt-4 pt-3 border-t border-gray-100">
                        {{ $assyNumbers->links() }}
                    </div>
                @endif
            </div>
        </section>

    </main>

    <!-- Modal Tambah Assy Number -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100" @click.away="createModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Tambah Assy Number</h3>
                <button type="button" @click="createModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('admin.master.assy_numbers.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nomor Assy <span class="text-red-500">*</span></label>
                    <input type="text" name="assy_number" placeholder="Contoh: 82111-VU020" required
                           class="w-full border border-gray-200 rounded-lg p-3 text-sm font-mono uppercase focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                    <span class="block text-[11px] text-gray-400 mt-1">Bisa format kanban (ada tanda '-') atau QR nameplate (tanpa strip).</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Carline / Konveyor</label>
                    <select name="carline_id"
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                        <option value="">-- Pilih Carline / Konveyor (Opsional) --</option>
                        @foreach ($carlines as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} {{ $c->carType ? '('.$c->carType->name.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Keterangan</label>
                    <input type="text" name="keterangan" placeholder="Contoh: Main Harness RHD"
                           class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-[#8b0000] hover:bg-red-900 rounded-lg transition-colors">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Assy Number -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100" @click.away="editModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Edit Assy Number</h3>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('/master/assy-numbers') }}/' + editId" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nomor Assy <span class="text-red-500">*</span></label>
                    <input type="text" name="assy_number" x-model="editAssyNumber" required
                           class="w-full border border-gray-200 rounded-lg p-3 text-sm font-mono uppercase focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Carline / Konveyor</label>
                    <select name="carline_id" x-model="editCarlineId"
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                        <option value="">-- Pilih Carline / Konveyor (Opsional) --</option>
                        @foreach ($carlines as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} {{ $c->carType ? '('.$c->carType->name.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Keterangan</label>
                    <input type="text" name="keterangan" x-model="editKeterangan"
                           class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-[#8b0000] hover:bg-red-900 rounded-lg transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Excel / CSV -->
    <div x-show="importModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100" @click.away="importModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Import Assy Number</h3>
                </div>
                <button type="button" @click="importModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('admin.master.assy_numbers.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Pilih File Excel / CSV <span class="text-red-500">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                           class="w-full border border-gray-200 rounded-lg p-2.5 text-xs text-gray-600 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                </div>

                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-[11px] text-gray-600 space-y-1.5">
                    <div class="font-bold text-gray-800">Petunjuk Format File:</div>
                    <p>&bull; Format file didukung: <span class="font-semibold text-gray-700">.xlsx, .xls, .csv</span></p>
                    <p>&bull; Header kolom yang dikenali: <span class="font-mono font-semibold text-gray-800">assy_number</span> (wajib), <span class="font-mono font-semibold text-gray-800">carline</span> (opsional, cocokkan nama carline), <span class="font-mono font-semibold text-gray-800">keterangan</span> (opsional).</p>
                    <p>&bull; Import bersifat <span class="font-semibold text-gray-800">idempotent</span>: jika assy number sudah ada, datanya akan diperbarui.</p>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="importModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center space-x-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Mulai Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>