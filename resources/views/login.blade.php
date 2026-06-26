<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Monitoring Defect</title>
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
<body class="bg-white min-h-screen flex flex-col items-center justify-center px-4">

    <!-- Login Container -->
    <div class="w-full max-w-md flex flex-col items-center">

        <!-- Logo Section -->
        <div class="flex flex-col items-center mb-8">
            <img class="h-10 w-auto" src="{{ asset('images/logo-yazaki.png') }}" alt="Yazaki Logo">
            <!-- Quality Subtitle -->
            <p class="text-[#a4262c]/70 text-xs font-semibold mt-14 tracking-wide text-center">
                Quality in Every Connection
            </p>
        </div>

        <!-- Form Section -->
        <form action="/login" method="POST" class="w-full max-w-xs space-y-4">
            @csrf

            <!-- Validation Error Alert -->
            @if($errors->any())
                <div class="bg-red-50 text-[#8b0000] text-[11px] font-semibold p-2.5 rounded-md border border-red-200 text-center">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Input Nama Lengkap -->
            <div>
                <label for="name" class="block text-xs font-bold text-[#a4262c] mb-1.5">
                    Nama Lengkap
                </label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#a4262c]/60">
                        <!-- User icon -->
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                        class="block w-full pl-10 pr-3 py-2 border border-[#a4262c] rounded-lg bg-white text-xs font-semibold text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#800000] focus:border-[#800000] transition-colors"
                        placeholder="Masukkan Nama Anda" required>
                </div>
            </div>

            <!-- Input PIN (6 Digit) -->
            <div>
                <label for="pin" class="block text-xs font-bold text-[#a4262c] mb-1.5">
                    PIN (6 Digit)
                </label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <!-- Lock icon -->
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password" id="pin" name="pin"
                        class="block w-full pl-10 pr-3 py-2 border border-[#a4262c] rounded-lg bg-gray-50 text-xs font-semibold text-gray-700 tracking-wider placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#800000] focus:border-[#800000] transition-colors"
                        placeholder="....." maxlength="6" required>
                </div>
            </div>

            <!-- Submit Button Section -->
            <div class="pt-3 flex justify-center">
                <button type="submit" class="bg-[#800000] hover:bg-[#600000] text-white text-xs font-bold py-2.5 px-6 rounded-lg flex items-center justify-center space-x-2 transition duration-200 shadow-sm">
                    <span>Masuk ke Sistem</span>
                    <!-- Login Arrow Icon -->
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l4-4m0 0l-4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>

</body>
</html>
