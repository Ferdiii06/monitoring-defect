<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Pre Assy - Sistem Monitoring Defect</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Flatpickr (Date Range Picker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
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
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between shrink-0">
        <div>
            <!-- Logo Section -->
            <div class="p-4 border-b border-gray-100 flex items-center space-x-3">
                <div class="w-9 h-9 rounded-lg bg-[#8b0000] flex items-center justify-center text-white shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-xs font-bold text-[#8b0000] uppercase tracking-wider leading-none">Report Defect</span>
                    <span class="block text-xs font-bold text-gray-900 uppercase tracking-wider mt-0.5">System</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('account.create') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    <span>Add Account</span>
                </a>
                <a href="{{ route('final_assy.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span>Final Assy</span>
                </a>
                <a href="{{ route('pre_assy.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold bg-[#8b0000] text-white shadow-sm transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    <span>Pre Assy</span>
                </a>
                <a href="{{ route('log_system.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 1118 0z"></path>
                    </svg>
                    <span>Log System</span>
                </a>
            </nav>
        </div>

        <!-- Logout Section -->
        <div class="p-3 border-t border-gray-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-[#8b0000] hover:bg-red-50 hover:text-[#600000] transition-all text-left">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto px-10 py-8 flex flex-col justify-start">
        
        <!-- Header Section -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Report Pre Assy</h1>
                <p class="text-sm text-gray-500 mt-1">Riwayat aktivitas defect pre assy secara real-time.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Admin Profile Card -->
                <div class="flex items-center space-x-3">
                    <div class="text-right">
                        <span class="block text-sm font-bold text-gray-900">{{ session('user_name', 'Admin QA') }}</span>
                        <span class="block text-xs font-semibold text-gray-400">{{ session('user_role', 'Administrator') }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 border border-gray-200">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filters and Table Container -->
        <section class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex-1 flex flex-col justify-between">
            
            <div>
                <!-- Form Filter -->
                <form id="filterForm" method="GET" action="{{ route('pre_assy.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <input type="hidden" name="page" value="1">
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Date Range Picker -->
                        <div class="relative min-w-[240px]">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </span>
                            <input type="text" id="date_range" name="date_range" placeholder="Pilih Tanggal" value="{{ $dateRange }}" readonly class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-xs font-medium text-gray-600 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                        </div>

                        <!-- Defect Select -->
                        <div class="relative min-w-[150px]">
                            <select name="defect" onchange="this.form.submit()" class="w-full appearance-none pl-4 pr-10 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                                <option value="all">Semua Defect</option>
                                @foreach($defectOptions as $option)
                                    <option value="{{ $option }}" {{ $selectedDefect === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Mobil Select -->
                        <div class="relative min-w-[130px]">
                            <select id="mobilSelect" name="line" class="w-full appearance-none pl-4 pr-10 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                                <option value="all">Semua Mobil</option>
                                @foreach($lineOptions as $option)
                                    <option value="{{ $option }}" {{ (string)$selectedLine === (string)$option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Conveyor Select -->
                        <div class="relative min-w-[150px]">
                            <select id="conveyorSelect" name="conveyor" onchange="this.form.submit()" class="w-full appearance-none pl-4 pr-10 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer disabled:bg-gray-100 disabled:cursor-not-allowed">
                                <option value="all">Semua Konveyor</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Reset Filter Button if filters active -->
                        @if($dateRange || ($selectedDefect && $selectedDefect !== 'all') || ($selectedLine && $selectedLine !== 'all') || ($selectedConveyor && $selectedConveyor !== 'all'))
                            <a href="{{ route('pre_assy.index') }}" class="text-xs text-gray-400 hover:text-[#8b0000] font-semibold transition-colors flex items-center space-x-1 pl-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Reset Filter</span>
                            </a>
                        @endif
                    </div>

                    <!-- Export Button -->
                    <button type="button" onclick="exportCSV()" class="bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold px-5 py-2.5 rounded-xl transition duration-200 shadow-sm flex items-center space-x-2">
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
                            <tr class="border-b border-gray-200">
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 pl-2">Waktu</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">User</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Data Mobil</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Konveyor</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Jenis Defect</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Jenis Sub Defect</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 text-center pr-2">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 text-sm text-gray-500 px-4 pl-2 font-medium">
                                        <div class="text-xs leading-normal">
                                            <span class="block text-gray-900">{{ \Carbon\Carbon::parse($record->waktu)->translatedFormat('d F Y') }}</span>
                                            <span class="block text-gray-400 mt-0.5 text-[11px]">{{ \Carbon\Carbon::parse($record->waktu)->format('H:i:s') }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 font-bold px-4">
                                        {{ $record->user_name }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-950 font-bold px-4">
                                        {{ $record->line_conveyor }}
                                    </td>
                                    <td class="py-4 text-sm font-bold px-4">
                                        <span class="inline-block bg-gray-100 text-gray-700 text-xs font-bold px-2 py-0.5 rounded-lg tracking-wider">
                                            {{ $record->konveyor }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono px-4">
                                        {{ $record->jenis_defect }}
                                    </td>
                                    <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono px-4">
                                        {{ $record->jenis_sub_defect }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 font-bold text-center px-4 pr-2">
                                        {{ $record->quantity }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-sm text-gray-400 font-medium">Tidak ada data defect untuk filter terpilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Pagination Row -->
            <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-100 pt-6 mt-6 gap-4">
                <span class="text-xs font-semibold text-gray-400">
                    Menampilkan {{ count($records) }} dari {{ $totalItems }} entri
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7-7"></path>
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
            'Toyota': [
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
            'Nissan': [
                'P33A-B1.BAT', 'P33A-B1.CELL', 'J32V-B2.LHD', 'J32V-B2.RHD',
                'J42U-B3.EGI', 'J42U-B3.ENGINE', 'J42U-B2.DOOR RH', 'J42U-B2.DOOR LH',
                'P33C-B1.BAT', 'P33C-B1.CELL'
            ],
            'Mazda': [
                'J72A-12B.LHD', 'J72A-AB9.RHD', 'J72A-16C.LHD', 'J72K-16C.LHD',
                'J30A-AB6.EXTEND LHD', 'J30A-AB1.INPANEL LHD', 'J30A-AB6.EXTEND RHD', 'J30A-AB1.INPANEL RHD',
                'J69P-AB8.EXTEND LHD', 'J69P-AB8.INPANEL LHD', 'J69P-AB8.EXTEND RHD', 'J69P-AB8.INPANEL RHD',
                'J69P-AB9.EXTEND LHD', 'J69P-AB3.INPANEL LHD'
            ]
        };

        document.addEventListener("DOMContentLoaded", function () {
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
                populateConveyors(this.value, 'all');
                this.form.submit();
            });

            // Initialize on load
            populateConveyors(mobilSelect.value, selectedConveyor);
        });

        // Client-side CSV exporter
        function exportCSV() {
            const data = @json($allFilteredRecords);
            if (data.length === 0) {
                alert('Tidak ada data defect untuk diexport.');
                return;
            }
            
            // Format column data
            let csvContent = "\uFEFF"; // UTF-8 BOM to display Indonesian characters properly in Excel
            csvContent += "Waktu,User,Jenis Assy,Data Mobil,Konveyor,Jenis Defect,Jenis Sub Defect,Quantity\n";
            
            data.forEach(function (row) {
                let csvRow = [
                    `"${row.waktu}"`,
                    `"${row.user_name.replace(/"/g, '""')}"`,
                    `"${row.jenis_assy}"`,
                    `"${row.line_conveyor}"`,
                    `"${row.konveyor}"`,
                    `"${row.jenis_defect.replace(/"/g, '""')}"`,
                    `"${row.jenis_sub_defect.replace(/"/g, '""')}"`,
                    `"${row.quantity}"`
                ].join(",");
                csvContent += csvRow + "\n";
            });
            
            // Download payload setup
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "report_pre_assy_" + new Date().toISOString().slice(0,10) + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
