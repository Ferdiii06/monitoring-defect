@extends('layouts.app')

@section('title', 'Nameplate Setting (Carline)')

@section('content')
<div class="space-y-6" x-data="{ 
    modalOpen: false, 
    isEdit: false, 
    uploadModal: false, 
    testScanOpen: false,
    testBarcode: '',
    testLoading: false,
    testResult: null,
    testError: '',
    formData: { 
        id: null, 
        carline: '', 
        assy_customer: false, 
        name: '', 
        set: 'CARTON BOX', 
        date_format: 'YYMMDD',
        assy_no: '',
        suffix: '',
        level: '',
        date_field: '',
        sequence_label: ''
    },
    openNewModal() {
        this.isEdit = false;
        this.testScanOpen = false;
        this.testBarcode = '';
        this.testResult = null;
        this.testError = '';
        this.formData = {
            id: null,
            carline: '',
            assy_customer: false,
            name: '',
            set: 'CARTON BOX',
            date_format: 'YYMMDD',
            assy_no: '',
            suffix: '',
            level: '',
            date_field: '',
            sequence_label: ''
        };
        this.modalOpen = true;
    },
    openEditModal(item) {
        this.isEdit = true;
        this.testScanOpen = false;
        this.testBarcode = '';
        this.testResult = null;
        this.testError = '';
        this.formData = {
            id: item.id,
            carline: item.carline || '',
            assy_customer: !!item.assy_customer,
            name: item.name || '',
            set: item.set || 'CARTON BOX',
            date_format: item.date_format || 'YYMMDD',
            assy_no: item.assy_no || '',
            suffix: item.suffix || '',
            level: item.level || '',
            date_field: item.date_field || '',
            sequence_label: item.sequence_label || ''
        };
        this.modalOpen = true;
    },
    async runTestScan() {
        if (!this.testBarcode.trim()) {
            this.testError = 'Masukkan atau scan barcode terlebih dahulu.';
            this.testResult = null;
            return;
        }
        this.testLoading = true;
        this.testError = '';
        this.testResult = null;
        try {
            const res = await fetch('{{ route('admin.master.nameplate_settings.test_scan') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    barcode: this.testBarcode,
                    carline: this.formData.carline,
                    date_format: this.formData.date_format,
                    assy_no: this.formData.assy_no,
                    suffix: this.formData.suffix,
                    level: this.formData.level,
                    date_field: this.formData.date_field,
                    sequence_label: this.formData.sequence_label
                })
            });
            const data = await res.json();
            if (data.success) {
                this.testResult = data.data;
            } else {
                this.testError = data.message || 'Gagal mengekstrak barcode.';
            }
        } catch (e) {
            this.testError = 'Terjadi kesalahan jaringan saat melakukan test scan.';
        } finally {
            this.testLoading = false;
        }
    }
}">
    <!-- Breadcrumb & Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <!-- Breadcrumb -->
            <nav class="flex items-center space-x-2 text-xs font-semibold text-gray-400 mb-1">
                <span>Master Data</span>
                <span>/</span>
                <span class="text-[#8b0000]">Nameplate Setting (Carline)</span>
            </nav>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">Nameplate Setting (Carline)</h1>
            <p class="text-xs font-semibold text-gray-500 mt-1">
                Konfigurasi format nameplate dan kemasan per carline untuk integrasi HTScan JAI.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <button type="button" @click="openNewModal()"
                    class="px-4 py-2.5 rounded-lg bg-[#8b0000] hover:bg-[#600000] text-white text-xs font-bold transition-all shadow-xs flex items-center space-x-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>New</span>
            </button>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center space-x-2 shadow-xs">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-[#8b0000] text-xs font-semibold space-y-1 shadow-xs">
            <div class="font-bold flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-[#8b0000] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>Terjadi kesalahan input:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Import Summary Card if available -->
    @if(session('nameplate_setting_import_summary'))
        @php $summary = session('nameplate_setting_import_summary'); @endphp
        <div class="p-5 rounded-lg bg-white border border-gray-200 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <span class="text-xs font-bold text-gray-800 uppercase tracking-wider">Laporan Hasil Impor Terakhir</span>
                <span class="text-[11px] font-semibold text-gray-500">
                    Ditambah: <span class="text-emerald-700 font-bold">{{ $summary['added'] ?? 0 }}</span> | 
                    Diperbarui: <span class="text-blue-700 font-bold">{{ $summary['updated'] ?? 0 }}</span> | 
                    Gagal: <span class="text-red-700 font-bold">{{ $summary['failed'] ?? 0 }}</span>
                </span>
            </div>

            @if(!empty($summary['errors']))
                <div class="max-h-48 overflow-y-auto border border-red-100 rounded-lg p-3 bg-red-50/40 text-xs space-y-1">
                    <div class="font-bold text-red-800 text-[11px] uppercase tracking-wider mb-1">Rincian Baris Gagal:</div>
                    @foreach($summary['errors'] as $err)
                        <div class="text-red-700 flex items-start space-x-2">
                            <span class="font-mono font-bold shrink-0">[Baris {{ $err['row'] ?? '-' }}]:</span>
                            <span>{{ $err['carline'] ? '(' . $err['carline'] . ') ' : '' }}{{ $err['reason'] ?? 'Kesalahan tidak diketahui' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- Main Card Table -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-xs overflow-hidden">
        <!-- Toolbar -->
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Left Toolbar: Download Excel & Upload by Excel -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.master.nameplate_settings.export', ['search' => $search, 'sort_by' => $sortBy, 'order' => $order]) }}"
                   class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span>Download Excel</span>
                </a>

                <button type="button" @click="uploadModal = true"
                        class="px-3.5 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition flex items-center space-x-1.5 shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Upload by Excel</span>
                </button>
            </div>

            <!-- Right Toolbar: Show Entries & Search -->
            <div class="flex flex-wrap items-center gap-4">
                <!-- Show Entries -->
                <div class="flex items-center space-x-2 text-xs font-semibold text-gray-600">
                    <span>Show</span>
                    <select name="per_page" 
                            onchange="window.location.href = '{{ route('admin.master.nameplate_settings.index') }}?search={{ urlencode($search) }}&sort_by={{ $sortBy }}&order={{ $order }}&per_page=' + this.value"
                            class="px-2.5 py-1.5 text-xs font-semibold text-gray-700 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white cursor-pointer">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                    <span>entries</span>
                </div>

                <!-- Search Input with Debounce 300ms -->
                <form method="GET" action="{{ route('admin.master.nameplate_settings.index') }}" class="relative flex items-center">
                    <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    <input type="hidden" name="order" value="{{ $order }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search..."
                               @input.debounce.300ms="$el.form.submit()"
                               class="pl-9 pr-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] w-48 sm:w-60 bg-white">
                    </div>
                    @if($search !== '')
                        <a href="{{ route('admin.master.nameplate_settings.index', ['sort_by' => $sortBy, 'order' => $order, 'per_page' => $perPage]) }}"
                           class="ml-1.5 text-xs text-gray-400 hover:text-[#8b0000] transition" title="Clear Search">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Table Responsive Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                        @php
                            $columns = [
                                'carline'     => 'Carline',
                                'name'        => 'Name',
                                'set'         => 'Set',
                                'date_format' => 'Date Format',
                            ];
                        @endphp

                        @foreach($columns as $colKey => $colLabel)
                            @php
                                $isActive = ($sortBy === $colKey);
                                $nextOrder = ($isActive && $order === 'asc') ? 'desc' : 'asc';
                            @endphp
                            <th scope="col" class="py-3 px-4 font-bold">
                                <a href="{{ route('admin.master.nameplate_settings.index', ['search' => $search, 'per_page' => $perPage, 'sort_by' => $colKey, 'order' => $nextOrder]) }}"
                                   class="inline-flex items-center space-x-1.5 hover:text-[#8b0000] transition group">
                                    <span>{{ $colLabel }}</span>
                                    <span class="inline-flex flex-col text-[9px] leading-none">
                                        @if($isActive)
                                            @if($order === 'asc')
                                                <svg class="w-3.5 h-3.5 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path></svg>
                                            @else
                                                <svg class="w-3.5 h-3.5 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                            @endif
                                        @else
                                            <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                                        @endif
                                    </span>
                                </a>
                            </th>
                        @endforeach
                        <th scope="col" class="py-3 px-4 text-center font-bold w-20">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                    @forelse($settings as $setting)
                        <tr class="{{ $loop->even ? 'bg-gray-50/60' : 'bg-white' }} hover:bg-red-50/20 transition-colors">
                            <td class="py-3 px-4 font-bold text-gray-900 uppercase">
                                {{ $setting->carline }}
                                @if($setting->assy_customer)
                                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        Assy Cust
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-800">
                                {{ $setting->name }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wide {{ $setting->set === 'CARTON BOX' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                                    {{ $setting->set }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-semibold text-gray-600">
                                {{ $setting->date_format }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button type="button" 
                                        @click="openEditModal({
                                            id: {{ $setting->id }},
                                            carline: '{{ addslashes($setting->carline) }}',
                                            assy_customer: {{ $setting->assy_customer ? 'true' : 'false' }},
                                            name: '{{ addslashes($setting->name) }}',
                                            set: '{{ addslashes($setting->set) }}',
                                            date_format: '{{ addslashes($setting->date_format) }}',
                                            assy_no: '{{ addslashes($setting->assy_no ?? '') }}',
                                            suffix: '{{ addslashes($setting->suffix ?? '') }}',
                                            level: '{{ addslashes($setting->level ?? '') }}',
                                            date_field: '{{ addslashes($setting->date_field ?? '') }}',
                                            sequence_label: '{{ addslashes($setting->sequence_label ?? '') }}'
                                        })"
                                        class="inline-flex items-center space-x-1 px-2 py-1 border border-gray-200 rounded-md hover:bg-gray-100 hover:border-gray-300 text-gray-600 hover:text-[#8b0000] text-[11px] font-semibold transition cursor-pointer shadow-2xs"
                                        title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                    <span>edit</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-400 text-xs font-semibold">
                                Tidak ada data nameplate yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer: Showing entries & Pagination -->
        <div class="p-4 sm:p-5 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50/40">
            <!-- Left Info -->
            <div class="text-xs font-semibold text-gray-500">
                Showing {{ $settings->firstItem() ?? 0 }} to {{ $settings->lastItem() ?? 0 }} of {{ $settings->total() }} entries
            </div>

            <!-- Right Pagination -->
            <div class="flex items-center space-x-1">
                {{-- Previous Page Link --}}
                @if ($settings->onFirstPage())
                    <span class="px-3 py-1.5 text-xs font-semibold text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed select-none">
                        Previous
                    </span>
                @else
                    <a href="{{ $settings->previousPageUrl() }}" 
                       class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition shadow-2xs">
                        Previous
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($settings->getUrlRange(max(1, $settings->currentPage() - 2), min($settings->lastPage(), $settings->currentPage() + 2)) as $page => $url)
                    @if ($page == $settings->currentPage())
                        <span class="px-3 py-1.5 text-xs font-bold text-white bg-[#8b0000] rounded-lg shadow-xs select-none">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" 
                           class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition shadow-2xs">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($settings->hasMorePages())
                    <a href="{{ $settings->nextPageUrl() }}" 
                       class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition shadow-2xs">
                        Next
                    </a>
                @else
                    <span class="px-3 py-1.5 text-xs font-semibold text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed select-none">
                        Next
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Form (New / Edit) -->
    <div x-show="modalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-gray-200 transform transition-all"
             @click.away="modalOpen = false">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/80 shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-gray-900" x-text="isEdit ? 'Edit Nameplate Setting' : 'New Nameplate Setting'"></h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">Konfigurasi carline dan aturan segmentasi barcode nameplate.</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body Form -->
            <form :action="isEdit ? ('{{ url('/master/nameplate-settings') }}/' + formData.id) : '{{ route('admin.master.nameplate_settings.store') }}'" 
                  method="POST" 
                  class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <!-- 1. Carline Code + Checkbox Assy Customer -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Carline Code <span class="text-[#8b0000]">*</span>
                        </label>
                        <div class="sm:col-span-8 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                            <input type="text" name="carline" x-model="formData.carline" required maxlength="50"
                                   placeholder="Contoh: LINE 1"
                                   class="w-full sm:flex-1 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] uppercase font-mono bg-white font-semibold">
                            <label class="inline-flex items-center space-x-2 cursor-pointer shrink-0 py-1">
                                <input type="checkbox" name="assy_customer" value="1" x-model="formData.assy_customer"
                                       class="rounded border-gray-300 text-[#8b0000] focus:ring-[#8b0000] h-4 w-4">
                                <span class="text-xs font-bold text-gray-700 select-none">Assy Customer</span>
                            </label>
                        </div>
                    </div>

                    <!-- 2. Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Name <span class="text-[#8b0000]">*</span>
                        </label>
                        <div class="sm:col-span-8">
                            <input type="text" name="name" x-model="formData.name" required maxlength="100"
                                   placeholder="Contoh: Nameplate Line 1"
                                   class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white">
                        </div>
                    </div>

                    <!-- 3. Set -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Set <span class="text-[#8b0000]">*</span>
                        </label>
                        <div class="sm:col-span-8">
                            <select name="set" x-model="formData.set" required
                                    class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] bg-white cursor-pointer font-semibold">
                                <option value="CARTON BOX">CARTON BOX</option>
                                <option value="POLYTAINER">POLYTAINER</option>
                            </select>
                        </div>
                    </div>

                    <!-- 4. Date Format -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Date Format <span class="text-[#8b0000]">*</span>
                        </label>
                        <div class="sm:col-span-8 relative flex items-center">
                            <input type="text" name="date_format" x-model="formData.date_format" required maxlength="20"
                                   placeholder="Contoh: YYMMDD"
                                   class="w-full pr-9 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] uppercase font-mono bg-white font-semibold">
                            <!-- TODO: konfirmasi ke pemilik sistem -->
                            <div x-data="{ tip: false }" class="absolute right-2.5 flex items-center">
                                <button type="button" @mouseenter="tip = true" @mouseleave="tip = false" @click="tip = !tip"
                                        class="w-4 h-4 rounded-full bg-gray-700 hover:bg-gray-900 text-white text-[10px] font-bold flex items-center justify-center cursor-pointer shadow-xs">
                                    i
                                </button>
                                <div x-show="tip" x-cloak
                                     class="absolute right-0 bottom-full mb-1.5 z-50 w-64 p-2.5 bg-gray-900 text-white text-[11px] rounded-lg shadow-xl leading-relaxed">
                                    Format tanggal pada barcode nameplate, hanya menggunakan kombinasi karakter Y, M, D. Contoh: YYMMDD untuk 260309 (9 Maret 2026) atau YYYYMMDD.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Assy No. -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Assy No.
                        </label>
                        <div class="sm:col-span-8 relative flex items-center">
                            <input type="text" name="assy_no" x-model="formData.assy_no" maxlength="100"
                                   placeholder="Contoh: 1,8 atau 1-8"
                                   class="w-full pr-9 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] font-mono bg-white">
                            <!-- TODO: konfirmasi ke pemilik sistem -->
                            <div x-data="{ tip: false }" class="absolute right-2.5 flex items-center">
                                <button type="button" @mouseenter="tip = true" @mouseleave="tip = false" @click="tip = !tip"
                                        class="w-4 h-4 rounded-full bg-gray-700 hover:bg-gray-900 text-white text-[10px] font-bold flex items-center justify-center cursor-pointer shadow-xs">
                                    i
                                </button>
                                <div x-show="tip" x-cloak
                                     class="absolute right-0 bottom-full mb-1.5 z-50 w-64 p-2.5 bg-gray-900 text-white text-[11px] rounded-lg shadow-xl leading-relaxed">
                                    Aturan posisi dan panjang nomor assy pada barcode. Contoh: 1,8 atau 1-8 (mulai karakter ke-1 sebanyak 8 karakter, misal 50060938).
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Suffix -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Suffix
                        </label>
                        <div class="sm:col-span-8 relative flex items-center">
                            <input type="text" name="suffix" x-model="formData.suffix" maxlength="100"
                                   placeholder="Contoh: 9,2 atau 9-10"
                                   class="w-full pr-9 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] font-mono bg-white">
                            <!-- TODO: konfirmasi ke pemilik sistem -->
                            <div x-data="{ tip: false }" class="absolute right-2.5 flex items-center">
                                <button type="button" @mouseenter="tip = true" @mouseleave="tip = false" @click="tip = !tip"
                                        class="w-4 h-4 rounded-full bg-gray-700 hover:bg-gray-900 text-white text-[10px] font-bold flex items-center justify-center cursor-pointer shadow-xs">
                                    i
                                </button>
                                <div x-show="tip" x-cloak
                                     class="absolute right-0 bottom-full mb-1.5 z-50 w-64 p-2.5 bg-gray-900 text-white text-[11px] rounded-lg shadow-xl leading-relaxed">
                                    Aturan posisi dan panjang karakter suffix/varian. Contoh: 9,2 atau 9-10 (mulai karakter ke-9 sebanyak 2 karakter, misal 01).
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Level -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Level
                        </label>
                        <div class="sm:col-span-8 relative flex items-center">
                            <input type="text" name="level" x-model="formData.level" maxlength="100"
                                   placeholder="Contoh: 11,2"
                                   class="w-full pr-9 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] font-mono bg-white">
                            <!-- TODO: konfirmasi ke pemilik sistem -->
                            <div x-data="{ tip: false }" class="absolute right-2.5 flex items-center">
                                <button type="button" @mouseenter="tip = true" @mouseleave="tip = false" @click="tip = !tip"
                                        class="w-4 h-4 rounded-full bg-gray-700 hover:bg-gray-900 text-white text-[10px] font-bold flex items-center justify-center cursor-pointer shadow-xs">
                                    i
                                </button>
                                <div x-show="tip" x-cloak
                                     class="absolute right-0 bottom-full mb-1.5 z-50 w-64 p-2.5 bg-gray-900 text-white text-[11px] rounded-lg shadow-xl leading-relaxed">
                                    Aturan posisi level perubahan desain / engineering change level pada barcode jika ada. Contoh: 11,2.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Date
                        </label>
                        <div class="sm:col-span-8 relative flex items-center">
                            <input type="text" name="date_field" x-model="formData.date_field" maxlength="100"
                                   placeholder="Contoh: 11,6 atau 11-16"
                                   class="w-full pr-9 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] font-mono bg-white">
                            <!-- TODO: konfirmasi ke pemilik sistem -->
                            <div x-data="{ tip: false }" class="absolute right-2.5 flex items-center">
                                <button type="button" @mouseenter="tip = true" @mouseleave="tip = false" @click="tip = !tip"
                                        class="w-4 h-4 rounded-full bg-gray-700 hover:bg-gray-900 text-white text-[10px] font-bold flex items-center justify-center cursor-pointer shadow-xs">
                                    i
                                </button>
                                <div x-show="tip" x-cloak
                                     class="absolute right-0 bottom-full mb-1.5 z-50 w-64 p-2.5 bg-gray-900 text-white text-[11px] rounded-lg shadow-xl leading-relaxed">
                                    Aturan posisi kode tanggal produksi pada barcode nameplate. Contoh: 11,6 atau 11-16 (mulai karakter ke-11 sebanyak 6 digit).
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 9. Sequence Label -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <label class="sm:col-span-4 text-xs font-bold text-gray-700">
                            Sequence Label
                        </label>
                        <div class="sm:col-span-8 relative flex items-center">
                            <input type="text" name="sequence_label" x-model="formData.sequence_label" maxlength="100"
                                   placeholder="Contoh: 17,4 atau 17-20"
                                   class="w-full pr-9 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] font-mono bg-white">
                            <!-- TODO: konfirmasi ke pemilik sistem -->
                            <div x-data="{ tip: false }" class="absolute right-2.5 flex items-center">
                                <button type="button" @mouseenter="tip = true" @mouseleave="tip = false" @click="tip = !tip"
                                        class="w-4 h-4 rounded-full bg-gray-700 hover:bg-gray-900 text-white text-[10px] font-bold flex items-center justify-center cursor-pointer shadow-xs">
                                    i
                                </button>
                                <div x-show="tip" x-cloak
                                     class="absolute right-0 bottom-full mb-1.5 z-50 w-64 p-2.5 bg-gray-900 text-white text-[11px] rounded-lg shadow-xl leading-relaxed">
                                    Aturan posisi nomor seri atau sequence label pada barcode nameplate. Contoh: 17,4 (mulai karakter ke-17 sebanyak 4 digit, misal 0001).
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Scan Collapsible Panel -->
                    <div x-show="testScanOpen" x-collapse x-cloak class="mt-4 pt-4 border-t border-gray-100">
                        <div class="bg-gray-50/80 border border-gray-200 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-800 flex items-center space-x-1.5">
                                    <svg class="w-4 h-4 text-[#8b0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                    <span>Simulasi Test Scan Barcode</span>
                                </span>
                                <span class="text-[10px] text-gray-400 font-semibold">(Tidak menyimpan data ke DB)</span>
                            </div>

                            <div class="flex gap-2">
                                <input type="text" x-model="testBarcode" @keydown.enter.prevent="runTestScan()"
                                       placeholder="Scan / paste barcode (contoh: *50060938012603090001*)"
                                       class="flex-1 px-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] font-mono bg-white">
                                <button type="button" @click="runTestScan()" :disabled="testLoading"
                                        class="px-3.5 py-1.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-lg transition disabled:opacity-50 cursor-pointer">
                                    <span x-show="!testLoading">Test</span>
                                    <span x-show="testLoading">...</span>
                                </button>
                            </div>

                            <!-- Error Alert -->
                            <div x-show="testError" x-cloak class="p-2.5 rounded-lg bg-red-50 border border-red-200 text-[#8b0000] text-xs font-semibold">
                                <span x-text="testError"></span>
                            </div>

                            <!-- Result Table -->
                            <div x-show="testResult" x-cloak class="overflow-hidden border border-gray-200 rounded-lg bg-white">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-gray-100/70 border-b border-gray-200 text-gray-600 font-bold text-[10px] uppercase">
                                            <th class="py-1.5 px-3">Field</th>
                                            <th class="py-1.5 px-3">Nilai Ekstraksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 font-mono text-[11px]">
                                        <tr>
                                            <td class="py-1.5 px-3 font-sans font-semibold text-gray-600">Assy No.</td>
                                            <td class="py-1.5 px-3 text-gray-900 font-bold" x-text="testResult?.assy_no || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-1.5 px-3 font-sans font-semibold text-gray-600">Suffix</td>
                                            <td class="py-1.5 px-3 text-gray-900" x-text="testResult?.suffix || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-1.5 px-3 font-sans font-semibold text-gray-600">Level</td>
                                            <td class="py-1.5 px-3 text-gray-900" x-text="testResult?.level || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-1.5 px-3 font-sans font-semibold text-gray-600">Date</td>
                                            <td class="py-1.5 px-3 text-gray-900" x-text="testResult?.date || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-1.5 px-3 font-sans font-semibold text-gray-600">Sequence Label</td>
                                            <td class="py-1.5 px-3 text-gray-900" x-text="testResult?.sequence_label || '-'"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between shrink-0">
                    <!-- Left: Test Scan Button -->
                    <button type="button" @click="testScanOpen = !testScanOpen"
                            class="px-3 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-lg transition flex items-center space-x-1.5 shadow-2xs cursor-pointer">
                        <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                        <span x-text="testScanOpen ? 'Tutup Test Scan' : 'Test Scan'"></span>
                    </button>

                    <!-- Right: Cancel & Save Buttons -->
                    <div class="flex items-center space-x-2">
                        <button type="button" @click="modalOpen = false"
                                class="px-4 py-2 border border-gray-200 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-[#8b0000] hover:bg-[#600000] text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center space-x-1.5 cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            <span>Save</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Upload by Excel -->
    <div x-show="uploadModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100 transform transition-all"
             @click.away="uploadModal = false">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
                <h3 class="text-sm font-bold text-gray-900">Upload by Excel</h3>
                <button type="button" @click="uploadModal = false" class="text-gray-400 hover:text-gray-600 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('admin.master.nameplate_settings.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-lg text-xs text-amber-900 space-y-1.5">
                    <div class="font-bold flex items-center space-x-1">
                        <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Petunjuk Impor Excel / CSV:</span>
                    </div>
                    <p class="text-[11px] leading-relaxed">
                        Data akan di-upsert (dibuat baru jika Carline belum ada, diperbarui jika sudah ada). File maksimal 2 MB (.xlsx, .xls, .csv).
                    </p>
                    <div class="pt-1">
                        <a href="{{ route('admin.master.nameplate_settings.template') }}" 
                           class="inline-flex items-center space-x-1 text-xs font-bold text-[#8b0000] hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span>Download Template Excel</span>
                        </a>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Pilih File (.xlsx, .xls, .csv) <span class="text-[#8b0000]">*</span></label>
                    <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                           class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 bg-white">
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="uploadModal = false"
                            class="px-4 py-2 border border-gray-200 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-[#8b0000] hover:bg-[#600000] text-white rounded-lg text-xs font-bold transition shadow-xs cursor-pointer">
                        Unggah &amp; Proses
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
