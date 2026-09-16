@php
    $isReportActive = request()->routeIs('final_assy.*') || request()->routeIs('pre_assy.*');
    $isRiwayatActive = request()->routeIs('recent_defects.*') || request()->routeIs('log_system.*');
    $isMasterActive = request()->routeIs('admin.master.*');
@endphp

<aside 
    x-data="{ 
        reportOpen: {{ $isReportActive ? 'true' : 'false' }},
        riwayatOpen: {{ $isRiwayatActive ? 'true' : 'false' }},
        masterOpen: {{ $isMasterActive ? 'true' : 'false' }},
        logoutModal: false
    }" 
    class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between shrink-0 h-full select-none"
>
    <div class="overflow-y-auto flex-1 py-1">
        <!-- Logo Section -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center space-x-3">
            <img class="w-14 h-14 -ml-2 -my-3 object-contain shrink-0 drop-shadow-sm" src="{{ asset('images/logo-yazaki.jpg') }}" alt="Yazaki Logo">
            <div>
                <span class="block text-[11px] font-extrabold text-[#8b0000] uppercase tracking-wider leading-none">Report Internal</span>
                <span class="block text-xs font-black text-gray-900 uppercase tracking-widest mt-1">Defect QA</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="p-3 space-y-1">
            <!-- 1. Dashboard -->
            <a href="{{ route('dashboard') }}" 
               class="group relative flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-[#8b0000] to-[#a30000] text-white shadow-sm shadow-red-950/20' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r bg-[#8b0000] transition-opacity {{ request()->routeIs('dashboard') ? 'opacity-0' : 'opacity-0 group-hover:opacity-100' }}"></span>
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="tracking-wide">Dashboard</span>
            </a>

            <!-- 2. Add Account -->
            <a href="{{ route('account.create') }}" 
               class="group relative flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('account.create') ? 'bg-gradient-to-r from-[#8b0000] to-[#a30000] text-white shadow-sm shadow-red-950/20' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r bg-[#8b0000] transition-opacity {{ request()->routeIs('account.create') ? 'opacity-0' : 'opacity-0 group-hover:opacity-100' }}"></span>
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('account.create') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                <span class="tracking-wide">Add Account</span>
            </a>

            <!-- Divider: Menu Laporan -->
            <div class="pt-3 pb-1">
                <div class="h-px bg-gray-100 mb-2 mx-1"></div>
                <span class="px-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Laporan &amp; Monitoring</span>
            </div>

            <!-- 3. Report (Dropdown) -->
            <div>
                <button type="button" @click="reportOpen = !reportOpen" 
                        class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-left {{ $isReportActive ? 'text-[#8b0000] bg-red-50/80 font-bold border border-red-100/70' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 shrink-0 {{ $isReportActive ? 'text-[#8b0000]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="tracking-wide">Report</span>
                    </div>
                    <svg class="w-4 h-4 transform transition-transform duration-300 ease-out {{ $isReportActive ? 'text-[#8b0000]' : 'text-gray-400' }}" :class="{ 'rotate-180': reportOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="reportOpen" 
                     x-collapse 
                     x-cloak 
                     class="mt-1 pl-3 pr-1 space-y-1 border-l-2 border-red-100 ml-5">
                    <a href="{{ route('final_assy.index') }}" 
                       class="group flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('final_assy.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('final_assy.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span>Final Assy</span>
                    </a>
                    <a href="{{ route('pre_assy.index') }}" 
                       class="group flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('pre_assy.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('pre_assy.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                        <span>Pre Assy</span>
                    </a>
                </div>
            </div>

            <!-- 4. Riwayat (Dropdown) -->
            <div>
                <button type="button" @click="riwayatOpen = !riwayatOpen" 
                        class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-left {{ $isRiwayatActive ? 'text-[#8b0000] bg-red-50/80 font-bold border border-red-100/70' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 shrink-0 {{ $isRiwayatActive ? 'text-[#8b0000]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="tracking-wide">Riwayat</span>
                    </div>
                    <svg class="w-4 h-4 transform transition-transform duration-300 ease-out {{ $isRiwayatActive ? 'text-[#8b0000]' : 'text-gray-400' }}" :class="{ 'rotate-180': riwayatOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="riwayatOpen" 
                     x-collapse 
                     x-cloak 
                     class="mt-1 pl-3 pr-1 space-y-1 border-l-2 border-red-100 ml-5">
                    <a href="{{ route('recent_defects.index') }}" 
                       class="group flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('recent_defects.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('recent_defects.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        <span>Recent Defect</span>
                    </a>
                    <a href="{{ route('log_system.index') }}" 
                       class="group flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('log_system.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('log_system.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Log System</span>
                    </a>
                </div>
            </div>

            <!-- Divider: Master Data -->
            <div class="pt-3 pb-1">
                <div class="h-px bg-gray-100 mb-2 mx-1"></div>
                <span class="px-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Master Data</span>
            </div>

            <!-- 5. Master Data (Dropdown with 2 Sub-groups) -->
            <div>
                <button type="button" @click="masterOpen = !masterOpen" 
                        class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-left {{ $isMasterActive ? 'text-[#8b0000] bg-red-50/80 font-bold border border-red-100/70' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 shrink-0 {{ $isMasterActive ? 'text-[#8b0000]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3s3.5-1 3.5-3V7c0-2-1.5-3-3.5-3S4 5 4 7zm9 0v10c0 2 1.5 3 3.5 3s3.5-1 3.5-3V7c0-2-1.5-3-3.5-3s-3.5 1-3.5 3z"></path></svg>
                        <span class="tracking-wide">Master Data</span>
                    </div>
                    <svg class="w-4 h-4 transform transition-transform duration-300 ease-out {{ $isMasterActive ? 'text-[#8b0000]' : 'text-gray-400' }}" :class="{ 'rotate-180': masterOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="masterOpen" 
                     x-collapse 
                     x-cloak 
                     class="mt-1 pl-3 pr-1 space-y-2 border-l-2 border-red-100 ml-5">
                    <!-- Sub-grup: Data Kendaraan -->
                    <div>
                        <span class="block px-3 py-1 text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Data Kendaraan</span>
                        <div class="space-y-1">
                            <a href="{{ route('admin.master.car_types.index') }}" 
                               class="group flex items-center space-x-2.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.master.car_types.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.master.car_types.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                                <span>Jenis Mobil</span>
                            </a>
                            <a href="{{ route('admin.master.carlines.index') }}" 
                               class="group flex items-center space-x-2.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.master.carlines.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.master.carlines.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3s3.5-1 3.5-3V7c0-2-1.5-3-3.5-3S4 5 4 7zm9 0v10c0 2 1.5 3 3.5 3s3.5-1 3.5-3V7c0-2-1.5-3-3.5-3s-3.5 1-3.5 3z"></path></svg>
                                <span>Carline / Konveyor</span>
                            </a>
                        </div>
                    </div>

                    <!-- Sub-grup: Data Defect & Inspect -->
                    <div>
                        <span class="block px-3 py-1 text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Data Defect &amp; Inspect</span>
                        <div class="space-y-1">
                            <a href="{{ route('admin.master.defect_types.index') }}" 
                               class="group flex items-center space-x-2.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.master.defect_types.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.master.defect_types.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span>Defect &amp; Sub-Defect</span>
                            </a>
                            <a href="{{ route('admin.master.inspect_process_types.index') }}" 
                               class="group flex items-center space-x-2.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.master.inspect_process_types.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.master.inspect_process_types.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                <span>Inspect Proses</span>
                            </a>
                            @if(\Illuminate\Support\Facades\Route::has('admin.master.final_assy_inspect_types.index'))
                                <a href="{{ route('admin.master.final_assy_inspect_types.index') }}" 
                                   class="group flex items-center space-x-2.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.master.final_assy_inspect_types.*') ? 'bg-[#8b0000] text-white shadow-sm shadow-red-950/20 font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70' }}">
                                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.master.final_assy_inspect_types.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Final Assy Inspect Type</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Logout Trigger Button -->
    <div class="p-3 border-t border-gray-100 bg-gray-50/40">
        <button type="button" @click="logoutModal = true" 
                class="group w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold text-gray-700 hover:text-brand hover:bg-red-50/80 border border-transparent hover:border-red-100 transition-all duration-200 text-left">
            <div class="flex items-center space-x-3">
                <span class="w-8 h-8 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 group-hover:bg-brand group-hover:text-white transition-all duration-200">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </span>
                <span class="font-bold tracking-wide">Logout</span>
            </div>
            <span class="text-[10px] text-gray-400 group-hover:text-brand font-medium">Keluar</span>
        </button>
    </div>

    <!-- Modal Konfirmasi Logout Menggunakan Reusable Component confirm-modal -->
    <x-confirm-modal 
        name="logoutModal"
        title="Konfirmasi Keluar Sesi"
        message="Apakah Anda yakin ingin logout? Anda harus memasukkan kredensial lagi untuk mengakses dashboard."
        confirmText="Ya, Logout"
        cancelText="Batal"
        :confirmAction="route('logout')"
        method="POST"
    />
</aside>
