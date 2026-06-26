<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Monitoring Defect</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <!-- Document SVG Icon -->
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
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold bg-[#8b0000] text-white shadow-sm transition-all">
                    <!-- Dashboard icon -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('account.create') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <!-- Add account icon -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    <span>Add Account</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <!-- Final Assy icon -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span>Final Assy</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <!-- Pre Assy icon -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    <span>Pre Assy</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <!-- Log system icon -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Log System</span>
                </a>
            </nav>
        </div>

        <!-- Logout Section at Bottom -->
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
            <div class="mb-6 bg-green-50 text-green-700 text-sm font-semibold p-4 rounded-xl border border-green-200 flex items-center space-x-2 shadow-sm">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- KPI Cards Grid -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Card 1: Total Defect -->
            <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-lg bg-[#fff2f2] flex items-center justify-center text-[#8b0000] shrink-0">
                    <!-- List menu icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Defect</span>
                    <span class="block text-2xl font-bold text-gray-950 mt-0.5 leading-none">{{ number_format($totalDefect, 0, ',', '.') }}</span>
                    <span class="block text-[10px] font-semibold text-gray-400 mt-1">Semua Waktu</span>
                </div>
            </div>

            <!-- Card 2: Defect Hari Ini -->
            <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-lg bg-[#eff6ff] flex items-center justify-center text-blue-600 shrink-0">
                    <!-- Chart icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Defect Hari Ini</span>
                    <span class="block text-2xl font-bold text-gray-950 mt-0.5 leading-none">{{ $defectToday }}</span>
                    <span class="block text-[10px] font-semibold text-gray-400 mt-1">Update real-time</span>
                </div>
            </div>

            <!-- Card 3: Active Users -->
            <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-lg bg-[#f0fdf4] flex items-center justify-center text-green-600 shrink-0">
                    <!-- Lightning icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Active Users</span>
                    <span class="block text-2xl font-bold text-gray-950 mt-0.5 leading-none">{{ $activeUsers }}</span>
                    <span class="block text-[10px] font-semibold text-gray-400 mt-1">Sedang Aktif</span>
                </div>
            </div>

            <!-- Card 4: Total Users -->
            <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-lg bg-[#faf5ff] flex items-center justify-center text-purple-600 shrink-0">
                    <!-- Users icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Users</span>
                    <span class="block text-2xl font-bold text-gray-950 mt-0.5 leading-none">{{ $totalUsers }}</span>
                    <span class="block text-[10px] font-semibold text-gray-400 mt-1">Semua User</span>
                </div>
            </div>
        </section>

        <!-- Defect Trend Section -->
        <section class="bg-white border border-gray-100 rounded-xl p-6 mb-8 shadow-sm">
            <h2 class="text-base font-semibold text-gray-950 mb-4">Defect Trend</h2>
            
            <div class="border border-gray-100 rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-bold text-gray-950">Grafik Defect (Real-time)</h3>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Dropdown Select -->
                        <div class="relative">
                            <select class="appearance-none border border-gray-200 rounded-md text-xs font-semibold text-gray-600 px-3 pr-8 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer">
                                <option>BULAN INI</option>
                                <option>MINGGU INI</option>
                                <option>HARI INI</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Live Status Badge -->
                        <span class="flex items-center space-x-1.5 text-xs text-green-600 bg-green-50 px-2.5 py-1 rounded-full font-bold">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span>Live</span>
                        </span>
                    </div>
                </div>

                <!-- Canvas for Chart.js -->
                <div class="relative h-64 w-full">
                    <canvas id="defectChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Recent Defect Section -->
        <section class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-semibold text-gray-950">Recent Defect</h2>
                <a href="#" class="text-xs font-bold text-[#8b0000] hover:text-[#600000] flex items-center space-x-1 hover:underline">
                    <span>Lihat Semua</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            <!-- Responsive Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 pl-2">Waktu</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3">User</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3">Jenis Assy</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3">Line / Conveyor</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3">Jenis Defect</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3">Jenis Sub Defect</th>
                            <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 text-center pr-2">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDefects as $defect)
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 text-sm text-gray-500 pl-2 font-medium">
                                    {{ \Carbon\Carbon::parse($defect->waktu)->translatedFormat('d F Y H:i:s') }}
                                </td>
                                <td class="py-4 text-sm text-gray-900 font-bold">
                                    {{ $defect->user_name }}
                                </td>
                                <td class="py-4 text-sm font-medium">
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
                                <td class="py-4 text-sm text-gray-900 font-bold pl-8">
                                    {{ $defect->line_conveyor }}
                                </td>
                                <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono">
                                    {{ $defect->jenis_defect }}
                                </td>
                                <td class="py-4 text-xs text-[#8b0000] font-bold tracking-wider uppercase font-mono">
                                    {{ $defect->jenis_sub_defect }}
                                </td>
                                <td class="py-4 text-sm text-gray-900 font-bold text-center pr-2">
                                    {{ $defect->quantity }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500 font-medium">Tidak ada data defect terbaru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- ChartJS Initialization Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('defectChart').getContext('2d');
            
            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
            gradient.addColorStop(0, 'rgba(139, 0, 0, 0.25)');   // Deep red with opacity
            gradient.addColorStop(1, 'rgba(139, 0, 0, 0.00)');   // Completely transparent

            const data = {
                labels: ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
                datasets: [{
                    label: 'Defect Counts',
                    data: [20, 32, 28, 42, 45, 60, 52, 30, 42, 48, 38, 50, 42, 38, 52, 58, 80, 88], // Match trend coordinates of screenshot
                    // Extra elements added to map custom interpolation points between standard hour labels
                    data: [20, 32, 28, 45, 60, 52, 28, 38, 48, 58, 54, 32, 28, 45, 50, 72, 84, 88],
                    borderColor: '#8b0000',
                    borderWidth: 2,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.4, // Smooth curve
                    pointBackgroundColor: '#8b0000',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 3,
                }]
            };

            // Interpolate labels to support multiple coordinate points
            const interpolatedLabels = [
                '08:00', '', '09:00', '', '10:00', '', '11:00', '', '12:00', '', '13:00', '', '14:00', '', '15:00', '', '16:00', '', '17:00'
            ];

            const config = {
                type: 'line',
                data: {
                    labels: interpolatedLabels.slice(0, 18),
                    datasets: [{
                        label: 'Defect Trend',
                        data: [20, 32, 28, 38, 42, 60, 52, 28, 38, 48, 58, 54, 32, 28, 45, 50, 72, 88],
                        borderColor: '#8b0000',
                        borderWidth: 2,
                        fill: true,
                        backgroundColor: gradient,
                        tension: 0.4, // Curve interpolation
                        pointBackgroundColor: '#8b0000',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // Hide legend
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Defect: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false // Hide x-axis grid lines
                            },
                            ticks: {
                                color: '#9ca3af',
                                font: {
                                    size: 10,
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            min: 0,
                            max: 100,
                            ticks: {
                                stepSize: 20,
                                color: '#9ca3af',
                                font: {
                                    size: 10,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: '#f3f4f6'
                            }
                        }
                    }
                }
            };

            const defectChart = new Chart(ctx, config);
        });
    </script>
</body>
</html>
