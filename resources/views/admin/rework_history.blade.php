@extends('layouts.app')

@section('title', 'Riwayat Rework')

@section('content')
<!-- Header Section -->
<header class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">Riwayat Rework</h1>
        <p class="text-xs font-semibold text-gray-500 mt-1">Daftar riwayat harness rework Final Assy per kelompok defect (Read-only).</p>
    </div>

    <div class="flex items-center space-x-6">
        <!-- Admin Profile Card -->
        <div class="flex items-center space-x-3">
            <div class="text-right">
                <span class="block text-xs font-extrabold text-gray-900">{{ session('user_name', 'Admin QA') }}</span>
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ session('user_role', 'Administrator') }}</span>
            </div>
            <div class="w-9 h-9 rounded-xl bg-red-50 text-brand flex items-center justify-center border border-red-100 font-black text-xs">
                {{ strtoupper(substr(session('user_name', 'A'), 0, 1)) }}
            </div>
        </div>
    </div>
</header>

<!-- Filters and Table Container -->
<section class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs flex-1 flex flex-col justify-between">
    <div>
        <!-- Filter Form -->
        <form id="filterForm" method="GET" action="{{ route('admin.rework_history.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <input type="hidden" name="page" value="1">

            <div class="flex flex-wrap items-center gap-3">
                <!-- Date Range Picker -->
                <div class="relative min-w-[220px]">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8b0000]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                    <input type="text"
                           id="date_range"
                           name="date_range"
                           placeholder="Pilih Rentang Tanggal"
                           value="{{ $dateRange }}"
                           readonly
                           class="w-full pl-10 pr-4 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                </div>

                <!-- Status Filter -->
                <div class="relative min-w-[140px]">
                    <select name="status"
                            onchange="this.form.submit()"
                            class="w-full appearance-none pl-4 pr-10 py-2 border border-[#8b0000] rounded-lg text-xs font-semibold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#8b0000]/20 focus:border-[#8b0000] cursor-pointer">
                        <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="Berjalan" {{ $selectedStatus === 'Berjalan' ? 'selected' : '' }}>Berjalan</option>
                        <option value="Selesai" {{ $selectedStatus === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#8b0000]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Reset Filter Button if any filter active -->
                @if($dateRange !== '' || ($selectedStatus !== 'all' && $selectedStatus !== ''))
                    <a href="{{ route('admin.rework_history.index') }}"
                       class="text-xs text-gray-400 hover:text-[#8b0000] font-semibold transition-colors flex items-center space-x-1 pl-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>

            <!-- Export Button & Total Badge (Design identik Final Assy & Pre Assy) -->
            <div class="flex items-center space-x-3">
                <!-- Export Button -->
                <button type="button"
                        onclick="exportExcel()"
                        class="bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold px-5 py-2.5 rounded-lg transition duration-200 shadow-sm flex items-center space-x-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span>Export</span>
                </button>

                <!-- Total Harness Badge -->
                <div class="text-xs font-bold text-gray-500 flex items-center space-x-1.5">
                    <span>Total Harness:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-red-50 text-brand border border-red-100">
                        {{ $totalHarnesses }}
                    </span>
                </div>
            </div>
        </form>

        <!-- Responsive Table Container -->
        <div class="overflow-x-auto min-h-[380px] pb-4">
            <table class="w-full min-w-[950px] text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/70 sticky top-0 z-10">
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-3 pl-3 text-center w-12">No.</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Waktu In / Out</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center">Status</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Durasi</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Kanban &amp; NP</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Operator Input</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4">Daftar Defect</th>
                        <th class="text-[11px] font-bold text-gray-400 uppercase tracking-wider py-3 px-4 text-center pr-3 w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody id="reworkTableBody">
                    @forelse($paginatedGroups as $groupItem)
                        @php
                            $groupId = $groupItem->rework_group_id;
                            $items = $defectsByGroup->get($groupId, collect());
                            $first = $items->first();
                            $rowNum = ($paginatedGroups->currentPage() - 1) * $paginatedGroups->perPage() + $loop->iteration;
                        @endphp

                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-red-50/20 transition-colors odd:bg-white even:bg-gray-50/40">
                            <!-- No -->
                            <td class="py-4 text-xs font-mono text-gray-400 font-bold text-center px-3 pl-3 align-top">
                                {{ $rowNum }}
                            </td>

                            <!-- Waktu In / Out -->
                            <td class="py-4 text-sm text-gray-500 px-4 align-top">
                                <div class="text-xs leading-relaxed space-y-1">
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">In:</span>
                                        <span class="font-extrabold text-gray-900 font-mono text-[11px]">
                                            {{ $first?->waktu_in ? \Carbon\Carbon::parse($first->waktu_in)->format('d/m/Y H:i') : ($first?->waktu ? \Carbon\Carbon::parse($first->waktu)->format('d/m/Y H:i') : '-') }}
                                        </span>
                                    </div>
                                    @if($first?->status_rework === 'Selesai' && $first?->waktu_out)
                                        <div class="pt-0.5 border-t border-gray-100">
                                            <span class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">Out:</span>
                                            <span class="font-extrabold text-gray-900 font-mono text-[11px]">
                                                {{ \Carbon\Carbon::parse($first->waktu_out)->format('d/m/Y H:i') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 text-xs font-bold px-4 text-center align-top">
                                @if(($first?->status_rework ?? '') === 'Selesai')
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
                            </td>

                            <!-- Durasi -->
                            <td class="py-4 text-xs font-bold px-4 align-top">
                                @if(($first?->status_rework ?? '') === 'Selesai')
                                    <span class="text-emerald-700 font-mono">
                                        {{ $first?->durasi_rework_menit ?? 0 }} menit
                                    </span>
                                @else
                                    <span class="text-amber-600 font-semibold italic">
                                        Sedang berjalan
                                    </span>
                                @endif
                            </td>

                            <!-- Kanban & Nameplate -->
                            <td class="py-4 text-xs px-4 align-top">
                                <div class="space-y-0.5">
                                    <span class="block text-sm font-black text-gray-900 tracking-tight">
                                        {{ $first?->kanban_assy ?? '-' }}
                                    </span>
                                    <span class="block text-xs font-bold font-mono text-gray-500">
                                        NP: {{ $first?->nameplate_lama ?? '-' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Operator Input -->
                            <td class="py-4 text-xs px-4 align-top">
                                <div class="space-y-0.5">
                                    <span class="block font-bold text-gray-900">
                                        {{ $first?->user_name ?? '-' }}
                                    </span>
                                    <span class="inline-block text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md">
                                        Shift {{ $first?->shift ?? '-' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Daftar Defect -->
                            <td class="py-4 text-xs px-4 align-top">
                                <div class="space-y-1.5 max-w-md">
                                    @forelse($items as $item)
                                        @php
                                            $code = $item->subDefectType?->code ?? \App\Support\SubDefectHelper::deriveCode($item->jenis_sub_defect) ?? '-';
                                            $name = $item->jenis_sub_defect ?? $item->subDefectType?->name ?? '-';
                                        @endphp
                                        <div class="flex items-start text-xs font-bold text-gray-800">
                                            <span class="bg-[#8b0000]/10 text-[#8b0000] border border-[#8b0000]/20 font-black px-1.5 py-0.5 rounded text-[10px] mr-2 shrink-0 font-mono">
                                                [{{ $code }}]
                                            </span>
                                            <span class="leading-tight text-gray-800">{{ $name }}</span>
                                        </div>
                                    @empty
                                        <span class="text-gray-400 italic text-xs">-</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Aksi (Edit) -->
                            <td class="py-4 text-center px-4 pr-3 align-top">
                                <a href="{{ route('rework.edit', array_merge(['groupId' => $groupId], request()->query() ? ['return_url' => request()->getRequestUri()] : [])) }}"
                                   title="Edit Riwayat Harness"
                                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold transition-all shadow-xs active:scale-95 cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                    <span>Edit</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-sm text-gray-400 font-medium">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <svg class="w-10 h-10 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="text-xs text-gray-500 font-semibold">Tidak ada riwayat rework yang cocok dengan filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer Pagination Row -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-100 pt-6 mt-4 gap-4">
        <span class="text-xs font-semibold text-gray-400">
            Menampilkan <span class="text-gray-900 font-bold">{{ $paginatedGroups->count() }}</span> dari <span class="text-gray-900 font-bold">{{ $paginatedGroups->total() }}</span> harness
        </span>

        <!-- Pagination Links -->
        <div class="flex items-center">
            {{ $paginatedGroups->links() }}
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function exportExcel() {
    const dateRange = document.getElementById("date_range") ? document.getElementById("date_range").value : '';
    const status = document.querySelector('select[name="status"]') ? document.querySelector('select[name="status"]').value : 'all';

    let url = "{{ route('admin.rework_history.export') }}?";
    url += "date_range=" + encodeURIComponent(dateRange);
    url += "&status=" + encodeURIComponent(status);

    window.location.href = url;
}

document.addEventListener("DOMContentLoaded", function () {
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#date_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2 || selectedDates.length === 0) {
                    document.getElementById("filterForm").submit();
                }
            }
        });
    }
});
</script>
@endpush
