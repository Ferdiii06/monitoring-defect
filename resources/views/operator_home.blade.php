<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Home - Report Internal Defect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-6 px-4 flex justify-center items-start">

    <!-- Mobile-First Container Card -->
    <main class="w-full max-w-md bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 flex flex-col">

        <!-- Header Row -->
        <header class="p-6 pb-4 flex justify-between items-center border-b border-gray-100">
            <h1 class="text-xl font-bold text-[#8b0000]">{{ session('user_name', 'Operator QA') }}</h1>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Logout" class="text-gray-600 hover:text-[#8b0000] p-1 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </button>
            </form>
        </header>

        <div class="p-6 space-y-6">

            <!-- Success Alert Banner -->
            @if(session('success'))
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-2xl p-4 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Active Shift Pill -->
            <div>
                <span class="inline-block bg-red-50 text-[#8b0000] text-xs font-semibold px-3.5 py-1.5 rounded-lg">
                    Shift {{ session('current_shift', '1A') }} aktif
                </span>
            </div>

            <!-- 2 Equal Red Shortcut Buttons -->
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('input_defect.create', ['type' => 'Final Assy']) }}" class="bg-[#8b0000] hover:bg-red-900 text-white rounded-xl px-3.5 py-3 text-xs font-bold flex items-center justify-between space-x-1 shadow-md transition-all">
                    <span class="leading-tight">Input Report<br>Final Assy</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('input_defect.create', ['type' => 'Pre Assy']) }}" class="bg-[#8b0000] hover:bg-red-900 text-white rounded-xl px-3.5 py-3 text-xs font-bold flex items-center justify-between space-x-1 shadow-md transition-all">
                    <span class="leading-tight">Input Report<br>Pre Assy</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>


            <!-- Section: Riwayat Report -->
            <section class="space-y-4">
                <h2 class="text-lg font-extrabold text-gray-900">Riwayat Report</h2>

                <div class="space-y-4">
                    @forelse($myDefects as $defect)
                        <div class="border border-gray-200 rounded-2xl p-5 bg-white space-y-3 relative shadow-sm">
                            
                            <!-- Card Header: Date, Type & Edit/Delete Icons -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="block text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                                        {{ \Carbon\Carbon::parse($defect->waktu)->format('d F Y') }}
                                    </span>
                                    <h3 class="text-base font-bold text-gray-900 mt-0.5">{{ $defect->jenis_assy }}</h3>
                                </div>

                                <div class="flex items-center space-x-2 text-[#8b0000]">
                                    <!-- Edit Link -->
                                    <a href="{{ route('input_defect.edit', $defect->id) }}" title="Edit" class="hover:text-red-900 transition-colors p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                    </a>

                                    <!-- Delete Form -->
                                    <form action="{{ route('input_defect.destroy', $defect->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus laporan defect ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus" class="hover:text-red-900 transition-colors p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <hr class="border-gray-100">

                            <!-- Field Stack Details -->
                            <div class="space-y-3">
                                <!-- Row 1: Defect & Quantity -->
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="block text-[11px] font-medium text-gray-400">Defect</span>
                                        <span class="block text-sm font-extrabold text-gray-900 uppercase mt-0.5">{{ $defect->jenis_defect }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-[11px] font-medium text-gray-400">Jumlah</span>
                                        <span class="block text-sm font-extrabold text-gray-900 mt-0.5">{{ $defect->quantity }} Unit</span>
                                    </div>
                                </div>

                                <!-- Row 2: Jenis Mobil -->
                                <div>
                                    <span class="block text-[11px] font-medium text-gray-400">Jenis Mobil</span>
                                    <span class="block text-sm font-extrabold text-gray-900 uppercase mt-0.5">{{ $defect->jenis_mobil }}</span>
                                </div>

                                <!-- Row 3: Conveyor -->
                                <div>
                                    <span class="block text-[11px] font-medium text-gray-400">Conveyor</span>
                                    <span class="block text-sm font-extrabold text-gray-900 uppercase mt-0.5">{{ $defect->conveyor }}</span>
                                </div>

                                <!-- Row 4: Sub-Defect -->
                                <div>
                                    <span class="block text-[11px] font-medium text-gray-400">Sub-Defect</span>
                                    <span class="block text-sm font-extrabold text-gray-900 uppercase mt-0.5">{{ $defect->jenis_sub_defect }}</span>
                                </div>

                                <!-- Additional fields if available (END # / Line) -->
                                @if($defect->end_number)
                                    <div>
                                        <span class="block text-[11px] font-medium text-gray-400">END (#)</span>
                                        <span class="block text-sm font-extrabold text-gray-900 mt-0.5">{{ $defect->end_number }}</span>
                                    </div>
                                @endif

                                @if($defect->no_terminal)
                                    <div>
                                        <span class="block text-[11px] font-medium text-gray-400">NO TERMINAL</span>
                                        <span class="block text-sm font-extrabold text-gray-900 mt-0.5">{{ $defect->no_terminal }}</span>
                                    </div>
                                @endif
                            </div>

                        </div>
                    @empty
                        <div class="text-center py-8 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                            <p class="text-xs font-semibold text-gray-400">Belum ada riwayat report defect.</p>
                        </div>
                    @endforelse
                </div>
            </section>

        </div>
    </main>

</body>
</html>
