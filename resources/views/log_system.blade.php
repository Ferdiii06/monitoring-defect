<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log System - Sistem Monitoring Defect</title>
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
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex">

    <!-- Left Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between shrink-0">
        <div>
            <!-- Logo Section -->
            <div class="p-6 border-b border-gray-100 flex items-center space-x-3">
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
            <nav class="p-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('account.create') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    <span>Add Account</span>
                </a>
                <a href="{{ route('final_assy.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span>Final Assy</span>
                </a>
                <a href="{{ route('pre_assy.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    <span>Pre Assy</span>
                </a>
                <a href="{{ route('log_system.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold bg-[#8b0000] text-white shadow-sm transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Log System</span>
                </a>
            </nav>
        </div>

        <!-- Logout Section -->
        <div class="p-6 border-t border-gray-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-[#8b0000] hover:bg-red-50 hover:text-[#600000] transition-all text-left">
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
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Log System</h1>
                <p class="text-sm text-gray-500 mt-1">Riwayat aktivitas semua pengguna secara real-time.</p>
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
                <form id="filterForm" method="GET" action="{{ route('log_system.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
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

                        <!-- Action Select -->
                        <div class="relative min-w-[150px]">
                            <select name="action" onchange="this.form.submit()" class="w-full appearance-none pl-4 pr-10 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                                <option value="all">Jenis Aksi</option>
                                @foreach($actionOptions as $option)
                                    <option value="{{ $option }}" {{ $selectedAction === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Defect Select -->
                        <div class="relative min-w-[150px]">
                            <select name="defect" onchange="this.form.submit()" class="w-full appearance-none pl-4 pr-10 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                                <option value="all">Jenis Defect</option>
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

                        <!-- Reset Filter Button if filters active -->
                        @if($dateRange || ($selectedAction && $selectedAction !== 'all') || ($selectedDefect && $selectedDefect !== 'all'))
                            <a href="{{ route('log_system.index') }}" class="text-xs text-gray-400 hover:text-[#8b0000] font-semibold transition-colors flex items-center space-x-1 pl-1">
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
                <div class="overflow-x-auto min-h-[400px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 pl-2">Waktu</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3">User</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 text-center">Jenis Aksi</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 pl-4">Aktivitas</th>
                                <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 pl-4">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 text-sm text-gray-500 pl-2 font-medium min-w-[160px]">
                                        {{ \Carbon\Carbon::parse($record->waktu)->translatedFormat('d F Y H:i:s') }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-900 font-bold">
                                        {{ $record->user_name }}
                                    </td>
                                    <td class="py-4 text-xs font-bold text-center">
                                        @if($record->jenis_aksi === 'Create Report')
                                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 tracking-wider">
                                                Create Report
                                            </span>
                                        @elseif($record->jenis_aksi === 'Delete Report')
                                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 tracking-wider">
                                                Delete Report
                                            </span>
                                        @elseif($record->jenis_aksi === 'Update Report')
                                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 tracking-wider">
                                                Update Report
                                            </span>
                                        @else
                                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 tracking-wider">
                                                Create Account
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 text-sm text-gray-600 font-medium pl-4">
                                        {{ $record->aktivitas }}
                                    </td>
                                    <td class="py-4 text-sm text-gray-500 font-medium pl-4 font-mono">
                                        {{ $record->ip_address }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-sm text-gray-400 font-medium">Tidak ada logs aktivitas untuk filter terpilih.</td>
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
        });

        // Client-side CSV exporter
        function exportCSV() {
            const data = @json($allFilteredRecords);
            if (data.length === 0) {
                alert('Tidak ada data logs untuk diexport.');
                return;
            }
            
            // Format column data
            let csvContent = "\uFEFF"; // UTF-8 BOM to display Indonesian characters properly in Excel
            csvContent += "Waktu,User,Jenis Aksi,Aktivitas,IP Address\n";
            
            data.forEach(function (row) {
                let csvRow = [
                    `"${row.waktu}"`,
                    `"${row.user_name.replace(/"/g, '""')}"`,
                    `"${row.jenis_aksi}"`,
                    `"${row.aktivitas.replace(/"/g, '""')}"`,
                    `"${row.ip_address}"`
                ].join(",");
                csvContent += csvRow + "\n";
            });
            
            // Download payload setup
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "log_system_" + new Date().toISOString().slice(0,10) + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
