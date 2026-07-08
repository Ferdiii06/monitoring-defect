<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Account - Sistem Monitoring Defect</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
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
                <a href="{{ route('account.create') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold bg-[#8b0000] text-white shadow-sm transition-all">
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
                <a href="{{ route('pre_assy.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    <span>Pre Assy</span>
                </a>
                <a href="{{ route('recent_defects.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    <span>Recent Defect</span>
                </a>
                <a href="{{ route('log_system.index') }}" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Log System</span>
                </a>
            </nav>
        </div>

        <!-- Logout Form -->
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
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Add Account</h1>
                <p class="text-sm text-gray-500 mt-1">Buat akun baru untuk pengguna sistem.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- User Profile Card -->
                <div class="flex items-center space-x-3 pl-6">
                    <div class="text-right">
                        <span class="block text-sm font-bold text-gray-900">{{ session('user_name', 'Admin QA') }}</span>
                        <span class="block text-xs font-semibold text-gray-400">Administrator</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 border border-gray-200">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </header>

        <!-- Form Card Container -->
        <section class="max-w-4xl bg-white border border-gray-100 rounded-lg p-8 shadow-sm">
            <form action="{{ route('account.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Validation Errors list -->
                @if ($errors->any())
                    <div class="bg-red-50 text-[#8b0000] text-xs font-semibold p-4 rounded-lg border border-red-200">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Inputs Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Input 1: Full Name -->
                    <div>
                        <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Masukkan full name" value="{{ old('name') }}" required
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] transition-colors bg-white">
                    </div>

                    <!-- Input 2: PIN (6 Digit) -->
                    <div>
                        <label for="pin" class="block text-sm font-bold text-gray-700 mb-2">PIN (6 Digit)</label>
                        <div class="relative">
                            <input type="password" id="pin" name="pin" placeholder="Masukkan PIN 6 digit" maxlength="6" required
                                class="w-full border border-gray-200 rounded-lg p-3 pr-10 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] transition-colors bg-white">
                            
                            <!-- Toggle Password Visibility Icon Button -->
                            <button type="button" onclick="togglePinVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                <!-- Eye SVG Icon -->
                                <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Input 3: Shift Dropdown -->
                    <div>
                        <label for="shift" class="block text-sm font-bold text-gray-700 mb-2">Shift</label>
                        <div class="relative">
                            <select id="shift" name="shift" required
                                class="w-full appearance-none border border-gray-200 rounded-lg p-3 pr-10 text-sm bg-white text-gray-600 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer">
                                <option value="" disabled selected>Pilih Shift</option>
                                <option value="1A" {{ old('shift') == '1A' ? 'selected' : '' }}>Shift 1A</option>
                                <option value="1B" {{ old('shift') == '1B' ? 'selected' : '' }}>Shift 1B</option>
                                <option value="2A" {{ old('shift') == '2A' ? 'selected' : '' }}>Shift 2A</option>
                                <option value="2B" {{ old('shift') == '2B' ? 'selected' : '' }}>Shift 2B</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Input 4: Role Dropdown -->
                    <div>
                        <label for="role" class="block text-sm font-bold text-gray-700 mb-2">Role</label>
                        <div class="relative">
                            <select id="role" name="role" required
                                class="w-full appearance-none border border-gray-200 rounded-lg p-3 pr-10 text-sm bg-white text-gray-600 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer">
                                <option value="" disabled selected>Pilih Role</option>
                                <option value="Admin" {{ old('role') == 'Admin' ? 'selected' : '' }}>Admin</option>
                                <option value="User" {{ old('role') == 'User' ? 'selected' : '' }}>User</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Spacer Divider Line -->
                <div class="border-t border-gray-100 pt-6 flex justify-end items-center">
                    
                    <!-- Batal Button -->
                    <a href="{{ route('dashboard') }}" class="px-6 py-2.5 border border-gray-200 hover:bg-gray-50 rounded-lg text-sm font-semibold text-gray-500 transition duration-200 text-center">
                        Batal
                    </a>
                    
                    <!-- Create Account Button -->
                    <button type="submit" class="ml-4 bg-[#8b0000] hover:bg-[#600000] text-white text-sm font-semibold py-2.5 px-6 rounded-lg flex items-center justify-center space-x-1.5 transition duration-200 shadow-sm">
                        <!-- Add User SVG Icon -->
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        <span>Create Account</span>
                    </button>
                </div>
            </form>
        </section>
    </main>

    <!-- Inline Script for Password Visibility Toggling -->
    <script>
        function togglePinVisibility() {
            const pinInput = document.getElementById('pin');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (pinInput.type === 'password') {
                pinInput.type = 'text';
                // Update to eye-off SVG icon representation
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                `;
            } else {
                pinInput.type = 'password';
                // Reset back to standard eye SVG representation
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
    </script>
</body>
</html>
