@extends('layouts.app')

@section('title', 'Riwayat Rework')

@section('content')
<!-- Header Section -->
<header class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">Riwayat Rework</h1>
        <p class="text-xs font-semibold text-gray-500 mt-1">Daftar riwayat harness rework Final Assy per kelompok defect (Read-only).</p>
    </div>

    <div class="flex items-center space-x-6">
        <!-- Admin Profile Card -->
        <div class="flex items-center space-x-3">
            <div class="text-right">
                <span class="block text-xs font-extrabold text-gray-900">{{ session('user_name', 'Admin QA') }}</span>
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ session('user_role', 'Administrator') }}</span>
            </div>
            <div class="w-9 h-9 rounded-xl bg-red-50 text-brand flex items-center justify-center border border-red-100 font-black text-xs">
                {{ strtoupper(substr(session('user_name', 'A'), 0, 1)) }}
            </div>
        </div>
    </div>
</header>

<!-- Filters and Table Container -->
<section class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs flex-1 flex flex-col justify-between">
    <div>
        <!-- Filter Form -->
        <form id="filterForm" method="GET" action="{{ route('admin.rework_history.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <input type="hidden" name="page" value="1">

            <div class="flex flex-wrap items-center gap-3">
                <!-- Date Range Picker -->
                <div class="relative min-w-[220px]">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8b0000]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                    <input type="text"
                           id="date_range"
                           name="date_range"
                           placeholder="Pilih Rentang Tanggal"
                           value="{{ $dateRange }}"
                           readonly
                           class="w-full pl-10 pr-4 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                </div>

                <!-- Status Filter -->
                <div class="relative min-w-[140px]">
                    <select name="status"
                            onchange="this.form.submit()"
                            class="w-full appearance-none pl-4 pr-10 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                        <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="OPEN" {{ strtoupper($selectedStatus) === 'OPEN' ? 'selected' : '' }}>OPEN</option>
                        <option value="CLOSE" {{ strtoupper($selectedStatus) === 'CLOSE' ? 'selected' : '' }}>CLOSE</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#8b0000]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Shift Filter -->
                <div class="relative min-w-[130px]">
                    <select name="shift"
                            onchange="this.form.submit()"
                            class="w-full appearance-none pl-4 pr-10 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                        <option value="all" {{ $selectedShift === 'all' || !$selectedShift ? 'selected' : '' }}>Semua Shift</option>
                        @foreach($shiftOptions as $shift)
                            <option value="{{ $shift }}" {{ $selectedShift === $shift ? 'selected' : '' }}>Shift {{ $shift }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#8b0000]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Area Ditemukan Filter -->
                <div class="relative min-w-[160px]">
                    <select name="area"
                            onchange="this.form.submit()"
                            class="w-full appearance-none pl-4 pr-10 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                        <option value="all" {{ $selectedArea === 'all' || !$selectedArea ? 'selected' : '' }}>Semua Area</option>
                        @foreach($areaOptions as $areaItem)
                            <option value="{{ $areaItem->name }}" {{ $selectedArea === $areaItem->name ? 'selected' : '' }}>{{ $areaItem->name }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#8b0000]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Reset Filter Button if any filter active -->
                @if($dateRange !== '' || ($selectedStatus !== 'all' && $selectedStatus !== '') || ($selectedShift !== 'all' && $selectedShift !== '') || ($selectedArea !== 'all' && $selectedArea !== '') || $search !== '')
                    <a href="{{ route('admin.rework_history.index') }}"
                       class="text-xs text-gray-400 hover:text-[#8b0000] font-semibold transition-colors flex items-center space-x-1 pl-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>

            <!-- Export Button & Total Badge (Design identik Final Assy & Pre Assy) -->
            <div class="flex items-center space-x-3">
                <!-- Export Button -->
                <button type="button"
                        onclick="exportExcel()"
                        class="bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold px-5 py-2.5 rounded-lg transition duration-200 shadow-sm flex items-center space-x-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span>Export</span>
                </button>

                <!-- Total Harness Badge -->
                <div class="text-xs font-bold text-gray-500 flex items-center space-x-1.5">
                    <span>Total Harness:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-red-50 text-brand border border-red-100">
                        {{ $totalHarnesses }}
                    </span>
                </div>
            </div>
        </form>

        <!-- Responsive Table Container -->
        <div class="overflow-x-auto min-h-[380px] pb-4">
            <table class="w-full min-w-[1250px] text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/70 sticky top-0 z-10">
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 text-center w-12">No</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">Waktu In</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">Waktu Out</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 text-center whitespace-nowrap">Status</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">Durasi</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">Kanban Assy</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">NP Lama</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">NP Baru</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">Jenis Mobil</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">Conveyor</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 whitespace-nowrap">Operator</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 text-center whitespace-nowrap">Shift</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 min-w-[220px]">Daftar Defect</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody id="reworkTableBody">
                    @forelse($paginatedGroups as $groupItem)
                        @php
                            $groupId = $groupItem->rework_group_id;
                            $items = $defectsByGroup->get($groupId, collect());
                            $first = $items->first();
                            $rowNum = ($paginatedGroups->currentPage() - 1) * $paginatedGroups->perPage() + $loop->iteration;

                            $isClose = in_array($first?->status_rework, ['Selesai', 'CLOSE'], true);
                            $waktuInObj = $first?->waktu_in
                                ? \Carbon\Carbon::parse($first->waktu_in)
                                : ($first?->waktu ? \Carbon\Carbon::parse($first->waktu) : null);
                            $waktuOutObj = ($isClose && $first?->waktu_out)
                                ? \Carbon\Carbon::parse($first->waktu_out)
                                : null;

                            $durasiDisplay = '-';
                            if ($isClose && $waktuInObj && $waktuOutObj) {
                                $totalSeconds = max(0, (int) $waktuInObj->diffInSeconds($waktuOutObj));
                                if ($totalSeconds < 60) {
                                    $durasiDisplay = $totalSeconds . ' dtk';
                                } else {
                                    $minutes = intdiv($totalSeconds, 60);
                                    $seconds = $totalSeconds % 60;
                                    $durasiDisplay = $minutes . ' mnt ' . $seconds . ' dtk';
                                }
                            }

                            $effectiveKanbanStatus = $first?->kanban_status;
                            if ($effectiveKanbanStatus === null && filled($first?->kanban_assy)) {
                                $effectiveKanbanStatus = \App\Support\KanbanCodeParser::evaluateKanban($first->kanban_assy)['kanban_status'] ?? null;
                            }
                            $kanbanBadgeMeta = match ($effectiveKanbanStatus) {
                                'terdaftar'            => ['label' => 'Terdaftar', 'class' => 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                'tidak_terdaftar'      => ['label' => 'Tidak terdaftar', 'class' => 'bg-amber-50 text-amber-800 border-amber-200'],
                                'family_beda'          => ['label' => 'Family beda', 'class' => 'bg-indigo-50 text-indigo-800 border-indigo-200'],
                                'conveyor_beda'        => ['label' => 'Conveyor beda', 'class' => 'bg-purple-50 text-purple-800 border-purple-200'],
                                'format_tidak_dikenal' => ['label' => 'Format tidak dikenal', 'class' => 'bg-rose-50 text-rose-800 border-rose-200'],
                                default                => ['label' => '-', 'class' => 'bg-gray-100 text-gray-500 border-gray-200'],
                            };

                            $carlineDisplay = filled($first?->jenis_mobil) ? $first->jenis_mobil : ($first?->carline?->carType?->name ?: $first?->carline?->name);
                            $conveyorDisplay = filled($first?->conveyor) ? $first->conveyor : ($first?->carline?->name);

                            $areaModel = filled($first?->area_ditemukan)
                                ? \App\Models\AreaDitemukan::where('name', $first->area_ditemukan)->first()
                                : null;
                            $isFinalAssyArea = $areaModel && $areaModel->kategori === \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY;
                            $areaTipe = $isFinalAssyArea ? $areaModel->tipe : null;
                            $badgeTipeLabel = ($areaTipe === \App\Models\AreaDitemukan::TIPE_ASSEMBLY_DEFECT)
                                ? 'Assembly Defect'
                                : ($isFinalAssyArea ? 'Internal Defect' : '-');
                            $badgeTipeClass = ($areaTipe === \App\Models\AreaDitemukan::TIPE_ASSEMBLY_DEFECT)
                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                : ($isFinalAssyArea ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200');
                        @endphp

                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-red-50/20 transition-colors odd:bg-white even:bg-gray-50/40">
                            <!-- 1. No -->
                            <td class="py-4 text-xs font-mono text-gray-400 font-bold text-center px-3 align-top">
                                {{ $rowNum }}
                            </td>

                            <!-- 2. Waktu In -->
                            <td class="py-4 text-xs px-3 align-top whitespace-nowrap">
                                <span class="font-extrabold text-gray-900 font-mono text-[11px]">
                                    {{ $waktuInObj ? $waktuInObj->format('d/m/Y H:i') : '-' }}
                                </span>
                            </td>

                            <!-- 3. Waktu Out -->
                            <td class="py-4 text-xs px-3 align-top whitespace-nowrap">
                                <span class="font-extrabold text-gray-900 font-mono text-[11px]">
                                    {{ $waktuOutObj ? $waktuOutObj->format('d/m/Y H:i') : '-' }}
                                </span>
                            </td>

                            <!-- 4. Status -->
                            <td class="py-4 text-xs font-bold px-3 text-center align-top whitespace-nowrap">
                                <div class="inline-flex flex-col items-center gap-1">
                                    @if($first?->is_susulan)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200">
                                            Susulan
                                        </span>
                                    @endif
                                    @if($isClose)
                                        <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>CLOSE</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-50 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            <span>OPEN</span>
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 5. Durasi -->
                            <td class="py-4 text-xs font-bold px-3 align-top whitespace-nowrap">
                                @if($isClose && $durasiDisplay !== '-')
                                    <span class="text-emerald-700 font-mono">
                                        {{ $durasiDisplay }}
                                    </span>
                                @else
                                    <span class="text-gray-400 font-mono">-</span>
                                @endif
                            </td>

                            <!-- 6. Kanban Assy -->
                            <td class="py-4 text-xs px-3 align-top">
                                <div class="space-y-0.5">
                                    <span class="block text-sm font-black text-gray-900 tracking-tight whitespace-nowrap">
                                        {{ filled($first?->kanban_assy) ? $first->kanban_assy : '-' }}
                                    </span>
                                    @if($first?->kanban_serial !== null)
                                        <span class="block text-[10px] font-bold font-mono text-gray-500">
                                            #{{ $first->kanban_serial }}
                                        </span>
                                    @endif
                                    @if($kanbanBadgeMeta)
                                        <span class="inline-block border text-[10px] font-bold px-1.5 py-0.5 rounded whitespace-nowrap {{ $kanbanBadgeMeta['class'] }}">
                                            {{ $kanbanBadgeMeta['label'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 7. NP Lama -->
                            <td class="py-4 text-xs font-bold font-mono text-gray-700 px-3 align-top whitespace-nowrap">
                                {{ filled($first?->nameplate_lama) ? $first->nameplate_lama : '-' }}
                            </td>

                            <!-- 8. NP Baru -->
                            <td class="py-4 text-xs font-bold font-mono px-3 align-top whitespace-nowrap">
                                @if(filled($first?->nameplate_baru))
                                    <span class="text-emerald-700">{{ $first->nameplate_baru }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- 9. Jenis Mobil -->
                            <td class="py-4 text-xs font-bold text-gray-800 px-3 align-top whitespace-nowrap">
                                {{ filled($carlineDisplay) ? $carlineDisplay : '-' }}
                            </td>

                            <!-- 10. Conveyor -->
                            <td class="py-4 text-xs font-bold text-blue-800 px-3 align-top whitespace-nowrap">
                                {{ filled($conveyorDisplay) ? $conveyorDisplay : '-' }}
                            </td>

                            <!-- 11. Operator -->
                            <td class="py-4 text-xs font-bold text-gray-900 px-3 align-top whitespace-nowrap">
                                {{ $first?->user_name ?? '-' }}
                            </td>

                            <!-- 12. Shift -->
                            <td class="py-4 text-xs font-bold text-center px-3 align-top whitespace-nowrap">
                                <span class="inline-block text-[11px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-md">
                                    {{ $first?->shift ?? '-' }}
                                </span>
                            </td>

                            <!-- 13. Daftar Defect -->
                            <td class="py-4 text-xs px-3 align-top">
                                <div class="space-y-1.5 max-w-md">
                                    @forelse($items as $item)
                                        @php
                                            $code = $item->subDefectType?->code ?? \App\Support\SubDefectHelper::deriveCode($item->jenis_sub_defect) ?? '-';
                                            $name = $item->jenis_sub_defect ?? $item->subDefectType?->name ?? '-';
                                        @endphp
                                        <div class="flex items-start text-xs font-bold text-gray-800">
                                            <span class="bg-[#8b0000]/10 text-[#8b0000] border border-[#8b0000]/20 font-black px-1.5 py-0.5 rounded text-[10px] mr-2 shrink-0 font-mono">
                                                [{{ $code }}]
                                            </span>
                                            <span class="leading-tight text-gray-800">{{ $name }}</span>
                                        </div>
                                    @empty
                                        <span class="text-gray-400 italic text-xs">-</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- 14. Aksi (Detail, Edit, Hapus) -->
                            <td class="py-4 text-center px-3 align-top whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1.5">
                                    <button type="button"
                                            onclick="openDetailModal('detail-modal-{{ $rowNum }}')"
                                            title="Detail Riwayat Harness"
                                            class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold transition-all shadow-xs active:scale-95 cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-gray-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <span>Detail</span>
                                    </button>
                                    <a href="{{ route('rework.edit', array_merge(['groupId' => $groupId], request()->query() ? ['return_url' => request()->getRequestUri()] : [])) }}"
                                       title="Edit Riwayat Harness"
                                       class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-xl bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold transition-all shadow-xs active:scale-95 cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                        <span>Edit</span>
                                    </a>
                                    <form action="{{ route('admin.rework_history.destroy', $groupId) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus data rework ini? Sebanyak {{ $items->count() }} defect yang ada di dalam harness ini akan ikut terhapus.');" 
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                title="Hapus Riwayat Harness"
                                                class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold transition-all shadow-xs active:scale-95 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                </div>

                                <!-- Modal Detail Harness -->
                                <div id="detail-modal-{{ $rowNum }}"
                                     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 text-left"
                                     onclick="if (event.target === this) closeDetailModal('detail-modal-{{ $rowNum }}')">
                                    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-gray-200">
                                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                                            <div>
                                                <h3 class="text-base font-black text-gray-900">Detail Riwayat Rework #{{ $rowNum }}</h3>
                                                <p class="text-[11px] font-mono text-gray-400">Group ID: {{ $groupId }}</p>
                                            </div>
                                            <button type="button"
                                                    onclick="closeDetailModal('detail-modal-{{ $rowNum }}')"
                                                    class="text-gray-400 hover:text-gray-700 p-1 rounded-lg cursor-pointer">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="p-6 space-y-5 text-xs">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">Kanban Assy</span>
                                                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                                                        <span class="font-black text-sm text-gray-900">{{ filled($first?->kanban_assy) ? $first->kanban_assy : '-' }}</span>
                                                        @if($first?->kanban_serial !== null)
                                                            <span class="bg-gray-100 text-gray-700 text-[10px] font-bold px-1.5 py-0.5 rounded font-mono">#{{ $first->kanban_serial }}</span>
                                                        @endif
                                                        @if($kanbanBadgeMeta)
                                                            <span class="border text-[10px] font-bold px-1.5 py-0.5 rounded {{ $kanbanBadgeMeta['class'] }}">
                                                                {{ $kanbanBadgeMeta['label'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div>
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">Status &amp; Durasi</span>
                                                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                                                        <span class="font-extrabold {{ $isClose ? 'text-emerald-700' : 'text-amber-700' }}">
                                                            {{ $isClose ? 'CLOSE' : 'OPEN' }}
                                                        </span>
                                                        @if($first?->is_susulan)
                                                            <span class="bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-black px-2 py-0.5 rounded-full uppercase">Susulan</span>
                                                        @endif
                                                        <span class="font-mono text-gray-700">({{ $durasiDisplay }})</span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">Waktu In / Out</span>
                                                    <div class="mt-1 font-mono text-gray-800">
                                                        In: {{ $waktuInObj ? $waktuInObj->format('d/m/Y H:i') : '-' }}<br>
                                                        Out: {{ $waktuOutObj ? $waktuOutObj->format('d/m/Y H:i') : '-' }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">Operator &amp; Shift</span>
                                                    <div class="mt-1 font-bold text-gray-800">
                                                        {{ $first?->user_name ?? '-' }} (Shift {{ $first?->shift ?? '-' }})
                                                    </div>
                                                </div>

                                                <div>
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">NP Lama / NP Baru</span>
                                                    <div class="mt-1 font-mono font-bold text-gray-800">
                                                        {{ filled($first?->nameplate_lama) ? $first->nameplate_lama : '-' }} / {{ filled($first?->nameplate_baru) ? $first->nameplate_baru : '-' }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">Jenis Mobil &amp; Conveyor</span>
                                                    <div class="mt-1 font-bold text-gray-800">
                                                        {{ filled($carlineDisplay) ? $carlineDisplay : '-' }} / {{ filled($conveyorDisplay) ? $conveyorDisplay : '-' }}
                                                    </div>
                                                </div>

                                                <div class="sm:col-span-2">
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">Area Ditemukan</span>
                                                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                                                        <span class="font-bold text-gray-800">{{ $first?->area_ditemukan ?? '-' }}</span>
                                                        @if(filled($first?->area_ditemukan))
                                                            <span class="inline-block border text-[10px] font-bold px-1.5 py-0.5 rounded {{ $badgeTipeClass }}">
                                                                {{ $badgeTipeLabel }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div>
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">No Defective Tag / 4M Status</span>
                                                    <div class="mt-1 font-bold text-gray-800">
                                                        {{ $first?->no_defective_tag ?? '-' }} / {{ $first?->status_4m ?? 'NO' }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">Ujung (#End) / No Box</span>
                                                    <div class="mt-1 font-bold text-gray-800">
                                                        {{ $first?->ujung ?? '-' }} / {{ $first?->no_box ?? '-' }}
                                                    </div>
                                                </div>

                                                <div class="sm:col-span-2">
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">Posisi &amp; Detail Rework</span>
                                                    <div class="mt-1 text-gray-800">{{ $first?->posisi_detail_rework ?? '-' }}</div>
                                                </div>

                                                <div class="sm:col-span-2">
                                                    <span class="block text-[10px] font-bold uppercase text-gray-400">F C/H, F C/W, R C/H, R C/W</span>
                                                    <div class="mt-1 font-mono text-gray-800">
                                                        {{ $first?->fc_h ?? '-' }} / {{ $first?->fc_w ?? '-' }} / {{ $first?->rc_h ?? '-' }} / {{ $first?->rc_w ?? '-' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="border-t border-gray-100 pt-4">
                                                <span class="block text-[10px] font-bold uppercase text-gray-400 mb-2">Daftar Defect ({{ $items->count() }})</span>
                                                <div class="space-y-1.5">
                                                    @forelse($items as $item)
                                                        @php
                                                            $code = $item->subDefectType?->code ?? \App\Support\SubDefectHelper::deriveCode($item->jenis_sub_defect) ?? '-';
                                                            $name = $item->jenis_sub_defect ?? $item->subDefectType?->name ?? '-';
                                                        @endphp
                                                        <div class="flex items-start text-xs font-bold text-gray-800">
                                                            <span class="bg-[#8b0000]/10 text-[#8b0000] border border-[#8b0000]/20 font-black px-1.5 py-0.5 rounded text-[10px] mr-2 shrink-0 font-mono">
                                                                [{{ $code }}]
                                                            </span>
                                                            <span class="leading-tight text-gray-800">{{ $name }}</span>
                                                        </div>
                                                    @empty
                                                        <span class="text-gray-400 italic">-</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="py-12 text-center text-sm text-gray-400 font-medium">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <svg class="w-10 h-10 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="text-xs text-gray-500 font-semibold">Tidak ada riwayat rework yang cocok dengan filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer Pagination Row -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-100 pt-6 mt-4 gap-4">
        <span class="text-xs font-semibold text-gray-400">
            Menampilkan <span class="text-gray-900 font-bold">{{ $paginatedGroups->count() }}</span> dari <span class="text-gray-900 font-bold">{{ $paginatedGroups->total() }}</span> harness
        </span>

        <!-- Pagination Links -->
        <div class="flex items-center">
            {{ $paginatedGroups->links() }}
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function openDetailModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function closeDetailModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

function exportExcel() {
    const form = document.getElementById("filterForm");
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    params.delete('page');

    window.location.href = "{{ route('admin.rework_history.export') }}?" + params.toString();
}

document.addEventListener("DOMContentLoaded", function () {
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#date_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    document.getElementById("filterForm").submit();
                }
            }
        });
    }
});
</script>
@endpush
