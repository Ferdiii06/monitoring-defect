@extends('layouts.operator')

@section('title', (isset($defect) ? 'Edit' : 'Report') . ' Defect')

@section('content')
<main class="w-full bg-surface rounded-3xl shadow-[0_4px_24px_-4px_rgba(0,0,0,0.06)] overflow-hidden border border-border flex flex-col relative" x-data="defectForm()" x-init="initData()">

    <!-- Header: Back Button + Title -->
    <div class="p-5 sm:p-6 pb-3 flex items-center space-x-3 border-b border-border bg-white">
        <a href="{{ $backRoute ?? route('operator.home') }}" class="text-brand hover:text-brand-active p-1.5 -ml-1.5 rounded-xl hover:bg-red-50 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Form Input QA</span>
            <h1 class="text-lg font-black text-brand tracking-tight mt-0.5">
                {{ isset($defect) ? 'Edit Report' : 'Report Defect' }} <span class="text-gray-900" x-text="form.type"></span>
            </h1>
        </div>
    </div>

    <!-- Progress Indicator Bar -->
    <div class="px-5 sm:px-6 pt-4 pb-2 space-y-2 bg-gray-50/60 border-b border-gray-100">
        <div class="flex justify-between items-center text-xs">
            <span class="font-bold text-gray-400 uppercase tracking-wider text-[10px]" x-text="step === 1 ? 'Langkah 1 dari 2' : 'Langkah 2 dari 2'"></span>
            <span class="font-extrabold text-brand text-xs" x-text="step === 1 ? '{{ isset($defect) ? "Informasi Dasar (Edit)" : "Informasi Dasar" }}' : 'Konfirmasi Laporan'"></span>
        </div>

        <!-- Two-segment Progress Line -->
        <div class="grid grid-cols-2 gap-1.5 h-1.5 w-full">
            <div class="bg-brand rounded-full"></div>
            <div class="rounded-full transition-colors duration-200" :class="step === 2 ? 'bg-brand' : 'bg-red-100'"></div>
        </div>
    </div>

    <!-- Main Form Container -->
    <div class="p-5 sm:p-6 pt-4">

        @if ($errors->any())
            <div class="mb-4 bg-rose-50 text-final-assy-defect text-xs font-semibold p-3.5 rounded-xl border border-rose-200">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($defect) && isset($backRoute) ? route('admin.report.update', $defect->id) : (isset($defect) ? route('input_defect.update', $defect->id) : route('input_defect.store')) }}" method="POST" id="defectForm">
            @csrf
            @if(isset($defect))
                @method('PUT')
            @endif

            <input type="hidden" name="type" x-model="form.type">
            <input type="hidden" name="carline_id" :value="form.type === 'Pre Assy' ? form.carline_id : ''">
            <input type="hidden" name="inspect_process_type_id" :value="form.type === 'Pre Assy' ? form.inspect_process_type_id : ''">
            <input type="hidden" name="final_inspect_type_id" :value="(form.type === 'Final Assy' && form.jenis_mobil === 'MAZDA') ? form.final_inspect_type_id : ''">
            <input type="hidden" name="jenis_defect" :value="form.jenis_defect">
            <input type="hidden" name="sub_defect" :value="form.sub_defect === 'LAIN-LAIN' ? form.custom_sub_defect : form.sub_defect">

            <!-- STEP 1: INPUT FIELDS -->
            <div x-show="step === 1" class="space-y-4 pb-20">

                <!-- AREA / TYPE PICKER (Card Segmented Selector) -->
                @if(!isset($defect))
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">AREA ASSY <span class="text-brand">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" 
                                    @click="form.type = 'Final Assy'; form.line = ''; form.carline_id = '';"
                                    class="min-h-[48px] px-3 py-2.5 rounded-xl text-xs font-extrabold border transition-all text-center flex items-center justify-center space-x-1.5"
                                    :class="form.type === 'Final Assy' ? 'bg-red-50 border-brand text-brand shadow-xs' : 'bg-white border-border text-gray-600 hover:bg-gray-50'">
                                <span class="w-2 h-2 rounded-full" :class="form.type === 'Final Assy' ? 'bg-brand' : 'bg-gray-300'"></span>
                                <span>Final Assy</span>
                            </button>
                            <button type="button" 
                                    @click="form.type = 'Pre Assy'; form.jenis_mobil = ''; form.conveyor = ''; form.final_inspect_type_id = '';"
                                    class="min-h-[48px] px-3 py-2.5 rounded-xl text-xs font-extrabold border transition-all text-center flex items-center justify-center space-x-1.5"
                                    :class="form.type === 'Pre Assy' ? 'bg-red-50 border-brand text-brand shadow-xs' : 'bg-white border-border text-gray-600 hover:bg-gray-50'">
                                <span class="w-2 h-2 rounded-full" :class="form.type === 'Pre Assy' ? 'bg-brand' : 'bg-gray-300'"></span>
                                <span>Pre Assy</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- JENIS MOBIL (Khusus Final Assy) -->
                <div x-show="form.type === 'Final Assy'" class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">JENIS MOBIL <span class="text-brand">*</span></label>
                    <select :name="form.type === 'Final Assy' ? 'jenis_mobil' : ''" x-model="form.jenis_mobil" @change="if(form.jenis_mobil !== 'MAZDA' || form.type !== 'Final Assy') { form.final_inspect_type_id = ''; }" class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand cursor-pointer" :required="form.type === 'Final Assy'">
                        <option value="" disabled selected>Pilih Jenis Mobil...</option>
                        <template x-for="mobil in Object.keys(conveyorMap)" :key="mobil">
                            <option :value="mobil" x-text="mobil"></option>
                        </template>
                    </select>
                </div>

                <!-- KONVEYOR (Khusus Final Assy) -->
                <div x-show="form.type === 'Final Assy'" class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">KONVEYOR <span class="text-brand">*</span></label>
                    <select :name="form.type === 'Final Assy' ? 'conveyor' : ''" x-model="form.conveyor" class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand cursor-pointer disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed" :disabled="!form.jenis_mobil" :required="form.type === 'Final Assy'">
                        <option value="" disabled selected>Pilih Konveyor...</option>
                        <template x-for="conv in currentConveyors" :key="conv">
                            <option :value="conv" x-text="conv"></option>
                        </template>
                    </select>
                </div>

                <!-- CARLINE (Khusus Pre Assy) -->
                <div x-show="form.type === 'Pre Assy'" class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">CARLINE <span class="text-brand">*</span></label>
                    <select x-model="form.carline_id" class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand cursor-pointer" :required="form.type === 'Pre Assy'">
                        <option value="" disabled selected>Pilih Carline...</option>
                        <template x-for="cl in preAssyCarlines" :key="cl.id">
                            <option :value="cl.id" x-text="cl.name"></option>
                        </template>
                    </select>
                </div>

                <!-- QUANTITY INSPECT TYPE (Khusus Pre Assy) -->
                <div x-show="form.type === 'Pre Assy'" class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">QUANTITY INSPECT TYPE</label>
                    <select x-model="form.inspect_process_type_id" class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand cursor-pointer">
                        <option value="">Pilih Quantity Inspect Type (Opsional)...</option>
                        @foreach($inspectProcessTypes as $process)
                            <option value="{{ $process->id }}">{{ $process->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- QUANTITY INSPECT TYPE (Khusus Final Assy & MAZDA) -->
                <div x-show="form.type === 'Final Assy' && form.jenis_mobil === 'MAZDA'" x-transition class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">QUANTITY INSPECT TYPE <span class="text-brand">*</span></label>
                    <select x-model="form.final_inspect_type_id" class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand cursor-pointer" :required="form.type === 'Final Assy' && form.jenis_mobil === 'MAZDA'">
                        <option value="" disabled selected>Pilih Quantity Inspect Type...</option>
                        @if(isset($finalAssyInspectTypes))
                            @foreach($finalAssyInspectTypes as $inspectType)
                                <option value="{{ $inspectType->id }}">{{ $inspectType->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- TANGGAL & JAM TEMUAN (2 Kolom) -->
                <div class="grid grid-cols-2 gap-3">
                    <x-form-input 
                        label="TANGGAL TEMUAN" 
                        type="date" 
                        name="tanggal" 
                        x-model="form.tanggal" 
                        required 
                    />
                    <x-form-input 
                        label="JAM TEMUAN" 
                        type="time" 
                        name="jam" 
                        x-model="form.jam" 
                        required 
                    />
                </div>

                <!-- LINE (Hanya untuk Pre Assy) -->
                <div x-show="form.type === 'Pre Assy'">
                    <x-form-input 
                        label="LINE" 
                        type="text" 
                        name="line" 
                        x-model="form.line" 
                        placeholder="Masukkan Line..." 
                        :required="false" 
                    />
                </div>

                <!-- JENIS DEFECT -->
                <div class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">JENIS DEFECT <span class="text-brand">*</span></label>
                    <select x-model="form.jenis_defect" class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand cursor-pointer" required>
                        <option value="" disabled selected>Pilih Jenis Defect...</option>
                        <template x-for="defect in Object.keys(currentDefectMap)" :key="defect">
                            <option :value="defect" x-text="defect"></option>
                        </template>
                    </select>
                </div>

                <!-- SUB DEFECT -->
                <div class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">JENIS SUB-DEFECT <span class="text-brand">*</span></label>
                    <select x-model="form.sub_defect" class="w-full min-h-[48px] bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand cursor-pointer disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed" :disabled="!form.jenis_defect" required>
                        <option value="" disabled selected>Pilih Sub-Defect...</option>
                        <template x-for="sub in currentSubDefects" :key="sub">
                            <option :value="sub" x-text="sub"></option>
                        </template>
                    </select>

                    <template x-if="form.sub_defect === 'LAIN-LAIN'">
                        <div class="pt-1.5">
                            <x-form-input 
                                type="text" 
                                x-model="form.custom_sub_defect" 
                                placeholder="Ketik sub-defect spesifik di sini..." 
                                required 
                            />
                        </div>
                    </template>
                </div>

                <!-- JUMLAH (QUANTITY) & INSPECT QUANTITY (2 Kolom) -->
                <div class="grid grid-cols-2 gap-3">
                    <x-form-input 
                        label="JUMLAH DEFECT" 
                        type="number" 
                        name="jumlah" 
                        x-model="form.jumlah" 
                        min="1" 
                        placeholder="1" 
                        required 
                    />
                    <x-form-input 
                        label="INSPECT QTY" 
                        type="number" 
                        name="inspect_quantity" 
                        x-model="form.inspect_quantity" 
                        min="0" 
                        placeholder="0" 
                    />
                </div>

                <!-- DITEMUKAN OLEH (Card Segmented Picker 2 Opsi) -->
                <div class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">DITEMUKAN OLEH</label>
                    <input type="hidden" name="ditemukan_oleh" :value="form.ditemukan_oleh">
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" 
                                @click="form.ditemukan_oleh = form.ditemukan_oleh === 'Inspektor' ? '' : 'Inspektor'"
                                class="min-h-[48px] px-3 py-2.5 rounded-xl text-xs font-bold border transition-all text-center flex items-center justify-center space-x-1.5"
                                :class="form.ditemukan_oleh === 'Inspektor' ? 'bg-red-50 border-brand text-brand shadow-xs' : 'bg-white border-border text-gray-600 hover:bg-gray-50'">
                            <span class="w-2 h-2 rounded-full" :class="form.ditemukan_oleh === 'Inspektor' ? 'bg-brand' : 'bg-gray-300'"></span>
                            <span>Inspektor</span>
                        </button>
                        <button type="button" 
                                @click="form.ditemukan_oleh = form.ditemukan_oleh === 'Operator' ? '' : 'Operator'"
                                class="min-h-[48px] px-3 py-2.5 rounded-xl text-xs font-bold border transition-all text-center flex items-center justify-center space-x-1.5"
                                :class="form.ditemukan_oleh === 'Operator' ? 'bg-red-50 border-brand text-brand shadow-xs' : 'bg-white border-border text-gray-600 hover:bg-gray-50'">
                            <span class="w-2 h-2 rounded-full" :class="form.ditemukan_oleh === 'Operator' ? 'bg-brand' : 'bg-gray-300'"></span>
                            <span>Operator</span>
                        </button>
                    </div>
                </div>

                <!-- DYNAMIC FIELDS SECTION -->
                <template x-if="form.type === 'Final Assy'">
                    <div class="space-y-3.5 pt-2 border-t border-gray-100">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Detail Final Assy</span>
                        <x-form-input label="END (#)" type="text" name="end_number" x-model="form.end_number" placeholder="Masukkan END (#)..." />
                        <x-form-input label="SPECIFICATION" type="text" name="specification" x-model="form.specification" placeholder="Masukkan Spesifikasi..." />
                        <x-form-input label="ACTUAL" type="text" name="actual" x-model="form.actual" placeholder="Masukkan Aktual..." />
                        <x-form-input label="AREA DITEMUKAN" type="text" name="area_ditemukan" x-model="form.area_ditemukan" placeholder="Masukkan Area Ditemukan..." />
                        <x-form-input label="JOB STATION" type="text" name="job_station" x-model="form.job_station" placeholder="Masukkan Job Station..." />
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">KETERANGAN (OPSIONAL)</label>
                            <textarea name="keterangan" x-model="form.keterangan" rows="2" placeholder="Catatan tambahan..." class="w-full bg-white border border-border rounded-xl px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all"></textarea>
                        </div>
                    </div>
                </template>

                <template x-if="form.type === 'Pre Assy'">
                    <div class="space-y-3.5 pt-2 border-t border-gray-100">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Detail Pre Assy</span>
                        <x-form-input label="NO TERMINAL" type="text" name="no_terminal" x-model="form.no_terminal" placeholder="Masukkan Nomor Terminal..." />
                        <x-form-input label="NO MESIN" type="text" name="no_mesin" x-model="form.no_mesin" placeholder="Masukkan Nomor Mesin..." />
                    </div>
                </template>

                <!-- Validation Alert Banner -->
                <div x-show="errorMessage" x-text="errorMessage" x-cloak class="p-3.5 rounded-xl bg-red-50 text-brand border border-red-200 text-xs font-bold"></div>

            </div>

            <!-- STEP 1: STICKY BOTTOM ACTION BAR -->
            <div x-show="step === 1" class="fixed sm:sticky bottom-0 left-0 right-0 p-4 bg-white/95 backdrop-blur-md border-t border-border z-20 max-w-md mx-auto">
                <button type="button" @click="goToConfirm" class="w-full min-h-[48px] bg-brand hover:bg-brand-active text-white font-black py-3 px-4 rounded-xl shadow-md shadow-brand/20 active:scale-95 transition-all flex items-center justify-center space-x-2 text-sm tracking-wide">
                    <span>LANJUT KE KONFIRMASI</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>

            <!-- STEP 2: CONFIRMATION SUMMARY -->
            <div x-show="step === 2" x-cloak class="space-y-4 pb-24">
                
                <div class="bg-gray-50/80 rounded-2xl p-4 sm:p-5 border border-border space-y-3.5 text-xs">
                    <div class="flex justify-between items-center pb-2.5 border-b border-border">
                        <span class="font-bold text-gray-500 uppercase tracking-wider text-[11px]">Jenis Laporan</span>
                        <x-status-badge :type="form.type === 'Final Assy' ? 'final-assy' : 'pre-assy'">
                            <span x-text="form.type"></span>
                        </x-status-badge>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="block text-gray-400 font-medium text-[10px] uppercase">Tanggal</span>
                            <span class="block font-extrabold text-gray-900 font-mono mt-0.5" x-text="form.tanggal"></span>
                        </div>
                        <div>
                            <span class="block text-gray-400 font-medium text-[10px] uppercase">Jam</span>
                            <span class="block font-extrabold text-gray-900 font-mono mt-0.5" x-text="form.jam"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3" x-show="form.type === 'Final Assy'">
                        <div>
                            <span class="block text-gray-400 font-medium text-[10px] uppercase">Jenis Mobil</span>
                            <span class="block font-extrabold text-gray-900 mt-0.5" x-text="form.jenis_mobil"></span>
                        </div>
                        <div>
                            <span class="block text-gray-400 font-medium text-[10px] uppercase">Conveyor</span>
                            <span class="block font-extrabold text-gray-900 mt-0.5" x-text="form.conveyor"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3" x-show="form.type === 'Pre Assy'">
                        <div>
                            <span class="block text-gray-400 font-medium text-[10px] uppercase">Carline</span>
                            <span class="block font-extrabold text-gray-900 mt-0.5" x-text="selectedCarlineName"></span>
                        </div>
                        <div x-show="form.inspect_process_type_id">
                            <span class="block text-gray-400 font-medium text-[10px] uppercase">Inspect Process</span>
                            <span class="block font-extrabold text-gray-900 mt-0.5" x-text="selectedInspectProcessName"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3" x-show="form.type === 'Pre Assy' && form.line">
                        <div>
                            <span class="block text-gray-400 font-medium text-[10px] uppercase">Line</span>
                            <span class="block font-extrabold text-gray-900 mt-0.5" x-text="form.line"></span>
                        </div>
                    </div>

                    <div class="h-px bg-gray-200"></div>

                    <div>
                        <span class="block text-gray-400 font-medium text-[10px] uppercase">Jenis Defect</span>
                        <span class="block font-black text-gray-900 font-mono uppercase text-sm mt-0.5" x-text="form.jenis_defect"></span>
                    </div>

                    <div>
                        <span class="block text-gray-400 font-medium text-[10px] uppercase">Sub-Defect</span>
                        <span class="block font-bold text-gray-800 mt-0.5" x-text="form.sub_defect === 'LAIN-LAIN' ? form.custom_sub_defect : form.sub_defect"></span>
                    </div>

                    <div class="p-3 bg-red-50/70 border border-red-100 rounded-xl flex items-center justify-between">
                        <span class="block font-bold text-brand uppercase text-[11px]">Jumlah Defect</span>
                        <span class="block text-lg font-black text-brand font-mono tabular-nums" x-text="form.jumlah + ' Unit'"></span>
                    </div>

                    <div x-show="form.inspect_quantity" class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium text-[11px]">Inspect Quantity:</span>
                        <span class="font-extrabold text-teal-800 font-mono tabular-nums" x-text="form.inspect_quantity + ' Unit'"></span>
                    </div>

                    <div x-show="form.ditemukan_oleh" class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium text-[11px]">Ditemukan Oleh:</span>
                        <span class="font-bold text-gray-900" x-text="form.ditemukan_oleh"></span>
                    </div>

                    <div x-show="form.type === 'Final Assy' && form.jenis_mobil === 'MAZDA' && form.final_inspect_type_id" class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium text-[11px]">Quantity Inspect Type:</span>
                        <span class="font-bold text-teal-800" x-text="selectedFinalInspectTypeName"></span>
                    </div>

                    <!-- Dynamic Fields Confirmation Summary -->
                    <template x-if="form.type === 'Final Assy'">
                        <div class="pt-2 border-t border-gray-200 space-y-1.5 text-[11px]">
                            <div x-show="form.end_number"><span class="text-gray-400">END (#):</span> <span class="font-bold text-gray-900 font-mono" x-text="form.end_number"></span></div>
                            <div x-show="form.specification"><span class="text-gray-400">Specification:</span> <span class="font-bold text-gray-900" x-text="form.specification"></span></div>
                            <div x-show="form.actual"><span class="text-gray-400">Actual:</span> <span class="font-bold text-gray-900" x-text="form.actual"></span></div>
                            <div x-show="form.area_ditemukan"><span class="text-gray-400">Area Ditemukan:</span> <span class="font-bold text-gray-900" x-text="form.area_ditemukan"></span></div>
                            <div x-show="form.job_station"><span class="text-gray-400">Job Station:</span> <span class="font-bold text-gray-900" x-text="form.job_station"></span></div>
                            <div x-show="form.keterangan"><span class="text-gray-400">Keterangan:</span> <span class="font-bold text-gray-900" x-text="form.keterangan"></span></div>
                        </div>
                    </template>

                    <template x-if="form.type === 'Pre Assy'">
                        <div class="pt-2 border-t border-gray-200 space-y-1.5 text-[11px]">
                            <div x-show="form.no_terminal"><span class="text-gray-400">No Terminal:</span> <span class="font-bold text-gray-900 font-mono" x-text="form.no_terminal"></span></div>
                            <div x-show="form.no_mesin"><span class="text-gray-400">No Mesin:</span> <span class="font-bold text-gray-900 font-mono" x-text="form.no_mesin"></span></div>
                        </div>
                    </template>
                </div>

            </div>

            <!-- STEP 2: STICKY BOTTOM ACTION BAR -->
            <div x-show="step === 2" x-cloak class="fixed sm:sticky bottom-0 left-0 right-0 p-4 bg-white/95 backdrop-blur-md border-t border-border z-20 max-w-md mx-auto space-y-2">
                <button type="submit" class="w-full min-h-[48px] bg-brand hover:bg-brand-active text-white font-black py-3 px-4 rounded-xl shadow-md shadow-brand/20 active:scale-95 transition-all flex items-center justify-center space-x-2 text-sm tracking-wide">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    <span>KIRIM LAPORAN</span>
                </button>

                <button type="button" @click="step = 1" class="w-full min-h-[44px] border border-border text-gray-700 font-bold py-2.5 px-4 rounded-xl hover:bg-gray-50 active:scale-95 transition-all text-xs">
                    Kembali ke Input
                </button>
            </div>

        </form>
    </div>

</main>

<script>
    const conveyorMap = @json($carTypes->mapWithKeys(fn($ct) => [$ct->name => $ct->carlines->pluck('name')]));
    const preAssyCarlines = @json($preAssyCarlines ?? []);
    const inspectProcessTypesList = @json($inspectProcessTypes ?? []);
    const finalAssyInspectTypesList = @json($finalAssyInspectTypes ?? []);

    const finalAssyDefects = {
        'INSER CIRCUIT': ['1.A - CROSS CIRCUIT', '1.B - CIRCUIT NOT INSERT', '1.C - WRONG INSERT CIRCUIT', '1.D - WRONG CAVITY', '1.E - MISSING CIRCUIT', '1.F - TPO'],
        'DAMAGE/DEFORM/BROKEN PART': ['2.A - DAMAGE CLIP', '2.B - DAMAGE CONNECTOR', '2.C - DAMAGE GROMMET', '2.D - DAMAGE / SCRATCH INSULATION', '2.E - DAMAGE PROTECTOR', '2.F - DAMAGE SPACER', '2.G - DAMAGE TUBE', '2.H - DAMAGE BOLT / TORQUE', '2.I - DAMAGE R/B', '2.J - DAMAGE FUSE', '2.K - DAMAGE RELAY ', '2.L - DAMAGE N/P', '2.M - DAMAGE COVER', '2.N - DAMAGE SEAL RUBBER', '2.O - DAMAGE BRACKET CONNECTOR', '2.P - DAMAGE WASHER HOSE','2.Q - CUT WIRE', '2.R - DAMAGE USB', '2.S - BENT TERMINAL','2.T - DEFORM TERMINAL','2.U - BROKEN TERMINAL', '2.V - FLARE TERMINAL'],
        'MISSING PART': ['3.A - MISSING CLIP', '3.B - MISSING COVER', '3.C - MISSING GREASE', '3.D - MISSING GROMMET', '3.E - MISSING PROTECTOR', '3.F - MISSING SEAL RUBBER', '3.G - MISSING SPACER', '3.H - MISSING SPOT TAPE', '3.I - MISSING FOAM TAPE', '3.J - MISSING TIE BACK', '3.K - MISSING TUBE', '3.L - MISSING JC / BUSSBAR', '3.M - MISSING PULLER', '3.N - MISSING PLUG', '3.O - MISSING FUSE', '3.P - MISSING RELAY', '3.Q - MISSING N/P', '3.R - MISSING MARKING / STAMP N/P', '3.S - MISSING SOLDER', '3.T - MISSING USB CABLE', '3.U - BRACKET CONNECTOR ', '3.V - WASHER HOSE'],
        'DIMENSON DEFECT': ['4.A - DIMENSION BRANCH', '4.B - DIMENSION TRUNK', '4.C - DIMENSION CLIP', '4.D - DIMENSION PROTECTOR', '4.E - DIMENSION GROMMET', '4.F - DIMENSION TUBE', '4.G - DIM.Y'],
        'HALF LOCK / INCOMPLETE DOCKING': ['5.A - HALF LOCK SPACER / RETAINER', '5.B - MISALIGN', '5.C - HALF LOCK DOCKING J/C', '5.D - HALF LOCK DOCKING LA TERMINAL', '5.E - HALF LOCK COVER R/B', '5.F - HALF LOCK PROTECTOR', '5.G - HALF LOCK INSERT FUSE', '5.H - HALF LOCK INSERT RELAY', '5.I - LOOSE TORQUE'],
        'WRONG PART': ['6.A - CRACK', '6.B - MISALIGN', '6.C - WRONG CIRCUIT', '6.D - WRONG CLIP', '6.E - WRONG COVER', '6.F - WRONG TAPE', '6.G - WRONG GROMMET', '6.H - WRONG PROTECTOR', '6.I - WRONG SEAL RUBBER', '6.J - WRONG SPACER / HOLDER', '6.K - WRONG FOAM TAPE', '6.L - WRONG TUBE', '6.M - WRONG JC / BUSSBAR', '6.N - WRONG PLUG','6.O - WRONG FUSE','6.P - WRONG RELAY','6.Q - WRONG N/P'],
        'TAPING DEFECT': ['7.A - WRONG TAPING METHOD', '7.B - MISSING TAPING', '7.C - WRONG SPOT TAPE', '7.D - WRONG TIE BACK', '7.E - TAPING BENDERA'],
        'WRONG ORIENTATION PART': ['8.A - ORIENTASI CLIP', '8.B - ORIENTASI BRANCH', '8.C - ORIENTASI GROMMET', '8.D - ORIENTASI COVER CONN.', '8.E - ORIENTASI N/P', '8.F - ORIENTASI TIE BACK' ],
        'CUTTING - CRIMPING PRE ASSY DEFECT': ['9.A - SALAH BENTUK REAR CRIMPING', '9.B - BUTHYL MELELEH', '9.C - OVER MELT SHRINK TUBE', '9.D - SOLDER N-OK', '9.E - RAYCHAM N-OK', '9.F - BONDER LEPAS', '9.G - OVER CIRCUIT BONDER', '9.H - MISSING CIRCUIT BONDER', '9.I - SALAH CIRCUIT BONDER', '9.J - SALAH KIND WIRE ', '9.K - SALAH SIZE WIRE', '9.L - INSULATION MUNDUR', '9.M - SEAL RUBBER MUNDUR', '9.N - FRAYING CORE', '9.O - CRACK TERMINAL' ],
        'INJECTION GROMMET / SISUI DEFECT': ['10.A - INJECTION GROMMET BERGELEMBUNG', '10.B - INJECTION GROMMET KURANG', '10.C - INJECTION GROMMET TDK MATANG', '10.D - SISUI BOCOR'],
        'LAIN-LAIN': ['11.A - FOREIGN MATERIAL', '11.B - CIRCUIT TERJEPIT', '11.C - AIR CHECKER N-OK', '11.D - BAND CLIP KEPENDEKAN', '11.E - BAND CLIP PANJANG'],
    };

    const preAssyDefects = {
        'CORE': ['A.1 - FRAYING', 'A.2 - CUT CORE', 'A.3 - TIDAK TERATUR', 'A.4 - MAJU','A.5 - MUNDUR', 'A.6 - TIDAK TERCRIMPING', 'A.7 - SCRATCH'],
        'TERMINAL': ['B.1 - TERGORES', 'B.2 - BENT UP','B.3 - BENT DOWN', 'B.4 - MELINTIR', 'B.5 - UJUNG TERPOTONG', 'B.6 - OPEN/FLARE', 'B.7 - DEFORM', 'B.8 - BRIDGE TERLALU PANJANG', 'B.9 - CANTILEVER RUSAK', 'B.10 - LEPAS DARI CIRCUIT'],
        'FRONT CRIMPING': ['C.1 - C/H TERLALU TINGGI', 'C.2 - C/H TERLALU RENDAH','C.3 - C/W TERLALU TINGGI', 'C.4 - C/W TERLALU RENDAH', 'C.5 - FLASH'],
        'REAR CRIMPING': ['D.1 - C/H - TERLALU TINGGI', 'D.2 - C/H TERLALU RENDAH', 'D.3 - C/W TERLALU TINGGI', 'D.4 - C/W TERLALU RENDAH', 'D.5 - ADA DI DALAM INSULASI', 'D.6 - TIDAK SEIMBANG'],
        'INSULATION': ['E.1 - TERCRIMPING', 'E.2 - TERLALU MUNDUR', 'E.3 - DAMAGE', 'E.4 - TIDAK RATA'],
        'SEAL SUMBER': ['F.1 - TERPOTONG', 'F.2 - TERBALIK', 'F.3 - TERLALU MUNDUR', 'F.4 - TERLALU MAJU', 'F.5 - TERCRIMPING', 'F.6 - MISSING', 'F.7 - SEAL SOBEK'],
        'CRIMPING': ['G.1 - FOREIGN MATERIAL', 'G.2 - ADB.1 TERMMINAL TERCIMPING', 'G.3 - NO CORE', 'G.4 - NO STRIPPING'],
        'LAIN-LAIN': ['H.1 - LANCE RUSAK', 'H.2 - STABILIZER RUSAK', 'H.3 - BELLMOUTH TIDAK STANDART', 'H.4 - KONDISI CORE BAG.A', 'H.5 - RESIN MASUK BAG.A', 'H.6 - RESIN BAREL BAG.B TERBUKA', 'H.7 - CORE TERLIHAT ATAS SISI C', 'H.8 - CORE TERLIHAT SAMPING SISI C', 'H.9 - SISI PUNGGUNG', 'H.10 - ABNORMAL RESIN', 'H.11 - PANJANG WELDING N-OK', 'H.12 - CIRCUIT TIDAK TERBONDER', 'H.13 - BONDER RETAK', 'H.14 - STRIPPING KEPANJANGAN'],
    };

    function defectForm() {
        return {
            step: 1,
            errorMessage: '',
            conveyorMap: conveyorMap,
            preAssyCarlines: preAssyCarlines,
            inspectProcessTypesList: inspectProcessTypesList,
            finalAssyInspectTypesList: finalAssyInspectTypesList,
            
            form: {
                type: '{{ old("type", $defect->jenis_assy ?? ($type ?? "Final Assy")) }}',
                jenis_mobil: '{{ old("jenis_mobil", $defect->jenis_mobil ?? "") }}',
                conveyor: '{{ old("conveyor", $defect->conveyor ?? "") }}',
                carline_id: '{{ old("carline_id", $defect->carline_id ?? "") }}',
                inspect_process_type_id: '{{ old("inspect_process_type_id", $defect->inspect_process_type_id ?? "") }}',
                final_inspect_type_id: '{{ old("final_inspect_type_id", $defect->final_inspect_type_id ?? "") }}',
                tanggal: '{{ old("tanggal", isset($defect) ? \Carbon\Carbon::parse($defect->waktu)->format("Y-m-d") : now()->format("Y-m-d")) }}',
                jam: '{{ old("jam", isset($defect) ? \Carbon\Carbon::parse($defect->waktu)->format("H:i") : now()->format("H:i")) }}',
                line: '{{ old("line", $defect->line_conveyor ?? "") }}',
                jenis_defect: '{{ old("jenis_defect", $defect->jenis_defect ?? "") }}',
                sub_defect: '{{ old("sub_defect", $defect->jenis_sub_defect ?? "") }}',
                custom_sub_defect: '',
                jumlah: {{ old("jumlah", $defect->quantity ?? 1) }},
                inspect_quantity: '{{ old("inspect_quantity", $defect->inspect_quantity ?? "") }}',
                ditemukan_oleh: '{{ old("ditemukan_oleh", $defect->ditemukan_oleh ?? "") }}',
                
                end_number: '{{ old("end_number", $defect->end_number ?? "") }}',
                specification: '{{ old("specification", $defect->specification ?? "") }}',
                actual: '{{ old("actual", $defect->actual ?? "") }}',
                area_ditemukan: '{{ old("area_ditemukan", $defect->area_ditemukan ?? "") }}',
                job_station: '{{ old("job_station", $defect->job_station ?? "") }}',
                keterangan: '{{ old("keterangan", $defect->keterangan ?? "") }}',
                
                no_terminal: '{{ old("no_terminal", $defect->no_terminal ?? "") }}',
                no_mesin: '{{ old("no_mesin", $defect->no_mesin ?? "") }}'
            },

            get selectedCarlineName() {
                if (!this.form.carline_id) return '-';
                const c = this.preAssyCarlines.find(item => String(item.id) === String(this.form.carline_id));
                return c ? c.name : '-';
            },

            get selectedInspectProcessName() {
                if (!this.form.inspect_process_type_id) return '-';
                const p = this.inspectProcessTypesList.find(item => String(item.id) === String(this.form.inspect_process_type_id));
                return p ? p.name : '-';
            },

            get selectedFinalInspectTypeName() {
                if (!this.form.final_inspect_type_id) return '-';
                const p = this.finalAssyInspectTypesList.find(item => String(item.id) === String(this.form.final_inspect_type_id));
                return p ? p.name : '-';
            },

            get currentConveyors() {
                return this.form.jenis_mobil ? this.conveyorMap[this.form.jenis_mobil] : [];
            },

            get currentDefectMap() {
                return this.form.type === 'Final Assy' ? finalAssyDefects : preAssyDefects;
            },

            get currentSubDefects() {
                if (!this.form.jenis_defect) return [];
                const list = [...(this.currentDefectMap[this.form.jenis_defect] || [])];
                if (!list.includes('LAIN-LAIN')) list.push('LAIN-LAIN');
                return list;
            },

            initData() {
                const initialConveyor = '{{ old("conveyor", $defect->conveyor ?? "") }}';
                const initialDefect = '{{ old("jenis_defect", $defect->jenis_defect ?? "") }}';
                const initialSubDefect = '{{ old("sub_defect", $defect->jenis_sub_defect ?? "") }}';

                if (this.form.type === 'Final Assy') {
                    this.form.line = '';
                }

                this.$nextTick(() => {
                    if (initialConveyor) {
                        this.form.conveyor = initialConveyor;
                    }
                    if (initialDefect) {
                        this.form.jenis_defect = initialDefect;
                    }
                    this.$nextTick(() => {
                        if (initialSubDefect) {
                            const subs = this.currentSubDefects;
                            if (subs.includes(initialSubDefect)) {
                                this.form.sub_defect = initialSubDefect;
                            } else if (initialSubDefect) {
                                this.form.sub_defect = 'LAIN-LAIN';
                                this.form.custom_sub_defect = initialSubDefect;
                            }
                        }
                    });
                });
            },

            goToConfirm() {
                this.errorMessage = '';
                if (this.form.type === 'Final Assy') {
                    if (!this.form.jenis_mobil || !this.form.conveyor || !this.form.tanggal || !this.form.jam || !this.form.jenis_defect || !this.form.sub_defect || !this.form.jumlah) {
                        this.errorMessage = 'Mohon lengkapi seluruh field wajib (Jenis Mobil, Konveyor, Tanggal, Jam, Defect, Sub-defect, Jumlah).';
                        return;
                    }
                    this.form.line = '';
                    this.form.carline_id = '';
                    if (this.form.jenis_mobil === 'MAZDA' && !this.form.final_inspect_type_id) {
                        this.errorMessage = 'Mohon pilih Quantity Inspect Type untuk mobil MAZDA.';
                        return;
                    }
                } else if (this.form.type === 'Pre Assy') {
                    if (!this.form.carline_id || !this.form.line || !this.form.tanggal || !this.form.jam || !this.form.jenis_defect || !this.form.sub_defect || !this.form.jumlah) {
                        this.errorMessage = 'Mohon lengkapi seluruh field wajib (Carline, Line, Tanggal, Jam, Defect, Sub-defect, Jumlah).';
                        return;
                    }
                    this.form.jenis_mobil = '';
                    this.form.conveyor = '';
                    this.form.final_inspect_type_id = '';
                }

                if (this.form.sub_defect === 'LAIN-LAIN' && !this.form.custom_sub_defect.trim()) {
                    this.errorMessage = 'Mohon ketikkan rincian sub-defect pada kolom LAIN-LAIN.';
                    return;
                }
                this.step = 2;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };
    }
</script>
@endsection
