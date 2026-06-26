<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Monitoring Defect</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-white min-h-screen flex flex-col items-center justify-center font-sans text-gray-800">

    <div class="w-full max-w-sm px-6">

        <div class="mb-10 text-center flex flex-col items-center">
           <img class="h-12 w-auto" src="{{ asset('images/logo-yazaki.png') }}" alt="Logo Yazaki">
            <p class="text-[#a4262c] text-xs font-medium tracking-wide">
                Quality in Every Connection
            </p>
        </div>

        <form action="#" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-xs font-medium text-[#a4262c] mb-1">
                    Nama Lengkap
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input type="text" id="name" name="name"
                        class="block w-full pl-10 pr-3 py-2 border border-[#a4262c] rounded-md bg-[#fdfbfb] text-sm focus:outline-none focus:ring-1 focus:ring-[#800000] focus:border-[#800000] placeholder-gray-400 transition-colors"
                        placeholder="Masukkan Nama Anda" required>
                </div>
            </div>

            <div>
                <label for="pin" class="block text-xs font-medium text-[#a4262c] mb-1">
                    PIN (6 Digit)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input type="password" id="pin" name="pin"
                        class="block w-full pl-10 pr-3 py-2 border border-[#a4262c] rounded-md bg-[#fdfbfb] text-sm tracking-widest focus:outline-none focus:ring-1 focus:ring-[#800000] focus:border-[#800000] placeholder-gray-400 transition-colors"
                        placeholder="••••••" maxlength="6" required>
                </div>
            </div>

            <div class="pt-4 flex justify-center">
                <button type="submit" class="bg-[#8b0000] hover:bg-[#600000] text-white text-sm font-semibold py-2 px-5 rounded-md flex items-center justify-center space-x-2 transition duration-200 shadow-sm">
                    <span>Masuk ke Sistem</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </form>
    </div>

</body>
</html>
