@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Header Section -->
    <header class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">Dashboard IDRECT</h1>
            <p class="text-xs font-medium text-slate-500 mt-1">Analitik real-time performa proses perakitan kabel body (Wiring Harness).</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between xl:justify-end gap-3.5">
            <!-- Mode Switcher Segmented Control (Total, Final Assy, Pre Assy) -->
            <div class="w-full sm:w-auto" role="region" aria-label="Mode Data Dashboard">
                <div class="inline-flex w-full sm:w-auto items-center p-1 bg-slate-200/70 border border-slate-200 rounded-xl shadow-2xs gap-1" role="tablist" aria-label="Mode Data">
                    <button type="button"
                        id="btnModeTotal"
                        data-mode="total"
                        role="tab"
                        aria-pressed="{{ ($currentMode ?? 'total') === 'total' ? 'true' : 'false' }}"
                        aria-selected="{{ ($currentMode ?? 'total') === 'total' ? 'true' : 'false' }}"
                        class="btn-dashboard-mode flex-1 sm:flex-initial min-h-[40px] px-3.5 py-2 text-xs rounded-lg transition-all duration-150 inline-flex items-center justify-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 border {{ ($currentMode ?? 'total') === 'total' ? 'bg-white text-slate-900 shadow-xs border-slate-200/80 font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 border-transparent font-bold' }}">
                        <span class="mode-dot w-2 h-2 rounded-full transition-colors {{ ($currentMode ?? 'total') === 'total' ? 'bg-indigo-600' : 'bg-slate-300' }}"></span>
                        <span>Total</span>
                    </button>

                    <button type="button"
                        id="btnModeFinal"
                        data-mode="final"
                        role="tab"
                        aria-pressed="{{ ($currentMode ?? 'total') === 'final' ? 'true' : 'false' }}"
                        aria-selected="{{ ($currentMode ?? 'total') === 'final' ? 'true' : 'false' }}"
                        class="btn-dashboard-mode flex-1 sm:flex-initial min-h-[40px] px-3.5 py-2 text-xs rounded-lg transition-all duration-150 inline-flex items-center justify-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 border {{ ($currentMode ?? 'total') === 'final' ? 'bg-white text-slate-900 shadow-xs border-slate-200/80 font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 border-transparent font-bold' }}">
                        <span class="mode-dot w-2 h-2 rounded-full transition-colors {{ ($currentMode ?? 'total') === 'final' ? 'bg-teal-600' : 'bg-slate-300' }}"></span>
                        <span>Final Assy</span>
                    </button>

                    <button type="button"
                        id="btnModePre"
                        data-mode="pre"
                        role="tab"
                        aria-pressed="{{ ($currentMode ?? 'total') === 'pre' ? 'true' : 'false' }}"
                        aria-selected="{{ ($currentMode ?? 'total') === 'pre' ? 'true' : 'false' }}"
                        class="btn-dashboard-mode flex-1 sm:flex-initial min-h-[40px] px-3.5 py-2 text-xs rounded-lg transition-all duration-150 inline-flex items-center justify-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30 border {{ ($currentMode ?? 'total') === 'pre' ? 'bg-white text-slate-900 shadow-xs border-slate-200/80 font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 border-transparent font-bold' }}">
                        <span class="mode-dot w-2 h-2 rounded-full transition-colors {{ ($currentMode ?? 'total') === 'pre' ? 'bg-blue-600' : 'bg-slate-300' }}"></span>
                        <span>Pre Assy</span>
                    </button>
                </div>
            </div>

            <!-- Admin Profile Card -->
            <div class="flex items-center space-x-3 shrink-0 self-end sm:self-auto">
                <div class="text-right">
                    <span class="block text-xs font-extrabold text-slate-900">{{ session('user_name', 'Admin QA') }}</span>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Administrator</span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-red-50 text-brand flex items-center justify-center border border-red-100 font-black text-xs shadow-2xs">
                    {{ strtoupper(substr(session('user_name', 'A'), 0, 1)) }}
                </div>
            </div>
        </div>
    </header>

    <!-- Success Alert Banner -->
    @if (session('success'))
        <div class="mb-6 bg-emerald-50 text-emerald-800 text-xs font-bold p-4 rounded-2xl border border-emerald-200 flex items-center space-x-2 shadow-xs">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- KPI Cards Grid (Using Reusable metric-card) -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Card 1: Total Defect -->
        <x-metric-card
            label="Total Defect"
            :value="number_format($totalDefect, 0, ',', '.')"
            valueId="stat-total-defect"
            iconBg="bg-red-50"
            iconBorder="border-red-100"
            iconColor="text-brand"
        >
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </x-slot:icon>
            <x-slot:trend>
                <x-status-badge type="defect">Defect QA</x-status-badge>
                <span class="text-[10px] text-gray-400 font-medium">Akumulatif</span>
            </x-slot:trend>
        </x-metric-card>

        <!-- Card 2: Defect Hari Ini -->
        <x-metric-card
            label="Defect Hari Ini"
            :value="(string)$defectToday"
            valueId="stat-defect-today"
            iconBg="bg-amber-50"
            iconBorder="border-amber-100"
            iconColor="text-pre-assy-warning"
        >
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </x-slot:icon>
            <x-slot:trend>
                <x-status-badge type="warning">Live Today</x-status-badge>
                <span class="text-[10px] text-gray-400 font-medium">Hari ini</span>
            </x-slot:trend>
        </x-metric-card>

        <!-- Card 3: Active Users -->
        <x-metric-card
            label="Active Users"
            :value="(string)$activeUsers"
            valueId="stat-active-users"
            iconBg="bg-teal-50"
            iconBorder="border-teal-100"
            iconColor="text-final-assy"
        >
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"></path>
                </svg>
            </x-slot:icon>
            <x-slot:trend>
                <span class="inline-flex items-center space-x-1 text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md">
                    <span class="w-1.5 h-1.5 rounded-full bg-final-assy animate-pulse"></span>
                    <span>Online</span>
                </span>
                <span class="text-[10px] text-gray-400 font-medium">Aktif</span>
            </x-slot:trend>
        </x-metric-card>

        <!-- Card 4: Total Users -->
        <x-metric-card
            label="Total Users"
            :value="(string)$totalUsers"
            valueId="stat-total-users"
            iconBg="bg-slate-100"
            iconBorder="border-slate-200"
            iconColor="text-pre-assy"
        >
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </x-slot:icon>
            <x-slot:trend>
                <x-status-badge type="pre-assy">Terdaftar</x-status-badge>
                <span class="text-[10px] text-gray-400 font-medium">Akun</span>
            </x-slot:trend>
        </x-metric-card>
    </section>

        <!-- 2 Columns Grid: Final Assy (Left) & Pre Assy (Right) -->
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8" id="analyticsGridContainer">
            <!-- KOLOM UTAMA: FINAL ASSY -->
            <div id="columnFinalAssy" class="space-y-6 {{ ($currentMode ?? 'total') === 'final' ? 'xl:col-span-2' : (($currentMode ?? 'total') === 'pre' ? 'hidden' : '') }}">
                <!-- Mode Total Wrapper (Final Assy) -->
                <div id="finalAssyModeTotalWrapper" class="{{ ($currentMode ?? 'total') === 'total' ? 'space-y-6' : 'hidden space-y-6' }}">
                <!-- Summary Cards Final Assy (Sinkron Filter Mandiri) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Summary DPM / Ringkasan Final Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider" id="summaryTitleDpmFinal">{{ ($currentMode ?? 'total') === 'final' ? 'DPM Final Assy' : 'Ringkasan Final Assy' }}</span>
                            <span class="text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/80" id="summaryPeriodDpmFinal">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmFinal">-</span>
                            <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmFinal">{{ ($currentMode ?? 'total') === 'final' ? 'DPM' : 'pcs' }}</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmFinal">
                            {{ ($currentMode ?? 'total') === 'final' ? 'Kalkulasi total defect / inspect' : 'Memuat perbandingan Assembly vs Internal...' }}
                        </p>
                    </div>

                    <!-- Summary Top Defect Final Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider" id="summaryTitleParetoFinal">Top Defect Final</span>
                            <span class="text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/80" id="summaryPeriodParetoFinal">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2 min-w-0">
                            <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate flex-1 min-w-0" id="summaryValParetoFinal">-</span>
                            <span class="text-xs font-bold text-teal-700 font-mono shrink-0" id="summaryCountParetoFinal"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubParetoFinal">Sub defect peringkat 1</p>
                    </div>
                </div>

                <!-- 1. Card: Tren Defect / DPM Final Assy -->
                <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.06)] transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600 inline-block shadow-xs"></span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight" id="cardTitleDpmFinal">{{ ($currentMode ?? 'total') === 'final' ? 'DPM Final Assy' : 'Tren Defect Final Assy' }}</h3>
                                    <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md" id="cardBadgeDpmFinal">{{ ($currentMode ?? 'total') === 'final' ? 'DPM Final Assy' : 'Quantity Defect' }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5" id="cardSubDpmFinal">{{ ($currentMode ?? 'total') === 'final' ? 'Defect Per Million tren per periode dengan target' : 'Tren kuantitas Internal Defect vs Assembly Defect per periode' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="loadingDpmFinal" class="hidden items-center space-x-1.5 text-[11px] text-teal-800 bg-teal-50 border border-teal-200/80 px-2.5 py-0.5 rounded-full font-bold">
                                <svg class="animate-spin h-3 w-3 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Memuat...</span>
                            </span>
                            <span class="flex items-center space-x-1 text-[11px] text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full font-bold shadow-2xs">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                </span>
                                <span>Live (8s)</span>
                            </span>
                        </div>
                    </div>

                    <!-- Error Banner -->
                    <div id="errorDpmFinal" class="hidden mt-3 p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-700">
                        <span id="errorMessageDpmFinal">Gagal memuat analitik Final Assy</span>
                    </div>

                    <!-- Card Filter Toolbar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 mt-3 mb-4 p-3 bg-slate-50/70 rounded-xl border border-slate-100 items-end">
                        <!-- Rentang Tanggal -->
                        <div class="min-w-0">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-semibold text-slate-400 mb-0.5">Mulai</span>
                                    <input type="date" id="filterStartDateDpmFinal"
                                        class="w-full border border-gray-200 rounded-xl px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition shadow-xs">
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-semibold text-slate-400 mb-0.5">Selesai</span>
                                    <input type="date" id="filterEndDateDpmFinal"
                                        class="w-full border border-gray-200 rounded-xl px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition shadow-xs">
                                </div>
                            </div>
                        </div>

                        <!-- Pengelompokan Tren (Hari / Minggu / Bulan) -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pengelompokan</label>
                            <div class="inline-flex w-full bg-slate-200/70 p-0.5 rounded-lg border border-slate-200" role="group">
                                <button type="button" data-val="day" class="btn-group-by-dpm-final flex-1 py-1 text-xs font-bold rounded-md transition-all text-center bg-teal-600 text-white shadow-2xs">Hari</button>
                                <button type="button" data-val="week" class="btn-group-by-dpm-final flex-1 py-1 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Minggu</button>
                                <button type="button" data-val="month" class="btn-group-by-dpm-final flex-1 py-1 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Bulan</button>
                            </div>
                        </div>

                        <!-- Conveyor + Reset -->
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 {{ ($currentMode ?? 'total') === 'final' ? '' : 'hidden' }}" id="wrapFilterConveyorDpmFinal">
                                <label for="filterConveyorDpmFinal" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Conveyor</label>
                                <div class="relative">
                                    <select id="filterConveyorDpmFinal" class="w-full appearance-none border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 px-2.5 pr-7 py-1.5 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-2xs">
                                        <option value="all">Semua Conveyor</option>
                                        @if(isset($finalAssyConveyors))
                                            @foreach($finalAssyConveyors as $conveyor)
                                                <option value="{{ $conveyor }}">{{ $conveyor }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                                <p id="conveyorNoticeDpmFinal" class="hidden text-[10px] text-amber-600 font-medium mt-1 leading-tight flex items-center gap-1"></p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-transparent uppercase tracking-wider mb-1 select-none">Aksi</label>
                                <button type="button" id="btnResetDpmFinal" title="Reset filter ke default" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Canvas & Empty State -->
                    <div id="wrapperDpmFinal" class="relative h-72 w-full">
                        <canvas id="chartDpmFinal"></canvas>
                    </div>
                    <div id="emptyDpmFinal" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                        <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                        <span class="text-[11px] text-slate-400 mt-0.5" id="emptySubDpmFinal">Ubah rentang tanggal atau pilih filter lain</span>
                    </div>
                    <div id="noteDpmFinal" class="hidden mt-2.5 pt-2.5 border-t border-slate-100 space-y-1"></div>
                </div>

                <!-- 2. Card: Pareto Final Assy -->
                <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.06)] transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600 inline-block shadow-xs"></span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight" id="cardTitleParetoFinal">Pareto Defect Final Assy</h3>
                                    <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md" id="cardBadgeParetoFinal">Final Assy</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5" id="cardSubParetoFinal">Internal Defect + Assembly Defect & persentase kumulatif</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="loadingParetoFinal" class="hidden items-center space-x-1.5 text-[11px] text-teal-800 bg-teal-50 border border-teal-200/80 px-2.5 py-0.5 rounded-full font-bold">
                                <svg class="animate-spin h-3 w-3 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Memuat...</span>
                            </span>
                            <span class="flex items-center space-x-1 text-[11px] text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full font-bold shadow-2xs">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                </span>
                                <span>Live (8s)</span>
                            </span>
                        </div>
                    </div>

                    <!-- Error Banner -->
                    <div id="errorParetoFinal" class="hidden mt-3 p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-700">
                        <span id="errorMessageParetoFinal">Gagal memuat analitik Pareto Final Assy</span>
                    </div>

                    <!-- Card Filter Toolbar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-3 mb-4 p-3 bg-slate-50/70 rounded-xl border border-slate-100 items-end">
                        <!-- Rentang Tanggal -->
                        <div class="min-w-0">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-semibold text-slate-400 mb-0.5">Mulai</span>
                                    <input type="date" id="filterStartDateParetoFinal"
                                        class="w-full border border-gray-200 rounded-xl px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition shadow-xs">
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-semibold text-slate-400 mb-0.5">Selesai</span>
                                    <input type="date" id="filterEndDateParetoFinal"
                                        class="w-full border border-gray-200 rounded-xl px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition shadow-xs">
                                </div>
                            </div>
                        </div>

                    <!-- Conveyor + Reset -->
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 {{ ($currentMode ?? 'total') === 'final' ? '' : 'hidden' }}" id="wrapFilterConveyorParetoFinal">
                            <label for="filterConveyorParetoFinal" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Conveyor</label>
                            <div class="relative">
                                <select id="filterConveyorParetoFinal" class="w-full appearance-none border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 px-2.5 pr-7 py-1.5 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-2xs">
                                    <option value="all">Semua Conveyor</option>
                                    @if(isset($finalAssyConveyors))
                                        @foreach($finalAssyConveyors as $conveyor)
                                            <option value="{{ $conveyor }}">{{ $conveyor }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                            <p id="conveyorNoticeParetoFinal" class="hidden text-[10px] text-amber-600 font-medium mt-1 leading-tight flex items-center gap-1"></p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-transparent uppercase tracking-wider mb-1 select-none">Aksi</label>
                            <button type="button" id="btnResetParetoFinal" title="Reset filter pareto ke default" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center space-x-1">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Canvas & Empty State -->
                <div id="wrapperParetoFinal" class="relative h-72 w-full">
                    <canvas id="chartParetoFinal"></canvas>
                </div>
                <div id="emptyParetoFinal" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                    <span class="text-[11px] text-slate-400 mt-0.5" id="emptySubParetoFinal">Ubah rentang tanggal atau pilih filter conveyor lain</span>
                </div>
                <!-- Hint Klik Interaktif -->
                <div class="mt-2 text-center">
                    <span class="inline-flex items-center space-x-1 text-[10px] font-semibold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60 cursor-default">
                        <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                        <span id="hintParetoFinal">Klik batang sub defect untuk melihat Top 5 Conveyor</span>
                    </span>
                </div>

                <!-- Drilldown Sub-Panel (3-Level Drilldown: Defect -> Carline -> Area Ditemukan) -->
                <div id="drilldownWrapperParetoFinal" class="hidden mt-4 pt-3.5 border-t border-slate-200/80 bg-slate-50/80 rounded-xl p-3.5 transition-all">
                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                        <div class="flex items-center space-x-2 min-w-0 flex-1">
                            <span class="w-2 h-2 rounded-full bg-teal-600 shrink-0"></span>
                            <div class="min-w-0">
                                <div class="flex items-center space-x-1.5 flex-wrap text-xs">
                                    <span class="font-extrabold text-slate-900 tracking-tight" id="drilldownTitleParetoFinal">Breakdown per Carline</span>
                                    <span class="text-slate-400">/</span>
                                    <span class="text-[10px] font-mono font-bold text-teal-800 bg-teal-100/70 border border-teal-200 px-1.5 py-0.5 rounded truncate max-w-[200px]" id="drilldownBadgeParetoFinal">-</span>
                                    <span id="drilldownCarlineBadgeWrapFinal" class="hidden items-center space-x-1">
                                        <span class="text-slate-400">/</span>
                                        <span class="text-[10px] font-mono font-bold text-amber-800 bg-amber-100/70 border border-amber-200 px-1.5 py-0.5 rounded truncate max-w-[200px]" id="drilldownCarlineBadgeFinal">-</span>
                                    </span>
                                    <span id="drilldownSourceParetoFinal" class="text-[10px] font-bold text-teal-700 bg-teal-50 border border-teal-200 px-1.5 py-0.5 rounded hidden">Final Assy</span>
                                </div>
                                <span class="text-[10px] text-slate-400 font-medium" id="drilldownSubParetoFinal">Klik batang carline untuk melihat breakdown per Area Ditemukan</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <button type="button" id="btnBackDrilldownParetoFinal" class="hidden text-xs font-bold text-teal-700 hover:text-teal-900 bg-teal-50 hover:bg-teal-100 border border-teal-200 px-2.5 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer" title="Kembali ke level sebelumnya">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span id="btnBackTextFinal">Kembali</span>
                            </button>
                            <button type="button" id="btnCloseDrilldownParetoFinal" class="text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-200 px-2 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer" title="Tutup Breakdown">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span>Tutup</span>
                            </button>
                        </div>
                    </div>

                    <!-- Loading & Empty state -->
                    <div id="drilldownLoadingParetoFinal" class="hidden py-6 text-center text-xs font-semibold text-teal-700">
                        <svg class="animate-spin inline-block h-4 w-4 text-teal-600 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span id="drilldownLoadingTextParetoFinal">Memuat breakdown...</span>
                    </div>
                    <div id="drilldownEmptyParetoFinal" class="hidden py-6 text-center text-xs font-medium text-slate-400 italic">
                        Tidak ada data untuk defect ini
                    </div>

                    <!-- Canvas Drilldown -->
                    <div id="drilldownCanvasWrapParetoFinal" class="relative h-48 w-full">
                        <canvas id="chartDrilldownParetoFinal"></canvas>
                    </div>
                </div>
            </div>
            </div>

            <!-- Mode Final Wrapper (Final Assy: 2 Kolom Kategori Internal & Assembly) -->
            <div id="finalAssyModeFinalWrapper" class="{{ ($currentMode ?? 'total') === 'final' ? 'space-y-6' : 'hidden space-y-6' }}">
                <!-- Toolbar Filter Mode Final Assy -->
                <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-2xl p-4 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600 inline-block shadow-xs"></span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Filter Analitik Final Assy</h3>
                                <p class="text-xs text-slate-500">Filter sinkron untuk Internal Defect, Assembly Defect, dan Top 5 Area</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="loadingFinalAssyMode" class="hidden items-center space-x-1.5 text-[11px] text-teal-800 bg-teal-50 border border-teal-200/80 px-2.5 py-0.5 rounded-full font-bold">
                                <svg class="animate-spin h-3 w-3 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Memuat...</span>
                            </span>
                            <span class="flex items-center space-x-1 text-[11px] text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full font-bold shadow-2xs">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                </span>
                                <span>Live (8s)</span>
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3 items-end">
                        <!-- Rentang Tanggal -->
                        <div class="min-w-0">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" id="filterStartDateFinalAssy"
                                    class="w-full border border-gray-200 rounded-xl px-2 py-1.5 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition shadow-xs">
                                <input type="date" id="filterEndDateFinalAssy"
                                    class="w-full border border-gray-200 rounded-xl px-2 py-1.5 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition shadow-xs">
                            </div>
                        </div>

                        <!-- Pengelompokan Tren -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pengelompokan</label>
                            <div class="inline-flex w-full bg-slate-200/70 p-0.5 rounded-lg border border-slate-200" role="group">
                                <button type="button" data-val="day" class="btn-group-by-final-assy flex-1 py-1.5 text-xs font-bold rounded-md transition-all text-center bg-teal-600 text-white shadow-2xs">Hari</button>
                                <button type="button" data-val="week" class="btn-group-by-final-assy flex-1 py-1.5 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Minggu</button>
                                <button type="button" data-val="month" class="btn-group-by-final-assy flex-1 py-1.5 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Bulan</button>
                            </div>
                        </div>

                        <!-- Conveyor -->
                        <div id="wrapFilterConveyorFinalAssy">
                            <label for="filterConveyorFinalAssy" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Conveyor</label>
                            <div class="relative">
                                <select id="filterConveyorFinalAssy" class="w-full appearance-none border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 px-2.5 pr-7 py-2 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-2xs">
                                    <option value="all">Semua Conveyor</option>
                                    @if(isset($finalAssyConveyors))
                                        @foreach($finalAssyConveyors as $conveyor)
                                            <option value="{{ $conveyor }}">{{ $conveyor }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Reset -->
                        <div>
                            <label class="block text-[10px] font-bold text-transparent uppercase tracking-wider mb-1 select-none">Aksi</label>
                            <button type="button" id="btnResetFinalAssy" title="Reset filter ke default" class="w-full py-2 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center justify-center space-x-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Reset Filter</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2 Columns Grid: Internal Defect (Left) & Assembly Defect (Right) -->
                <div id="categoryGridFinalAssy" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <!-- KOLOM 1: INTERNAL DEFECT -->
                    <div id="colFinalInternal" class="space-y-6">
                        <!-- Summary Cards Internal Defect -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">DPM Internal Defect</span>
                                    <span class="text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/80" id="summaryPeriodDpmFinalInternal">Memuat...</span>
                                </div>
                                <div class="mt-2 flex items-baseline space-x-2">
                                    <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmFinalInternal">-</span>
                                    <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmFinalInternal">DPM</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmFinalInternal">Kalkulasi internal defect / total inspect</p>
                            </div>

                            <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Top Defect Internal</span>
                                    <span class="text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/80" id="summaryPeriodParetoFinalInternal">Memuat...</span>
                                </div>
                                <div class="mt-2 flex items-baseline space-x-2 min-w-0">
                                    <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate flex-1 min-w-0" id="summaryValParetoFinalInternal">-</span>
                                    <span class="text-xs font-bold text-teal-700 font-mono shrink-0" id="summaryCountParetoFinalInternal"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubParetoFinalInternal">Sub defect peringkat 1</p>
                            </div>
                        </div>

                        <!-- Card: Tren DPM Internal Defect -->
                        <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-600 inline-block shadow-xs"></span>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tren DPM Internal Defect</h3>
                                            <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md">Internal Defect</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">DPM Internal Defect per periode vs garis Target DPM</p>
                                    </div>
                                </div>
                            </div>
                            <div id="wrapperDpmFinalInternal" class="relative h-72 w-full mt-4">
                                <canvas id="chartDpmFinalInternal"></canvas>
                            </div>
                            <div id="emptyDpmFinalInternal" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mt-4">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                            </div>
                            <div id="noteDpmFinalInternal" class="hidden mt-2.5 pt-2.5 border-t border-slate-100 space-y-1"></div>
                        </div>

                        <!-- Card: Pareto Internal Defect -->
                        <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-600 inline-block shadow-xs"></span>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Pareto Internal Defect</h3>
                                            <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md">Internal Defect</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">Kuantitas defect internal & persentase kumulatif (0-100%)</p>
                                    </div>
                                </div>
                            </div>
                            <div id="wrapperParetoFinalInternal" class="relative h-72 w-full mt-4">
                                <canvas id="chartParetoFinalInternal"></canvas>
                            </div>
                            <div id="emptyParetoFinalInternal" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mt-4">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                            </div>
                            <div class="mt-2 text-center">
                                <span class="inline-flex items-center space-x-1 text-[10px] font-semibold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60 cursor-default">
                                    <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                                    <span>Klik batang sub defect untuk breakdown (Internal)</span>
                                </span>
                            </div>

                            <!-- Drilldown Internal Defect -->
                            <div id="drilldownWrapperParetoFinalInternal" class="hidden mt-4 pt-3.5 border-t border-slate-200/80 bg-slate-50/80 rounded-xl p-3.5 transition-all">
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                                    <div class="flex items-center space-x-2 min-w-0 flex-1">
                                        <span class="w-2 h-2 rounded-full bg-teal-600 shrink-0"></span>
                                        <div class="min-w-0">
                                            <div class="flex items-center space-x-1.5 flex-wrap text-xs">
                                                <span class="font-extrabold text-slate-900 tracking-tight" id="drilldownTitleParetoFinalInternal">Breakdown per Carline / Conveyor</span>
                                                <span class="text-slate-400">/</span>
                                                <span class="text-[10px] font-mono font-bold text-teal-800 bg-teal-100/70 border border-teal-200 px-1.5 py-0.5 rounded truncate max-w-[180px]" id="drilldownBadgeParetoFinalInternal">-</span>
                                                <span id="drilldownCarlineBadgeWrapFinalInternal" class="hidden items-center space-x-1">
                                                    <span class="text-slate-400">/</span>
                                                    <span class="text-[10px] font-mono font-bold text-amber-800 bg-amber-100/70 border border-amber-200 px-1.5 py-0.5 rounded truncate max-w-[180px]" id="drilldownCarlineBadgeFinalInternal">-</span>
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium" id="drilldownSubParetoFinalInternal">Berdasarkan data Internal Defect</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 shrink-0">
                                        <button type="button" id="btnBackDrilldownParetoFinalInternal" class="hidden text-xs font-bold text-teal-700 hover:text-teal-900 bg-teal-50 hover:bg-teal-100 border border-teal-200 px-2.5 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                            <span id="btnBackTextFinalInternal">Kembali</span>
                                        </button>
                                        <button type="button" id="btnCloseDrilldownParetoFinalInternal" class="text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-200 px-2 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            <span>Tutup</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="drilldownLoadingParetoFinalInternal" class="hidden py-6 text-center text-xs font-semibold text-teal-700">
                                    <svg class="animate-spin inline-block h-4 w-4 text-teal-600 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Memuat breakdown...</span>
                                </div>
                                <div id="drilldownEmptyParetoFinalInternal" class="hidden py-6 text-center text-xs font-medium text-slate-400 italic">
                                    Tidak ada data untuk defect ini
                                </div>
                                <div id="drilldownCanvasWrapParetoFinalInternal" class="relative h-48 w-full">
                                    <canvas id="chartDrilldownParetoFinalInternal"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM 2: ASSEMBLY DEFECT -->
                    <div id="colFinalAssembly" class="space-y-6">
                        <!-- Summary Cards Assembly Defect -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white border border-slate-200 border-t-4 border-t-indigo-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">DPM Assembly Defect</span>
                                    <span class="text-[10px] font-semibold text-indigo-800 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200/80" id="summaryPeriodDpmFinalAssembly">Memuat...</span>
                                </div>
                                <div class="mt-2 flex items-baseline space-x-2">
                                    <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmFinalAssembly">-</span>
                                    <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmFinalAssembly">DPM</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmFinalAssembly">Kalkulasi assembly defect / total inspect</p>
                            </div>

                            <div class="bg-white border border-slate-200 border-t-4 border-t-indigo-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Top Defect Assembly</span>
                                    <span class="text-[10px] font-semibold text-indigo-800 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200/80" id="summaryPeriodParetoFinalAssembly">Memuat...</span>
                                </div>
                                <div class="mt-2 flex items-baseline space-x-2 min-w-0">
                                    <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate flex-1 min-w-0" id="summaryValParetoFinalAssembly">-</span>
                                    <span class="text-xs font-bold text-indigo-700 font-mono shrink-0" id="summaryCountParetoFinalAssembly"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubParetoFinalAssembly">Sub defect peringkat 1</p>
                            </div>
                        </div>

                        <!-- Card: Tren DPM Assembly Defect -->
                        <div class="bg-white border border-slate-200 border-t-4 border-t-indigo-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 inline-block shadow-xs"></span>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tren DPM Assembly Defect</h3>
                                            <span class="text-[10px] font-bold text-indigo-800 bg-indigo-50 border border-indigo-200/80 px-2 py-0.5 rounded-md">Assembly Defect</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">DPM Assembly Defect per periode vs garis Target DPM</p>
                                    </div>
                                </div>
                            </div>
                            <div id="wrapperDpmFinalAssembly" class="relative h-72 w-full mt-4">
                                <canvas id="chartDpmFinalAssembly"></canvas>
                            </div>
                            <div id="emptyDpmFinalAssembly" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mt-4">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                            </div>
                            <div id="noteDpmFinalAssembly" class="hidden mt-2.5 pt-2.5 border-t border-slate-100 space-y-1"></div>
                        </div>

                        <!-- Card: Pareto Assembly Defect -->
                        <div class="bg-white border border-slate-200 border-t-4 border-t-indigo-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 inline-block shadow-xs"></span>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Pareto Assembly Defect</h3>
                                            <span class="text-[10px] font-bold text-indigo-800 bg-indigo-50 border border-indigo-200/80 px-2 py-0.5 rounded-md">Assembly Defect</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">Kuantitas defect assembly & persentase kumulatif (0-100%)</p>
                                    </div>
                                </div>
                            </div>
                            <div id="wrapperParetoFinalAssembly" class="relative h-72 w-full mt-4">
                                <canvas id="chartParetoFinalAssembly"></canvas>
                            </div>
                            <div id="emptyParetoFinalAssembly" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mt-4">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                            </div>
                            <div class="mt-2 text-center">
                                <span class="inline-flex items-center space-x-1 text-[10px] font-semibold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60 cursor-default">
                                    <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                                    <span>Klik batang sub defect untuk breakdown (Assembly)</span>
                                </span>
                            </div>

                            <!-- Drilldown Assembly Defect -->
                            <div id="drilldownWrapperParetoFinalAssembly" class="hidden mt-4 pt-3.5 border-t border-slate-200/80 bg-slate-50/80 rounded-xl p-3.5 transition-all">
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                                    <div class="flex items-center space-x-2 min-w-0 flex-1">
                                        <span class="w-2 h-2 rounded-full bg-indigo-600 shrink-0"></span>
                                        <div class="min-w-0">
                                            <div class="flex items-center space-x-1.5 flex-wrap text-xs">
                                                <span class="font-extrabold text-slate-900 tracking-tight" id="drilldownTitleParetoFinalAssembly">Breakdown per Carline / Conveyor</span>
                                                <span class="text-slate-400">/</span>
                                                <span class="text-[10px] font-mono font-bold text-indigo-800 bg-indigo-100/70 border border-indigo-200 px-1.5 py-0.5 rounded truncate max-w-[180px]" id="drilldownBadgeParetoFinalAssembly">-</span>
                                                <span id="drilldownCarlineBadgeWrapFinalAssembly" class="hidden items-center space-x-1">
                                                    <span class="text-slate-400">/</span>
                                                    <span class="text-[10px] font-mono font-bold text-amber-800 bg-amber-100/70 border border-amber-200 px-1.5 py-0.5 rounded truncate max-w-[180px]" id="drilldownCarlineBadgeFinalAssembly">-</span>
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium" id="drilldownSubParetoFinalAssembly">Berdasarkan data Assembly Defect</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 shrink-0">
                                        <button type="button" id="btnBackDrilldownParetoFinalAssembly" class="hidden text-xs font-bold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2.5 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                            <span id="btnBackTextFinalAssembly">Kembali</span>
                                        </button>
                                        <button type="button" id="btnCloseDrilldownParetoFinalAssembly" class="text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-200 px-2 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            <span>Tutup</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="drilldownLoadingParetoFinalAssembly" class="hidden py-6 text-center text-xs font-semibold text-indigo-700">
                                    <svg class="animate-spin inline-block h-4 w-4 text-indigo-600 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Memuat breakdown...</span>
                                </div>
                                <div id="drilldownEmptyParetoFinalAssembly" class="hidden py-6 text-center text-xs font-medium text-slate-400 italic">
                                    Tidak ada data untuk defect ini
                                </div>
                                <div id="drilldownCanvasWrapParetoFinalAssembly" class="relative h-48 w-full">
                                    <canvas id="chartDrilldownParetoFinalAssembly"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Card: Top 5 Area Ditemukan (Final Assy) -->
            <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.06)] transition-all duration-200 {{ ($currentMode ?? 'total') === 'final' ? '' : 'hidden' }}" id="cardTop5AreaFinal">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-teal-600 inline-block shadow-xs"></span>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Top 5 Area Ditemukan</h3>
                                <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md">Final Assy</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Top 5 area dengan akumulasi kuantitas defect (PCS) terbanyak berdasarkan filter aktif. Zero defect dikecualikan.</p>
                        </div>
                    </div>
                </div>
                <div id="wrapperTop5AreaFinal" class="relative h-56 w-full mt-4">
                    <canvas id="chartTop5AreaFinal"></canvas>
                    <div id="emptyTop5AreaFinal" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-white/90">
                        <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <p class="text-xs font-semibold text-slate-400">Belum ada data pada periode ini</p>
                    </div>
                </div>
            </div>
        </div>

            <!-- KOLOM KANAN: PRE ASSY -->
            <div id="columnPreAssy" class="space-y-6 {{ ($currentMode ?? 'total') === 'pre' ? 'xl:col-span-2' : (($currentMode ?? 'total') === 'final' ? 'hidden' : '') }}">
                <!-- Mode Total Wrapper (Pre Assy) -->
                <div id="preAssyModeTotalWrapper" class="{{ ($currentMode ?? 'total') === 'total' ? 'space-y-6' : 'hidden space-y-6' }}">
                <!-- Summary Cards Pre Assy (Sinkron Filter Mandiri) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Summary DPM / Ringkasan Pre Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider" id="summaryTitleDpmPre">{{ ($currentMode ?? 'total') === 'pre' ? 'PPM Pre Assy' : 'Ringkasan Pre Assy' }}</span>
                            <span class="text-[10px] font-semibold text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/80" id="summaryPeriodDpmPre">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmPre">-</span>
                            <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmPre">{{ ($currentMode ?? 'total') === 'pre' ? 'PPM' : 'pcs' }}</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmPre">{{ ($currentMode ?? 'total') === 'pre' ? 'Kalkulasi total defect / inspect' : 'Memuat perbandingan Operator vs Inspektor...' }}</p>
                    </div>

                    <!-- Summary Top Defect Pre Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Top Defect Pre</span>
                            <span class="text-[10px] font-semibold text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/80" id="summaryPeriodParetoPre">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2 min-w-0">
                            <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate flex-1 min-w-0" id="summaryValParetoPre">-</span>
                            <span class="text-xs font-bold text-blue-700 font-mono shrink-0" id="summaryCountParetoPre"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubParetoPre">Sub defect peringkat 1</p>
                    </div>
                </div>

                <!-- 3. Card: DPM Pre Assy -->
                <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.06)] transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block shadow-xs"></span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight" id="cardTitleDpmPre">{{ ($currentMode ?? 'total') === 'pre' ? 'PPM Pre Assy' : 'Tren Defect Pre Assy' }}</h3>
                                    <span class="text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md" id="cardBadgeDpmPre">{{ ($currentMode ?? 'total') === 'pre' ? 'PPM Pre Assy' : 'Quantity Defect' }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5" id="cardSubDpmPre">{{ ($currentMode ?? 'total') === 'pre' ? 'Parts Per Million tren per periode dengan target' : 'Tren kuantitas temuan Operator vs Inspektor per periode' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="loadingDpmPre" class="hidden items-center space-x-1.5 text-[11px] text-blue-800 bg-blue-50 border border-blue-200/80 px-2.5 py-0.5 rounded-full font-bold">
                                <svg class="animate-spin h-3 w-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Memuat...</span>
                            </span>
                            <span class="flex items-center space-x-1 text-[11px] text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full font-bold shadow-2xs">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                </span>
                                <span>Live (8s)</span>
                            </span>
                        </div>
                    </div>

                    <!-- Error Banner -->
                    <div id="errorDpmPre" class="hidden mt-3 p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-700">
                        <span id="errorMessageDpmPre">Gagal memuat analitik PPM Pre Assy</span>
                    </div>

                    <!-- Card Filter Toolbar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 mt-3 mb-4 p-3 bg-slate-50/70 rounded-xl border border-slate-100 items-end">
                        <!-- Rentang Tanggal -->
                        <div class="min-w-0">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-semibold text-slate-400 mb-0.5">Mulai</span>
                                    <input type="date" id="filterStartDateDpmPre"
                                        class="w-full border border-gray-200 rounded-xl px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-xs">
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-semibold text-slate-400 mb-0.5">Selesai</span>
                                    <input type="date" id="filterEndDateDpmPre"
                                        class="w-full border border-gray-200 rounded-xl px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-xs">
                                </div>
                            </div>
                        </div>

                        <!-- Pengelompokan Tren (Hari / Minggu / Bulan) -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pengelompokan</label>
                            <div class="inline-flex w-full bg-slate-200/70 p-0.5 rounded-lg border border-slate-200" role="group">
                                <button type="button" data-val="day" class="btn-group-by-dpm-pre flex-1 py-1 text-xs font-bold rounded-md transition-all text-center bg-blue-600 text-white shadow-2xs">Hari</button>
                                <button type="button" data-val="week" class="btn-group-by-dpm-pre flex-1 py-1 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Minggu</button>
                                <button type="button" data-val="month" class="btn-group-by-dpm-pre flex-1 py-1 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Bulan</button>
                            </div>
                        </div>

                        <!-- Carline + Reset -->
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 {{ ($currentMode ?? 'total') === 'pre' ? '' : 'hidden' }}" id="wrapFilterCarlineDpmPre">
                                <label for="filterCarlineDpmPre" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Carline</label>
                                <div class="relative">
                                    <select id="filterCarlineDpmPre" class="w-full appearance-none border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 px-2.5 pr-7 py-1.5 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 cursor-pointer transition shadow-2xs">
                                        <option value="all">Semua Carline</option>
                                        @if(isset($preAssyCarlines))
                                            @foreach($preAssyCarlines as $carline)
                                                <option value="{{ $carline->id }}">{{ $carline->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-transparent uppercase tracking-wider mb-1 select-none">Aksi</label>
                                <button type="button" id="btnResetDpmPre" title="Reset filter PPM Pre Assy ke default" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Canvas & Empty State -->
                    <div id="wrapperDpmPre" class="relative h-72 w-full">
                        <canvas id="chartDpmPre"></canvas>
                    </div>
                    <div id="emptyDpmPre" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                        <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p class="text-xs font-semibold text-slate-600" id="emptyMsgDpmPre">Belum ada data pada periode ini</p>
                        <span class="text-[11px] text-slate-400 mt-0.5" id="emptySubDpmPre">Ubah rentang tanggal atau pilih filter lain</span>
                    </div>
                    <div id="noteDpmPre" class="hidden mt-2.5 pt-2.5 border-t border-slate-100 space-y-1"></div>
                </div>

                <!-- 4. Card: Pareto Pre Assy -->
                <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.06)] transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block shadow-xs"></span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight" id="cardTitleParetoPre">Pareto Sub Defect Pre Assy</h3>
                                    <span class="text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md" id="cardBadgeParetoPre">Pre Assy</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5" id="cardSubParetoPre">Temuan Operator + Inspektor & persentase kumulatif</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="loadingParetoPre" class="hidden items-center space-x-1.5 text-[11px] text-blue-800 bg-blue-50 border border-blue-200/80 px-2.5 py-0.5 rounded-full font-bold">
                                <svg class="animate-spin h-3 w-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Memuat...</span>
                            </span>
                            <span class="flex items-center space-x-1 text-[11px] text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full font-bold shadow-2xs">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                </span>
                                <span>Live (8s)</span>
                            </span>
                        </div>
                    </div>

                    <!-- Error Banner -->
                    <div id="errorParetoPre" class="hidden mt-3 p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-700">
                        <span id="errorMessageParetoPre">Gagal memuat analitik Pareto Pre Assy</span>
                    </div>

                    <!-- Card Filter Toolbar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-3 mb-4 p-3 bg-slate-50/70 rounded-xl border border-slate-100 items-end">
                        <!-- Rentang Tanggal -->
                        <div class="min-w-0">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-semibold text-slate-400 mb-0.5">Mulai</span>
                                    <input type="date" id="filterStartDateParetoPre"
                                        class="w-full border border-gray-200 rounded-xl px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-xs">
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-semibold text-slate-400 mb-0.5">Selesai</span>
                                    <input type="date" id="filterEndDateParetoPre"
                                        class="w-full border border-gray-200 rounded-xl px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-xs">
                                </div>
                            </div>
                        </div>

                    <!-- Carline + Reset -->
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 {{ ($currentMode ?? 'total') === 'pre' ? '' : 'hidden' }}" id="wrapFilterCarlineParetoPre">
                            <label for="filterCarlineParetoPre" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Carline</label>
                            <div class="relative">
                                <select id="filterCarlineParetoPre" class="w-full appearance-none border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 px-2.5 pr-7 py-1.5 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 cursor-pointer transition shadow-2xs">
                                    <option value="all">Semua Carline</option>
                                    @if(isset($preAssyCarlines))
                                        @foreach($preAssyCarlines as $carline)
                                            <option value="{{ $carline->id }}">{{ $carline->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-transparent uppercase tracking-wider mb-1 select-none">Aksi</label>
                            <button type="button" id="btnResetParetoPre" title="Reset filter Pareto Pre Assy ke default" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center space-x-1">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Canvas & Empty State -->
                <div id="wrapperParetoPre" class="relative h-72 w-full">
                    <canvas id="chartParetoPre"></canvas>
                </div>
                <div id="emptyParetoPre" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <p class="text-xs font-semibold text-slate-600" id="emptyMsgParetoPre">Belum ada data pada periode ini</p>
                    <span class="text-[11px] text-slate-400 mt-0.5" id="emptySubParetoPre">Ubah rentang tanggal atau pilih filter lain</span>
                </div>
                <!-- Hint Klik Interaktif -->
                <div class="mt-2 text-center">
                    <span class="inline-flex items-center space-x-1 text-[10px] font-semibold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60 cursor-default">
                        <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                        <span>Klik batang sub defect untuk melihat Top 5 Carline</span>
                    </span>
                </div>

                <!-- Drilldown Sub-Panel Pre Assy (3-Level Drilldown: Defect -> Carline -> Area Ditemukan) -->
                <div id="drilldownWrapperParetoPre" class="hidden mt-4 pt-3.5 border-t border-slate-200/80 bg-slate-50/80 rounded-xl p-3.5 transition-all">
                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                        <div class="flex items-center space-x-2 min-w-0 flex-1">
                            <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
                            <div class="min-w-0">
                                <div class="flex items-center space-x-1.5 flex-wrap text-xs">
                                    <span class="font-extrabold text-slate-900 tracking-tight" id="drilldownTitleParetoPre">Breakdown per Carline</span>
                                    <span class="text-slate-400">/</span>
                                    <span class="text-[10px] font-mono font-bold text-blue-800 bg-blue-100/70 border border-blue-200 px-1.5 py-0.5 rounded truncate max-w-[200px]" id="drilldownBadgeParetoPre">-</span>
                                    <span id="drilldownCarlineBadgeWrapPre" class="hidden items-center space-x-1">
                                        <span class="text-slate-400">/</span>
                                        <span class="text-[10px] font-mono font-bold text-amber-800 bg-amber-100/70 border border-amber-200 px-1.5 py-0.5 rounded truncate max-w-[200px]" id="drilldownCarlineBadgePre">-</span>
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-400 font-medium" id="drilldownSubParetoPre">Klik batang carline untuk melihat breakdown per Area Ditemukan</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <button type="button" id="btnBackDrilldownParetoPre" class="hidden text-xs font-bold text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-2.5 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer" title="Kembali ke level sebelumnya">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span id="btnBackTextPre">Kembali</span>
                            </button>
                            <button type="button" id="btnCloseDrilldownParetoPre" class="text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-200 px-2 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer" title="Tutup Breakdown">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span>Tutup</span>
                            </button>
                        </div>
                    </div>

                    <!-- Loading & Empty state -->
                    <div id="drilldownLoadingParetoPre" class="hidden py-6 text-center text-xs font-semibold text-blue-700">
                        <svg class="animate-spin inline-block h-4 w-4 text-blue-600 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span id="drilldownLoadingTextParetoPre">Memuat breakdown...</span>
                    </div>
                    <div id="drilldownEmptyParetoPre" class="hidden py-6 text-center text-xs font-medium text-slate-400 italic">
                        Tidak ada data untuk defect ini
                    </div>

                    <!-- Canvas Drilldown -->
                    <div id="drilldownCanvasWrapParetoPre" class="relative h-48 w-full">
                        <canvas id="chartDrilldownParetoPre"></canvas>
                    </div>
                </div>
            </div>
            </div>

            <!-- Mode Pre Wrapper (Pre Assy: 2 Kolom Kategori Operator & Inspektor) -->
            <div id="preAssyModePreWrapper" class="{{ ($currentMode ?? 'total') === 'pre' ? 'space-y-6' : 'hidden space-y-6' }}">
                <!-- Toolbar Filter Mode Pre Assy -->
                <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-2xl p-4 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block shadow-xs"></span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Filter Analitik Pre Assy</h3>
                                <p class="text-xs text-slate-500">Filter sinkron untuk temuan Operator dan Inspektor</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="loadingPreAssyMode" class="hidden items-center space-x-1.5 text-[11px] text-blue-800 bg-blue-50 border border-blue-200/80 px-2.5 py-0.5 rounded-full font-bold">
                                <svg class="animate-spin h-3 w-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Memuat...</span>
                            </span>
                            <span class="flex items-center space-x-1 text-[11px] text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full font-bold shadow-2xs">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                </span>
                                <span>Live (8s)</span>
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3 items-end">
                        <!-- Rentang Tanggal -->
                        <div class="min-w-0">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" id="filterStartDatePreAssy"
                                    class="w-full border border-gray-200 rounded-xl px-2 py-1.5 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-xs">
                                <input type="date" id="filterEndDatePreAssy"
                                    class="w-full border border-gray-200 rounded-xl px-2 py-1.5 text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-xs">
                            </div>
                        </div>

                        <!-- Pengelompokan Tren -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pengelompokan</label>
                            <div class="inline-flex w-full bg-slate-200/70 p-0.5 rounded-lg border border-slate-200" role="group">
                                <button type="button" data-val="day" class="btn-group-by-pre-assy flex-1 py-1.5 text-xs font-bold rounded-md transition-all text-center bg-blue-600 text-white shadow-2xs">Hari</button>
                                <button type="button" data-val="week" class="btn-group-by-pre-assy flex-1 py-1.5 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Minggu</button>
                                <button type="button" data-val="month" class="btn-group-by-pre-assy flex-1 py-1.5 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Bulan</button>
                            </div>
                        </div>

                        <!-- Carline -->
                        <div id="wrapFilterCarlinePreAssy">
                            <label for="filterCarlinePreAssy" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Carline</label>
                            <div class="relative">
                                <select id="filterCarlinePreAssy" class="w-full appearance-none border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 px-2.5 pr-7 py-2 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 cursor-pointer transition shadow-2xs">
                                    <option value="all">Semua Carline</option>
                                    @if(isset($preAssyCarlines))
                                        @foreach($preAssyCarlines as $carline)
                                            <option value="{{ $carline->id }}">{{ $carline->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Reset -->
                        <div>
                            <label class="block text-[10px] font-bold text-transparent uppercase tracking-wider mb-1 select-none">Aksi</label>
                            <button type="button" id="btnResetPreAssy" title="Reset filter ke default" class="w-full py-2 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center justify-center space-x-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Reset Filter</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2 Columns Grid: Operator (Left) & Inspektor (Right) -->
                <div id="categoryGridPreAssy" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <!-- KOLOM 1: OPERATOR -->
                    <div id="colPreOperator" class="space-y-6">
                        <!-- Summary Cards Operator -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Ditemukan oleh Operator</span>
                                    <span class="text-[10px] font-semibold text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/80" id="summaryPeriodDpmPreOperator">Memuat...</span>
                                </div>
                                <div class="mt-2 flex items-baseline space-x-2">
                                    <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmPreOperator">-</span>
                                    <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmPreOperator">PPM</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmPreOperator">Kalkulasi temuan operator / total inspect</p>
                            </div>

                            <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Top Defect - Ditemukan oleh Operator</span>
                                    <span class="text-[10px] font-semibold text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/80" id="summaryPeriodParetoPreOperator">Memuat...</span>
                                </div>
                                <div class="mt-2 flex items-baseline space-x-2 min-w-0">
                                    <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate flex-1 min-w-0" id="summaryValParetoPreOperator">-</span>
                                    <span class="text-xs font-bold text-blue-700 font-mono shrink-0" id="summaryCountParetoPreOperator"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubParetoPreOperator">Sub defect peringkat 1</p>
                            </div>
                        </div>

                        <!-- Card: Tren PPM Operator -->
                        <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block shadow-xs"></span>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tren PPM - Ditemukan oleh Operator</h3>
                                            <span class="text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md">Ditemukan oleh Operator</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">PPM temuan Operator per periode vs garis Target PPM</p>
                                    </div>
                                </div>
                            </div>
                            <div id="wrapperDpmPreOperator" class="relative h-72 w-full mt-4">
                                <canvas id="chartDpmPreOperator"></canvas>
                            </div>
                            <div id="emptyDpmPreOperator" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mt-4">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                            </div>
                            <div id="noteDpmPreOperator" class="hidden mt-2.5 pt-2.5 border-t border-slate-100 space-y-1"></div>
                        </div>

                        <!-- Card: Pareto Operator -->
                        <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block shadow-xs"></span>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Pareto - Ditemukan oleh Operator</h3>
                                            <span class="text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md">Ditemukan oleh Operator</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">Kuantitas defect temuan operator & persentase kumulatif (0-100%)</p>
                                    </div>
                                </div>
                            </div>
                            <div id="wrapperParetoPreOperator" class="relative h-72 w-full mt-4">
                                <canvas id="chartParetoPreOperator"></canvas>
                            </div>
                            <div id="emptyParetoPreOperator" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mt-4">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                            </div>
                            <div class="mt-2 text-center">
                                <span class="inline-flex items-center space-x-1 text-[10px] font-semibold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60 cursor-default">
                                    <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                                    <span>Klik batang sub defect untuk breakdown (Ditemukan oleh Operator)</span>
                                </span>
                            </div>

                            <!-- Drilldown Operator -->
                            <div id="drilldownWrapperParetoPreOperator" class="hidden mt-4 pt-3.5 border-t border-slate-200/80 bg-slate-50/80 rounded-xl p-3.5 transition-all">
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                                    <div class="flex items-center space-x-2 min-w-0 flex-1">
                                        <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
                                        <div class="min-w-0">
                                            <div class="flex items-center space-x-1.5 flex-wrap text-xs">
                                                <span class="font-extrabold text-slate-900 tracking-tight" id="drilldownTitleParetoPreOperator">Breakdown per Carline</span>
                                                <span class="text-slate-400">/</span>
                                                <span class="text-[10px] font-mono font-bold text-blue-800 bg-blue-100/70 border border-blue-200 px-1.5 py-0.5 rounded truncate max-w-[180px]" id="drilldownBadgeParetoPreOperator">-</span>
                                                <span id="drilldownCarlineBadgeWrapPreOperator" class="hidden items-center space-x-1">
                                                    <span class="text-slate-400">/</span>
                                                    <span class="text-[10px] font-mono font-bold text-amber-800 bg-amber-100/70 border border-amber-200 px-1.5 py-0.5 rounded truncate max-w-[180px]" id="drilldownCarlineBadgePreOperator">-</span>
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium" id="drilldownSubParetoPreOperator">Berdasarkan data Ditemukan oleh Operator</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 shrink-0">
                                        <button type="button" id="btnBackDrilldownParetoPreOperator" class="hidden text-xs font-bold text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-2.5 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                            <span id="btnBackTextPreOperator">Kembali</span>
                                        </button>
                                        <button type="button" id="btnCloseDrilldownParetoPreOperator" class="text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-200 px-2 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            <span>Tutup</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="drilldownLoadingParetoPreOperator" class="hidden py-6 text-center text-xs font-semibold text-blue-700">
                                    <svg class="animate-spin inline-block h-4 w-4 text-blue-600 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Memuat breakdown...</span>
                                </div>
                                <div id="drilldownEmptyParetoPreOperator" class="hidden py-6 text-center text-xs font-medium text-slate-400 italic">
                                    Tidak ada data untuk defect ini
                                </div>
                                <div id="drilldownCanvasWrapParetoPreOperator" class="relative h-48 w-full">
                                    <canvas id="chartDrilldownParetoPreOperator"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM 2: INSPEKTOR -->
                    <div id="colPreInspektor" class="space-y-6">
                        <!-- Summary Cards Inspektor -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white border border-slate-200 border-t-4 border-t-purple-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Ditemukan oleh Inspektor</span>
                                    <span class="text-[10px] font-semibold text-purple-800 bg-purple-50 px-2 py-0.5 rounded border border-purple-200/80" id="summaryPeriodDpmPreInspektor">Memuat...</span>
                                </div>
                                <div class="mt-2 flex items-baseline space-x-2">
                                    <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmPreInspektor">-</span>
                                    <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmPreInspektor">PPM</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmPreInspektor">Kalkulasi temuan inspektor / total inspect</p>
                            </div>

                            <div class="bg-white border border-slate-200 border-t-4 border-t-purple-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Top Defect - Ditemukan oleh Inspektor</span>
                                    <span class="text-[10px] font-semibold text-purple-800 bg-purple-50 px-2 py-0.5 rounded border border-purple-200/80" id="summaryPeriodParetoPreInspektor">Memuat...</span>
                                </div>
                                <div class="mt-2 flex items-baseline space-x-2 min-w-0">
                                    <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate flex-1 min-w-0" id="summaryValParetoPreInspektor">-</span>
                                    <span class="text-xs font-bold text-purple-700 font-mono shrink-0" id="summaryCountParetoPreInspektor"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubParetoPreInspektor">Sub defect peringkat 1</p>
                            </div>
                        </div>

                        <!-- Card: Tren PPM Inspektor -->
                        <div class="bg-white border border-slate-200 border-t-4 border-t-purple-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-purple-600 inline-block shadow-xs"></span>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tren PPM - Ditemukan oleh Inspektor</h3>
                                            <span class="text-[10px] font-bold text-purple-800 bg-purple-50 border border-purple-200/80 px-2 py-0.5 rounded-md">Ditemukan oleh Inspektor</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">PPM temuan Inspektor per periode vs garis Target PPM</p>
                                    </div>
                                </div>
                            </div>
                            <div id="wrapperDpmPreInspektor" class="relative h-72 w-full mt-4">
                                <canvas id="chartDpmPreInspektor"></canvas>
                            </div>
                            <div id="emptyDpmPreInspektor" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mt-4">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                            </div>
                            <div id="noteDpmPreInspektor" class="hidden mt-2.5 pt-2.5 border-t border-slate-100 space-y-1"></div>
                        </div>

                        <!-- Card: Pareto Inspektor -->
                        <div class="bg-white border border-slate-200 border-t-4 border-t-purple-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-purple-600 inline-block shadow-xs"></span>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Pareto - Ditemukan oleh Inspektor</h3>
                                            <span class="text-[10px] font-bold text-purple-800 bg-purple-50 border border-purple-200/80 px-2 py-0.5 rounded-md">Ditemukan oleh Inspektor</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">Kuantitas defect temuan inspektor & persentase kumulatif (0-100%)</p>
                                    </div>
                                </div>
                            </div>
                            <div id="wrapperParetoPreInspektor" class="relative h-72 w-full mt-4">
                                <canvas id="chartParetoPreInspektor"></canvas>
                            </div>
                            <div id="emptyParetoPreInspektor" class="hidden flex flex-col items-center justify-center h-72 text-center p-6 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 mt-4">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data pada periode ini</p>
                            </div>
                            <div class="mt-2 text-center">
                                <span class="inline-flex items-center space-x-1 text-[10px] font-semibold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60 cursor-default">
                                    <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                                    <span>Klik batang sub defect untuk breakdown (Ditemukan oleh Inspektor)</span>
                                </span>
                            </div>

                            <!-- Drilldown Inspektor -->
                            <div id="drilldownWrapperParetoPreInspektor" class="hidden mt-4 pt-3.5 border-t border-slate-200/80 bg-slate-50/80 rounded-xl p-3.5 transition-all">
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                                    <div class="flex items-center space-x-2 min-w-0 flex-1">
                                        <span class="w-2 h-2 rounded-full bg-purple-600 shrink-0"></span>
                                        <div class="min-w-0">
                                            <div class="flex items-center space-x-1.5 flex-wrap text-xs">
                                                <span class="font-extrabold text-slate-900 tracking-tight" id="drilldownTitleParetoPreInspektor">Breakdown per Carline</span>
                                                <span class="text-slate-400">/</span>
                                                <span class="text-[10px] font-mono font-bold text-purple-800 bg-purple-100/70 border border-purple-200 px-1.5 py-0.5 rounded truncate max-w-[180px]" id="drilldownBadgeParetoPreInspektor">-</span>
                                                <span id="drilldownCarlineBadgeWrapPreInspektor" class="hidden items-center space-x-1">
                                                    <span class="text-slate-400">/</span>
                                                    <span class="text-[10px] font-mono font-bold text-amber-800 bg-amber-100/70 border border-amber-200 px-1.5 py-0.5 rounded truncate max-w-[180px]" id="drilldownCarlineBadgePreInspektor">-</span>
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium" id="drilldownSubParetoPreInspektor">Berdasarkan data Ditemukan oleh Inspektor</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 shrink-0">
                                        <button type="button" id="btnBackDrilldownParetoPreInspektor" class="hidden text-xs font-bold text-purple-700 hover:text-purple-900 bg-purple-50 hover:bg-purple-100 border border-purple-200 px-2.5 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                            <span id="btnBackTextPreInspektor">Kembali</span>
                                        </button>
                                        <button type="button" id="btnCloseDrilldownParetoPreInspektor" class="text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-200 px-2 py-1 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            <span>Tutup</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="drilldownLoadingParetoPreInspektor" class="hidden py-6 text-center text-xs font-semibold text-purple-700">
                                    <svg class="animate-spin inline-block h-4 w-4 text-purple-600 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Memuat breakdown...</span>
                                </div>
                                <div id="drilldownEmptyParetoPreInspektor" class="hidden py-6 text-center text-xs font-medium text-slate-400 italic">
                                    Tidak ada data untuk defect ini
                                </div>
                                <div id="drilldownCanvasWrapParetoPreInspektor" class="relative h-48 w-full">
                                    <canvas id="chartDrilldownParetoPreInspektor"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Analytics & Real-time Polling Script -->
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Dashboard Mode ('total', 'final', 'pre')
        const validModes = ['total', 'final', 'pre'];
        const urlMode = new URLSearchParams(window.location.search).get('mode');
        let currentMode = validModes.includes(urlMode)
            ? urlMode
            : (validModes.includes('{{ $currentMode ?? "total" }}') ? '{{ $currentMode ?? "total" }}' : 'total');

        // Theme Colors
        const THEME_COLORS = {
            internal: '#0d9488',
            internalDark: '#0f766e',
            internalLight: 'rgba(13, 148, 136, 0.08)',
            assembly: '#6366f1',
            assemblyDark: '#4f46e5',
            assemblyLight: 'rgba(99, 102, 241, 0.08)',
            operator: '#2563eb',
            operatorDark: '#1d4ed8',
            operatorLight: 'rgba(37, 99, 235, 0.08)',
            inspektor: '#8b5cf6',
            inspektorDark: '#7c3aed',
            inspektorLight: 'rgba(139, 92, 246, 0.08)',
            target: '#64748b',
            cumLine: '#d97706',
        };

        function updateConveyorFilterUI(mode) {
            const wrapDpm = document.getElementById('wrapFilterConveyorDpmFinal');
            const wrapPareto = document.getElementById('wrapFilterConveyorParetoFinal');
            const convDpm = document.getElementById('filterConveyorDpmFinal');
            const convPareto = document.getElementById('filterConveyorParetoFinal');

            const wrapCarlineDpm = document.getElementById('wrapFilterCarlineDpmPre');
            const wrapCarlinePareto = document.getElementById('wrapFilterCarlineParetoPre');
            const carlineDpm = document.getElementById('filterCarlineDpmPre');
            const carlinePareto = document.getElementById('filterCarlineParetoPre');

            if (mode === 'final') {
                if (wrapDpm) wrapDpm.classList.remove('hidden');
                if (wrapPareto) wrapPareto.classList.remove('hidden');
                if (convDpm) convDpm.disabled = false;
                if (convPareto) convPareto.disabled = false;

                if (wrapCarlineDpm) wrapCarlineDpm.classList.add('hidden');
                if (wrapCarlinePareto) wrapCarlinePareto.classList.add('hidden');
                filtersDpmPre.carline_id = 'all';
                filtersParetoPre.carline_id = 'all';
                if (carlineDpm) carlineDpm.value = 'all';
                if (carlinePareto) carlinePareto.value = 'all';
            } else if (mode === 'pre') {
                if (wrapDpm) wrapDpm.classList.add('hidden');
                if (wrapPareto) wrapPareto.classList.add('hidden');
                filtersDpmFinal.conveyor = 'all';
                filtersParetoFinal.conveyor = 'all';
                if (convDpm) convDpm.value = 'all';
                if (convPareto) convPareto.value = 'all';

                if (wrapCarlineDpm) wrapCarlineDpm.classList.remove('hidden');
                if (wrapCarlinePareto) wrapCarlinePareto.classList.remove('hidden');
                if (carlineDpm) carlineDpm.disabled = false;
                if (carlinePareto) carlinePareto.disabled = false;
            } else {
                // Mode 'total': sembunyikan conveyor dan carline
                if (wrapDpm) wrapDpm.classList.add('hidden');
                if (wrapPareto) wrapPareto.classList.add('hidden');
                filtersDpmFinal.conveyor = 'all';
                filtersParetoFinal.conveyor = 'all';
                if (convDpm) convDpm.value = 'all';
                if (convPareto) convPareto.value = 'all';

                if (wrapCarlineDpm) wrapCarlineDpm.classList.add('hidden');
                if (wrapCarlinePareto) wrapCarlinePareto.classList.add('hidden');
                filtersDpmPre.carline_id = 'all';
                filtersParetoPre.carline_id = 'all';
                if (carlineDpm) carlineDpm.value = 'all';
                if (carlinePareto) carlinePareto.value = 'all';
            }
        }

        function updateModeVisuals(mode) {
            const finalTotalWrap = document.getElementById('finalAssyModeTotalWrapper');
            const finalFinalWrap = document.getElementById('finalAssyModeFinalWrapper');
            const preTotalWrap = document.getElementById('preAssyModeTotalWrapper');
            const prePreWrap = document.getElementById('preAssyModePreWrapper');

            const titleDpmFinal = document.getElementById('cardTitleDpmFinal');
            const subDpmFinal = document.getElementById('cardSubDpmFinal');
            const badgeDpmFinal = document.getElementById('cardBadgeDpmFinal');
            const titleParetoFinal = document.getElementById('cardTitleParetoFinal');
            const subParetoFinal = document.getElementById('cardSubParetoFinal');
            const badgeParetoFinal = document.getElementById('cardBadgeParetoFinal');
            const summaryTitleDpm = document.getElementById('summaryTitleDpmFinal');
            const summaryTitlePareto = document.getElementById('summaryTitleParetoFinal');

            const titleDpmPre = document.getElementById('cardTitleDpmPre');
            const subDpmPre = document.getElementById('cardSubDpmPre');
            const badgeDpmPre = document.getElementById('cardBadgeDpmPre');
            const titleParetoPre = document.getElementById('cardTitleParetoPre');
            const subParetoPre = document.getElementById('cardSubParetoPre');
            const badgeParetoPre = document.getElementById('cardBadgeParetoPre');
            const summaryTitleDpmPre = document.getElementById('summaryTitleDpmPre');
            const summaryTitleParetoPre = document.getElementById('summaryTitleParetoPre');

            const colPre = document.getElementById('columnPreAssy');
            const colFinal = document.getElementById('columnFinalAssy');
            const cardTop5 = document.getElementById('cardTop5AreaFinal');

            const hintParetoFinal = document.getElementById('hintParetoFinal');
            const drilldownTitleParetoFinal = document.getElementById('drilldownTitleParetoFinal');
            const drilldownSourceParetoFinal = document.getElementById('drilldownSourceParetoFinal');
            const drilldownSubParetoFinal = document.getElementById('drilldownSubParetoFinal');
            const emptySubDpmFinal = document.getElementById('emptySubDpmFinal');
            const emptySubParetoFinal = document.getElementById('emptySubParetoFinal');

            if (mode === 'total') {
                if (colFinal) colFinal.classList.remove('hidden', 'xl:col-span-2');
                if (colPre) colPre.classList.remove('hidden', 'xl:col-span-2');
                if (finalTotalWrap) finalTotalWrap.classList.remove('hidden');
                if (finalFinalWrap) finalFinalWrap.classList.add('hidden');
                if (preTotalWrap) preTotalWrap.classList.remove('hidden');
                if (prePreWrap) prePreWrap.classList.add('hidden');
                if (cardTop5) cardTop5.classList.add('hidden');

                if (titleDpmFinal) titleDpmFinal.textContent = 'Tren Defect Final Assy';
                if (subDpmFinal) subDpmFinal.textContent = 'Perbandingan quantity internal vs assembly per periode';
                if (badgeDpmFinal) {
                    badgeDpmFinal.textContent = 'Final Assy';
                    badgeDpmFinal.className = 'text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md';
                }
                if (titleParetoFinal) titleParetoFinal.textContent = 'Pareto Sub Defect Final Assy';
                if (subParetoFinal) subParetoFinal.textContent = 'Total defect & persentase kumulatif per sub defect';
                if (badgeParetoFinal) {
                    badgeParetoFinal.textContent = 'Final Assy';
                    badgeParetoFinal.className = 'text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md';
                }
                if (summaryTitleDpm) summaryTitleDpm.textContent = 'Total Defect Final Assy';
                if (summaryTitlePareto) summaryTitlePareto.textContent = 'Top Defect Final';

                if (titleDpmPre) titleDpmPre.textContent = 'Tren Defect Pre Assy';
                if (subDpmPre) subDpmPre.textContent = 'Perbandingan quantity operator vs inspektor per periode';
                if (badgeDpmPre) {
                    badgeDpmPre.textContent = 'Pre Assy';
                    badgeDpmPre.className = 'text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md';
                }
                if (titleParetoPre) titleParetoPre.textContent = 'Pareto Sub Defect Pre Assy';
                if (subParetoPre) subParetoPre.textContent = 'Total defect & persentase kumulatif per sub defect';
                if (badgeParetoPre) {
                    badgeParetoPre.textContent = 'Pre Assy';
                    badgeParetoPre.className = 'text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md';
                }
                if (summaryTitleDpmPre) summaryTitleDpmPre.textContent = 'Total Defect Pre Assy';
                if (summaryTitleParetoPre) summaryTitleParetoPre.textContent = 'Top Defect Pre';

                setTimeout(() => {
                    if (chartDpmFinal) chartDpmFinal.resize();
                    if (chartParetoFinal) chartParetoFinal.resize();
                    if (chartDpmPre) chartDpmPre.resize();
                    if (chartParetoPre) chartParetoPre.resize();
                }, 50);

            } else if (mode === 'final') {
                if (colFinal) {
                    colFinal.classList.remove('hidden');
                    colFinal.classList.add('xl:col-span-2');
                }
                if (colPre) {
                    colPre.classList.add('hidden');
                    colPre.classList.remove('xl:col-span-2');
                }
                if (finalTotalWrap) finalTotalWrap.classList.add('hidden');
                if (finalFinalWrap) finalFinalWrap.classList.remove('hidden');
                if (preTotalWrap) preTotalWrap.classList.add('hidden');
                if (prePreWrap) prePreWrap.classList.add('hidden');
                if (cardTop5) cardTop5.classList.remove('hidden');

                if (typeof initCategoryChartsFinal === 'function') {
                    initCategoryChartsFinal();
                }

                setTimeout(() => {
                    if (chartDpmFinalInternal) chartDpmFinalInternal.resize();
                    if (chartParetoFinalInternal) chartParetoFinalInternal.resize();
                    if (chartDpmFinalAssembly) chartDpmFinalAssembly.resize();
                    if (chartParetoFinalAssembly) chartParetoFinalAssembly.resize();
                    if (chartTop5AreaFinalInstance) chartTop5AreaFinalInstance.resize();
                }, 50);

                if (titleDpmFinal) titleDpmFinal.textContent = 'DPM Final Assy';
                if (subDpmFinal) subDpmFinal.textContent = 'Defect Per Million tren per periode';
                if (badgeDpmFinal) {
                    badgeDpmFinal.textContent = 'Final Assy';
                    badgeDpmFinal.className = 'text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md';
                }
                if (titleParetoFinal) titleParetoFinal.textContent = 'Pareto Sub Defect Final Assy';
                if (subParetoFinal) subParetoFinal.textContent = 'Total defect & persentase kumulatif per sub defect';
                if (badgeParetoFinal) {
                    badgeParetoFinal.textContent = 'Final Assy';
                    badgeParetoFinal.className = 'text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md';
                }
                if (summaryTitleDpm) summaryTitleDpm.textContent = 'DPM Final Assy';
                if (summaryTitlePareto) summaryTitlePareto.textContent = 'Top Defect Final';

            } else if (mode === 'pre') {
                if (colFinal) {
                    colFinal.classList.add('hidden');
                    colFinal.classList.remove('xl:col-span-2');
                }
                if (colPre) {
                    colPre.classList.remove('hidden');
                    colPre.classList.add('xl:col-span-2');
                }
                if (finalTotalWrap) finalTotalWrap.classList.add('hidden');
                if (finalFinalWrap) finalFinalWrap.classList.add('hidden');
                if (preTotalWrap) preTotalWrap.classList.add('hidden');
                if (prePreWrap) prePreWrap.classList.remove('hidden');
                if (cardTop5) cardTop5.classList.add('hidden');

                if (typeof initCategoryChartsPre === 'function') {
                    initCategoryChartsPre();
                }

                setTimeout(() => {
                    if (chartDpmPreOperator) chartDpmPreOperator.resize();
                    if (chartParetoPreOperator) chartParetoPreOperator.resize();
                    if (chartDpmPreInspektor) chartDpmPreInspektor.resize();
                    if (chartParetoPreInspektor) chartParetoPreInspektor.resize();
                }, 50);

                if (titleDpmPre) titleDpmPre.textContent = 'PPM Pre Assy';
                if (subDpmPre) subDpmPre.textContent = 'Part Per Million tren per periode';
                if (badgeDpmPre) {
                    badgeDpmPre.textContent = 'Pre Assy';
                    badgeDpmPre.className = 'text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md';
                }
                if (titleParetoPre) titleParetoPre.textContent = 'Pareto Sub Defect Pre Assy';
                if (subParetoPre) subParetoPre.textContent = 'Total defect & persentase kumulatif per sub defect';
                if (badgeParetoPre) {
                    badgeParetoPre.textContent = 'Pre Assy';
                    badgeParetoPre.className = 'text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md';
                }
                if (summaryTitleDpmPre) summaryTitleDpmPre.textContent = 'PPM Pre Assy';
                if (summaryTitleParetoPre) summaryTitleParetoPre.textContent = 'Top Defect Pre';
            }

            if (hintParetoFinal) hintParetoFinal.textContent = 'Klik batang sub defect untuk melihat breakdown per Carline / Conveyor';
            if (drilldownTitleParetoFinal) drilldownTitleParetoFinal.textContent = 'Breakdown per Carline / Conveyor';
            if (drilldownSourceParetoFinal) drilldownSourceParetoFinal.classList.add('hidden');
            if (drilldownSubParetoFinal) drilldownSubParetoFinal.textContent = 'Berdasarkan sub defect yang dipilih';
            if (emptySubDpmFinal) emptySubDpmFinal.textContent = 'Ubah rentang tanggal filter';
            if (emptySubParetoFinal) emptySubParetoFinal.textContent = 'Ubah rentang tanggal filter';

            updateConveyorFilterUI(mode);
        }

        function updateModeToggleUI(activeMode) {
            document.querySelectorAll('.btn-dashboard-mode').forEach(btn => {
                const btnMode = btn.getAttribute('data-mode');
                const isSelected = (btnMode === activeMode);
                const dot = btn.querySelector('.mode-dot');

                btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');

                if (isSelected) {
                    btn.classList.add('bg-white', 'text-slate-900', 'shadow-xs', 'border-slate-200/80', 'font-extrabold');
                    btn.classList.remove('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-200/50', 'border-transparent', 'font-bold');
                    if (dot) {
                        dot.classList.remove('bg-slate-300');
                        if (btnMode === 'total') dot.classList.add('bg-indigo-600');
                        else if (btnMode === 'final') dot.classList.add('bg-teal-600');
                        else if (btnMode === 'pre') dot.classList.add('bg-blue-600');
                    }
                } else {
                    btn.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'border-slate-200/80', 'font-extrabold');
                    btn.classList.add('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-200/50', 'border-transparent', 'font-bold');
                    if (dot) {
                        dot.classList.remove('bg-indigo-600', 'bg-teal-600', 'bg-blue-600');
                        dot.classList.add('bg-slate-300');
                    }
                }
            });
        }

        function setDashboardMode(newMode, shouldPushState = true) {
            if (!validModes.includes(newMode)) return;
            if (newMode === currentMode) return;

            currentMode = newMode;
            updateModeToggleUI(currentMode);
            updateModeVisuals(currentMode);

            if (shouldPushState) {
                const url = new URL(window.location.href);
                url.searchParams.set('mode', currentMode);
                window.history.pushState({ mode: currentMode }, '', url.toString());
            }

            // Muat ulang data feed dengan mode baru
            fetchStats();
            if (currentMode === 'total') {
                fetchDpmFinal(false);
                fetchParetoFinal(false);
                fetchDpmPre(false);
                fetchParetoPre(false);
            } else if (currentMode === 'final') {
                fetchDpmFinal(false);
                fetchParetoFinal(false);
            } else if (currentMode === 'pre') {
                fetchDpmPre(false);
                fetchParetoPre(false);
            }
        }

        document.querySelectorAll('.btn-dashboard-mode').forEach(btn => {
            btn.addEventListener('click', function () {
                const newMode = this.getAttribute('data-mode');
                setDashboardMode(newMode, true);
            });
        });

        window.addEventListener('popstate', function () {
            const params = new URLSearchParams(window.location.search);
            const modeFromUrl = params.get('mode') || 'total';
            const targetMode = validModes.includes(modeFromUrl) ? modeFromUrl : 'total';
            if (targetMode !== currentMode) {
                setDashboardMode(targetMode, false);
            }
        });

        // --- 1. Month Names & Helpers ---
        const MONTH_NAMES_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        function getInitialDates() {
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - 30);
            const pad = n => String(n).padStart(2, '0');
            const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            return { start: fmt(start), end: fmt(end) };
        }

        function formatPeriodLabel(startStr, endStr) {
            if (!startStr && !endStr) return 'Semua Periode';
            const fmt = str => {
                if (!str) return null;
                const p = str.split('-');
                if (p.length === 3) {
                    const d = parseInt(p[2], 10);
                    const m = parseInt(p[1], 10) - 1;
                    const y = p[0];
                    return { d: String(d).padStart(2, '0'), m: MONTH_NAMES_ID[m] || p[1], y };
                }
                return { d: str, m: '', y: '' };
            };

            const s = fmt(startStr);
            const e = fmt(endStr);

            if (s && !e) return `Sejak ${s.d} ${s.m} ${s.y}`.trim();
            if (!s && e) return `s/d ${e.d} ${e.m} ${e.y}`.trim();
            if (s && e) {
                if (s.y === e.y && s.m === e.m && s.d === e.d) {
                    return `${s.d} ${s.m} ${s.y}`;
                }
                if (s.y === e.y) {
                    return `${s.d} ${s.m} - ${e.d} ${e.m} ${s.y}`;
                }
                return `${s.d} ${s.m} ${s.y} - ${e.d} ${e.m} ${e.y}`;
            }
            return 'Semua Periode';
        }

        function formatDpmXLabel(rawLabel, groupBy) {
            if (!rawLabel || typeof rawLabel !== 'string') return rawLabel || '';
            const pad = n => String(n).padStart(2, '0');

            if (groupBy === 'month') {
                const parts = rawLabel.split('-');
                if (parts.length >= 2) {
                    const yr = parts[0];
                    const moIdx = parseInt(parts[1], 10) - 1;
                    const mo = MONTH_NAMES_ID[moIdx] || parts[1];
                    return `${mo} ${yr}`;
                }
                return rawLabel;
            }

            if (groupBy === 'week') {
                const parts = rawLabel.split('-');
                if (parts.length === 3) {
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10) - 1;
                    const d = parseInt(parts[2], 10);
                    const startD = new Date(y, m, d);
                    const endD = new Date(y, m, d + 6);

                    const startDayStr = pad(startD.getDate());
                    const endDayStr = pad(endD.getDate());
                    const startMo = MONTH_NAMES_ID[startD.getMonth()];
                    const endMo = MONTH_NAMES_ID[endD.getMonth()];

                    if (startD.getMonth() === endD.getMonth()) {
                        return `${startDayStr}-${endDayStr} ${endMo}`;
                    } else {
                        return `${startDayStr} ${startMo}-${endDayStr} ${endMo}`;
                    }
                }
                return rawLabel;
            }

            // Default 'day': format 'YYYY-MM-DD' -> "05 Okt"
            const parts = rawLabel.split('-');
            if (parts.length === 3) {
                const dayStr = pad(parseInt(parts[2], 10));
                const moIdx = parseInt(parts[1], 10) - 1;
                const mo = MONTH_NAMES_ID[moIdx] || parts[1];
                return `${dayStr} ${mo}`;
            }

            return rawLabel;
        }

        // --- 2. Filter States Terpisah per Grafik ---
        const initDpmFinal = getInitialDates();
        const filtersDpmFinal = {
            start_date: initDpmFinal.start,
            end_date: initDpmFinal.end,
            group_by: 'day',
            conveyor: 'all'
        };

        const initParetoFinal = getInitialDates();
        const filtersParetoFinal = {
            start_date: initParetoFinal.start,
            end_date: initParetoFinal.end,
            conveyor: 'all'
        };

        const initDpmPre = getInitialDates();
        const filtersDpmPre = {
            start_date: initDpmPre.start,
            end_date: initDpmPre.end,
            group_by: 'day',
            carline_id: 'all'
        };

        const initParetoPre = getInitialDates();
        const filtersParetoPre = {
            start_date: initParetoPre.start,
            end_date: initParetoPre.end,
            carline_id: 'all'
        };

        let abortDpmFinal = null;
        let abortParetoFinal = null;
        let abortDpmPre = null;
        let abortParetoPre = null;

        let debounceDpmFinal = null;
        let debounceParetoFinal = null;
        let debounceDpmPre = null;
        let debounceParetoPre = null;

        let pollingTimer = null;

        // --- 3. Chart Factory ---
        function createDpmChart(canvasId, accentColor, bgGradientColor, targetColor = '#64748b', metricLabel = 'DPM') {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;
            const existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }
            const ctx = canvas.getContext('2d');

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: `${metricLabel} Real`,
                            data: [],
                            borderColor: accentColor,
                            backgroundColor: bgGradientColor,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.3,
                            spanGaps: true,
                            pointBackgroundColor: accentColor,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            _meta: []
                        },
                        {
                            label: `Target ${metricLabel}`,
                            data: [],
                            borderColor: targetColor, // Abu gelap putus-putus
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            fill: false,
                            tension: 0,
                            spanGaps: false,
                            pointRadius: 0,
                            pointHoverRadius: 0,
                            hidden: true // Disembunyikan default jika belum ada target DPM / PPM
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 4,
                                usePointStyle: false,
                                padding: 12,
                                color: '#475569',
                                font: { size: 11, weight: '600' },
                                filter: function(legendItem, chartData) {
                                    const chart = this.chart;
                                    const ds = chartData.datasets ? chartData.datasets[legendItem.datasetIndex] : null;
                                    if (ds && ds.hidden) {
                                        return false;
                                    }
                                    if (chart && typeof chart.isDatasetVisible === 'function') {
                                        return chart.isDatasetVisible(legendItem.datasetIndex);
                                    }
                                    return !ds?.hidden;
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.96)',
                            titleColor: '#ffffff',
                            titleFont: { size: 12, weight: '700' },
                            bodyColor: '#f1f5f9',
                            bodyFont: { size: 11, weight: '500' },
                            padding: 12,
                            cornerRadius: 8,
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            callbacks: {
                                title: function(tooltipItems) {
                                    if (!tooltipItems.length) return '';
                                    const item = tooltipItems[0];
                                    const raw = item.label || '';
                                    const gb = item.chart?._groupBy || 'day';
                                    if (gb === 'week') {
                                        return `Minggu: ${raw}`;
                                    } else if (gb === 'month') {
                                        return `Bulan: ${raw}`;
                                    }
                                    return `Tanggal: ${raw}`;
                                },
                                label: function(context) {
                                    const chart = context.chart;
                                    const mode = chart?._mode || 'total';
                                    const dsIdx = context.datasetIndex;
                                    const ds = chart.data.datasets[dsIdx];
                                    const dsLabel = ds?.label || '';
                                    const val = context.raw;
                                    const i = context.dataIndex;
                                    const meta = chart?.data?.datasets[0]?._meta ? chart.data.datasets[0]._meta[i] : null;

                                    if (mode === 'total') {
                                        return `  ${dsLabel}: ${Number(val || 0).toLocaleString('id-ID')} pcs`;
                                    }

                                    if (val === null || val === undefined) return null;

                                    if (dsIdx === 1) {
                                        return `  ${dsLabel}: ${Number(val).toLocaleString('id-ID')}`;
                                    }

                                    if (dsIdx >= 2) {
                                        return `  ${dsLabel}: ${Number(val).toLocaleString('id-ID')}`;
                                    }

                                    // Dataset 0 (Total DPM / PPM)
                                    const mLabel = chart?._metricLabel || metricLabel || 'DPM';
                                    const lines = [
                                        `  ${mLabel} Real: ${Number(val).toLocaleString('id-ID')}`
                                    ];
                                    const targetVal = chart.data.datasets[1]?.data?.[i];
                                    if (targetVal !== null && targetVal !== undefined && !isNaN(Number(targetVal))) {
                                        if (Number(val) > Number(targetVal)) {
                                            lines.push(`  ⚠️ Status: Melebihi Target (${Number(targetVal).toLocaleString('id-ID')})`);
                                        } else {
                                            lines.push(`  ✓ Status: Memenuhi Target (${Number(targetVal).toLocaleString('id-ID')})`);
                                        }
                                    }
                                    if (meta) {
                                        if (meta.total_defect !== undefined) lines.push(`  Total Defect: ${Number(meta.total_defect).toLocaleString('id-ID')} pcs`);
                                        if (meta.total_inspect !== undefined) lines.push(`  Total Inspect: ${Number(meta.total_inspect).toLocaleString('id-ID')} pcs`);
                                    }
                                    return lines;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#475569',
                                font: { size: 11, weight: '600' },
                                padding: 6,
                                callback: function(val, index) {
                                    const rawLabel = this.chart.data.labels[index];
                                    return formatDpmXLabel(rawLabel, this.chart._groupBy || 'day');
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: metricLabel,
                                color: '#475569',
                                font: { size: 10, weight: '700' }
                            },
                            ticks: {
                                precision: 0,
                                color: '#475569',
                                font: { size: 11, weight: '600' },
                                padding: 8,
                                callback: function(val) {
                                    return val.toLocaleString('id-ID');
                                }
                            },
                            grid: { color: '#e2e8f0', drawBorder: false },
                            border: { dash: [3, 3] }
                        }
                    }
                }
            });

            chart._groupBy = 'day';
            chart._metricLabel = metricLabel;
            return chart;
        }

        function createParetoChart(canvasId, barColor) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;
            const existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }
            const ctx = canvas.getContext('2d');

            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            type: 'line',
                            label: 'Kumulatif %',
                            data: [],
                            borderColor: '#d97706',
                            backgroundColor: '#d97706',
                            borderWidth: 2,
                            pointRadius: 3.5,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#d97706',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            fill: false,
                            tension: 0.25,
                            yAxisID: 'y1',
                            order: 1
                        },
                        {
                            type: 'bar',
                            label: 'Total Defect',
                            data: [],
                            backgroundColor: barColor,
                            borderRadius: 4,
                            maxBarThickness: 44,
                            yAxisID: 'y',
                            order: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onHover: function(event, elements) {
                        event.native.target.style.cursor = (elements && elements.length > 0) ? 'pointer' : 'default';
                    },
                    onClick: function(event, elements) {
                        if (!elements || elements.length === 0) return;
                        const el = elements[0];
                        const item = (this._paretoList && this._paretoList[el.index]) ? this._paretoList[el.index] : null;
                        let targetKey = item?.key || item?.name;
                        if (!targetKey) {
                            const rawLabel = this.data.labels[el.index];
                            targetKey = Array.isArray(rawLabel) ? (rawLabel[1] || rawLabel[0]) : rawLabel;
                        }
                        if (!targetKey) return;
                        triggerParetoDrilldown(canvasId, targetKey);
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                padding: 12,
                                color: '#475569',
                                font: { size: 11, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.96)',
                            titleColor: '#ffffff',
                            titleFont: { size: 12, weight: '700' },
                            bodyColor: '#f1f5f9',
                            bodyFont: { size: 11, weight: '500' },
                            padding: 12,
                            cornerRadius: 8,
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            callbacks: {
                                title: function(tooltipItems) {
                                    if (!tooltipItems.length) return '';
                                    const item = tooltipItems[0];
                                    const chart = item.chart;
                                    const p = chart?._paretoList?.[item.dataIndex];
                                    return p ? (p.name || p.key) : (chart?.data?.labels?.[item.dataIndex] || item.label || '');
                                },
                                beforeBody: function(tooltipItems) {
                                    if (!tooltipItems.length) return [];
                                    const item = tooltipItems[0];
                                    const chart = item.chart;
                                    const p = chart?._paretoList?.[item.dataIndex];
                                    if (p) {
                                        return [`Total: ${Number(p.total || 0).toLocaleString('id-ID')} pcs`];
                                    }
                                    return [];
                                },
                                label: function(context) {
                                    const val = context.raw || 0;
                                    if (context.dataset.yAxisID === 'y1') {
                                        return `  Kumulatif: ${Number(val).toFixed(1)}%`;
                                    }
                                    const dsLabel = context.dataset.label || 'Defect';
                                    return `  ${dsLabel}: ${Number(val).toLocaleString('id-ID')} pcs`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#475569',
                                font: { size: 10, weight: '600' },
                                padding: 6,
                                callback: function(val, index) {
                                    const rawLabel = this.getLabelForValue(val);
                                    if (Array.isArray(rawLabel)) {
                                        const tag = rawLabel[0] || '';
                                        const text = rawLabel[1] || '';
                                        const truncatedText = text.length > 13 ? text.substring(0, 13) + '…' : text;
                                        return [tag, truncatedText];
                                    }
                                    if (typeof rawLabel === 'string' && rawLabel.length > 13) {
                                        return rawLabel.substring(0, 13) + '…';
                                    }
                                    return rawLabel;
                                }
                            }
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Defect (pcs)',
                                color: '#475569',
                                font: { size: 10, weight: '700' }
                            },
                            ticks: {
                                precision: 0,
                                color: '#475569',
                                font: { size: 11, weight: '600' },
                                padding: 8,
                                callback: function(val) {
                                    if (Number.isInteger(val)) {
                                        return val.toLocaleString('id-ID');
                                    }
                                    return '';
                                }
                            },
                            grid: { color: '#e2e8f0', drawBorder: false },
                            border: { dash: [3, 3] }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            min: 0,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Kumulatif (%)',
                                color: '#d97706',
                                font: { size: 10, weight: '700' }
                            },
                            grid: { display: false },
                            ticks: {
                                color: '#d97706',
                                font: { size: 10, weight: '600' },
                                padding: 6,
                                callback: function(val) {
                                    return val + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Final Assy (Teal: #0f766e) & Pre Assy (Cobalt Blue: #1d4ed8 / #1e40af)
        const chartDpmFinal = createDpmChart('chartDpmFinal', '#0f766e', 'rgba(15, 118, 110, 0.08)', '#64748b', 'DPM');
        const chartParetoFinal = createParetoChart('chartParetoFinal', '#0f766e');
        const chartDpmPre = createDpmChart('chartDpmPre', '#1d4ed8', 'rgba(29, 78, 216, 0.08)', '#64748b', 'PPM');
        const chartParetoPre = createParetoChart('chartParetoPre', '#1e40af');

        let chartDrilldownParetoFinal = null;
        let chartDrilldownParetoPre = null;

        // Kategori Terpisah Mode Final Assy & Pre Assy
        let chartDpmFinalInternal = null;
        let chartParetoFinalInternal = null;
        let chartDpmFinalAssembly = null;
        let chartParetoFinalAssembly = null;

        let chartDpmPreOperator = null;
        let chartParetoPreOperator = null;
        let chartDpmPreInspektor = null;
        let chartParetoPreInspektor = null;

        const drilldownCharts = {
            Final: null,
            Pre: null,
            FinalInternal: null,
            FinalAssembly: null,
            PreOperator: null,
            PreInspektor: null
        };

        function createSingleParetoChart(canvasId, accentColor, onBarClick) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;
            const existingChart = Chart.getChart(canvas);
            if (existingChart) existingChart.destroy();

            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            type: 'line',
                            label: 'Kumulatif %',
                            data: [],
                            borderColor: '#d97706',
                            backgroundColor: '#d97706',
                            borderWidth: 2,
                            pointRadius: 3.5,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#d97706',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            fill: false,
                            tension: 0.25,
                            yAxisID: 'y1',
                            order: 1
                        },
                        {
                            type: 'bar',
                            label: 'Defect',
                            data: [],
                            backgroundColor: accentColor,
                            borderRadius: 4,
                            maxBarThickness: 44,
                            yAxisID: 'y',
                            order: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onHover: function (event, elements) {
                        event.native.target.style.cursor = (elements && elements.length > 0) ? 'pointer' : 'default';
                    },
                    onClick: function (event, elements) {
                        if (!elements || elements.length === 0) return;
                        const el = elements[0];
                        const rawLabel = this.data.labels[el.index];
                        const label = Array.isArray(rawLabel) ? (rawLabel[1] || rawLabel[0]) : rawLabel;
                        const p = this._paretoList?.[el.index];
                        const defectName = p ? (p.name || p.key) : label;
                        if (defectName && typeof onBarClick === 'function') {
                            onBarClick(defectName);
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 4,
                                padding: 12,
                                color: '#475569',
                                font: { size: 11, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.96)',
                            titleColor: '#ffffff',
                            titleFont: { size: 12, weight: '700' },
                            bodyColor: '#f1f5f9',
                            bodyFont: { size: 11, weight: '500' },
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                title: function (tooltipItems) {
                                    if (!tooltipItems.length) return '';
                                    const item = tooltipItems[0];
                                    const c = item.chart;
                                    const p = c?._paretoList?.[item.dataIndex];
                                    return p ? (p.name || p.key) : (c?.data?.labels?.[item.dataIndex] || item.label || '');
                                },
                                label: function (context) {
                                    const val = context.raw || 0;
                                    if (context.dataset.yAxisID === 'y1') {
                                        return `  Kumulatif: ${Number(val).toFixed(1)}%`;
                                    }
                                    return `  Defect: ${Number(val).toLocaleString('id-ID')} pcs`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#475569',
                                font: { size: 10, weight: '600' },
                                padding: 6,
                                callback: function (val) {
                                    const rawLabel = this.getLabelForValue(val);
                                    if (typeof rawLabel === 'string' && rawLabel.length > 13) {
                                        return rawLabel.substring(0, 13) + '…';
                                    }
                                    return rawLabel;
                                }
                            }
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Defect (pcs)',
                                color: '#475569',
                                font: { size: 10, weight: '700' }
                            },
                            ticks: {
                                precision: 0,
                                color: '#475569',
                                font: { size: 11, weight: '600' },
                                padding: 8,
                                callback: function (val) {
                                    if (Number.isInteger(val)) return val.toLocaleString('id-ID');
                                    return '';
                                }
                            },
                            grid: { color: '#e2e8f0', drawBorder: false },
                            border: { dash: [3, 3] }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            min: 0,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Kumulatif (%)',
                                color: '#d97706',
                                font: { size: 10, weight: '700' }
                            },
                            grid: { display: false },
                            ticks: {
                                color: '#d97706',
                                font: { size: 10, weight: '600' },
                                padding: 6,
                                callback: function (val) {
                                    return val + '%';
                                }
                            }
                        }
                    }
                }
            });
            return chart;
        }

        function initCategoryChartsFinal() {
            if (!chartDpmFinalInternal) {
                chartDpmFinalInternal = createDpmChart('chartDpmFinalInternal', THEME_COLORS.internal, THEME_COLORS.internalLight, THEME_COLORS.target, 'DPM');
            }
            if (!chartParetoFinalInternal) {
                chartParetoFinalInternal = createSingleParetoChart('chartParetoFinalInternal', THEME_COLORS.internal, function(defectName) {
                    triggerParetoDrilldown('chartParetoFinalInternal', defectName, 2, null, 'internal');
                });
            }
            if (!chartDpmFinalAssembly) {
                chartDpmFinalAssembly = createDpmChart('chartDpmFinalAssembly', THEME_COLORS.assembly, THEME_COLORS.assemblyLight, THEME_COLORS.target, 'DPM');
            }
            if (!chartParetoFinalAssembly) {
                chartParetoFinalAssembly = createSingleParetoChart('chartParetoFinalAssembly', THEME_COLORS.assembly, function(defectName) {
                    triggerParetoDrilldown('chartParetoFinalAssembly', defectName, 2, null, 'assembly');
                });
            }
        }

        function initCategoryChartsPre() {
            if (!chartDpmPreOperator) {
                chartDpmPreOperator = createDpmChart('chartDpmPreOperator', THEME_COLORS.operator, THEME_COLORS.operatorLight, THEME_COLORS.target, 'PPM');
            }
            if (!chartParetoPreOperator) {
                chartParetoPreOperator = createSingleParetoChart('chartParetoPreOperator', THEME_COLORS.operator, function(defectName) {
                    triggerParetoDrilldown('chartParetoPreOperator', defectName, 2, null, 'operator');
                });
            }
            if (!chartDpmPreInspektor) {
                chartDpmPreInspektor = createDpmChart('chartDpmPreInspektor', THEME_COLORS.inspektor, THEME_COLORS.inspektorLight, THEME_COLORS.target, 'PPM');
            }
            if (!chartParetoPreInspektor) {
                chartParetoPreInspektor = createSingleParetoChart('chartParetoPreInspektor', THEME_COLORS.inspektor, function(defectName) {
                    triggerParetoDrilldown('chartParetoPreInspektor', defectName, 2, null, 'inspektor');
                });
            }
        }

        function updateCategoryDpmData(chart, wrapperId, emptyId, noteId, trends, metricField, color, metricLabel) {
            const wrapper = document.getElementById(wrapperId);
            const emptyEl = document.getElementById(emptyId);
            const noteEl = noteId ? document.getElementById(noteId) : null;
            const msgEl = emptyEl ? emptyEl.querySelector('p') : null;

            const trendList = Array.isArray(trends) ? trends : [];
            const missingInspectCount = trendList.filter(t => (Number(t.total_defect) || 0) > 0 && (!t.total_inspect || Number(t.total_inspect) === 0)).length;
            const validRateCount = trendList.filter(t => t[metricField] !== null && t[metricField] !== undefined && !isNaN(Number(t[metricField]))).length;

            if (trendList.length === 0) {
                if (msgEl) msgEl.textContent = 'Belum ada data pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (noteEl) { noteEl.innerHTML = ''; noteEl.classList.add('hidden'); }
                return;
            }

            const hasAnyTarget = trendList.some(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));
            const allRateNull = trendList.every(t => t[metricField] === null || t[metricField] === undefined);

            if (allRateNull && !hasAnyTarget) {
                if (msgEl) msgEl.textContent = 'Belum ada data pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (noteEl) { noteEl.innerHTML = ''; noteEl.classList.add('hidden'); }
                return;
            }

            if (wrapper) wrapper.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            if (noteEl) {
                noteEl.innerHTML = '';
                noteEl.classList.add('hidden');
                const notes = [];
                if (missingInspectCount > 0) {
                    notes.push(`${missingInspectCount} periode belum memiliki data inspect, ${metricLabel}-nya tidak dapat dihitung.`);
                }
                if (validRateCount === 1) {
                    notes.push(`Baru 1 titik ${metricLabel} valid. Garis tren muncul setelah minimal 2 periode memiliki data inspect.`);
                } else if (validRateCount === 0 && hasAnyTarget) {
                    notes.push(`Belum ada data ${metricLabel} real pada periode ini. Menampilkan garis target.`);
                }
                if (notes.length > 0) {
                    noteEl.innerHTML = notes.map(msg => `
                        <div class="flex items-center space-x-1.5 text-[11px] font-medium text-slate-500">
                            <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>${msg}</span>
                        </div>
                    `).join('');
                    noteEl.classList.remove('hidden');
                }
            }

            if (chart) {
                chart._mode = 'category';
                chart._metricLabel = metricLabel;
                chart.data.labels = trendList.map(t => t.label);
                chart.data.datasets = [
                    {
                        label: `${metricLabel} Real`,
                        data: trendList.map(t => t[metricField]),
                        borderColor: color,
                        backgroundColor: color + '14',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.3,
                        spanGaps: true,
                        pointStyle: 'circle',
                        pointBackgroundColor: color,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        _meta: trendList
                    },
                    {
                        label: `Target ${metricLabel}`,
                        data: hasAnyTarget ? trendList.map(t => (t.target !== null && t.target !== undefined ? Number(t.target) : null)) : [],
                        borderColor: THEME_COLORS.target,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [6, 4],
                        fill: false,
                        tension: 0,
                        spanGaps: false,
                        pointRadius: 0,
                        pointHoverRadius: 0,
                        hidden: !hasAnyTarget
                    }
                ];
                chart.update('none');
            }
        }

        function updateCategoryParetoData(chart, wrapperId, emptyId, pareto, barColor) {
            const wrapper = document.getElementById(wrapperId);
            const emptyEl = document.getElementById(emptyId);
            const msgEl = emptyEl ? emptyEl.querySelector('p') : null;

            const paretoList = Array.isArray(pareto) ? pareto : [];
            const hasData = paretoList.length > 0 && paretoList.some(p => (p.total || 0) > 0);

            if (!hasData) {
                if (msgEl) msgEl.textContent = 'Belum ada data pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            if (wrapper) wrapper.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            if (chart) {
                chart._paretoList = paretoList;
                chart.data.labels = paretoList.map(p => p.name || p.key || '');
                chart.data.datasets = [
                    {
                        type: 'line',
                        label: 'Kumulatif %',
                        data: paretoList.map(p => p.cumulative_percent),
                        borderColor: '#d97706',
                        backgroundColor: '#d97706',
                        borderWidth: 2,
                        pointRadius: 3.5,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#d97706',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        fill: false,
                        tension: 0.25,
                        yAxisID: 'y1',
                        order: 1
                    },
                    {
                        type: 'bar',
                        label: 'Defect',
                        data: paretoList.map(p => p.total || 0),
                        backgroundColor: barColor,
                        borderRadius: 4,
                        maxBarThickness: 44,
                        yAxisID: 'y',
                        order: 2
                    }
                ];
                chart.update('none');
            }
        }

        function updateCategoryDpmSummaryCard(category, trends, filters) {
            let valId = '';
            let periodId = '';
            let subId = '';
            let defectField = '';
            let unitLabel = 'DPM';

            if (category === 'internal') {
                valId = 'summaryValDpmFinalInternal';
                periodId = 'summaryPeriodDpmFinalInternal';
                subId = 'summarySubDpmFinalInternal';
                defectField = 'internal_defect';
                unitLabel = 'DPM';
            } else if (category === 'assembly') {
                valId = 'summaryValDpmFinalAssembly';
                periodId = 'summaryPeriodDpmFinalAssembly';
                subId = 'summarySubDpmFinalAssembly';
                defectField = 'assembly_defect';
                unitLabel = 'DPM';
            } else if (category === 'operator') {
                valId = 'summaryValDpmPreOperator';
                periodId = 'summaryPeriodDpmPreOperator';
                subId = 'summarySubDpmPreOperator';
                defectField = 'operator_defect';
                unitLabel = 'PPM';
            } else if (category === 'inspektor') {
                valId = 'summaryValDpmPreInspektor';
                periodId = 'summaryPeriodDpmPreInspektor';
                subId = 'summarySubDpmPreInspektor';
                defectField = 'inspektor_defect';
                unitLabel = 'PPM';
            }

            const valEl = document.getElementById(valId);
            const periodEl = document.getElementById(periodId);
            const subEl = document.getElementById(subId);

            if (periodEl) {
                periodEl.textContent = formatPeriodLabel(filters.start_date, filters.end_date);
            }

            const list = Array.isArray(trends) ? trends : [];
            let validDefect = 0;
            let validInspect = 0;
            let excludedDefect = 0;

            for (const item of list) {
                const insp = Number(item.total_inspect) || 0;
                const def = Number(item[defectField]) || 0;
                if (insp > 0) {
                    validDefect += def;
                    validInspect += insp;
                } else if (def > 0) {
                    excludedDefect += def;
                }
            }

            if (validInspect > 0) {
                const calcRate = Math.round((validDefect / validInspect) * 1000000);
                if (valEl) valEl.textContent = calcRate.toLocaleString('id-ID');

                const targetFromValid = [...list].reverse().find(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)) && (Number(t.total_inspect) || 0) > 0);
                const latestTarget = targetFromValid || [...list].reverse().find(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));
                let statusPrefix = '';
                if (latestTarget) {
                    const targetVal = Number(latestTarget.target);
                    if (calcRate > targetVal) {
                        statusPrefix = `<span class="text-red-600 font-semibold">⚠️ Melebihi Target (${targetVal.toLocaleString('id-ID')})</span> &bull; `;
                    } else {
                        statusPrefix = `<span class="text-emerald-600 font-semibold">✓ Memenuhi Target (${targetVal.toLocaleString('id-ID')})</span> &bull; `;
                    }
                }

                let subText = `${statusPrefix}${validDefect.toLocaleString('id-ID')} defect / ${validInspect.toLocaleString('id-ID')} inspect (periode valid)`;
                if (excludedDefect > 0) {
                    subText += ` &bull; <span class="text-amber-600 font-medium">${excludedDefect.toLocaleString('id-ID')} defect belum punya data inspect</span>`;
                }
                if (subEl) subEl.innerHTML = subText;
            } else {
                if (valEl) valEl.textContent = '-';
                if (subEl) {
                    if (excludedDefect > 0) {
                        subEl.innerHTML = `<span class="text-amber-600 font-medium">0 inspect valid &bull; ${excludedDefect.toLocaleString('id-ID')} defect belum punya data inspect</span>`;
                    } else {
                        subEl.textContent = '0 inspect pada periode ini';
                    }
                }
            }
        }

        function updateCategoryParetoSummaryCard(category, pareto, filters) {
            let valId = '';
            let countId = '';
            let periodId = '';
            let subId = '';

            if (category === 'internal') {
                valId = 'summaryValParetoFinalInternal';
                countId = 'summaryCountParetoFinalInternal';
                periodId = 'summaryPeriodParetoFinalInternal';
                subId = 'summarySubParetoFinalInternal';
            } else if (category === 'assembly') {
                valId = 'summaryValParetoFinalAssembly';
                countId = 'summaryCountParetoFinalAssembly';
                periodId = 'summaryPeriodParetoFinalAssembly';
                subId = 'summarySubParetoFinalAssembly';
            } else if (category === 'operator') {
                valId = 'summaryValParetoPreOperator';
                countId = 'summaryCountParetoPreOperator';
                periodId = 'summaryPeriodParetoPreOperator';
                subId = 'summarySubParetoPreOperator';
            } else if (category === 'inspektor') {
                valId = 'summaryValParetoPreInspektor';
                countId = 'summaryCountParetoPreInspektor';
                periodId = 'summaryPeriodParetoPreInspektor';
                subId = 'summarySubParetoPreInspektor';
            }

            const valEl = document.getElementById(valId);
            const countEl = document.getElementById(countId);
            const periodEl = document.getElementById(periodId);
            const subEl = document.getElementById(subId);

            if (periodEl) {
                periodEl.textContent = formatPeriodLabel(filters.start_date, filters.end_date);
            }

            const list = Array.isArray(pareto) ? pareto : [];
            const topItem = list.length > 0 && (list[0].total > 0) ? list[0] : null;

            if (topItem) {
                if (valEl) {
                    valEl.textContent = topItem.name || topItem.key;
                    valEl.title = topItem.name || topItem.key;
                }
                if (countEl) {
                    countEl.textContent = `(${Number(topItem.total).toLocaleString('id-ID')} pcs)`;
                }
                if (subEl) {
                    const cum = topItem.cumulative_percent !== undefined ? `${Number(topItem.cumulative_percent).toFixed(1)}%` : '';
                    subEl.textContent = `Peringkat 1 (${cum} kumulatif)`;
                }
            } else {
                if (valEl) {
                    valEl.textContent = 'Tidak Ada Defect';
                    valEl.title = '';
                }
                if (countEl) countEl.textContent = '';
                if (subEl) subEl.textContent = 'Nol defect pada periode ini';
            }
        }

        // Pre Assy specific adjustments
        if (chartDpmPre) {
            chartDpmPre.data.datasets[0].spanGaps = true;
            chartDpmPre.options.scales.y.grace = '10%';
        }
        if (chartParetoPre) {
            chartParetoPre.options.scales.y1.max = 105;
            chartParetoPre.options.scales.y1.clip = false;
            chartParetoPre.data.datasets[0].clip = false;
            chartParetoPre.options.scales.y1.ticks.stepSize = 20;
            chartParetoPre.options.scales.y1.ticks.callback = function(val) {
                if (val > 100) return '';
                return val + '%';
            };
        }

        // --- 4. In-Place Update & Empty State Logic ---
        function updateDpmData(chart, wrapperId, emptyId, trends, groupBy = 'day', mode = currentMode) {
            const isFinal = (wrapperId === 'wrapperDpmFinal');
            const isPre = (wrapperId === 'wrapperDpmPre');
            const wrapper = document.getElementById(wrapperId);
            const emptyEl = document.getElementById(emptyId);
            const msgEl = emptyEl ? emptyEl.querySelector('p') : null;
            const noteEl = document.getElementById(isFinal ? 'noteDpmFinal' : 'noteDpmPre');

            const trendList = Array.isArray(trends) ? trends : [];
            const missingInspectCount = trendList.filter(t => (Number(t.total_defect) || 0) > 0 && (!t.total_inspect || Number(t.total_inspect) === 0)).length;
            const validDpmCount = trendList.filter(t => t.dpm !== null && t.dpm !== undefined && !isNaN(Number(t.dpm))).length;

            if (trendList.length === 0) {
                if (msgEl) msgEl.textContent = 'Belum ada data pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (noteEl) {
                    noteEl.innerHTML = '';
                    noteEl.classList.add('hidden');
                }
                return;
            }

            if (mode !== 'total') {
                const hasAnyTarget = trendList.some(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));
                const allDpmNull = trendList.every(t => t.dpm === null || t.dpm === undefined);
                if (allDpmNull && !hasAnyTarget) {
                    if (msgEl) msgEl.textContent = 'Belum ada data pada periode ini';
                    if (wrapper) wrapper.classList.add('hidden');
                    if (emptyEl) emptyEl.classList.remove('hidden');
                    if (noteEl) {
                        noteEl.innerHTML = '';
                        noteEl.classList.add('hidden');
                    }
                    return;
                }
            }

            if (msgEl) msgEl.textContent = 'Belum ada data pada periode ini';
            if (wrapper) wrapper.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            if (noteEl) {
                noteEl.innerHTML = '';
                noteEl.classList.add('hidden');

                if (mode !== 'total') {
                    const notes = [];
                    const mLabel = isFinal ? 'DPM' : 'PPM';
                    if (missingInspectCount > 0) {
                        notes.push(`${missingInspectCount} periode belum memiliki data inspect, ${mLabel}-nya tidak dapat dihitung.`);
                    }
                    if (validDpmCount === 1) {
                        notes.push(`Baru 1 titik ${mLabel} valid. Garis tren muncul setelah minimal 2 periode memiliki data inspect.`);
                    } else if (validDpmCount === 0 && trendList.some(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)))) {
                        notes.push(`Belum ada data ${mLabel} real pada periode ini. Menampilkan garis target.`);
                    }

                    if (notes.length > 0) {
                        noteEl.innerHTML = notes.map(msg => `
                            <div class="flex items-center space-x-1.5 text-[11px] font-medium text-slate-500">
                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>${msg}</span>
                            </div>
                        `).join('');
                        noteEl.classList.remove('hidden');
                    }
                }
            }

            if (chart) {
                chart._groupBy = groupBy;
                chart._mode = mode;
                chart.data.labels = trendList.map(t => t.label);

                if (mode === 'total') {
                    chart.options.scales.y.title.text = 'Quantity defect';

                    if (isFinal) {
                        chart.data.datasets = [
                            {
                                label: 'Internal Defect',
                                data: trendList.map(t => t.internal_defect || 0),
                                borderColor: THEME_COLORS.internal,
                                backgroundColor: THEME_COLORS.internalLight,
                                borderWidth: 2.5,
                                fill: false,
                                tension: 0.3,
                                spanGaps: false,
                                pointStyle: 'circle',
                                pointBackgroundColor: THEME_COLORS.internal,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                _meta: trendList
                            },
                            {
                                label: 'Assembly Defect',
                                data: trendList.map(t => t.assembly_defect || 0),
                                borderColor: THEME_COLORS.assembly,
                                backgroundColor: THEME_COLORS.assemblyLight,
                                borderWidth: 2.5,
                                fill: false,
                                tension: 0.3,
                                spanGaps: false,
                                pointStyle: 'circle',
                                pointBackgroundColor: THEME_COLORS.assembly,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                _meta: trendList
                            }
                        ];
                    } else {
                        chart.data.datasets = [
                            {
                                label: 'Ditemukan oleh Operator',
                                data: trendList.map(t => t.operator_defect || 0),
                                borderColor: THEME_COLORS.operator,
                                backgroundColor: THEME_COLORS.operatorLight,
                                borderWidth: 2.5,
                                fill: false,
                                tension: 0.3,
                                spanGaps: false,
                                pointStyle: 'circle',
                                pointBackgroundColor: THEME_COLORS.operator,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                _meta: trendList
                            },
                            {
                                label: 'Ditemukan oleh Inspektor',
                                data: trendList.map(t => t.inspektor_defect || 0),
                                borderColor: THEME_COLORS.inspektor,
                                backgroundColor: THEME_COLORS.inspektorLight,
                                borderWidth: 2.5,
                                fill: false,
                                tension: 0.3,
                                spanGaps: false,
                                pointStyle: 'circle',
                                pointBackgroundColor: THEME_COLORS.inspektor,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                _meta: trendList
                            }
                        ];
                    }
                } else if (isFinal && mode === 'final') {
                    chart.options.scales.y.title.text = 'DPM';

                    const hasAnyTarget = trendList.some(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));

                    chart.data.datasets = [
                        {
                            label: 'DPM Real',
                            data: trendList.map(t => t.dpm),
                            borderColor: THEME_COLORS.internalDark,
                            backgroundColor: 'rgba(15, 118, 110, 0.08)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.3,
                            spanGaps: false,
                            pointStyle: 'circle',
                            pointBackgroundColor: THEME_COLORS.internalDark,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            _meta: trendList
                        },
                        {
                            label: 'Target DPM',
                            data: hasAnyTarget ? trendList.map(t => (t.target !== null && t.target !== undefined ? Number(t.target) : null)) : [],
                            borderColor: THEME_COLORS.target,
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            fill: false,
                            tension: 0,
                            spanGaps: false,
                            pointRadius: 0,
                            pointHoverRadius: 0,
                            hidden: !hasAnyTarget
                        },
                        {
                            label: 'DPM Internal',
                            data: trendList.map(t => t.dpm_internal),
                            borderColor: THEME_COLORS.internal,
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [3, 3],
                            fill: false,
                            tension: 0.3,
                            spanGaps: false,
                            pointStyle: 'circle',
                            pointRadius: 3,
                            pointHoverRadius: 5
                        },
                        {
                            label: 'DPM Assembly',
                            data: trendList.map(t => t.dpm_assembly),
                            borderColor: THEME_COLORS.assembly,
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [3, 3],
                            fill: false,
                            tension: 0.3,
                            spanGaps: false,
                            pointStyle: 'circle',
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }
                    ];
                } else if (isPre && mode === 'pre') {
                    chart.options.scales.y.title.text = 'PPM';

                    const hasAnyTarget = trendList.some(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));

                    chart.data.datasets = [
                        {
                            label: 'PPM Real',
                            data: trendList.map(t => t.dpm),
                            borderColor: THEME_COLORS.operatorDark,
                            backgroundColor: THEME_COLORS.operatorLight,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.3,
                            spanGaps: false,
                            pointStyle: 'circle',
                            pointBackgroundColor: THEME_COLORS.operatorDark,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            _meta: trendList
                        },
                        {
                            label: 'Target PPM',
                            data: hasAnyTarget ? trendList.map(t => (t.target !== null && t.target !== undefined ? Number(t.target) : null)) : [],
                            borderColor: THEME_COLORS.target,
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            fill: false,
                            tension: 0,
                            spanGaps: false,
                            pointRadius: 0,
                            pointHoverRadius: 0,
                            hidden: !hasAnyTarget
                        },
                        {
                            label: 'Ditemukan oleh Inspektor',
                            data: trendList.map(t => t.dpm_inspektor),
                            borderColor: THEME_COLORS.inspektor,
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [3, 3],
                            fill: false,
                            tension: 0.3,
                            spanGaps: false,
                            pointStyle: 'circle',
                            pointRadius: 3,
                            pointHoverRadius: 5
                        },
                        {
                            label: 'Ditemukan oleh Operator',
                            data: trendList.map(t => t.dpm_operator),
                            borderColor: THEME_COLORS.operator,
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [3, 3],
                            fill: false,
                            tension: 0.3,
                            spanGaps: false,
                            pointStyle: 'circle',
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }
                    ];
                }

                chart.update('none');
            }
        }

        function updateParetoData(chart, wrapperId, emptyId, pareto, mode = currentMode) {
            const isFinal = (wrapperId === 'wrapperParetoFinal');
            const wrapper = document.getElementById(wrapperId);
            const emptyEl = document.getElementById(emptyId);
            const msgEl = emptyEl ? emptyEl.querySelector('p') : null;

            const paretoList = Array.isArray(pareto) ? pareto : [];
            const hasData = paretoList.length > 0 && paretoList.some(p => (p.total || 0) > 0);

            if (!hasData) {
                if (msgEl) msgEl.textContent = 'Belum ada data pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            if (wrapper) wrapper.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            if (chart) {
                chart._mode = mode;
                chart._paretoList = paretoList;
                chart.data.labels = paretoList.map(p => p.name || p.key || '');

                chart.options.scales.x.stacked = true;
                chart.options.scales.y.stacked = true;

                if (isFinal) {
                    chart.data.datasets = [
                        {
                            type: 'line',
                            label: 'Kumulatif %',
                            data: paretoList.map(p => p.cumulative_percent),
                            borderColor: '#d97706',
                            backgroundColor: '#d97706',
                            borderWidth: 2,
                            pointRadius: 3.5,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#d97706',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            fill: false,
                            tension: 0.25,
                            yAxisID: 'y1',
                            order: 1
                        },
                        {
                            type: 'bar',
                            label: 'Internal Defect',
                            data: paretoList.map(p => p.internal_qty || 0),
                            backgroundColor: THEME_COLORS.internal,
                            borderRadius: 0,
                            maxBarThickness: 44,
                            yAxisID: 'y',
                            order: 2,
                            stack: 'defect'
                        },
                        {
                            type: 'bar',
                            label: 'Assembly Defect',
                            data: paretoList.map(p => p.assembly_qty || 0),
                            backgroundColor: THEME_COLORS.assembly,
                            borderRadius: 4,
                            maxBarThickness: 44,
                            yAxisID: 'y',
                            order: 3,
                            stack: 'defect'
                        }
                    ];
                } else {
                    chart.data.datasets = [
                        {
                            type: 'line',
                            label: 'Kumulatif %',
                            data: paretoList.map(p => p.cumulative_percent),
                            borderColor: '#d97706',
                            backgroundColor: '#d97706',
                            borderWidth: 2,
                            pointRadius: 3.5,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#d97706',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            fill: false,
                            tension: 0.25,
                            yAxisID: 'y1',
                            order: 1
                        },
                        {
                            type: 'bar',
                            label: 'Ditemukan oleh Operator',
                            data: paretoList.map(p => p.operator_qty || 0),
                            backgroundColor: THEME_COLORS.operator,
                            borderRadius: 0,
                            maxBarThickness: 44,
                            yAxisID: 'y',
                            order: 2,
                            stack: 'defect'
                        },
                        {
                            type: 'bar',
                            label: 'Ditemukan oleh Inspektor',
                            data: paretoList.map(p => p.inspektor_qty || 0),
                            backgroundColor: THEME_COLORS.inspektor,
                            borderRadius: 4,
                            maxBarThickness: 44,
                            yAxisID: 'y',
                            order: 3,
                            stack: 'defect'
                        }
                    ];
                }

                chart.update('none');
            }
        }

        function updateDpmSummaryCard(type, trends, filters, mode = currentMode) {
            const isFinal = type === 'final';
            const titleEl = document.getElementById(isFinal ? 'summaryTitleDpmFinal' : 'summaryTitleDpmPre');
            const periodEl = document.getElementById(isFinal ? 'summaryPeriodDpmFinal' : 'summaryPeriodDpmPre');
            const valEl = document.getElementById(isFinal ? 'summaryValDpmFinal' : 'summaryValDpmPre');
            const unitEl = document.getElementById(isFinal ? 'summaryUnitDpmFinal' : 'summaryUnitDpmPre');
            const subEl = document.getElementById(isFinal ? 'summarySubDpmFinal' : 'summarySubDpmPre');

            if (periodEl) {
                periodEl.textContent = formatPeriodLabel(filters.start_date, filters.end_date);
            }

            const list = Array.isArray(trends) ? trends : [];

            if (mode === 'total') {
                if (titleEl) titleEl.textContent = isFinal ? 'Total Defect Final Assy' : 'Total Defect Pre Assy';
                if (unitEl) unitEl.textContent = 'pcs';

                if (isFinal) {
                    const intDef = list.reduce((sum, t) => sum + (Number(t.internal_defect) || 0), 0);
                    const assDef = list.reduce((sum, t) => sum + (Number(t.assembly_defect) || 0), 0);
                    const totDef = intDef + assDef;

                    const intPct = totDef > 0 ? ((intDef / totDef) * 100).toFixed(1).replace('.', ',') : '0';
                    const assPct = totDef > 0 ? ((assDef / totDef) * 100).toFixed(1).replace('.', ',') : '0';

                    if (valEl) valEl.textContent = totDef.toLocaleString('id-ID');
                    if (subEl) {
                        subEl.innerHTML = `Internal: ${intDef.toLocaleString('id-ID')} pcs (${intPct}%) &bull; Assembly: ${assDef.toLocaleString('id-ID')} pcs (${assPct}%)`;
                    }
                } else {
                    const opDef = list.reduce((sum, t) => sum + (Number(t.operator_defect) || 0), 0);
                    const inspDef = list.reduce((sum, t) => sum + (Number(t.inspektor_defect) || 0), 0);
                    const totDef = opDef + inspDef;

                    const opPct = totDef > 0 ? ((opDef / totDef) * 100).toFixed(1).replace('.', ',') : '0';
                    const inspPct = totDef > 0 ? ((inspDef / totDef) * 100).toFixed(1).replace('.', ',') : '0';

                    if (valEl) valEl.textContent = totDef.toLocaleString('id-ID');
                    if (subEl) {
                        subEl.innerHTML = `Ditemukan oleh Operator: ${opDef.toLocaleString('id-ID')} pcs (${opPct}%) &bull; Ditemukan oleh Inspektor: ${inspDef.toLocaleString('id-ID')} pcs (${inspPct}%)`;
                    }
                }
                return;
            }

            if (titleEl) titleEl.textContent = isFinal ? 'DPM Final Assy' : 'PPM Pre Assy';
            if (unitEl) unitEl.textContent = isFinal ? 'DPM' : 'PPM';

            let validDefect = 0;
            let validInspect = 0;
            let excludedDefect = 0;

            for (const item of list) {
                const insp = Number(item.total_inspect) || 0;
                const def = Number(item.total_defect) || 0;
                if (insp > 0) {
                    validDefect += def;
                    validInspect += insp;
                } else if (def > 0) {
                    excludedDefect += def;
                }
            }

            if (validInspect > 0) {
                const dpmCalc = Math.round((validDefect / validInspect) * 1000000);
                if (valEl) valEl.textContent = dpmCalc.toLocaleString('id-ID');

                const targetFromValidPeriod = [...list].reverse().find(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)) && (Number(t.total_inspect) || 0) > 0);
                const latestTargetItem = targetFromValidPeriod || [...list].reverse().find(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));
                let statusPrefix = '';
                if (latestTargetItem) {
                    const targetVal = Number(latestTargetItem.target);
                    if (dpmCalc > targetVal) {
                        statusPrefix = `<span class="text-red-600 font-semibold">⚠️ Melebihi Target (${targetVal.toLocaleString('id-ID')})</span> &bull; `;
                    } else {
                        statusPrefix = `<span class="text-emerald-600 font-semibold">✓ Memenuhi Target (${targetVal.toLocaleString('id-ID')})</span> &bull; `;
                    }
                }

                let subText = `${statusPrefix}${validDefect.toLocaleString('id-ID')} defect / ${validInspect.toLocaleString('id-ID')} inspect (periode valid)`;
                if (excludedDefect > 0) {
                    subText += ` &bull; <span class="text-amber-600 font-medium">${excludedDefect.toLocaleString('id-ID')} defect belum punya data inspect</span>`;
                }
                if (subEl) subEl.innerHTML = subText;
            } else {
                if (valEl) valEl.textContent = '-';
                if (subEl) {
                    if (excludedDefect > 0) {
                        subEl.innerHTML = `<span class="text-amber-600 font-medium">0 inspect valid &bull; ${excludedDefect.toLocaleString('id-ID')} defect belum punya data inspect</span>`;
                    } else {
                        subEl.textContent = '0 inspect pada periode ini';
                    }
                }
            }
        }

        function updateParetoSummaryCard(type, pareto, filters, mode = currentMode) {
            const isFinal = type === 'final';
            const periodEl = document.getElementById(isFinal ? 'summaryPeriodParetoFinal' : 'summaryPeriodParetoPre');
            const valEl = document.getElementById(isFinal ? 'summaryValParetoFinal' : 'summaryValParetoPre');
            const countEl = document.getElementById(isFinal ? 'summaryCountParetoFinal' : 'summaryCountParetoPre');
            const subEl = document.getElementById(isFinal ? 'summarySubParetoFinal' : 'summarySubParetoPre');

            if (periodEl) {
                periodEl.textContent = formatPeriodLabel(filters.start_date, filters.end_date);
            }

            const list = Array.isArray(pareto) ? pareto : [];
            const topItem = list.length > 0 && (list[0].total > 0) ? list[0] : null;

            if (topItem) {
                if (valEl) {
                    valEl.textContent = topItem.name || topItem.key;
                    valEl.title = topItem.name || topItem.key;
                }
                if (countEl) {
                    countEl.textContent = `(${Number(topItem.total).toLocaleString('id-ID')} pcs)`;
                }
                if (subEl) {
                    const cum = topItem.cumulative_percent !== undefined ? `${Number(topItem.cumulative_percent).toFixed(1)}%` : '';
                    if (isFinal) {
                        const intQ = (topItem.internal_qty || 0).toLocaleString('id-ID');
                        const assQ = (topItem.assembly_qty || 0).toLocaleString('id-ID');
                        subEl.textContent = `Peringkat 1 (${cum} kumulatif) • Internal: ${intQ}, Assembly: ${assQ}`;
                    } else {
                        const opQ = (topItem.operator_qty || 0).toLocaleString('id-ID');
                        const inspQ = (topItem.inspektor_qty || 0).toLocaleString('id-ID');
                        subEl.textContent = `Peringkat 1 (${cum} kumulatif) • Ditemukan oleh Operator: ${opQ}, Ditemukan oleh Inspektor: ${inspQ}`;
                    }
                }
            } else {
                if (valEl) {
                    valEl.textContent = 'Tidak Ada Defect';
                    valEl.title = '';
                }
                if (countEl) countEl.textContent = '';
                if (subEl) subEl.textContent = 'Nol defect pada periode ini';
            }
        }

        // --- Helper Indikator Status Fetch Kartu ---
        function setLoadingBadge(id, status) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('hidden');
            if (status === 'loading') {
                el.className = 'inline-flex items-center space-x-1.5 text-[11px] text-slate-700 bg-slate-100 border border-slate-200/90 px-2.5 py-0.5 rounded-full font-bold transition-all';
                el.innerHTML = `
                    <svg class="animate-spin h-3 w-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memuat...</span>
                `;
            } else if (status === 'success') {
                el.className = 'inline-flex items-center space-x-1 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2.5 py-0.5 rounded-full font-semibold transition-all';
                el.innerHTML = `
                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Selesai</span>
                `;
            } else if (status === 'error') {
                el.className = 'inline-flex items-center space-x-1 text-[11px] text-red-700 bg-red-50 border border-red-200/80 px-2.5 py-0.5 rounded-full font-semibold transition-all';
                el.innerHTML = `
                    <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>Gagal</span>
                `;
            }
        }

        // --- 5. Fetch Functions Terpisah Per Grafik (Isolated Abort & State) ---
        let isFetchingDpmFinal = false;
        let isFetchingParetoFinal = false;
        let isFetchingDpmPre = false;
        let isFetchingParetoPre = false;

        function fetchDpmFinal(isSilent = false) {
            const reqMode = currentMode;
            if (isSilent && isFetchingDpmFinal) return;
            if (!isSilent) {
                if (abortDpmFinal) abortDpmFinal.abort();
                setLoadingBadge('loadingDpmFinal', 'loading');
                setLoadingBadge('loadingFinalAssyMode', 'loading');
            }
            abortDpmFinal = new AbortController();
            isFetchingDpmFinal = true;

            const errorEl = document.getElementById('errorDpmFinal');
            const errorMsgEl = document.getElementById('errorMessageDpmFinal');

            const params = new URLSearchParams();
            params.append('assy', 'final');
            if (filtersDpmFinal.start_date) params.append('start_date', filtersDpmFinal.start_date);
            if (filtersDpmFinal.end_date) params.append('end_date', filtersDpmFinal.end_date);
            if (filtersDpmFinal.group_by) params.append('group_by', filtersDpmFinal.group_by);
            if (currentMode === 'final' && filtersDpmFinal.conveyor && filtersDpmFinal.conveyor !== 'all') params.append('conveyor', filtersDpmFinal.conveyor);
            if (currentMode) params.append('mode', currentMode);

            fetch(`{{ route('admin.dashboard.analytics') }}?${params.toString()}`, {
                signal: abortDpmFinal.signal,
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');
                if (!response.ok || !isJson) {
                    if (response.redirected || response.status === 401 || response.status === 419 || !isJson) {
                        const sessionErr = new Error('Sesi berakhir. Muat ulang halaman.');
                        sessionErr.isSessionExpired = true;
                        throw sessionErr;
                    }
                    throw new Error('Gagal memuat data DPM Final Assy.');
                }
                return response.json();
            })
            .then(res => {
                if (reqMode !== currentMode) return;
                const data = res.dpm_final || [];
                if (currentMode === 'final') {
                    initCategoryChartsFinal();
                    updateCategoryDpmData(chartDpmFinalInternal, 'wrapperDpmFinalInternal', 'emptyDpmFinalInternal', 'noteDpmFinalInternal', data, 'dpm_internal', THEME_COLORS.internal, 'DPM');
                    updateCategoryDpmData(chartDpmFinalAssembly, 'wrapperDpmFinalAssembly', 'emptyDpmFinalAssembly', 'noteDpmFinalAssembly', data, 'dpm_assembly', THEME_COLORS.assembly, 'DPM');
                    updateCategoryDpmSummaryCard('internal', data, filtersDpmFinal);
                    updateCategoryDpmSummaryCard('assembly', data, filtersDpmFinal);
                } else {
                    updateDpmData(chartDpmFinal, 'wrapperDpmFinal', 'emptyDpmFinal', data, filtersDpmFinal.group_by, currentMode);
                    updateDpmSummaryCard('final', data, filtersDpmFinal, currentMode);
                }
                setLoadingBadge('loadingDpmFinal', 'success');
                setLoadingBadge('loadingFinalAssyMode', 'success');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (reqMode !== currentMode) return;
                console.error('[Analytics DPM Final] Error fetch:', err);
                setLoadingBadge('loadingDpmFinal', 'error');
                setLoadingBadge('loadingFinalAssyMode', 'error');
                const defaultMsg = 'Gagal memuat data tren defect Final Assy, mencoba lagi pada polling berikutnya.';
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : defaultMsg;
                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            })
            .finally(() => {
                isFetchingDpmFinal = false;
            });
        }

        function fetchParetoFinal(isSilent = false) {
            const reqMode = currentMode;
            if (isSilent && isFetchingParetoFinal) return;
            if (!isSilent) {
                if (abortParetoFinal) abortParetoFinal.abort();
                setLoadingBadge('loadingParetoFinal', 'loading');
                setLoadingBadge('loadingFinalAssyMode', 'loading');
            }
            abortParetoFinal = new AbortController();
            isFetchingParetoFinal = true;

            const errorEl = document.getElementById('errorParetoFinal');
            const errorMsgEl = document.getElementById('errorMessageParetoFinal');

            const params = new URLSearchParams();
            params.append('assy', 'final');
            params.append('pareto_by', 'defect');
            if (filtersParetoFinal.start_date) params.append('start_date', filtersParetoFinal.start_date);
            if (filtersParetoFinal.end_date) params.append('end_date', filtersParetoFinal.end_date);
            if (currentMode === 'final' && filtersParetoFinal.conveyor && filtersParetoFinal.conveyor !== 'all') params.append('conveyor', filtersParetoFinal.conveyor);
            if (currentMode) params.append('mode', currentMode);

            fetch(`{{ route('admin.dashboard.analytics') }}?${params.toString()}`, {
                signal: abortParetoFinal.signal,
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');
                if (!response.ok || !isJson) {
                    if (response.redirected || response.status === 401 || response.status === 419 || !isJson) {
                        const sessionErr = new Error('Sesi berakhir. Muat ulang halaman.');
                        sessionErr.isSessionExpired = true;
                        throw sessionErr;
                    }
                    throw new Error('Gagal memuat data Pareto Final Assy.');
                }
                return response.json();
            })
            .then(res => {
                if (reqMode !== currentMode) return;
                if (currentMode === 'final') {
                    initCategoryChartsFinal();
                    const internalList = res.pareto_final_internal || [];
                    const assemblyList = res.pareto_final_assembly || [];
                    updateCategoryParetoData(chartParetoFinalInternal, 'wrapperParetoFinalInternal', 'emptyParetoFinalInternal', internalList, THEME_COLORS.internal);
                    updateCategoryParetoData(chartParetoFinalAssembly, 'wrapperParetoFinalAssembly', 'emptyParetoFinalAssembly', assemblyList, THEME_COLORS.assembly);
                    updateCategoryParetoSummaryCard('internal', internalList, filtersParetoFinal);
                    updateCategoryParetoSummaryCard('assembly', assemblyList, filtersParetoFinal);
                    updateTop5AreaFinal(res.top5_area_final || []);
                } else {
                    const data = res.pareto_final || [];
                    updateParetoData(chartParetoFinal, 'wrapperParetoFinal', 'emptyParetoFinal', data, currentMode);
                    updateParetoSummaryCard('final', data, filtersParetoFinal, currentMode);
                    updateTop5AreaFinal(res.top5_area_final || []);
                }
                setLoadingBadge('loadingParetoFinal', 'success');
                setLoadingBadge('loadingFinalAssyMode', 'success');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (reqMode !== currentMode) return;
                console.error('[Analytics Pareto Final] Error fetch:', err);
                setLoadingBadge('loadingParetoFinal', 'error');
                setLoadingBadge('loadingFinalAssyMode', 'error');
                const defaultMsg = 'Gagal memuat data Pareto Final Assy, mencoba lagi pada polling berikutnya.';
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : defaultMsg;
                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            })
            .finally(() => {
                isFetchingParetoFinal = false;
            });
        }

        function fetchDpmPre(isSilent = false) {
            const reqMode = currentMode;
            if (isSilent && isFetchingDpmPre) return;
            if (!isSilent) {
                if (abortDpmPre) abortDpmPre.abort();
                setLoadingBadge('loadingDpmPre', 'loading');
                setLoadingBadge('loadingPreAssyMode', 'loading');
            }
            abortDpmPre = new AbortController();
            isFetchingDpmPre = true;

            const errorEl = document.getElementById('errorDpmPre');
            const errorMsgEl = document.getElementById('errorMessageDpmPre');

            const params = new URLSearchParams();
            params.append('assy', 'pre');
            if (filtersDpmPre.start_date) params.append('start_date', filtersDpmPre.start_date);
            if (filtersDpmPre.end_date) params.append('end_date', filtersDpmPre.end_date);
            if (filtersDpmPre.group_by) params.append('group_by', filtersDpmPre.group_by);
            if (currentMode === 'pre' && filtersDpmPre.carline_id && filtersDpmPre.carline_id !== 'all') params.append('carline_id', filtersDpmPre.carline_id);
            if (currentMode) params.append('mode', currentMode);

            fetch(`{{ route('admin.dashboard.analytics') }}?${params.toString()}`, {
                signal: abortDpmPre.signal,
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');
                if (!response.ok || !isJson) {
                    if (response.redirected || response.status === 401 || response.status === 419 || !isJson) {
                        const sessionErr = new Error('Sesi berakhir. Muat ulang halaman.');
                        sessionErr.isSessionExpired = true;
                        throw sessionErr;
                    }
                    throw new Error('Gagal memuat data PPM Pre Assy.');
                }
                return response.json();
            })
            .then(res => {
                if (reqMode !== currentMode) return;
                const data = res.dpm_pre || [];
                if (currentMode === 'pre') {
                    initCategoryChartsPre();
                    updateCategoryDpmData(chartDpmPreOperator, 'wrapperDpmPreOperator', 'emptyDpmPreOperator', 'noteDpmPreOperator', data, 'dpm_operator', THEME_COLORS.operator, 'PPM');
                    updateCategoryDpmData(chartDpmPreInspektor, 'wrapperDpmPreInspektor', 'emptyDpmPreInspektor', 'noteDpmPreInspektor', data, 'dpm_inspektor', THEME_COLORS.inspektor, 'PPM');
                    updateCategoryDpmSummaryCard('operator', data, filtersDpmPre);
                    updateCategoryDpmSummaryCard('inspektor', data, filtersDpmPre);
                } else {
                    updateDpmData(chartDpmPre, 'wrapperDpmPre', 'emptyDpmPre', data, filtersDpmPre.group_by, currentMode);
                    updateDpmSummaryCard('pre', data, filtersDpmPre, currentMode);
                }
                setLoadingBadge('loadingDpmPre', 'success');
                setLoadingBadge('loadingPreAssyMode', 'success');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (reqMode !== currentMode) return;
                console.error('[Analytics DPM Pre] Error fetch:', err);
                setLoadingBadge('loadingDpmPre', 'error');
                setLoadingBadge('loadingPreAssyMode', 'error');
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data PPM Pre Assy, mencoba lagi pada polling berikutnya.';
                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            })
            .finally(() => {
                isFetchingDpmPre = false;
            });
        }

        function fetchParetoPre(isSilent = false) {
            const reqMode = currentMode;
            if (isSilent && isFetchingParetoPre) return;
            if (!isSilent) {
                if (abortParetoPre) abortParetoPre.abort();
                setLoadingBadge('loadingParetoPre', 'loading');
                setLoadingBadge('loadingPreAssyMode', 'loading');
            }
            abortParetoPre = new AbortController();
            isFetchingParetoPre = true;

            const errorEl = document.getElementById('errorParetoPre');
            const errorMsgEl = document.getElementById('errorMessageParetoPre');

            const params = new URLSearchParams();
            params.append('assy', 'pre');
            params.append('pareto_by', 'defect');
            if (filtersParetoPre.start_date) params.append('start_date', filtersParetoPre.start_date);
            if (filtersParetoPre.end_date) params.append('end_date', filtersParetoPre.end_date);
            if (currentMode === 'pre' && filtersParetoPre.carline_id && filtersParetoPre.carline_id !== 'all') params.append('carline_id', filtersParetoPre.carline_id);
            if (currentMode) params.append('mode', currentMode);

            fetch(`{{ route('admin.dashboard.analytics') }}?${params.toString()}`, {
                signal: abortParetoPre.signal,
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');
                if (!response.ok || !isJson) {
                    if (response.redirected || response.status === 401 || response.status === 419 || !isJson) {
                        const sessionErr = new Error('Sesi berakhir. Muat ulang halaman.');
                        sessionErr.isSessionExpired = true;
                        throw sessionErr;
                    }
                    throw new Error('Gagal memuat data Pareto Pre Assy.');
                }
                return response.json();
            })
            .then(res => {
                if (reqMode !== currentMode) return;
                if (currentMode === 'pre') {
                    initCategoryChartsPre();
                    const operatorList = res.pareto_pre_operator || [];
                    const inspektorList = res.pareto_pre_inspektor || [];
                    updateCategoryParetoData(chartParetoPreOperator, 'wrapperParetoPreOperator', 'emptyParetoPreOperator', operatorList, THEME_COLORS.operator);
                    updateCategoryParetoData(chartParetoPreInspektor, 'wrapperParetoPreInspektor', 'emptyParetoPreInspektor', inspektorList, THEME_COLORS.inspektor);
                    updateCategoryParetoSummaryCard('operator', operatorList, filtersParetoPre);
                    updateCategoryParetoSummaryCard('inspektor', inspektorList, filtersParetoPre);
                } else {
                    const data = res.pareto_pre || [];
                    updateParetoData(chartParetoPre, 'wrapperParetoPre', 'emptyParetoPre', data, currentMode);
                    updateParetoSummaryCard('pre', data, filtersParetoPre, currentMode);
                }
                setLoadingBadge('loadingParetoPre', 'success');
                setLoadingBadge('loadingPreAssyMode', 'success');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (reqMode !== currentMode) return;
                console.error('[Analytics Pareto Pre] Error fetch:', err);
                setLoadingBadge('loadingParetoPre', 'error');
                setLoadingBadge('loadingPreAssyMode', 'error');
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data Pareto Pre Assy, mencoba lagi pada polling berikutnya.';
                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            })
            .finally(() => {
                isFetchingParetoPre = false;
            });
        }

        // --- 6. Trigger Filter Debouncers ---
        function triggerFilterDpmFinal(debounceMs = 300) {
            if (debounceDpmFinal) clearTimeout(debounceDpmFinal);
            if (debounceMs === 0) fetchDpmFinal(false);
            else debounceDpmFinal = setTimeout(() => fetchDpmFinal(false), debounceMs);
        }

        function triggerFilterParetoFinal(debounceMs = 300) {
            if (debounceParetoFinal) clearTimeout(debounceParetoFinal);
            if (debounceMs === 0) fetchParetoFinal(false);
            else debounceParetoFinal = setTimeout(() => fetchParetoFinal(false), debounceMs);
        }

        function triggerFilterDpmPre(debounceMs = 300) {
            if (debounceDpmPre) clearTimeout(debounceDpmPre);
            if (debounceMs === 0) fetchDpmPre(false);
            else debounceDpmPre = setTimeout(() => fetchDpmPre(false), debounceMs);
        }

        function triggerFilterParetoPre(debounceMs = 300) {
            if (debounceParetoPre) clearTimeout(debounceParetoPre);
            if (debounceMs === 0) fetchParetoPre(false);
            else debounceParetoPre = setTimeout(() => fetchParetoPre(false), debounceMs);
        }

        // Helper untuk memasang event listener dan validasi native dual date picker (<input type="date">)
        function setupDateRangeFilter({
            startInputId,
            endInputId,
            periodElId,
            filters,
            initialDates,
            triggerFn
        }) {
            const startEl = document.getElementById(startInputId);
            const endEl = document.getElementById(endInputId);
            const periodEl = periodElId ? document.getElementById(periodElId) : null;
            if (!startEl || !endEl) return;

            // 4. Nilai awal diisi ke kedua input, format Y-m-d
            if (initialDates && initialDates.start && initialDates.end) {
                startEl.value = initialDates.start;
                endEl.value = initialDates.end;
                startEl.max = initialDates.end;
                endEl.min = initialDates.start;
            }

            function handleChange() {
                const sVal = startEl.value ? startEl.value.trim() : '';
                const eVal = endEl.value ? endEl.value.trim() : '';

                // 3. Pasang atribut min/max supaya Mulai tidak bisa melewati Selesai dan sebaliknya
                if (sVal) {
                    endEl.min = sVal;
                } else {
                    endEl.removeAttribute('min');
                }

                if (eVal) {
                    startEl.max = eVal;
                } else {
                    startEl.removeAttribute('max');
                }

                // 2. Fetch dipicu hanya jika KEDUANYA terisi dan Selesai >= Mulai,
                //    atau KEDUANYA kosong (kembali ke default). Jika salah satu saja terisi, jangan fetch.
                if (sVal && eVal) {
                    if (eVal >= sVal) {
                        filters.start_date = sVal;
                        filters.end_date = eVal;
                        if (periodEl) periodEl.textContent = formatPeriodLabel(filters.start_date, filters.end_date);
                        triggerFn(300);
                    }
                } else if (!sVal && !eVal) {
                    filters.start_date = '';
                    filters.end_date = '';
                    if (periodEl) periodEl.textContent = formatPeriodLabel('', '');
                    triggerFn(300);
                }
            }

            startEl.addEventListener('change', handleChange);
            endEl.addEventListener('change', handleChange);
        }

        // --- 7. UI Events: DPM Final Assy ---
        setupDateRangeFilter({
            startInputId: 'filterStartDateDpmFinal',
            endInputId: 'filterEndDateDpmFinal',
            periodElId: 'summaryPeriodDpmFinal',
            filters: filtersDpmFinal,
            initialDates: initDpmFinal,
            triggerFn: triggerFilterDpmFinal
        });

        const groupButtonsDpmFinal = document.querySelectorAll('.btn-group-by-dpm-final');
        groupButtonsDpmFinal.forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.dataset.val;
                if (filtersDpmFinal.group_by === val) return;
                filtersDpmFinal.group_by = val;
                groupButtonsDpmFinal.forEach(b => {
                    b.classList.remove('bg-teal-600', 'text-white', 'shadow-2xs');
                    b.classList.add('text-slate-600', 'hover:text-slate-900');
                });
                this.classList.remove('text-slate-600', 'hover:text-slate-900');
                this.classList.add('bg-teal-600', 'text-white', 'shadow-2xs');
                triggerFilterDpmFinal(300);
            });
        });

        const conveyorSelectDpmFinal = document.getElementById('filterConveyorDpmFinal');
        if (conveyorSelectDpmFinal) {
            conveyorSelectDpmFinal.addEventListener('change', function () {
                filtersDpmFinal.conveyor = this.value;
                updateConveyorFilterUI(currentMode);
                triggerFilterDpmFinal(300);
            });
        }

        const btnResetDpmFinal = document.getElementById('btnResetDpmFinal');
        if (btnResetDpmFinal) {
            btnResetDpmFinal.addEventListener('click', function () {
                const resetDates = getInitialDates();
                filtersDpmFinal.start_date = resetDates.start;
                filtersDpmFinal.end_date = resetDates.end;
                filtersDpmFinal.group_by = 'day';
                filtersDpmFinal.conveyor = 'all';

                const startEl = document.getElementById('filterStartDateDpmFinal');
                const endEl = document.getElementById('filterEndDateDpmFinal');
                if (startEl && endEl) {
                    startEl.value = resetDates.start;
                    endEl.value = resetDates.end;
                    startEl.max = resetDates.end;
                    endEl.min = resetDates.start;
                }

                const periodEl = document.getElementById('summaryPeriodDpmFinal');
                if (periodEl) periodEl.textContent = formatPeriodLabel(resetDates.start, resetDates.end);

                if (conveyorSelectDpmFinal) conveyorSelectDpmFinal.value = 'all';
                updateConveyorFilterUI(currentMode);

                groupButtonsDpmFinal.forEach(b => {
                    if (b.dataset.val === 'day') {
                        b.classList.remove('text-slate-600', 'hover:text-slate-900');
                        b.classList.add('bg-teal-600', 'text-white', 'shadow-2xs');
                    } else {
                        b.classList.remove('bg-teal-600', 'text-white', 'shadow-2xs');
                        b.classList.add('text-slate-600', 'hover:text-slate-900');
                    }
                });

                triggerFilterDpmFinal(0);
            });
        }

        // --- 8. UI Events: Pareto Final Assy ---
        setupDateRangeFilter({
            startInputId: 'filterStartDateParetoFinal',
            endInputId: 'filterEndDateParetoFinal',
            periodElId: 'summaryPeriodParetoFinal',
            filters: filtersParetoFinal,
            initialDates: initParetoFinal,
            triggerFn: triggerFilterParetoFinal
        });

        const conveyorSelectParetoFinal = document.getElementById('filterConveyorParetoFinal');
        if (conveyorSelectParetoFinal) {
            conveyorSelectParetoFinal.addEventListener('change', function () {
                filtersParetoFinal.conveyor = this.value;
                updateConveyorFilterUI(currentMode);
                triggerFilterParetoFinal(300);
            });
        }

        const btnResetParetoFinal = document.getElementById('btnResetParetoFinal');
        if (btnResetParetoFinal) {
            btnResetParetoFinal.addEventListener('click', function () {
                const resetDates = getInitialDates();
                filtersParetoFinal.start_date = resetDates.start;
                filtersParetoFinal.end_date = resetDates.end;
                filtersParetoFinal.conveyor = 'all';

                const startEl = document.getElementById('filterStartDateParetoFinal');
                const endEl = document.getElementById('filterEndDateParetoFinal');
                if (startEl && endEl) {
                    startEl.value = resetDates.start;
                    endEl.value = resetDates.end;
                    startEl.max = resetDates.end;
                    endEl.min = resetDates.start;
                }

                const periodEl = document.getElementById('summaryPeriodParetoFinal');
                if (periodEl) periodEl.textContent = formatPeriodLabel(resetDates.start, resetDates.end);

                if (conveyorSelectParetoFinal) conveyorSelectParetoFinal.value = 'all';
                updateConveyorFilterUI(currentMode);

                triggerFilterParetoFinal(0);
            });
        }

        // --- 9. UI Events: DPM Pre Assy ---
        setupDateRangeFilter({
            startInputId: 'filterStartDateDpmPre',
            endInputId: 'filterEndDateDpmPre',
            periodElId: 'summaryPeriodDpmPre',
            filters: filtersDpmPre,
            initialDates: initDpmPre,
            triggerFn: triggerFilterDpmPre
        });

        const groupButtonsDpmPre = document.querySelectorAll('.btn-group-by-dpm-pre');
        groupButtonsDpmPre.forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.dataset.val;
                if (filtersDpmPre.group_by === val) return;
                filtersDpmPre.group_by = val;
                groupButtonsDpmPre.forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white', 'shadow-2xs');
                    b.classList.add('text-slate-600', 'hover:text-slate-900');
                });
                this.classList.remove('text-slate-600', 'hover:text-slate-900');
                this.classList.add('bg-blue-600', 'text-white', 'shadow-2xs');
                triggerFilterDpmPre(300);
            });
        });

        const carlineSelectDpmPre = document.getElementById('filterCarlineDpmPre');
        if (carlineSelectDpmPre) {
            carlineSelectDpmPre.addEventListener('change', function () {
                filtersDpmPre.carline_id = this.value;
                triggerFilterDpmPre(300);
            });
        }

        const btnResetDpmPre = document.getElementById('btnResetDpmPre');
        if (btnResetDpmPre) {
            btnResetDpmPre.addEventListener('click', function () {
                const resetDates = getInitialDates();
                filtersDpmPre.start_date = resetDates.start;
                filtersDpmPre.end_date = resetDates.end;
                filtersDpmPre.group_by = 'day';
                filtersDpmPre.carline_id = 'all';

                const startEl = document.getElementById('filterStartDateDpmPre');
                const endEl = document.getElementById('filterEndDateDpmPre');
                if (startEl && endEl) {
                    startEl.value = resetDates.start;
                    endEl.value = resetDates.end;
                    startEl.max = resetDates.end;
                    endEl.min = resetDates.start;
                }

                const periodEl = document.getElementById('summaryPeriodDpmPre');
                if (periodEl) periodEl.textContent = formatPeriodLabel(resetDates.start, resetDates.end);

                if (carlineSelectDpmPre) carlineSelectDpmPre.value = 'all';

                groupButtonsDpmPre.forEach(b => {
                    if (b.dataset.val === 'day') {
                        b.classList.remove('text-slate-600', 'hover:text-slate-900');
                        b.classList.add('bg-blue-600', 'text-white', 'shadow-2xs');
                    } else {
                        b.classList.remove('bg-blue-600', 'text-white', 'shadow-2xs');
                        b.classList.add('text-slate-600', 'hover:text-slate-900');
                    }
                });

                triggerFilterDpmPre(0);
            });
        }

        // --- 10. UI Events: Pareto Pre Assy ---
        setupDateRangeFilter({
            startInputId: 'filterStartDateParetoPre',
            endInputId: 'filterEndDateParetoPre',
            periodElId: 'summaryPeriodParetoPre',
            filters: filtersParetoPre,
            initialDates: initParetoPre,
            triggerFn: triggerFilterParetoPre
        });

        const carlineSelectParetoPre = document.getElementById('filterCarlineParetoPre');
        if (carlineSelectParetoPre) {
            carlineSelectParetoPre.addEventListener('change', function () {
                filtersParetoPre.carline_id = this.value;
                triggerFilterParetoPre(300);
            });
        }

        const btnResetParetoPre = document.getElementById('btnResetParetoPre');
        if (btnResetParetoPre) {
            btnResetParetoPre.addEventListener('click', function () {
                const resetDates = getInitialDates();
                filtersParetoPre.start_date = resetDates.start;
                filtersParetoPre.end_date = resetDates.end;
                filtersParetoPre.carline_id = 'all';

                const startEl = document.getElementById('filterStartDateParetoPre');
                const endEl = document.getElementById('filterEndDateParetoPre');
                if (startEl && endEl) {
                    startEl.value = resetDates.start;
                    endEl.value = resetDates.end;
                    startEl.max = resetDates.end;
                    endEl.min = resetDates.start;
                }

                const periodEl = document.getElementById('summaryPeriodParetoPre');
                if (periodEl) periodEl.textContent = formatPeriodLabel(resetDates.start, resetDates.end);

                if (carlineSelectParetoPre) carlineSelectParetoPre.value = 'all';

                triggerFilterParetoPre(0);
            });
        }

        // --- 10b. UI Events: Unified Mode Final Assy Toolbar ---
        const initFinalAssyDates = getInitialDates();
        const startInputFinalAssy = document.getElementById('filterStartDateFinalAssy');
        const endInputFinalAssy = document.getElementById('filterEndDateFinalAssy');
        if (startInputFinalAssy && endInputFinalAssy) {
            startInputFinalAssy.value = initFinalAssyDates.start;
            endInputFinalAssy.value = initFinalAssyDates.end;
            startInputFinalAssy.max = initFinalAssyDates.end;
            endInputFinalAssy.min = initFinalAssyDates.start;

            startInputFinalAssy.addEventListener('change', function () {
                filtersDpmFinal.start_date = this.value;
                filtersParetoFinal.start_date = this.value;
                endInputFinalAssy.min = this.value;
                triggerFilterDpmFinal(300);
                triggerFilterParetoFinal(300);
            });
            endInputFinalAssy.addEventListener('change', function () {
                filtersDpmFinal.end_date = this.value;
                filtersParetoFinal.end_date = this.value;
                startInputFinalAssy.max = this.value;
                triggerFilterDpmFinal(300);
                triggerFilterParetoFinal(300);
            });
        }

        const groupButtonsFinalAssy = document.querySelectorAll('.btn-group-by-final-assy');
        groupButtonsFinalAssy.forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.dataset.val;
                if (filtersDpmFinal.group_by === val) return;
                filtersDpmFinal.group_by = val;
                groupButtonsFinalAssy.forEach(b => {
                    b.classList.remove('bg-teal-600', 'text-white', 'shadow-2xs');
                    b.classList.add('text-slate-600', 'hover:text-slate-900');
                });
                this.classList.remove('text-slate-600', 'hover:text-slate-900');
                this.classList.add('bg-teal-600', 'text-white', 'shadow-2xs');
                triggerFilterDpmFinal(300);
            });
        });

        const conveyorSelectFinalAssy = document.getElementById('filterConveyorFinalAssy');
        if (conveyorSelectFinalAssy) {
            conveyorSelectFinalAssy.addEventListener('change', function () {
                filtersDpmFinal.conveyor = this.value;
                filtersParetoFinal.conveyor = this.value;
                triggerFilterDpmFinal(300);
                triggerFilterParetoFinal(300);
            });
        }

        const btnResetFinalAssy = document.getElementById('btnResetFinalAssy');
        if (btnResetFinalAssy) {
            btnResetFinalAssy.addEventListener('click', function () {
                const resetDates = getInitialDates();
                filtersDpmFinal.start_date = resetDates.start;
                filtersDpmFinal.end_date = resetDates.end;
                filtersDpmFinal.group_by = 'day';
                filtersDpmFinal.conveyor = 'all';

                filtersParetoFinal.start_date = resetDates.start;
                filtersParetoFinal.end_date = resetDates.end;
                filtersParetoFinal.conveyor = 'all';

                if (startInputFinalAssy && endInputFinalAssy) {
                    startInputFinalAssy.value = resetDates.start;
                    endInputFinalAssy.value = resetDates.end;
                    startInputFinalAssy.max = resetDates.end;
                    endInputFinalAssy.min = resetDates.start;
                }

                if (conveyorSelectFinalAssy) conveyorSelectFinalAssy.value = 'all';

                groupButtonsFinalAssy.forEach(b => {
                    if (b.dataset.val === 'day') {
                        b.classList.remove('text-slate-600', 'hover:text-slate-900');
                        b.classList.add('bg-teal-600', 'text-white', 'shadow-2xs');
                    } else {
                        b.classList.remove('bg-teal-600', 'text-white', 'shadow-2xs');
                        b.classList.add('text-slate-600', 'hover:text-slate-900');
                    }
                });

                triggerFilterDpmFinal(0);
                triggerFilterParetoFinal(0);
            });
        }

        // --- 10c. UI Events: Unified Mode Pre Assy Toolbar ---
        const initPreAssyDates = getInitialDates();
        const startInputPreAssy = document.getElementById('filterStartDatePreAssy');
        const endInputPreAssy = document.getElementById('filterEndDatePreAssy');
        if (startInputPreAssy && endInputPreAssy) {
            startInputPreAssy.value = initPreAssyDates.start;
            endInputPreAssy.value = initPreAssyDates.end;
            startInputPreAssy.max = initPreAssyDates.end;
            endInputPreAssy.min = initPreAssyDates.start;

            startInputPreAssy.addEventListener('change', function () {
                filtersDpmPre.start_date = this.value;
                filtersParetoPre.start_date = this.value;
                endInputPreAssy.min = this.value;
                triggerFilterDpmPre(300);
                triggerFilterParetoPre(300);
            });
            endInputPreAssy.addEventListener('change', function () {
                filtersDpmPre.end_date = this.value;
                filtersParetoPre.end_date = this.value;
                startInputPreAssy.max = this.value;
                triggerFilterDpmPre(300);
                triggerFilterParetoPre(300);
            });
        }

        const groupButtonsPreAssy = document.querySelectorAll('.btn-group-by-pre-assy');
        groupButtonsPreAssy.forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.dataset.val;
                if (filtersDpmPre.group_by === val) return;
                filtersDpmPre.group_by = val;
                groupButtonsPreAssy.forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white', 'shadow-2xs');
                    b.classList.add('text-slate-600', 'hover:text-slate-900');
                });
                this.classList.remove('text-slate-600', 'hover:text-slate-900');
                this.classList.add('bg-blue-600', 'text-white', 'shadow-2xs');
                triggerFilterDpmPre(300);
            });
        });

        const carlineSelectPreAssy = document.getElementById('filterCarlinePreAssy');
        if (carlineSelectPreAssy) {
            carlineSelectPreAssy.addEventListener('change', function () {
                filtersDpmPre.carline_id = this.value;
                filtersParetoPre.carline_id = this.value;
                triggerFilterDpmPre(300);
                triggerFilterParetoPre(300);
            });
        }

        const btnResetPreAssy = document.getElementById('btnResetPreAssy');
        if (btnResetPreAssy) {
            btnResetPreAssy.addEventListener('click', function () {
                const resetDates = getInitialDates();
                filtersDpmPre.start_date = resetDates.start;
                filtersDpmPre.end_date = resetDates.end;
                filtersDpmPre.group_by = 'day';
                filtersDpmPre.carline_id = 'all';

                filtersParetoPre.start_date = resetDates.start;
                filtersParetoPre.end_date = resetDates.end;
                filtersParetoPre.carline_id = 'all';

                if (startInputPreAssy && endInputPreAssy) {
                    startInputPreAssy.value = resetDates.start;
                    endInputPreAssy.value = resetDates.end;
                    startInputPreAssy.max = resetDates.end;
                    endInputPreAssy.min = resetDates.start;
                }

                if (carlineSelectPreAssy) carlineSelectPreAssy.value = 'all';

                groupButtonsPreAssy.forEach(b => {
                    if (b.dataset.val === 'day') {
                        b.classList.remove('text-slate-600', 'hover:text-slate-900');
                        b.classList.add('bg-blue-600', 'text-white', 'shadow-2xs');
                    } else {
                        b.classList.remove('bg-blue-600', 'text-white', 'shadow-2xs');
                        b.classList.add('text-slate-600', 'hover:text-slate-900');
                    }
                });

                triggerFilterDpmPre(0);
                triggerFilterParetoPre(0);
            });
        }

        // --- 11. Polling Stats (Real-time Defect & Users) ---
        function flashElement(el) {
            if (!el) return;
            el.classList.remove('poll-updated');
            void el.offsetWidth;
            el.classList.add('poll-updated');
        }

        function fetchStats() {
            const reqMode = currentMode;
            fetch(`{{ url("/api/dashboard/stats") }}?mode=${encodeURIComponent(currentMode)}`)
                .then(r => r.json())
                .then(stats => {
                    if (reqMode !== currentMode) return;
                    const totalEl = document.getElementById('stat-total-defect');
                    if (totalEl && stats.totalDefect !== undefined && stats.totalDefect !== null) {
                        const num = Number(stats.totalDefect);
                        const newTxt = isNaN(num) ? '0' : num.toLocaleString('id-ID');
                        if (totalEl.innerText !== newTxt) {
                            totalEl.innerText = newTxt;
                            flashElement(totalEl);
                        }
                    }

                    const todayEl = document.getElementById('stat-defect-today');
                    if (todayEl && stats.defectToday !== undefined && stats.defectToday !== null) {
                        const num = Number(stats.defectToday);
                        const newTxt = isNaN(num) ? '0' : num.toLocaleString('id-ID');
                        if (todayEl.innerText !== newTxt) {
                            todayEl.innerText = newTxt;
                            flashElement(todayEl);
                        }
                    }

                    const activeEl = document.getElementById('stat-active-users');
                    if (activeEl && stats.activeUsers !== undefined && stats.activeUsers !== null) {
                        const num = Number(stats.activeUsers);
                        const newTxt = isNaN(num) ? '0' : String(num);
                        if (activeEl.innerText !== newTxt) {
                            activeEl.innerText = newTxt;
                            flashElement(activeEl);
                        }
                    }

                    const totalUsersEl = document.getElementById('stat-total-users');
                    if (totalUsersEl && stats.totalUsers !== undefined && stats.totalUsers !== null) {
                        const num = Number(stats.totalUsers);
                        const newTxt = isNaN(num) ? '0' : String(num);
                        if (totalUsersEl.innerText !== newTxt) {
                            totalUsersEl.innerText = newTxt;
                            flashElement(totalUsersEl);
                        }
                    }
                })
                .catch(err => {
                    if (reqMode !== currentMode) return;
                    console.error('[Polling] Gagal fetch stats:', err);
                });
        }

        // --- 12. Polling Execution (8s & Tab Inactive Prevention) ---
        function runPollingCycle() {
            if (document.hidden) return;
            fetchStats();
            if (currentMode === 'total') {
                fetchDpmFinal(true);
                fetchParetoFinal(true);
                fetchDpmPre(true);
                fetchParetoPre(true);
            } else if (currentMode === 'final') {
                fetchDpmFinal(true);
                fetchParetoFinal(true);
            } else if (currentMode === 'pre') {
                fetchDpmPre(true);
                fetchParetoPre(true);
            }
        }

        // Inisialisasi label periode awal kartu analitik
        if (document.getElementById('summaryPeriodDpmFinal')) {
            document.getElementById('summaryPeriodDpmFinal').textContent = formatPeriodLabel(filtersDpmFinal.start_date, filtersDpmFinal.end_date);
        }
        if (document.getElementById('summaryPeriodParetoFinal')) {
            document.getElementById('summaryPeriodParetoFinal').textContent = formatPeriodLabel(filtersParetoFinal.start_date, filtersParetoFinal.end_date);
        }
        if (document.getElementById('summaryPeriodDpmPre')) {
            document.getElementById('summaryPeriodDpmPre').textContent = formatPeriodLabel(filtersDpmPre.start_date, filtersDpmPre.end_date);
        }
        if (document.getElementById('summaryPeriodParetoPre')) {
            document.getElementById('summaryPeriodParetoPre').textContent = formatPeriodLabel(filtersParetoPre.start_date, filtersParetoPre.end_date);
        }

        // Inisialisasi visual mode awal (judul kartu, filter conveyor, dll.)
        updateModeVisuals(currentMode);

        // Initial Load
        fetchStats();
        if (currentMode === 'total') {
            fetchDpmFinal();
            fetchParetoFinal();
            fetchDpmPre();
            fetchParetoPre();
        } else if (currentMode === 'final') {
            fetchDpmFinal();
            fetchParetoFinal();
        } else if (currentMode === 'pre') {
            fetchDpmPre();
            fetchParetoPre();
        }

        // 8-second interval
        pollingTimer = setInterval(runPollingCycle, 8000);

        // Resume saat tab kembali aktif
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                runPollingCycle();
            }
        });

        // --- Top 5 Area Ditemukan (Final Assy) Handler ---
        let chartTop5AreaFinalInstance = null;
        function updateTop5AreaFinal(items) {
            const canvas = document.getElementById('chartTop5AreaFinal');
            const empty = document.getElementById('emptyTop5AreaFinal');
            if (!canvas) return;

            if (!items || items.length === 0) {
                if (empty) empty.classList.remove('hidden');
                if (chartTop5AreaFinalInstance) {
                    chartTop5AreaFinalInstance.destroy();
                    chartTop5AreaFinalInstance = null;
                }
                return;
            }

            if (empty) empty.classList.add('hidden');

            if (chartTop5AreaFinalInstance) {
                chartTop5AreaFinalInstance.destroy();
                chartTop5AreaFinalInstance = null;
            }

            const ctx = canvas.getContext('2d');
            chartTop5AreaFinalInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: items.map(i => i.name),
                    datasets: [{
                        label: 'Total Defect (PCS)',
                        data: items.map(i => i.total),
                        backgroundColor: '#0f766e',
                        borderRadius: 6,
                        maxBarThickness: 22,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleFont: { size: 11, weight: '700' },
                            bodyFont: { size: 11, weight: '500' },
                            cornerRadius: 6,
                            padding: 8,
                            callbacks: {
                                label: function(ctx) {
                                    return `  Total Defect: ${Number(ctx.raw).toLocaleString('id-ID')} PCS`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { precision: 0, font: { size: 10, weight: '600' }, color: '#64748b' }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11, weight: '700' },
                                color: '#1e293b',
                                callback: function(val, index) {
                                    const raw = this.chart.data.labels[index];
                                    return (typeof raw === 'string' && raw.length > 24) ? raw.substring(0, 24) + '…' : raw;
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- Pareto Drilldown Handler (3-Level Drilldown) ---
        function triggerParetoDrilldown(canvasId, defectName, targetLevel = 2, carlineName = null, category = null) {
            let targetType = 'Final';
            let isPre = false;
            let barColor = '#0f766e';
            let jenisAssy = 'Final Assy';
            let filters = filtersParetoFinal;

            if (category === 'internal') {
                targetType = 'FinalInternal';
                isPre = false;
                barColor = THEME_COLORS.internal;
                jenisAssy = 'Final Assy';
                filters = filtersParetoFinal;
            } else if (category === 'assembly') {
                targetType = 'FinalAssembly';
                isPre = false;
                barColor = THEME_COLORS.assembly;
                jenisAssy = 'Final Assy';
                filters = filtersParetoFinal;
            } else if (category === 'operator') {
                targetType = 'PreOperator';
                isPre = true;
                barColor = THEME_COLORS.operator;
                jenisAssy = 'Pre Assy';
                filters = filtersParetoPre;
            } else if (category === 'inspektor') {
                targetType = 'PreInspektor';
                isPre = true;
                barColor = THEME_COLORS.inspektor;
                jenisAssy = 'Pre Assy';
                filters = filtersParetoPre;
            } else {
                isPre = (canvasId === 'chartParetoPre');
                targetType = isPre ? 'Pre' : 'Final';
                barColor = isPre ? '#2563eb' : '#0f766e';
                jenisAssy = isPre ? 'Pre Assy' : 'Final Assy';
                filters = isPre ? filtersParetoPre : filtersParetoFinal;
            }

            const cleanDefect = (typeof defectName === 'string')
                ? defectName.replace(/^\[(Final[\s+]+Pre|Final|Pre)\]\s*/i, '').trim()
                : defectName;

            const wrapper = document.getElementById(`drilldownWrapperPareto${targetType}`);
            const badge = document.getElementById(`drilldownBadgePareto${targetType}`);
            const carlineWrap = document.getElementById(`drilldownCarlineBadgeWrap${targetType}`);
            const carlineBadge = document.getElementById(`drilldownCarlineBadge${targetType}`);
            const btnBack = document.getElementById(`btnBackDrilldownPareto${targetType}`);
            const btnBackText = document.getElementById(`btnBackText${targetType}`);
            const loading = document.getElementById(`drilldownLoadingPareto${targetType}`);
            const empty = document.getElementById(`drilldownEmptyPareto${targetType}`);
            const canvasWrap = document.getElementById(`drilldownCanvasWrapPareto${targetType}`);
            const canvas = document.getElementById(`chartDrilldownPareto${targetType}`);
            const titleEl = document.getElementById(`drilldownTitlePareto${targetType}`);
            const subEl = document.getElementById(`drilldownSubPareto${targetType}`);

            if (!wrapper) return;

            wrapper.classList.remove('hidden');
            if (badge) badge.textContent = cleanDefect;

            if (targetLevel === 3 && carlineName) {
                if (titleEl) titleEl.textContent = 'Breakdown per Area Ditemukan';
                if (carlineWrap) carlineWrap.classList.remove('hidden');
                if (carlineBadge) carlineBadge.textContent = carlineName;
                if (subEl) subEl.textContent = `Breakdown defect pada ${carlineName} per Area Ditemukan`;
                if (btnBack) {
                    btnBack.classList.remove('hidden');
                    if (btnBackText) btnBackText.textContent = 'Kembali ke Carline';
                    btnBack.onclick = function () {
                        triggerParetoDrilldown(canvasId, cleanDefect, 2, null, category);
                    };
                }
            } else {
                if (titleEl) {
                    titleEl.textContent = isPre ? 'Breakdown per Carline' : 'Breakdown per Carline / Conveyor';
                }
                if (carlineWrap) carlineWrap.classList.add('hidden');
                if (subEl) subEl.textContent = 'Klik batang carline untuk melihat breakdown per Area Ditemukan';
                if (btnBack) {
                    btnBack.classList.remove('hidden');
                    if (btnBackText) btnBackText.textContent = 'Kembali ke Defect';
                    btnBack.onclick = function () {
                        wrapper.classList.add('hidden');
                        if (drilldownCharts[targetType]) {
                            drilldownCharts[targetType].destroy();
                            drilldownCharts[targetType] = null;
                        }
                        if (targetType === 'Final' && chartDrilldownParetoFinal) {
                            chartDrilldownParetoFinal.destroy();
                            chartDrilldownParetoFinal = null;
                        }
                        if (targetType === 'Pre' && chartDrilldownParetoPre) {
                            chartDrilldownParetoPre.destroy();
                            chartDrilldownParetoPre = null;
                        }
                    };
                }
            }

            if (loading) loading.classList.remove('hidden');
            if (empty) empty.classList.add('hidden');
            if (canvasWrap) canvasWrap.classList.add('hidden');

            const params = new URLSearchParams({
                defect: cleanDefect,
                sub_defect: cleanDefect,
                level: targetLevel,
                start_date: filters.start_date || '',
                end_date: filters.end_date || '',
                mode: currentMode,
                jenis_assy: jenisAssy
            });

            if (category) {
                params.append('category', category);
            }

            if (targetLevel === 3 && carlineName) {
                params.append('carline', carlineName);
            }

            fetch(`{{ route('admin.dashboard.pareto_drilldown') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (loading) loading.classList.add('hidden');
                const items = data.items || [];

                if (items.length === 0) {
                    if (empty) {
                        empty.textContent = (targetLevel === 3)
                            ? 'Tidak ada data area ditemukan untuk kombinasi defect dan carline ini'
                            : (isPre ? 'Tidak ada data carline untuk defect ini' : 'Tidak ada data conveyor / carline untuk defect ini');
                        empty.classList.remove('hidden');
                    }
                    return;
                }

                if (canvasWrap) canvasWrap.classList.remove('hidden');

                if (drilldownCharts[targetType]) {
                    drilldownCharts[targetType].destroy();
                    drilldownCharts[targetType] = null;
                }
                if (targetType === 'Final' && chartDrilldownParetoFinal) {
                    chartDrilldownParetoFinal.destroy();
                    chartDrilldownParetoFinal = null;
                }
                if (targetType === 'Pre' && chartDrilldownParetoPre) {
                    chartDrilldownParetoPre.destroy();
                    chartDrilldownParetoPre = null;
                }

                const ctx = canvas.getContext('2d');
                const newChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: items.map(i => i.name),
                        datasets: [{
                            label: 'Total Defect',
                            data: items.map(i => i.total),
                            backgroundColor: (targetLevel === 3) ? '#f59e0b' : barColor,
                            borderRadius: 6,
                            maxBarThickness: 22,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        onHover: function(event, elements) {
                            if (targetLevel === 2) {
                                event.native.target.style.cursor = (elements && elements.length > 0) ? 'pointer' : 'default';
                            }
                        },
                        onClick: function(event, elements) {
                            if (targetLevel === 2 && elements && elements.length > 0) {
                                const el = elements[0];
                                const selectedCarline = this.data.labels[el.index];
                                if (selectedCarline) {
                                    triggerParetoDrilldown(canvasId, cleanDefect, 3, selectedCarline, category);
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleFont: { size: 11, weight: '700' },
                                bodyFont: { size: 11, weight: '500' },
                                cornerRadius: 6,
                                padding: 8,
                                callbacks: {
                                    label: function(ctx) {
                                        return `  Total: ${Number(ctx.raw).toLocaleString('id-ID')} PCS`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: { precision: 0, font: { size: 10, weight: '600' }, color: '#64748b' }
                            },
                            y: {
                                grid: { display: false },
                                ticks: {
                                    font: { size: 11, weight: '700' },
                                    color: '#1e293b',
                                    callback: function(val, index) {
                                        const raw = this.chart.data.labels[index];
                                        return (typeof raw === 'string' && raw.length > 24) ? raw.substring(0, 24) + '…' : raw;
                                    }
                                }
                            }
                        }
                    }
                });

                drilldownCharts[targetType] = newChart;
                if (targetType === 'Final') chartDrilldownParetoFinal = newChart;
                if (targetType === 'Pre') chartDrilldownParetoPre = newChart;

                wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(err => {
                console.error('[Pareto Drilldown] Error:', err);
                if (loading) loading.classList.add('hidden');
                if (empty) {
                    empty.textContent = 'Gagal memuat data breakdown.';
                    empty.classList.remove('hidden');
                }
            });
        }

        ['Final', 'Pre', 'FinalInternal', 'FinalAssembly', 'PreOperator', 'PreInspektor'].forEach(suffix => {
            const btn = document.getElementById(`btnCloseDrilldownPareto${suffix}`);
            if (btn) {
                btn.addEventListener('click', function() {
                    const wrapper = document.getElementById(`drilldownWrapperPareto${suffix}`);
                    if (wrapper) wrapper.classList.add('hidden');
                    if (drilldownCharts[suffix]) {
                        drilldownCharts[suffix].destroy();
                        drilldownCharts[suffix] = null;
                    }
                    if (suffix === 'Final' && chartDrilldownParetoFinal) {
                        chartDrilldownParetoFinal.destroy();
                        chartDrilldownParetoFinal = null;
                    }
                    if (suffix === 'Pre' && chartDrilldownParetoPre) {
                        chartDrilldownParetoPre.destroy();
                        chartDrilldownParetoPre = null;
                    }
                });
            }
        });

        window.addEventListener('beforeunload', function() {
            if (pollingTimer) clearInterval(pollingTimer);
            if (chartDpmFinal) chartDpmFinal.destroy();
            if (chartParetoFinal) chartParetoFinal.destroy();
            if (chartTop5AreaFinalInstance) chartTop5AreaFinalInstance.destroy();
            if (chartDpmPre) chartDpmPre.destroy();
            if (chartParetoPre) chartParetoPre.destroy();
            if (chartDpmFinalInternal) chartDpmFinalInternal.destroy();
            if (chartParetoFinalInternal) chartParetoFinalInternal.destroy();
            if (chartDpmFinalAssembly) chartDpmFinalAssembly.destroy();
            if (chartParetoFinalAssembly) chartParetoFinalAssembly.destroy();
            if (chartDpmPreOperator) chartDpmPreOperator.destroy();
            if (chartParetoPreOperator) chartParetoPreOperator.destroy();
            if (chartDpmPreInspektor) chartDpmPreInspektor.destroy();
            if (chartParetoPreInspektor) chartParetoPreInspektor.destroy();
            Object.values(drilldownCharts).forEach(c => {
                if (c && typeof c.destroy === 'function') c.destroy();
            });
            if (chartDrilldownParetoFinal) chartDrilldownParetoFinal.destroy();
            if (chartDrilldownParetoPre) chartDrilldownParetoPre.destroy();
            if (abortDpmFinal) abortDpmFinal.abort();
            if (abortParetoFinal) abortParetoFinal.abort();
            if (abortDpmPre) abortDpmPre.abort();
            if (abortParetoPre) abortParetoPre.abort();
        });
    });
    </script>
@endsection
