@extends('layouts.app')

@section('title', 'Report Final Assy')

@section('content')
        <!-- Header Section -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">Report Final Assy</h1>
                <p class="text-xs font-semibold text-gray-500 mt-1">Riwayat aktivitas defect final assy secara real-time.</p>
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
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 border border-gray-200">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filters and Table Container -->
        <section class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex-1 flex flex-col justify-between">
            
            <div>
                <!-- Form Filter -->
                <form id="filterForm" method="GET" action="{{ route('final_assy.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <input type="hidden" name="page" value="1">
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Date Range Picker -->
                        <div class="relative min-w-[240px]">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8b0000]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </span>
                            <input type="text" id="date_range" name="date_range" placeholder="Pilih Tanggal" value="{{ $dateRange }}" readonly class="w-full pl-10 pr-4 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                        </div>

                        <!-- Defect Select -->
                        <div class="relative min-w-[150px]">
                            <select name="defect" onchange="this.form.submit()" class="w-full appearance-none pl-4 pr-10 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                                <option value="all">Semua Defect</option>
                                @foreach($defectOptions as $option)
                                    <option value="{{ $option }}" {{ $selectedDefect === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#8b0000]">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Mobil Select -->
                        <div class="relative min-w-[130px]">
                            <select id="mobilSelect" name="line" class="w-full appearance-none pl-4 pr-10 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                                <option value="all">Semua Mobil</option>
                                @foreach($lineOptions as $option)
                                    <option value="{{ $option }}" {{ (string)$selectedLine === (string)$option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#8b0000]">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Conveyor Select -->
                        <div class="relative min-w-[150px]">
                            <select id="conveyorSelect" name="conveyor" onchange="this.form.submit()" class="peer w-full appearance-none pl-4 pr-10 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer disabled:bg-gray-100 disabled:border-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed">
                                <option value="all">Semua Konveyor</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#8b0000] peer-disabled:text-gray-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Pattern Select (Khusus Mobil MAZDA) -->
                        <div id="patternFilterWrapper" class="relative min-w-[170px]" style="{{ (string)$selectedLine === 'MAZDA' ? '' : 'display: none;' }}">
                            <select id="patternSelect" name="pattern" onchange="this.form.submit()" class="w-full appearance-none pl-4 pr-10 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                                <option value="all">Semua Pattern</option>
                                <optgroup label="AB6. Extend LHD / RHD">
                                    <option value="67120 (AB6. Extend LHD / RHD)" {{ ($selectedPattern ?? '') === '67120 (AB6. Extend LHD / RHD)' ? 'selected' : '' }}>67120 (AB6. Extend LHD / RHD)</option>
                                    <option value="67240 (AB6. Extend LHD / RHD)" {{ ($selectedPattern ?? '') === '67240 (AB6. Extend LHD / RHD)' ? 'selected' : '' }}>67240 (AB6. Extend LHD / RHD)</option>
                                    <option value="67550 (AB6. Extend LHD / RHD)" {{ ($selectedPattern ?? '') === '67550 (AB6. Extend LHD / RHD)' ? 'selected' : '' }}>67550 (AB6. Extend LHD / RHD)</option>
                                </optgroup>
                                <optgroup label="AB9. EXTEND LHD">
                                    <option value="67120 (AB9. EXTEND LHD)" {{ ($selectedPattern ?? '') === '67120 (AB9. EXTEND LHD)' ? 'selected' : '' }}>67120 (AB9. EXTEND LHD)</option>
                                    <option value="67240 (AB9. EXTEND LHD)" {{ ($selectedPattern ?? '') === '67240 (AB9. EXTEND LHD)' ? 'selected' : '' }}>67240 (AB9. EXTEND LHD)</option>
                                </optgroup>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#8b0000]">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Reset Filter Button if filters active -->
                        @if($dateRange || ($selectedDefect && $selectedDefect !== 'all') || ($selectedLine && $selectedLine !== 'all') || ($selectedConveyor && $selectedConveyor !== 'all') || ($selectedPattern && $selectedPattern !== 'all'))
                            <a href="{{ route('final_assy.index') }}" class="text-xs text-gray-400 hover:text-[#8b0000] font-semibold transition-colors flex items-center space-x-1 pl-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Reset Filter</span>
                            </a>
                        @endif
                    </div>

                    <!-- Export Button -->
                    <button type="button" onclick="exportExcel()" class="bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold px-5 py-2.5 rounded-lg transition duration-200 shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>Export</span>
                    </button>
                </form>

                <!-- Responsive Table -->
                <div class="overflow-x-auto min-h-[400px] pb-4">
                    <table class="w-full min-w-[850px] text-left border-collapse">
                        <thead>
                            <tr class="border-b border-border bg-gray-50/70 sticky top-0 z-10">
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 pl-3">Waktu</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">User</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center">Shift</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Jenis Mobil</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Konveyor</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Quantity Inspect Type</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Jenis Defect</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Jenis Sub Defect</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">END (#)</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Specification</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Actual</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Area Ditemukan</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Job Station</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center">Inspect Qty</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center">Defect Qty</th>
                                <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center pr-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            @forelse($records as $record)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-red-50/20 transition-colors odd:bg-white even:bg-gray-50/40" data-id="{{ $record->external_id ?? $record->id }}">
                                    <td class="py-4 text-sm text-gray-500 px-4 pl-2 font-medium">
                                        <div class="text-xs leading-normal">
                                            <span class="block text-gray-900">{{ \Carbon\Carbon::parse($record->waktu)->translatedFormat('d F Y') }}</span>
                                            <span class="block text-gray-400 mt-0.5 text-[11px]">{{ \Carbon\Carbon::parse($record->waktu)->format('H:i:s') }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 font-bold px-4">
                                        {{ $record->user_name }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 font-bold px-4 text-center">
                                        {{ $record->shift ?? '-' }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-950 font-bold px-4">
                                        {{ $record->jenis_mobil ?? '-' }}
                                    </td>
                                    <td class="py-4 text-sm font-bold px-4">
                                        <span class="inline-block bg-gray-100 text-gray-700 text-xs font-bold px-2 py-0.5 rounded-lg tracking-wider">
                                            {{ $record->conveyor }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-sm text-gray-700 font-semibold px-4">
                                        {{ $record->finalInspectType?->name ?? '-' }}
                                    </td>
                                    <td class="py-4 text-xs text-brand font-bold tracking-wider uppercase font-mono px-4">
                                        {{ $record->jenis_defect }}
                                    </td>
                                    <td class="py-4 text-xs text-brand font-bold tracking-wider uppercase font-mono px-4">
                                        {{ $record->jenis_sub_defect }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 px-4 font-medium">
                                        {{ $record->end_number ?? '-' }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 px-4 font-medium">
                                        {{ $record->specification ?? '-' }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 px-4 font-medium">
                                        {{ $record->actual ?? '-' }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 px-4 font-medium">
                                        {{ $record->area_ditemukan ?? '-' }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 px-4 font-medium">
                                        {{ $record->job_station ?? '-' }}
                                    </td>
                                    <td class="py-4 text-sm text-final-assy font-black font-mono tabular-nums text-center px-4">
                                        {{ $record->inspect_quantity ?? 0 }}
                                    </td>
                                    <td class="py-4 text-sm text-brand font-black font-mono tabular-nums text-center px-4">
                                        {{ $record->quantity }}
                                    </td>
                                    <td class="py-4 text-center px-4 pr-2">
                                        <div class="inline-flex items-center space-x-1.5">
                                            <a href="{{ route('admin.report.edit', $record->id) }}" 
                                               class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-brand hover:bg-brand-active text-white text-xs font-bold transition-all shadow-xs active:scale-95">
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.report.destroy', $record->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data defect ini?');" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-brand text-xs font-bold transition-colors">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="py-12 text-center text-sm text-gray-400 font-medium">Tidak ada data defect untuk filter terpilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Pagination Row -->
            <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-100 pt-6 mt-6 gap-4">
                <span class="text-xs font-semibold text-gray-400">
                    Menampilkan <span id="visible-count">{{ count($records) }}</span> dari <span id="total-count">{{ $totalItems }}</span> entri
                </span>
                
                <!-- Pagination Buttons -->
                <div class="flex items-center space-x-1">
                    <!-- Prev Page -->
                    @if($currentPage > 1)
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['page' => $currentPage - 1])) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center justify-center transition-colors text-xs font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </a>
                    @else
                        <span class="w-8 h-8 rounded-lg border border-gray-200 text-gray-300 flex items-center justify-center text-xs font-semibold cursor-not-allowed">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </span>
                    @endif

                    <!-- Page Numbers -->
                    @for($p = 1; $p <= $totalPages; $p++)
                        @if($p === $currentPage)
                            <span class="w-8 h-8 rounded-lg bg-[#8b0000] text-white flex items-center justify-center text-xs font-bold shadow-sm">{{ $p }}</span>
                        @else
                            <a href="?{{ http_build_query(array_merge(request()->query(), ['page' => $p])) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors text-xs font-semibold">{{ $p }}</a>
                        @endif
                    @endfor

                    <!-- Next Page -->
                    @if($currentPage < $totalPages)
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['page' => $currentPage + 1])) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center justify-center transition-colors text-xs font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    @else
                        <span class="w-8 h-8 rounded-lg border border-gray-200 text-gray-300 flex items-center justify-center text-xs font-semibold cursor-not-allowed">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                    @endif
                </div>
            </div>

        </section>
    </main>

    <!-- Script Initializations -->
    <script>
        // List konveyor per mobil
        const conveyorsByMobil = {
            'TOYOTA': [
                '664W-C5', '664W-C5C', '664W-C5A', '664W-C5B', '664W-C5D',
                '711W TNGA-C5', '711W TNGA-C5A', '737W TNGA-C5A', '737W TNGA-C5',
                '738W-C5C', '858W-C5C', '810W-C5', '941W-C5', '023J-C5', '072Y-C5',
                '718W-AB5.HEV', '718W-C4.CONV', '718W-C4.TNGA', '891W/892W-C1.GAS LHD',
                '853W-AT2.HEV LHD', '853W-AT6.GAS LHD', '853W-AT16.GAS LHD',
                '852W-AT19.HEV PHV LHD', '852W-AT2.HEV PHV LHD', '852W-AT19.HEV PHV RHD',
                '852W-AT6.GAS LHD', '909W-AT7.GAS LHD', '909W-AT11.HEV LHD',
                '909W-AT9.GAS LHD', '910W-AT7.GAS LHD', '910W-AT11.HEV LHD',
                '910W-AT9.GAS LHD', '953W-C6.HEV RHD', '953W-C6.HEV LHD',
                '953W ENG NO.3-C9', '898W-AB5.HEV', '898W-C4.CONV', '898W-C4.TNGA'
            ],
            'NISSAN': [
                'P33A-B1.BAT', 'P33A-B1.CELL', 'J32V-B2.LHD', 'J32V-B2.RHD',
                'J42U-B3.EGI', 'J42U-B3.ENGINE', 'J42U-B2.DOOR RH', 'J42U-B2.DOOR LH',
                'P33C-B1.BAT', 'P33C-B1.CELL'
            ],
            'MAZDA': [
                'J72A-12B.LHD', 'J72A-AB9.RHD', 'J72A-16C.LHD', 'J72K-16C.LHD',
                'J30A-AB6.EXTEND LHD', 'J30A-AB1.INPANEL LHD', 'J30A-AB6.EXTEND RHD', 'J30A-AB1.INPANEL RHD',
                'J69P-AB8.EXTEND LHD', 'J69P-AB8.INPANEL LHD', 'J69P-AB8.EXTEND RHD', 'J69P-AB8.INPANEL RHD',
                'J69P-AB9.EXTEND LHD', 'J69P-AB3.INPANEL LHD'
            ]
        };

        function convertToCustomSelect(selectEl) {
            // Hide original select
            selectEl.classList.add('hidden');
            
            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'w-full relative';
            selectEl.parentNode.insertBefore(wrapper, selectEl);
            wrapper.appendChild(selectEl); // move select inside wrapper
            
            // Create trigger button
            const button = document.createElement('button');
            button.type = 'button';
            button.className = selectEl.className.replace('hidden', '') + ' text-left w-full';
            
            const labelSpan = document.createElement('span');
            button.appendChild(labelSpan);
            wrapper.appendChild(button);
            
            // Create dropdown list container
            const listContainer = document.createElement('div');
            listContainer.className = 'absolute z-50 left-0 right-0 mt-1 bg-white border border-[#8b0000] rounded-lg shadow-lg max-h-60 overflow-y-auto hidden custom-select-list';
            wrapper.appendChild(listContainer);
            
            function renderOptions() {
                listContainer.innerHTML = '';
                const options = selectEl.options;
                const selectedIndex = selectEl.selectedIndex;
                
                labelSpan.textContent = selectedIndex >= 0 ? options[selectedIndex].text : '';
                
                for (let i = 0; i < options.length; i++) {
                    const opt = options[i];
                    const item = document.createElement('div');
                    
                    if (i === selectedIndex) {
                        item.className = 'px-4 py-2 text-xs font-semibold text-gray-900 hover:bg-[#8b0000] hover:text-white cursor-pointer transition-colors duration-75';
                    } else {
                        item.className = 'px-4 py-2 text-xs font-semibold text-gray-900 hover:bg-[#8b0000] hover:text-white cursor-pointer transition-colors duration-75';
                    }
                    
                    if (opt.disabled) {
                        item.className = 'px-4 py-2 text-xs font-semibold text-gray-400 bg-gray-50 cursor-not-allowed';
                    }
                    
                    item.textContent = opt.text;
                    
                    if (!opt.disabled) {
                        item.addEventListener('click', (e) => {
                            e.stopPropagation();
                            selectEl.selectedIndex = i;
                            selectEl.dispatchEvent(new Event('change'));
                            listContainer.classList.add('hidden');
                        });
                    }
                    listContainer.appendChild(item);
                }
            }
            
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                if (selectEl.disabled) return;
                
                document.querySelectorAll('.custom-select-list').forEach(list => {
                    if (list !== listContainer) list.classList.add('hidden');
                });
                
                listContainer.classList.toggle('hidden');
            });
            
            document.addEventListener('click', () => {
                listContainer.classList.add('hidden');
            });
            
            renderOptions();
            
            const observer = new MutationObserver(() => {
                renderOptions();
            });
            observer.observe(selectEl, { childList: true });
            
            const disabledObserver = new MutationObserver(() => {
                if (selectEl.disabled) {
                    button.setAttribute('disabled', 'disabled');
                    button.classList.add('bg-gray-100', 'border-gray-200', 'text-gray-400', 'cursor-not-allowed');
                    button.classList.remove('bg-white', 'border-[#8b0000]', 'text-gray-900', 'cursor-pointer');
                } else {
                    button.removeAttribute('disabled');
                    button.classList.remove('bg-gray-100', 'border-gray-200', 'text-gray-400', 'cursor-not-allowed');
                    button.classList.add('bg-white', 'border-[#8b0000]', 'text-gray-900', 'cursor-pointer');
                }
            });
            disabledObserver.observe(selectEl, { attributes: true, attributeFilter: ['disabled'] });
            
            if (selectEl.disabled) {
                button.setAttribute('disabled', 'disabled');
                button.classList.add('bg-gray-100', 'border-gray-200', 'text-gray-400', 'cursor-not-allowed');
                button.classList.remove('bg-white', 'border-[#8b0000]', 'text-gray-900', 'cursor-pointer');
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            // Convert native selects to custom selects
            document.querySelectorAll('#filterForm select').forEach(select => {
                convertToCustomSelect(select);
            });

            // Flatpickr setup
            flatpickr("#date_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2 || selectedDates.length === 0) {
                        // Submit filter form automatically on date selection
                        document.getElementById("filterForm").submit();
                    }
                }
            });

            const mobilSelect = document.getElementById("mobilSelect");
            const conveyorSelect = document.getElementById("conveyorSelect");
            const selectedConveyor = @json($selectedConveyor ?? 'all');

            function populateConveyors(mobil, selectedVal) {
                // Clear and add base option
                conveyorSelect.innerHTML = '';
                
                if (mobil && mobil !== 'all') {
                    const optAll = document.createElement("option");
                    optAll.value = "all";
                    optAll.textContent = "Semua Konveyor";
                    if (selectedVal === 'all') {
                        optAll.selected = true;
                    }
                    conveyorSelect.appendChild(optAll);

                    const list = conveyorsByMobil[mobil] || [];
                    list.forEach(item => {
                        const opt = document.createElement("option");
                        opt.value = item;
                        opt.textContent = item;
                        if (item === selectedVal) {
                            opt.selected = true;
                        }
                        conveyorSelect.appendChild(opt);
                    });
                    conveyorSelect.disabled = false;
                } else {
                    const optPlaceholder = document.createElement("option");
                    optPlaceholder.value = "all";
                    optPlaceholder.textContent = "Pilih Mobil Terlebih Dahulu";
                    optPlaceholder.selected = true;
                    conveyorSelect.appendChild(optPlaceholder);
                    conveyorSelect.disabled = true;
                }
            }

            // Listen to mobil changes
            mobilSelect.addEventListener("change", function () {
                const patternWrapper = document.getElementById("patternFilterWrapper");
                const patternSelect = document.getElementById("patternSelect");
                if (this.value === 'MAZDA') {
                    if (patternWrapper) patternWrapper.style.display = '';
                } else {
                    if (patternWrapper) patternWrapper.style.display = 'none';
                    if (patternSelect) patternSelect.value = 'all';
                }
                populateConveyors(this.value, 'all');
                this.form.submit();
            });

            // Initialize on load
            populateConveyors(mobilSelect.value, selectedConveyor);
        });

        // Excel exporter via URL redirect
        function exportExcel() {
            const dateRange = document.getElementById("date_range").value;
            const defect = document.querySelector('select[name="defect"]').value;
            const line = document.getElementById("mobilSelect").value;
            const conveyor = document.getElementById("conveyorSelect").value;
            const patternEl = document.getElementById("patternSelect");
            const pattern = patternEl ? patternEl.value : 'all';
            
            let url = "{{ route('final_assy.export') }}?";
            url += "date_range=" + encodeURIComponent(dateRange);
            url += "&defect=" + encodeURIComponent(defect);
            url += "&line=" + encodeURIComponent(line);
            url += "&conveyor=" + encodeURIComponent(conveyor);
            url += "&pattern=" + encodeURIComponent(pattern);
            
            window.location.href = url;
        }
    </script>

    <!-- AJAX Polling Script (8 Detik) -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let pollingTimer = null;

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        }

        function formatTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return '';
            return d.toTimeString().substring(0, 8);
        }

        function fetchLiveFinalAssy() {
            const currentQuery = window.location.search;
            fetch('{{ url("/api/final-assy/live") }}' + currentQuery)
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !Array.isArray(res.data)) return;
                    const tbody = document.getElementById('reportTableBody');
                    if (!tbody) return;

                    if (res.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="16" class="py-12 text-center text-sm text-gray-400 font-medium">Tidak ada data defect untuk filter terpilih.</td></tr>';
                        return;
                    }

                    let html = '';
                    res.data.forEach(item => {
                        const finalInspectTypeName = (item.final_inspect_type && item.final_inspect_type.name)
                            ? item.final_inspect_type.name
                            : '-';

                        html += `
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors" data-id="${item.id}">
                                <td class="py-4 text-sm text-gray-500 px-4 pl-2 font-medium"><div class="text-xs leading-normal"><span class="block text-gray-900">${formatDate(item.waktu)}</span><span class="block text-gray-400 mt-0.5 text-[11px]">${formatTime(item.waktu)}</span></div></td>
                                <td class="py-4 text-sm text-gray-900 font-bold px-4">${item.user_name || '-'}</td>
                                <td class="py-4 text-sm text-gray-900 font-bold px-4 text-center">${item.shift || '-'}</td>
                                <td class="py-4 text-sm text-gray-950 font-bold px-4">${item.jenis_mobil || '-'}</td>
                                <td class="py-4 text-sm font-bold px-4"><span class="inline-block bg-gray-100 text-gray-700 text-xs font-bold px-2 py-0.5 rounded-lg tracking-wider">${item.conveyor || '-'}</span></td>
                                <td class="py-4 text-sm text-gray-700 font-semibold px-4">${finalInspectTypeName}</td>
                                <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono px-4">${item.jenis_defect || '-'}</td>
                                <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono px-4">${item.jenis_sub_defect || '-'}</td>
                                <td class="py-4 text-sm text-gray-900 px-4 font-medium">${item.end_number || '-'}</td>
                                <td class="py-4 text-sm text-gray-900 px-4 font-medium">${item.specification || '-'}</td>
                                <td class="py-4 text-sm text-gray-900 px-4 font-medium">${item.actual || '-'}</td>
                                <td class="py-4 text-sm text-gray-900 px-4 font-medium">${item.area_ditemukan || '-'}</td>
                                <td class="py-4 text-sm text-gray-900 px-4 font-medium">${item.job_station || '-'}</td>
                                <td class="py-4 text-sm text-teal-700 font-bold text-center px-4">${item.inspect_quantity ?? 0}</td>
                                <td class="py-4 text-sm text-gray-900 font-bold text-center px-4">${item.quantity || 0}</td>
                                <td class="py-4 text-center px-4 pr-2"><a href="/report/${item.id}/edit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-[#8b0000] hover:bg-red-900 text-white text-xs font-semibold transition-colors">Edit</a></td>
                            </tr>
                        `;
                    });

                    tbody.innerHTML = html;

                    const visibleEl = document.getElementById('visible-count');
                    if (visibleEl) visibleEl.innerText = res.data.length;

                    const totalEl = document.getElementById('total-count');
                    if (totalEl && res.total !== undefined) totalEl.innerText = res.total;
                })
                .catch(err => console.error('[Polling] Gagal fetch final assy live:', err));
        }

        // Init polling timer
        pollingTimer = setInterval(fetchLiveFinalAssy, 8000);

        window.addEventListener('beforeunload', function() {
            if (pollingTimer) clearInterval(pollingTimer);
        });
    });
    </script>
@endsection
