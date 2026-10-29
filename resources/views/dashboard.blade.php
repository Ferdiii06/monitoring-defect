@extends('layouts.app')

@section('title', 'Monitoring Internal Defect')

@section('content')
    <!-- Header Section -->
    <header class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">Monitoring Internal Defect</h1>
            <p class="text-xs font-medium text-slate-500 mt-1">Analitik real-time performa proses perakitan kabel body (Wiring Harness).</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between xl:justify-end gap-3.5">
            <!-- Mode Switcher Segmented Control (Gabungan, Internal, Rework) -->
            <div class="w-full sm:w-auto" role="region" aria-label="Mode Data Dashboard">
                <div class="inline-flex w-full sm:w-auto items-center p-1 bg-slate-200/70 border border-slate-200 rounded-xl shadow-2xs gap-1" role="tablist" aria-label="Mode Data">
                    <button type="button"
                        id="btnModeAll"
                        data-mode="all"
                        role="tab"
                        aria-pressed="{{ ($currentMode ?? 'internal') === 'all' ? 'true' : 'false' }}"
                        aria-selected="{{ ($currentMode ?? 'internal') === 'all' ? 'true' : 'false' }}"
                        class="btn-dashboard-mode flex-1 sm:flex-initial min-h-[40px] px-3.5 py-2 text-xs rounded-lg transition-all duration-150 inline-flex items-center justify-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 border {{ ($currentMode ?? 'internal') === 'all' ? 'bg-white text-slate-900 shadow-xs border-slate-200/80 font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 border-transparent font-bold' }}">
                        <span class="mode-dot w-2 h-2 rounded-full transition-colors {{ ($currentMode ?? 'internal') === 'all' ? 'bg-indigo-600' : 'bg-slate-300' }}"></span>
                        <span>Gabungan</span>
                    </button>

                    <button type="button"
                        id="btnModeInternal"
                        data-mode="internal"
                        role="tab"
                        aria-pressed="{{ ($currentMode ?? 'internal') === 'internal' ? 'true' : 'false' }}"
                        aria-selected="{{ ($currentMode ?? 'internal') === 'internal' ? 'true' : 'false' }}"
                        class="btn-dashboard-mode flex-1 sm:flex-initial min-h-[40px] px-3.5 py-2 text-xs rounded-lg transition-all duration-150 inline-flex items-center justify-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 border {{ ($currentMode ?? 'internal') === 'internal' ? 'bg-white text-slate-900 shadow-xs border-slate-200/80 font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 border-transparent font-bold' }}">
                        <span class="mode-dot w-2 h-2 rounded-full transition-colors {{ ($currentMode ?? 'internal') === 'internal' ? 'bg-teal-600' : 'bg-slate-300' }}"></span>
                        <span>Internal Defect</span>
                    </button>

                    <button type="button"
                        id="btnModeRework"
                        data-mode="rework"
                        role="tab"
                        aria-pressed="{{ ($currentMode ?? 'internal') === 'rework' ? 'true' : 'false' }}"
                        aria-selected="{{ ($currentMode ?? 'internal') === 'rework' ? 'true' : 'false' }}"
                        class="btn-dashboard-mode flex-1 sm:flex-initial min-h-[40px] px-3.5 py-2 text-xs rounded-lg transition-all duration-150 inline-flex items-center justify-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 border {{ ($currentMode ?? 'internal') === 'rework' ? 'bg-white text-slate-900 shadow-xs border-slate-200/80 font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 border-transparent font-bold' }}">
                        <span class="mode-dot w-2 h-2 rounded-full transition-colors {{ ($currentMode ?? 'internal') === 'rework' ? 'bg-amber-600' : 'bg-slate-300' }}"></span>
                        <span>Rework</span>
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

        <!-- 2 Columns Grid: Final Assy (Left) & Pre Assy (Right) - Opsi A -->
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8" id="analyticsGridContainer">
            <!-- KOLOM KIRI: FINAL ASSY -->
            <div id="columnFinalAssy" class="space-y-6 {{ in_array(($currentMode ?? 'internal'), ['all', 'rework'], true) ? 'xl:col-span-2' : '' }}">
                <!-- Summary Cards Final Assy (Sinkron Filter Mandiri) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Summary DPM Final Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider" id="summaryTitleDpmFinal">{{ ($currentMode ?? 'internal') === 'all' ? 'Total Defect (Gabungan)' : (($currentMode ?? 'internal') === 'rework' ? 'Defect Rework' : 'DPM Final Assy') }}</span>
                            <span class="text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/80" id="summaryPeriodDpmFinal">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmFinal">-</span>
                            <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmFinal">DPM</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmFinal">Kalkulasi total defect / inspect</p>
                    </div>

                    <!-- Summary Top Defect Final Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider" id="summaryTitleParetoFinal">{{ ($currentMode ?? 'internal') === 'all' ? 'Top Defect (Gabungan)' : (($currentMode ?? 'internal') === 'rework' ? 'Top Defect Rework' : 'Top Defect Final') }}</span>
                            <span class="text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/80" id="summaryPeriodParetoFinal">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2 min-w-0">
                            <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate flex-1 min-w-0" id="summaryValParetoFinal">-</span>
                            <span class="text-xs font-bold text-teal-700 font-mono shrink-0" id="summaryCountParetoFinal"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubParetoFinal">Sub defect peringkat 1</p>
                    </div>
                </div>

                <!-- 1. Card: DPM Final Assy -->
                <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.06)] transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600 inline-block shadow-xs"></span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight" id="cardTitleDpmFinal">{{ ($currentMode ?? 'internal') === 'all' ? 'Tren Defect (Gabungan)' : (($currentMode ?? 'internal') === 'rework' ? 'Tren Rework Final Assy' : 'DPM Final Assy') }}</h3>
                                    <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md" id="cardBadgeDpmFinal">{{ ($currentMode ?? 'internal') === 'all' ? 'Gabungan' : 'Final Assy' }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5" id="cardSubDpmFinal">{{ ($currentMode ?? 'internal') === 'all' ? 'Jumlah defect tren per periode' : (($currentMode ?? 'internal') === 'rework' ? 'Jumlah defect & harness dirework per periode' : 'Defect Per Million tren per periode') }}</p>
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
                        <span id="errorMessageDpmFinal">Gagal memuat analitik DPM Final Assy</span>
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

                        <!-- Pengelompokan DPM -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pengelompokan</label>
                            <div class="inline-flex w-full bg-slate-200/70 p-0.5 rounded-lg border border-slate-200" role="group">
                                <button type="button" data-val="day" class="btn-group-by-dpm-final flex-1 py-1 text-xs font-bold rounded-md transition-all text-center bg-teal-600 text-white shadow-2xs">Hari</button>
                                <button type="button" data-val="week" class="btn-group-by-dpm-final flex-1 py-1 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Minggu</button>
                                <button type="button" data-val="month" class="btn-group-by-dpm-final flex-1 py-1 text-xs font-bold rounded-md transition-all text-center text-slate-600 hover:text-slate-900">Bulan</button>
                            </div>
                        </div>

                        <!-- Konveyor + Reset -->
                        <div class="flex items-center space-x-2">
                            <div class="flex-1">
                                <label for="filterConveyorDpmFinal" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Konveyor</label>
                                <div class="relative">
                                    <select id="filterConveyorDpmFinal" class="w-full appearance-none border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 px-2.5 pr-7 py-1.5 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-2xs">
                                        <option value="all">Semua Konveyor</option>
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
                                <button type="button" id="btnResetDpmFinal" title="Reset filter DPM Final Assy ke default" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center space-x-1">
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
                        <p class="text-xs font-semibold text-slate-600">Tidak ada data pada periode ini</p>
                        <span class="text-[11px] text-slate-400 mt-0.5">Ubah rentang tanggal atau pilih filter konveyor lain</span>
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
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight" id="cardTitleParetoFinal">{{ ($currentMode ?? 'internal') === 'all' ? 'Pareto Defect (Gabungan)' : (($currentMode ?? 'internal') === 'rework' ? 'Pareto Sub Defect Rework Final Assy' : 'Pareto Sub Defect Final Assy') }}</h3>
                                    <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md" id="cardBadgeParetoFinal">{{ ($currentMode ?? 'internal') === 'all' ? 'Gabungan' : 'Final Assy' }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5" id="cardSubParetoFinal">{{ ($currentMode ?? 'internal') === 'rework' ? 'Defect rework & persentase kumulatif per sub defect' : 'Total defect & persentase kumulatif per sub defect' }}</p>
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

                    <!-- Konveyor + Reset -->
                    <div class="flex items-center space-x-2">
                        <div class="flex-1">
                            <label for="filterConveyorParetoFinal" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Konveyor</label>
                            <div class="relative">
                                <select id="filterConveyorParetoFinal" class="w-full appearance-none border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 px-2.5 pr-7 py-1.5 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-2xs">
                                    <option value="all">Semua Konveyor</option>
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
                            <button type="button" id="btnResetParetoFinal" title="Reset filter Pareto Final Assy ke default" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center space-x-1">
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
                    <p class="text-xs font-semibold text-slate-600">Tidak ada data pada periode ini</p>
                    <span class="text-[11px] text-slate-400 mt-0.5">Ubah rentang tanggal atau pilih filter konveyor lain</span>
                </div>
            </div>
        </div>

            <!-- KOLOM KANAN: PRE ASSY -->
            <div id="columnPreAssy" class="space-y-6 {{ in_array(($currentMode ?? 'internal'), ['all', 'rework'], true) ? 'hidden' : '' }}">
                <!-- Summary Cards Pre Assy (Sinkron Filter Mandiri) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Summary DPM Pre Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider" id="summaryTitleDpmPre">PPM Pre Assy</span>
                            <span class="text-[10px] font-semibold text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/80" id="summaryPeriodDpmPre">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmPre">-</span>
                            <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmPre">PPM</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmPre">Kalkulasi total defect / inspect</p>
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
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">PPM Pre Assy</h3>
                                    <span class="text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md">Pre Assy</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Parts Per Million tren per periode</p>
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

                        <!-- Pengelompokan DPM -->
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
                            <div class="flex-1">
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
                        <p class="text-xs font-semibold text-slate-600" id="emptyMsgDpmPre">Tidak ada data pada periode ini</p>
                        <span class="text-[11px] text-slate-400 mt-0.5" id="emptySubDpmPre">Ubah rentang tanggal atau pilih filter carline lain</span>
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
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Pareto Sub Defect Pre Assy</h3>
                                    <span class="text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md">Pre Assy</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Total defect & persentase kumulatif per sub defect</p>
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
                        <div class="flex-1">
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
                    <p class="text-xs font-semibold text-slate-600" id="emptyMsgParetoPre">Tidak ada data pada periode ini</p>
                    <span class="text-[11px] text-slate-400 mt-0.5" id="emptySubParetoPre">Ubah rentang tanggal atau pilih filter carline lain</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Analytics & Real-time Polling Script -->
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Dashboard Mode ('all', 'internal', 'rework')
        const validModes = ['all', 'internal', 'rework'];
        const urlMode = new URLSearchParams(window.location.search).get('mode');
        let currentMode = validModes.includes(urlMode)
            ? urlMode
            : (validModes.includes('{{ $currentMode ?? "internal" }}') ? '{{ $currentMode ?? "internal" }}' : 'internal');

        // Theme Colors
        const THEME_COLORS = {
            internal: '#0d9488',
            internalDark: '#0f766e',
            internalLight: 'rgba(13, 148, 136, 0.08)',
            rework: '#d97706',
            reworkDark: '#b45309',
            reworkLight: 'rgba(217, 119, 6, 0.08)',
            target: '#64748b',
            preAssy: '#1d4ed8',
            preAssyDark: '#1e40af',
            preAssyLight: 'rgba(29, 78, 216, 0.08)',
        };

        function updateConveyorFilterUI(mode) {
            const convDpm = document.getElementById('filterConveyorDpmFinal');
            const convPareto = document.getElementById('filterConveyorParetoFinal');
            const noticeDpm = document.getElementById('conveyorNoticeDpmFinal');
            const noticePareto = document.getElementById('conveyorNoticeParetoFinal');

            const warningIcon = `<svg class="w-3.5 h-3.5 text-amber-500 shrink-0 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;

            if (mode === 'rework') {
                filtersDpmFinal.conveyor = 'all';
                filtersParetoFinal.conveyor = 'all';
                if (convDpm) {
                    convDpm.value = 'all';
                    convDpm.disabled = true;
                    convDpm.classList.add('bg-slate-100', 'cursor-not-allowed', 'opacity-75');
                    convDpm.classList.remove('bg-white', 'cursor-pointer');
                }
                if (convPareto) {
                    convPareto.value = 'all';
                    convPareto.disabled = true;
                    convPareto.classList.add('bg-slate-100', 'cursor-not-allowed', 'opacity-75');
                    convPareto.classList.remove('bg-white', 'cursor-pointer');
                }
                const text = `${warningIcon}<span>Baris rework belum punya data konveyor, jadi tidak ikut terfilter</span>`;
                if (noticeDpm) {
                    noticeDpm.innerHTML = text;
                    noticeDpm.classList.remove('hidden');
                }
                if (noticePareto) {
                    noticePareto.innerHTML = text;
                    noticePareto.classList.remove('hidden');
                }
            } else if (mode === 'all') {
                filtersDpmFinal.conveyor = 'all';
                filtersParetoFinal.conveyor = 'all';
                if (convDpm) {
                    convDpm.value = 'all';
                    convDpm.disabled = true;
                    convDpm.classList.add('bg-slate-100', 'cursor-not-allowed', 'opacity-75');
                    convDpm.classList.remove('bg-white', 'cursor-pointer');
                }
                if (convPareto) {
                    convPareto.value = 'all';
                    convPareto.disabled = true;
                    convPareto.classList.add('bg-slate-100', 'cursor-not-allowed', 'opacity-75');
                    convPareto.classList.remove('bg-white', 'cursor-pointer');
                }
                const infoText = `<svg class="w-3.5 h-3.5 text-slate-500 shrink-0 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>Filter konveyor dan carline tersedia di mode Internal Defect</span>`;
                // Catatan: Baris rework belum punya konveyor dan tidak ikut terfilter
                if (noticeDpm) {
                    noticeDpm.innerHTML = infoText;
                    noticeDpm.classList.remove('hidden');
                }
                if (noticePareto) {
                    noticePareto.innerHTML = infoText;
                    noticePareto.classList.remove('hidden');
                }
            } else {
                if (convDpm) {
                    convDpm.disabled = false;
                    convDpm.classList.remove('bg-slate-100', 'cursor-not-allowed', 'opacity-75');
                    convDpm.classList.add('bg-white', 'cursor-pointer');
                }
                if (convPareto) {
                    convPareto.disabled = false;
                    convPareto.classList.remove('bg-slate-100', 'cursor-not-allowed', 'opacity-75');
                    convPareto.classList.add('bg-white', 'cursor-pointer');
                }
                if (noticeDpm) {
                    noticeDpm.innerHTML = '';
                    noticeDpm.classList.add('hidden');
                }
                if (noticePareto) {
                    noticePareto.innerHTML = '';
                    noticePareto.classList.add('hidden');
                }
            }
        }

        function updateModeVisuals(mode) {
            const titleDpmFinal = document.getElementById('cardTitleDpmFinal');
            const subDpmFinal = document.getElementById('cardSubDpmFinal');
            const badgeDpmFinal = document.getElementById('cardBadgeDpmFinal');
            const titleParetoFinal = document.getElementById('cardTitleParetoFinal');
            const subParetoFinal = document.getElementById('cardSubParetoFinal');
            const badgeParetoFinal = document.getElementById('cardBadgeParetoFinal');
            const summaryTitleDpm = document.getElementById('summaryTitleDpmFinal');
            const summaryTitlePareto = document.getElementById('summaryTitleParetoFinal');
            const colPre = document.getElementById('columnPreAssy');
            const colFinal = document.getElementById('columnFinalAssy');

            if (mode === 'all') {
                if (titleDpmFinal) titleDpmFinal.textContent = 'Tren Defect (Gabungan)';
                if (subDpmFinal) subDpmFinal.textContent = 'Jumlah defect tren per periode';
                if (badgeDpmFinal) badgeDpmFinal.textContent = 'Gabungan';
                if (titleParetoFinal) titleParetoFinal.textContent = 'Pareto Defect (Gabungan)';
                if (subParetoFinal) subParetoFinal.textContent = 'Total defect & persentase kumulatif per sub defect';
                if (badgeParetoFinal) badgeParetoFinal.textContent = 'Gabungan';
                if (summaryTitleDpm) summaryTitleDpm.textContent = 'Total Defect (Gabungan)';
                if (summaryTitlePareto) summaryTitlePareto.textContent = 'Top Defect (Gabungan)';
                if (colPre) colPre.classList.add('hidden');
                if (colFinal) colFinal.classList.add('xl:col-span-2');
            } else if (mode === 'rework') {
                if (titleDpmFinal) titleDpmFinal.textContent = 'Tren Rework Final Assy';
                if (subDpmFinal) subDpmFinal.textContent = 'Jumlah defect & harness dirework per periode';
                if (badgeDpmFinal) badgeDpmFinal.textContent = 'Final Assy';
                if (titleParetoFinal) titleParetoFinal.textContent = 'Pareto Sub Defect Rework Final Assy';
                if (subParetoFinal) subParetoFinal.textContent = 'Defect rework & persentase kumulatif per sub defect';
                if (badgeParetoFinal) badgeParetoFinal.textContent = 'Final Assy';
                if (summaryTitleDpm) summaryTitleDpm.textContent = 'Defect Rework';
                if (summaryTitlePareto) summaryTitlePareto.textContent = 'Top Defect Rework';
                if (colPre) colPre.classList.add('hidden');
                if (colFinal) colFinal.classList.add('xl:col-span-2');
            } else {
                if (titleDpmFinal) titleDpmFinal.textContent = 'DPM Final Assy';
                if (subDpmFinal) subDpmFinal.textContent = 'Defect Per Million tren per periode';
                if (badgeDpmFinal) badgeDpmFinal.textContent = 'Final Assy';
                if (titleParetoFinal) titleParetoFinal.textContent = 'Pareto Sub Defect Final Assy';
                if (subParetoFinal) subParetoFinal.textContent = 'Total defect & persentase kumulatif per sub defect';
                if (badgeParetoFinal) badgeParetoFinal.textContent = 'Final Assy';
                if (summaryTitleDpm) summaryTitleDpm.textContent = 'DPM Final Assy';
                if (summaryTitlePareto) summaryTitlePareto.textContent = 'Top Defect Final';
                if (colPre) colPre.classList.remove('hidden');
                if (colFinal) colFinal.classList.remove('xl:col-span-2');
            }

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
                        if (btnMode === 'all') dot.classList.add('bg-indigo-600');
                        else if (btnMode === 'internal') dot.classList.add('bg-teal-600');
                        else if (btnMode === 'rework') dot.classList.add('bg-amber-600');
                    }
                } else {
                    btn.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'border-slate-200/80', 'font-extrabold');
                    btn.classList.add('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-200/50', 'border-transparent', 'font-bold');
                    if (dot) {
                        dot.classList.remove('bg-indigo-600', 'bg-teal-600', 'bg-amber-600');
                        dot.classList.add('bg-slate-300');
                    }
                }
            });
        }

        function setDashboardMode(newMode, shouldPushState = true) {
            if (!validModes.includes(newMode)) return;
            if (newMode === currentMode) return;

            // Reset konveyor saat masuk ke mode rework/all atau kembali dari mode rework/all
            if (newMode === 'rework' || newMode === 'all' || currentMode === 'rework' || currentMode === 'all') {
                filtersDpmFinal.conveyor = 'all';
                filtersParetoFinal.conveyor = 'all';
                const convDpm = document.getElementById('filterConveyorDpmFinal');
                const convPareto = document.getElementById('filterConveyorParetoFinal');
                if (convDpm) convDpm.value = 'all';
                if (convPareto) convPareto.value = 'all';
            }

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
            fetchDpmFinal(false);
            fetchParetoFinal(false);
            if (currentMode === 'internal') {
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
            const modeFromUrl = params.get('mode') || 'internal';
            const targetMode = validModes.includes(modeFromUrl) ? modeFromUrl : 'internal';
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
                                    // Sembunyikan label target dari legend jika dataset hidden
                                    if (legendItem.datasetIndex === 1 && chartData.datasets[1]?.hidden) {
                                        return false;
                                    }
                                    return true;
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
                                    const mode = chart?._mode || 'internal';
                                    const isFinalAssy = (chart?.canvas?.id === 'chartDpmFinal');
                                    const i = context.dataIndex;
                                    const meta = chart?.data?.datasets[0]?._meta ? chart.data.datasets[0]._meta[i] : null;

                                    if (isFinalAssy && mode === 'rework') {
                                        if (context.datasetIndex === 0) {
                                            const defVal = context.raw !== null && context.raw !== undefined ? Number(context.raw).toLocaleString('id-ID') : '0';
                                            return `  Jumlah defect rework: ${defVal} pcs`;
                                        } else if (context.datasetIndex === 1) {
                                            const hVal = context.raw !== null && context.raw !== undefined ? Number(context.raw).toLocaleString('id-ID') : '0';
                                            return `  Jumlah harness dirework: ${hVal} pcs`;
                                        }
                                        return null;
                                    }

                                    if (isFinalAssy && mode === 'all') {
                                        if (context.datasetIndex === 0) {
                                            const intVal = context.raw !== null && context.raw !== undefined ? Number(context.raw).toLocaleString('id-ID') : '0';
                                            return `  Internal Defect: ${intVal} pcs`;
                                        } else if (context.datasetIndex === 1) {
                                            const rewVal = context.raw !== null && context.raw !== undefined ? Number(context.raw).toLocaleString('id-ID') : '0';
                                            return `  Rework: ${rewVal} pcs`;
                                        }
                                        return null;
                                    }

                                    const hasDataset0 = chart.tooltip?.dataPoints?.some(dp => dp.datasetIndex === 0);
                                    if (hasDataset0 && context.datasetIndex !== 0) {
                                        return null;
                                    }

                                    const dpmVal = meta ? meta.dpm : (context.datasetIndex === 0 ? context.raw : null);
                                    const targetVal = (meta && meta.target !== undefined && meta.target !== null)
                                        ? meta.target
                                        : (chart.data.datasets[1]?.data && chart.data.datasets[1].data[i] !== undefined && chart.data.datasets[1].data[i] !== null
                                            ? chart.data.datasets[1].data[i]
                                            : (context.datasetIndex === 1 ? context.raw : null));

                                    const dpmStr = (dpmVal !== null && dpmVal !== undefined)
                                        ? Number(dpmVal).toLocaleString('id-ID')
                                        : '-';
                                    const targetStr = (targetVal !== null && targetVal !== undefined && !isNaN(Number(targetVal)))
                                        ? Number(targetVal).toLocaleString('id-ID')
                                        : null;
                                    const defectStr = meta && meta.total_defect !== undefined
                                        ? Number(meta.total_defect).toLocaleString('id-ID')
                                        : '0';
                                    const inspectStr = meta && meta.total_inspect !== undefined
                                        ? Number(meta.total_inspect).toLocaleString('id-ID')
                                        : '0';

                                    const currentMetric = chart?._metricLabel || metricLabel || 'DPM';
                                    const lines = [
                                        `  ${currentMetric} Real: ${dpmStr}`
                                    ];
                                    if (targetStr !== null) {
                                        lines.push(`  Target ${currentMetric}: ${targetStr}`);
                                        if (dpmVal !== null && Number(dpmVal) > Number(targetVal)) {
                                            lines.push(`  ⚠️ Status: Melebihi Target`);
                                        }
                                    }
                                    lines.push(`  Total Defect: ${defectStr} pcs`);
                                    lines.push(`  Total Inspect: ${inspectStr} pcs`);
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
                                    const labels = item.chart?.data?.labels;
                                    return (labels && labels[item.dataIndex]) ? labels[item.dataIndex] : (item.label || '');
                                },
                                label: function(context) {
                                    const val = context.raw || 0;
                                    if (context.dataset.yAxisID === 'y1') {
                                        return `  Kumulatif: ${Number(val).toFixed(1)}%`;
                                    }
                                    const chart = context.chart;
                                    const mode = chart?._mode || 'internal';
                                    const isFinalAssy = (chart?.canvas?.id === 'chartParetoFinal');

                                    if (isFinalAssy && mode === 'all') {
                                        if (context.datasetIndex === 1) {
                                            return `  Internal Defect: ${Number(val).toLocaleString('id-ID')} pcs`;
                                        }
                                        if (context.datasetIndex === 2) {
                                            return `  Rework: ${Number(val).toLocaleString('id-ID')} pcs`;
                                        }
                                    } else if (isFinalAssy && mode === 'rework') {
                                        return `  Defect Rework: ${Number(val).toLocaleString('id-ID')} pcs`;
                                    }

                                    return `  Total Defect: ${Number(val).toLocaleString('id-ID')} pcs`;
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
                                    const label = this.getLabelForValue(val);
                                    if (typeof label === 'string' && label.length > 13) {
                                        return label.substring(0, 13) + '…';
                                    }
                                    return label;
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
            const subMsgEl = emptyEl ? emptyEl.querySelector('span') : null;
            const noteEl = document.getElementById(isFinal ? 'noteDpmFinal' : 'noteDpmPre');

            // 1. Khusus Pre Assy pada mode rework: sembunyikan canvas, tampilkan pesan rework tidak tersedia
            if (isPre && mode === 'rework') {
                if (msgEl) msgEl.textContent = 'Rework tidak tersedia untuk Pre Assy';
                if (subMsgEl) subMsgEl.textContent = 'Proses rework hanya terdapat pada Final Assy';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (noteEl) {
                    noteEl.innerHTML = '';
                    noteEl.classList.add('hidden');
                }
                return;
            }

            // Kembalikan subteks default jika sebelumnya dari mode rework
            if (isPre && subMsgEl && subMsgEl.textContent === 'Proses rework hanya terdapat pada Final Assy') {
                subMsgEl.textContent = 'Ubah rentang tanggal atau pilih filter carline lain';
            }

            const trendList = Array.isArray(trends) ? trends : [];
            const missingInspectCount = trendList.filter(t => (Number(t.total_defect) || 0) > 0 && (!t.total_inspect || Number(t.total_inspect) === 0)).length;
            const validDpmCount = trendList.filter(t => t.dpm !== null && t.dpm !== undefined && !isNaN(Number(t.dpm))).length;

            if (trendList.length === 0) {
                if (msgEl) msgEl.textContent = 'Tidak ada data pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (noteEl) {
                    noteEl.innerHTML = '';
                    noteEl.classList.add('hidden');
                }
                return;
            }

            // Mode internal atau Pre Assy: jika semua DPM null dan tidak ada target, tampilkan pesan belum ada quantity inspect
            if (mode === 'internal' || isPre) {
                const hasAnyTarget = trendList.some(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));
                const allDpmNull = trendList.every(t => t.dpm === null || t.dpm === undefined);
                if (allDpmNull && !hasAnyTarget) {
                    if (msgEl) msgEl.textContent = 'Belum ada Quantity Inspect pada periode ini';
                    if (wrapper) wrapper.classList.add('hidden');
                    if (emptyEl) emptyEl.classList.remove('hidden');
                    if (noteEl) {
                        noteEl.innerHTML = '';
                        noteEl.classList.add('hidden');
                    }
                    return;
                }
            }

            if (msgEl) msgEl.textContent = 'Tidak ada data pada periode ini';
            if (wrapper) wrapper.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            // Render catatan teks kondisi data
            if (noteEl) {
                if (isFinal && mode === 'rework') {
                    noteEl.innerHTML = `
                        <div class="flex items-center space-x-1.5 text-[11px] font-medium text-slate-500">
                            <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>DPM rework menyusul</span>
                        </div>
                    `;
                    noteEl.classList.remove('hidden');
                } else if (isFinal && mode === 'all') {
                    noteEl.innerHTML = `
                        <div class="flex items-center space-x-1.5 text-[11px] font-medium text-slate-500">
                            <svg class="w-3.5 h-3.5 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>DPM gabungan menyusul</span>
                        </div>
                    `;
                    noteEl.classList.remove('hidden');
                } else {
                    const notes = [];
                    const mLabel = chart?._metricLabel || 'DPM';
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
                    } else {
                        noteEl.innerHTML = '';
                        noteEl.classList.add('hidden');
                    }
                }
            }

            if (chart) {
                chart._groupBy = groupBy;
                chart._mode = mode;
                chart.data.labels = trendList.map(t => t.label);

                // Pastikan chart memiliki 2 dataset awal
                while (chart.data.datasets.length < 2) {
                    chart.data.datasets.push({});
                }
                if (chart.data.datasets.length > 2) {
                    chart.data.datasets = chart.data.datasets.slice(0, 2);
                }

                if (isFinal && mode === 'rework') {
                    // Sumbu Y = "Jumlah"
                    chart.options.scales.y.title.text = 'Jumlah';

                    // Seri 1: Jumlah defect rework
                    chart.data.datasets[0].label = 'Jumlah defect rework';
                    chart.data.datasets[0].data = trendList.map(t => (t.total_defect_rework !== undefined ? t.total_defect_rework : t.total_defect) || 0);
                    chart.data.datasets[0].borderColor = THEME_COLORS.rework;
                    chart.data.datasets[0].backgroundColor = THEME_COLORS.reworkLight;
                    chart.data.datasets[0].borderWidth = 2.5;
                    chart.data.datasets[0].borderDash = [];
                    chart.data.datasets[0].fill = true;
                    chart.data.datasets[0].tension = 0.3;
                    chart.data.datasets[0].spanGaps = true;
                    chart.data.datasets[0].pointStyle = 'circle';
                    chart.data.datasets[0].pointBackgroundColor = THEME_COLORS.rework;
                    chart.data.datasets[0].pointBorderColor = '#ffffff';
                    chart.data.datasets[0].pointBorderWidth = 2;
                    chart.data.datasets[0].pointRadius = 4;
                    chart.data.datasets[0].pointHoverRadius = 6;
                    chart.data.datasets[0]._meta = trendList;
                    chart.data.datasets[0].hidden = false;

                    // Seri 2: Jumlah harness dirework
                    chart.data.datasets[1].label = 'Jumlah harness dirework';
                    chart.data.datasets[1].data = trendList.map(t => t.total_harness_rework || 0);
                    chart.data.datasets[1].borderColor = THEME_COLORS.reworkDark;
                    chart.data.datasets[1].backgroundColor = 'transparent';
                    chart.data.datasets[1].borderWidth = 2;
                    chart.data.datasets[1].borderDash = [4, 4];
                    chart.data.datasets[1].fill = false;
                    chart.data.datasets[1].tension = 0.3;
                    chart.data.datasets[1].spanGaps = true;
                    chart.data.datasets[1].pointStyle = 'circle';
                    chart.data.datasets[1].pointBackgroundColor = THEME_COLORS.reworkDark;
                    chart.data.datasets[1].pointBorderColor = '#ffffff';
                    chart.data.datasets[1].pointBorderWidth = 2;
                    chart.data.datasets[1].pointRadius = 4;
                    chart.data.datasets[1].pointHoverRadius = 6;
                    chart.data.datasets[1].hidden = false;

                } else if (isFinal && mode === 'all') {
                    // Sumbu Y = "Jumlah defect"
                    chart.options.scales.y.title.text = 'Jumlah defect';

                    // Seri 1: Internal Defect
                    chart.data.datasets[0].label = 'Internal Defect';
                    chart.data.datasets[0].data = trendList.map(t => (t.total_defect_internal !== undefined ? t.total_defect_internal : t.total_defect) || 0);
                    chart.data.datasets[0].borderColor = THEME_COLORS.internal;
                    chart.data.datasets[0].backgroundColor = THEME_COLORS.internalLight;
                    chart.data.datasets[0].borderWidth = 2.5;
                    chart.data.datasets[0].borderDash = [];
                    chart.data.datasets[0].fill = true;
                    chart.data.datasets[0].tension = 0.3;
                    chart.data.datasets[0].spanGaps = true;
                    chart.data.datasets[0].pointStyle = 'circle';
                    chart.data.datasets[0].pointBackgroundColor = THEME_COLORS.internal;
                    chart.data.datasets[0].pointBorderColor = '#ffffff';
                    chart.data.datasets[0].pointBorderWidth = 2;
                    chart.data.datasets[0].pointRadius = 4;
                    chart.data.datasets[0].pointHoverRadius = 6;
                    chart.data.datasets[0]._meta = trendList;
                    chart.data.datasets[0].hidden = false;

                    // Seri 2: Rework
                    chart.data.datasets[1].label = 'Rework';
                    chart.data.datasets[1].data = trendList.map(t => t.total_defect_rework || 0);
                    chart.data.datasets[1].borderColor = THEME_COLORS.rework;
                    chart.data.datasets[1].backgroundColor = THEME_COLORS.reworkLight;
                    chart.data.datasets[1].borderWidth = 2.5;
                    chart.data.datasets[1].borderDash = [];
                    chart.data.datasets[1].fill = false;
                    chart.data.datasets[1].tension = 0.3;
                    chart.data.datasets[1].spanGaps = true;
                    chart.data.datasets[1].pointStyle = 'circle';
                    chart.data.datasets[1].pointBackgroundColor = THEME_COLORS.rework;
                    chart.data.datasets[1].pointBorderColor = '#ffffff';
                    chart.data.datasets[1].pointBorderWidth = 2;
                    chart.data.datasets[1].pointRadius = 4;
                    chart.data.datasets[1].pointHoverRadius = 6;
                    chart.data.datasets[1].hidden = false;

                } else {
                    // Mode internal atau Pre Assy: 100% pulih ke perilaku legacy DPM / PPM
                    const mLabel = isFinal ? 'DPM' : 'PPM';
                    const baseColor = isFinal ? THEME_COLORS.internalDark : THEME_COLORS.preAssy;
                    const bgLight = isFinal ? 'rgba(15, 118, 110, 0.08)' : THEME_COLORS.preAssyLight;
                    const targetColor = THEME_COLORS.target;

                    chart.options.scales.y.title.text = mLabel;

                    // Dataset 0: DPM Real
                    chart.data.datasets[0].label = `${mLabel} Real`;
                    chart.data.datasets[0].data = trendList.map(t => t.dpm);
                    chart.data.datasets[0].borderColor = baseColor;
                    chart.data.datasets[0].backgroundColor = bgLight;
                    chart.data.datasets[0].borderWidth = 2.5;
                    chart.data.datasets[0].borderDash = [];
                    chart.data.datasets[0].fill = true;
                    chart.data.datasets[0].tension = 0.3;
                    chart.data.datasets[0].spanGaps = true;
                    chart.data.datasets[0].pointStyle = 'circle';
                    chart.data.datasets[0]._meta = trendList;
                    chart.data.datasets[0].hidden = false;

                    // Dataset 1: Target DPM
                    chart.data.datasets[1].label = `Target ${mLabel}`;
                    chart.data.datasets[1].borderColor = targetColor;
                    chart.data.datasets[1].backgroundColor = 'transparent';
                    chart.data.datasets[1].borderWidth = 2;
                    chart.data.datasets[1].borderDash = [6, 4];
                    chart.data.datasets[1].fill = false;
                    chart.data.datasets[1].tension = 0;
                    chart.data.datasets[1].spanGaps = false;

                    const hasAnyTarget = trendList.some(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));
                    const validTargetPoints = trendList.filter(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));
                    const validTargetCount = validTargetPoints.length;

                    if (hasAnyTarget) {
                        chart.data.datasets[1].data = trendList.map(t => (t.target !== null && t.target !== undefined ? Number(t.target) : null));
                        chart.data.datasets[1].hidden = false;

                        if (validTargetCount === 1) {
                            chart.data.datasets[1].pointStyle = 'line';
                            chart.data.datasets[1].pointRadius = trendList.map(t => (t.target !== null && t.target !== undefined && !isNaN(Number(t.target)) ? 20 : 0));
                            chart.data.datasets[1].pointHoverRadius = trendList.map(t => (t.target !== null && t.target !== undefined && !isNaN(Number(t.target)) ? 22 : 0));
                            chart.data.datasets[1].pointBorderColor = targetColor;
                            chart.data.datasets[1].pointBorderWidth = 3;
                            chart.data.datasets[1].pointBackgroundColor = targetColor;
                        } else {
                            chart.data.datasets[1].pointStyle = 'circle';
                            chart.data.datasets[1].pointRadius = 0;
                            chart.data.datasets[1].pointHoverRadius = 0;
                            chart.data.datasets[1].pointBorderWidth = 0;
                        }

                        chart.data.datasets[0].pointBackgroundColor = trendList.map(t => {
                            const hasTarget = t.target !== null && t.target !== undefined;
                            const hasDpm = t.dpm !== null && t.dpm !== undefined;
                            if (hasTarget && hasDpm && Number(t.dpm) > Number(t.target)) {
                                return '#dc2626';
                            }
                            return baseColor;
                        });

                        chart.data.datasets[0].pointBorderColor = trendList.map(t => {
                            const hasTarget = t.target !== null && t.target !== undefined;
                            const hasDpm = t.dpm !== null && t.dpm !== undefined;
                            if (hasTarget && hasDpm && Number(t.dpm) > Number(t.target)) {
                                return '#991b1b';
                            }
                            return '#ffffff';
                        });

                        chart.data.datasets[0].pointRadius = trendList.map(t => {
                            const hasDpm = t.dpm !== null && t.dpm !== undefined;
                            if (!hasDpm) return 0;
                            const hasTarget = t.target !== null && t.target !== undefined;
                            if (hasTarget && Number(t.dpm) > Number(t.target)) {
                                return validDpmCount === 1 ? 8 : 5;
                            }
                            return validDpmCount === 1 ? 7 : 4;
                        });
                        chart.data.datasets[0].pointHoverRadius = trendList.map(t => {
                            const hasDpm = t.dpm !== null && t.dpm !== undefined;
                            if (!hasDpm) return 0;
                            return validDpmCount === 1 ? 9 : 6;
                        });
                    } else {
                        chart.data.datasets[1].data = [];
                        chart.data.datasets[1].hidden = true;
                        chart.data.datasets[1].pointStyle = 'circle';
                        chart.data.datasets[1].pointRadius = 0;
                        chart.data.datasets[1].pointHoverRadius = 0;
                        chart.data.datasets[1].pointBorderWidth = 0;
                        chart.data.datasets[0].pointBackgroundColor = baseColor;
                        chart.data.datasets[0].pointBorderColor = '#ffffff';
                        chart.data.datasets[0].pointRadius = trendList.map(t => {
                            const hasDpm = t.dpm !== null && t.dpm !== undefined;
                            if (!hasDpm) return 0;
                            return validDpmCount === 1 ? 7 : 4;
                        });
                        chart.data.datasets[0].pointHoverRadius = trendList.map(t => {
                            const hasDpm = t.dpm !== null && t.dpm !== undefined;
                            if (!hasDpm) return 0;
                            return validDpmCount === 1 ? 9 : 6;
                        });
                    }
                }

                chart.update('none');
            }
        }

        function updateParetoData(chart, wrapperId, emptyId, pareto, mode = currentMode) {
            const isFinal = (wrapperId === 'wrapperParetoFinal');
            const isPre = (wrapperId === 'wrapperParetoPre');
            const wrapper = document.getElementById(wrapperId);
            const emptyEl = document.getElementById(emptyId);
            const msgEl = emptyEl ? emptyEl.querySelector('p') : null;
            const subMsgEl = emptyEl ? emptyEl.querySelector('span') : null;

            // 1. Khusus Pre Assy pada mode rework: sembunyikan canvas, tampilkan pesan rework tidak tersedia
            if (isPre && mode === 'rework') {
                if (msgEl) msgEl.textContent = 'Rework tidak tersedia untuk Pre Assy';
                if (subMsgEl) subMsgEl.textContent = 'Proses rework hanya terdapat pada Final Assy';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            // Kembalikan subteks default jika sebelumnya dari mode rework
            if (isPre && subMsgEl && subMsgEl.textContent === 'Proses rework hanya terdapat pada Final Assy') {
                subMsgEl.textContent = 'Ubah rentang tanggal atau pilih filter carline lain';
            }

            const paretoList = Array.isArray(pareto) ? pareto : [];
            const hasData = paretoList.length > 0 && paretoList.some(p => (p.total || 0) > 0);

            if (!hasData) {
                if (msgEl) msgEl.textContent = 'Tidak ada data pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            if (wrapper) wrapper.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            if (chart) {
                chart._mode = mode;
                chart.data.labels = paretoList.map(p => p.name);

                if (isFinal && mode === 'all') {
                    // Stacked bar: Internal Defect (Teal) + Rework (Amber)
                    chart.options.scales.x.stacked = true;
                    chart.options.scales.y.stacked = true;

                    // Dataset 0: Kumulatif %
                    chart.data.datasets[0] = {
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
                    };

                    // Dataset 1: Internal Defect (Teal)
                    chart.data.datasets[1] = {
                        type: 'bar',
                        label: 'Internal Defect',
                        data: paretoList.map(p => p.internal_qty || 0),
                        backgroundColor: THEME_COLORS.internal,
                        borderRadius: 0,
                        maxBarThickness: 44,
                        yAxisID: 'y',
                        order: 2,
                        stack: 'defect'
                    };

                    // Dataset 2: Rework (Amber)
                    chart.data.datasets[2] = {
                        type: 'bar',
                        label: 'Rework',
                        data: paretoList.map(p => p.rework_qty || 0),
                        backgroundColor: THEME_COLORS.rework,
                        borderRadius: 4,
                        maxBarThickness: 44,
                        yAxisID: 'y',
                        order: 3,
                        stack: 'defect'
                    };

                } else if (isFinal && mode === 'rework') {
                    // Pareto Rework Final: Bar Amber + Kumulatif Line
                    chart.options.scales.x.stacked = false;
                    chart.options.scales.y.stacked = false;
                    if (chart.data.datasets.length > 2) {
                        chart.data.datasets = chart.data.datasets.slice(0, 2);
                    }

                    // Dataset 0: Kumulatif %
                    chart.data.datasets[0] = {
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
                    };

                    // Dataset 1: Defect Rework
                    chart.data.datasets[1] = {
                        type: 'bar',
                        label: 'Defect Rework',
                        data: paretoList.map(p => (p.rework_qty !== undefined ? p.rework_qty : p.total)),
                        backgroundColor: THEME_COLORS.rework,
                        borderRadius: 4,
                        maxBarThickness: 44,
                        yAxisID: 'y',
                        order: 2
                    };

                } else {
                    // Mode internal atau Pre Assy: 100% pulih ke legacy pareto
                    chart.options.scales.x.stacked = false;
                    chart.options.scales.y.stacked = false;
                    if (chart.data.datasets.length > 2) {
                        chart.data.datasets = chart.data.datasets.slice(0, 2);
                    }

                    const barColor = isFinal ? THEME_COLORS.internalDark : THEME_COLORS.preAssyDark;

                    // Dataset 0: Kumulatif %
                    chart.data.datasets[0] = {
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
                    };

                    // Dataset 1: Total Defect
                    chart.data.datasets[1] = {
                        type: 'bar',
                        label: 'Total Defect',
                        data: paretoList.map(p => p.total),
                        backgroundColor: barColor,
                        borderRadius: 4,
                        maxBarThickness: 44,
                        yAxisID: 'y',
                        order: 2
                    };
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

            if (!isFinal && mode === 'rework') {
                if (titleEl) titleEl.textContent = 'PPM Pre Assy';
                if (valEl) valEl.textContent = '-';
                if (unitEl) unitEl.textContent = 'PPM';
                if (subEl) subEl.textContent = 'Rework tidak tersedia untuk Pre Assy';
                return;
            }

            const list = Array.isArray(trends) ? trends : [];

            if (isFinal && mode === 'rework') {
                if (titleEl) titleEl.textContent = 'Defect Rework';
                const totalDefectRework = list.reduce((sum, t) => sum + (Number(t.total_defect_rework !== undefined ? t.total_defect_rework : t.total_defect) || 0), 0);
                const totalHarnessRework = list.reduce((sum, t) => sum + (Number(t.total_harness_rework) || 0), 0);

                if (valEl) valEl.textContent = totalDefectRework.toLocaleString('id-ID');
                if (unitEl) unitEl.textContent = 'pcs';
                if (subEl) {
                    subEl.innerHTML = `${totalHarnessRework.toLocaleString('id-ID')} harness dirework &bull; <span class="text-amber-600 font-medium">DPM rework menyusul</span>`;
                }
                return;
            }

            if (isFinal && mode === 'all') {
                if (titleEl) titleEl.textContent = 'Total Defect (Gabungan)';
                const intDef = list.reduce((sum, t) => sum + (Number(t.total_defect_internal !== undefined ? t.total_defect_internal : t.total_defect) || 0), 0);
                const rewDef = list.reduce((sum, t) => sum + (Number(t.total_defect_rework) || 0), 0);
                const combinedDef = intDef + rewDef;

                if (valEl) valEl.textContent = combinedDef.toLocaleString('id-ID');
                if (unitEl) unitEl.textContent = 'pcs';
                if (subEl) {
                    subEl.innerHTML = `Internal: ${intDef.toLocaleString('id-ID')} &bull; Rework: ${rewDef.toLocaleString('id-ID')} &bull; <span class="text-teal-600 font-medium">DPM gabungan menyusul</span>`;
                }
                return;
            }

            // Mode internal atau Pre Assy: perhitungan DPM/PPM legacy
            const hasQuantity = list.length > 0 && list.some(item => item.total_inspect !== undefined && item.total_defect !== undefined);

            if (hasQuantity) {
                if (titleEl) titleEl.textContent = isFinal ? 'DPM Final Assy' : 'PPM Pre Assy';

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
                    if (unitEl) unitEl.textContent = isFinal ? 'DPM' : 'PPM';

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
                    if (unitEl) unitEl.textContent = isFinal ? 'DPM' : 'PPM';
                    if (subEl) {
                        if (excludedDefect > 0) {
                            subEl.innerHTML = `<span class="text-amber-600 font-medium">0 inspect valid &bull; ${excludedDefect.toLocaleString('id-ID')} defect belum punya data inspect</span>`;
                        } else {
                            subEl.textContent = '0 inspect pada periode ini';
                        }
                    }
                }
            } else {
                if (titleEl) titleEl.textContent = isFinal ? 'DPM Terakhir' : 'PPM Terakhir';

                const lastValid = [...list].reverse().find(item => item.dpm !== null && item.dpm !== undefined && !isNaN(Number(item.dpm)));
                if (lastValid && valEl) {
                    valEl.textContent = Math.round(Number(lastValid.dpm)).toLocaleString('id-ID');
                    if (unitEl) unitEl.textContent = isFinal ? 'DPM' : 'PPM';
                    if (subEl) subEl.textContent = `Titik terakhir (${lastValid.label || 'data'})`;
                } else {
                    if (valEl) valEl.textContent = '-';
                    if (unitEl) unitEl.textContent = isFinal ? 'DPM' : 'PPM';
                    if (subEl) subEl.textContent = 'Data tidak tersedia';
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

            if (!isFinal && mode === 'rework') {
                if (valEl) {
                    valEl.textContent = '-';
                    valEl.title = '';
                }
                if (countEl) countEl.textContent = '';
                if (subEl) subEl.textContent = 'Rework tidak tersedia untuk Pre Assy';
                return;
            }

            const list = Array.isArray(pareto) ? pareto : [];
            const topItem = list.length > 0 && (list[0].total > 0) ? list[0] : null;

            if (topItem) {
                if (valEl) {
                    valEl.textContent = topItem.name;
                    valEl.title = topItem.name;
                }
                if (countEl) {
                    const displayQty = (isFinal && mode === 'rework' && topItem.rework_qty !== undefined)
                        ? topItem.rework_qty
                        : topItem.total;
                    countEl.textContent = `(${Number(displayQty).toLocaleString('id-ID')} pcs)`;
                }
                if (subEl) {
                    const cum = topItem.cumulative_percent !== undefined ? `${Number(topItem.cumulative_percent).toFixed(1)}%` : '';
                    if (isFinal && mode === 'all') {
                        subEl.textContent = `Peringkat 1 total (Internal: ${(topItem.internal_qty || 0).toLocaleString('id-ID')}, Rework: ${(topItem.rework_qty || 0).toLocaleString('id-ID')})`;
                    } else if (isFinal && mode === 'rework') {
                        subEl.textContent = cum ? `Peringkat 1 rework (${cum} kumulatif)` : 'Peringkat 1 defect rework';
                    } else {
                        subEl.textContent = cum ? `Peringkat 1 (${cum} kumulatif)` : 'Peringkat 1 terbanyak';
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
            if (currentMode !== 'rework' && filtersDpmFinal.conveyor && filtersDpmFinal.conveyor !== 'all' && currentMode !== 'all') params.append('conveyor', filtersDpmFinal.conveyor);
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
                updateDpmData(chartDpmFinal, 'wrapperDpmFinal', 'emptyDpmFinal', data, filtersDpmFinal.group_by, currentMode);
                updateDpmSummaryCard('final', data, filtersDpmFinal, currentMode);
                setLoadingBadge('loadingDpmFinal', 'success');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (reqMode !== currentMode) return;
                console.error('[Analytics DPM Final] Error fetch:', err);
                setLoadingBadge('loadingDpmFinal', 'error');
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data DPM Final Assy, mencoba lagi pada polling berikutnya.';
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
            }
            abortParetoFinal = new AbortController();
            isFetchingParetoFinal = true;

            const errorEl = document.getElementById('errorParetoFinal');
            const errorMsgEl = document.getElementById('errorMessageParetoFinal');

            const params = new URLSearchParams();
            params.append('assy', 'final');
            if (filtersParetoFinal.start_date) params.append('start_date', filtersParetoFinal.start_date);
            if (filtersParetoFinal.end_date) params.append('end_date', filtersParetoFinal.end_date);
            if (currentMode !== 'rework' && filtersParetoFinal.conveyor && filtersParetoFinal.conveyor !== 'all' && currentMode !== 'all') params.append('conveyor', filtersParetoFinal.conveyor);
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
                const data = res.pareto_final || [];
                updateParetoData(chartParetoFinal, 'wrapperParetoFinal', 'emptyParetoFinal', data, currentMode);
                updateParetoSummaryCard('final', data, filtersParetoFinal, currentMode);
                setLoadingBadge('loadingParetoFinal', 'success');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (reqMode !== currentMode) return;
                console.error('[Analytics Pareto Final] Error fetch:', err);
                setLoadingBadge('loadingParetoFinal', 'error');
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data Pareto Final Assy, mencoba lagi pada polling berikutnya.';
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
            if (filtersDpmPre.carline_id && filtersDpmPre.carline_id !== 'all') params.append('carline_id', filtersDpmPre.carline_id);
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
                updateDpmData(chartDpmPre, 'wrapperDpmPre', 'emptyDpmPre', data, filtersDpmPre.group_by, currentMode);
                updateDpmSummaryCard('pre', data, filtersDpmPre, currentMode);
                setLoadingBadge('loadingDpmPre', 'success');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (reqMode !== currentMode) return;
                console.error('[Analytics DPM Pre] Error fetch:', err);
                setLoadingBadge('loadingDpmPre', 'error');
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
            }
            abortParetoPre = new AbortController();
            isFetchingParetoPre = true;

            const errorEl = document.getElementById('errorParetoPre');
            const errorMsgEl = document.getElementById('errorMessageParetoPre');

            const params = new URLSearchParams();
            params.append('assy', 'pre');
            if (filtersParetoPre.start_date) params.append('start_date', filtersParetoPre.start_date);
            if (filtersParetoPre.end_date) params.append('end_date', filtersParetoPre.end_date);
            if (filtersParetoPre.carline_id && filtersParetoPre.carline_id !== 'all') params.append('carline_id', filtersParetoPre.carline_id);
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
                const data = res.pareto_pre || [];
                updateParetoData(chartParetoPre, 'wrapperParetoPre', 'emptyParetoPre', data, currentMode);
                updateParetoSummaryCard('pre', data, filtersParetoPre, currentMode);
                setLoadingBadge('loadingParetoPre', 'success');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (reqMode !== currentMode) return;
                console.error('[Analytics Pareto Pre] Error fetch:', err);
                setLoadingBadge('loadingParetoPre', 'error');
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
                    if (totalEl && stats.totalDefect !== undefined) {
                        const newTxt = stats.totalDefect.toLocaleString('id-ID');
                        if (totalEl.innerText !== newTxt) {
                            totalEl.innerText = newTxt;
                            flashElement(totalEl);
                        }
                    }

                    const todayEl = document.getElementById('stat-defect-today');
                    if (todayEl && stats.defectToday !== undefined) {
                        const newTxt = stats.defectToday.toLocaleString('id-ID');
                        if (todayEl.innerText !== newTxt) {
                            todayEl.innerText = newTxt;
                            flashElement(todayEl);
                        }
                    }

                    const activeEl = document.getElementById('stat-active-users');
                    if (activeEl && stats.activeUsers !== undefined) {
                        const newTxt = String(stats.activeUsers);
                        if (activeEl.innerText !== newTxt) {
                            activeEl.innerText = newTxt;
                            flashElement(activeEl);
                        }
                    }

                    const totalUsersEl = document.getElementById('stat-total-users');
                    if (totalUsersEl && stats.totalUsers !== undefined) {
                        const newTxt = String(stats.totalUsers);
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
            fetchDpmFinal(true);
            fetchParetoFinal(true);
            if (currentMode === 'internal') {
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

        // Inisialisasi visual mode awal (judul kartu, filter konveyor, dll.)
        updateModeVisuals(currentMode);

        // Initial Load
        fetchStats();
        fetchDpmFinal();
        fetchParetoFinal();
        if (currentMode === 'internal') {
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

        window.addEventListener('beforeunload', function() {
            if (pollingTimer) clearInterval(pollingTimer);
            if (chartDpmFinal) chartDpmFinal.destroy();
            if (chartParetoFinal) chartParetoFinal.destroy();
            if (chartDpmPre) chartDpmPre.destroy();
            if (chartParetoPre) chartParetoPre.destroy();
            if (abortDpmFinal) abortDpmFinal.abort();
            if (abortParetoFinal) abortParetoFinal.abort();
            if (abortDpmPre) abortDpmPre.abort();
            if (abortParetoPre) abortParetoPre.abort();
        });
    });
    </script>
@endsection
