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

        <!-- Unified Filter Bar Section -->
        <section class="bg-white border border-gray-100 rounded-2xl p-4 sm:p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Left: Title & Indicator -->
                <div class="flex items-center space-x-3">
                    <span class="w-3 h-3 rounded-lg bg-[#8b0000] inline-block shadow-sm shadow-[#8b0000]/30"></span>
                    <div>
                        <h2 class="text-base font-extrabold text-gray-950 tracking-tight">Filter Analitik</h2>
                        <p class="text-xs text-gray-500">Sesuaikan rentang tanggal, agregasi, konveyor, dan carline</p>
                    </div>
                </div>

                <!-- Right: Status indicator (Loading & Live) -->
                <div class="flex items-center space-x-3 self-end lg:self-center">
                    <span id="analyticsLoading" class="hidden items-center space-x-1.5 text-[11px] text-[#8b0000] bg-red-50 border border-red-100 px-2.5 py-1 rounded-full font-bold">
                        <svg class="animate-spin h-3 w-3 text-[#8b0000]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Memuat...</span>
                    </span>
                    <span class="flex items-center space-x-1.5 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-full font-bold shadow-xs">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>Live (8s)</span>
                    </span>
                </div>
            </div>

            <!-- Error Banner -->
            <div id="analyticsError" class="hidden mt-3 p-3 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-700">
                <span id="analyticsErrorMessage">Gagal memuat analitik</span>
            </div>

            <!-- Filter Controls Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4 pt-4 border-t border-gray-100 items-end">
                <!-- 1. Rentang Tanggal (Flatpickr) -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Rentang Tanggal</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </span>
                        <input type="text" id="filterDateRange" readonly placeholder="Pilih rentang tanggal" class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                    </div>
                </div>

                <!-- 2. Pengelompokan (Segmented Control) -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Pengelompokan</label>
                    <div class="inline-flex w-full bg-gray-100 p-1 rounded-xl border border-gray-200/70" role="group">
                        <button type="button" data-val="day" class="btn-group-by flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-center bg-[#8b0000] text-white shadow-xs">Hari</button>
                        <button type="button" data-val="week" class="btn-group-by flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-center text-gray-600 hover:text-gray-900">Minggu</button>
                        <button type="button" data-val="month" class="btn-group-by flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-center text-gray-600 hover:text-gray-900">Bulan</button>
                    </div>
                </div>

                <!-- 3. Konveyor (Final Assy) -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Konveyor (Final Assy)</label>
                    <div class="relative">
                        <select id="filterConveyor" class="w-full appearance-none border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 px-3 pr-8 py-2 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                            <option value="all">Semua Konveyor</option>
                            @if(isset($finalAssyConveyors))
                                @foreach($finalAssyConveyors as $conveyor)
                                    <option value="{{ $conveyor }}">{{ $conveyor }}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- 4. Carline (Pre Assy) + Reset Button -->
                <div class="flex items-center space-x-2">
                    <div class="flex-1">
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Carline (Pre Assy)</label>
                        <div class="relative">
                            <select id="filterCarline" class="w-full appearance-none border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 px-3 pr-8 py-2 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                                <option value="all">Semua Carline</option>
                                @if(isset($preAssyCarlines))
                                    @foreach($preAssyCarlines as $carline)
                                        <option value="{{ $carline->id }}">{{ $carline->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-transparent uppercase tracking-wider mb-1.5 select-none">Aksi</label>
                        <button type="button" id="btnResetFilter" title="Reset semua filter ke default" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all shadow-xs flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4 Charts Grid (2x2 Desktop, 1 Col Mobile) -->
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            <!-- Kolom Kiri: Final Assy (DPM di atas, Pareto di bawah) -->
            <div class="space-y-6">
                <!-- 1. DPM Final Assy -->
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

                <!-- 2. Pareto Final Assy -->
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

            <!-- Kolom Kanan: Pre Assy (DPM di atas, Pareto di bawah) -->
            <div class="space-y-6">
                <!-- 3. DPM Pre Assy -->
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

                <!-- 4. Pareto Pre Assy -->
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
        // --- 1. Filter State & Helpers ---
        function getInitialDates() {
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - 30);
            const pad = n => String(n).padStart(2, '0');
            const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            return { start: fmt(start), end: fmt(end) };
        }

        const initialDates = getInitialDates();
        const activeFilters = {
            start_date: initialDates.start,
            end_date: initialDates.end,
            group_by: 'day',
            conveyor: 'all',
            carline_id: 'all'
        };

        let analyticsAbortCtrl = null;
        let debounceTimer = null;
        let pollingTimer = null;

        // --- 2. Chart Inisialisasi ---
        function createDpmChart(canvasId, accentColor, bgGradientColor, targetColor = '#e11d48') {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;
            const ctx = canvas.getContext('2d');

            return new Chart(ctx, {
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
                            spanGaps: false, // target null tidak digambar
                            pointRadius: 2.5,
                            pointHoverRadius: 5,
                            pointBackgroundColor: targetColor,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5
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
                                padding: 6
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
                                    return val.toLocaleString('id-ID');
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

        // --- 3. In-Place Update & Empty State Logic ---
        function updateDpmData(chart, wrapperId, emptyId, trends) {
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

        function applyAnalyticsData(res) {
            if (!res) return;

            const dpmFinal = res.dpm_final || [];
            const paretoFinal = res.pareto_final || [];
            const dpmPre = res.dpm_pre || [];
            const paretoPre = res.pareto_pre || [];

            updateDpmData(chartDpmFinal, 'wrapperDpmFinal', 'emptyDpmFinal', dpmFinal);
            updateParetoData(chartParetoFinal, 'wrapperParetoFinal', 'emptyParetoFinal', paretoFinal);
            updateDpmData(chartDpmPre, 'wrapperDpmPre', 'emptyDpmPre', dpmPre);
            updateParetoData(chartParetoPre, 'wrapperParetoPre', 'emptyParetoPre', paretoPre);
        }

        // --- 4. Fetch Analytics (dengan AbortController & Filter State) ---
        function fetchAnalytics() {
            if (analyticsAbortCtrl) {
                analyticsAbortCtrl.abort();
            }
            analyticsAbortCtrl = new AbortController();

            const loadingEl = document.getElementById('analyticsLoading');
            const errorEl = document.getElementById('analyticsError');
            const errorMsgEl = document.getElementById('analyticsErrorMessage');

            if (loadingEl) loadingEl.classList.remove('hidden');

            const params = new URLSearchParams();
            if (activeFilters.start_date) params.append('start_date', activeFilters.start_date);
            if (activeFilters.end_date) params.append('end_date', activeFilters.end_date);
            if (activeFilters.group_by) params.append('group_by', activeFilters.group_by);
            if (activeFilters.conveyor && activeFilters.conveyor !== 'all') params.append('conveyor', activeFilters.conveyor);
            if (activeFilters.carline_id && activeFilters.carline_id !== 'all') params.append('carline_id', activeFilters.carline_id);

            fetch(`{{ route('admin.dashboard.analytics') }}?${params.toString()}`, {
                signal: analyticsAbortCtrl.signal,
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
                applyAnalyticsData(res);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.add('hidden');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('[Analytics] Error fetch analytics:', err);
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

        function triggerFilterChange(debounceMs = 300) {
            if (debounceTimer) clearTimeout(debounceTimer);
            if (debounceMs === 0) {
                fetchAnalytics();
            } else {
                debounceTimer = setTimeout(fetchAnalytics, debounceMs);
            }
        }

        // --- 5. Flatpickr & UI Filter Events ---
        let fpInstance = null;
        if (window.flatpickr) {
            fpInstance = window.flatpickr("#filterDateRange", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [initialDates.start, initialDates.end],
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const pad = n => String(n).padStart(2, '0');
                        const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        activeFilters.start_date = fmt(selectedDates[0]);
                        activeFilters.end_date = fmt(selectedDates[1]);
                        triggerFilterChange(300);
                    } else if (selectedDates.length === 0) {
                        activeFilters.start_date = '';
                        activeFilters.end_date = '';
                        triggerFilterChange(300);
                    }
                }
            });
        }

        // Segmented Control (Hari | Minggu | Bulan)
        const groupButtons = document.querySelectorAll('.btn-group-by');
        groupButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.dataset.val;
                if (activeFilters.group_by === val) return;

                activeFilters.group_by = val;
                groupButtons.forEach(b => {
                    b.classList.remove('bg-[#8b0000]', 'text-white', 'shadow-xs');
                    b.classList.add('text-gray-600', 'hover:text-gray-900');
                });
                this.classList.remove('text-gray-600', 'hover:text-gray-900');
                this.classList.add('bg-[#8b0000]', 'text-white', 'shadow-xs');

                triggerFilterChange(300);
            });
        });

        // Dropdown Konveyor (Final Assy)
        const conveyorSelect = document.getElementById('filterConveyor');
        if (conveyorSelect) {
            conveyorSelect.addEventListener('change', function () {
                activeFilters.conveyor = this.value;
                triggerFilterChange(300);
            });
        }

        // Dropdown Carline (Pre Assy)
        const carlineSelect = document.getElementById('filterCarline');
        if (carlineSelect) {
            carlineSelect.addEventListener('change', function () {
                activeFilters.carline_id = this.value;
                triggerFilterChange(300);
            });
        }

        // Reset Filter Button
        const btnReset = document.getElementById('btnResetFilter');
        if (btnReset) {
            btnReset.addEventListener('click', function () {
                const resetDates = getInitialDates();
                activeFilters.start_date = resetDates.start;
                activeFilters.end_date = resetDates.end;
                activeFilters.group_by = 'day';
                activeFilters.conveyor = 'all';
                activeFilters.carline_id = 'all';

                if (fpInstance) {
                    fpInstance.setDate([resetDates.start, resetDates.end], false);
                }
                if (conveyorSelect) conveyorSelect.value = 'all';
                if (carlineSelect) carlineSelect.value = 'all';

                groupButtons.forEach(b => {
                    if (b.dataset.val === 'day') {
                        b.classList.remove('text-gray-600', 'hover:text-gray-900');
                        b.classList.add('bg-[#8b0000]', 'text-white', 'shadow-xs');
                    } else {
                        b.classList.remove('bg-[#8b0000]', 'text-white', 'shadow-xs');
                        b.classList.add('text-gray-600', 'hover:text-gray-900');
                    }
                });

                triggerFilterChange(0);
            });
        }

        // --- 6. Polling Stats (Pertahankan dari kode lama) ---
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

        // --- 7. Polling Execution (8s & Tab Inactive Prevention) ---
        function runPollingCycle() {
            if (document.hidden) return; // Jangan polling saat tab tidak aktif
            fetchStats();
            fetchAnalytics();
        }

        // Initial Load
        fetchStats();
        fetchAnalytics();

        // Start 8-second interval
        pollingTimer = setInterval(runPollingCycle, 8000);

        // Resume saat tab kembali aktif
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                runPollingCycle();
            }
        });

        window.addEventListener('beforeunload', function() {
            if (pollingTimer) clearInterval(pollingTimer);
            if (analyticsAbortCtrl) analyticsAbortCtrl.abort();
        });
    });
    </script>
@endsection
