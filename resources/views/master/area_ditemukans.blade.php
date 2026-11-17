@extends('layouts.app')

@section('title', 'Master Area Ditemukan')

@section('content')
<div x-data="{
    selectedCategory: 'all',
    // Form Tambah
    addKategori: '{{ !old('edit_id') ? old('kategori', \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY) : \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}',
    addTipe: '{{ !old('edit_id') ? old('tipe', \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT) : \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT }}',

    // Modal Edit
    editModal: {{ ($errors->any() && old('edit_id')) ? 'true' : 'false' }},
    editId: {{ old('edit_id', 'null') }},
    editName: '{{ old('edit_id') ? addslashes(old('name', '')) : '' }}',
    editKategori: '{{ old('edit_id') ? old('kategori', \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY) : \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}',
    editTipe: '{{ old('edit_id') ? old('tipe', \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT) : \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT }}',
    originalKategori: '{{ old('original_kategori', \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY) }}',
    originalTipe: '{{ old('original_tipe', \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT) }}',
    editDefectsCount: {{ old('edit_defects_count', 0) }},

    openEditModal(area) {
        this.editModal = true;
        this.editId = area.id;
        this.editName = area.name;
        this.editKategori = area.kategori;
        this.editTipe = area.tipe;
        this.originalKategori = area.kategori;
        this.originalTipe = area.tipe;
        this.editDefectsCount = area.defects_count;
    }
}">
    <!-- Header -->
    <header class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 leading-tight">Master Area Ditemukan</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola data master area ditemukan untuk Final Assy dan Pre Assy.</p>
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
                <h2 class="text-sm font-bold text-gray-900">Tambah Area Ditemukan</h2>
            </div>

            <form action="{{ route('admin.master.area_ditemukans.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Area <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ !old('edit_id') ? old('name') : '' }}" placeholder="Contoh: CK / V1 / Cutting" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white uppercase">
                    @if(!old('edit_id'))
                        @error('name')
                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label for="kategori" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Kategori Area <span class="text-red-500">*</span></label>
                    <select id="kategori" name="kategori" x-model="addKategori" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white text-gray-700 cursor-pointer">
                        <option value="{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}">Final Assy</option>
                        <option value="{{ \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY }}">Pre Assy</option>
                    </select>
                </div>

                <div x-show="addKategori === '{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}'">
                    <label for="tipe" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tipe Defect <span class="text-red-500">*</span></label>
                    <select id="tipe" name="tipe" x-model="addTipe"
                        :disabled="addKategori === '{{ \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY }}'"
                        :required="addKategori === '{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}'"
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white text-gray-700 cursor-pointer">
                        <option value="{{ \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT }}">Internal Defect</option>
                        <option value="{{ \App\Models\AreaDitemukan::TIPE_ASSEMBLY_DEFECT }}">Assembly Defect</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Tipe area menentukan klasifikasi defect Final Assy.</p>
                    @if(!old('edit_id'))
                        @error('tipe')
                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    @endif
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
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Daftar Area Ditemukan ({{ count($areas) }})</h2>
                    <div class="flex items-center space-x-1.5 bg-gray-100 p-1 rounded-lg text-xs font-semibold">
                        <button type="button" @click="selectedCategory = 'all'"
                            :class="selectedCategory === 'all' ? 'bg-white text-[#8b0000] shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1.5 rounded-md transition-all">Semua</button>
                        <button type="button" @click="selectedCategory = '{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}'"
                            :class="selectedCategory === '{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}' ? 'bg-white text-[#8b0000] shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1.5 rounded-md transition-all">Final Assy</button>
                        <button type="button" @click="selectedCategory = '{{ \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY }}'"
                            :class="selectedCategory === '{{ \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY }}' ? 'bg-white text-[#8b0000] shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1.5 rounded-md transition-all">Pre Assy</button>
                    </div>
                </div>

                <div class="overflow-x-auto min-h-[300px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 pl-2 w-12">No</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Nama Area</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Kategori Area</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Tipe Defect</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Data Defect</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 text-center pr-2 w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($areas as $index => $area)
                                <tr x-show="selectedCategory === 'all' || selectedCategory === '{{ $area->kategori }}'"
                                    class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 text-xs text-gray-500 px-4 pl-2 font-medium">{{ $index + 1 }}</td>
                                    <td class="py-3.5 text-sm font-bold text-gray-900 px-4">
                                        <span class="inline-block bg-gray-100 text-gray-800 text-xs font-bold px-2.5 py-1 rounded-md tracking-wider">
                                            {{ $area->name }}
                                        </span>
                                    </td>
                                    <!-- Badge Kategori Area (Final Assy / Pre Assy) -->
                                    <td class="py-3.5 px-4">
                                        @if($area->kategori === \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY)
                                            <span class="inline-block bg-sky-50 text-sky-700 border border-sky-200 text-xs font-bold px-2.5 py-0.5 rounded-full tracking-wide">
                                                Pre Assy
                                            </span>
                                        @else
                                            <span class="inline-block bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold px-2.5 py-0.5 rounded-full tracking-wide">
                                                Final Assy
                                            </span>
                                        @endif
                                    </td>
                                    <!-- Badge Tipe Defect (Internal Defect / Assembly Defect) -->
                                    <td class="py-3.5 px-4">
                                        @if($area->kategori === \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY)
                                            <span class="text-xs text-gray-400 font-semibold">-</span>
                                        @elseif($area->tipe === \App\Models\AreaDitemukan::TIPE_ASSEMBLY_DEFECT)
                                            <span class="inline-block bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold px-2.5 py-0.5 rounded-full tracking-wide">
                                                Assembly Defect
                                            </span>
                                        @else
                                            <span class="inline-block bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold px-2.5 py-0.5 rounded-full tracking-wide">
                                                Internal Defect
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-xs">
                                        @if($area->defects_count > 0)
                                            <span class="inline-flex items-center text-xs font-semibold text-gray-700 bg-gray-100 px-2.5 py-0.5 rounded">
                                                {{ $area->defects_count }} defect
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">0 defect</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 text-center px-4 pr-2">
                                        <div class="inline-flex items-center space-x-1.5">
                                            <!-- Tombol Edit -->
                                            <button type="button"
                                                @click="openEditModal({ id: {{ $area->id }}, name: '{{ addslashes($area->name) }}', kategori: '{{ $area->kategori }}', tipe: '{{ $area->tipe }}', defects_count: {{ $area->defects_count }} })"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-[#8b0000] hover:bg-red-900 text-white text-xs font-semibold transition-colors">
                                                Edit
                                            </button>
                                            <!-- Tombol Hapus -->
                                            @if($area->defects_count > 0)
                                                <button type="button" disabled title="Terkunci: area sudah digunakan pada {{ $area->defects_count }} data defect"
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-semibold cursor-not-allowed">
                                                    Hapus
                                                </button>
                                            @else
                                                <form action="{{ route('admin.master.area_ditemukans.destroy', $area->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus area ditemukan ini?');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-red-100 hover:bg-red-200 text-red-700 text-xs font-semibold transition-colors">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-xs text-gray-400 font-medium">Belum ada data area ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div>

    <!-- Modal Edit Area Ditemukan -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100" @click.away="editModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Edit Area Ditemukan</h3>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('/master/area-ditemukans') }}/' + editId" method="POST"
                @submit="if (editDefectsCount > 0 && (editKategori !== originalKategori || editTipe !== originalTipe)) { if (!confirm('Area ini memiliki ' + editDefectsCount + ' data defect terkait.\n\nPeringatan: Perubahan tipe atau kategori ini akan mempengaruhi klasifikasi data defect terkait di dashboard dan laporan.\n\nApakah Anda yakin ingin melanjutkan?')) { $event.preventDefault(); } }"
                class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_id" :value="editId">
                <input type="hidden" name="original_kategori" :value="originalKategori">
                <input type="hidden" name="original_tipe" :value="originalTipe">
                <input type="hidden" name="edit_defects_count" :value="editDefectsCount">

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Area <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="editName" required
                        :readonly="editDefectsCount > 0"
                        :class="editDefectsCount > 0 ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-900'"
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] uppercase">
                    <p x-show="editDefectsCount > 0" class="text-[11px] text-amber-600 mt-1 font-medium">
                        Terkunci: Nama area tidak dapat diubah karena sudah digunakan pada <span x-text="editDefectsCount"></span> data defect.
                    </p>
                    @if(old('edit_id'))
                        @error('name')
                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Kategori Area <span class="text-red-500">*</span></label>
                    <select name="kategori" x-model="editKategori" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white text-gray-700 cursor-pointer">
                        <option value="{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}">Final Assy</option>
                        <option value="{{ \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY }}">Pre Assy</option>
                    </select>
                </div>

                <div x-show="editKategori === '{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}'">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tipe Defect <span class="text-red-500">*</span></label>
                    <select name="tipe" x-model="editTipe"
                        :disabled="editKategori === '{{ \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY }}'"
                        :required="editKategori === '{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}'"
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white text-gray-700 cursor-pointer">
                        <option value="{{ \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT }}">Internal Defect</option>
                        <option value="{{ \App\Models\AreaDitemukan::TIPE_ASSEMBLY_DEFECT }}">Assembly Defect</option>
                    </select>
                    @if(old('edit_id'))
                        @error('tipe')
                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
                <div x-show="editDefectsCount > 0 && (editKategori !== originalKategori || (editKategori === '{{ \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY }}' && editTipe !== originalTipe))" class="mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                    <strong>Peringatan:</strong> Area ini memiliki <span x-text="editDefectsCount"></span> data defect. Mengubah tipe atau kategori akan mempengaruhi filter dan laporan.
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-[#8b0000] hover:bg-red-900 rounded-lg transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
