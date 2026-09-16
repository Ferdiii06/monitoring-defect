<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Defect & Sub-Defect - Sistem Monitoring Defect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 h-screen overflow-hidden flex" x-data="{ 
    editModal: false, 
    editId: null, 
    editName: '', 
    editType: 'Final Assy',
    expanded: {} 
}">

    @include('partials.sidebar')

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto px-10 py-8 flex flex-col justify-start">
        
        <!-- Header -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Master Defect & Sub-Defect</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola data master kategori defect dan sub-defect untuk Final Assy & Pre Assy.</p>
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
            
            <!-- Form Tambah Defect Type Card -->
            <section class="bg-white border border-gray-100 rounded-lg p-6 shadow-sm h-fit">
                <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-gray-100">
                    <svg class="w-5 h-5 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    <h2 class="text-sm font-bold text-gray-900">Tambah Defect Type</h2>
                </div>

                <form action="{{ route('admin.master.defect_types.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="type" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tipe Proses <span class="text-red-500">*</span></label>
                        <select id="type" name="type" required
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                            <option value="Final Assy" {{ old('type', $filterType) === 'Final Assy' ? 'selected' : '' }}>Final Assy</option>
                            <option value="Pre Assy" {{ old('type', $filterType) === 'Pre Assy' ? 'selected' : '' }}>Pre Assy</option>
                        </select>
                    </div>

                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Defect Type <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" placeholder="Contoh: WIRING, CONNECTOR, TERMINAL" value="{{ old('name') }}" required
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white uppercase">
                    </div>

                    <button type="submit" class="w-full bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold py-3 rounded-lg transition duration-200 shadow-sm flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Simpan Defect Type</span>
                    </button>
                </form>
            </section>

            <!-- Tabel List Card -->
            <section class="lg:col-span-2 bg-white border border-gray-100 rounded-lg p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">Daftar Defect Type ({{ count($defectTypes) }})</h2>
                        
                        <!-- Filter Tipe Proses -->
                        <div class="inline-flex rounded-lg border border-gray-200 p-0.5 bg-gray-50 text-xs">
                            <a href="{{ route('admin.master.defect_types.index') }}" 
                               class="px-3 py-1 rounded-md font-semibold transition-all {{ empty($filterType) ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                                Semua
                            </a>
                            <a href="{{ route('admin.master.defect_types.index', ['type' => 'Final Assy']) }}" 
                               class="px-3 py-1 rounded-md font-semibold transition-all {{ $filterType === 'Final Assy' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                                Final Assy
                            </a>
                            <a href="{{ route('admin.master.defect_types.index', ['type' => 'Pre Assy']) }}" 
                               class="px-3 py-1 rounded-md font-semibold transition-all {{ $filterType === 'Pre Assy' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                                Pre Assy
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto min-h-[300px]">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="w-8 pb-3 px-2"></th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 pl-2 w-12">No</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 w-28">Tipe</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Nama Defect</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 text-center w-36">Sub-Defect</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 text-center pr-2 w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($defectTypes as $index => $dt)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                        <!-- Expand Toggle Button -->
                                        <td class="py-3.5 px-2 text-center">
                                            <button type="button" @click="expanded[{{ $dt->id }}] = !expanded[{{ $dt->id }}]" class="p-1 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                                                <svg class="w-4 h-4 transform transition-transform duration-200" :class="expanded[{{ $dt->id }}] ? 'rotate-90 text-[#8b0000]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="py-3.5 text-xs text-gray-500 px-4 pl-2 font-medium">{{ $index + 1 }}</td>
                                        <td class="py-3.5 text-xs px-4 font-semibold">
                                            @if ($dt->type === 'Final Assy')
                                                <span class="inline-block bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[11px] font-bold">Final Assy</span>
                                            @else
                                                <span class="inline-block bg-amber-50 text-amber-700 px-2 py-0.5 rounded text-[11px] font-bold">Pre Assy</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 text-sm font-bold text-gray-900 px-4">
                                            {{ $dt->name }}
                                        </td>
                                        <td class="py-3.5 text-xs text-gray-700 px-4 text-center font-semibold">
                                            <button type="button" @click="expanded[{{ $dt->id }}] = !expanded[{{ $dt->id }}]" 
                                                class="inline-flex items-center space-x-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-2.5 py-1 rounded-full text-xs font-bold transition-colors">
                                                <span>{{ $dt->subDefectTypes->count() }} Sub-Defect</span>
                                                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </button>
                                        </td>
                                        <td class="py-3.5 text-center px-4 pr-2">
                                            <div class="inline-flex items-center space-x-1.5">
                                                <!-- Tombol Edit -->
                                                <button type="button" 
                                                    @click="editModal = true; editId = {{ $dt->id }}; editName = '{{ addslashes($dt->name) }}'; editType = '{{ $dt->type }}'"
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-[#8b0000] hover:bg-red-900 text-white text-xs font-semibold transition-colors">
                                                    Edit
                                                </button>
                                                <!-- Tombol Hapus -->
                                                <form action="{{ route('admin.master.defect_types.destroy', $dt->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Defect Type ini? Semua sub-defect terkait juga akan terhapus.');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-red-100 hover:bg-red-200 text-red-700 text-xs font-semibold transition-colors">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Accordion Baris Sub-Defect -->
                                    <tr x-show="expanded[{{ $dt->id }}]" x-cloak class="bg-gray-50/80 border-b border-gray-200">
                                        <td colspan="6" class="p-5 pl-14">
                                            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                                                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-3">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-2 h-2 rounded-full bg-[#8b0000]"></span>
                                                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Sub-Defect untuk: <span class="text-[#8b0000]">{{ $dt->name }}</span> ({{ $dt->type }})</h3>
                                                    </div>
                                                    <span class="text-[11px] text-gray-500 font-medium">Total: {{ $dt->subDefectTypes->count() }} item</span>
                                                </div>

                                                <!-- Tabel Mini Sub-Defect -->
                                                @if ($dt->subDefectTypes->count() > 0)
                                                    <div class="overflow-x-auto mb-4">
                                                        <table class="w-full text-left border-collapse text-xs">
                                                            <thead>
                                                                <tr class="border-b border-gray-100 text-gray-400 font-semibold">
                                                                    <th class="py-2 px-3 w-10">#</th>
                                                                    <th class="py-2 px-3">Nama Sub-Defect</th>
                                                                    <th class="py-2 px-3 text-center w-24">Aksi</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-50">
                                                                @foreach ($dt->subDefectTypes as $subIndex => $sub)
                                                                    <tr class="hover:bg-gray-50">
                                                                        <td class="py-2 px-3 text-gray-400 font-medium">{{ $subIndex + 1 }}</td>
                                                                        <td class="py-2 px-3 font-semibold text-gray-800">{{ $sub->name }}</td>
                                                                        <td class="py-2 px-3 text-center">
                                                                            <form action="{{ route('admin.master.defect_types.sub.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('Hapus sub-defect ini?');" class="inline-block">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="text-red-600 hover:text-red-800 font-bold hover:underline text-xs">
                                                                                    Hapus
                                                                                </button>
                                                                            </form>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <p class="text-xs text-gray-400 italic mb-4">Belum ada sub-defect untuk jenis defect ini.</p>
                                                @endif

                                                <!-- Form Tambah Sub-Defect Inline -->
                                                <form action="{{ route('admin.master.defect_types.sub.store', $dt->id) }}" method="POST" class="pt-3 border-t border-gray-100 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                    @csrf
                                                    <div class="flex-1">
                                                        <input type="text" name="name" placeholder="Tambah nama sub-defect baru..." required
                                                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white uppercase">
                                                    </div>
                                                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-bold px-4 py-2 rounded-lg transition duration-200 shrink-0 flex items-center justify-center space-x-1.5 shadow-sm">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                        <span>Tambah Sub-Defect</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-xs text-gray-400 font-medium">Belum ada data defect type.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>

    </main>

    <!-- Modal Edit Defect Type -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100" @click.away="editModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Edit Defect Type</h3>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('/master/defect-types') }}/' + editId" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tipe Proses <span class="text-red-500">*</span></label>
                    <select name="type" x-model="editType" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                        <option value="Final Assy">Final Assy</option>
                        <option value="Pre Assy">Pre Assy</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Defect Type <span class="text-red-500">*</span></label>
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
