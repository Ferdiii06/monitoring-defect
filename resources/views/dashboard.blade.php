@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Header Section -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold tracking-wider bg-red-100 text-brand uppercase">PT JAI</span>
                <span class="text-xs font-semibold text-slate-500">Quality Assurance Division</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight mt-1">Monitoring Defect & Kualitas QA</h1>
            <p class="text-xs font-medium text-slate-500 mt-0.5">Analitik real-time performa proses perakitan kabel body (Wiring Harness).</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Live Clock Status Container -->
            <div id="statusContainer" class="flex items-center space-x-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200/90 px-3.5 py-2 rounded-xl shadow-2xs">
                <span id="statusIndicatorDot" class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span id="statusLabel" class="text-slate-500">Update:</span>
                <span id="lastUpdatedClock" class="font-mono font-bold text-slate-800">Menghubungkan...</span>
            </div>

            <!-- Add Account Button -->
            <a href="{{ route('account.create') }}" class="bg-brand hover:bg-brand-active text-white text-xs font-bold py-2.5 px-4 rounded-xl transition duration-150 shadow-xs shadow-brand/20 flex items-center space-x-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Add Account</span>
            </a>

            <!-- Admin Profile Card -->
            <div class="flex items-center space-x-3 border-l border-slate-200 pl-3">
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
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            <!-- KOLOM KIRI: FINAL ASSY -->
            <div class="space-y-6">
                <!-- Summary Cards Final Assy (Sinkron Filter Mandiri) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Summary DPM Final Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider" id="summaryTitleDpmFinal">DPM Final Assy</span>
                            <span class="text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/80" id="summaryPeriodDpmFinal">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <span class="text-2xl font-black text-slate-900 font-mono tracking-tight tabular-nums" id="summaryValDpmFinal">-</span>
                            <span class="text-[11px] text-slate-400 font-semibold" id="summaryUnitDpmFinal">PPM</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 truncate" id="summarySubDpmFinal">Kalkulasi total defect / inspect</p>
                    </div>

                    <!-- Summary Top Defect Final Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Top Defect Final</span>
                            <span class="text-[10px] font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/80" id="summaryPeriodParetoFinal">Memuat...</span>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate max-w-[170px]" id="summaryValParetoFinal">-</span>
                            <span class="text-xs font-bold text-teal-700 font-mono" id="summaryCountParetoFinal"></span>
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
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">DPM Final Assy</h3>
                                    <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md">Final Assy</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Defect Per Million tren per periode</p>
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
                        <div>
                            <label for="filterDateRangeDpmFinal" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </span>
                                <input type="text" id="filterDateRangeDpmFinal" readonly placeholder="Pilih rentang tanggal" class="w-full pl-8 pr-2.5 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-2xs">
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
                </div>

                <!-- 2. Card: Pareto Final Assy -->
                <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 rounded-2xl p-5 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.06)] transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600 inline-block shadow-xs"></span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Pareto Sub Defect Final Assy</h3>
                                    <span class="text-[10px] font-bold text-teal-800 bg-teal-50 border border-teal-200/80 px-2 py-0.5 rounded-md">Final Assy</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Total defect & persentase kumulatif per sub defect</p>
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
                        <div>
                            <label for="filterDateRangeParetoFinal" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </span>
                            <input type="text" id="filterDateRangeParetoFinal" readonly placeholder="Pilih rentang tanggal" class="w-full pl-8 pr-2.5 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-2xs">
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
            <div class="space-y-6">
                <!-- Summary Cards Pre Assy (Sinkron Filter Mandiri) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Summary DPM Pre Assy -->
                    <div class="bg-white border border-slate-200 border-t-4 border-t-blue-600 rounded-xl p-4 shadow-[0_2px_10px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider" id="summaryTitleDpmPre">DPM Pre Assy</span>
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
                        <div class="mt-2 flex items-baseline space-x-2">
                            <span class="text-xl font-black text-slate-900 font-mono tracking-tight truncate max-w-[170px]" id="summaryValParetoPre">-</span>
                            <span class="text-xs font-bold text-blue-700 font-mono" id="summaryCountParetoPre"></span>
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
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">DPM Pre Assy</h3>
                                    <span class="text-[10px] font-bold text-blue-800 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md">Pre Assy</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Defect Per Million tren per periode</p>
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
                        <span id="errorMessageDpmPre">Gagal memuat analitik DPM Pre Assy</span>
                    </div>

                    <!-- Card Filter Toolbar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 mt-3 mb-4 p-3 bg-slate-50/70 rounded-xl border border-slate-100 items-end">
                        <!-- Rentang Tanggal -->
                        <div>
                            <label for="filterDateRangeDpmPre" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </span>
                                <input type="text" id="filterDateRangeDpmPre" readonly placeholder="Pilih rentang tanggal" class="w-full pl-8 pr-2.5 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 cursor-pointer transition shadow-2xs">
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
                                <button type="button" id="btnResetDpmPre" title="Reset filter DPM Pre Assy ke default" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center space-x-1">
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
                        <p class="text-xs font-semibold text-slate-600">Tidak ada data pada periode ini</p>
                        <span class="text-[11px] text-slate-400 mt-0.5">Ubah rentang tanggal atau pilih filter carline lain</span>
                    </div>
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
                        <div>
                            <label for="filterDateRangeParetoPre" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </span>
                            <input type="text" id="filterDateRangeParetoPre" readonly placeholder="Pilih rentang tanggal" class="w-full pl-8 pr-2.5 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 bg-white hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 cursor-pointer transition shadow-2xs">
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
                    <p class="text-xs font-semibold text-slate-600">Tidak ada data pada periode ini</p>
                    <span class="text-[11px] text-slate-400 mt-0.5">Ubah rentang tanggal atau pilih filter carline lain</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Analytics & Real-time Polling Script -->
    <script>
    document.addEventListener("DOMContentLoaded", function () {
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
        function createDpmChart(canvasId, accentColor, bgGradientColor, targetColor = '#64748b') {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;
            const ctx = canvas.getContext('2d');

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'DPM Real',
                            data: [],
                            borderColor: accentColor,
                            backgroundColor: bgGradientColor,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.3,
                            spanGaps: false,
                            pointBackgroundColor: accentColor,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            _meta: []
                        },
                        {
                            label: 'Target DPM',
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
                            hidden: true // Disembunyikan default jika belum ada target DPM
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
                                    const hasDataset0 = context.chart.tooltip?.dataPoints?.some(dp => dp.datasetIndex === 0);
                                    if (hasDataset0 && context.datasetIndex !== 0) {
                                        return null;
                                    }

                                    const i = context.dataIndex;
                                    const meta = context.chart.data.datasets[0]._meta ? context.chart.data.datasets[0]._meta[i] : null;
                                    const dpmVal = meta ? meta.dpm : (context.datasetIndex === 0 ? context.raw : null);
                                    const targetVal = meta ? meta.target : (context.datasetIndex === 1 ? context.raw : null);

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

                                    const lines = [
                                        `  DPM Real: ${dpmStr}`
                                    ];
                                    if (targetStr !== null) {
                                        lines.push(`  Target DPM: ${targetStr}`);
                                        if (dpmVal !== null && Number(dpmVal) > Number(targetVal)) {
                                            lines.push(`  ⚠️ Status: Melebihi Target`);
                                        }
                                    }
                                    lines.push(`  Total Defect: ${defectStr} unit`);
                                    lines.push(`  Total Inspect: ${inspectStr} unit`);
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
                                text: 'DPM',
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
            return chart;
        }

        function createParetoChart(canvasId, barColor) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;
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
                                    return `  Total Defect: ${Number(val).toLocaleString('id-ID')} unit`;
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
                                text: 'Defect (Unit)',
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
        const chartDpmFinal = createDpmChart('chartDpmFinal', '#0f766e', 'rgba(15, 118, 110, 0.08)', '#64748b');
        const chartParetoFinal = createParetoChart('chartParetoFinal', '#0f766e');
        const chartDpmPre = createDpmChart('chartDpmPre', '#1d4ed8', 'rgba(29, 78, 216, 0.08)', '#64748b');
        const chartParetoPre = createParetoChart('chartParetoPre', '#1e40af');

        // --- 4. In-Place Update & Empty State Logic ---
        function updateDpmData(chart, wrapperId, emptyId, trends, groupBy = 'day') {
            const wrapper = document.getElementById(wrapperId);
            const emptyEl = document.getElementById(emptyId);
            const msgEl = emptyEl ? emptyEl.querySelector('p') : null;

            const trendList = Array.isArray(trends) ? trends : [];

            if (trendList.length === 0) {
                if (msgEl) msgEl.textContent = 'Tidak ada data pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            const allDpmNull = trendList.every(t => t.dpm === null || t.dpm === undefined);
            if (allDpmNull) {
                if (msgEl) msgEl.textContent = 'Belum ada Quantity Inspect pada periode ini';
                if (wrapper) wrapper.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            if (msgEl) msgEl.textContent = 'Tidak ada data pada periode ini';
            if (wrapper) wrapper.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            if (chart) {
                chart._groupBy = groupBy;
                const baseColor = chart.data.datasets[0].borderColor || '#0f766e';
                chart.data.labels = trendList.map(t => t.label);

                // Dataset 0: DPM Real
                chart.data.datasets[0].data = trendList.map(t => t.dpm);
                chart.data.datasets[0]._meta = trendList;

                // Cek ketersediaan data target DPM
                const hasAnyTarget = trendList.some(t => t.target !== null && t.target !== undefined && !isNaN(Number(t.target)));

                if (hasAnyTarget) {
                    // Tampilkan garis target (abu gelap putus-putus)
                    chart.data.datasets[1].data = trendList.map(t => (t.target !== null && t.target !== undefined ? Number(t.target) : null));
                    chart.data.datasets[1].hidden = false;

                    // Titik spike: Titik merah jika DPM Real melebihi Target
                    chart.data.datasets[0].pointBackgroundColor = trendList.map(t => {
                        const hasTarget = t.target !== null && t.target !== undefined;
                        const hasDpm = t.dpm !== null && t.dpm !== undefined;
                        if (hasTarget && hasDpm && Number(t.dpm) > Number(t.target)) {
                            return '#dc2626'; // Merah spike
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
                        const hasTarget = t.target !== null && t.target !== undefined;
                        const hasDpm = t.dpm !== null && t.dpm !== undefined;
                        if (hasTarget && hasDpm && Number(t.dpm) > Number(t.target)) {
                            return 5;
                        }
                        return 4;
                    });
                } else {
                    // Sembunyikan garis target dan highlight spike sampai data target tersedia
                    chart.data.datasets[1].data = [];
                    chart.data.datasets[1].hidden = true;
                    chart.data.datasets[0].pointBackgroundColor = baseColor;
                    chart.data.datasets[0].pointBorderColor = '#ffffff';
                    chart.data.datasets[0].pointRadius = 4;
                }

                chart.update('none'); // Update in place tanpa flicker
            }
        }

        function updateParetoData(chart, wrapperId, emptyId, pareto) {
            const wrapper = document.getElementById(wrapperId);
            const emptyEl = document.getElementById(emptyId);
            const msgEl = emptyEl ? emptyEl.querySelector('p') : null;

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
                chart.data.labels = paretoList.map(p => p.name);
                chart.data.datasets[0].data = paretoList.map(p => p.cumulative_percent);
                chart.data.datasets[1].data = paretoList.map(p => p.total);
                chart.update('none'); // Update in place tanpa flicker
            }
        }

        // --- 5. Fetch Functions Terpisah Per Grafik (Isolated Abort & State) ---
        function fetchDpmFinal() {
            if (abortDpmFinal) abortDpmFinal.abort();
            abortDpmFinal = new AbortController();

            const loadingEl = document.getElementById('loadingDpmFinal');
            const errorEl = document.getElementById('errorDpmFinal');
            const errorMsgEl = document.getElementById('errorMessageDpmFinal');

            if (loadingEl) loadingEl.classList.remove('hidden');

            const params = new URLSearchParams();
            params.append('assy', 'final');
            if (filtersDpmFinal.start_date) params.append('start_date', filtersDpmFinal.start_date);
            if (filtersDpmFinal.end_date) params.append('end_date', filtersDpmFinal.end_date);
            if (filtersDpmFinal.group_by) params.append('group_by', filtersDpmFinal.group_by);
            if (filtersDpmFinal.conveyor && filtersDpmFinal.conveyor !== 'all') params.append('conveyor', filtersDpmFinal.conveyor);

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
                updateDpmData(chartDpmFinal, 'wrapperDpmFinal', 'emptyDpmFinal', res.dpm_final || [], filtersDpmFinal.group_by);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('[Analytics DPM Final] Error fetch:', err);
                if (loadingEl) loadingEl.classList.add('hidden');
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data DPM Final Assy, mencoba lagi pada polling berikutnya.';
                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            });
        }

        function fetchParetoFinal() {
            if (abortParetoFinal) abortParetoFinal.abort();
            abortParetoFinal = new AbortController();

            const loadingEl = document.getElementById('loadingParetoFinal');
            const errorEl = document.getElementById('errorParetoFinal');
            const errorMsgEl = document.getElementById('errorMessageParetoFinal');

            if (loadingEl) loadingEl.classList.remove('hidden');

            const params = new URLSearchParams();
            params.append('assy', 'final');
            if (filtersParetoFinal.start_date) params.append('start_date', filtersParetoFinal.start_date);
            if (filtersParetoFinal.end_date) params.append('end_date', filtersParetoFinal.end_date);
            if (filtersParetoFinal.conveyor && filtersParetoFinal.conveyor !== 'all') params.append('conveyor', filtersParetoFinal.conveyor);

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
                updateParetoData(chartParetoFinal, 'wrapperParetoFinal', 'emptyParetoFinal', res.pareto_final || []);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('[Analytics Pareto Final] Error fetch:', err);
                if (loadingEl) loadingEl.classList.add('hidden');
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data Pareto Final Assy, mencoba lagi pada polling berikutnya.';
                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            });
        }

        function fetchDpmPre() {
            if (abortDpmPre) abortDpmPre.abort();
            abortDpmPre = new AbortController();

            const loadingEl = document.getElementById('loadingDpmPre');
            const errorEl = document.getElementById('errorDpmPre');
            const errorMsgEl = document.getElementById('errorMessageDpmPre');

            if (loadingEl) loadingEl.classList.remove('hidden');

            const params = new URLSearchParams();
            params.append('assy', 'pre');
            if (filtersDpmPre.start_date) params.append('start_date', filtersDpmPre.start_date);
            if (filtersDpmPre.end_date) params.append('end_date', filtersDpmPre.end_date);
            if (filtersDpmPre.group_by) params.append('group_by', filtersDpmPre.group_by);
            if (filtersDpmPre.carline_id && filtersDpmPre.carline_id !== 'all') params.append('carline_id', filtersDpmPre.carline_id);

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
                    throw new Error('Gagal memuat data DPM Pre Assy.');
                }
                return response.json();
            })
            .then(res => {
                updateDpmData(chartDpmPre, 'wrapperDpmPre', 'emptyDpmPre', res.dpm_pre || [], filtersDpmPre.group_by);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('[Analytics DPM Pre] Error fetch:', err);
                if (loadingEl) loadingEl.classList.add('hidden');
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data DPM Pre Assy, mencoba lagi pada polling berikutnya.';
                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            });
        }

        function fetchParetoPre() {
            if (abortParetoPre) abortParetoPre.abort();
            abortParetoPre = new AbortController();

            const loadingEl = document.getElementById('loadingParetoPre');
            const errorEl = document.getElementById('errorParetoPre');
            const errorMsgEl = document.getElementById('errorMessageParetoPre');

            if (loadingEl) loadingEl.classList.remove('hidden');

            const params = new URLSearchParams();
            params.append('assy', 'pre');
            if (filtersParetoPre.start_date) params.append('start_date', filtersParetoPre.start_date);
            if (filtersParetoPre.end_date) params.append('end_date', filtersParetoPre.end_date);
            if (filtersParetoPre.carline_id && filtersParetoPre.carline_id !== 'all') params.append('carline_id', filtersParetoPre.carline_id);

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
                updateParetoData(chartParetoPre, 'wrapperParetoPre', 'emptyParetoPre', res.pareto_pre || []);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('[Analytics Pareto Pre] Error fetch:', err);
                if (loadingEl) loadingEl.classList.add('hidden');
                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data Pareto Pre Assy, mencoba lagi pada polling berikutnya.';
                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            });
        }

        // --- 6. Trigger Filter Debouncers ---
        function triggerFilterDpmFinal(debounceMs = 300) {
            if (debounceDpmFinal) clearTimeout(debounceDpmFinal);
            if (debounceMs === 0) fetchDpmFinal();
            else debounceDpmFinal = setTimeout(fetchDpmFinal, debounceMs);
        }

        function triggerFilterParetoFinal(debounceMs = 300) {
            if (debounceParetoFinal) clearTimeout(debounceParetoFinal);
            if (debounceMs === 0) fetchParetoFinal();
            else debounceParetoFinal = setTimeout(fetchParetoFinal, debounceMs);
        }

        function triggerFilterDpmPre(debounceMs = 300) {
            if (debounceDpmPre) clearTimeout(debounceDpmPre);
            if (debounceMs === 0) fetchDpmPre();
            else debounceDpmPre = setTimeout(fetchDpmPre, debounceMs);
        }

        function triggerFilterParetoPre(debounceMs = 300) {
            if (debounceParetoPre) clearTimeout(debounceParetoPre);
            if (debounceMs === 0) fetchParetoPre();
            else debounceParetoPre = setTimeout(fetchParetoPre, debounceMs);
        }

        // --- 7. Flatpickr & UI Events: DPM Final Assy ---
        let fpDpmFinal = null;
        if (window.flatpickr) {
            fpDpmFinal = window.flatpickr("#filterDateRangeDpmFinal", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [initDpmFinal.start, initDpmFinal.end],
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const pad = n => String(n).padStart(2, '0');
                        const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        filtersDpmFinal.start_date = fmt(selectedDates[0]);
                        filtersDpmFinal.end_date = fmt(selectedDates[1]);
                        triggerFilterDpmFinal(300);
                    } else if (selectedDates.length === 0) {
                        filtersDpmFinal.start_date = '';
                        filtersDpmFinal.end_date = '';
                        triggerFilterDpmFinal(300);
                    }
                }
            });
        }

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

                if (fpDpmFinal) fpDpmFinal.setDate([resetDates.start, resetDates.end], false);
                if (conveyorSelectDpmFinal) conveyorSelectDpmFinal.value = 'all';

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

        // --- 8. Flatpickr & UI Events: Pareto Final Assy ---
        let fpParetoFinal = null;
        if (window.flatpickr) {
            fpParetoFinal = window.flatpickr("#filterDateRangeParetoFinal", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [initParetoFinal.start, initParetoFinal.end],
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const pad = n => String(n).padStart(2, '0');
                        const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        filtersParetoFinal.start_date = fmt(selectedDates[0]);
                        filtersParetoFinal.end_date = fmt(selectedDates[1]);
                        triggerFilterParetoFinal(300);
                    } else if (selectedDates.length === 0) {
                        filtersParetoFinal.start_date = '';
                        filtersParetoFinal.end_date = '';
                        triggerFilterParetoFinal(300);
                    }
                }
            });
        }

        const conveyorSelectParetoFinal = document.getElementById('filterConveyorParetoFinal');
        if (conveyorSelectParetoFinal) {
            conveyorSelectParetoFinal.addEventListener('change', function () {
                filtersParetoFinal.conveyor = this.value;
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

                if (fpParetoFinal) fpParetoFinal.setDate([resetDates.start, resetDates.end], false);
                if (conveyorSelectParetoFinal) conveyorSelectParetoFinal.value = 'all';

                triggerFilterParetoFinal(0);
            });
        }

        // --- 9. Flatpickr & UI Events: DPM Pre Assy ---
        let fpDpmPre = null;
        if (window.flatpickr) {
            fpDpmPre = window.flatpickr("#filterDateRangeDpmPre", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [initDpmPre.start, initDpmPre.end],
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const pad = n => String(n).padStart(2, '0');
                        const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        filtersDpmPre.start_date = fmt(selectedDates[0]);
                        filtersDpmPre.end_date = fmt(selectedDates[1]);
                        triggerFilterDpmPre(300);
                    } else if (selectedDates.length === 0) {
                        filtersDpmPre.start_date = '';
                        filtersDpmPre.end_date = '';
                        triggerFilterDpmPre(300);
                    }
                }
            });
        }

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

                if (fpDpmPre) fpDpmPre.setDate([resetDates.start, resetDates.end], false);
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

        // --- 10. Flatpickr & UI Events: Pareto Pre Assy ---
        let fpParetoPre = null;
        if (window.flatpickr) {
            fpParetoPre = window.flatpickr("#filterDateRangeParetoPre", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [initParetoPre.start, initParetoPre.end],
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const pad = n => String(n).padStart(2, '0');
                        const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        filtersParetoPre.start_date = fmt(selectedDates[0]);
                        filtersParetoPre.end_date = fmt(selectedDates[1]);
                        triggerFilterParetoPre(300);
                    } else if (selectedDates.length === 0) {
                        filtersParetoPre.start_date = '';
                        filtersParetoPre.end_date = '';
                        triggerFilterParetoPre(300);
                    }
                }
            });
        }

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

                if (fpParetoPre) fpParetoPre.setDate([resetDates.start, resetDates.end], false);
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
            fetch('{{ url("/api/dashboard/stats") }}')
                .then(r => r.json())
                .then(stats => {
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
                .catch(err => console.error('[Polling] Gagal fetch stats:', err));
        }

        // --- 12. Polling Execution (8s & Tab Inactive Prevention) ---
        function runPollingCycle() {
            if (document.hidden) return;
            fetchStats();
            fetchDpmFinal();
            fetchParetoFinal();
            fetchDpmPre();
            fetchParetoPre();
        }

        // Initial Load
        fetchStats();
        fetchDpmFinal();
        fetchParetoFinal();
        fetchDpmPre();
        fetchParetoPre();

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
            if (abortDpmFinal) abortDpmFinal.abort();
            if (abortParetoFinal) abortParetoFinal.abort();
            if (abortDpmPre) abortDpmPre.abort();
            if (abortParetoPre) abortParetoPre.abort();
        });
    });
    </script>
@endsection
