@extends('layouts.app')

@section('title', 'Master Target DPM')

@section('content')
<div x-data="dpmTargetsPage()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <header class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">Master Target DPM</h1>
            <p class="text-xs font-semibold text-gray-500 mt-1">Kelola batas target Defect Per Million (DPM) per periode untuk Final Assy dan Pre Assy.</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="text-right">
                <span class="block text-xs font-extrabold text-gray-900">{{ session('user_name', 'Admin QA') }}</span>
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Administrator</span>
            </div>
            <div class="w-9 h-9 rounded-xl bg-red-50 text-brand flex items-center justify-center border border-red-100 font-black text-xs">
                {{ strtoupper(substr(session('user_name', 'A'), 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- Alerts -->
    @if (session('success'))
        <div class="bg-emerald-50 text-emerald-800 text-xs font-bold p-4 rounded-2xl border border-emerald-200 flex items-center space-x-2 shadow-xs">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="bg-red-50 text-[#8b0000] text-xs font-semibold p-4 rounded-2xl border border-red-200 shadow-xs">
            <div class="flex items-center space-x-2 font-bold mb-1">
                <svg class="w-4 h-4 text-[#8b0000] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>Terjadi kesalahan validasi:</span>
            </div>
            <ul class="list-disc pl-6 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Layout Grid: Form Tambah (Kiri) + Tabel List Ber-tab (Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Form Tambah Target Card -->
        <section class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-gray-100">
                <span class="w-2.5 h-2.5 rounded-full bg-[#8b0000]"></span>
                <h2 class="text-sm font-extrabold text-gray-900 tracking-tight">Tambah Target DPM</h2>
            </div>

            <form action="{{ route('admin.master.dpm_targets.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- 1. Jenis Assy -->
                <div>
                    <label for="add_jenis_assy" class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                        Jenis Assy <span class="text-red-500">*</span>
                    </label>
                    <select id="add_jenis_assy" name="jenis_assy" x-model="addForm.jenis_assy" @change="onAssyChange(addForm)" required
                        class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                        <option value="Final Assy">Final Assy</option>
                        <option value="Pre Assy">Pre Assy</option>
                    </select>
                </div>

                <!-- 1.1 Carline (Opsional, Default Semua Carline) -->
                <div>
                    <label for="add_carline_id" class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                        Carline
                    </label>
                    <select id="add_carline_id" name="carline_id" x-model="addForm.carline_id"
                        class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                        <option value="">Semua Carline</option>
                        <template x-for="c in filteredCarlines(addForm.jenis_assy)" :key="c.id">
                            <option :value="c.id" x-text="formatCarlineOption(c)"></option>
                        </template>
                    </select>
                </div>

                <!-- 2. Rentang Tanggal (Mulai & Akhir) -->
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                            Periode Target <span class="text-red-500">*</span>
                        </label>
                        <!-- Quick Fill Buttons -->
                        <div class="inline-flex space-x-1">
                            <button type="button" @click="setQuickPeriod(addForm, 'month')"
                                class="px-2 py-0.5 text-[10px] font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition shadow-2xs"
                                title="Isi otomatis 1 bulan berdasarkan tanggal mulai">
                                +1 Bulan
                            </button>
                            <button type="button" @click="setQuickPeriod(addForm, 'year')"
                                class="px-2 py-0.5 text-[10px] font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition shadow-2xs"
                                title="Isi otomatis 1 tahun berdasarkan tanggal mulai">
                                +1 Tahun
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="block text-[10px] font-semibold text-gray-400 mb-1">Mulai</span>
                            <input type="date" id="add_period_start" name="period_start" x-model="addForm.period_start" required
                                class="w-full border border-gray-200 rounded-xl p-2 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition shadow-xs">
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold text-gray-400 mb-1">Selesai</span>
                            <input type="date" id="add_period_end" name="period_end" x-model="addForm.period_end" required
                                class="w-full border border-gray-200 rounded-xl p-2 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition shadow-xs">
                        </div>
                    </div>
                </div>

                <!-- 3. Target DPM / PPM -->
                <div>
                    <label for="add_target_dpm" class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                        <span x-text="addForm.jenis_assy === 'Pre Assy' ? 'Target PPM' : 'Target DPM'">Target DPM</span> <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" id="add_target_dpm" name="target_dpm" x-model="addForm.target_dpm"
                            :placeholder="addForm.jenis_assy === 'Pre Assy' ? 'Contoh: 15.00 PPM' : 'Contoh: 15.00 DPM'" placeholder="Contoh: 15.00" required
                            class="w-full border border-gray-200 rounded-xl p-2.5 pr-14 text-xs font-mono font-bold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition shadow-xs">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-bold text-gray-400"
                            x-text="addForm.jenis_assy === 'Pre Assy' ? 'PPM' : 'DPM'">
                            DPM
                        </span>
                    </div>
                    <p class="text-[10px] font-medium text-gray-400 mt-1">Batas toleransi cacat per satu juta pcs.</p>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold py-2.5 px-4 rounded-xl transition duration-150 shadow-sm shadow-[#8b0000]/20 flex items-center justify-center space-x-1.5 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Simpan Target DPM</span>
                </button>
            </form>
        </section>

        <!-- Tabel List Target DPM Ber-Tab (Kanan) -->
        <section class="lg:col-span-2 bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
            <!-- Tabs Nav: Final Assy vs Pre Assy -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-5">
                <div class="flex space-x-2">
                    <button type="button" @click="activeTab = 'Final Assy'"
                        :class="activeTab === 'Final Assy' ? 'bg-[#8b0000] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 bg-gray-100'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full" :class="activeTab === 'Final Assy' ? 'bg-white' : 'bg-teal-500'"></span>
                        <span>Final Assy</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full" :class="activeTab === 'Final Assy' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'">
                            {{ count($finalAssyTargets) }}
                        </span>
                    </button>
                    <button type="button" @click="activeTab = 'Pre Assy'"
                        :class="activeTab === 'Pre Assy' ? 'bg-[#8b0000] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 bg-gray-100'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full" :class="activeTab === 'Pre Assy' ? 'bg-white' : 'bg-sky-500'"></span>
                        <span>Pre Assy</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full" :class="activeTab === 'Pre Assy' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'">
                            {{ count($preAssyTargets) }}
                        </span>
                    </button>
                </div>

                <span class="text-[11px] font-semibold text-gray-400">Urut periode terbaru</span>
            </div>

            <!-- Tab 1: Final Assy Targets -->
            <div x-show="activeTab === 'Final Assy'" class="overflow-x-auto min-h-[300px]">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 pl-3 w-14">No</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Carline</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Periode Target</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Durasi</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-right">Target DPM</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center pr-3 w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($finalAssyTargets as $index => $target)
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 text-xs text-gray-500 px-4 pl-3 font-medium">{{ $index + 1 }}</td>
                                <td class="py-3.5 px-4">
                                    @if($target->carline)
                                        <span class="text-xs font-bold text-gray-900 font-mono">{{ $target->carline->name }}</span>
                                    @else
                                        <span class="inline-block text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md">
                                            Semua Carline
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-xs font-bold text-gray-900 font-mono">
                                        {{ \Carbon\Carbon::parse($target->period_start)->translatedFormat('d M Y') }}
                                        <span class="text-gray-400 font-normal">s/d</span>
                                        {{ \Carbon\Carbon::parse($target->period_end)->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-xs font-semibold text-gray-500">
                                    {{ \Carbon\Carbon::parse($target->period_start)->diffInDays(\Carbon\Carbon::parse($target->period_end)) + 1 }} hari
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <span class="inline-block bg-teal-50 text-teal-800 border border-teal-200/80 text-xs font-bold font-mono px-2.5 py-0.5 rounded-lg">
                                        {{ number_format($target->target_dpm, 2, ',', '.') }} DPM
                                    </span>
                                </td>
                                <td class="py-3.5 text-center px-4 pr-3">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <button type="button"
                                            @click="openEditModal({{ $target->id }}, '{{ $target->jenis_assy }}', '{{ $target->carline_id ?? '' }}', '{{ \Carbon\Carbon::parse($target->period_start)->format('Y-m-d') }}', '{{ \Carbon\Carbon::parse($target->period_end)->format('Y-m-d') }}', '{{ $target->target_dpm }}')"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-semibold transition shadow-2xs">
                                            Edit
                                        </button>
                                        <button type="button"
                                            @click="confirmDelete('{{ route('admin.master.dpm_targets.destroy', $target->id) }}')"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-[#8b0000] border border-red-200 text-xs font-semibold transition">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-xs text-gray-400 font-semibold">
                                    Belum ada target DPM untuk Final Assy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Pre Assy Targets -->
            <div x-show="activeTab === 'Pre Assy'" x-cloak class="overflow-x-auto min-h-[300px]">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 pl-3 w-14">No</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Carline</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Periode Target</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Durasi</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-right">Target PPM</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center pr-3 w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preAssyTargets as $index => $target)
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 text-xs text-gray-500 px-4 pl-3 font-medium">{{ $index + 1 }}</td>
                                <td class="py-3.5 px-4">
                                    @if($target->carline)
                                        <span class="text-xs font-bold text-gray-900 font-mono">{{ $target->carline->name }}</span>
                                    @else
                                        <span class="inline-block text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md">
                                            Semua Carline
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-xs font-bold text-gray-900 font-mono">
                                        {{ \Carbon\Carbon::parse($target->period_start)->translatedFormat('d M Y') }}
                                        <span class="text-gray-400 font-normal">s/d</span>
                                        {{ \Carbon\Carbon::parse($target->period_end)->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-xs font-semibold text-gray-500">
                                    {{ \Carbon\Carbon::parse($target->period_start)->diffInDays(\Carbon\Carbon::parse($target->period_end)) + 1 }} hari
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <span class="inline-block bg-sky-50 text-sky-800 border border-sky-200/80 text-xs font-bold font-mono px-2.5 py-0.5 rounded-lg">
                                        {{ number_format($target->target_dpm, 2, ',', '.') }} PPM
                                    </span>
                                </td>
                                <td class="py-3.5 text-center px-4 pr-3">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <button type="button"
                                            @click="openEditModal({{ $target->id }}, '{{ $target->jenis_assy }}', '{{ $target->carline_id ?? '' }}', '{{ \Carbon\Carbon::parse($target->period_start)->format('Y-m-d') }}', '{{ \Carbon\Carbon::parse($target->period_end)->format('Y-m-d') }}', '{{ $target->target_dpm }}')"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-semibold transition shadow-2xs">
                                            Edit
                                        </button>
                                        <button type="button"
                                            @click="confirmDelete('{{ route('admin.master.dpm_targets.destroy', $target->id) }}')"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-[#8b0000] border border-red-200 text-xs font-semibold transition">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-xs text-gray-400 font-semibold">
                                    Belum ada target PPM untuk Pre Assy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Modal Edit Target DPM -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100" @click.away="editModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#8b0000]"></span>
                    <h3 class="text-sm font-extrabold text-gray-900 tracking-tight">Edit Target DPM</h3>
                </div>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form :action="'{{ url('/master/dpm-targets') }}/' + editForm.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- Jenis Assy -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                        Jenis Assy <span class="text-red-500">*</span>
                    </label>
                    <select name="jenis_assy" x-model="editForm.jenis_assy" @change="onAssyChange(editForm)" required
                        class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                        <option value="Final Assy">Final Assy</option>
                        <option value="Pre Assy">Pre Assy</option>
                    </select>
                </div>

                <!-- Carline (Opsional, Default Semua Carline) -->
                <div>
                    <label for="edit_carline_id" class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                        Carline
                    </label>
                    <select id="edit_carline_id" name="carline_id" x-model="editForm.carline_id"
                        class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                        <option value="">Semua Carline</option>
                        <template x-for="c in filteredCarlines(editForm.jenis_assy)" :key="c.id">
                            <option :value="c.id" x-text="formatCarlineOption(c)"></option>
                        </template>
                    </select>
                </div>

                <!-- Rentang Tanggal -->
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                            Periode Target <span class="text-red-500">*</span>
                        </label>
                        <div class="inline-flex space-x-1">
                            <button type="button" @click="setQuickPeriod(editForm, 'month')"
                                class="px-2 py-0.5 text-[10px] font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition shadow-2xs">
                                +1 Bulan
                            </button>
                            <button type="button" @click="setQuickPeriod(editForm, 'year')"
                                class="px-2 py-0.5 text-[10px] font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition shadow-2xs">
                                +1 Tahun
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="block text-[10px] font-semibold text-gray-400 mb-1">Mulai</span>
                            <input type="date" name="period_start" x-model="editForm.period_start" required
                                class="w-full border border-gray-200 rounded-xl p-2 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition shadow-xs">
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold text-gray-400 mb-1">Selesai</span>
                            <input type="date" name="period_end" x-model="editForm.period_end" required
                                class="w-full border border-gray-200 rounded-xl p-2 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition shadow-xs">
                        </div>
                    </div>
                </div>

                <!-- Target DPM / PPM -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                        <span x-text="editForm.jenis_assy === 'Pre Assy' ? 'Target PPM' : 'Target DPM'">Target DPM</span> <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="target_dpm" x-model="editForm.target_dpm" required
                            class="w-full border border-gray-200 rounded-xl p-2.5 pr-14 text-xs font-mono font-bold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition shadow-xs">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-bold text-gray-400"
                            x-text="editForm.jenis_assy === 'Pre Assy' ? 'PPM' : 'DPM'">
                            DPM
                        </span>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="editModal = false"
                        class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-bold text-white bg-[#8b0000] hover:bg-[#600000] rounded-xl transition shadow-xs">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden Form untuk Eksekusi Hapus dari confirm-modal -->
    <form id="globalDeleteForm" method="POST" :action="deleteAction" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <!-- Modal Konfirmasi Hapus Menggunakan Component confirm-modal -->
    <x-confirm-modal
        name="deleteModal"
        title="Hapus Target DPM"
        message="Apakah Anda yakin ingin menghapus data target DPM ini? Target yang terhapus tidak dapat dikembalikan."
        confirmText="Ya, Hapus"
        cancelText="Batal"
    />
</div>

<script>
function dpmTargetsPage() {
    return {
        carlines: @json($carlines),
        activeTab: 'Final Assy',
        editModal: false,
        deleteModal: false,
        deleteAction: '',
        addForm: {
            jenis_assy: 'Final Assy',
            carline_id: '{{ old('carline_id', '') }}',
            period_start: '{{ old('period_start', date('Y-m-01')) }}',
            period_end: '{{ old('period_end', date('Y-m-t')) }}',
            target_dpm: '{{ old('target_dpm', '') }}'
        },
        editForm: {
            id: null,
            jenis_assy: 'Final Assy',
            carline_id: '',
            period_start: '',
            period_end: '',
            target_dpm: ''
        },
        init() {
            window.addEventListener('confirmed-deleteModal', () => {
                if (this.deleteAction) {
                    const form = document.getElementById('globalDeleteForm');
                    if (form) {
                        form.action = this.deleteAction;
                        form.submit();
                    }
                }
            });
        },
        filteredCarlines(assy) {
            if (assy === 'Pre Assy') {
                return this.carlines.filter(c => c.car_type_id === null);
            }
            return this.carlines.filter(c => c.car_type_id !== null);
        },
        formatCarlineOption(c) {
            if (c.car_type && c.car_type.name) {
                return `${c.name} (${c.car_type.name})`;
            }
            return c.name;
        },
        onAssyChange(targetObj) {
            const valid = this.filteredCarlines(targetObj.jenis_assy);
            if (targetObj.carline_id && !valid.some(c => String(c.id) === String(targetObj.carline_id))) {
                targetObj.carline_id = '';
            }
        },
        openEditModal(id, assy, carlineId, start, end, target) {
            this.editForm.id = id;
            this.editForm.jenis_assy = assy;
            this.editForm.carline_id = carlineId ? String(carlineId) : '';
            this.editForm.period_start = start;
            this.editForm.period_end = end;
            this.editForm.target_dpm = target;
            this.editModal = true;
        },
        confirmDelete(actionUrl) {
            this.deleteAction = actionUrl;
            this.deleteModal = true;
        },
        setQuickPeriod(targetObj, type) {
            let base = targetObj.period_start ? new Date(targetObj.period_start) : new Date();
            if (isNaN(base.getTime())) base = new Date();

            const y = base.getFullYear();
            const m = base.getMonth();
            const pad = n => String(n).padStart(2, '0');

            if (type === 'month') {
                targetObj.period_start = `${y}-${pad(m + 1)}-01`;
                const lastDay = new Date(y, m + 1, 0).getDate();
                targetObj.period_end = `${y}-${pad(m + 1)}-${pad(lastDay)}`;
            } else if (type === 'year') {
                targetObj.period_start = `${y}-01-01`;
                targetObj.period_end = `${y}-12-31`;
            }
        }
    };
}
</script>
@endsection
