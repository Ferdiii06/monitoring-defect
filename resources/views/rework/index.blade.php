@extends('layouts.operator')

@section('title', 'Rework')

@section('content')
<div class="w-full">

    <!-- Mobile-First Container Card (Identik dengan Operator Home) -->
    <main class="w-full bg-surface rounded-3xl shadow-[0_4px_24px_-4px_rgba(0,0,0,0.06)] overflow-hidden border border-border flex flex-col">

        <!-- Header Row (Peletakan Nama & Logout seperti Internal Defect Operator) -->
        <header class="p-5 sm:p-6 pb-4 flex justify-between items-center border-b border-border bg-white">
            <div>
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Rework Workspace</span>
                <h1 class="text-xl font-black text-brand tracking-tight mt-1">{{ session('user_name', 'reworkA') }}</h1>
            </div>

            <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin logout?');">
                @csrf
                <button type="submit"
                        title="Logout"
                        class="text-gray-500 hover:text-brand p-2 rounded-xl hover:bg-red-50 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center touch-manipulation cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </button>
            </form>
        </header>

        <div class="p-5 sm:p-6 space-y-6">

            <!-- Active Shift Pill & Date & Role (Identik dengan Operator Home) -->
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center space-x-1.5 bg-red-50 text-brand border border-red-100 text-xs font-bold px-3 py-1.5 rounded-xl">
                        <span class="w-2 h-2 rounded-full bg-brand animate-pulse"></span>
                        <span>Shift {{ session('current_shift', '1A') }} aktif</span>
                    </span>
                    <span class="inline-flex items-center bg-gray-800 text-white font-bold text-[10px] px-2.5 py-1.5 rounded-xl uppercase tracking-wider">
                        Role: {{ session('user_role', 'Rework') }}
                    </span>
                </div>
                <span class="text-[11px] font-mono text-gray-400 font-semibold">{{ now()->format('d M Y') }}</span>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-2xl p-4 text-xs font-bold flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-rose-50 text-rose-800 border border-rose-200 rounded-2xl p-4 text-xs font-bold flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-rose-50 text-rose-800 border border-rose-200 rounded-2xl p-4 text-xs font-bold space-y-1">
                    <div class="flex items-center space-x-1.5 text-rose-900 font-extrabold mb-1">
                        <svg class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>Mohon periksa input berikut:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!$isAdmin)
                <!-- Section: Form Mulai Rework (In) - Hanya untuk role Rework -->
                <section class="space-y-4 pt-1">
                    <div class="flex items-center justify-between pb-2 border-b border-border">
                        <div>
                            <h2 class="text-base font-extrabold text-gray-900 tracking-tight flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-brand"></span>
                                <span>Mulai Rework (In)</span>
                            </h2>
                            <p class="text-[11px] text-gray-400 font-semibold mt-0.5">Scan / masukkan identitas harness untuk mulai rework</p>
                        </div>
                    </div>

                    <form id="rework_form" action="{{ route('rework.store_in') }}" method="POST" class="space-y-4">
                        @csrf

                        <!-- Field 1: Kanban Assy -->
                        <div class="space-y-1.5">
                            <label for="kanban_assy" class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                Kanban Assy <span class="text-brand">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                       id="kanban_assy"
                                       name="kanban_assy"
                                       class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 pr-10 text-base font-semibold text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all touch-manipulation"
                                       placeholder="Scan / ketik Kanban Assy"
                                       value="{{ old('kanban_assy') }}"
                                       autofocus
                                       autocomplete="off"
                                       required>
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Field 2: Nameplate -->
                        <div class="space-y-1.5">
                            <label for="nameplate" class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                Nameplate (NP) <span class="text-brand">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                       id="nameplate"
                                       name="nameplate"
                                       class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 pr-10 text-base font-semibold text-gray-900 placeholder:text-gray-400 uppercase focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all touch-manipulation"
                                       placeholder="Scan / ketik Nameplate"
                                       value="{{ old('nameplate') }}"
                                       autocomplete="off"
                                       required>
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Field 3: Defect Search -->
                        <div class="space-y-1.5">
                            <label for="defect_search" class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                Defect (Kode / Nama) <span class="text-brand">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                       id="defect_search"
                                       class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 pr-10 text-base font-semibold text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all touch-manipulation"
                                       placeholder="Ketik kode (1A) / nama defect"
                                       autocomplete="off">
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>

                                <!-- Autocomplete Dropdown List -->
                                <div id="dropdown_results"
                                     class="hidden absolute top-full left-0 right-0 bg-white border border-border rounded-xl shadow-xl mt-1.5 overflow-hidden z-50 max-h-56 overflow-y-auto divide-y divide-gray-100">
                                </div>
                            </div>
                        </div>

                        <!-- Selected Defect Chips -->
                        <div class="space-y-1.5">
                            <span class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider">Defect Terpilih:</span>
                            <div id="selected_defects_container" class="space-y-2"></div>
                        </div>

                        <!-- Submit Button (Primary Yazaki Brand) -->
                        <button type="submit"
                                class="w-full min-h-[50px] bg-brand hover:bg-brand-active text-white rounded-2xl p-3.5 text-base font-extrabold flex items-center justify-center space-x-2 shadow-sm shadow-brand/20 active:scale-95 transition-all cursor-pointer touch-manipulation">
                            <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Mulai Rework (In)</span>
                        </button>
                    </form>
                </section>
            @endif

            <!-- Section: Riwayat Rework -->
            <section class="space-y-3.5 pt-2 border-t border-border">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-extrabold text-gray-900 tracking-tight">Riwayat Rework</h2>
                    <span class="text-[11px] font-mono text-gray-400 font-bold">Terbaru di Atas</span>
                </div>

                <div class="space-y-3.5">
                    @forelse($paginatedGroups as $groupItem)
                        @php
                            $groupId = $groupItem->rework_group_id;
                            $items = $defectsByGroup->get($groupId, collect());
                            $first = $items->first();
                        @endphp

                        @if($first)
                            <div class="border border-border rounded-2xl p-4 sm:p-5 bg-surface space-y-3 relative shadow-[0_2px_12px_-2px_rgba(0,0,0,0.03)] hover:border-gray-300 transition-colors">
                                
                                <!-- Header Card: Kanban, Status Badge & Edit Icon -->
                                <div class="flex justify-between items-start gap-2 pb-2.5 border-b border-gray-100">
                                    <div class="space-y-0.5 min-w-0 flex-1">
                                        <span class="text-base font-black text-gray-900 tracking-tight block truncate">
                                            {{ $first->kanban_assy ?? '-' }}
                                        </span>
                                        <span class="text-xs font-bold font-mono text-gray-500 block truncate">
                                            NP: {{ $first->nameplate_lama ?? '-' }}
                                        </span>
                                        @if($isAdmin)
                                            <span class="text-[11px] font-semibold text-gray-600 block mt-0.5 truncate">
                                                Pembuat: <strong class="text-brand">{{ $first->user_name }}</strong> (Shift {{ $first->shift ?? '-' }})
                                            </span>
                                        @endif
                                    </div>

                                    <div class="shrink-0 flex items-center space-x-1 -mr-2 -mt-2">
                                        @if($first->status_rework === 'Selesai')
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>Selesai</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-50 text-amber-800 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span>Berjalan</span>
                                            </span>
                                        @endif

                                        <!-- Edit Icon Button (Pola Internal Defect) -->
                                        <a href="{{ route('rework.edit', $groupId) }}"
                                           title="Edit"
                                           aria-label="Edit rework"
                                           class="text-brand hover:text-brand-active hover:bg-red-50 active:bg-red-100 active:scale-95 transition-all p-3 rounded-xl min-w-[48px] min-h-[48px] flex items-center justify-center touch-manipulation cursor-pointer shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                <!-- Meta Times Grid -->
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                                    <div>
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Waktu In</span>
                                        <span class="font-extrabold text-gray-900 font-mono text-[11px]">
                                            {{ $first->waktu_in ? \Carbon\Carbon::parse($first->waktu_in)->format('d/m/Y H:i') : '-' }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Durasi</span>
                                        @if($first->status_rework === 'Selesai')
                                            <span class="font-extrabold text-emerald-700 text-[11px]">
                                                {{ $first->durasi_rework_menit ?? 0 }} menit
                                            </span>
                                        @else
                                            <span class="font-bold text-amber-600 text-[11px]">
                                                Sedang berjalan
                                            </span>
                                        @endif
                                    </div>

                                    @if($first->status_rework === 'Selesai')
                                        <div class="col-span-2 pt-1 border-t border-gray-200/60">
                                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Waktu Out</span>
                                            <span class="font-extrabold text-gray-900 font-mono text-[11px]">
                                                {{ $first->waktu_out ? \Carbon\Carbon::parse($first->waktu_out)->format('d/m/Y H:i') : '-' }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Defect List -->
                                <div class="space-y-1.5">
                                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Daftar Defect:</span>
                                    <div class="bg-gray-50/80 rounded-xl p-2.5 border border-gray-100 space-y-1.5">
                                        @foreach($items as $item)
                                            @php
                                                $code = $item->subDefectType?->code ?? \App\Support\SubDefectHelper::deriveCode($item->jenis_sub_defect) ?? '-';
                                                $name = $item->jenis_sub_defect ?? $item->subDefectType?->name ?? '-';
                                            @endphp
                                            <div class="flex items-start text-xs font-bold text-gray-800">
                                                <span class="bg-brand/10 text-brand border border-brand/20 font-black px-1.5 py-0.5 rounded text-[10px] mr-2 shrink-0">
                                                    [{{ $code }}]
                                                </span>
                                                <span class="leading-tight">{{ $name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Tombol Out (Hanya untuk Rework berstatus Berjalan) -->
                                @if(!$isAdmin && $first->status_rework === 'Berjalan')
                                    <div class="pt-1">
                                        <form action="{{ route('rework.store_out', $groupId) }}" method="POST" onsubmit="return confirm('Selesaikan rework untuk harness ini?');" class="w-full">
                                            @csrf
                                            <button type="submit"
                                                    class="w-full min-h-[48px] bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-sm rounded-xl flex items-center justify-center space-x-1.5 shadow-sm shadow-emerald-600/20 transition-all cursor-pointer touch-manipulation">
                                                <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span>Selesai (Out)</span>
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                <!-- Group UUID for Traceability and Audit (Hidden from visual UI) -->
                                <div class="hidden" aria-hidden="true">
                                    Group: {{ $groupId }}
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center py-10 bg-gray-50 rounded-2xl border border-dashed border-border">
                            <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="text-xs font-semibold text-gray-400">Belum ada riwayat rework.</p>
                        </div>
                    @endforelse
                </div>

                <div class="pt-2">
                    {{ $paginatedGroups->links() }}
                </div>
            </section>

        </div>
    </main>

</div>

@push('scripts')
<script>
    (function() {
        const reworkForm = document.getElementById('rework_form');
        if (!reworkForm) {
            return;
        }

        const finalAssyDefects = @json($finalAssySubDefects);
        let selectedDefects = [];

        // Restore old selected defects if validation failed
        @if(old('sub_defect_ids'))
            const oldIds = @json(old('sub_defect_ids'));
            if (Array.isArray(oldIds)) {
                oldIds.forEach(function(id) {
                    const match = finalAssyDefects.find(function(d) { return d.id == id; });
                    if (match) {
                        selectedDefects.push(match);
                    }
                });
            }
        @endif

        const kanbanInput = document.getElementById('kanban_assy');
        const npInput = document.getElementById('nameplate');
        const defectSearch = document.getElementById('defect_search');
        const dropdown = document.getElementById('dropdown_results');
        const chipsContainer = document.getElementById('selected_defects_container');

        // 1. Enter on Kanban input moves focus to Nameplate
        if (kanbanInput) {
            kanbanInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (npInput) npInput.focus();
                }
            });
        }

        // 2. Enter on Nameplate input moves focus to Defect Search
        if (npInput) {
            npInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (defectSearch) defectSearch.focus();
                }
            });
        }

        // Render chips
        function renderChips() {
            chipsContainer.innerHTML = '';
            if (selectedDefects.length === 0) {
                const emptyWrap = document.createElement('div');
                emptyWrap.className = 'text-xs text-gray-400 italic bg-gray-50/80 border border-dashed border-gray-200 rounded-xl p-3 text-center';
                emptyWrap.textContent = 'Belum ada defect dipilih';
                chipsContainer.appendChild(emptyWrap);
                return;
            }

            selectedDefects.forEach(function(item, index) {
                const chip = document.createElement('div');
                chip.className = 'flex items-center justify-between bg-red-50/70 border border-red-200/80 rounded-xl p-2.5 sm:p-3 text-xs sm:text-sm font-bold text-gray-900 shadow-2xs';

                const leftWrap = document.createElement('div');
                leftWrap.className = 'flex items-center min-w-0 pr-2';

                if (item.code) {
                    const codeBadge = document.createElement('span');
                    codeBadge.className = 'bg-brand text-white font-black px-2 py-0.5 rounded-md text-[11px] mr-2 shrink-0';
                    codeBadge.textContent = '[' + item.code + ']';
                    leftWrap.appendChild(codeBadge);
                }

                const nameText = document.createElement('span');
                nameText.className = 'truncate';
                nameText.textContent = item.name;
                leftWrap.appendChild(nameText);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'min-w-[36px] min-h-[36px] flex items-center justify-center text-gray-400 hover:text-brand hover:bg-white rounded-lg transition-colors cursor-pointer shrink-0 touch-manipulation';
                removeBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>';
                removeBtn.setAttribute('aria-label', 'Hapus defect');
                removeBtn.addEventListener('click', function(evt) {
                    evt.stopPropagation();
                    selectedDefects.splice(index, 1);
                    renderChips();
                });

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'sub_defect_ids[]';
                hiddenInput.value = item.id;

                chip.appendChild(leftWrap);
                chip.appendChild(removeBtn);
                chip.appendChild(hiddenInput);
                chipsContainer.appendChild(chip);
            });
        }

        renderChips();

        function getFiltered(query) {
            const q = query.trim().toLowerCase();
            if (!q) return [];
            return finalAssyDefects.filter(function(d) {
                const code = (d.code || '').toLowerCase();
                const name = (d.name || '').toLowerCase();
                return code === q || code.includes(q) || name.includes(q);
            });
        }

        function showDropdown(items) {
            dropdown.innerHTML = '';
            if (items.length === 0) {
                dropdown.classList.add('hidden');
                return;
            }

            items.slice(0, 10).forEach(function(item) {
                const div = document.createElement('div');
                div.className = 'px-3.5 py-3 hover:bg-red-50 text-sm font-semibold text-gray-800 flex items-center justify-between border-b border-gray-100 last:border-b-0 cursor-pointer min-h-[48px] touch-manipulation transition-colors';

                const left = document.createElement('div');
                left.className = 'flex items-center min-w-0 pr-2';
                if (item.code) {
                    const code = document.createElement('span');
                    code.className = 'bg-brand/10 text-brand font-black px-2 py-0.5 rounded text-xs mr-2 shrink-0';
                    code.textContent = '[' + item.code + ']';
                    left.appendChild(code);
                }
                const name = document.createElement('span');
                name.className = 'truncate text-xs sm:text-sm';
                name.textContent = item.name;
                left.appendChild(name);

                div.appendChild(left);
                div.addEventListener('click', function() {
                    addDefect(item);
                });
                dropdown.appendChild(div);
            });
            dropdown.classList.remove('hidden');
        }

        function addDefect(item) {
            // Prevent duplicate sub defect in same harness
            const exists = selectedDefects.some(function(d) { return d.id == item.id; });
            if (exists) {
                alert('Defect ini sudah dipilih.');
                dropdown.classList.add('hidden');
                defectSearch.value = '';
                defectSearch.focus();
                return;
            }

            selectedDefects.push(item);
            renderChips();
            defectSearch.value = '';
            dropdown.classList.add('hidden');
            defectSearch.focus();
        }

        if (defectSearch) {
            defectSearch.addEventListener('input', function() {
                const filtered = getFiltered(this.value);
                showDropdown(filtered);
            });

            defectSearch.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = this.value.trim().toLowerCase();
                    if (!val) return;

                    const filtered = getFiltered(val);
                    if (filtered.length > 0) {
                        const exact = filtered.find(function(d) {
                            return (d.code || '').toLowerCase() === val;
                        });
                        addDefect(exact || filtered[0]);
                    }
                }
            });
        }

        // Close dropdown if clicked outside
        document.addEventListener('click', function(e) {
            if (dropdown && defectSearch && !defectSearch.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Form validation before submit
        reworkForm.addEventListener('submit', function(e) {
            if (selectedDefects.length === 0) {
                e.preventDefault();
                alert('Minimal satu defect harus dipilih.');
                if (defectSearch) defectSearch.focus();
            }
        });
    })();
</script>
@endpush
@endsection
