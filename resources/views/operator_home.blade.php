@extends('layouts.operator')

@section('title', 'Operator Home')

@section('content')
<div x-data="{ logoutModal: false }" class="w-full">

    <!-- Mobile-First Container Card -->
    <main class="w-full bg-surface rounded-3xl shadow-[0_4px_24px_-4px_rgba(0,0,0,0.06)] overflow-hidden border border-border flex flex-col">

        <!-- Header Row -->
        <header class="p-5 sm:p-6 pb-4 flex justify-between items-center border-b border-border bg-white">
            <div>
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Operator Workspace</span>
                <h1 class="text-xl font-black text-brand tracking-tight mt-1">{{ session('user_name', 'Operator QA') }}</h1>
            </div>
            
            <button type="button" @click="logoutModal = true" title="Logout" class="text-gray-500 hover:text-brand p-2 rounded-xl hover:bg-red-50 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center touch-manipulation">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </button>
        </header>

        <div class="p-5 sm:p-6 space-y-6">

            <!-- Success Alert Banner -->
            @if(session('success'))
                <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-2xl p-4 text-xs font-bold flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Active Shift Pill -->
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center space-x-1.5 bg-red-50 text-brand border border-red-100 text-xs font-bold px-3 py-1.5 rounded-xl">
                    <span class="w-2 h-2 rounded-full bg-brand animate-pulse"></span>
                    <span>Shift {{ session('current_shift', '1A') }} aktif</span>
                </span>
                <span class="text-[11px] font-mono text-gray-400 font-semibold">{{ now()->format('d M Y') }}</span>
            </div>

            <!-- 2 Equal Red Shortcut Buttons (Tap target min 48px) -->
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('input_defect.create', ['type' => 'Final Assy']) }}" class="min-h-[52px] bg-brand hover:bg-brand-active text-white rounded-2xl p-3.5 text-xs font-bold flex items-center justify-between space-x-1 shadow-sm shadow-brand/20 active:scale-95 transition-all">
                    <span class="leading-tight text-left">Input Report<br><span class="text-[10px] text-red-200 font-semibold uppercase tracking-wider">Final Assy</span></span>
                    <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('input_defect.create', ['type' => 'Pre Assy']) }}" class="min-h-[52px] bg-brand hover:bg-brand-active text-white rounded-2xl p-3.5 text-xs font-bold flex items-center justify-between space-x-1 shadow-sm shadow-brand/20 active:scale-95 transition-all">
                    <span class="leading-tight text-left">Input Report<br><span class="text-[10px] text-red-200 font-semibold uppercase tracking-wider">Pre Assy</span></span>
                    <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>

            <!-- Section: Riwayat Report -->
            <section class="space-y-3.5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-extrabold text-gray-900 tracking-tight">Riwayat Report</h2>
                    <span class="text-[11px] font-mono text-gray-400 font-bold">10 Terakhir</span>
                </div>

                <div class="space-y-3.5">
                    @forelse($myDefects as $defect)
                        <div class="border border-border rounded-2xl p-4 sm:p-5 bg-surface space-y-3 relative shadow-[0_2px_12px_-2px_rgba(0,0,0,0.03)] hover:border-gray-300 transition-colors">
                            
                            <!-- Card Header: Date, Status Badge & Edit/Delete Icons -->
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <span class="block text-[10px] font-bold font-mono tracking-wider text-gray-400 uppercase">
                                        {{ \Carbon\Carbon::parse($defect->waktu)->format('d F Y, H:i') }}
                                    </span>
                                    <div class="flex items-center space-x-2">
                                        <x-status-badge :type="$defect->jenis_assy === 'Final Assy' ? 'final-assy' : 'pre-assy'">
                                            {{ $defect->jenis_assy }}
                                        </x-status-badge>
                                        @if($defect->jenis_mobil)
                                            <span class="text-[11px] font-bold text-gray-900 tracking-wide">{{ $defect->jenis_mobil }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center space-x-1.5 text-brand">
                                    <!-- Edit Link -->
                                    <a href="{{ route('input_defect.edit', $defect->id) }}" title="Edit" class="hover:text-brand-active p-2 rounded-lg hover:bg-red-50 transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center touch-manipulation">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                    </a>

                                    <!-- Delete Form -->
                                    <form action="{{ route('input_defect.destroy', $defect->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus laporan defect ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus" class="hover:text-brand-active p-2 rounded-lg hover:bg-red-50 transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center touch-manipulation">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="h-px bg-gray-100"></div>

                            <!-- Field Stack Details -->
                            <div class="space-y-2.5 text-xs">
                                <!-- Row 1: Defect & Quantity -->
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Defect</span>
                                        <span class="block text-xs font-black text-gray-900 uppercase font-mono mt-0.5">{{ $defect->jenis_defect }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Jumlah</span>
                                        <span class="block text-sm font-black text-brand font-mono tabular-nums mt-0.5">{{ $defect->quantity }} Unit</span>
                                    </div>
                                </div>

                                <!-- Row 2: Sub-Defect -->
                                <div>
                                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sub-Defect</span>
                                    <span class="block text-xs font-semibold text-gray-800 mt-0.5">{{ $defect->jenis_sub_defect }}</span>
                                </div>

                                <!-- Row 3: Conveyor / Line -->
                                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-50">
                                    <div>
                                        <span class="block text-[10px] font-medium text-gray-400 uppercase">Conveyor/Carline</span>
                                        <span class="block text-xs font-bold text-gray-900 mt-0.5">{{ $defect->carline?->name ?? $defect->conveyor ?? '-' }}</span>
                                    </div>
                                    @if($defect->jenis_mobil === 'MAZDA' && $defect->final_inspect_type_id && $defect->finalInspectType?->name)
                                        <div>
                                            <span class="block text-[10px] font-medium text-gray-400 uppercase">Inspect Type</span>
                                            <span class="block text-xs font-bold text-final-assy mt-0.5">{{ $defect->finalInspectType->name }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Additional info tags if exists -->
                                @if($defect->end_number || $defect->no_terminal || $defect->no_mesin)
                                    <div class="flex flex-wrap gap-2 pt-1 text-[11px]">
                                        @if($defect->end_number)
                                            <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono font-semibold">END: {{ $defect->end_number }}</span>
                                        @endif
                                        @if($defect->no_terminal)
                                            <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono font-semibold">Term: {{ $defect->no_terminal }}</span>
                                        @endif
                                        @if($defect->no_mesin)
                                            <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono font-semibold">Mesin: {{ $defect->no_mesin }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                        </div>
                    @empty
                        <div class="text-center py-10 bg-gray-50 rounded-2xl border border-dashed border-border">
                            <p class="text-xs font-semibold text-gray-400">Belum ada riwayat report defect.</p>
                        </div>
                    @endforelse
                </div>
            </section>

        </div>
    </main>

    <!-- Modal Konfirmasi Logout -->
    <x-confirm-modal 
        name="logoutModal"
        title="Konfirmasi Logout"
        message="Apakah Anda yakin ingin keluar dari sistem pelaporan defect?"
        confirmText="Ya, Logout"
        cancelText="Batal"
        :confirmAction="route('logout')"
        method="POST"
    />

</div>
@endsection
