<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Monitoring Defect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom Scrollbar for premium aesthetic */
        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }
        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 8px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 8px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 h-screen overflow-hidden flex">

    <!-- Left Sidebar -->
    @include('partials.sidebar')

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto px-10 py-8 flex flex-col justify-start">

        <!-- Header Section -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">Monitoring real-time data defect dan aktivitas sistem.</p>
            </div>



            <div class="flex items-center space-x-6">
                <!-- Add Account Button -->
                <a href="{{ route('account.create') }}" class="bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-semibold py-2 px-4 rounded-md transition duration-200 shadow-sm flex items-center space-x-1.5 text-center">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Add Account</span>
                </a>

                <!-- Admin Profile Card -->
                <div class="flex items-center space-x-3 border-l border-gray-200 pl-6">
                    <div class="text-right">
                        <span class="block text-sm font-bold text-gray-900">{{ session('user_name', 'Admin QA') }}</span>
                        <span class="block text-xs font-semibold text-gray-400">Administrator</span>
                    </div>
                    <!-- Avatar image placeholder -->
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 border border-gray-200">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </header>

        <!-- Success Alert Banner -->
        @if (session('success'))
            <div class="mb-6 bg-green-50 text-green-700 text-sm font-semibold p-4 rounded-lg border border-green-200 flex items-center space-x-2 shadow-sm">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- KPI Cards Grid -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Card 1: Total Defect -->
            <div class="bg-white border border-gray-100 rounded-lg p-5 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-lg bg-[#fff2f2] flex items-center justify-center text-[#8b0000] shrink-0">
                    <!-- List menu icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Defect</span>
                    <span id="stat-total-defect" class="block text-2xl font-bold text-gray-950 mt-0.5 leading-none">{{ number_format($totalDefect, 0, ',', '.') }}</span>
                    <span class="block text-[10px] font-semibold text-gray-400 mt-1">Semua Waktu</span>
                </div>
            </div>

            <!-- Card 2: Defect Hari Ini -->
            <div class="bg-white border border-gray-100 rounded-lg p-5 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-lg bg-[#eff6ff] flex items-center justify-center text-blue-600 shrink-0">
                    <!-- Chart icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Defect Hari Ini</span>
                    <span id="stat-defect-today" class="block text-2xl font-bold text-gray-950 mt-0.5 leading-none">{{ $defectToday }}</span>
                    <span class="block text-[10px] font-semibold text-gray-400 mt-1">Update real-time</span>
                </div>
            </div>

            <!-- Card 3: Active Users -->
            <div class="bg-white border border-gray-100 rounded-lg p-5 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-lg bg-[#f0fdf4] flex items-center justify-center text-green-600 shrink-0">
                    <!-- Lightning icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Active Users</span>
                    <span id="stat-active-users" class="block text-2xl font-bold text-gray-950 mt-0.5 leading-none">{{ $activeUsers }}</span>
                    <span class="block text-[10px] font-semibold text-gray-400 mt-1">Sedang Aktif</span>
                </div>
            </div>

            <!-- Card 4: Total Users -->
            <div class="bg-white border border-gray-100 rounded-lg p-5 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-lg bg-[#faf5ff] flex items-center justify-center text-purple-600 shrink-0">
                    <!-- Users icon -->
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Users</span>
                    <span id="stat-total-users" class="block text-2xl font-bold text-gray-950 mt-0.5 leading-none">{{ $totalUsers }}</span>
                    <span class="block text-[10px] font-semibold text-gray-400 mt-1">Semua User</span>
                </div>
            </div>
        </section>

        <!-- Defect & Inspect Trend Section (Final Assy & Pre Assy Dipisah) -->
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            
            <!-- Card 1: Final Assy Trend -->
            <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-md bg-emerald-500 inline-block"></span>
                                <h2 class="text-base font-bold text-gray-950">Grafik Final Assy</h2>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Monitoring perbandingan Quantity Inspect & Defect</p>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Period Switcher Final Assy -->
                            <div class="relative">
                                <select id="periodFinalAssy" class="appearance-none border border-gray-200 rounded-lg text-xs font-bold text-gray-700 px-3 pr-8 py-1.5 bg-gray-50 hover:bg-white focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer transition">
                                    <option value="month">BULAN INI</option>
                                    <option value="week">MINGGU INI</option>
                                    <option value="today">HARI INI</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <!-- Live Badge -->
                            <span class="flex items-center space-x-1.5 text-xs text-green-600 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full font-bold">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                <span>Live</span>
                            </span>
                        </div>
                    </div>

                    <!-- Mini Summary Cards Final Assy -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-teal-50/70 border border-teal-100 rounded-xl p-3">
                            <div class="flex items-center space-x-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider">Total Inspect</span>
                            </div>
                            <span class="text-xl font-extrabold text-teal-900 leading-none">
                                {{ number_format($finalAssyChart['total_inspect'], 0, ',', '.') }}
                            </span>
                            <span id="final-today-inspect" class="block text-[10px] font-semibold text-teal-600 mt-1">Hari ini: {{ number_format($finalAssyChart['today_inspect'], 0, ',', '.') }}</span>
                        </div>
                        <div class="bg-red-50/70 border border-red-100 rounded-xl p-3">
                            <div class="flex items-center space-x-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-[#8b0000]"></span>
                                <span class="text-[10px] font-bold text-[#8b0000] uppercase tracking-wider">Total Defect</span>
                            </div>
                            <span id="final-summary-defect" class="text-xl font-extrabold text-[#8b0000] leading-none">
                                {{ number_format($finalAssyChart['total_defect'], 0, ',', '.') }}
                            </span>
                            <span id="final-today-defect" class="block text-[10px] font-semibold text-red-600 mt-1">Hari ini: {{ number_format($finalAssyChart['today_defect'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Canvas for Chart.js Final Assy -->
                    <div class="relative h-64 w-full">
                        <canvas id="finalAssyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Card 2: Pre Assy Trend -->
            <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-md bg-rose-500 inline-block"></span>
                                <h2 class="text-base font-bold text-gray-950">Grafik Pre Assy</h2>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Monitoring perbandingan Quantity Inspect & Defect</p>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Period Switcher Pre Assy -->
                            <div class="relative">
                                <select id="periodPreAssy" class="appearance-none border border-gray-200 rounded-lg text-xs font-bold text-gray-700 px-3 pr-8 py-1.5 bg-gray-50 hover:bg-white focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer transition">
                                    <option value="month">BULAN INI</option>
                                    <option value="week">MINGGU INI</option>
                                    <option value="today">HARI INI</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <!-- Live Badge -->
                            <span class="flex items-center space-x-1.5 text-xs text-green-600 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full font-bold">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                <span>Live</span>
                            </span>
                        </div>
                    </div>

                    <!-- Mini Summary Cards Pre Assy -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-teal-50/70 border border-teal-100 rounded-xl p-3">
                            <div class="flex items-center space-x-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider">Total Inspect</span>
                            </div>
                            <span class="text-xl font-extrabold text-teal-900 leading-none">
                                {{ number_format($preAssyChart['total_inspect'], 0, ',', '.') }}
                            </span>
                            <span id="pre-today-inspect" class="block text-[10px] font-semibold text-teal-600 mt-1">Hari ini: {{ number_format($preAssyChart['today_inspect'], 0, ',', '.') }}</span>
                        </div>
                        <div class="bg-red-50/70 border border-red-100 rounded-xl p-3">
                            <div class="flex items-center space-x-1.5 mb-1">
                                <span class="w-2 h-2 rounded-full bg-[#8b0000]"></span>
                                <span class="text-[10px] font-bold text-[#8b0000] uppercase tracking-wider">Total Defect</span>
                            </div>
                            <span id="pre-summary-defect" class="text-xl font-extrabold text-[#8b0000] leading-none">
                                {{ number_format($preAssyChart['total_defect'], 0, ',', '.') }}
                            </span>
                            <span id="pre-today-defect" class="block text-[10px] font-semibold text-red-600 mt-1">Hari ini: {{ number_format($preAssyChart['today_defect'], 0, ',', '.') }}</span>
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
        <section class="bg-white border border-gray-100 rounded-lg p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-semibold text-gray-950">Recent Defect</h2>
                <a href="{{ route('recent_defects.index') }}" class="text-xs font-bold text-[#8b0000] hover:text-[#600000] flex items-center space-x-1 hover:underline">
                    <span>Lihat Semua</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            <!-- Responsive Table -->
            <div class="overflow-x-auto pb-4">
                <table class="w-full min-w-[850px] text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 pl-2">Waktu</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">User</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Shift</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Jenis Assy</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Jenis Mobil</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Konveyor</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Pattern</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Jenis Defect</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Jenis Sub Defect</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 text-center pr-2">Quantity</th>
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
    </main>

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
                gradInspect.addColorStop(0, 'rgba(13, 148, 136, 0.25)');
                gradInspect.addColorStop(1, 'rgba(13, 148, 136, 0.00)');

                const gradDefect = ctx.createLinearGradient(0, 0, 0, canvas.height || 260);
                gradDefect.addColorStop(0, 'rgba(139, 0, 0, 0.25)');
                gradDefect.addColorStop(1, 'rgba(139, 0, 0, 0.00)');

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
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#0d9488',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 3.5,
                                pointHoverRadius: 5.5,
                            },
                            {
                                label: 'Qty Defect',
                                data: assyData.month.defect,
                                borderColor: '#8b0000',
                                backgroundColor: gradDefect,
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#8b0000',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 3.5,
                                pointHoverRadius: 5.5,
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
                                    padding: 15,
                                    font: {
                                        size: 11,
                                        weight: '600'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(17, 24, 39, 0.95)',
                                titleFont: { size: 11, weight: 'bold' },
                                bodyFont: { size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.parsed.y || 0;
                                        return ` ${label}: ${value.toLocaleString('id-ID')} unit`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: '#9ca3af',
                                    font: { size: 10, weight: '600' }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    color: '#9ca3af',
                                    font: { size: 10, weight: '600' }
                                },
                                grid: { color: '#f3f4f6' }
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

        function fetchStats() {
            fetch('{{ url("/api/dashboard/stats") }}')
                .then(r => r.json())
                .then(stats => {
                    const totalEl = document.getElementById('stat-total-defect');
                    if (totalEl && stats.totalDefect !== undefined) totalEl.innerText = stats.totalDefect.toLocaleString('id-ID');

                    const todayEl = document.getElementById('stat-defect-today');
                    if (todayEl && stats.defectToday !== undefined) todayEl.innerText = stats.defectToday.toLocaleString('id-ID');

                    const activeEl = document.getElementById('stat-active-users');
                    if (activeEl && stats.activeUsers !== undefined) activeEl.innerText = stats.activeUsers;

                    const totalUsersEl = document.getElementById('stat-total-users');
                    if (totalUsersEl && stats.totalUsers !== undefined) totalUsersEl.innerText = stats.totalUsers;
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

</body>
</html>
