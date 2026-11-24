<?php

namespace Tests\Feature;

use App\Exports\ReworkHistoryExport;
use App\Models\AreaDitemukan;
use App\Models\Carline;
use App\Models\CarType;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Tests\TestCase;

class Batch3ReworkExportAndFilterTest extends TestCase
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
     * 1. Header dan urutan 32 kolom persis sesuai spesifikasi.
     */
    public function test_rework_export_has_exact_32_columns_in_exact_order(): void
    {
        $export = new ReworkHistoryExport(new Request());
        $headings = $export->headings();

        $expectedHeadings = [
            'No',
            'No Rework',
            'Status Rework',
            'Input Susulan',
            'Waktu In',
            'Waktu Out',
            'Waktu Rework (Menit)',
            'Shift',
            'Operator Rework',
            'Scan Kanban Assy',
            'No Urut Kanban',
            'Status Kanban',
            'No Nameplate Lama',
            'No Nameplate Baru',
            'Jenis Mobil',
            'Konveyor',
            'Kode Defect',
            'Jenis Defect',
            'Nama Sub Defect',
            'Quantity',
            'Area Ditemukan',
            'Tipe Area',
            'No Defective Tag',
            '4M Change Status',
            'Ujung (#End)',
            'Carton Box / No Box',
            'Posisi & Detail Rework',
            'F C/H',
            'F C/W',
            'R C/H',
            'R C/W',
            'Group ID',
        ];

        $this->assertCount(32, $headings);
        $this->assertSame($expectedHeadings, $headings);
    }

    /**
     * 2. 1 harness dengan 2 defect menghasilkan 2 baris dengan No Rework sama.
     */
    public function test_one_harness_with_two_defects_produces_two_rows_with_same_no_rework(): void
    {
        $groupId1 = (string) Str::uuid();
        $groupId2 = (string) Str::uuid();

        // Harness 1: 2 defect
        $d1 = Defect::create([
            'rework_group_id'  => $groupId1,
            'user_name'        => 'Operator QA',
            'waktu'            => Carbon::parse('2026-11-24 08:00:00'),
            'waktu_in'         => Carbon::parse('2026-11-24 08:00:00'),
            'status_rework'    => 'OPEN',
            'kanban_assy'      => 'KB-H1',
            'nameplate_lama'   => 'NP-H1',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT 1',
            'jenis_sub_defect' => 'SUB 1',
            'quantity'         => 1,
        ]);

        $d2 = Defect::create([
            'rework_group_id'  => $groupId1,
            'user_name'        => 'Operator QA',
            'waktu'            => Carbon::parse('2026-11-24 08:00:00'),
            'waktu_in'         => Carbon::parse('2026-11-24 08:00:00'),
            'status_rework'    => 'OPEN',
            'kanban_assy'      => 'KB-H1',
            'nameplate_lama'   => 'NP-H1',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT 2',
            'jenis_sub_defect' => 'SUB 2',
            'quantity'         => 1,
        ]);

        // Harness 2: 1 defect
        $d3 = Defect::create([
            'rework_group_id'  => $groupId2,
            'user_name'        => 'Operator QA',
            'waktu'            => Carbon::parse('2026-11-24 09:00:00'),
            'waktu_in'         => Carbon::parse('2026-11-24 09:00:00'),
            'status_rework'    => 'CLOSE',
            'waktu_out'        => Carbon::parse('2026-11-24 09:30:00'),
            'kanban_assy'      => 'KB-H2',
            'nameplate_lama'   => 'NP-H2',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT 3',
            'jenis_sub_defect' => 'SUB 3',
            'quantity'         => 1,
        ]);

        $export = new ReworkHistoryExport(new Request());
        $rows = $export->query()->get();

        $this->assertCount(3, $rows);

        $mapped1 = $export->map($rows[0]);
        $mapped2 = $export->map($rows[1]);
        $mapped3 = $export->map($rows[2]);

        // Baris 1: No = 1, No Rework = 1
        $this->assertEquals(1, $mapped1[0]);
        $this->assertEquals(1, $mapped1[1]);

        // Baris 2: No = 2, No Rework = 1 (karena group sama)
        $this->assertEquals(2, $mapped2[0]);
        $this->assertEquals(1, $mapped2[1]);

        // Baris 3: No = 3, No Rework = 2 (harness kedua)
        $this->assertEquals(3, $mapped3[0]);
        $this->assertEquals(2, $mapped3[1]);
    }

    /**
     * 3. Waktu Out "-" saat OPEN, format d/m/Y H:i saat CLOSE.
     */
    public function test_waktu_out_is_dash_when_status_is_open_and_formatted_when_close(): void
    {
        $groupOpen = (string) Str::uuid();
        $groupClose = (string) Str::uuid();

        // Status OPEN dengan waktu_in
        $openDefect = Defect::create([
            'rework_group_id'  => $groupOpen,
            'user_name'        => 'Operator QA',
            'waktu'            => Carbon::parse('2026-11-24 08:15:00'),
            'waktu_in'         => Carbon::parse('2026-11-24 08:15:00'),
            'waktu_out'        => null,
            'status_rework'    => 'OPEN',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT OPEN',
            'jenis_sub_defect' => 'SUB OPEN',
            'quantity'         => 1,
        ]);

        // Status CLOSE dengan waktu_in dan waktu_out
        $closeDefect = Defect::create([
            'rework_group_id'      => $groupClose,
            'user_name'            => 'Operator QA',
            'waktu'                => Carbon::parse('2026-11-24 09:10:00'),
            'waktu_in'             => Carbon::parse('2026-11-24 09:10:00'),
            'waktu_out'            => Carbon::parse('2026-11-24 09:45:00'),
            'status_rework'        => 'CLOSE',
            'durasi_rework_menit'  => 35,
            'jenis_assy'           => 'Final Assy',
            'jenis_defect'         => 'DEFECT CLOSE',
            'jenis_sub_defect'     => 'SUB CLOSE',
            'quantity'             => 1,
        ]);

        $export = new ReworkHistoryExport(new Request());

        $mappedOpen = $export->map($openDefect);
        $this->assertEquals('24/11/2026 08:15', $mappedOpen[4]); // Waktu In
        $this->assertEquals('-', $mappedOpen[5]); // Waktu Out "-" saat OPEN

        $mappedClose = $export->map($closeDefect);
        $this->assertEquals('24/11/2026 09:10', $mappedClose[4]); // Waktu In
        $this->assertEquals('24/11/2026 09:45', $mappedClose[5]); // Waktu Out terformat d/m/Y H:i
        $this->assertEquals(35, $mappedClose[6]); // Waktu Rework (Menit)
    }

    /**
     * 4. Filter shift, area, status, dan tanggal mempengaruhi export.
     */
    public function test_filters_shift_area_status_and_date_affect_export(): void
    {
        $areaA = AreaDitemukan::create(['name' => 'AREA-EXPORT-A', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $areaB = AreaDitemukan::create(['name' => 'AREA-EXPORT-B', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY_DEFECT]);

        // Defect A: Shift 1A, Area A, OPEN, 2026-11-20
        $defectA = Defect::create([
            'rework_group_id'  => (string) Str::uuid(),
            'user_name'        => 'Operator QA',
            'waktu'            => Carbon::parse('2026-11-20 08:00:00'),
            'waktu_in'         => Carbon::parse('2026-11-20 08:00:00'),
            'status_rework'    => 'OPEN',
            'shift'            => '1A',
            'area_ditemukan'   => $areaA->name,
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT A',
            'jenis_sub_defect' => 'SUB A',
            'quantity'         => 1,
        ]);

        // Defect B: Shift 1B, Area B, CLOSE, 2026-11-24
        $defectB = Defect::create([
            'rework_group_id'     => (string) Str::uuid(),
            'user_name'           => 'Operator QA',
            'waktu'               => Carbon::parse('2026-11-24 14:00:00'),
            'waktu_in'            => Carbon::parse('2026-11-24 14:00:00'),
            'waktu_out'           => Carbon::parse('2026-11-24 14:30:00'),
            'status_rework'       => 'CLOSE',
            'durasi_rework_menit' => 30,
            'shift'               => '1B',
            'area_ditemukan'      => $areaB->name,
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => 'DEFECT B',
            'jenis_sub_defect'    => 'SUB B',
            'quantity'            => 1,
        ]);

        // a. Filter Shift
        $exportShift1A = new ReworkHistoryExport(new Request(['shift' => '1A']));
        $rowsShift1A = $exportShift1A->query()->get();
        $this->assertTrue($rowsShift1A->contains('id', $defectA->id));
        $this->assertFalse($rowsShift1A->contains('id', $defectB->id));

        $exportShift1B = new ReworkHistoryExport(new Request(['shift' => '1B']));
        $rowsShift1B = $exportShift1B->query()->get();
        $this->assertFalse($rowsShift1B->contains('id', $defectA->id));
        $this->assertTrue($rowsShift1B->contains('id', $defectB->id));

        // b. Filter Area
        $exportAreaA = new ReworkHistoryExport(new Request(['area' => $areaA->name]));
        $rowsAreaA = $exportAreaA->query()->get();
        $this->assertTrue($rowsAreaA->contains('id', $defectA->id));
        $this->assertFalse($rowsAreaA->contains('id', $defectB->id));

        $exportAreaB = new ReworkHistoryExport(new Request(['area' => $areaB->name]));
        $rowsAreaB = $exportAreaB->query()->get();
        $this->assertFalse($rowsAreaB->contains('id', $defectA->id));
        $this->assertTrue($rowsAreaB->contains('id', $defectB->id));

        // c. Filter Status
        $exportOpen = new ReworkHistoryExport(new Request(['status' => 'OPEN']));
        $rowsOpen = $exportOpen->query()->get();
        $this->assertTrue($rowsOpen->contains('id', $defectA->id));
        $this->assertFalse($rowsOpen->contains('id', $defectB->id));

        $exportClose = new ReworkHistoryExport(new Request(['status' => 'CLOSE']));
        $rowsClose = $exportClose->query()->get();
        $this->assertFalse($rowsClose->contains('id', $defectA->id));
        $this->assertTrue($rowsClose->contains('id', $defectB->id));

        // d. Filter Tanggal
        $exportDateA = new ReworkHistoryExport(new Request(['date_range' => '2026-11-20 to 2026-11-21']));
        $rowsDateA = $exportDateA->query()->get();
        $this->assertTrue($rowsDateA->contains('id', $defectA->id));
        $this->assertFalse($rowsDateA->contains('id', $defectB->id));

        $exportDateB = new ReworkHistoryExport(new Request(['date_range' => '2026-11-24 to 2026-11-24']));
        $rowsDateB = $exportDateB->query()->get();
        $this->assertFalse($rowsDateB->contains('id', $defectA->id));
        $this->assertTrue($rowsDateB->contains('id', $defectB->id));
    }

    /**
     * 5. Filter shift dan area pada halaman Riwayat Rework bekerja dan view merender dropdown filter.
     */
    public function test_page_filters_shift_and_area_work(): void
    {
        $areaAlpha = AreaDitemukan::create(['name' => 'AREA-ALPHA', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $areaBeta  = AreaDitemukan::create(['name' => 'AREA-BETA', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY_DEFECT]);

        $groupAlpha = (string) Str::uuid();
        $groupBeta  = (string) Str::uuid();

        // Group Alpha: Shift 2A, Area ALPHA
        Defect::create([
            'rework_group_id'  => $groupAlpha,
            'user_name'        => 'Operator QA',
            'waktu'            => Carbon::now(),
            'waktu_in'         => Carbon::now(),
            'status_rework'    => 'OPEN',
            'shift'            => '2A',
            'area_ditemukan'   => $areaAlpha->name,
            'kanban_assy'      => 'KB-ALPHA-99',
            'nameplate_lama'   => 'NP-ALPHA-99',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT ALPHA',
            'jenis_sub_defect' => 'SUB ALPHA',
            'quantity'         => 1,
        ]);

        // Group Beta: Shift 2B, Area BETA
        Defect::create([
            'rework_group_id'  => $groupBeta,
            'user_name'        => 'Operator QA',
            'waktu'            => Carbon::now(),
            'waktu_in'         => Carbon::now(),
            'status_rework'    => 'OPEN',
            'shift'            => '2B',
            'area_ditemukan'   => $areaBeta->name,
            'kanban_assy'      => 'KB-BETA-88',
            'nameplate_lama'   => 'NP-BETA-88',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT BETA',
            'jenis_sub_defect' => 'SUB BETA',
            'quantity'         => 1,
        ]);

        // 1. Cek Dropdown Filter di HTML
        $resAll = $this->actingAsAdmin()->get(route('admin.rework_history.index'));
        $resAll->assertOk();
        $resAll->assertSee('name="shift"', false);
        $resAll->assertSee('name="area"', false);
        $resAll->assertSee('Semua Shift');
        $resAll->assertSee('Semua Area');
        $resAll->assertSee('Shift 1A');
        $resAll->assertSee('Shift 1B');
        $resAll->assertSee('Shift 2A');
        $resAll->assertSee('Shift 2B');
        $resAll->assertSee($areaAlpha->name);
        $resAll->assertSee($areaBeta->name);
        $resAll->assertSee('KB-ALPHA-99');
        $resAll->assertSee('KB-BETA-88');

        // 2. Filter Shift = 2A
        $resShift2A = $this->actingAsAdmin()->get(route('admin.rework_history.index', ['shift' => '2A']));
        $resShift2A->assertOk();
        $resShift2A->assertSee('KB-ALPHA-99');
        $resShift2A->assertDontSee('KB-BETA-88');

        // 3. Filter Shift = 2B
        $resShift2B = $this->actingAsAdmin()->get(route('admin.rework_history.index', ['shift' => '2B']));
        $resShift2B->assertOk();
        $resShift2B->assertDontSee('KB-ALPHA-99');
        $resShift2B->assertSee('KB-BETA-88');

        // 4. Filter Area = AREA-ALPHA
        $resAreaAlpha = $this->actingAsAdmin()->get(route('admin.rework_history.index', ['area' => $areaAlpha->name]));
        $resAreaAlpha->assertOk();
        $resAreaAlpha->assertSee('KB-ALPHA-99');
        $resAreaAlpha->assertDontSee('KB-BETA-88');

        // 5. Filter Area = AREA-BETA
        $resAreaBeta = $this->actingAsAdmin()->get(route('admin.rework_history.index', ['area' => $areaBeta->name]));
        $resAreaBeta->assertOk();
        $resAreaBeta->assertDontSee('KB-ALPHA-99');
        $resAreaBeta->assertSee('KB-BETA-88');
    }

    /**
     * 6. Fallback kanban_status di ReworkHistoryExport:
     *    - Jika kanban_status NULL tetapi kanban_assy terisi, hitung via evaluateKanban.
     *    - Tidak menulis ke database.
     *    - Jika kanban_assy juga kosong/NULL, tampil "-".
     *    - Jika kanban_status sudah terisi, pakai status yang ada.
     */
    public function test_kanban_status_fallback_when_null_in_export(): void
    {
        $group = (string) Str::uuid();

        // Case A: kanban_status NULL, kanban_assy input asal-asalan -> "Format Tidak Dikenal"
        $defectA = Defect::create([
            'rework_group_id'  => $group,
            'user_name'        => 'Operator QA',
            'waktu'            => now(),
            'waktu_in'         => now(),
            'status_rework'    => 'OPEN',
            'kanban_assy'      => 'MANUAL-ASAL-ASALAN',
            'kanban_status'    => null,
            'nameplate_lama'   => 'NP-01',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT A',
            'jenis_sub_defect' => 'SUB A',
            'quantity'         => 1,
        ]);

        // Case B: kanban_status NULL, kanban_assy format valid tetapi tidak di master -> "Tidak Terdaftar"
        $defectB = Defect::create([
            'rework_group_id'  => $group,
            'user_name'        => 'Operator QA',
            'waktu'            => now(),
            'waktu_in'         => now(),
            'status_rework'    => 'OPEN',
            'kanban_assy'      => '82111-VU020.CA1.010',
            'kanban_status'    => null,
            'nameplate_lama'   => 'NP-02',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT B',
            'jenis_sub_defect' => 'SUB B',
            'quantity'         => 1,
        ]);

        // Case C: kanban_status NULL, kanban_assy NULL/kosong -> "-"
        $defectC = Defect::create([
            'rework_group_id'  => $group,
            'user_name'        => 'Operator QA',
            'waktu'            => now(),
            'waktu_in'         => now(),
            'status_rework'    => 'OPEN',
            'kanban_assy'      => null,
            'kanban_status'    => null,
            'nameplate_lama'   => 'NP-03',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT C',
            'jenis_sub_defect' => 'SUB C',
            'quantity'         => 1,
        ]);

        // Case D: kanban_status sudah terisi "family_beda" -> "Family Beda"
        $defectD = Defect::create([
            'rework_group_id'  => $group,
            'user_name'        => 'Operator QA',
            'waktu'            => now(),
            'waktu_in'         => now(),
            'status_rework'    => 'OPEN',
            'kanban_assy'      => 'MANUAL-INPUT',
            'kanban_status'    => 'family_beda',
            'nameplate_lama'   => 'NP-04',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DEFECT D',
            'jenis_sub_defect' => 'SUB D',
            'quantity'         => 1,
        ]);

        $export = new ReworkHistoryExport(new Request());

        $mappedA = $export->map($defectA);
        $mappedB = $export->map($defectB);
        $mappedC = $export->map($defectC);
        $mappedD = $export->map($defectD);

        // Heading index 11 adalah "Status Kanban" (kolom ke-12)
        $headings = $export->headings();
        $statusColIdx = array_search('Status Kanban', $headings, true);
        $this->assertSame(11, $statusColIdx);

        $this->assertSame('Format Tidak Dikenal', $mappedA[$statusColIdx]);
        $this->assertSame('Tidak Terdaftar', $mappedB[$statusColIdx]);
        $this->assertSame('-', $mappedC[$statusColIdx]);
        $this->assertSame('Family Beda', $mappedD[$statusColIdx]);

        // Pastikan tidak menulis perubahan ke database
        $defectA->refresh();
        $this->assertNull($defectA->kanban_status);

        $defectB->refresh();
        $this->assertNull($defectB->kanban_status);
    }
}
