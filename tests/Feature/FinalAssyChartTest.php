<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class FinalAssyChartTest extends TestCase
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
            'user_role' => 'Operator',
        ]);
    }

    private function createDefect(array $attributes = []): Defect
    {
        return Defect::create(array_merge([
            'waktu'            => Carbon::now(),
            'user_name'        => 'QC Inspector',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'MISSING PART',
            'jenis_sub_defect' => 'LOCK HOUSING',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ], $attributes));
    }

    /**
     * 1. Test Hak Akses Endpoint Chart Data:
     * - Guest dilarang (redirect ke login)
     * - Operator dilarang (403 atau redirect)
     * - Admin diizinkan (200 OK dengan JSON)
     */
    public function test_chart_data_endpoint_access_control(): void
    {
        // a. Guest tanpa login
        $guestRes = $this->getJson(route('final_assy.chart_data'));
        $guestRes->assertStatus(302);

        // b. Operator QA (non-admin)
        $opRes = $this->actingAsOperator()->getJson(route('final_assy.chart_data'));
        $opRes->assertStatus(403);

        // c. Administrator
        $adminRes = $this->actingAsAdmin()->getJson(route('final_assy.chart_data'));
        $adminRes->assertOk();
        $adminRes->assertJsonStructure([
            'labels',
            'datasets' => [
                '*' => ['label', 'backgroundColor', 'borderColor', 'borderWidth', 'data', 'counts']
            ],
            'summary' => ['total_pcs', 'total_count', 'internal_pcs', 'assembly_pcs', 'unclassified_pcs']
        ]);
    }

    /**
     * 2. Test Invarian Matematis Chart PCS vs Defect Scope:
     * - internal_pcs + assembly_pcs + unclassified_pcs === total_pcs
     * - total_pcs === Defect::finalAssyReport()->sum('quantity')
     * - total_count === Defect::finalAssyReport()->count()
     */
    public function test_chart_pcs_mathematical_invariant_matches_report_scope(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSPEKSI', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);
        $areaAssembly = AreaDitemukan::create(['name' => 'LINE_ASSEMBLY', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY]);
        $areaUnclassified = AreaDitemukan::create(['name' => 'STASIUN_BARU', 'tipe' => AreaDitemukan::TIPE_BELUM_DIKLASIFIKASI]);

        // 1 defect Internal (quantity = 3)
        $this->createDefect([
            'area_ditemukan' => $areaInspeksi->name,
            'rework_group_id' => null,
            'jenis_defect' => 'MISSING PART',
            'quantity' => 3,
        ]);

        // 1 defect Assembly (quantity = 7)
        $this->createDefect([
            'area_ditemukan' => $areaAssembly->name,
            'rework_group_id' => null,
            'jenis_defect' => 'DAMAGE/DEFORM/BROKEN PART',
            'quantity' => 7,
        ]);

        // 1 defect Belum Terklasifikasi (quantity = 4)
        $this->createDefect([
            'area_ditemukan' => $areaUnclassified->name,
            'rework_group_id' => null,
            'jenis_defect' => 'OTHERS',
            'quantity' => 4,
        ]);

        $res = $this->actingAsAdmin()->getJson(route('final_assy.chart_data'));
        $res->assertOk();

        $summary = $res->json('summary');
        $expectedTotalPcs = (int) Defect::finalAssyReport()->sum('quantity');
        $expectedTotalCount = (int) Defect::finalAssyReport()->count();

        $this->assertEquals(14, $expectedTotalPcs);
        $this->assertEquals(3, $expectedTotalCount);

        $this->assertEquals(3, $summary['internal_pcs']);
        $this->assertEquals(7, $summary['assembly_pcs']);
        $this->assertEquals(4, $summary['unclassified_pcs']);
        $this->assertEquals($expectedTotalPcs, $summary['total_pcs']);
        $this->assertEquals($expectedTotalCount, $summary['total_count']);
        $this->assertEquals(
            $summary['total_pcs'],
            $summary['internal_pcs'] + $summary['assembly_pcs'] + $summary['unclassified_pcs']
        );
    }

    /**
     * 3. Test Pembobotan Kuantitas: 5 + 10 = 15 PCS
     * Memastikan sumbu Y menjumlahkan kolom quantity, bukan sekadar menghitung baris (count).
     */
    public function test_chart_sums_quantity_five_plus_ten_equals_fifteen(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSPEKSI', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);

        // Rekord 1: quantity = 5
        $this->createDefect([
            'area_ditemukan' => $areaInspeksi->name,
            'rework_group_id' => null,
            'jenis_defect' => 'MISSING PART',
            'quantity' => 5,
        ]);

        // Rekord 2: jenis defect sama, quantity = 10
        $this->createDefect([
            'area_ditemukan' => $areaInspeksi->name,
            'rework_group_id' => null,
            'jenis_defect' => 'MISSING PART',
            'quantity' => 10,
        ]);

        $res = $this->actingAsAdmin()->getJson(route('final_assy.chart_data'));
        $res->assertOk();

        $labels = $res->json('labels');
        $this->assertEquals(['MISSING PART'], $labels);

        $datasets = $res->json('datasets');
        // Dataset 0: Internal Defect
        $this->assertEquals('Internal Defect', $datasets[0]['label']);
        $this->assertEquals([15], $datasets[0]['data'], '5 + 10 harus bernilai 15 PCS');
        $this->assertEquals([2], $datasets[0]['counts'], '2 temuan defect');

        $this->assertEquals(15, $res->json('summary.total_pcs'));
        $this->assertEquals(2, $res->json('summary.total_count'));
    }

    /**
     * 4. Test Rework Dikecualikan:
     * Record dengan rework_group_id TIDAK boleh masuk ke grafik.
     */
    public function test_rework_records_are_strictly_excluded_from_chart(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSPEKSI', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);
        $areaRework = AreaDitemukan::create(['name' => 'REWORK', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        // Defect sah reguler
        $this->createDefect([
            'area_ditemukan' => $areaInspeksi->name,
            'rework_group_id' => null,
            'jenis_defect' => 'MISSING PART',
            'quantity' => 2,
        ]);

        // Defect rework
        $this->createDefect([
            'area_ditemukan' => $areaRework->name,
            'rework_group_id' => 'RW-GROUP-999',
            'jenis_defect' => 'MISSING PART',
            'quantity' => 50, // Angka besar untuk deteksi bila bocor
        ]);

        $res = $this->actingAsAdmin()->getJson(route('final_assy.chart_data'));
        $res->assertOk();

        // Total PCS harus 2, bukan 52
        $this->assertEquals(2, $res->json('summary.total_pcs'));
        $this->assertEquals(1, $res->json('summary.total_count'));

        $datasets = $res->json('datasets');
        $this->assertEquals([2], $datasets[0]['data']);
    }

    /**
     * 5. Test Kasus Area NULL dan Orphan:
     * Defect dengan area NULL atau tidak terdaftar di master masuk ke Belum Terklasifikasi.
     */
    public function test_null_and_orphan_areas_fall_into_unclassified_category(): void
    {
        // Area NULL
        $this->createDefect([
            'area_ditemukan' => null,
            'rework_group_id' => null,
            'jenis_defect' => 'INSER CIRCUIT',
            'quantity' => 4,
        ]);

        // Area Orphan (tidak ada di tabel area_ditemukans)
        $this->createDefect([
            'area_ditemukan' => 'STASIUN_HANTU_TIDAK_ADA',
            'rework_group_id' => null,
            'jenis_defect' => 'INSER CIRCUIT',
            'quantity' => 6,
        ]);

        $res = $this->actingAsAdmin()->getJson(route('final_assy.chart_data'));
        $res->assertOk();

        $labels = $res->json('labels');
        $this->assertEquals(['INSER CIRCUIT'], $labels);

        $datasets = $res->json('datasets');
        // Dataset 0: Internal (0 PCS)
        $this->assertEquals([0], $datasets[0]['data']);
        // Dataset 1: Assembly (0 PCS)
        $this->assertEquals([0], $datasets[1]['data']);
        // Dataset 2: Belum Terklasifikasi (4 + 6 = 10 PCS, 2 temuan)
        $this->assertEquals('Belum Terklasifikasi', $datasets[2]['label']);
        $this->assertEquals([10], $datasets[2]['data']);
        $this->assertEquals([2], $datasets[2]['counts']);

        $this->assertEquals(10, $res->json('summary.unclassified_pcs'));
        $this->assertEquals(0, $res->json('summary.internal_pcs'));
        $this->assertEquals(0, $res->json('summary.assembly_pcs'));
    }

    /**
     * 6. Test Filter Berpengaruh pada Data Grafik:
     * - date_range
     * - defect
     * - line
     * - conveyor
     * - pattern
     */
    public function test_filters_affect_chart_data_accurately(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSPEKSI', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);

        // Record target
        $this->createDefect([
            'waktu' => Carbon::parse('2026-10-15 10:00:00'),
            'area_ditemukan' => $areaInspeksi->name,
            'rework_group_id' => null,
            'jenis_defect' => 'MISSING PART',
            'jenis_mobil' => 'TOYOTA',
            'conveyor' => 'CV-01',
            'pattern' => '67120',
            'quantity' => 8,
        ]);

        // Record pembeda (berbeda tanggal, line, conveyor)
        $this->createDefect([
            'waktu' => Carbon::parse('2026-11-01 10:00:00'),
            'area_ditemukan' => $areaInspeksi->name,
            'rework_group_id' => null,
            'jenis_defect' => 'WRONG PART',
            'jenis_mobil' => 'NISSAN',
            'conveyor' => 'CV-02',
            'pattern' => '67240',
            'quantity' => 12,
        ]);

        // a. Filter Tanggal: 2026-10-01 sampai 2026-10-31
        $resDate = $this->actingAsAdmin()->getJson(route('final_assy.chart_data', [
            'date_range' => '2026-10-01 to 2026-10-31'
        ]));
        $this->assertEquals(['MISSING PART'], $resDate->json('labels'));
        $this->assertEquals(8, $resDate->json('summary.total_pcs'));

        // b. Filter Defect: WRONG PART
        $resDefect = $this->actingAsAdmin()->getJson(route('final_assy.chart_data', [
            'defect' => 'WRONG PART'
        ]));
        $this->assertEquals(['WRONG PART'], $resDefect->json('labels'));
        $this->assertEquals(12, $resDefect->json('summary.total_pcs'));

        // c. Filter Line: TOYOTA
        $resLine = $this->actingAsAdmin()->getJson(route('final_assy.chart_data', [
            'line' => 'TOYOTA'
        ]));
        $this->assertEquals(['MISSING PART'], $resLine->json('labels'));
        $this->assertEquals(8, $resLine->json('summary.total_pcs'));

        // d. Filter Conveyor: CV-02
        $resCv = $this->actingAsAdmin()->getJson(route('final_assy.chart_data', [
            'conveyor' => 'CV-02'
        ]));
        $this->assertEquals(['WRONG PART'], $resCv->json('labels'));
        $this->assertEquals(12, $resCv->json('summary.total_pcs'));

        // e. Filter Pattern: 67120
        $resPat = $this->actingAsAdmin()->getJson(route('final_assy.chart_data', [
            'pattern' => '67120'
        ]));
        $this->assertEquals(['MISSING PART'], $resPat->json('labels'));
        $this->assertEquals(8, $resPat->json('summary.total_pcs'));
    }

    /**
     * 7. Test Pareto Top 15 + "Lainnya":
     * Bila terdapat lebih dari 15 jenis defect, tampilkan 15 terbesar dan sisa diakumulasi ke "Lainnya".
     */
    public function test_pareto_top_15_and_lainnya_grouping(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSPEKSI', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);

        // Buat 18 jenis defect unik dengan PCS menurun dari 18 down to 1
        for ($i = 1; $i <= 18; $i++) {
            $this->createDefect([
                'area_ditemukan' => $areaInspeksi->name,
                'rework_group_id' => null,
                'jenis_defect' => sprintf('DEFECT_%02d', $i),
                'quantity' => $i * 2, // DEFECT_18 = 36 PCS, DEFECT_17 = 34 PCS, ..., DEFECT_01 = 2 PCS
            ]);
        }

        $res = $this->actingAsAdmin()->getJson(route('final_assy.chart_data'));
        $res->assertOk();

        $labels = $res->json('labels');
        // Top 15 + 1 label "Lainnya" = 16 labels
        $this->assertCount(16, $labels);
        $this->assertEquals('DEFECT_18', $labels[0], 'Terbesar harus di posisi pertama');
        $this->assertEquals('DEFECT_17', $labels[1]);
        $this->assertEquals('Lainnya', $labels[15], 'Label terakhir harus Lainnya');

        // Total 18 jenis defect: total PCS = sum(i * 2 for i 1..18) = 2 * (18 * 19 / 2) = 342 PCS
        $this->assertEquals(342, $res->json('summary.total_pcs'));

        // Sisa yang masuk "Lainnya" adalah 3 terkecil: DEFECT_01 (2), DEFECT_02 (4), DEFECT_03 (6) -> Total 12 PCS (3 temuan)
        $datasets = $res->json('datasets');
        $internalPcs = $datasets[0]['data'];
        $internalCounts = $datasets[0]['counts'];

        $this->assertEquals(12, $internalPcs[15], 'Lainnya harus memuat total PCS dari sisa defect');
        $this->assertEquals(3, $internalCounts[15], 'Lainnya harus memuat total temuan dari sisa defect');
    }

    /**
     * 8. Test Tampilan Halaman Report Final Assy Memuat Komponen Grafik dan KPI Opsi A:
     */
    public function test_final_assy_report_view_renders_kpi_option_a_and_chart_canvas(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSPEKSI', 'tipe' => AreaDitemukan::TIPE_INSPEKSI]);

        $this->createDefect([
            'area_ditemukan' => $areaInspeksi->name,
            'rework_group_id' => null,
            'jenis_defect' => 'MISSING PART',
            'quantity' => 7,
        ]);

        $res = $this->actingAsAdmin()->get(route('final_assy.index'));
        $res->assertOk();

        // Memastikan canvas chart ada di HTML
        $res->assertSee('id="finalAssyBarChart"', false);
        $res->assertSee('Grafik Defect Final Assy (PCS)');

        // Memastikan KPI Opsi A dirender dengan "N PCS" dan "M temuan"
        $res->assertSee('7 <span class="text-xs font-bold text-gray-400 font-sans">PCS</span>', false);
        $res->assertSee('1 temuan defect (F/A)');
    }

    /**
     * 9. Grafik HANYA tampil di submenu "All". Submenu lain tanpa grafik.
     */
    public function test_chart_only_renders_on_all_category_and_not_on_submenus(): void
    {
        $areaInspeksi = AreaDitemukan::create(['name' => 'CK_INSP', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT, 'kategori_report' => AreaDitemukan::REPORT_INSPEKSI]);
        $areaAssembly = AreaDitemukan::create(['name' => 'LINE_ASM', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT, 'kategori_report' => AreaDitemukan::REPORT_ASSEMBLY]);

        $this->createDefect(['area_ditemukan' => $areaInspeksi->name, 'quantity' => 2]);
        $this->createDefect(['area_ditemukan' => $areaAssembly->name, 'quantity' => 3]);
        $this->createDefect(['rework_group_id' => 'RWK-001', 'quantity' => 1]);

        // GET /final-assy (All) -> Chart HARUS tampil
        $resAll = $this->actingAsAdmin()->get(route('final_assy.index'));
        $resAll->assertOk();
        $resAll->assertSee('id="finalAssyBarChart"', false);

        // GET /final-assy/internal -> Chart TIDAK BOLEH tampil
        $resInternal = $this->actingAsAdmin()->get(route('final_assy.internal'));
        $resInternal->assertOk();
        $resInternal->assertDontSee('id="finalAssyBarChart"', false);

        // GET /final-assy/assembly -> Chart TIDAK BOLEH tampil
        $resAssembly = $this->actingAsAdmin()->get(route('final_assy.assembly'));
        $resAssembly->assertOk();
        $resAssembly->assertDontSee('id="finalAssyBarChart"', false);

        // GET /final-assy/rework -> Chart TIDAK BOLEH tampil
        $resRework = $this->actingAsAdmin()->get(route('final_assy.rework'));
        $resRework->assertOk();
        $resRework->assertDontSee('id="finalAssyBarChart"', false);
    }

    /**
     * 10. Test Route Submenu Final Assy dan Pre Assy Berfungsi Sesuai Kategori
     */
    public function test_final_assy_and_pre_assy_submenu_routes(): void
    {
        // Final Assy submenus
        $this->actingAsAdmin()->get(route('final_assy.index'))->assertOk();
        $this->actingAsAdmin()->get(route('final_assy.internal'))->assertOk();
        $this->actingAsAdmin()->get(route('final_assy.assembly'))->assertOk();
        $this->actingAsAdmin()->get(route('final_assy.rework'))->assertOk();

        // Pre Assy submenus
        $this->actingAsAdmin()->get(route('pre_assy.index'))->assertOk();
        $this->actingAsAdmin()->get(route('pre_assy.internal'))->assertOk();
        $this->actingAsAdmin()->get(route('pre_assy.assembly'))->assertOk();
    }

    /**
     * 11. Test Pre Assy Mengganti Semua Label "Process Defect" Menjadi "Assembly Defect"
     */
    public function test_pre_assy_replaces_process_defect_with_assembly_defect(): void
    {
        Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'ditemukan_oleh'   => 'Operator',
            'jenis_defect'     => 'CRIMPING',
            'jenis_sub_defect' => 'LOCK',
            'quantity'         => 3,
            'inspect_quantity' => 10,
        ]);

        $res = $this->actingAsAdmin()->get(route('pre_assy.index'));
        $res->assertOk();
        $res->assertSee('Assembly Defect');
        $res->assertSee('Assembly Defect (Operator)');
        $res->assertDontSee('Process Defect');
    }

    /**
     * 12. Test Sidebar Merender Akordion Final Assy dan Pre Assy dan Master Kanban
     */
    public function test_sidebar_renders_accordions_and_master_kanban(): void
    {
        $res = $this->actingAsAdmin()->get(route('dashboard'));
        $res->assertOk();

        // Akordion state di sidebar
        $res->assertSee('finalAssyOpen:', false);
        $res->assertSee('preAssyOpen:', false);

        // Submenu Final Assy
        $res->assertSee(route('final_assy.internal'));
        $res->assertSee(route('final_assy.assembly'));
        $res->assertSee(route('final_assy.rework'));

        // Submenu Pre Assy
        $res->assertSee(route('pre_assy.internal'));
        $res->assertSee(route('pre_assy.assembly'));

        // Master Kanban (bukan Cetak Kanban)
        $res->assertSee('Master Kanban');
        $res->assertDontSee('Cetak Kanban');
    }

    /**
     * 13. Test Halaman Master Kanban Memuat Tombol "Atur Format Urutan" dan "Import Excel / CSV"
     */
    public function test_master_kanban_view_contains_features(): void
    {
        $res = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index'));
        $res->assertOk();

        $res->assertSee('Master Kanban');
        $res->assertSee('Atur Format Urutan');
        $res->assertSee(route('admin.master.kanban_format.index'));
        $res->assertSee('Import Excel / CSV');
    }
}
