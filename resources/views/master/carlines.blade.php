<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Carline / Conveyor - IDRECT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen min-h-[100dvh] flex" 
      x-data="{ 
          editModal: false, 
          editId: null, 
          editArea: 'Final Assy', 
          editCarTypeId: '', 
          editName: '', 
          editIsUsed: false,
          addArea: '{{ old('jenis_assy', ($selectedArea === 'Pre Assy' ? 'Pre Assy' : 'Final Assy')) }}'
      }">

    @include('partials.sidebar')

    <!-- Main Content Area -->
    <main class="flex-1 min-w-0 px-4 sm:px-8 lg:px-10 py-6 sm:py-8 flex flex-col justify-start">
        
        <!-- Header -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Master Carline / Conveyor</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola data master carline Pre Assy dan conveyor Final Assy.</p>
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

        @if ($errors->any())
            <div class="mb-6 bg-red-50 text-[#8b0000] text-xs font-semibold p-4 rounded-lg border border-red-200">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Layout Grid: Form Tambah (Kiri) + Tabel List (Kanan) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Form Tambah Card -->
            <section class="bg-white border border-gray-100 rounded-lg p-6 shadow-sm h-fit">
                <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-gray-100">
                    <svg class="w-5 h-5 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    <h2 class="text-sm font-bold text-gray-900">Tambah Carline / Conveyor</h2>
                </div>

                <form action="{{ route('admin.master.carlines.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Pilihan Area -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Area <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center justify-center px-3 py-2.5 border rounded-lg cursor-pointer text-xs font-bold transition-all"
                                   :class="addArea === 'Final Assy' ? 'bg-red-50 border-[#8b0000] text-[#8b0000]' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="jenis_assy" value="Final Assy" x-model="addArea" class="sr-only">
                                <span>Final Assy</span>
                            </label>
                            <label class="flex items-center justify-center px-3 py-2.5 border rounded-lg cursor-pointer text-xs font-bold transition-all"
                                   :class="addArea === 'Pre Assy' ? 'bg-red-50 border-[#8b0000] text-[#8b0000]' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="jenis_assy" value="Pre Assy" x-model="addArea" class="sr-only">
                                <span>Pre Assy</span>
                            </label>
                        </div>
                    </div>

                    <!-- Jenis Mobil (Hanya Final Assy) -->
                    <div x-show="addArea === 'Final Assy'" x-transition>
                        <label for="car_type_id" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Jenis Mobil <span class="text-red-500">*</span></label>
                        <select id="car_type_id" name="car_type_id" :required="addArea === 'Final Assy'"
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                            <option value="">-- Pilih Jenis Mobil --</option>
                            @foreach ($carTypes as $ct)
                                <option value="{{ $ct->id }}" {{ old('car_type_id', $selectedCarType) == $ct->id ? 'selected' : '' }}>{{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nama Carline / Conveyor -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                            <span x-text="addArea === 'Final Assy' ? 'Nama Conveyor' : 'Nama Carline'">Nama Carline / Conveyor</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" :placeholder="addArea === 'Final Assy' ? 'Contoh: LINE 1, CONVEYOR A' : 'Contoh: NSN, D26A'" value="{{ old('name') }}" required
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white uppercase">
                    </div>

                    <button type="submit" class="w-full bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold py-3 rounded-lg transition duration-200 shadow-sm flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Simpan Data</span>
                    </button>
                </form>
            </section>

            <!-- Tabel List Card -->
            <section class="lg:col-span-2 bg-white border border-gray-100 rounded-lg p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <!-- Header Tabel + Filter -->
                    <div class="flex flex-col gap-4 mb-4 pb-3 border-b border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <h2 class="text-sm font-bold text-gray-900">Daftar Carline / Conveyor ({{ $carlines->total() }})</h2>
                            
                            <!-- Filter Jenis Mobil (Hanya jika bukan Pre Assy) -->
                            @if ($selectedArea !== 'Pre Assy')
                                <form method="GET" action="{{ route('admin.master.carlines.index') }}" class="flex items-center space-x-2">
                                    <input type="hidden" name="area" value="{{ $selectedArea }}">
                                    <label for="filter_car_type_id" class="text-xs font-semibold text-gray-500 shrink-0">Jenis Mobil:</label>
                                    <select id="filter_car_type_id" name="car_type_id" onchange="this.form.submit()"
                                        class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#8b0000] bg-white">
                                        <option value="">Semua Mobil</option>
                                        @foreach ($carTypes as $ct)
                                            <option value="{{ $ct->id }}" {{ $selectedCarType == $ct->id ? 'selected' : '' }}>{{ $ct->name }}</option>
                                        @endforeach
                                    </select>
                                    @if ($selectedCarType)
                                        <a href="{{ route('admin.master.carlines.index', ['area' => $selectedArea]) }}" class="text-xs text-gray-400 hover:text-red-700 ml-1">Reset</a>
                                    @endif
                                </form>
                            @endif
                        </div>

                        <!-- Tab Filter Area -->
                        <div class="flex items-center space-x-2 border-b border-gray-200 -mb-3 pb-2 text-xs font-bold">
                            <a href="{{ route('admin.master.carlines.index', ['area' => 'all', 'car_type_id' => $selectedCarType]) }}"
                               class="px-3 py-1.5 rounded-lg transition-colors {{ ($selectedArea === 'all' || !$selectedArea) ? 'bg-[#8b0000] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                Semua Area
                            </a>
                            <a href="{{ route('admin.master.carlines.index', ['area' => 'Final Assy', 'car_type_id' => $selectedCarType]) }}"
                               class="px-3 py-1.5 rounded-lg transition-colors {{ $selectedArea === 'Final Assy' ? 'bg-[#8b0000] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                Final Assy
                            </a>
                            <a href="{{ route('admin.master.carlines.index', ['area' => 'Pre Assy']) }}"
                               class="px-3 py-1.5 rounded-lg transition-colors {{ $selectedArea === 'Pre Assy' ? 'bg-[#8b0000] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                Pre Assy
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto min-h-[300px]">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 pl-2 w-12 text-center">No</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 w-28 text-center">Area</th>
                                    @if ($selectedArea !== 'Pre Assy')
                                        <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Jenis Mobil</th>
                                    @endif
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Carline / Conveyor</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 text-center pr-2 w-44">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($carlines as $index => $carline)
                                    @php
                                        $isPre = ($carline->jenis_assy === \App\Models\Carline::AREA_PRE_ASSY);
                                        $isUsed = in_array($carline->id, $usedCarlineIds, true);
                                    @endphp
                                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                        <!-- No -->
                                        <td class="py-3.5 text-xs text-gray-500 px-4 pl-2 font-medium text-center">
                                            {{ ($carlines->currentPage() - 1) * $carlines->perPage() + $loop->iteration }}
                                        </td>

                                        <!-- Badge Area -->
                                        <td class="py-3.5 px-4 text-center">
                                            @if($isPre)
                                                <span class="inline-block bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-extrabold px-2 py-0.5 rounded-full tracking-wider uppercase">
                                                    Pre Assy
                                                </span>
                                            @else
                                                <span class="inline-block bg-red-50 text-[#8b0000] border border-red-200 text-[10px] font-extrabold px-2 py-0.5 rounded-full tracking-wider uppercase">
                                                    Final Assy
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Kolom Jenis Mobil (Tampil kecuali di Tab Pre Assy) -->
                                        @if ($selectedArea !== 'Pre Assy')
                                            <td class="py-3.5 text-xs font-bold text-gray-800 px-4">
                                                @if($isPre)
                                                    <span class="text-xs text-gray-400 italic font-normal">Pre Assy (tanpa jenis mobil)</span>
                                                @else
                                                    <span class="inline-block bg-gray-100 text-gray-800 text-xs font-bold px-2.5 py-1 rounded-md tracking-wider">
                                                        {{ $carline->carType->name ?? '-' }}
                                                    </span>
                                                @endif
                                            </td>
                                        @endif

                                        <!-- Nama Carline / Conveyor -->
                                        <td class="py-3.5 text-sm font-semibold text-gray-900 px-4">
                                            <span>{{ $carline->name }}</span>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="py-3.5 text-center px-4 pr-2">
                                            <div class="inline-flex items-center space-x-1.5">
                                                <!-- Link ke Master No Mesin untuk Pre Assy -->
                                                @if($isPre)
                                                    <a href="{{ route('admin.master.pre_assy_machines.index', ['carline_id' => $carline->id]) }}" 
                                                       class="inline-flex items-center px-2 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition-colors"
                                                       title="Kelola Master No Mesin Pre Assy">
                                                        <span>Mesin</span>
                                                    </a>
                                                @endif

                                                <!-- Tombol Edit -->
                                                <button type="button" 
                                                    @click="editModal = true; 
                                                            editId = {{ $carline->id }}; 
                                                            editArea = '{{ $carline->jenis_assy }}'; 
                                                            editCarTypeId = '{{ $carline->car_type_id ?? '' }}'; 
                                                            editName = '{{ addslashes($carline->name) }}';
                                                            editIsUsed = {{ $isUsed ? 'true' : 'false' }};"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#8b0000] hover:bg-red-900 text-white text-xs font-semibold transition-colors">
                                                    Edit
                                                </button>

                                                <!-- Tombol Hapus -->
                                                <form action="{{ route('admin.master.carlines.destroy', $carline->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus carline ini?');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-100 hover:bg-red-200 text-red-700 text-xs font-semibold transition-colors">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $selectedArea === 'Pre Assy' ? 4 : 5 }}" class="py-10 text-center text-xs text-gray-400 font-medium">Belum ada data carline untuk filter ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($carlines->hasPages())
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            {{ $carlines->links() }}
                        </div>
                    @endif
                </div>
            </section>

        </div>

    </main>

    <!-- Modal Edit Carline -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100" @click.away="editModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Edit Carline / Conveyor</h3>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('/master/carlines') }}/' + editId" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- Area Edit -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Area <span class="text-red-500">*</span></label>
                    <select name="jenis_assy" x-model="editArea" :disabled="editIsUsed" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white disabled:bg-gray-100 disabled:text-gray-500">
                        <option value="Final Assy">Final Assy</option>
                        <option value="Pre Assy">Pre Assy</option>
                    </select>
                    <!-- Fallback hidden input when disabled so form submits value -->
                    <template x-if="editIsUsed">
                        <input type="hidden" name="jenis_assy" :value="editArea">
                    </template>
                    <p class="text-[11px] text-amber-700 mt-1" x-show="editIsUsed">
                        * Area dikunci karena carline ini sudah digunakan pada data terkait.
                    </p>
                </div>

                <!-- Jenis Mobil (Hanya jika Final Assy) -->
                <div x-show="editArea === 'Final Assy'" x-transition>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Jenis Mobil <span class="text-red-500">*</span></label>
                    <select name="car_type_id" x-model="editCarTypeId" :required="editArea === 'Final Assy'"
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                        <option value="">-- Pilih Jenis Mobil --</option>
                        @foreach ($carTypes as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Nama Carline / Conveyor -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                        <span x-text="editArea === 'Final Assy' ? 'Nama Conveyor' : 'Nama Carline'">Nama Carline / Conveyor</span>
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" x-model="editName" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] uppercase">
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-[#8b0000] hover:bg-red-900 rounded-lg transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
