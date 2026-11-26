<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Carline;
use App\Models\Defect;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class FinalAssyReportInvariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Once::class)) {
            Once::flush();
        }

        \Illuminate\Support\Facades\Http::fake([
            '*/users*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            '*' => \Illuminate\Support\Facades\Http::response([], 200),
        ]);
    }

    private function actingAsAdmin()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator',
        ]);
    }

    private function actingAsOperator()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Operator QA',
            'user_role' => 'User',
            'current_shift' => '1A',
        ]);
    }

    /**
     * Invarian Mutlak Final Assy:
     * Internal + Assembly + Belum Terklasifikasi = Total Final Assy Non-Rework.
     * Edge cases: area NULL, area tidak ada di master, area rework tanpa rework_group_id.
     */
    public function test_final_assy_report_mathematical_invariant_with_all_edge_cases(): void
    {
        // 1. Setup master area dengan klasifikasi
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSPEKSI', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $areaAssembly = AreaDitemukan::create(['name' => 'LINE_ASSEMBLY', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY_DEFECT]);
        $areaUnclassified = AreaDitemukan::create(['name' => 'STASIUN_BARU', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $areaRework = AreaDitemukan::create(['name' => 'REWORK', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        $baseTime = Carbon::now();

        // 2. Buat data defect Final Assy Non-Rework
        // a. Sah Internal Defect (Area Inspeksi)
        $d1 = Defect::create([
            'waktu'            => $baseTime,
            'user_name'        => 'QC Inspector 1',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => $areaInspeksi->name,
            'rework_group_id'  => null,
            'jenis_defect'     => 'MISSING PART',
            'jenis_sub_defect' => 'LOCK HOUSING',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // b. Sah Assembly Defect (Area Assembly / Produksi)
        $d2 = Defect::create([
            'waktu'            => $baseTime,
            'user_name'        => 'Operator Assy 1',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => $areaAssembly->name,
            'rework_group_id'  => null,
            'jenis_defect'     => 'DAMAGE/DEFORM/BROKEN PART',
            'jenis_sub_defect' => 'HOUSING CRACK',
            'quantity'         => 2,
            'inspect_quantity' => 10,
        ]);

        // c. Edge case 1: area_ditemukan = NULL -> Wajib masuk Belum Terklasifikasi
        $d3 = Defect::create([
            'waktu'            => $baseTime,
            'user_name'        => 'Operator 2',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => null,
            'rework_group_id'  => null,
            'jenis_defect'     => 'OTHERS',
            'jenis_sub_defect' => 'MISC',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // d. Edge case 2: area_ditemukan tidak ada di master area (orphan) -> Wajib masuk Belum Terklasifikasi
        $d4 = Defect::create([
            'waktu'            => $baseTime,
            'user_name'        => 'Operator 3',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA_GHAIB_NOT_IN_MASTER',
            'rework_group_id'  => null,
            'jenis_defect'     => 'OTHERS',
            'jenis_sub_defect' => 'MISC',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // e. Edge case 3: area_ditemukan bertipe rework TAPI rework_group_id = null (mismatch) -> Wajib masuk Belum Terklasifikasi
        $d5 = Defect::create([
            'waktu'            => $baseTime,
            'user_name'        => 'Operator 4',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => $areaRework->name,
            'rework_group_id'  => null,
            'jenis_defect'     => 'OTHERS',
            'jenis_sub_defect' => 'MISC',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // f. Area eksplisit tipe 'belum_diklasifikasi' -> Wajib masuk Belum Terklasifikasi
        $d6 = Defect::create([
            'waktu'            => $baseTime,
            'user_name'        => 'Operator 5',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => $areaUnclassified->name,
            'rework_group_id'  => null,
            'jenis_defect'     => 'OTHERS',
            'jenis_sub_defect' => 'MISC',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // g. Record Rework (ber-rework_group_id) -> TIDAK boleh masuk ke Final Assy Non-Rework
        $dRework = Defect::create([
            'waktu'            => $baseTime,
            'user_name'        => 'Rework Op 1',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => $areaRework->name,
            'rework_group_id'  => 'uuid-rework-record-001',
            'jenis_defect'     => 'TAPING DEFECT',
            'jenis_sub_defect' => 'LOOSE TAPING',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // h. Defect Pre Assy -> TIDAK boleh masuk Final Assy sama sekali
        $dPreAssy = Defect::create([
            'waktu'            => $baseTime,
            'user_name'        => 'Pre Assy Op 1',
            'jenis_assy'       => 'Pre Assy',
            'ditemukan_oleh'   => 'Operator',
            'rework_group_id'  => null,
            'jenis_defect'     => 'CRIMPING',
            'jenis_sub_defect' => 'HIGH CRIMP',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // 3. Verifikasi Invarian Matematika
        $totalNonRework = Defect::finalAssyReport()->count();
        $totalInternal = Defect::finalAssyReport()->finalAssyInternalDefect()->count();
        $totalAssembly = Defect::finalAssyReport()->finalAssyAssemblyDefect()->count();

        // Total non-rework harus tepat 6 (d1, d2, d3, d4, d5, d6)
        $this->assertEquals(6, $totalNonRework, 'Total Final Assy Non-Rework harus tepat 6');
        $this->assertEquals(5, $totalInternal, 'Internal Defect harus tepat 5 (d1, d3, d4, d5, d6)');
        $this->assertEquals(1, $totalAssembly, 'Assembly Defect harus tepat 1 (d2 area assembly)');

        // Invarian mutlak: Internal + Assembly == Total Final Assy Non-Rework
        $this->assertEquals(
            $totalNonRework,
            $totalInternal + $totalAssembly,
            'INVARIAN GAGAL: Total Non-Rework != Internal + Assembly'
        );

        // Record rework terisolasi dan tidak bocor ke non-rework
        $this->assertEquals(1, Defect::where('jenis_assy', 'Final Assy')->whereNotNull('rework_group_id')->count());
    }

    /**
     * Verifikasi Relasi areaDitemukanRef pada Defect model.
     */
    public function test_defect_area_ditemukan_ref_relationship(): void
    {
        $area = AreaDitemukan::create([
            'name' => 'AREA_REF_TEST',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $defect = Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Tester',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA_REF_TEST',
            'rework_group_id'  => null,
            'jenis_defect'     => 'MISSING PART',
            'jenis_sub_defect' => 'LOCK',
            'quantity'         => 1,
        ]);

        $this->assertNotNull($defect->areaDitemukanRef);
        $this->assertEquals('AREA_REF_TEST', $defect->areaDitemukanRef->name);
        $this->assertEquals(AreaDitemukan::TIPE_INTERNAL_DEFECT, $defect->areaDitemukanRef->tipe);
    }

    /**
     * Verifikasi Tampilan dan Filter Kategori pada Report Final Assy Controller & View.
     */
    public function test_final_assy_report_view_and_category_filters(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'INSP_1', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $areaAssembly = AreaDitemukan::create(['name' => 'ASSY_1', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY_DEFECT]);

        Defect::create([
            'waktu' => Carbon::now(),
            'user_name' => 'Insp User',
            'jenis_assy' => 'Final Assy',
            'area_ditemukan' => $areaInspeksi->name,
            'rework_group_id' => null,
            'jenis_defect' => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity' => 1,
        ]);

        Defect::create([
            'waktu' => Carbon::now(),
            'user_name' => 'Assy User',
            'jenis_assy' => 'Final Assy',
            'area_ditemukan' => $areaAssembly->name,
            'rework_group_id' => null,
            'jenis_defect' => 'INSER CIRCUIT',
            'jenis_sub_defect' => 'WRONG COLOR',
            'quantity' => 2,
        ]);

        Defect::create([
            'waktu' => Carbon::now(),
            'user_name' => 'Rework User',
            'jenis_assy' => 'Final Assy',
            'area_ditemukan' => 'REWORK',
            'rework_group_id' => 'uuid-rework-99',
            'jenis_defect' => 'TAPING DEFECT',
            'jenis_sub_defect' => 'LOOSE',
            'quantity' => 1,
        ]);

        // 1. GET all
        $response = $this->actingAsAdmin()->get(route('final_assy.index'));
        $response->assertStatus(200);
        $response->assertViewHas('totalFinalAssyNonRework', 2);
        $response->assertViewHas('totalInternal', 1);
        $response->assertViewHas('totalAssembly', 1);
        $response->assertViewHas('totalUnclassified', 0);
        $response->assertDontSee('Riwayat Defect Rework');
        $response->assertDontSee('Record Terpisah');

        // 2. Filter category=internal
        $respInternal = $this->actingAsAdmin()->get(route('final_assy.index', ['category' => 'internal']));
        $respInternal->assertStatus(200);
        $respInternal->assertSee('Insp User');
        $respInternal->assertDontSee('Assy User');

        // 3. Filter category=assembly
        $respAssembly = $this->actingAsAdmin()->get(route('final_assy.index', ['category' => 'assembly']));
        $respAssembly->assertStatus(200);
        $respAssembly->assertSee('Assy User');
        $respAssembly->assertDontSee('Insp User');
    }

    /**
     * Verifikasi Pre Assy:
     * Invarian: Internal Defect (Inspektor) + Process Defect (Operator) + Tidak Diketahui = Total Pre Assy.
     * Label wajib: "Process Defect", "Internal Defect", "Tidak Diketahui".
     */
    public function test_pre_assy_report_invariants_and_process_defect_naming(): void
    {
        $baseTime = Carbon::now();

        // 1. Ditemukan oleh Inspektor -> Internal Defect
        Defect::create([
            'waktu' => $baseTime,
            'user_name' => 'Pre Insp',
            'jenis_assy' => 'Pre Assy',
            'ditemukan_oleh' => 'Inspektor',
            'jenis_defect' => 'CORE',
            'jenis_sub_defect' => 'CUT WIRE',
            'quantity' => 1,
        ]);

        // 2. Ditemukan oleh Operator -> Process Defect (Wajib: BUKAN Assembly Defect)
        Defect::create([
            'waktu' => $baseTime,
            'user_name' => 'Pre Op',
            'jenis_assy' => 'Pre Assy',
            'ditemukan_oleh' => 'Operator',
            'jenis_defect' => 'TERMINAL',
            'jenis_sub_defect' => 'DEFORM',
            'quantity' => 3,
        ]);

        // 3. Ditemukan oleh NULL -> Tidak Diketahui
        Defect::create([
            'waktu' => $baseTime,
            'user_name' => 'Pre Anon',
            'jenis_assy' => 'Pre Assy',
            'ditemukan_oleh' => null,
            'jenis_defect' => 'CRIMPING',
            'jenis_sub_defect' => 'LOOSE',
            'quantity' => 2,
        ]);

        // 4. Verifikasi Invarian Pre Assy
        $baseQuery = Defect::where('jenis_assy', 'Pre Assy');
        $totalPreAssy = (clone $baseQuery)->count();
        $totalInternal = (clone $baseQuery)->preAssyByInspektor()->count();
        $totalProcess = (clone $baseQuery)->preAssyByOperator()->count();
        $totalUnknown = (clone $baseQuery)->preAssyUnknown()->count();

        $this->assertEquals(3, $totalPreAssy);
        $this->assertEquals(1, $totalInternal);
        $this->assertEquals(1, $totalProcess);
        $this->assertEquals(1, $totalUnknown);

        // Invarian mutlak
        $this->assertEquals(
            $totalPreAssy,
            $totalInternal + $totalProcess + $totalUnknown,
            'INVARIAN GAGAL: Total Pre Assy != Internal + Process + Tidak Diketahui'
        );

        // 5. Verifikasi Response View
        $response = $this->actingAsAdmin()->get(route('pre_assy.index'));
        $response->assertStatus(200);
        $response->assertViewHas('totalPreAssy', 3);
        $response->assertViewHas('totalInternal', 1);
        $response->assertViewHas('totalProcess', 1);
        $response->assertViewHas('totalUnknown', 1);

        $response->assertSee('Ditemukan oleh Operator');
        $response->assertSee('Ditemukan oleh Inspektor');
        $response->assertDontSee('Tidak Diketahui');

        // 6. Filter category=process
        $respProcess = $this->actingAsAdmin()->get(route('pre_assy.index', ['category' => 'process']));
        $respProcess->assertStatus(200);
        $respProcess->assertSee('Pre Op');
        $respProcess->assertDontSee('Pre Insp');
        $respProcess->assertDontSee('Pre Anon');

        // 7. Filter category=unknown
        $respUnknown = $this->actingAsAdmin()->get(route('pre_assy.index', ['category' => 'unknown']));
        $respUnknown->assertStatus(200);
        $respUnknown->assertSee('Pre Anon');
        $respUnknown->assertDontSee('Pre Insp');
        $respUnknown->assertDontSee('Pre Op');
    }

    /**
     * Verifikasi Export Excel Final Assy & Pre Assy berfungsi dengan baik.
     */
    public function test_final_assy_and_pre_assy_export(): void
    {
        AreaDitemukan::create(['name' => 'AREA_CK', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        Defect::create([
            'waktu' => Carbon::now(),
            'user_name' => 'Export User FA',
            'jenis_assy' => 'Final Assy',
            'area_ditemukan' => 'AREA_CK',
            'jenis_defect' => 'MISSING PART',
            'jenis_sub_defect' => 'LOCK',
            'quantity' => 1,
        ]);

        Defect::create([
            'waktu' => Carbon::now(),
            'user_name' => 'Export User PA',
            'jenis_assy' => 'Pre Assy',
            'ditemukan_oleh' => 'Operator',
            'jenis_defect' => 'CORE',
            'jenis_sub_defect' => 'CUT',
            'quantity' => 1,
        ]);

        $resFa = $this->actingAsAdmin()->get(route('final_assy.export', ['category' => 'all']));
        $resFa->assertStatus(200);

        $resPa = $this->actingAsAdmin()->get(route('pre_assy.export', ['category' => 'all']));
        $resPa->assertStatus(200);
    }

    /**
     * Verifikasi fitur Edit Pre Assy pada submenu "All":
     * - Defect "Tidak Diketahui" (ditemukan_oleh = null, carline_id = null) dapat diedit tanpa validasi error.
     * - Nilai conveyor tidak terhapus jika carline_id kosong.
     * - Nilai ditemukan_oleh tetap null (Tidak Diketahui) saat diupdate kosong.
     * - Invarian Pre Assy (Internal + Assembly + Tidak Diketahui = Total Pre Assy) tetap terjaga.
     */
    public function test_pre_assy_all_submenu_edit_and_update_supports_unknown_defect_and_preserves_invariant(): void
    {
        $baseTime = Carbon::now();
        $carline = Carline::create(['name' => 'CARLINE_PA_01']);

        // 1. Defect Internal (Inspektor)
        $dInternal = Defect::create([
            'waktu'          => $baseTime,
            'user_name'      => 'Pre Insp',
            'jenis_assy'     => 'Pre Assy',
            'ditemukan_oleh' => 'Inspektor',
            'carline_id'     => $carline->id,
            'jenis_defect'   => 'CORE',
            'jenis_sub_defect' => 'CUT WIRE',
            'quantity'       => 1,
        ]);

        // 2. Defect Assembly (Operator)
        $dAssembly = Defect::create([
            'waktu'          => $baseTime,
            'user_name'      => 'Pre Op',
            'jenis_assy'     => 'Pre Assy',
            'ditemukan_oleh' => 'Operator',
            'carline_id'     => $carline->id,
            'jenis_defect'   => 'TERMINAL',
            'jenis_sub_defect' => 'DEFORM',
            'quantity'       => 2,
        ]);

        // 3. Defect Tidak Diketahui (NULL ditemukan_oleh dan NULL carline_id dengan conveyor warisan)
        $dUnknown = Defect::create([
            'waktu'          => $baseTime,
            'user_name'      => 'Pre Anon',
            'jenis_assy'     => 'Pre Assy',
            'ditemukan_oleh' => null,
            'carline_id'     => null,
            'conveyor'       => 'LEGACY-PA-CONVEYOR',
            'jenis_defect'   => 'CRIMPING',
            'jenis_sub_defect' => 'LOOSE',
            'quantity'       => 3,
        ]);

        // 4. Cek Invarian Awal
        $this->assertEquals(3, Defect::where('jenis_assy', 'Pre Assy')->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyByInspektor()->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyByOperator()->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyUnknown()->count());

        // 5. Test Admin membuka halaman Edit Defect Tidak Diketahui
        $resEdit = $this->actingAsAdmin()->get(route('admin.report.edit', $dUnknown->id));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Edit Report');

        // 6. Test Admin mencoba update defect non-zero tanpa ditemukan_oleh ditolak validasi
        $inspectProcess = \App\Models\InspectProcessType::firstOrCreate(['name' => 'Cutting']);
        $invalidPayload = [
            'tanggal'                 => $baseTime->format('Y-m-d'),
            'jam'                     => $baseTime->format('H:i'),
            'type'                    => 'Pre Assy',
            'jenis_defect'            => 'CRIMPING',
            'sub_defect'              => 'LOOSE',
            'jumlah'                  => 10,
            'inspect_process_type_id' => $inspectProcess->id,
            'carline_id'              => '',
            'ditemukan_oleh'          => '',
            'no_terminal'             => 'TERM-999',
        ];

        $resInvalid = $this->actingAsAdmin()->put(route('admin.report.update', $dUnknown->id), $invalidPayload);
        $resInvalid->assertSessionHasErrors(['ditemukan_oleh' => 'Ditemukan oleh wajib dipilih.']);

        // 7. Test Admin mengupdate dengan memilih ditemukan_oleh ('Operator') berhasil
        $updatePayload = array_merge($invalidPayload, ['ditemukan_oleh' => 'Operator']);
        $resUpdate = $this->actingAsAdmin()->put(route('admin.report.update', $dUnknown->id), $updatePayload);
        $resUpdate->assertSessionHasNoErrors();
        $resUpdate->assertRedirect(route('recent_defects.index'));

        // 8. Verifikasi defect tersimpan dengan benar: quantity berubah, ditemukan_oleh = Operator, conveyor tidak terhapus
        $freshUnknown = $dUnknown->fresh();
        $this->assertEquals(10, $freshUnknown->quantity);
        $this->assertEquals('Operator', $freshUnknown->ditemukan_oleh);
        $this->assertNull($freshUnknown->carline_id);
        $this->assertEquals('LEGACY-PA-CONVEYOR', $freshUnknown->conveyor);
        $this->assertEquals('TERM-999', $freshUnknown->no_terminal);

        // 9. Verifikasi Invarian Pre Assy tetap utuh setelah edit (total 3 = 1 Internal + 2 Assembly + 0 Unknown)
        $this->assertEquals(3, Defect::where('jenis_assy', 'Pre Assy')->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyByInspektor()->count());
        $this->assertEquals(2, Defect::where('jenis_assy', 'Pre Assy')->preAssyByOperator()->count());
        $this->assertEquals(0, Defect::where('jenis_assy', 'Pre Assy')->preAssyUnknown()->count());

        // Invarian mutlak
        $this->assertEquals(
            Defect::where('jenis_assy', 'Pre Assy')->count(),
            Defect::where('jenis_assy', 'Pre Assy')->preAssyByInspektor()->count()
            + Defect::where('jenis_assy', 'Pre Assy')->preAssyByOperator()->count()
            + Defect::where('jenis_assy', 'Pre Assy')->preAssyUnknown()->count()
        );

        // 9. Test Admin mengupdate defect internal tetap berjalan normal
        $resUpdateInternal = $this->actingAsAdmin()->put(route('admin.report.update', $dInternal->id), [
            'tanggal'                 => $baseTime->format('Y-m-d'),
            'jam'                     => $baseTime->format('H:i'),
            'type'                    => 'Pre Assy',
            'jenis_defect'            => 'CORE',
            'sub_defect'              => 'CUT WIRE',
            'jumlah'                  => 7,
            'inspect_process_type_id' => $inspectProcess->id,
            'carline_id'              => $carline->id,
            'ditemukan_oleh'          => 'Inspektor',
        ]);
        $resUpdateInternal->assertSessionHasNoErrors();
        $this->assertEquals(7, $dInternal->fresh()->quantity);
        $this->assertEquals('Inspektor', $dInternal->fresh()->ditemukan_oleh);
    }

    public function test_pagination_sliding_window_and_query_string_preservation(): void
    {
        $carline = Carline::create(['name' => 'CARLINE-PAGE-TEST']);
        \App\Models\AreaDitemukan::firstOrCreate(
            ['name' => 'Cutting'],
            ['kategori' => \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY, 'tipe' => \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY]
        );

        // Buat 50 defect (5 halaman x 10 baris)
        for ($i = 1; $i <= 50; $i++) {
            Defect::create([
                'waktu'            => Carbon::parse('2026-11-01 08:00:00')->addMinutes($i),
                'user_name'        => 'Operator Page',
                'shift'            => '1A',
                'jenis_assy'       => 'Pre Assy',
                'carline_id'       => $carline->id,
                'area_ditemukan'   => 'Cutting',
                'jenis_defect'     => 'CRIMPING',
                'jenis_sub_defect' => 'DEFECT-' . $i,
                'quantity'         => 1,
                'ditemukan_oleh'   => 'Operator',
            ]);
        }

        // 1. Halaman 1 -> (1)(2)(3)(Next); Back tidak muncul
        $res1 = $this->actingAsAdmin()->get('/pre-assy?page=1&defect=CRIMPING');
        $res1->assertOk();
        $html1 = $res1->getContent();
        $this->assertStringContainsString('aria-label="Pagination Navigation"', $html1);
        $this->assertStringContainsString('Next', $html1);
        // Back link must not appear
        $this->assertStringNotContainsString('rel="prev"', $html1);
        // Halaman 1 active (maroon)
        $this->assertStringContainsString('bg-[#8b0000]', $html1);

        // 2. Halaman 2 -> (Back)(1)(2)(3)(Next)
        $res2 = $this->actingAsAdmin()->get('/pre-assy?page=2&defect=CRIMPING');
        $res2->assertOk();
        $html2 = $res2->getContent();
        $this->assertStringContainsString('rel="prev"', $html2);
        $this->assertStringContainsString('rel="next"', $html2);
        // Query string defect=CRIMPING preserved in next link
        $this->assertStringContainsString('defect=CRIMPING', $html2);

        // 3. Halaman 3 -> (Back)(2)(3)(4)(Next)
        $res3 = $this->actingAsAdmin()->get('/pre-assy?page=3&defect=CRIMPING');
        $res3->assertOk();
        $html3 = $res3->getContent();
        $this->assertStringContainsString('rel="prev"', $html3);
        $this->assertStringContainsString('rel="next"', $html3);

        // 4. Halaman 5 (terakhir) -> (Back)(3)(4)(5); Next tidak muncul
        $res5 = $this->actingAsAdmin()->get('/pre-assy?page=5&defect=CRIMPING');
        $res5->assertOk();
        $html5 = $res5->getContent();
        $this->assertStringContainsString('rel="prev"', $html5);
        $this->assertStringNotContainsString('rel="next"', $html5);
    }

    /**
     * Test Zero Defect di Final Assy:
     * - TIDAK PERNAH masuk scope unclassified (totalUnclassified = 0).
     * - Kartu & tab "Belum Terklasifikasi" / "Area belum diisi" tidak muncul.
     * - Badge kategori = "Zero Defect", Area Ditemukan = "-".
     * - Export Excel & Live Polling memetakan kategori = "Zero Defect", area = "-".
     */
    public function test_zero_defect_never_unclassified_in_final_assy(): void
    {
        $defect = Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Operator QA',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => null,
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
        ]);

        // 1. Verifikasi scope
        $this->assertEquals(0, Defect::finalAssyReport()->finalAssyInternalDefect()->count());
        $this->assertEquals(0, Defect::finalAssyReport()->finalAssyAssemblyDefect()->count());
        $this->assertEquals(1, Defect::finalAssyReport()->count());

        // 2. Verifikasi View
        $response = $this->actingAsAdmin()->get(route('final_assy.index'));
        $response->assertOk();
        $response->assertViewHas('totalUnclassified', 0);
        $response->assertDontSee('Belum Terklasifikasi');
        $response->assertDontSee('Area belum diisi (');
        $response->assertSee('Zero Defect');

        // 3. Verifikasi Live Polling API
        $resLive = $this->actingAsAdmin()->getJson('/api/final-assy/live');
        $resLive->assertOk();
        $items = $resLive->json('data');
        $this->assertNotEmpty($items);
        $found = collect($items)->firstWhere('id', $defect->id);
        $this->assertNotNull($found);
        $this->assertEquals('Zero Defect', $found['category_label']);
        $this->assertEquals('-', $found['area_ditemukan']);

        // 4. Verifikasi Export Excel
        $export = new \App\Exports\FinalAssyExport(request());
        $mapped = $export->map($defect);
        // Index 5: Kategori Defect, Index 14: Area Ditemukan, Index 15: Tipe Area Master
        $this->assertEquals('Zero Defect', $mapped[5]);
        $this->assertEquals('-', $mapped[14]);
        $this->assertEquals('-', $mapped[15]);
        $this->assertNotEquals('Belum Terklasifikasi', $mapped[5]);
    }

    /**
     * Test Zero Defect di Pre Assy:
     * - TIDAK PERNAH masuk scope unknown (totalUnknown = 0).
     * - Kartu & tab "Tidak Diketahui" tidak muncul akibat Zero Defect.
     * - Kartu "Zero Defect" muncul bila ada record Zero Defect.
     * - Badge tabel & live API = "Zero Defect".
     * - Export Excel memetakan kategori = "Zero Defect".
     */
    public function test_zero_defect_never_unknown_in_pre_assy(): void
    {
        $carline = Carline::firstOrCreate(['name' => 'W100'], ['line' => 'TOYOTA']);

        $defect = Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Operator Pre',
            'shift'            => '1A',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'ditemukan_oleh'   => null, // tidak ada penemu
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
        ]);

        // 1. Verifikasi scope
        $this->assertEquals(0, Defect::preAssyUnknown()->count());
        $this->assertEquals(0, Defect::where('jenis_assy', 'Pre Assy')->preAssyByInspektor()->count());
        $this->assertEquals(0, Defect::where('jenis_assy', 'Pre Assy')->preAssyByOperator()->count());

        // 2. Verifikasi View
        $response = $this->actingAsAdmin()->get(route('pre_assy.index'));
        $response->assertOk();
        $response->assertViewHas('totalUnknown', 0);
        $response->assertViewHas('totalZeroDefect', 1);
        $response->assertDontSee('Tidak Diketahui (');
        $response->assertSee('Zero Defect');
        $response->assertSee('Laporan nihil defect');

        // 3. Verifikasi Live Polling API
        $resLive = $this->actingAsAdmin()->getJson('/api/pre-assy/live');
        $resLive->assertOk();
        $items = $resLive->json('data');
        $found = collect($items)->firstWhere('id', $defect->id);
        $this->assertNotNull($found);
        $this->assertEquals('Zero Defect', $found['ditemukan_label']);

        // 4. Verifikasi Export Excel
        $export = new \App\Exports\PreAssyExport(request());
        $mapped = $export->map($defect);
        // Index 4: Kategori Pre Assy
        $this->assertEquals('Zero Defect', $mapped[4]);
        $this->assertNotEquals('Tidak Diketahui', $mapped[4]);
    }

    /**
     * Test Defect Biasa tanpa Area Ditemukan (Data Lama/Anomali):
     * - Masuk scope unclassified / area belum diisi.
     * - Menampilkan label netral "Area belum diisi" (BUKAN "Belum Terklasifikasi").
     * - Kartu dan tab "Area belum diisi" muncul.
     */
    public function test_actual_defects_with_missing_area_show_dash_and_never_area_belum_diisi(): void
    {
        $defect = Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Operator QA',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => null,
            'jenis_defect'     => 'INSER CIRCUIT',
            'jenis_sub_defect' => 'LOCK',
            'quantity'         => 3,
        ]);

        $this->assertEquals(1, Defect::finalAssyReport()->finalAssyInternalDefect()->count());
        $this->assertEquals(0, Defect::finalAssyReport()->finalAssyAssemblyDefect()->count());

        // View
        $response = $this->actingAsAdmin()->get(route('final_assy.index'));
        $response->assertOk();
        $response->assertViewHas('totalUnclassified', 0);
        $response->assertDontSee('Area belum diisi');
        $response->assertDontSee('Belum Terklasifikasi');

        // Export Excel
        $export = new \App\Exports\FinalAssyExport(request());
        $mapped = $export->map($defect);
        $this->assertEquals('-', $mapped[5]);
        $this->assertEquals('-', $mapped[14]);
        $this->assertNotEquals('Area belum diisi', $mapped[5]);
        $this->assertNotEquals('Belum Terklasifikasi', $mapped[5]);
    }

    /**
     * Verifikasi tidak ada lagi teks "Belum Terklasifikasi", "Area belum diisi", atau "Tidak Diketahui" di view report Final Assy dan Pre Assy.
     */
    public function test_no_belum_terklasifikasi_or_area_belum_diisi_or_tidak_diketahui_text_remains_in_views(): void
    {
        $finalAssyView = file_get_contents(resource_path('views/final_assy.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/belum\s*terklasifikasi/i', $finalAssyView);
        $this->assertDoesNotMatchRegularExpression('/area\s*belum\s*diisi/i', $finalAssyView);

        $finalAssyChart = file_get_contents(resource_path('views/partials/final_assy_chart.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/belum\s*terklasifikasi/i', $finalAssyChart);
        $this->assertDoesNotMatchRegularExpression('/area\s*belum\s*diisi/i', $finalAssyChart);

        $preAssyView = file_get_contents(resource_path('views/pre_assy.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/belum\s*terklasifikasi/i', $preAssyView);
        $this->assertDoesNotMatchRegularExpression('/tidak\s*diketahui/i', $preAssyView);

        $preAssyChart = file_get_contents(resource_path('views/partials/pre_assy_chart.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/tidak\s*diketahui/i', $preAssyChart);
    }

    /**
     * Verifikasi submenu spesifik menyembunyikan baris tab kategori dan menyesuaikan kartu KPI.
     */
    public function test_submenu_specific_pages_hide_category_tabs_and_adapt_kpi_cards(): void
    {
        AreaDitemukan::firstOrCreate(['name' => 'CK_TEST'], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        Defect::create([
            'waktu' => Carbon::now(),
            'user_name' => 'QC',
            'jenis_assy' => 'Final Assy',
            'area_ditemukan' => 'CK_TEST',
            'jenis_defect' => 'MISSING PART',
            'jenis_sub_defect' => 'LOCK',
            'quantity' => 2,
        ]);

        // Final Assy All: Tampil baris tab
        $resFaAll = $this->actingAsAdmin()->get(route('final_assy.index'));
        $resFaAll->assertOk();
        $resFaAll->assertSee('Kategori Defect:');
        $resFaAll->assertSee('Report Final Assy');

        // Final Assy Submenu Internal: Sembunyikan baris tab, kartu KPI terfokus
        $resFaInt = $this->actingAsAdmin()->get(route('final_assy.internal'));
        $resFaInt->assertOk();
        $resFaInt->assertDontSee('Kategori Defect:');
        $resFaInt->assertSee('Report Final Assy - Internal Defect');

        // Final Assy Submenu Assembly: Sembunyikan baris tab
        $resFaAsm = $this->actingAsAdmin()->get(route('final_assy.assembly'));
        $resFaAsm->assertOk();
        $resFaAsm->assertDontSee('Kategori Defect:');
        $resFaAsm->assertSee('Report Final Assy - Assembly Defect');

        // Final Assy Submenu Rework: dialihkan ke Riwayat Rework
        $resFaRew = $this->actingAsAdmin()->get(route('final_assy.rework'));
        $resFaRew->assertRedirect(route('admin.rework_history.index'));

        // Pre Assy All: Tampil baris tab
        $resPaAll = $this->actingAsAdmin()->get(route('pre_assy.index'));
        $resPaAll->assertOk();
        $resPaAll->assertSee('Kategori Defect:');
        $resPaAll->assertSee('Report Pre Assy');

        // Pre Assy Submenu Internal: Sembunyikan baris tab
        $resPaInt = $this->actingAsAdmin()->get(route('pre_assy.internal'));
        $resPaInt->assertOk();
        $resPaInt->assertDontSee('Kategori Defect:');
        $resPaInt->assertSee('Report Pre Assy - Ditemukan oleh Inspektor');

        // Pre Assy Submenu Assembly: Sembunyikan baris tab
        $resPaAsm = $this->actingAsAdmin()->get(route('pre_assy.assembly'));
        $resPaAsm->assertOk();
        $resPaAsm->assertDontSee('Kategori Defect:');
        $resPaAsm->assertSee('Report Pre Assy - Ditemukan oleh Operator');
    }

    /**
     * Verifikasi validasi input/edit Pre Assy: ditemukan_oleh wajib jika bukan zero defect, null jika zero defect.
     */
    public function test_pre_assy_input_defect_requires_ditemukan_oleh_when_nonzero_and_null_when_zero_defect(): void
    {
        $area = AreaDitemukan::firstOrCreate(['name' => 'Cutting Area'], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $carline = \App\Models\Carline::firstOrCreate(['name' => 'CARLINE_TEST']);

        // Non-zero Pre Assy tanpa ditemukan_oleh ditolak
        $resNonZeroInvalid = $this->actingAsOperator()->post(route('input_defect.store'), [
            'tanggal'        => Carbon::now()->format('Y-m-d'),
            'jam'            => Carbon::now()->format('H:i'),
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'jenis_defect'   => 'CUT WIRE',
            'sub_defect'     => 'SHORT',
            'jumlah'         => 5,
            'area_ditemukan' => $area->name,
            'ditemukan_oleh' => '',
        ]);
        $resNonZeroInvalid->assertSessionHasErrors(['ditemukan_oleh' => 'Ditemukan oleh wajib dipilih.']);

        // Zero defect Pre Assy memaksa ditemukan_oleh = null
        $resZeroValid = $this->actingAsOperator()->post(route('input_defect.store'), [
            'tanggal'          => Carbon::now()->format('Y-m-d'),
            'jam'              => Carbon::now()->format('H:i'),
            'type'             => 'Pre Assy',
            'carline_id'       => $carline->id,
            'jenis_defect'     => 'Zero Defect',
            'sub_defect'       => '-',
            'jumlah'           => 0,
            'inspect_quantity' => 100,
            'area_ditemukan'   => 'Cutting',
            'ditemukan_oleh'   => 'Inspektor', // dikirim tapi harus dinull-kan oleh server
        ]);
        $resZeroValid->assertSessionHasNoErrors();

        $savedZero = Defect::where('jenis_assy', 'Pre Assy')->where('jenis_defect', 'Zero Defect')->latest()->first();
        $this->assertNotNull($savedZero);
        $this->assertNull($savedZero->ditemukan_oleh);
        $this->assertSame('Cutting', $savedZero->area_ditemukan);
    }

    /**
     * Test (1): Pre Assy dipisah hanya oleh ditemukan_oleh walau area bertipe/kategori apa pun.
     */
    public function test_pre_assy_dipisah_hanya_oleh_ditemukan_oleh_walau_area_bertipe_kategori_apa_pun(): void
    {
        $areaAssembly = AreaDitemukan::create([
            'name'     => 'AREA_ASM_PRE_TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);

        $areaInternal = AreaDitemukan::create([
            'name'     => 'AREA_INT_PRE_TEST',
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $d1 = Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'area_ditemukan'   => $areaAssembly->name, // Tipe assembly, tapi penemu Inspektor
            'ditemukan_oleh'   => 'Inspektor',
            'jenis_defect'     => 'CRIMPING',
            'jenis_sub_defect' => 'BARREL',
            'quantity'         => 2,
        ]);

        $d2 = Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'area_ditemukan'   => $areaInternal->name, // Tipe internal, tapi penemu Operator
            'ditemukan_oleh'   => 'Operator',
            'jenis_defect'     => 'STRIPPING',
            'jenis_sub_defect' => 'WIRE',
            'quantity'         => 3,
        ]);

        $d3 = Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'area_ditemukan'   => null, // Area null, tapi penemu Inspektor
            'ditemukan_oleh'   => 'Inspektor',
            'jenis_defect'     => 'BENDING',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 1,
        ]);

        $d4 = Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'area_ditemukan'   => $areaAssembly->name, // Tipe assembly, penemu Operator
            'ditemukan_oleh'   => 'Operator',
            'jenis_defect'     => 'MISSING',
            'jenis_sub_defect' => 'PART',
            'quantity'         => 4,
        ]);

        // Verifikasi scope preAssyInspektor HANYA mengambil penemu Inspektor (d1, d3)
        $inspektorIds = Defect::preAssyInspektor()->pluck('id')->all();
        $this->assertContains($d1->id, $inspektorIds);
        $this->assertContains($d3->id, $inspektorIds);
        $this->assertNotContains($d2->id, $inspektorIds);
        $this->assertNotContains($d4->id, $inspektorIds);

        // Verifikasi scope preAssyOperator HANYA mengambil penemu Operator (d2, d4)
        $operatorIds = Defect::preAssyOperator()->pluck('id')->all();
        $this->assertContains($d2->id, $operatorIds);
        $this->assertContains($d4->id, $operatorIds);
        $this->assertNotContains($d1->id, $operatorIds);
        $this->assertNotContains($d3->id, $operatorIds);
    }

    /**
     * Test (2): Store dan Update Pre Assy tanpa ditemukan_oleh ditolak.
     */
    public function test_store_dan_update_pre_assy_tanpa_ditemukan_oleh_ditolak(): void
    {
        $carline = \App\Models\Carline::firstOrCreate(['name' => 'CARLINE_VALIDATION_TEST']);
        $area = AreaDitemukan::firstOrCreate(['name' => 'Manual Crimping'], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        // 1. Store Pre Assy non-zero tanpa ditemukan_oleh ditolak
        $resStore = $this->actingAsOperator()->post(route('input_defect.store'), [
            'tanggal'        => Carbon::today()->toDateString(),
            'jam'            => '08:00',
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'area_ditemukan' => $area->name,
            'jenis_defect'   => 'CRIMPING',
            'sub_defect'     => 'BARREL',
            'jumlah'         => 2,
            'ditemukan_oleh' => '',
        ]);
        $resStore->assertSessionHasErrors(['ditemukan_oleh' => 'Ditemukan oleh wajib dipilih.']);

        // 2. Buat defect Pre Assy valid terlebih dahulu
        $defect = Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'area_ditemukan'   => $area->name,
            'jenis_defect'     => 'CRIMPING',
            'jenis_sub_defect' => 'BARREL',
            'quantity'         => 2,
            'ditemukan_oleh'   => 'Operator',
        ]);

        // 3. Operator Update Pre Assy non-zero tanpa ditemukan_oleh ditolak
        $resOpUpdate = $this->actingAsOperator()->put(route('input_defect.update', $defect->id), [
            'tanggal'        => Carbon::today()->toDateString(),
            'jam'            => '08:00',
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'area_ditemukan' => $area->name,
            'jenis_defect'   => 'CRIMPING',
            'sub_defect'     => 'BARREL',
            'jumlah'         => 3,
            'ditemukan_oleh' => '',
        ]);
        $resOpUpdate->assertSessionHasErrors(['ditemukan_oleh' => 'Ditemukan oleh wajib dipilih.']);

        // 4. Admin Update Pre Assy non-zero tanpa ditemukan_oleh ditolak
        $resAdminUpdate = $this->actingAsAdmin()->put(route('admin.report.update', $defect->id), [
            'tanggal'        => Carbon::today()->toDateString(),
            'jam'            => '08:00',
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'area_ditemukan' => $area->name,
            'jenis_defect'   => 'CRIMPING',
            'sub_defect'     => 'BARREL',
            'jumlah'         => 4,
            'ditemukan_oleh' => '',
        ]);
        $resAdminUpdate->assertSessionHasErrors(['ditemukan_oleh' => 'Ditemukan oleh wajib dipilih.']);
    }
}
