@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Header Section -->
    <header class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">Dashboard</h1>
            <p class="text-xs font-semibold text-gray-500 mt-1">Monitoring real-time data defect dan aktivitas sistem.</p>
        </div>

        <div class="flex items-center space-x-6">
            <!-- Add Account Button -->
            <a href="{{ route('account.create') }}" class="bg-brand hover:bg-brand-active text-white text-xs font-bold py-2.5 px-4 rounded-xl transition duration-150 shadow-sm shadow-brand/20 flex items-center space-x-1.5 text-center active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Add Account</span>
            </a>

            <!-- Admin Profile Card -->
            <div class="flex items-center space-x-3 border-l border-border pl-6">
                <div class="text-right">
                    <span class="block text-xs font-extrabold text-gray-900">{{ session('user_name', 'Admin QA') }}</span>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Administrator</span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-red-50 text-brand flex items-center justify-center border border-red-100 font-black text-xs">
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
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            <!-- KOLOM KIRI: FINAL ASSY -->
            <div class="space-y-6">
                <!-- 1. Filter Bar Final Assy -->
                <div class="bg-white border border-gray-100 rounded-2xl p-4 sm:p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center justify-between gap-3 pb-3 border-b border-gray-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-3 h-3 rounded-lg bg-teal-500 inline-block shadow-sm shadow-teal-500/30"></span>
                            <div>
                                <h2 class="text-base font-extrabold text-gray-950 tracking-tight">Final Assy</h2>
                                <p class="text-xs text-gray-500">Filter analitik khusus Final Assy</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="loadingFinal" class="hidden items-center space-x-1.5 text-[11px] text-teal-700 bg-teal-50 border border-teal-100 px-2.5 py-0.5 rounded-full font-bold">
                                <svg class="animate-spin h-3 w-3 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Memuat...</span>
                            </span>
                            <span class="flex items-center space-x-1 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full font-bold shadow-xs">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                </span>
                                <span>Live (8s)</span>
                            </span>
                        </div>
                    </div>

                    <!-- Error Banner Final Assy -->
                    <div id="errorFinal" class="hidden mt-3 p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-700">
                        <span id="errorMessageFinal">Gagal memuat analitik Final Assy</span>
                    </div>

                    <!-- Filter Controls Final Assy -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 mt-3 items-end">
                        <!-- Rentang Tanggal -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </span>
                                <input type="text" id="filterDateRangeFinal" readonly placeholder="Pilih rentang tanggal" class="w-full pl-8 pr-2.5 py-1.5 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-xs">
                            </div>
                        </div>

                        <!-- Pengelompokan DPM -->
                        <div>
                            <div class="flex items-center space-x-1 mb-1">
                                <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider">Pengelompokan DPM</label>
                                <span class="cursor-help text-gray-400 hover:text-gray-600 transition" title="Pareto tidak terpengaruh pengelompokan">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                            </div>
                            <div class="inline-flex w-full bg-gray-100 p-0.5 rounded-xl border border-gray-200/70" role="group">
                                <button type="button" data-val="day" class="btn-group-by-final flex-1 py-1 text-xs font-bold rounded-lg transition-all text-center bg-teal-600 text-white shadow-xs">Hari</button>
                                <button type="button" data-val="week" class="btn-group-by-final flex-1 py-1 text-xs font-bold rounded-lg transition-all text-center text-gray-600 hover:text-gray-900">Minggu</button>
                                <button type="button" data-val="month" class="btn-group-by-final flex-1 py-1 text-xs font-bold rounded-lg transition-all text-center text-gray-600 hover:text-gray-900">Bulan</button>
                            </div>
                        </div>

                        <!-- Konveyor + Reset Button -->
                        <div class="flex items-center space-x-2">
                            <div class="flex-1">
                                <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1">Konveyor</label>
                                <div class="relative">
                                    <select id="filterConveyorFinal" class="w-full appearance-none border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 px-2.5 pr-7 py-1.5 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 cursor-pointer transition shadow-xs">
                                        <option value="all">Semua Konveyor</option>
                                        @if(isset($finalAssyConveyors))
                                            @foreach($finalAssyConveyors as $conveyor)
                                                <option value="{{ $conveyor }}">{{ $conveyor }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-gray-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-transparent uppercase tracking-wider mb-1 select-none">Aksi</label>
                                <button type="button" id="btnResetFilterFinal" title="Reset filter Final Assy ke default" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all shadow-xs flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DPM Final Assy -->
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_25px_-5px_rgba(0,0,0,0.08)] transition-all duration-300">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-lg bg-teal-500 inline-block shadow-sm shadow-teal-500/30"></span>
                                <h3 class="text-base font-extrabold text-gray-950 tracking-tight">DPM Final Assy</h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Defect Per Million (DPM) tren per periode</p>
                        </div>
                        <span class="text-[11px] font-bold text-teal-700 bg-teal-50 border border-teal-100 px-2.5 py-0.5 rounded-full">Final Assy</span>
                    </div>
                    <div id="wrapperDpmFinal" class="relative h-64 w-full">
                        <canvas id="chartDpmFinal"></canvas>
                    </div>
                    <div id="emptyDpmFinal" class="hidden flex flex-col items-center justify-center h-64 text-center p-6 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p class="text-xs font-semibold text-gray-500">Tidak ada data pada periode ini</p>
                    </div>
                </div>

                <!-- 3. Pareto Final Assy -->
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_25px_-5px_rgba(0,0,0,0.08)] transition-all duration-300">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-lg bg-[#8b0000] inline-block shadow-sm shadow-[#8b0000]/30"></span>
                                <h3 class="text-base font-extrabold text-gray-950 tracking-tight">Pareto Final Assy</h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Total defect & persentase kumulatif per sub defect</p>
                        </div>
                        <span class="text-[11px] font-bold text-red-700 bg-red-50 border border-red-100 px-2.5 py-0.5 rounded-full">Final Assy</span>
                    </div>
                    <div id="wrapperParetoFinal" class="relative h-64 w-full">
                        <canvas id="chartParetoFinal"></canvas>
                    </div>
                    <div id="emptyParetoFinal" class="hidden flex flex-col items-center justify-center h-64 text-center p-6 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p class="text-xs font-semibold text-gray-500">Tidak ada data pada periode ini</p>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: PRE ASSY -->
            <div class="space-y-6">
                <!-- 1. Filter Bar Pre Assy -->
                <div class="bg-white border border-gray-100 rounded-2xl p-4 sm:p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center justify-between gap-3 pb-3 border-b border-gray-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-3 h-3 rounded-lg bg-sky-600 inline-block shadow-sm shadow-sky-600/30"></span>
                            <div>
                                <h2 class="text-base font-extrabold text-gray-950 tracking-tight">Pre Assy</h2>
                                <p class="text-xs text-gray-500">Filter analitik khusus Pre Assy</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="loadingPre" class="hidden items-center space-x-1.5 text-[11px] text-sky-700 bg-sky-50 border border-sky-100 px-2.5 py-0.5 rounded-full font-bold">
                                <svg class="animate-spin h-3 w-3 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Memuat...</span>
                            </span>
                            <span class="flex items-center space-x-1 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full font-bold shadow-xs">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                </span>
                                <span>Live (8s)</span>
                            </span>
                        </div>
                    </div>

                    <!-- Error Banner Pre Assy -->
                    <div id="errorPre" class="hidden mt-3 p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-700">
                        <span id="errorMessagePre">Gagal memuat analitik Pre Assy</span>
                    </div>

                    <!-- Filter Controls Pre Assy -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 mt-3 items-end">
                        <!-- Rentang Tanggal -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </span>
                                <input type="text" id="filterDateRangePre" readonly placeholder="Pilih rentang tanggal" class="w-full pl-8 pr-2.5 py-1.5 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-600 cursor-pointer transition shadow-xs">
                            </div>
                        </div>

                        <!-- Pengelompokan DPM -->
                        <div>
                            <div class="flex items-center space-x-1 mb-1">
                                <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider">Pengelompokan DPM</label>
                                <span class="cursor-help text-gray-400 hover:text-gray-600 transition" title="Pareto tidak terpengaruh pengelompokan">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                            </div>
                            <div class="inline-flex w-full bg-gray-100 p-0.5 rounded-xl border border-gray-200/70" role="group">
                                <button type="button" data-val="day" class="btn-group-by-pre flex-1 py-1 text-xs font-bold rounded-lg transition-all text-center bg-sky-600 text-white shadow-xs">Hari</button>
                                <button type="button" data-val="week" class="btn-group-by-pre flex-1 py-1 text-xs font-bold rounded-lg transition-all text-center text-gray-600 hover:text-gray-900">Minggu</button>
                                <button type="button" data-val="month" class="btn-group-by-pre flex-1 py-1 text-xs font-bold rounded-lg transition-all text-center text-gray-600 hover:text-gray-900">Bulan</button>
                            </div>
                        </div>

                        <!-- Carline + Reset Button -->
                        <div class="flex items-center space-x-2">
                            <div class="flex-1">
                                <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1">Carline</label>
                                <div class="relative">
                                    <select id="filterCarlinePre" class="w-full appearance-none border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 px-2.5 pr-7 py-1.5 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-600 cursor-pointer transition shadow-xs">
                                        <option value="all">Semua Carline</option>
                                        @if(isset($preAssyCarlines))
                                            @foreach($preAssyCarlines as $carline)
                                                <option value="{{ $carline->id }}">{{ $carline->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-gray-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-transparent uppercase tracking-wider mb-1 select-none">Aksi</label>
                                <button type="button" id="btnResetFilterPre" title="Reset filter Pre Assy ke default" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all shadow-xs flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DPM Pre Assy -->
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_25px_-5px_rgba(0,0,0,0.08)] transition-all duration-300">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-lg bg-sky-600 inline-block shadow-sm shadow-sky-600/30"></span>
                                <h3 class="text-base font-extrabold text-gray-950 tracking-tight">DPM Pre Assy</h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Defect Per Million (DPM) tren per periode</p>
                        </div>
                        <span class="text-[11px] font-bold text-sky-700 bg-sky-50 border border-sky-100 px-2.5 py-0.5 rounded-full">Pre Assy</span>
                    </div>
                    <div id="wrapperDpmPre" class="relative h-64 w-full">
                        <canvas id="chartDpmPre"></canvas>
                    </div>
                    <div id="emptyDpmPre" class="hidden flex flex-col items-center justify-center h-64 text-center p-6 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p class="text-xs font-semibold text-gray-500">Tidak ada data pada periode ini</p>
                    </div>
                </div>

                <!-- 3. Pareto Pre Assy -->
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_25px_-5px_rgba(0,0,0,0.08)] transition-all duration-300">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-lg bg-slate-700 inline-block shadow-sm shadow-slate-700/30"></span>
                                <h3 class="text-base font-extrabold text-gray-950 tracking-tight">Pareto Pre Assy</h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Total defect & persentase kumulatif per sub defect</p>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full">Pre Assy</span>
                    </div>
                    <div id="wrapperParetoPre" class="relative h-64 w-full">
                        <canvas id="chartParetoPre"></canvas>
                    </div>
                    <div id="emptyParetoPre" class="hidden flex flex-col items-center justify-center h-64 text-center p-6 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p class="text-xs font-semibold text-gray-500">Tidak ada data pada periode ini</p>
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

        // --- 2. Filter States Terpisah ---
        const initialFinal = getInitialDates();
        const filtersFinal = {
            start_date: initialFinal.start,
            end_date: initialFinal.end,
            group_by: 'day',
            conveyor: 'all'
        };

        const initialPre = getInitialDates();
        const filtersPre = {
            start_date: initialPre.start,
            end_date: initialPre.end,
            group_by: 'day',
            carline_id: 'all'
        };

        let abortFinal = null;
        let abortPre = null;
        let debounceFinal = null;
        let debouncePre = null;
        let pollingTimer = null;

        // --- 3. Chart Factory ---
        function createDpmChart(canvasId, accentColor, bgGradientColor, targetColor = '#e11d48') {
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
                            tension: 0.35,
                            spanGaps: false,
                            pointBackgroundColor: accentColor,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            _meta: []
                        },
                        {
                            label: 'Target',
                            data: [],
                            borderColor: targetColor,
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            fill: false,
                            tension: 0.2,
                            spanGaps: false,
                            pointRadius: 0,
                            pointHoverRadius: 0
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
                                boxWidth: 14,
                                boxHeight: 4,
                                usePointStyle: false,
                                padding: 12,
                                color: '#4b5563',
                                font: { size: 11, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.96)',
                            titleColor: '#f9fafb',
                            titleFont: { size: 12, weight: '700' },
                            bodyColor: '#f3f4f6',
                            bodyFont: { size: 11, weight: '500' },
                            padding: 12,
                            cornerRadius: 10,
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
                                    const targetStr = (targetVal !== null && targetVal !== undefined)
                                        ? Number(targetVal).toLocaleString('id-ID')
                                        : 'Belum ada target';
                                    const defectStr = meta && meta.total_defect !== undefined
                                        ? Number(meta.total_defect).toLocaleString('id-ID')
                                        : '0';
                                    const inspectStr = meta && meta.total_inspect !== undefined
                                        ? Number(meta.total_inspect).toLocaleString('id-ID')
                                        : '0';

                                    return [
                                        `  DPM Real: ${dpmStr}`,
                                        `  Target: ${targetStr}`,
                                        `  Total Defect: ${defectStr} unit`,
                                        `  Total Inspect: ${inspectStr} unit`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#6b7280',
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
                            ticks: {
                                precision: 0,
                                color: '#6b7280',
                                font: { size: 11, weight: '600' },
                                padding: 8,
                                callback: function(val) {
                                    return val.toLocaleString('id-ID');
                                }
                            },
                            grid: { color: '#e5e7eb', drawBorder: false },
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
                            borderColor: '#f59e0b',
                            backgroundColor: '#f59e0b',
                            borderWidth: 2,
                            pointRadius: 3.5,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#f59e0b',
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
                            borderRadius: 6,
                            maxBarThickness: 56,
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
                                color: '#4b5563',
                                font: { size: 11, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.96)',
                            titleColor: '#f9fafb',
                            titleFont: { size: 12, weight: '700' },
                            bodyColor: '#f3f4f6',
                            bodyFont: { size: 11, weight: '500' },
                            padding: 12,
                            cornerRadius: 10,
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
                                color: '#6b7280',
                                font: { size: 10, weight: '600' },
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
                            ticks: {
                                precision: 0,
                                color: '#6b7280',
                                font: { size: 11, weight: '600' },
                                padding: 8,
                                callback: function(val) {
                                    if (Number.isInteger(val)) {
                                        return val.toLocaleString('id-ID');
                                    }
                                    return '';
                                }
                            },
                            grid: { color: '#e5e7eb', drawBorder: false },
                            border: { dash: [3, 3] }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            min: 0,
                            max: 100,
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

        const chartDpmFinal = createDpmChart('chartDpmFinal', '#0d9488', 'rgba(13, 148, 136, 0.12)');
        const chartParetoFinal = createParetoChart('chartParetoFinal', '#8b0000');
        const chartDpmPre = createDpmChart('chartDpmPre', '#0284c7', 'rgba(2, 132, 199, 0.12)');
        const chartParetoPre = createParetoChart('chartParetoPre', '#334155');

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
                const baseColor = chart.data.datasets[0].borderColor || '#0d9488';
                chart.data.labels = trendList.map(t => t.label);

                // Dataset 0: DPM Real
                chart.data.datasets[0].data = trendList.map(t => t.dpm);
                chart.data.datasets[0]._meta = trendList;

                // Titik DPM Real berwarna merah jika nilainya di atas target, normal jika di bawah atau sama dengan target atau target tidak ada
                chart.data.datasets[0].pointBackgroundColor = trendList.map(t => {
                    const hasTarget = t.target !== null && t.target !== undefined;
                    const hasDpm = t.dpm !== null && t.dpm !== undefined;
                    if (hasTarget && hasDpm && Number(t.dpm) > Number(t.target)) {
                        return '#ef4444';
                    }
                    return baseColor;
                });

                chart.data.datasets[0].pointBorderColor = trendList.map(t => {
                    const hasTarget = t.target !== null && t.target !== undefined;
                    const hasDpm = t.dpm !== null && t.dpm !== undefined;
                    if (hasTarget && hasDpm && Number(t.dpm) > Number(t.target)) {
                        return '#b91c1c';
                    }
                    return '#ffffff';
                });

                // Dataset 1: Target
                if (chart.data.datasets[1]) {
                    chart.data.datasets[1].data = trendList.map(t => (t.target !== null && t.target !== undefined ? Number(t.target) : null));
                }

                chart.update('none'); // Update in place tanpa destroy / flicker
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
                chart.update('none'); // Update in place tanpa destroy / flicker
            }
        }

        // --- 5. Fetch Final & Fetch Pre Terpisah (dengan AbortController & State) ---
        function fetchFinal() {
            if (abortFinal) {
                abortFinal.abort();
            }
            abortFinal = new AbortController();

            const loadingEl = document.getElementById('loadingFinal');
            const errorEl = document.getElementById('errorFinal');
            const errorMsgEl = document.getElementById('errorMessageFinal');

            if (loadingEl) loadingEl.classList.remove('hidden');

            const params = new URLSearchParams();
            params.append('assy', 'final');
            if (filtersFinal.start_date) params.append('start_date', filtersFinal.start_date);
            if (filtersFinal.end_date) params.append('end_date', filtersFinal.end_date);
            if (filtersFinal.group_by) params.append('group_by', filtersFinal.group_by);
            if (filtersFinal.conveyor && filtersFinal.conveyor !== 'all') params.append('conveyor', filtersFinal.conveyor);

            fetch(`{{ route('admin.dashboard.analytics') }}?${params.toString()}`, {
                signal: abortFinal.signal,
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
                    throw new Error('Gagal memuat data, mencoba lagi pada polling berikutnya.');
                }
                return response.json();
            })
            .then(res => {
                updateDpmData(chartDpmFinal, 'wrapperDpmFinal', 'emptyDpmFinal', res.dpm_final || [], filtersFinal.group_by);
                updateParetoData(chartParetoFinal, 'wrapperParetoFinal', 'emptyParetoFinal', res.pareto_final || []);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('[Analytics Final] Error fetch:', err);
                if (loadingEl) loadingEl.classList.add('hidden');

                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data, mencoba lagi pada polling berikutnya.';

                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            });
        }

        function fetchPre() {
            if (abortPre) {
                abortPre.abort();
            }
            abortPre = new AbortController();

            const loadingEl = document.getElementById('loadingPre');
            const errorEl = document.getElementById('errorPre');
            const errorMsgEl = document.getElementById('errorMessagePre');

            if (loadingEl) loadingEl.classList.remove('hidden');

            const params = new URLSearchParams();
            params.append('assy', 'pre');
            if (filtersPre.start_date) params.append('start_date', filtersPre.start_date);
            if (filtersPre.end_date) params.append('end_date', filtersPre.end_date);
            if (filtersPre.group_by) params.append('group_by', filtersPre.group_by);
            if (filtersPre.carline_id && filtersPre.carline_id !== 'all') params.append('carline_id', filtersPre.carline_id);

            fetch(`{{ route('admin.dashboard.analytics') }}?${params.toString()}`, {
                signal: abortPre.signal,
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
                    throw new Error('Gagal memuat data, mencoba lagi pada polling berikutnya.');
                }
                return response.json();
            })
            .then(res => {
                updateDpmData(chartDpmPre, 'wrapperDpmPre', 'emptyDpmPre', res.dpm_pre || [], filtersPre.group_by);
                updateParetoData(chartParetoPre, 'wrapperParetoPre', 'emptyParetoPre', res.pareto_pre || []);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('[Analytics Pre] Error fetch:', err);
                if (loadingEl) loadingEl.classList.add('hidden');

                const message = err.isSessionExpired
                    ? 'Sesi berakhir. Muat ulang halaman.'
                    : 'Gagal memuat data, mencoba lagi pada polling berikutnya.';

                if (errorEl && errorMsgEl) {
                    errorMsgEl.innerText = message;
                    errorEl.classList.remove('hidden');
                }
            });
        }

        function triggerFilterFinal(debounceMs = 300) {
            if (debounceFinal) clearTimeout(debounceFinal);
            if (debounceMs === 0) {
                fetchFinal();
            } else {
                debounceFinal = setTimeout(fetchFinal, debounceMs);
            }
        }

        function triggerFilterPre(debounceMs = 300) {
            if (debouncePre) clearTimeout(debouncePre);
            if (debounceMs === 0) {
                fetchPre();
            } else {
                debouncePre = setTimeout(fetchPre, debounceMs);
            }
        }

        // --- 6. Flatpickr & UI Events Final Assy ---
        let fpFinal = null;
        if (window.flatpickr) {
            fpFinal = window.flatpickr("#filterDateRangeFinal", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [initialFinal.start, initialFinal.end],
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const pad = n => String(n).padStart(2, '0');
                        const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        filtersFinal.start_date = fmt(selectedDates[0]);
                        filtersFinal.end_date = fmt(selectedDates[1]);
                        triggerFilterFinal(300);
                    } else if (selectedDates.length === 0) {
                        filtersFinal.start_date = '';
                        filtersFinal.end_date = '';
                        triggerFilterFinal(300);
                    }
                }
            });
        }

        const groupButtonsFinal = document.querySelectorAll('.btn-group-by-final');
        groupButtonsFinal.forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.dataset.val;
                if (filtersFinal.group_by === val) return;

                filtersFinal.group_by = val;
                groupButtonsFinal.forEach(b => {
                    b.classList.remove('bg-teal-600', 'text-white', 'shadow-xs');
                    b.classList.add('text-gray-600', 'hover:text-gray-900');
                });
                this.classList.remove('text-gray-600', 'hover:text-gray-900');
                this.classList.add('bg-teal-600', 'text-white', 'shadow-xs');

                triggerFilterFinal(300);
            });
        });

        const conveyorSelectFinal = document.getElementById('filterConveyorFinal');
        if (conveyorSelectFinal) {
            conveyorSelectFinal.addEventListener('change', function () {
                filtersFinal.conveyor = this.value;
                triggerFilterFinal(300);
            });
        }

        const btnResetFinal = document.getElementById('btnResetFilterFinal');
        if (btnResetFinal) {
            btnResetFinal.addEventListener('click', function () {
                const resetDates = getInitialDates();
                filtersFinal.start_date = resetDates.start;
                filtersFinal.end_date = resetDates.end;
                filtersFinal.group_by = 'day';
                filtersFinal.conveyor = 'all';

                if (fpFinal) {
                    fpFinal.setDate([resetDates.start, resetDates.end], false);
                }
                if (conveyorSelectFinal) conveyorSelectFinal.value = 'all';

                groupButtonsFinal.forEach(b => {
                    if (b.dataset.val === 'day') {
                        b.classList.remove('text-gray-600', 'hover:text-gray-900');
                        b.classList.add('bg-teal-600', 'text-white', 'shadow-xs');
                    } else {
                        b.classList.remove('bg-teal-600', 'text-white', 'shadow-xs');
                        b.classList.add('text-gray-600', 'hover:text-gray-900');
                    }
                });

                triggerFilterFinal(0);
            });
        }

        // --- 7. Flatpickr & UI Events Pre Assy ---
        let fpPre = null;
        if (window.flatpickr) {
            fpPre = window.flatpickr("#filterDateRangePre", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [initialPre.start, initialPre.end],
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const pad = n => String(n).padStart(2, '0');
                        const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        filtersPre.start_date = fmt(selectedDates[0]);
                        filtersPre.end_date = fmt(selectedDates[1]);
                        triggerFilterPre(300);
                    } else if (selectedDates.length === 0) {
                        filtersPre.start_date = '';
                        filtersPre.end_date = '';
                        triggerFilterPre(300);
                    }
                }
            });
        }

        const groupButtonsPre = document.querySelectorAll('.btn-group-by-pre');
        groupButtonsPre.forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.dataset.val;
                if (filtersPre.group_by === val) return;

                filtersPre.group_by = val;
                groupButtonsPre.forEach(b => {
                    b.classList.remove('bg-sky-600', 'text-white', 'shadow-xs');
                    b.classList.add('text-gray-600', 'hover:text-gray-900');
                });
                this.classList.remove('text-gray-600', 'hover:text-gray-900');
                this.classList.add('bg-sky-600', 'text-white', 'shadow-xs');

                triggerFilterPre(300);
            });
        });

        const carlineSelectPre = document.getElementById('filterCarlinePre');
        if (carlineSelectPre) {
            carlineSelectPre.addEventListener('change', function () {
                filtersPre.carline_id = this.value;
                triggerFilterPre(300);
            });
        }

        const btnResetPre = document.getElementById('btnResetFilterPre');
        if (btnResetPre) {
            btnResetPre.addEventListener('click', function () {
                const resetDates = getInitialDates();
                filtersPre.start_date = resetDates.start;
                filtersPre.end_date = resetDates.end;
                filtersPre.group_by = 'day';
                filtersPre.carline_id = 'all';

                if (fpPre) {
                    fpPre.setDate([resetDates.start, resetDates.end], false);
                }
                if (carlineSelectPre) carlineSelectPre.value = 'all';

                groupButtonsPre.forEach(b => {
                    if (b.dataset.val === 'day') {
                        b.classList.remove('text-gray-600', 'hover:text-gray-900');
                        b.classList.add('bg-sky-600', 'text-white', 'shadow-xs');
                    } else {
                        b.classList.remove('bg-sky-600', 'text-white', 'shadow-xs');
                        b.classList.add('text-gray-600', 'hover:text-gray-900');
                    }
                });

                triggerFilterPre(0);
            });
        }

        // --- 8. Polling Stats (Real-time Defect & Users) ---
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

        // --- 9. Polling Execution (8s & Tab Inactive Prevention) ---
        function runPollingCycle() {
            if (document.hidden) return;
            fetchStats();
            fetchFinal();
            fetchPre();
        }

        // Initial Load
        fetchStats();
        fetchFinal();
        fetchPre();

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
            if (abortFinal) abortFinal.abort();
            if (abortPre) abortPre.abort();
        });
    });
    </script>
@endsection
