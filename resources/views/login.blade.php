<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Monitoring Defect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#990000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo-yazaki.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 via-gray-50 to-red-50/40 min-h-screen flex items-center justify-center p-4">

    <!-- Card Login Container -->
    <div class="w-full max-w-md bg-white rounded-3xl border border-gray-100/80 p-8 sm:p-9 shadow-2xl relative overflow-hidden">

        <!-- Decorative Top Red Accent Bar -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#8b0000] via-red-600 to-[#8b0000]"></div>

        <!-- Logo & Title Section -->
        <div class="flex flex-col items-center mb-6 text-center">
            <div class="p-3 bg-red-50/60 rounded-2xl mb-3 flex items-center justify-center">
                <img class="h-12 object-contain" src="{{ asset('images/logo-yazaki.jpg') }}" alt="Yazaki Logo">
            </div>
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">Report Internal Defect</h1>
            <p id="role-subtitle" class="text-xs text-gray-500 font-medium mt-1">Silakan masuk sebagai Operator</p>
        </div>

        <!-- Role Toggle Tabs (Operator / Rework / Admin) -->
        <div class="grid grid-cols-3 p-1 bg-gray-100 rounded-2xl mb-5 text-xs font-bold gap-1 border border-gray-200/70" role="tablist">
            <button type="button" id="tab-user" onclick="setRole('user', true)"
                class="py-2.5 rounded-xl transition-all duration-200 text-center {{ old('login_type', 'user') === 'user' ? 'bg-white text-[#8b0000] shadow-sm font-bold' : 'text-gray-500 hover:text-gray-900 font-semibold' }} border border-transparent">
                Operator
            </button>
            <button type="button" id="tab-rework" onclick="setRole('rework', true)"
                class="py-2.5 rounded-xl transition-all duration-200 text-center {{ old('login_type') === 'rework' ? 'bg-amber-50 text-amber-700 border-amber-200 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-900 font-semibold border-transparent' }} border">
                Rework
            </button>
            <button type="button" id="tab-admin" onclick="setRole('admin', true)"
                class="py-2.5 rounded-xl transition-all duration-200 text-center {{ old('login_type') === 'admin' ? 'bg-white text-[#8b0000] shadow-sm font-bold' : 'text-gray-500 hover:text-gray-900 font-semibold' }} border border-transparent">
                Admin
            </button>
        </div>

        <!-- Validation Error Alert -->
        @if($errors->any())
            <div class="mb-5 bg-red-50 text-[#8b0000] text-xs font-semibold p-3.5 rounded-xl border border-red-200 text-center flex items-center justify-center space-x-2">
                <svg class="w-4 h-4 shrink-0 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Main Form (100% Offline Native JS Controlled) -->
        <form action="{{ route('login.post') }}" method="POST" class="space-y-4 w-full">
            @csrf
            <input type="hidden" name="login_type" id="login_type" value="{{ old('login_type', 'user') }}">

            <!-- Field 1: Operator / Rework Name -->
            <div id="operator-field-group">
                <label for="name" class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                    <span id="name-label-text">Nama Lengkap</span> <span class="text-[#8b0000]">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <input type="text" id="name" name="name" required autocomplete="off"
                        value="{{ old('name') }}"
                        placeholder="Masukkan Nama Anda"
                        class="w-full pl-10 pr-3.5 py-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-semibold text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition-all">
                </div>
            </div>

            <!-- Field 1: Admin Username -->
            <div id="admin-field-group" class="hidden">
                <label for="admin_name" class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                    Username Admin <span class="text-[#8b0000]">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <input type="text" id="admin_name" name="name" disabled value="{{ old('name') }}" autocomplete="off"
                        placeholder="Masukkan Username Admin"
                        class="w-full pl-10 pr-3.5 py-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-semibold text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition-all">
                </div>
            </div>

            <!-- Field 2: Shift Picker (Row Horizontal 4 Pill Sejajar) -->
            <div id="shift-field-group">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                    Pilih Shift <span class="text-[#8b0000]">*</span>
                </label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="shift-card flex items-center justify-center py-2.5 px-2 rounded-xl border border-[#8b0000] bg-red-50/80 text-[#8b0000] font-bold text-xs cursor-pointer shadow-sm transition-all text-center">
                        <input type="radio" name="shift" value="1A" class="sr-only" {{ old('shift', '1A') === '1A' ? 'checked' : '' }} onchange="updateShiftStyle()">
                        <span>1A</span>
                    </label>
                    <label class="shift-card flex items-center justify-center py-2.5 px-2 rounded-xl border border-gray-200 bg-gray-50/80 text-gray-700 text-xs font-semibold cursor-pointer hover:bg-gray-100 transition-all text-center">
                        <input type="radio" name="shift" value="1B" class="sr-only" {{ old('shift') === '1B' ? 'checked' : '' }} onchange="updateShiftStyle()">
                        <span>1B</span>
                    </label>
                    <label class="shift-card flex items-center justify-center py-2.5 px-2 rounded-xl border border-gray-200 bg-gray-50/80 text-gray-700 text-xs font-semibold cursor-pointer hover:bg-gray-100 transition-all text-center">
                        <input type="radio" name="shift" value="2A" class="sr-only" {{ old('shift') === '2A' ? 'checked' : '' }} onchange="updateShiftStyle()">
                        <span>2A</span>
                    </label>
                    <label class="shift-card flex items-center justify-center py-2.5 px-2 rounded-xl border border-gray-200 bg-gray-50/80 text-gray-700 text-xs font-semibold cursor-pointer hover:bg-gray-100 transition-all text-center">
                        <input type="radio" name="shift" value="2B" class="sr-only" {{ old('shift') === '2B' ? 'checked' : '' }} onchange="updateShiftStyle()">
                        <span>2B</span>
                    </label>
                </div>
            </div>

            <!-- Field 3: PIN (6 DIGIT) -->
            <div>
                <label for="pin" class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                    <span id="pin-label-text">PIN (6 DIGIT)</span> <span class="text-[#8b0000]">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input type="password" id="pin" name="pin" maxlength="6" placeholder="••••••" required autocomplete="new-password"
                        class="w-full pl-10 pr-3.5 py-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-semibold tracking-[0.3em] text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] transition-all">
                </div>
            </div>

            <!-- Button Group -->
            <div class="pt-3">
                <!-- Tombol Submit Utama -->
                <button type="submit" id="btn-submit-main" class="w-full bg-gradient-to-r from-[#8b0000] to-red-900 hover:from-red-900 hover:to-[#600000] text-white font-bold py-3.5 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center space-x-2 text-sm uppercase tracking-wider active:scale-[0.99]">
                    <span id="btn-submit-label">Masuk sebagai Operator</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </form>

        <!-- Footer -->
        <div class="mt-6 text-center border-t border-gray-100 pt-4">
            <p class="text-[11px] font-medium text-gray-400">© Yazaki Report Internal Defect System</p>
        </div>
    </div>

    <!-- Offline Vanilla JS Logic -->
    <script>
        const allowedRoles = ['user', 'rework', 'admin'];
        const initialRole = '{{ old('login_type', 'user') }}';
        let currentRole = allowedRoles.includes(initialRole) ? initialRole : 'user';

        function setRole(role, shouldFocus = false) {
            currentRole = allowedRoles.includes(role) ? role : 'user';

            const loginTypeInput = document.getElementById('login_type');
            const roleSubtitle = document.getElementById('role-subtitle');
            const operatorGroup = document.getElementById('operator-field-group');
            const adminGroup = document.getElementById('admin-field-group');
            const shiftGroup = document.getElementById('shift-field-group');
            const operatorInput = document.getElementById('name');
            const adminInput = document.getElementById('admin_name');
            const nameLabelText = document.getElementById('name-label-text');
            const pinLabelText = document.getElementById('pin-label-text');
            const submitLabel = document.getElementById('btn-submit-label');
            const pinInput = document.getElementById('pin');

            const tabUser = document.getElementById('tab-user');
            const tabRework = document.getElementById('tab-rework');
            const tabAdmin = document.getElementById('tab-admin');

            loginTypeInput.value = currentRole;

            // Reset tab styles
            [tabUser, tabRework, tabAdmin].forEach(tab => {
                tab.className = 'py-2.5 rounded-xl transition-all duration-200 text-center text-gray-500 hover:text-gray-900 font-semibold border border-transparent';
            });

            if (currentRole === 'user') {
                tabUser.className = 'py-2.5 rounded-xl transition-all duration-200 text-center bg-white text-[#8b0000] shadow-sm font-bold border border-transparent';
                roleSubtitle.textContent = 'Silakan masuk sebagai Operator';
                if (nameLabelText) nameLabelText.textContent = 'Nama Lengkap';
                if (pinLabelText) pinLabelText.textContent = 'PIN (6 DIGIT)';

                operatorGroup.classList.remove('hidden');
                shiftGroup.classList.remove('hidden');
                adminGroup.classList.add('hidden');

                operatorInput.disabled = false;
                operatorInput.required = true;
                operatorInput.placeholder = 'Masukkan Nama Anda';
                adminInput.disabled = true;
                adminInput.required = false;

                pinInput.setAttribute('inputmode', 'numeric');
                pinInput.setAttribute('pattern', '[0-9]*');
                pinInput.maxLength = 6;

                submitLabel.textContent = 'Masuk sebagai Operator';
            } else if (currentRole === 'rework') {
                tabRework.className = 'py-2.5 rounded-xl transition-all duration-200 text-center bg-amber-50 text-amber-700 border border-amber-200 shadow-sm font-bold';
                roleSubtitle.textContent = 'Silakan masuk sebagai Rework';
                if (nameLabelText) nameLabelText.textContent = 'Nama Akun Rework';
                if (pinLabelText) pinLabelText.textContent = 'PIN (6 DIGIT)';

                operatorGroup.classList.remove('hidden');
                shiftGroup.classList.remove('hidden');
                adminGroup.classList.add('hidden');

                operatorInput.disabled = false;
                operatorInput.required = true;
                operatorInput.placeholder = 'Masukkan Nama Akun Rework';
                adminInput.disabled = true;
                adminInput.required = false;

                pinInput.setAttribute('inputmode', 'numeric');
                pinInput.setAttribute('pattern', '[0-9]*');
                pinInput.maxLength = 6;

                submitLabel.textContent = 'Masuk sebagai Rework';
            } else {
                tabAdmin.className = 'py-2.5 rounded-xl transition-all duration-200 text-center bg-white text-[#8b0000] shadow-sm font-bold border border-transparent';
                roleSubtitle.textContent = 'Silakan masuk sebagai Administrator';
                if (pinLabelText) pinLabelText.textContent = 'PIN ADMIN';

                operatorGroup.classList.add('hidden');
                shiftGroup.classList.add('hidden');
                adminGroup.classList.remove('hidden');

                operatorInput.disabled = true;
                operatorInput.required = false;
                adminInput.disabled = false;
                adminInput.required = true;

                pinInput.removeAttribute('inputmode');
                pinInput.removeAttribute('pattern');
                pinInput.maxLength = 20;

                submitLabel.textContent = 'Masuk sebagai Admin';
            }

            if (shouldFocus) {
                if (currentRole === 'admin') {
                    adminInput.focus();
                } else {
                    operatorInput.focus();
                }
            }
        }

        function updateShiftStyle() {
            const shiftCards = document.querySelectorAll('.shift-card');
            shiftCards.forEach(card => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio.checked) {
                    card.className = 'shift-card flex items-center justify-center py-2.5 px-2 rounded-xl border border-[#8b0000] bg-red-50/80 text-[#8b0000] font-bold text-xs cursor-pointer shadow-sm transition-all text-center';
                } else {
                    card.className = 'shift-card flex items-center justify-center py-2.5 px-2 rounded-xl border border-gray-200 bg-gray-50/80 text-gray-700 text-xs font-semibold cursor-pointer hover:bg-gray-100 transition-all text-center';
                }
            });
        }

        function initLoginPage() {
            setRole(currentRole, false);
            updateShiftStyle();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLoginPage);
        } else {
            initLoginPage();
        }
    </script>
</body>
</html>
