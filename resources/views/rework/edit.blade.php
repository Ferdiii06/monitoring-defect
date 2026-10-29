@extends('layouts.operator')

@section('title', 'Edit Riwayat Harness')

@section('content')
<div class="w-full">

    <main class="w-full bg-surface rounded-3xl shadow-[0_4px_24px_-4px_rgba(0,0,0,0.06)] overflow-hidden border border-border flex flex-col">

        <!-- Header: Back Button + Title -->
        <header class="p-5 sm:p-6 pb-4 flex items-center space-x-3 border-b border-border bg-white">
            <a href="{{ route('rework.index') }}"
               class="text-brand hover:text-brand-active p-2 -ml-2 rounded-xl hover:bg-red-50 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center touch-manipulation cursor-pointer"
               title="Kembali ke Riwayat">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Rework QA • Edit</span>
                <h1 class="text-xl font-black text-brand tracking-tight mt-1">Edit Riwayat Harness</h1>
            </div>
        </header>

        <div class="p-5 sm:p-6 space-y-5">

            <!-- Read-only Metadata Card -->
            <div class="bg-gray-50/80 rounded-2xl p-4 border border-gray-200/80 space-y-2 text-xs">
                <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                    <span class="text-gray-500 font-bold uppercase tracking-wider text-[10px]">Informasi Rework (Terkunci)</span>
                    @if($first->status_rework === 'Selesai')
                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Selesai</span>
                        </span>
                    @else
                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <span>Berjalan</span>
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2 text-gray-700">
                    <div>
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pemilik Akun</span>
                        <span class="font-extrabold text-gray-900">{{ $first->user_name }} (Shift {{ $first->shift ?? '-' }})</span>
                    </div>

                    <div>
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Waktu In</span>
                        <span class="font-mono font-bold text-gray-900">{{ $first->waktu_in ? \Carbon\Carbon::parse($first->waktu_in)->format('d/m/Y H:i') : '-' }}</span>
                    </div>

                    @if($first->status_rework === 'Selesai')
                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Waktu Out</span>
                            <span class="font-mono font-bold text-gray-900">{{ $first->waktu_out ? \Carbon\Carbon::parse($first->waktu_out)->format('d/m/Y H:i') : '-' }}</span>
                        </div>

                        <div>
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Durasi</span>
                            <span class="font-bold text-emerald-700">{{ $first->durasi_rework_menit ?? 0 }} menit</span>
                        </div>
                    @endif
                </div>

                <div class="text-[10px] font-mono text-gray-400 pt-1 border-t border-gray-200 truncate">
                    Group: {{ $groupId }}
                </div>
            </div>

            <!-- Flash Error / Validation Messages -->
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

            <!-- Edit Form -->
            <form id="rework_edit_form" action="{{ route('rework.update', $groupId) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

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
                               value="{{ old('kanban_assy', $first->kanban_assy) }}"
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
                               value="{{ old('nameplate', $first->nameplate_lama) }}"
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
                        Tambah Defect (Kode / Nama)
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
                    <span class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        Daftar Defect Harness (Minimal 1) <span class="text-brand">*</span>
                    </span>
                    <div id="selected_defects_container" class="space-y-2"></div>
                </div>

                <!-- Action Buttons: Simpan & Batal -->
                <div class="pt-3 space-y-2">
                    <button type="submit"
                            class="w-full min-h-[50px] bg-brand hover:bg-brand-active text-white rounded-2xl p-3.5 text-base font-extrabold flex items-center justify-center space-x-2 shadow-sm shadow-brand/20 active:scale-95 transition-all cursor-pointer touch-manipulation">
                        <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>

                    <a href="{{ route('rework.index') }}"
                       class="w-full min-h-[48px] bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl p-3 text-sm font-bold flex items-center justify-center border border-gray-300 transition-all cursor-pointer touch-manipulation">
                        Batal
                    </a>
                </div>
            </form>

        </div>
    </main>

</div>

@push('scripts')
<script>
    (function() {
        const finalAssyDefects = @json($finalAssySubDefects);
        let selectedDefects = [];

        // Initial pre-fill: use old input if validation failed, else existing current defects
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
        @else
            const currentDefects = @json($currentDefects);
            if (Array.isArray(currentDefects)) {
                currentDefects.forEach(function(cur) {
                    const match = finalAssyDefects.find(function(d) { return d.id == cur.id; });
                    if (match) {
                        selectedDefects.push(match);
                    } else if (cur.id) {
                        selectedDefects.push(cur);
                    }
                });
            }
        @endif

        const kanbanInput = document.getElementById('kanban_assy');
        const npInput = document.getElementById('nameplate');
        const defectSearch = document.getElementById('defect_search');
        const dropdown = document.getElementById('dropdown_results');
        const chipsContainer = document.getElementById('selected_defects_container');
        const reworkEditForm = document.getElementById('rework_edit_form');

        // 1. Enter on Kanban input moves focus to Nameplate
        kanbanInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                npInput.focus();
            }
        });

        // 2. Enter on Nameplate input moves focus to Defect Search
        npInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                defectSearch.focus();
            }
        });

        // Render chips
        function renderChips() {
            chipsContainer.innerHTML = '';
            if (selectedDefects.length === 0) {
                const emptyWrap = document.createElement('div');
                emptyWrap.className = 'text-xs text-rose-600 italic bg-rose-50 border border-dashed border-rose-200 rounded-xl p-3 text-center';
                emptyWrap.textContent = 'Semua defect dihapus. Minimal 1 defect wajib ada.';
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

        // Close dropdown if clicked outside
        document.addEventListener('click', function(e) {
            if (!defectSearch.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Form validation before submit
        reworkEditForm.addEventListener('submit', function(e) {
            if (selectedDefects.length === 0) {
                e.preventDefault();
                alert('Minimal satu defect harus dipilih.');
                defectSearch.focus();
                return;
            }

            // Check duplicates
            const ids = selectedDefects.map(function(d) { return d.id; });
            const unique = new Set(ids);
            if (ids.length !== unique.size) {
                e.preventDefault();
                alert('Defect tidak boleh duplikat dalam satu harness.');
                defectSearch.focus();
                return;
            }
        });
    })();
</script>
@endpush
@endsection
