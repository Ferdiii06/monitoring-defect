<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($defect) ? 'Edit' : 'Report' }} Defect - Sistem Monitoring Defect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-6 px-4 flex justify-center items-start">

    <!-- Mobile-First Standalone Card Container -->
    <main class="w-full max-w-md bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 flex flex-col" x-data="defectForm()">

        <!-- Header: Back Button + Title -->
        <div class="p-6 pb-2 flex items-center space-x-3">
            <a href="{{ route('operator.home') }}" class="text-[#8b0000] hover:text-red-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-xl font-bold text-[#8b0000]">
                {{ isset($defect) ? 'Edit Report Defect' : 'Report Defect' }} <span x-text="form.type"></span>
            </h1>
        </div>


        <!-- Progress Indicator Bar -->
        <div class="px-6 pt-4 pb-2 space-y-2">
            <div class="flex justify-between items-center text-xs">
                <span class="font-medium text-gray-400" x-text="step === 1 ? 'Langkah 1 dari 2' : 'Langkah 2 dari 2'"></span>
                <span class="font-bold text-[#8b0000]" x-text="step === 1 ? '{{ isset($defect) ? "Informasi Dasar (Edit)" : "Informasi Dasar" }}' : 'Konfirmasi Laporan'"></span>
            </div>

            <!-- Two-segment Progress Line -->
            <div class="grid grid-cols-2 gap-1 h-1.5 w-full">
                <div class="bg-[#8b0000] rounded-full"></div>
                <div class="rounded-full transition-colors duration-200" :class="step === 2 ? 'bg-[#8b0000]' : 'bg-red-100'"></div>
            </div>
        </div>

        <!-- Main Form Container -->
        <div class="p-6 pt-4">

            @if ($errors->any())
                <div class="mb-4 bg-red-50 text-[#8b0000] text-xs font-semibold p-3.5 rounded-xl border border-red-200">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ isset($defect) ? route('input_defect.update', $defect->id) : route('input_defect.store') }}" method="POST" id="defectForm">
                @csrf
                @if(isset($defect))
                    @method('PUT')
                @endif

                <input type="hidden" name="type" x-model="form.type">
                <input type="hidden" name="jenis_defect" x-model="form.jenis_defect">
                <input type="hidden" name="sub_defect" :value="form.sub_defect === 'LAIN-LAIN' ? form.custom_sub_defect : form.sub_defect">

                <!-- STEP 1: INPUT FIELDS -->
                <div x-show="step === 1" class="space-y-4">

                    <!-- JENIS MOBIL -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">JENIS MOBIL <span class="text-red-500">*</span></label>
                        <select name="jenis_mobil" x-model="form.jenis_mobil" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer" required>
                            <option value="" disabled selected>Pilih Jenis Mobil...</option>
                            <template x-for="mobil in Object.keys(conveyorMap)" :key="mobil">
                                <option :value="mobil" x-text="mobil"></option>
                            </template>
                        </select>
                    </div>

                    <!-- KONVEYOR -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">KONVEYOR <span class="text-red-500">*</span></label>
                        <select name="conveyor" x-model="form.conveyor" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer" :disabled="!form.jenis_mobil" required>
                            <option value="" disabled selected>Pilih Konveyor...</option>
                            <template x-for="conv in currentConveyors" :key="conv">
                                <option :value="conv" x-text="conv"></option>
                            </template>
                        </select>
                    </div>

                    <!-- TANGGAL TEMUAN -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">TANGGAL TEMUAN <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal" x-model="form.tanggal" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]" required>
                    </div>

                    <!-- LINE -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">LINE <span class="text-red-500">*</span></label>
                        <input type="text" name="line" x-model="form.line" placeholder="Masukkan Line..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]" required>
                    </div>

                    <!-- JENIS DEFECT -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">JENIS DEFECT <span class="text-red-500">*</span></label>
                        <select x-model="form.jenis_defect" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer" required>
                            <option value="" disabled selected>Pilih Jenis Defect...</option>
                            <template x-for="defect in Object.keys(currentDefectMap)" :key="defect">
                                <option :value="defect" x-text="defect"></option>
                            </template>
                        </select>
                    </div>

                    <!-- SUB DEFECT -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">JENIS SUB-DEFECT <span class="text-red-500">*</span></label>
                        <select x-model="form.sub_defect" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000] cursor-pointer" :disabled="!form.jenis_defect" required>
                            <option value="" disabled selected>Pilih Sub-Defect...</option>
                            <template x-for="sub in currentSubDefects" :key="sub">
                                <option :value="sub" x-text="sub"></option>
                            </template>
                        </select>

                        <template x-if="form.sub_defect === 'LAIN-LAIN'">
                            <input type="text" x-model="form.custom_sub_defect" placeholder="Ketik sub-defect spesifik di sini..." class="mt-2.5 w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]" required>
                        </template>
                    </div>

                    <!-- JUMLAH (QUANTITY) -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">JUMLAH (QUANTITY) <span class="text-red-500">*</span></label>
                        <input type="number" name="jumlah" x-model="form.jumlah" min="1" placeholder="1" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]" required>
                    </div>

                    <!-- DYNAMIC FIELDS SECTION -->
                    <template x-if="form.type === 'Final Assy'">
                        <div class="space-y-4 pt-2">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">END (#)</label>
                                <input type="text" name="end_number" x-model="form.end_number" placeholder="Masukkan END (#)..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">SPECIFICATION</label>
                                <input type="text" name="specification" x-model="form.specification" placeholder="Masukkan Spesifikasi..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">ACTUAL</label>
                                <input type="text" name="actual" x-model="form.actual" placeholder="Masukkan Aktual..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">AREA DITEMUKAN</label>
                                <input type="text" name="area_ditemukan" x-model="form.area_ditemukan" placeholder="Masukkan Area Ditemukan..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">JOB STATION</label>
                                <input type="text" name="job_station" x-model="form.job_station" placeholder="Masukkan Job Station..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                            </div>
                        </div>
                    </template>

                    <template x-if="form.type === 'Pre Assy'">
                        <div class="space-y-4 pt-2">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">NO TERMINAL</label>
                                <input type="text" name="no_terminal" x-model="form.no_terminal" placeholder="Masukkan Nomor Terminal..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">NO MESIN</label>
                                <input type="text" name="no_mesin" x-model="form.no_mesin" placeholder="Masukkan Nomor Mesin..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8b0000] focus:border-[#8b0000]">
                            </div>
                        </div>
                    </template>

                    <!-- Validation Alert Banner -->
                    <div x-show="errorMessage" x-text="errorMessage" x-cloak class="p-3 rounded-xl bg-red-50 text-[#8b0000] border border-red-200 text-xs font-semibold"></div>

                    <!-- Step 1 Next Button -->
                    <div class="pt-4 space-y-3">
                        <button type="button" @click="goToConfirm" class="w-full bg-[#8b0000] hover:bg-red-900 text-white font-bold py-3.5 rounded-xl shadow-md transition-all duration-200 flex items-center justify-center space-x-2 text-sm">
                            <span>LANJUT KE KONFIRMASI</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>

                        <!-- Centered 2 Dots Indicator -->
                        <div class="flex justify-center items-center space-x-1.5 pt-1">
                            <span class="w-2 h-2 rounded-full bg-[#8b0000]"></span>
                            <span class="w-2 h-2 rounded-full bg-red-100"></span>
                        </div>
                    </div>

                </div>

                <!-- STEP 2: CONFIRMATION SUMMARY -->
                <div x-show="step === 2" x-cloak class="space-y-4">
                    
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 space-y-3 text-xs">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                            <span class="font-bold text-gray-500 uppercase">Jenis Report</span>
                            <span class="font-extrabold text-[#8b0000] bg-red-100 px-2.5 py-0.5 rounded-md" x-text="form.type"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="block text-gray-400 font-medium">Tanggal</span>
                                <span class="block font-bold text-gray-900 mt-0.5" x-text="form.tanggal"></span>
                            </div>
                            <div>
                                <span class="block text-gray-400 font-medium">Line</span>
                                <span class="block font-bold text-gray-900 mt-0.5" x-text="form.line"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="block text-gray-400 font-medium">Jenis Mobil</span>
                                <span class="block font-bold text-gray-900 mt-0.5" x-text="form.jenis_mobil"></span>
                            </div>
                            <div>
                                <span class="block text-gray-400 font-medium">Conveyor</span>
                                <span class="block font-bold text-gray-900 mt-0.5" x-text="form.conveyor"></span>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        <div>
                            <span class="block text-gray-400 font-medium">Jenis Defect</span>
                            <span class="block font-bold text-gray-900 mt-0.5" x-text="form.jenis_defect"></span>
                        </div>

                        <div>
                            <span class="block text-gray-400 font-medium">Sub-Defect</span>
                            <span class="block font-bold text-gray-900 mt-0.5" x-text="form.sub_defect === 'LAIN-LAIN' ? form.custom_sub_defect : form.sub_defect"></span>
                        </div>

                        <div>
                            <span class="block text-gray-400 font-medium">Jumlah (Quantity)</span>
                            <span class="block text-base font-extrabold text-[#8b0000] mt-0.5" x-text="form.jumlah + ' Unit'"></span>
                        </div>

                        <!-- Dynamic Fields Confirmation Summary -->
                        <template x-if="form.type === 'Final Assy'">
                            <div class="pt-2 border-t border-gray-200 space-y-2">
                                <div x-show="form.end_number"><span class="text-gray-400">END (#):</span> <span class="font-bold text-gray-900" x-text="form.end_number"></span></div>
                                <div x-show="form.specification"><span class="text-gray-400">Specification:</span> <span class="font-bold text-gray-900" x-text="form.specification"></span></div>
                                <div x-show="form.actual"><span class="text-gray-400">Actual:</span> <span class="font-bold text-gray-900" x-text="form.actual"></span></div>
                                <div x-show="form.area_ditemukan"><span class="text-gray-400">Area Ditemukan:</span> <span class="font-bold text-gray-900" x-text="form.area_ditemukan"></span></div>
                                <div x-show="form.job_station"><span class="text-gray-400">Job Station:</span> <span class="font-bold text-gray-900" x-text="form.job_station"></span></div>
                            </div>
                        </template>

                        <template x-if="form.type === 'Pre Assy'">
                            <div class="pt-2 border-t border-gray-200 space-y-2">
                                <div x-show="form.no_terminal"><span class="text-gray-400">No Terminal:</span> <span class="font-bold text-gray-900" x-text="form.no_terminal"></span></div>
                                <div x-show="form.no_mesin"><span class="text-gray-400">No Mesin:</span> <span class="font-bold text-gray-900" x-text="form.no_mesin"></span></div>
                            </div>
                        </template>
                    </div>

                    <!-- Action Buttons Step 2 -->
                    <div class="pt-3 space-y-2">
                        <!-- Submit Form Button -->
                        <button type="submit" class="w-full bg-[#8b0000] hover:bg-red-900 text-white font-bold py-3.5 rounded-xl shadow-md transition-all duration-200 flex items-center justify-center space-x-2 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            <span>KIRIM LAPORAN</span>
                        </button>

                        <!-- Back to Edit Button -->
                        <button type="button" @click="step = 1" class="w-full border border-gray-300 text-gray-700 font-semibold py-3 rounded-xl hover:bg-gray-50 transition-colors text-sm">
                            Kembali ke Input
                        </button>

                        <!-- Centered 2 Dots Indicator -->
                        <div class="flex justify-center items-center space-x-1.5 pt-2">
                            <span class="w-2 h-2 rounded-full bg-red-100"></span>
                            <span class="w-2 h-2 rounded-full bg-[#8b0000]"></span>
                        </div>
                    </div>

                </div>

            </form>
        </div>

    </main>

    <script>
        const conveyorMap = {
          "TOYOTA": [
            "664W-C5", "664W-C5C", "664W-C5A", "664W-C5B", "664W-C5D", "711W TNGA-C5", "711W TNGA-C5A", "737W TNGA-C5A", "737W TNGA-C5",
            "738W-C5C", "858W-C5C", "810W-C5", "941W-C5", "023J-C5", "072Y-C5", "718W-AB5.HEV", "718W-C4.CONV", "718W-C4.TNGA", "891W/892W-C1.GAS LHD",
            "853W-AT2.HEV LHD", "853W-AT6.GAS LHD", "853W-AT16.GAS LHD", "852W-AT19.HEV PHV LHD", "852W-AT2.HEV PHV LHD", "852W-AT19.HEV PHV RHD",
            "852W-AT6.GAS LHD", "909W-AT7.GAS LHD", "909W-AT11.HEV LHD", "909W-AT9.GAS LHD", "910W-AT7.GAS LHD", "910W-AT11.HEV LHD",
            "910W-AT9.GAS LHD", "953W-C6.HEV RHD", "953W-C6.HEV LHD", "953W ENG NO.3-C9", "898W-AB5.HEV", "898W-C4.CONV", "898W-C4.TNGA"
          ],
          "NISSAN": ["P33A-B1.BAT", "P33A-B1.CELL", "J32V-B2.LHD", "J32V-B2.RHD", "J42U-B3.EGI", "J42U-B3.ENGINE", "J42U-B2.DOOR RH", "J42U-B2.DOOR LH", "P33C-B1.BAT", "P33C-B1.CELL"],
          "MAZDA": ["J72A-12B.LHD", "J72A-AB9.RHD", "J72A-16C.LHD", "J72K-16C.LHD", "J30A-AB6.EXTEND LHD", "J30A-AB1.INPANEL LHD", "J30A-AB6.EXTEND RHD", "J30A-AB1.INPANEL RHD", "J69P-AB8.EXTEND LHD", "J69P-AB8.INPANEL LHD", "J69P-AB8.EXTEND RHD", "J69P-AB8.INPANEL RHD", "J69P-AB9.EXTEND LHD", "J69P-AB3.INPANEL LHD"]
        };

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

        document.addEventListener('alpine:init', () => {
            Alpine.data('defectForm', () => ({
                step: 1,
                errorMessage: '',
                conveyorMap: conveyorMap,
                
                form: {
                    type: '{{ old("type", $defect->jenis_assy ?? ($type ?? "Final Assy")) }}',
                    jenis_mobil: '{{ old("jenis_mobil", $defect->jenis_mobil ?? "") }}',
                    conveyor: '{{ old("conveyor", $defect->conveyor ?? "") }}',
                    tanggal: '{{ old("tanggal", isset($defect) ? \Carbon\Carbon::parse($defect->waktu)->format("Y-m-d") : now()->format("Y-m-d")) }}',
                    line: '{{ old("line", $defect->line_conveyor ?? "") }}',
                    jenis_defect: '{{ old("jenis_defect", $defect->jenis_defect ?? "") }}',
                    sub_defect: '{{ old("sub_defect", $defect->jenis_sub_defect ?? "") }}',
                    custom_sub_defect: '',
                    jumlah: {{ old("jumlah", $defect->quantity ?? 1) }},
                    
                    end_number: '{{ old("end_number", $defect->end_number ?? "") }}',
                    specification: '{{ old("specification", $defect->specification ?? "") }}',
                    actual: '{{ old("actual", $defect->actual ?? "") }}',
                    area_ditemukan: '{{ old("area_ditemukan", $defect->area_ditemukan ?? "") }}',
                    job_station: '{{ old("job_station", $defect->job_station ?? "") }}',
                    
                    no_terminal: '{{ old("no_terminal", $defect->no_terminal ?? "") }}',
                    no_mesin: '{{ old("no_mesin", $defect->no_mesin ?? "") }}'
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

                goToConfirm() {
                    this.errorMessage = '';
                    if (!this.form.jenis_mobil || !this.form.conveyor || !this.form.line || !this.form.jenis_defect || !this.form.sub_defect || !this.form.jumlah) {
                        this.errorMessage = 'Mohon lengkapi seluruh field wajib (Jenis Mobil, Konveyor, Line, Defect, Sub-defect, Jumlah).';
                        return;
                    }
                    if (this.form.sub_defect === 'LAIN-LAIN' && !this.form.custom_sub_defect.trim()) {
                        this.errorMessage = 'Mohon ketikkan rincian sub-defect pada kolom LAIN-LAIN.';
                        return;
                    }
                    this.step = 2;
                }
            }));
        });
    </script>
</body>
</html>
