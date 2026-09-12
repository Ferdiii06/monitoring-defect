<aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between shrink-0">
    <div>
        <!-- Logo Section -->
        <div class="p-4 border-b border-gray-100 flex items-center space-x-3">
            <img class="w-16 h-16 -ml-3 -my-4 object-contain shrink-0" src="{{ asset('images/logo-yazaki.jpg') }}" alt="Yazaki Logo">
            <div>
                <span class="block text-xs font-bold text-[#8b0000] uppercase tracking-wider leading-none">Report Internal</span>
                <span class="block text-xs font-bold text-gray-900 uppercase tracking-wider mt-0.5">Defect</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="p-3 space-y-1">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('dashboard') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('account.create') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('account.create') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                <span>Add Account</span>
            </a>
            <a href="{{ route('final_assy.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('final_assy.index') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Final Assy</span>
            </a>
            <a href="{{ route('pre_assy.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('pre_assy.index') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                <span>Pre Assy</span>
            </a>
            <a href="{{ route('recent_defects.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('recent_defects.index') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                <span>Recent Defect</span>
            </a>
            <a href="{{ route('log_system.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('log_system.index') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Log System</span>
            </a>

            <!-- Divider Master Data -->
            <div class="pt-3 pb-1">
                <span class="px-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Master Data</span>
            </div>
            <a href="{{ route('admin.master.car_types.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.master.car_types.*') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                <span>Jenis Mobil</span>
            </a>
            <a href="{{ route('admin.master.carlines.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.master.carlines.*') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3s3.5-1 3.5-3V7c0-2-1.5-3-3.5-3S4 5 4 7zm9 0v10c0 2 1.5 3 3.5 3s3.5-1 3.5-3V7c0-2-1.5-3-3.5-3s-3.5 1-3.5 3z"></path></svg>
                <span>Carline / Konveyor</span>
            </a>
            <a href="{{ route('admin.master.defect_types.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.master.defect_types.*') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>Defect & Sub-Defect</span>
            </a>
            <a href="{{ route('admin.master.inspect_process_types.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.master.inspect_process_types.*') ? 'bg-[#8b0000] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }} transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                <span>Inspect Proses</span>
            </a>
        </nav>
    </div>

    <!-- Logout Form -->
    <div class="p-3 border-t border-gray-100">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-[#8b0000] hover:bg-red-50 hover:text-[#600000] transition-all text-left">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
