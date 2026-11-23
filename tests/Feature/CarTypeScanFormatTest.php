<?php

namespace Tests\Feature;

use App\Models\AssyNumber;
use App\Models\Carline;
use App\Models\CarType;
use App\Support\KanbanCodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class CarTypeScanFormatTest extends TestCase
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

    private function actingAsAdmin()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator',
        ]);
    }

    private function actingAsRework()
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'Rework QA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ]);
    }

    /**
     * (a) jenis mobil assy_family: KCLL-8211.AB1.020 terdaftar
     */
    public function test_car_type_assy_family_registered(): void
    {
        $carType = CarType::create([
            'name'        => 'MAZDA',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);

        Carline::create([
            'name'        => 'J30A-AB1.INPANEL',
            'car_type_id' => $carType->id,
        ]);

        $master = AssyNumber::create([
            'assy_number' => 'KCLL',
            'family'      => '8211',
            'conveyor'    => 'AB1',
        ]);

        $eval = KanbanCodeParser::evaluateKanban('KCLL-8211.AB1.020');

        $this->assertSame('terdaftar', $eval['kanban_status']);
        $this->assertSame(20, $eval['kanban_serial']);
        $this->assertSame('KCLL', $eval['parsed']['assy']);
        $this->assertSame('8211', $eval['parsed']['family']);
        $this->assertSame($master->id, $eval['master']?->id);

        // Cek via endpoint lookup
        $response = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => 'KCLL-8211.AB1.020',
        ]));
        $response->assertOk();
        $response->assertJson([
            'assy_number'         => 'KCLL-8211',
            'assy'                => 'KCLL',
            'family'              => '8211',
            'conveyor'            => 'AB1',
            'serial'              => '020',
            'ditemukan_di_master' => true,
        ]);
    }

    /**
     * (b) jenis mobil family_assy: 82111-1LB01.xxx.010 terdaftar dengan master assy=1LB01 family=82111
     */
    public function test_car_type_family_assy_registered_with_swapped_tokens(): void
    {
        $carType = CarType::create([
            'name'        => 'NISSAN',
            'scan_format' => CarType::SCAN_FORMAT_FAMILY_ASSY,
        ]);

        Carline::create([
            'name'        => 'CV-XXX',
            'car_type_id' => $carType->id,
        ]);

        $master = AssyNumber::create([
            'assy_number' => '1LB01',
            'family'      => '82111',
            'conveyor'    => 'XXX',
        ]);

        $eval = KanbanCodeParser::evaluateKanban('82111-1LB01.xxx.010');

        $this->assertSame('terdaftar', $eval['kanban_status']);
        $this->assertSame(10, $eval['kanban_serial']);
        $this->assertSame('1LB01', $eval['parsed']['assy']);
        $this->assertSame('82111', $eval['parsed']['family']);
        $this->assertSame($master->id, $eval['master']?->id);

        // Cek via endpoint lookup
        $response = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '82111-1LB01.XXX.010',
        ]));
        $response->assertOk();
        $response->assertJson([
            'assy_number'         => '82111-1LB01',
            'assy'                => '1LB01',
            'family'              => '82111',
            'conveyor'            => 'XXX',
            'serial'              => '010',
            'ditemukan_di_master' => true,
        ]);
    }

    /**
     * (c) format dikonfigurasi tetapi scan urutan terbalik berstatus "Belum terdaftar"
     */
    public function test_configured_format_with_inverted_scan_is_unregistered(): void
    {
        // 1. Kasus assy_family tapi scan dikirim terbalik (family-assy)
        $carTypeMazda = CarType::create([
            'name'        => 'MAZDA',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);

        Carline::create([
            'name'        => 'J30A-AB1',
            'car_type_id' => $carTypeMazda->id,
        ]);

        AssyNumber::create([
            'assy_number' => 'KCLL',
            'family'      => '8211',
            'conveyor'    => 'AB1',
        ]);

        // Scan urutan terbalik: 8211-KCLL.AB1.020 (karena assy_family, token pertama 8211 dianggap assy)
        $evalInverted = KanbanCodeParser::evaluateKanban('8211-KCLL.AB1.020');

        $this->assertSame('tidak_terdaftar', $evalInverted['kanban_status']);
        $this->assertNull($evalInverted['master']);
        $this->assertSame('8211', $evalInverted['parsed']['assy']);
        $this->assertSame('KCLL', $evalInverted['parsed']['family']);

        // 2. Kasus family_assy tapi scan dikirim terbalik (assy-family)
        $carTypeNissan = CarType::create([
            'name'        => 'NISSAN',
            'scan_format' => CarType::SCAN_FORMAT_FAMILY_ASSY,
        ]);

        Carline::create([
            'name'        => 'CV-XXX',
            'car_type_id' => $carTypeNissan->id,
        ]);

        AssyNumber::create([
            'assy_number' => '1LB01',
            'family'      => '82111',
            'conveyor'    => 'XXX',
        ]);

        // Scan urutan terbalik: 1LB01-82111.XXX.010 (karena family_assy, token kedua 82111 dianggap assy)
        $evalNissanInverted = KanbanCodeParser::evaluateKanban('1LB01-82111.XXX.010');

        $this->assertSame('tidak_terdaftar', $evalNissanInverted['kanban_status']);
        $this->assertNull($evalNissanInverted['master']);
        $this->assertSame('82111', $evalNissanInverted['parsed']['assy']);
        $this->assertSame('1LB01', $evalNissanInverted['parsed']['family']);
    }

    /**
     * (d) NULL mencoba kedua urutan
     */
    public function test_null_scan_format_tries_both_orders(): void
    {
        $carTypeHonda = CarType::create([
            'name'        => 'HONDA',
            'scan_format' => null, // mode otomatis
        ]);

        Carline::create([
            'name'        => 'LINE-H1',
            'car_type_id' => $carTypeHonda->id,
        ]);

        // Urutan 1: cocok dengan Token B (family-assy)
        $master1 = AssyNumber::create([
            'assy_number' => 'ENG01',
            'family'      => 'FAM01',
            'conveyor'    => 'H1',
        ]);

        $eval1 = KanbanCodeParser::evaluateKanban('FAM01-ENG01.H1.005');
        $this->assertSame('terdaftar', $eval1['kanban_status']);
        $this->assertSame('ENG01', $eval1['parsed']['assy']);
        $this->assertSame('FAM01', $eval1['parsed']['family']);
        $this->assertSame($master1->id, $eval1['master']?->id);

        // Urutan 2: cocok dengan Token A (assy-family)
        $master2 = AssyNumber::create([
            'assy_number' => 'BDY02',
            'family'      => 'FAM02',
            'conveyor'    => 'H1',
        ]);

        $eval2 = KanbanCodeParser::evaluateKanban('BDY02-FAM02.H1.006');
        $this->assertSame('terdaftar', $eval2['kanban_status']);
        $this->assertSame('BDY02', $eval2['parsed']['assy']);
        $this->assertSame('FAM02', $eval2['parsed']['family']);
        $this->assertSame($master2->id, $eval2['master']?->id);

        // Kasus kedua token ada di master: pilih yang family-nya cocok dengan token pasangannya
        $masterA = AssyNumber::create([
            'assy_number' => 'TOKENA',
            'family'      => 'TOKENB',
            'conveyor'    => 'H1',
        ]);
        AssyNumber::create([
            'assy_number' => 'TOKENB',
            'family'      => 'OTHER',
            'conveyor'    => 'H1',
        ]);

        $evalBoth = KanbanCodeParser::evaluateKanban('TOKENA-TOKENB.H1.007');
        $this->assertSame('terdaftar', $evalBoth['kanban_status']);
        $this->assertSame('TOKENA', $evalBoth['parsed']['assy']);
        $this->assertSame('TOKENB', $evalBoth['parsed']['family']);
        $this->assertSame($masterA->id, $evalBoth['master']?->id);
    }

    /**
     * (e) conveyor yang jenis mobilnya ambigu jatuh ke mode otomatis
     */
    public function test_ambiguous_conveyor_car_type_falls_back_to_automatic_mode(): void
    {
        $carTypeMazda = CarType::create([
            'name'        => 'MAZDA',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);
        $carTypeNissan = CarType::create([
            'name'        => 'NISSAN',
            'scan_format' => CarType::SCAN_FORMAT_FAMILY_ASSY,
        ]);

        // Conveyor MIX1 ada di dua carline dengan car_type berbeda
        Carline::create(['name' => 'LINE-MIX1', 'car_type_id' => $carTypeMazda->id]);
        Carline::create(['name' => 'LINE-MIX1.RHD', 'car_type_id' => $carTypeNissan->id]);

        $master = AssyNumber::create([
            'assy_number' => '1LB01',
            'family'      => '82111',
            'conveyor'    => 'MIX1',
        ]);

        // Scan berformat 82111-1LB01.MIX1.010
        // Karena MIX1 ambigu, jatuh ke mode otomatis dan menemukan assy 1LB01 di token B
        $eval = KanbanCodeParser::evaluateKanban('82111-1LB01.MIX1.010');

        $this->assertSame('terdaftar', $eval['kanban_status']);
        $this->assertSame('1LB01', $eval['parsed']['assy']);
        $this->assertSame('82111', $eval['parsed']['family']);
        $this->assertSame($master->id, $eval['master']?->id);
    }

    /**
     * (f) huruf kecil dan bintang tetap terdaftar
     */
    public function test_lowercase_and_asterisks_are_registered(): void
    {
        $carType = CarType::create([
            'name'        => 'MAZDA',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);

        Carline::create([
            'name'        => 'J30A-AB1',
            'car_type_id' => $carType->id,
        ]);

        AssyNumber::create([
            'assy_number' => 'KCLL',
            'family'      => '8211',
            'conveyor'    => 'AB1',
        ]);

        // Huruf kecil dan bintang
        $eval = KanbanCodeParser::evaluateKanban('*kcll-8211.ab1.020*');

        $this->assertSame('terdaftar', $eval['kanban_status']);
        $this->assertSame(20, $eval['kanban_serial']);
        $this->assertSame('KCLL', $eval['parsed']['assy']);
        $this->assertSame('8211', $eval['parsed']['family']);
        $this->assertSame('AB1', $eval['parsed']['conveyor']);
    }

    /**
     * (g) validasi form menolak nilai di luar tiga opsi
     */
    public function test_car_type_form_validation_rejects_invalid_scan_format(): void
    {
        // 1. Post nilai tidak valid ditolak
        $invalidStore = $this->actingAsAdmin()->post(route('admin.master.car_types.store'), [
            'name'        => 'INVALID_CAR',
            'scan_format' => 'format_palsu',
        ]);
        $invalidStore->assertSessionHasErrors('scan_format');
        $this->assertDatabaseMissing('car_types', ['name' => 'INVALID_CAR']);

        // 2. Post nilai assy_family sukses
        $storeAssyFamily = $this->actingAsAdmin()->post(route('admin.master.car_types.store'), [
            'name'        => 'CAR_ASSY_FAMILY',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);
        $storeAssyFamily->assertRedirect(route('admin.master.car_types.index'));
        $this->assertDatabaseHas('car_types', [
            'name'        => 'CAR_ASSY_FAMILY',
            'scan_format' => 'assy_family',
        ]);

        // 3. Post nilai family_assy sukses
        $storeFamilyAssy = $this->actingAsAdmin()->post(route('admin.master.car_types.store'), [
            'name'        => 'CAR_FAMILY_ASSY',
            'scan_format' => CarType::SCAN_FORMAT_FAMILY_ASSY,
        ]);
        $storeFamilyAssy->assertRedirect(route('admin.master.car_types.index'));
        $this->assertDatabaseHas('car_types', [
            'name'        => 'CAR_FAMILY_ASSY',
            'scan_format' => 'family_assy',
        ]);

        // 4. Post nilai kosong (otomatis) sukses
        $storeAuto = $this->actingAsAdmin()->post(route('admin.master.car_types.store'), [
            'name'        => 'CAR_AUTO',
            'scan_format' => '',
        ]);
        $storeAuto->assertRedirect(route('admin.master.car_types.index'));
        $createdAuto = CarType::where('name', 'CAR_AUTO')->first();
        $this->assertNotNull($createdAuto);
        $this->assertNull($createdAuto->scan_format);

        // 5. Update dengan nilai tidak valid ditolak
        $invalidUpdate = $this->actingAsAdmin()->put(route('admin.master.car_types.update', $createdAuto->id), [
            'name'        => 'CAR_AUTO_UPDATED',
            'scan_format' => 'tidak_valid',
        ]);
        $invalidUpdate->assertSessionHasErrors('scan_format');

        // 6. View menampilkan label yang sesuai di tabel
        $viewResponse = $this->actingAsAdmin()->get(route('admin.master.car_types.index'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Assy-Family');
        $viewResponse->assertSee('Family-Assy');
        $viewResponse->assertSee('Otomatis');
    }

    /**
     * (a) jenis mobil family_assy dengan master assy=1LB01 family=99999 dan scan 82111-1LB01.xxx.010 berstatus "Family beda"
     */
    public function test_family_assy_with_different_family_results_in_family_beda(): void
    {
        $carType = CarType::create([
            'name'        => 'NISSAN_DIFF',
            'scan_format' => CarType::SCAN_FORMAT_FAMILY_ASSY,
        ]);

        Carline::create([
            'name'        => 'CV-XXX',
            'car_type_id' => $carType->id,
        ]);

        $master = AssyNumber::create([
            'assy_number' => '1LB01',
            'family'      => '99999',
            'conveyor'    => 'XXX',
        ]);

        $eval = KanbanCodeParser::evaluateKanban('82111-1LB01.xxx.010');

        $this->assertSame('family_beda', $eval['kanban_status']);
        $this->assertSame(10, $eval['kanban_serial']);
        $this->assertSame('1LB01', $eval['parsed']['assy']);
        $this->assertSame('82111', $eval['parsed']['family']);
        $this->assertSame($master->id, $eval['master']?->id);
    }

    /**
     * (b) jenis mobil assy_family dengan scan KCLL-67030.AB1.020 dan master KCLL family=8211 berstatus "Family beda"
     */
    public function test_assy_family_with_different_family_results_in_family_beda(): void
    {
        $carType = CarType::create([
            'name'        => 'MAZDA_DIFF',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);

        Carline::create([
            'name'        => 'J30A-AB1',
            'car_type_id' => $carType->id,
        ]);

        $master = AssyNumber::create([
            'assy_number' => 'KCLL',
            'family'      => '8211',
            'conveyor'    => 'AB1',
        ]);

        $eval = KanbanCodeParser::evaluateKanban('KCLL-67030.AB1.020');

        $this->assertSame('family_beda', $eval['kanban_status']);
        $this->assertSame(20, $eval['kanban_serial']);
        $this->assertSame('KCLL', $eval['parsed']['assy']);
        $this->assertSame('67030', $eval['parsed']['family']);
        $this->assertSame($master->id, $eval['master']?->id);
    }

    /**
     * (c) master family kosong pada kedua format tidak dianggap beda
     */
    public function test_empty_master_family_is_not_treated_as_different_family_on_both_formats(): void
    {
        // 1. Format family_assy dengan master family null
        $carTypeNissan = CarType::create([
            'name'        => 'NISSAN_NULL_FAM',
            'scan_format' => CarType::SCAN_FORMAT_FAMILY_ASSY,
        ]);

        Carline::create([
            'name'        => 'CV-CV1',
            'car_type_id' => $carTypeNissan->id,
        ]);

        $masterNissan = AssyNumber::create([
            'assy_number' => '1LB01',
            'family'      => null,
            'conveyor'    => 'CV1',
        ]);

        $evalNissan = KanbanCodeParser::evaluateKanban('82111-1LB01.cv1.010');
        $this->assertSame('terdaftar', $evalNissan['kanban_status']);
        $this->assertSame('1LB01', $evalNissan['parsed']['assy']);
        $this->assertSame('82111', $evalNissan['parsed']['family']);
        $this->assertSame($masterNissan->id, $evalNissan['master']?->id);

        // 2. Format assy_family dengan master family null
        $carTypeMazda = CarType::create([
            'name'        => 'MAZDA_NULL_FAM',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);

        Carline::create([
            'name'        => 'J30A-CV2',
            'car_type_id' => $carTypeMazda->id,
        ]);

        $masterMazda = AssyNumber::create([
            'assy_number' => 'KCLL',
            'family'      => null,
            'conveyor'    => 'CV2',
        ]);

        $evalMazda = KanbanCodeParser::evaluateKanban('KCLL-67030.cv2.020');
        $this->assertSame('terdaftar', $evalMazda['kanban_status']);
        $this->assertSame('KCLL', $evalMazda['parsed']['assy']);
        $this->assertSame('67030', $evalMazda['parsed']['family']);
        $this->assertSame($masterMazda->id, $evalMazda['master']?->id);
    }

    /**
     * Saringan query case-insensitive dan token penuh (\b):
     * - Carline bernama "Ab1" (huruf kecil/campur) tetap cocok dengan scan AB1
     * - C5 tidak cocok dengan C5A
     */
    public function test_resolve_from_conveyor_is_case_insensitive_and_c5_does_not_match_c5a(): void
    {
        $carTypeMazda = CarType::create([
            'name'        => 'MAZDA_CASE',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);

        // Carline dengan huruf campuran "Line-Ab1"
        Carline::create([
            'name'        => 'Line-Ab1',
            'car_type_id' => $carTypeMazda->id,
        ]);

        $resolvedAb1 = CarType::resolveFromConveyor('AB1');
        $this->assertNotNull($resolvedAb1);
        $this->assertSame($carTypeMazda->id, $resolvedAb1->id);

        $resolvedLower = CarType::resolveFromConveyor('ab1');
        $this->assertNotNull($resolvedLower);
        $this->assertSame($carTypeMazda->id, $resolvedLower->id);

        // Carline dengan nama "Line-C5A"
        $carTypeToyota = CarType::create([
            'name'        => 'TOYOTA_CASE',
            'scan_format' => CarType::SCAN_FORMAT_FAMILY_ASSY,
        ]);

        Carline::create([
            'name'        => 'Line-C5A',
            'car_type_id' => $carTypeToyota->id,
        ]);

        // Scan dengan conveyor C5 tidak boleh cocok dengan Line-C5A
        $resolvedC5 = CarType::resolveFromConveyor('C5');
        $this->assertNull($resolvedC5);

        // Sedangkan C5A cocok dengan Toyota
        $resolvedC5A = CarType::resolveFromConveyor('C5A');
        $this->assertNotNull($resolvedC5A);
        $this->assertSame($carTypeToyota->id, $resolvedC5A->id);
    }

    /**
     * Mengosongkan conveyorCache otomatis saat CarType atau Carline disimpan/dihapus:
     * Ubah scan_format CarType lalu scan ulang dalam test case yang sama, hasilnya mengikuti nilai baru.
     */
    public function test_updating_car_type_scan_format_clears_cache_and_rescan_follows_new_value(): void
    {
        $carType = CarType::create([
            'name'        => 'CACHE_TEST_TYPE',
            'scan_format' => CarType::SCAN_FORMAT_ASSY_FAMILY,
        ]);

        $carline = Carline::create([
            'name'        => 'CV-AB1',
            'car_type_id' => $carType->id,
        ]);

        AssyNumber::create([
            'assy_number' => 'KCLL',
            'family'      => '8211',
            'conveyor'    => 'AB1',
        ]);

        // 1. Scan pertama (format assy_family):
        // Scan KCLL-8211.AB1.020 -> terdaftar
        $eval1 = KanbanCodeParser::evaluateKanban('KCLL-8211.AB1.020');
        $this->assertSame('terdaftar', $eval1['kanban_status']);
        $this->assertSame('KCLL', $eval1['parsed']['assy']);

        // Scan urutan terbalik 8211-KCLL.AB1.020 -> tidak_terdaftar
        $evalInverted1 = KanbanCodeParser::evaluateKanban('8211-KCLL.AB1.020');
        $this->assertSame('tidak_terdaftar', $evalInverted1['kanban_status']);

        // 2. Ubah scan_format CarType menjadi family_assy dalam test case yang sama
        // Event saved() akan mengosongkan cache CarType::$conveyorCache otomatis
        $carType->update([
            'scan_format' => CarType::SCAN_FORMAT_FAMILY_ASSY,
        ]);

        // 3. Scan ulang tanpa membersihkan cache secara manual
        // Sekarang formatnya family_assy:
        // KCLL-8211.AB1.020 sekarang tidak terdaftar karena token ke-2 (8211) dianggap assy
        $eval2 = KanbanCodeParser::evaluateKanban('KCLL-8211.AB1.020');
        $this->assertSame('tidak_terdaftar', $eval2['kanban_status']);

        // Sedangkan 8211-KCLL.AB1.020 sekarang terdaftar karena token ke-2 (KCLL) dianggap assy
        $evalInverted2 = KanbanCodeParser::evaluateKanban('8211-KCLL.AB1.020');
        $this->assertSame('terdaftar', $evalInverted2['kanban_status']);
        $this->assertSame('KCLL', $evalInverted2['parsed']['assy']);
        $this->assertSame('8211', $evalInverted2['parsed']['family']);

        // 4. Hapus carline: event deleted() juga mengosongkan cache CarType::$conveyorCache
        $carline->delete();
        $this->assertNull(CarType::resolveFromConveyor('AB1'));
    }

    /**
     * Scan rusak ("ABC", ".AB1.010", "*", string kosong) tidak melempar exception
     * dan mengembalikan status format_tidak_dikenal.
     */
    public function test_damaged_scans_do_not_throw_exceptions_and_return_format_tidak_dikenal(): void
    {
        $damagedScans = ['ABC', '.AB1.010', '*', ''];

        foreach ($damagedScans as $raw) {
            $eval = KanbanCodeParser::evaluateKanban($raw);

            $this->assertSame('format_tidak_dikenal', $eval['kanban_status'], "Scan '{$raw}' harus mengembalikan format_tidak_dikenal");
            $this->assertNull($eval['kanban_serial'], "kanban_serial harus null untuk scan '{$raw}'");
            $this->assertNull($eval['master'], "master harus null untuk scan '{$raw}'");
            $this->assertNull($eval['parsed'], "parsed harus null untuk scan '{$raw}'");
        }
    }
}
