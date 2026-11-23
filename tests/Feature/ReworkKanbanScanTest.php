<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\AssyNumber;
use App\Models\Carline;
use App\Models\CarType;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use App\Support\KanbanCodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class ReworkKanbanScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Once::class)) {
            Once::flush();
        }

        KanbanCodeParser::clearCache();

        \Illuminate\Support\Facades\Http::fake([
            '*/users*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            '*' => \Illuminate\Support\Facades\Http::response([], 200),
        ]);
    }

    private function actingAsReworkUser(string $name = 'rework_operator')
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => $name,
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ]);
    }

    private function actingAsAdmin(string $name = 'admin_user')
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => $name,
            'user_role'     => 'Administrator',
            'current_shift' => '1A',
        ]);
    }

    private function setupReworkFixtures(): array
    {
        $carType = CarType::create(['name' => 'AVANZA']);
        $carline = Carline::create([
            'name'        => 'C7',
            'car_type_id' => $carType->id,
        ]);

        $defectType = DefectType::create([
            'name' => 'TERMINAL DEFECT',
            'type' => 'Final Assy',
        ]);

        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CRIMPING JELEK',
            'code'           => '1A',
        ]);

        $reworkArea = AreaDitemukan::create([
            'name'     => 'RWK_AREA',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        return compact('carType', 'carline', 'defectType', 'subDefect', 'reworkArea');
    }

    public function test_kanban_scan_with_asterisk_registered_when_assy_and_conveyor_match(): void
    {
        $fixtures = $this->setupReworkFixtures();

        AssyNumber::create([
            'assy_number' => '8211-VU020',
            'carline_id'  => $fixtures['carline']->id,
            'conveyor'    => 'C7',
            'family'      => 'VU020',
        ]);

        $payload = [
            'kanban_assy'    => '*8211-VU020.C7.010*',
            'nameplate'      => 'NP-1001',
            'sub_defect_ids' => [$fixtures['subDefect']->id],
            'area_ditemukan' => $fixtures['reworkArea']->name,
            'carline_id'     => $fixtures['carline']->id,
            'conveyor'       => 'C7',
            'mode_input'     => 'normal',
        ];

        $response = $this->actingAsReworkUser()->post(route('rework.store_in'), $payload);
        $response->assertRedirect(route('rework.index'));

        $defect = Defect::latest('id')->first();
        $this->assertNotNull($defect);
        $this->assertSame('*8211-VU020.C7.010*', $defect->kanban_assy);
        $this->assertSame(10, $defect->kanban_serial);
        $this->assertSame('terdaftar', $defect->kanban_status);
    }

    public function test_kanban_scan_without_asterisk_registered(): void
    {
        $fixtures = $this->setupReworkFixtures();

        AssyNumber::create([
            'assy_number' => '8211-VU020',
            'carline_id'  => $fixtures['carline']->id,
            'conveyor'    => 'C7',
        ]);

        $payload = [
            'kanban_assy'    => '8211-VU020.C7.010',
            'nameplate'      => 'NP-1002',
            'sub_defect_ids' => [$fixtures['subDefect']->id],
            'area_ditemukan' => $fixtures['reworkArea']->name,
            'carline_id'     => $fixtures['carline']->id,
            'conveyor'       => 'C7',
            'mode_input'     => 'normal',
        ];

        $response = $this->actingAsReworkUser()->post(route('rework.store_in'), $payload);
        $response->assertRedirect(route('rework.index'));

        $defect = Defect::latest('id')->first();
        $this->assertNotNull($defect);
        $this->assertSame(10, $defect->kanban_serial);
        $this->assertSame('terdaftar', $defect->kanban_status);
    }

    public function test_conveyor_master_null_does_not_produce_conveyor_beda(): void
    {
        $fixtures = $this->setupReworkFixtures();

        AssyNumber::create([
            'assy_number' => '8211-VU020',
            'carline_id'  => $fixtures['carline']->id,
            'conveyor'    => null, // Conveyor master masih NULL
        ]);

        $payload = [
            'kanban_assy'    => '*8211-VU020.C7.010*',
            'nameplate'      => 'NP-1003',
            'sub_defect_ids' => [$fixtures['subDefect']->id],
            'area_ditemukan' => $fixtures['reworkArea']->name,
            'carline_id'     => $fixtures['carline']->id,
            'mode_input'     => 'normal',
        ];

        $response = $this->actingAsReworkUser()->post(route('rework.store_in'), $payload);
        $response->assertRedirect(route('rework.index'));

        $defect = Defect::latest('id')->first();
        $this->assertNotNull($defect);
        $this->assertSame(10, $defect->kanban_serial);
        // Tetap 'terdaftar', bukan 'conveyor_beda' karena conveyor master NULL
        $this->assertSame('terdaftar', $defect->kanban_status);
    }

    public function test_conveyor_beda_when_master_conveyor_filled_and_differs(): void
    {
        $fixtures = $this->setupReworkFixtures();

        AssyNumber::create([
            'assy_number' => '8211-VU020',
            'carline_id'  => $fixtures['carline']->id,
            'conveyor'    => 'C5', // Berbeda dari hasil scan C7
        ]);

        $payload = [
            'kanban_assy'    => '*8211-VU020.C7.010*',
            'nameplate'      => 'NP-1004',
            'sub_defect_ids' => [$fixtures['subDefect']->id],
            'area_ditemukan' => $fixtures['reworkArea']->name,
            'carline_id'     => $fixtures['carline']->id,
            'mode_input'     => 'normal',
        ];

        $response = $this->actingAsReworkUser()->post(route('rework.store_in'), $payload);
        $response->assertRedirect(route('rework.index'));

        $defect = Defect::latest('id')->first();
        $this->assertNotNull($defect);
        $this->assertSame(10, $defect->kanban_serial);
        $this->assertSame('conveyor_beda', $defect->kanban_status);
    }

    public function test_tidak_terdaftar_saves_rework_successfully(): void
    {
        $fixtures = $this->setupReworkFixtures();
        // AssyNumber tidak dibuat di master

        $payload = [
            'kanban_assy'    => '*9999-NOTFOUND.C7.025*',
            'nameplate'      => 'NP-1005',
            'sub_defect_ids' => [$fixtures['subDefect']->id],
            'area_ditemukan' => $fixtures['reworkArea']->name,
            'carline_id'     => $fixtures['carline']->id,
            'mode_input'     => 'normal',
        ];

        $response = $this->actingAsReworkUser()->post(route('rework.store_in'), $payload);
        $response->assertRedirect(route('rework.index'));

        $defect = Defect::latest('id')->first();
        $this->assertNotNull($defect);
        $this->assertSame(25, $defect->kanban_serial);
        $this->assertSame('tidak_terdaftar', $defect->kanban_status);
    }

    public function test_format_tidak_dikenal_saves_rework_successfully(): void
    {
        $fixtures = $this->setupReworkFixtures();

        $payload = [
            'kanban_assy'    => 'MANUAL-INPUT-STRING',
            'nameplate'      => 'NP-1006',
            'sub_defect_ids' => [$fixtures['subDefect']->id],
            'area_ditemukan' => $fixtures['reworkArea']->name,
            'carline_id'     => $fixtures['carline']->id,
            'mode_input'     => 'normal',
        ];

        $response = $this->actingAsReworkUser()->post(route('rework.store_in'), $payload);
        $response->assertRedirect(route('rework.index'));

        $defect = Defect::latest('id')->first();
        $this->assertNotNull($defect);
        $this->assertNull($defect->kanban_serial);
        $this->assertSame('format_tidak_dikenal', $defect->kanban_status);
    }

    public function test_rework_history_view_displays_serial_and_status_badges(): void
    {
        $fixtures = $this->setupReworkFixtures();

        // 1. Rework dengan status 'terdaftar' -> tidak tampilkan badge status
        Defect::create([
            'rework_group_id'     => 'group-terdaftar-1',
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'OPEN',
            'kanban_assy'         => '8211-VU020.C7.010',
            'kanban_serial'       => 10,
            'kanban_status'       => 'terdaftar',
            'nameplate_lama'      => 'NP-01',
            'area_ditemukan'      => $fixtures['reworkArea']->name,
            'user_name'           => 'rework_operator',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $fixtures['defectType']->id,
            'sub_defect_type_id'  => $fixtures['subDefect']->id,
            'jenis_defect'        => $fixtures['defectType']->name,
            'jenis_sub_defect'    => $fixtures['subDefect']->name,
        ]);

        // 2. Rework dengan status 'tidak_terdaftar' -> tampilkan badge "Belum terdaftar"
        Defect::create([
            'rework_group_id'     => 'group-unreg-2',
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'OPEN',
            'kanban_assy'         => '9999-NOTFOUND.C7.020',
            'kanban_serial'       => 20,
            'kanban_status'       => 'tidak_terdaftar',
            'nameplate_lama'      => 'NP-02',
            'area_ditemukan'      => $fixtures['reworkArea']->name,
            'user_name'           => 'rework_operator',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $fixtures['defectType']->id,
            'sub_defect_type_id'  => $fixtures['subDefect']->id,
            'jenis_defect'        => $fixtures['defectType']->name,
            'jenis_sub_defect'    => $fixtures['subDefect']->name,
        ]);

        $response = $this->actingAsAdmin()->get('/admin/rework-history');
        $response->assertOk();

        $response->assertSee('#10');
        $response->assertSee('#20');
        $response->assertSee('Belum terdaftar');
    }

    public function test_rework_history_export_contains_new_headings_and_values(): void
    {
        $fixtures = $this->setupReworkFixtures();

        $defect = Defect::create([
            'rework_group_id'     => 'group-export-test-1',
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'OPEN',
            'kanban_assy'         => '8211-VU020.C7.010',
            'kanban_serial'       => 10,
            'kanban_status'       => 'conveyor_beda',
            'nameplate_lama'      => 'NP-03',
            'area_ditemukan'      => $fixtures['reworkArea']->name,
            'user_name'           => 'rework_operator',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $fixtures['defectType']->id,
            'sub_defect_type_id'  => $fixtures['subDefect']->id,
            'jenis_defect'        => $fixtures['defectType']->name,
            'jenis_sub_defect'    => $fixtures['subDefect']->name,
        ]);

        $export = new \App\Exports\ReworkHistoryExport(new \Illuminate\Http\Request(['search' => '8211-VU020.C7.010']));
        $headings = $export->headings();

        $this->assertContains('No Urut Kanban', $headings);
        $this->assertContains('Status Kanban', $headings);

        $mapped = $export->map($defect);
        $this->assertContains(10, $mapped);
        $this->assertContains('Conveyor Beda', $mapped);
    }

    public function test_rework_update_recalculates_kanban_serial_and_status(): void
    {
        $fixtures = $this->setupReworkFixtures();

        AssyNumber::create([
            'assy_number' => '8211-VU099',
            'carline_id'  => $fixtures['carline']->id,
            'conveyor'    => 'C7',
        ]);

        $defect = Defect::create([
            'rework_group_id'     => 'group-update-test-1',
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'OPEN',
            'kanban_assy'         => 'MANUAL-INPUT-OLD',
            'kanban_serial'       => null,
            'kanban_status'       => 'format_tidak_dikenal',
            'nameplate_lama'      => 'NP-OLD',
            'area_ditemukan'      => $fixtures['reworkArea']->name,
            'user_name'           => 'rework_operator',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $fixtures['defectType']->id,
            'sub_defect_type_id'  => $fixtures['subDefect']->id,
            'jenis_defect'        => $fixtures['defectType']->name,
            'jenis_sub_defect'    => $fixtures['subDefect']->name,
            'carline_id'          => $fixtures['carline']->id,
            'conveyor'            => 'C7',
        ]);

        $payload = [
            'kanban_assy'    => '*8211-VU099.C7.030*',
            'nameplate'      => 'NP-NEW',
            'sub_defect_ids' => [$fixtures['subDefect']->id],
            'area_ditemukan' => $fixtures['reworkArea']->name,
            'carline_id'     => $fixtures['carline']->id,
            'conveyor'       => 'C7',
        ];

        $response = $this->actingAsReworkUser()->put(route('rework.update', ['groupId' => 'group-update-test-1']), $payload);
        $response->assertRedirect(route('rework.index'));

        $defect->refresh();
        $this->assertSame('*8211-VU099.C7.030*', $defect->kanban_assy);
        $this->assertSame(30, $defect->kanban_serial);
        $this->assertSame('terdaftar', $defect->kanban_status);
    }

    public function test_scan_with_and_without_asterisk_matches_master_assy_kcll_and_serial_020_becomes_20(): void
    {
        $fixtures = $this->setupReworkFixtures();

        // Master disimpan dengan assy 'KCLL' dan family '8211' (dua kolom terpisah)
        AssyNumber::create([
            'assy_number' => 'KCLL',
            'family'      => '8211',
            'conveyor'    => 'AB1',
            'carline_id'  => $fixtures['carline']->id,
        ]);

        // 1. Scan dengan tanda bintang: *KCLL-8211.AB1.020*
        $evalWithStar = KanbanCodeParser::evaluateKanban('*KCLL-8211.AB1.020*');
        $this->assertNotNull($evalWithStar['master']);
        $this->assertSame('KCLL', $evalWithStar['master']->assy_number);
        $this->assertSame(20, $evalWithStar['kanban_serial']); // 020 -> 20
        $this->assertSame('terdaftar', $evalWithStar['kanban_status']);
        $this->assertSame('KCLL', $evalWithStar['parsed']['assy']);
        $this->assertSame('8211', $evalWithStar['parsed']['family']);

        // 2. Scan tanpa tanda bintang: KCLL-8211.AB1.020
        $evalWithoutStar = KanbanCodeParser::evaluateKanban('KCLL-8211.AB1.020');
        $this->assertNotNull($evalWithoutStar['master']);
        $this->assertSame('KCLL', $evalWithoutStar['master']->assy_number);
        $this->assertSame(20, $evalWithoutStar['kanban_serial']);
        $this->assertSame('terdaftar', $evalWithoutStar['kanban_status']);
    }

    public function test_kanban_scan_family_beda_status_and_master_family_null(): void
    {
        $fixtures = $this->setupReworkFixtures();

        // Master 1: Family master terisi '67030'
        $master1 = AssyNumber::create([
            'assy_number' => 'KCLT',
            'family'      => '67030',
            'conveyor'    => 'AB1',
            'carline_id'  => $fixtures['carline']->id,
        ]);

        // Scan scan family '8211' berbeda dari master '67030' -> 'family_beda'
        $evalFamilyBeda = KanbanCodeParser::evaluateKanban('KCLT-8211.AB1.020');
        $this->assertSame('family_beda', $evalFamilyBeda['kanban_status']);
        $this->assertSame(20, $evalFamilyBeda['kanban_serial']);

        // Simpan rework dengan family_beda -> tetap sukses tersimpan
        $payload = [
            'kanban_assy'    => 'KCLT-8211.AB1.020',
            'nameplate'      => 'NP-FB-1',
            'sub_defect_ids' => [$fixtures['subDefect']->id],
            'area_ditemukan' => $fixtures['reworkArea']->name,
            'carline_id'     => $fixtures['carline']->id,
            'mode_input'     => 'normal',
        ];

        $response = $this->actingAsReworkUser()->post(route('rework.store_in'), $payload);
        $response->assertRedirect(route('rework.index'));

        $defect = Defect::latest('id')->first();
        $this->assertNotNull($defect);
        $this->assertSame('family_beda', $defect->kanban_status);
        $this->assertSame(20, $defect->kanban_serial);

        // Halaman riwayat admin menampilkan badge "Family beda"
        $resAdmin = $this->actingAsAdmin()->get('/admin/rework-history');
        $resAdmin->assertSee('Family beda');

        // Master 2: Family master NULL -> TIDAK menghasilkan family_beda
        $master2 = AssyNumber::create([
            'assy_number' => 'KCLM',
            'family'      => null,
            'conveyor'    => 'AB1',
            'carline_id'  => $fixtures['carline']->id,
        ]);

        $evalFamilyNull = KanbanCodeParser::evaluateKanban('KCLM-8211.AB1.020');
        $this->assertSame('terdaftar', $evalFamilyNull['kanban_status']);
    }

    public function test_rework_saves_for_all_five_statuses(): void
    {
        $fixtures = $this->setupReworkFixtures();

        // 1. terdaftar
        AssyNumber::create(['assy_number' => 'REG1', 'family' => 'F1', 'conveyor' => 'CV1']);
        $r1 = KanbanCodeParser::evaluateKanban('REG1-F1.CV1.010');
        $this->assertSame('terdaftar', $r1['kanban_status']);

        // 2. tidak_terdaftar
        $r2 = KanbanCodeParser::evaluateKanban('NOTFOUND-F1.CV1.010');
        $this->assertSame('tidak_terdaftar', $r2['kanban_status']);

        // 3. family_beda
        AssyNumber::create(['assy_number' => 'FBEDA', 'family' => 'MASTERFAM', 'conveyor' => 'CV1']);
        $r3 = KanbanCodeParser::evaluateKanban('FBEDA-SCANFAM.CV1.010');
        $this->assertSame('family_beda', $r3['kanban_status']);

        // 4. conveyor_beda
        AssyNumber::create(['assy_number' => 'CBEDA', 'family' => 'F1', 'conveyor' => 'CVMASTER']);
        $r4 = KanbanCodeParser::evaluateKanban('CBEDA-F1.CVSCAN.010');
        $this->assertSame('conveyor_beda', $r4['kanban_status']);

        // 5. format_tidak_dikenal
        $r5 = KanbanCodeParser::evaluateKanban('NON-STANDARD-STRING');
        $this->assertSame('format_tidak_dikenal', $r5['kanban_status']);
    }

    public function test_assy_lookup_controller_response_for_found_and_not_found(): void
    {
        $fixtures = $this->setupReworkFixtures();

        AssyNumber::create([
            'assy_number' => 'KCLL',
            'family'      => '8211',
            'conveyor'    => 'AB1',
            'carline_id'  => $fixtures['carline']->id,
        ]);

        // 1. Lookup ketemu
        $responseFound = $this->actingAsReworkUser()->getJson(route('rework.lookup_assy', ['code' => 'KCLL-8211.AB1.020']));
        $responseFound->assertOk();
        $responseFound->assertJson([
            'assy_number'         => 'KCLL-8211',
            'assy'                => 'KCLL',
            'family'              => '8211',
            'conveyor'            => 'AB1',
            'serial'              => '020',
            'carline_id'          => $fixtures['carline']->id,
            'carline_nama'        => 'C7',
            'car_type_nama'       => 'AVANZA',
            'ditemukan_di_master' => true,
        ]);

        // 2. Lookup tidak ketemu
        $responseNotFound = $this->actingAsReworkUser()->getJson(route('rework.lookup_assy', ['code' => 'UNKNOWN-9999.AB1.020']));
        $responseNotFound->assertOk();
        $responseNotFound->assertJson([
            'assy_number'         => 'UNKNOWN-9999',
            'assy'                => 'UNKNOWN',
            'family'              => '9999',
            'conveyor'            => 'AB1',
            'serial'              => '020',
            'carline_id'          => null,
            'ditemukan_di_master' => false,
        ]);
    }
}
