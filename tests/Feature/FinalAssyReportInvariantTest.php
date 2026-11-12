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

    /**
     * Invarian Mutlak Final Assy:
     * Internal + Assembly + Belum Terklasifikasi = Total Final Assy Non-Rework.
     * Edge cases: area NULL, area tidak ada di master, area rework tanpa rework_group_id.
     */
    public function test_final_assy_report_mathematical_invariant_with_all_edge_cases(): void
    {
        // 1. Setup master area dengan klasifikasi
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSPEKSI', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);
        $areaAssembly = AreaDitemukan::create(['name' => 'LINE_ASSEMBLY', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY]);
        $areaUnclassified = AreaDitemukan::create(['name' => 'STASIUN_BARU', 'tipe' => AreaDitemukan::TIPE_BELUM_DIKLASIFIKASI]);
        $areaRework = AreaDitemukan::create(['name' => 'REWORK', 'tipe' => AreaDitemukan::TIPE_REWORK]);

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
        $totalInternal = Defect::finalAssyReport()->finalAssyInternal()->count();
        $totalAssembly = Defect::finalAssyReport()->finalAssyAssembly()->count();
        $totalUnclassified = Defect::finalAssyReport()->finalAssyUnclassified()->count();

        // Total non-rework harus tepat 6 (d1, d2, d3, d4, d5, d6)
        $this->assertEquals(6, $totalNonRework, 'Total Final Assy Non-Rework harus tepat 6');
        $this->assertEquals(1, $totalInternal, 'Internal Defect harus tepat 1 (d1)');
        $this->assertEquals(2, $totalAssembly, 'Assembly Defect harus tepat 2 (d2 area assembly dan d5 area rework)');
        $this->assertEquals(3, $totalUnclassified, 'Belum Terklasifikasi harus tepat 3 (null, orphan, unclassified)');

        // Invarian mutlak: Internal + Assembly + Belum Terklasifikasi == Total Final Assy Non-Rework
        $this->assertEquals(
            $totalNonRework,
            $totalInternal + $totalAssembly + $totalUnclassified,
            'INVARIAN GAGAL: Total Non-Rework != Internal + Assembly + Belum Terklasifikasi'
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
            'name'            => 'AREA_REF_TEST',
            'tipe'            => AreaDitemukan::TIPE_INTERNAL_DEFECT,
            'kategori_report' => AreaDitemukan::REPORT_INSPEKSI,
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
        $this->assertEquals(AreaDitemukan::REPORT_INSPEKSI, $defect->areaDitemukanRef->kategori_report);
    }

    /**
     * Verifikasi Tampilan dan Filter Kategori pada Report Final Assy Controller & View.
     */
    public function test_final_assy_report_view_and_category_filters(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'INSP_1', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);
        $areaAssembly = AreaDitemukan::create(['name' => 'ASSY_1', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY]);

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
        $response->assertViewHas('totalRework', 1);
        $response->assertSee('Riwayat Defect Rework');
        $response->assertSee('Record Terpisah');

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
        $totalInternal = (clone $baseQuery)->preAssyInternal()->count();
        $totalProcess = (clone $baseQuery)->preAssyProcess()->count();
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

        $response->assertSee('Assembly Defect (Operator)');
        $response->assertSee('Internal Defect (Inspektor)');
        $response->assertSee('Tidak Diketahui');

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
        AreaDitemukan::create(['name' => 'AREA_CK', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);

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
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyInternal()->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyAssembly()->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyUnknown()->count());

        // 5. Test Admin membuka halaman Edit Defect Tidak Diketahui
        $resEdit = $this->actingAsAdmin()->get(route('admin.report.edit', $dUnknown->id));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Edit Report');

        // 6. Test Admin mengupdate Defect Tidak Diketahui tanpa mengubah ditemukan_oleh (tetap kosong)
        $updatePayload = [
            'tanggal'        => $baseTime->format('Y-m-d'),
            'jam'            => $baseTime->format('H:i'),
            'type'           => 'Pre Assy',
            'jenis_defect'   => 'CRIMPING',
            'sub_defect'     => 'LOOSE',
            'jumlah'         => 10,
            'carline_id'     => '',
            'ditemukan_oleh' => '',
            'no_terminal'    => 'TERM-999',
        ];

        $resUpdate = $this->actingAsAdmin()->put(route('admin.report.update', $dUnknown->id), $updatePayload);
        $resUpdate->assertSessionHasNoErrors();
        $resUpdate->assertRedirect(route('recent_defects.index'));

        // 7. Verifikasi defect tersimpan dengan benar: quantity berubah, ditemukan_oleh tetap null, conveyor tidak terhapus
        $freshUnknown = $dUnknown->fresh();
        $this->assertEquals(10, $freshUnknown->quantity);
        $this->assertNull($freshUnknown->ditemukan_oleh);
        $this->assertNull($freshUnknown->carline_id);
        $this->assertEquals('LEGACY-PA-CONVEYOR', $freshUnknown->conveyor);
        $this->assertEquals('TERM-999', $freshUnknown->no_terminal);

        // 8. Verifikasi Invarian Pre Assy tetap utuh setelah edit
        $this->assertEquals(3, Defect::where('jenis_assy', 'Pre Assy')->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyInternal()->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyAssembly()->count());
        $this->assertEquals(1, Defect::where('jenis_assy', 'Pre Assy')->preAssyUnknown()->count());

        // Invarian mutlak
        $this->assertEquals(
            Defect::where('jenis_assy', 'Pre Assy')->count(),
            Defect::where('jenis_assy', 'Pre Assy')->preAssyInternal()->count()
            + Defect::where('jenis_assy', 'Pre Assy')->preAssyAssembly()->count()
            + Defect::where('jenis_assy', 'Pre Assy')->preAssyUnknown()->count()
        );

        // 9. Test Admin mengupdate defect internal tetap berjalan normal
        $resUpdateInternal = $this->actingAsAdmin()->put(route('admin.report.update', $dInternal->id), [
            'tanggal'        => $baseTime->format('Y-m-d'),
            'jam'            => $baseTime->format('H:i'),
            'type'           => 'Pre Assy',
            'jenis_defect'   => 'CORE',
            'sub_defect'     => 'CUT WIRE',
            'jumlah'         => 7,
            'carline_id'     => $carline->id,
            'ditemukan_oleh' => 'Inspektor',
        ]);
        $resUpdateInternal->assertSessionHasNoErrors();
        $this->assertEquals(7, $dInternal->fresh()->quantity);
        $this->assertEquals('Inspektor', $dInternal->fresh()->ditemukan_oleh);
    }
}
