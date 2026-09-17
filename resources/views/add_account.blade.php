<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Account - Sistem Monitoring Defect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 h-screen overflow-hidden flex" x-data="{ 
    editModal: false, 
    editId: null, 
    editName: '', 
    editRole: 'User', 
    editPin: '',
    createRole: '{{ old('role', 'User') }}'
}">

    <!-- Left Sidebar -->
    @include('partials.sidebar')

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto px-10 py-8 flex flex-col justify-start">
        
        <!-- Header Section -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Add Account</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola data pengguna sistem (Administrator dan Operator).</p>
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

        <!-- Alerts -->
        @if (session('success'))
            <div class="mb-6 bg-green-50 text-green-700 text-sm font-semibold p-4 rounded-lg border border-green-200 flex items-center space-x-2 shadow-sm">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-50 text-[#8b0000] text-xs font-semibold p-4 rounded-lg border border-red-200">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Layout Grid: Form Tambah (Kiri) + Tabel List (Kanan) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Form Tambah Card -->
            <section class="bg-white border border-gray-100 rounded-lg p-6 shadow-sm h-fit">
                <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-gray-100">
                    <svg class="w-5 h-5 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <h2 class="text-sm font-bold text-gray-900">Tambah Akun</h2>
                </div>

                <form action="{{ route('account.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" placeholder="Masukkan full name" value="{{ old('name') }}" required
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Role <span class="text-red-500">*</span></label>
                        <select id="role" name="role" x-model="createRole" required
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white text-gray-700 cursor-pointer">
                            <option value="Administrator">Administrator</option>
                            <option value="User">User</option>
                        </select>
                    </div>

                    <div>
                        <label for="pin" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">PIN <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="pin" name="pin" :placeholder="createRole === 'User' ? 'PIN 6 digit' : 'Min. 4 karakter'" :maxlength="createRole === 'User' ? 6 : 20" required
                                class="w-full border border-gray-200 rounded-lg p-3 pr-10 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                            <button type="button" onclick="togglePinVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1" x-text="createRole === 'User' ? 'Wajib 6 digit angka untuk operator.' : 'Minimal 4 karakter untuk administrator.'"></p>
                    </div>

                    <button type="submit" class="w-full bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold py-3 rounded-lg transition duration-200 shadow-sm flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        <span>Simpan Akun</span>
                    </button>
                </form>
            </section>

            <!-- Tabel List Card -->
            <section class="lg:col-span-2 bg-white border border-gray-100 rounded-lg p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">Daftar Akun ({{ count($users) }})</h2>
                    </div>

                    <div class="overflow-x-auto min-h-[300px]">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 pl-2 w-12">No</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">Nama</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 text-center">Role</th>
                                    <th class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4 text-center pr-2 w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $index => $user)
                                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3.5 text-xs text-gray-500 px-4 pl-2 font-medium">{{ $index + 1 }}</td>
                                        <td class="py-3.5 text-sm font-bold text-gray-900 px-4">
                                            {{ $user->name }}
                                        </td>
                                        <td class="py-3.5 text-xs px-4 text-center font-semibold">
                                            @if($user->role === 'Administrator')
                                                <span class="inline-block bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-0.5 rounded-full font-bold">
                                                    Administrator
                                                </span>
                                            @else
                                                <span class="inline-block bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 rounded-full font-bold">
                                                    User
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 text-center px-4 pr-2">
                                            <div class="inline-flex items-center space-x-1.5">
                                                <!-- Tombol Edit -->
                                                <button type="button" 
                                                    @click="editModal = true; editId = {{ $user->id }}; editName = '{{ addslashes($user->name) }}'; editRole = '{{ addslashes($user->role) }}'; editPin = '';"
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-[#8b0000] hover:bg-red-900 text-white text-xs font-semibold transition-colors">
                                                    Edit
                                                </button>
                                                <!-- Tombol Hapus -->
                                                <form action="{{ route('account.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ addslashes($user->name) }}?');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-red-100 hover:bg-red-200 text-red-700 text-xs font-semibold transition-colors">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 text-center text-xs text-gray-400 font-medium">Belum ada data akun.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>

    </main>

    <!-- Modal Edit Akun -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100" @click.away="editModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Edit Akun</h3>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('/add-account') }}/' + editId" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="editName" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Role <span class="text-red-500">*</span></label>
                    <select name="role" x-model="editRole" required
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white text-gray-700">
                        <option value="Administrator">Administrator</option>
                        <option value="User">User</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">PIN Baru <span class="text-xs text-gray-400 font-normal">(Kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="pin" x-model="editPin" :placeholder="editRole === 'User' ? '6 digit angka (opsional)' : 'Min. 4 karakter (opsional)'"
                        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-[#8b0000] hover:bg-red-900 rounded-lg transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inline Script for Password Visibility Toggle -->
    <script>
        function togglePinVisibility() {
            const pinInput = document.getElementById('pin');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (pinInput.type === 'password') {
                pinInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                `;
            } else {
                pinInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
    </script>
</body>
</html>
