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

    <!-- KPI Cards Grid (Using Reusable <x-metric-card>) -->
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

        <!-- Defect & Inspect Trend Section (Final Assy & Pre Assy Dipisah) -->
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            
            <!-- Card 1: Final Assy Trend -->
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_25px_-5px_rgba(0,0,0,0.08)] transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-5 pb-3 border-b border-gray-100">
                        <div>
                            <div class="flex items-center space-x-2.5">
                                <span class="w-3 h-3 rounded-lg bg-teal-500 inline-block shadow-sm shadow-teal-500/30"></span>
                                <h2 class="text-base font-extrabold text-gray-950 tracking-tight">Tren Final Assy</h2>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Perbandingan Qty Inspect (Teal) vs Qty Defect (Merah Yazaki)</p>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Period Switcher Final Assy -->
                            <div class="relative">
                                <select id="periodFinalAssy" class="appearance-none border border-gray-200 rounded-xl text-xs font-bold text-gray-700 px-3.5 pr-8 py-2 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                                    <option value="month">BULAN INI</option>
                                    <option value="week">MINGGU INI</option>
                                    <option value="today">HARI INI</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <!-- Live Badge -->
                            <span class="flex items-center space-x-1.5 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-full font-bold shadow-xs">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span>Live</span>
                            </span>
                        </div>
                    </div>

                    <!-- Mini Summary Cards Final Assy -->
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div class="bg-teal-50/60 border border-teal-100 rounded-xl p-3.5">
                            <div class="flex items-center space-x-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider">Total Inspect</span>
                            </div>
                            <span class="text-xl font-extrabold text-teal-950 leading-none">
                                {{ number_format($finalAssyChart['total_inspect'], 0, ',', '.') }}
                            </span>
                            <span id="final-today-inspect" class="block text-[10px] font-semibold text-teal-700 mt-1">Hari ini: {{ number_format($finalAssyChart['today_inspect'], 0, ',', '.') }}</span>
                        </div>
                        <div class="bg-red-50/60 border border-red-100 rounded-xl p-3.5">
                            <div class="flex items-center space-x-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-[#8b0000]"></span>
                                <span class="text-[10px] font-bold text-[#8b0000] uppercase tracking-wider">Total Defect</span>
                            </div>
                            <span id="final-summary-defect" class="text-xl font-extrabold text-[#8b0000] leading-none">
                                {{ number_format($finalAssyChart['total_defect'], 0, ',', '.') }}
                            </span>
                            <span id="final-today-defect" class="block text-[10px] font-semibold text-red-700 mt-1">Hari ini: {{ number_format($finalAssyChart['today_defect'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Canvas for Chart.js Final Assy -->
                    <div class="relative h-64 w-full">
                        <canvas id="finalAssyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Card 2: Pre Assy Trend -->
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_25px_-5px_rgba(0,0,0,0.08)] transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-5 pb-3 border-b border-gray-100">
                        <div>
                            <div class="flex items-center space-x-2.5">
                                <span class="w-3 h-3 rounded-lg bg-teal-500 inline-block shadow-sm shadow-teal-500/30"></span>
                                <h2 class="text-base font-extrabold text-gray-950 tracking-tight">Tren Pre Assy</h2>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Perbandingan Qty Inspect (Teal) vs Qty Defect (Merah Yazaki)</p>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Period Switcher Pre Assy -->
                            <div class="relative">
                                <select id="periodPreAssy" class="appearance-none border border-gray-200 rounded-xl text-xs font-bold text-gray-700 px-3.5 pr-8 py-2 bg-gray-50/70 hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer transition shadow-xs">
                                    <option value="month">BULAN INI</option>
                                    <option value="week">MINGGU INI</option>
                                    <option value="today">HARI INI</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <!-- Live Badge -->
                            <span class="flex items-center space-x-1.5 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-full font-bold shadow-xs">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span>Live</span>
                            </span>
                        </div>
                    </div>

                    <!-- Mini Summary Cards Pre Assy -->
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div class="bg-teal-50/60 border border-teal-100 rounded-xl p-3.5">
                            <div class="flex items-center space-x-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider">Total Inspect</span>
                            </div>
                            <span class="text-xl font-extrabold text-teal-950 leading-none">
                                {{ number_format($preAssyChart['total_inspect'], 0, ',', '.') }}
                            </span>
                            <span id="pre-today-inspect" class="block text-[10px] font-semibold text-teal-700 mt-1">Hari ini: {{ number_format($preAssyChart['today_inspect'], 0, ',', '.') }}</span>
                        </div>
                        <div class="bg-red-50/60 border border-red-100 rounded-xl p-3.5">
                            <div class="flex items-center space-x-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-[#8b0000]"></span>
                                <span class="text-[10px] font-bold text-[#8b0000] uppercase tracking-wider">Total Defect</span>
                            </div>
                            <span id="pre-summary-defect" class="text-xl font-extrabold text-[#8b0000] leading-none">
                                {{ number_format($preAssyChart['total_defect'], 0, ',', '.') }}
                            </span>
                            <span id="pre-today-defect" class="block text-[10px] font-semibold text-red-700 mt-1">Hari ini: {{ number_format($preAssyChart['today_defect'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Canvas for Chart.js Pre Assy -->
                    <div class="relative h-64 w-full">
                        <canvas id="preAssyChart"></canvas>
                    </div>
                </div>
            </div>

        </section>

        <!-- Recent Defect Section -->
        <section class="bg-surface border border-border rounded-2xl p-5 sm:p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)]">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-brand"></span>
                    <h2 class="text-base font-extrabold text-gray-950 tracking-tight">Recent Defect</h2>
                </div>
                <a href="{{ route('recent_defects.index') }}" class="text-xs font-bold text-brand hover:text-brand-active flex items-center space-x-1 transition-colors">
                    <span>Lihat Semua</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            <!-- Responsive Table Container -->
            <div class="overflow-x-auto pb-2">
                <table class="w-full min-w-[850px] text-left border-collapse">
                    <thead>
                        <tr class="border-b border-border bg-gray-50/50">
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 pl-3">Waktu</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">User</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center">Shift</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Jenis Assy</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Jenis Mobil</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Konveyor</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Pattern</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Jenis Defect</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Jenis Sub Defect</th>
                            <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center pr-3">Quantity</th>
                        </tr>
                    </thead>
                    <tbody id="recentDefectsBody">
                        @forelse($recentDefects as $defect)
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors" data-id="{{ $defect->external_id ?? $defect->id }}">
                                <td class="py-4 text-sm text-gray-500 px-4 pl-2 font-medium">
                                    <div class="text-xs leading-normal">
                                        <span class="block text-gray-900">{{ \Carbon\Carbon::parse($defect->waktu)->translatedFormat('d F Y') }}</span>
                                        <span class="block text-gray-400 mt-0.5 text-[11px]">{{ \Carbon\Carbon::parse($defect->waktu)->format('H:i:s') }}</span>
                                    </div>
                                </td>
                                <td class="py-4 text-sm text-gray-900 font-bold px-4">
                                    {{ $defect->user_name }}
                                </td>
                                <td class="py-4 text-sm text-gray-900 font-bold px-4 text-center">
                                    {{ $defect->shift ?? '-' }}
                                </td>
                                <td class="py-4 text-sm font-medium px-4">
                                    @if($defect->jenis_assy === 'Final Assy')
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded bg-[#e8fbf2] text-[#0f5132]">
                                            Final Assy
                                        </span>
                                    @else
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded bg-[#fdf2f2] text-[#842029]">
                                            Pre Assy
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 text-sm text-gray-950 font-bold px-4">
                                    {{ $defect->jenis_mobil ?? '-' }}
                                </td>
                                <td class="py-4 text-sm font-bold px-4">
                                    <span class="inline-block bg-gray-100 text-gray-700 text-xs font-bold px-2 py-0.5 rounded-lg tracking-wider">
                                        {{ $defect->conveyor }}
                                    </span>
                                </td>
                                <td class="py-4 text-sm font-semibold px-4">
                                    @if($defect->pattern)
                                        <span class="inline-block bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold px-2 py-0.5 rounded-lg tracking-wider">
                                            {{ $defect->pattern }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono px-4">
                                    {{ $defect->jenis_defect }}
                                </td>
                                <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono px-4">
                                    {{ $defect->jenis_sub_defect }}
                                </td>
                                <td class="py-4 text-sm text-gray-900 font-bold text-center px-4 pr-2">
                                    {{ $defect->quantity }}
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="10" class="py-6 text-center text-sm text-gray-500 font-medium">Tidak ada data defect terbaru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

@endsection
    <!-- ChartJS Initialization Script (Final Assy & Pre Assy Dual Metrics) -->
    <script>
        let finalChartInstance = null;
        let preChartInstance = null;
        let currentFinalAssy = @json($finalAssyChart);
        let currentPreAssy = @json($preAssyChart);

        const labelsToday = [
            '00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00',
            '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00',
            '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
        ];
        const labelsWeek = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        const labelsMonth = ['Tgl 1-5', 'Tgl 6-10', 'Tgl 11-15', 'Tgl 16-20', 'Tgl 21-25', 'Tgl 26+'];

        document.addEventListener("DOMContentLoaded", function () {
            function createAssyChart(canvasId, assyData, periodSelectId) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return null;
                const ctx = canvas.getContext('2d');

                const gradInspect = ctx.createLinearGradient(0, 0, 0, canvas.height || 260);
                gradInspect.addColorStop(0, 'rgba(13, 148, 136, 0.28)');
                gradInspect.addColorStop(1, 'rgba(13, 148, 136, 0.01)');

                const gradDefect = ctx.createLinearGradient(0, 0, 0, canvas.height || 260);
                gradDefect.addColorStop(0, 'rgba(185, 28, 28, 0.32)');
                gradDefect.addColorStop(1, 'rgba(185, 28, 28, 0.01)');

                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labelsMonth,
                        datasets: [
                            {
                                label: 'Qty Inspect',
                                data: assyData.month.inspect,
                                borderColor: '#0d9488',
                                backgroundColor: gradInspect,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#0d9488',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointHoverBorderWidth: 3,
                                pointHoverBackgroundColor: '#0f766e',
                            },
                            {
                                label: 'Qty Defect',
                                data: assyData.month.defect,
                                borderColor: '#b91c1c',
                                backgroundColor: gradDefect,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#8b0000',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointHoverBorderWidth: 3,
                                pointHoverBackgroundColor: '#7f1d1d',
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
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 16,
                                    color: '#4b5563',
                                    font: {
                                        size: 11,
                                        weight: '600'
                                    }
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
                                displayColors: true,
                                boxPadding: 6,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.parsed.y || 0;
                                        return `  ${label}: ${value.toLocaleString('id-ID')} unit`;
                                    }
                                }
                            }
                        },
                scales: {
                    x: {
                        grid: { 
                            display: false 
                        },
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
                            padding: 8
                        },
                        grid: { 
                            color: '#e5e7eb',
                            drawBorder: false
                        },
                        border: {
                            dash: [3, 3]
                        }
                    }
                }
                    }
                });

                const selectEl = document.getElementById(periodSelectId);
                if (selectEl) {
                    selectEl.addEventListener('change', function () {
                        const val = this.value;
                        const dataObj = (canvasId === 'finalAssyChart') ? currentFinalAssy : currentPreAssy;
                        if (val === 'today') {
                            chart.data.labels = labelsToday;
                            chart.data.datasets[0].data = dataObj.today.inspect;
                            chart.data.datasets[1].data = dataObj.today.defect;
                        } else if (val === 'week') {
                            chart.data.labels = labelsWeek;
                            chart.data.datasets[0].data = dataObj.week.inspect;
                            chart.data.datasets[1].data = dataObj.week.defect;
                        } else {
                            chart.data.labels = labelsMonth;
                            chart.data.datasets[0].data = dataObj.month.inspect;
                            chart.data.datasets[1].data = dataObj.month.defect;
                        }
                        chart.update();
                    });
                }

                return chart;
            }

            finalChartInstance = createAssyChart('finalAssyChart', currentFinalAssy, 'periodFinalAssy');
            preChartInstance = createAssyChart('preAssyChart', currentPreAssy, 'periodPreAssy');
        });
    </script>

    <!-- AJAX Polling Script (8 Detik) Real-time -->
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

        function flashElement(el) {
            if (!el) return;
            el.classList.remove('poll-updated');
            void el.offsetWidth; // trigger DOM reflow to restart animation
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

        function fetchChartsData() {
            fetch('{{ url("/api/dashboard/charts") }}')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;

                    currentFinalAssy = res.finalAssy;
                    currentPreAssy = res.preAssy;

                    // Update Final Assy Chart
                    const finalPeriod = document.getElementById('periodFinalAssy') ? document.getElementById('periodFinalAssy').value : 'month';
                    if (finalChartInstance && currentFinalAssy && currentFinalAssy[finalPeriod]) {
                        finalChartInstance.data.datasets[0].data = currentFinalAssy[finalPeriod].inspect;
                        finalChartInstance.data.datasets[1].data = currentFinalAssy[finalPeriod].defect;
                        finalChartInstance.update('none');
                    }

                    // Update Pre Assy Chart
                    const prePeriod = document.getElementById('periodPreAssy') ? document.getElementById('periodPreAssy').value : 'month';
                    if (preChartInstance && currentPreAssy && currentPreAssy[prePeriod]) {
                        preChartInstance.data.datasets[0].data = currentPreAssy[prePeriod].inspect;
                        preChartInstance.data.datasets[1].data = currentPreAssy[prePeriod].defect;
                        preChartInstance.update('none');
                    }

                    // Update Mini Summaries
                    if (currentFinalAssy) {
                        const elFI = document.getElementById('final-summary-inspect');
                        if (elFI) elFI.innerText = currentFinalAssy.total_inspect.toLocaleString('id-ID');
                        const elFD = document.getElementById('final-summary-defect');
                        if (elFD) elFD.innerText = currentFinalAssy.total_defect.toLocaleString('id-ID');
                        const elFTI = document.getElementById('final-today-inspect');
                        if (elFTI) elFTI.innerText = `Hari ini: ${currentFinalAssy.today_inspect.toLocaleString('id-ID')}`;
                        const elFTD = document.getElementById('final-today-defect');
                        if (elFTD) elFTD.innerText = `Hari ini: ${currentFinalAssy.today_defect.toLocaleString('id-ID')}`;
                    }

                    if (currentPreAssy) {
                        const elPI = document.getElementById('pre-summary-inspect');
                        if (elPI) elPI.innerText = currentPreAssy.total_inspect.toLocaleString('id-ID');
                        const elPD = document.getElementById('pre-summary-defect');
                        if (elPD) elPD.innerText = currentPreAssy.total_defect.toLocaleString('id-ID');
                        const elPTI = document.getElementById('pre-today-inspect');
                        if (elPTI) elPTI.innerText = `Hari ini: ${currentPreAssy.today_inspect.toLocaleString('id-ID')}`;
                        const elPTD = document.getElementById('pre-today-defect');
                        if (elPTD) elPTD.innerText = `Hari ini: ${currentPreAssy.today_defect.toLocaleString('id-ID')}`;
                    }
                })
                .catch(err => console.error('[Polling] Gagal fetch charts data:', err));
        }

        function fetchRecentDefects() {
            fetch('{{ url("/api/dashboard/recent-defects") }}')
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !Array.isArray(res.data)) return;
                    const tbody = document.getElementById('recentDefectsBody');
                    if (!tbody) return;

                    if (res.data.length === 0) {
                        tbody.innerHTML = '<tr id="emptyRow"><td colspan="10" class="py-8 text-center text-xs text-gray-400 font-semibold">Belum ada data defect.</td></tr>';
                        return;
                    }

                    let rowsHtml = '';
                    res.data.forEach(item => {
                        const waktu = item.waktu || item.created_at;
                        const jenisAssy = item.jenis_assy || 'Final Assy';
                        const assyBadge = jenisAssy === 'Final Assy'
                            ? '<span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded bg-[#e8fbf2] text-[#0f5132]">Final Assy</span>'
                            : '<span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded bg-[#fdf2f2] text-[#842029]">Pre Assy</span>';
                        const patternBadge = item.pattern
                            ? `<span class="inline-block bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold px-2 py-0.5 rounded-lg tracking-wider">${item.pattern}</span>`
                            : '<span class="text-gray-400">-</span>';

                        rowsHtml += `
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors" data-id="${item.id}">
                                <td class="py-4 text-sm text-gray-500 px-4 pl-2 font-medium"><div class="text-xs leading-normal"><span class="block text-gray-900">${formatDate(waktu)}</span><span class="block text-gray-400 mt-0.5 text-[11px]">${formatTime(waktu)}</span></div></td>
                                <td class="py-4 text-sm text-gray-900 font-bold px-4">${item.user_name || '-'}</td>
                                <td class="py-4 text-sm text-gray-900 font-bold px-4 text-center">${item.shift || '-'}</td>
                                <td class="py-4 text-sm font-medium px-4">${assyBadge}</td>
                                <td class="py-4 text-sm text-gray-950 font-bold px-4">${item.jenis_mobil || '-'}</td>
                                <td class="py-4 text-sm font-bold px-4"><span class="inline-block bg-gray-100 text-gray-700 text-xs font-bold px-2 py-0.5 rounded-lg tracking-wider">${item.conveyor || '-'}</span></td>
                                <td class="py-4 text-sm font-semibold px-4">${patternBadge}</td>
                                <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono px-4">${item.jenis_defect || '-'}</td>
                                <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono px-4">${item.jenis_sub_defect || '-'}</td>
                                <td class="py-4 text-sm text-gray-900 font-bold text-center px-4 pr-2">${item.quantity || 0}</td>
                            </tr>
                        `;
                    });

                    tbody.innerHTML = rowsHtml;
                })
                .catch(err => console.error('[Polling] Gagal fetch recent defects:', err));
        }

        // Initial fetch
        fetchStats();
        fetchChartsData();
        fetchRecentDefects();

        // 8-second polling timer
        pollingTimer = setInterval(() => {
            fetchStats();
            fetchChartsData();
            fetchRecentDefects();
        }, 8000);

        window.addEventListener('beforeunload', function() {
            if (pollingTimer) clearInterval(pollingTimer);
        });
    });
    </script>
@endsection
