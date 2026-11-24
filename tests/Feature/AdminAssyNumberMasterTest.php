<?php

namespace Tests\Feature;

use App\Models\AssyNumber;
use App\Models\Carline;
use App\Models\CarType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Once;
use Tests\TestCase;

class AdminAssyNumberMasterTest extends TestCase
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

    private function actingAsRework()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Rework Staff',
            'user_role' => 'Rework',
            'current_shift' => '1A',
        ]);
    }

    public function test_admin_can_access_assy_numbers_index_and_see_data()
    {
        $carType = CarType::create(['name' => 'D26']);
        $carline = Carline::create(['name' => '664W-C5', 'car_type_id' => $carType->id]);

        AssyNumber::create([
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
            'keterangan'  => 'Main Harness RHD',
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index'));

        $response->assertStatus(200);
        $response->assertSee('Master Assy Number');
        $response->assertSee('82111-VU020');
        $response->assertSee('664W-C5');
        $response->assertSee('Main Harness RHD');
    }

    public function test_admin_can_search_and_filter_assy_numbers()
    {
        $carType = CarType::create(['name' => 'D26']);
        $carlineA = Carline::create(['name' => '664W-C5', 'car_type_id' => $carType->id]);
        $carlineB = Carline::create(['name' => 'AT11', 'car_type_id' => $carType->id]);

        AssyNumber::create([
            'assy_number' => '82111-VU020',
            'carline_id'  => $carlineA->id,
            'keterangan'  => 'Main Harness',
        ]);

        AssyNumber::create([
            'assy_number' => '82115-VU010',
            'carline_id'  => $carlineB->id,
            'keterangan'  => 'Engine Room Harness',
        ]);

        // Filter by carline_id
        $filterResponse = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index', [
            'carline_id' => $carlineA->id,
        ]));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('82111-VU020');
        $filterResponse->assertDontSee('82115-VU010');

        // Search by normalized assy without hyphen
        $searchResponse = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index', [
            'search' => '82111VU020',
        ]));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('82111-VU020');
        $searchResponse->assertDontSee('82115-VU010');
    }

    public function test_admin_can_manually_create_assy_number()
    {
        $carType = CarType::create(['name' => 'D26']);
        $carline = Carline::create(['name' => '664W-C5', 'car_type_id' => $carType->id]);

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.store'), [
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
            'keterangan'  => 'Floor Harness',
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $this->assertDatabaseHas('assy_numbers', [
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
            'keterangan'  => 'Floor Harness',
        ]);
    }

    public function test_admin_cannot_create_duplicate_assy_number()
    {
        AssyNumber::create([
            'assy_number' => '82111-VU020',
        ]);

        // Try creating with normalized equivalent "82111VU020"
        $response = $this->actingAsAdmin()->from(route('admin.master.assy_numbers.index'))->post(route('admin.master.assy_numbers.store'), [
            'assy_number' => '82111VU020',
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $response->assertSessionHasErrors(['assy_number']);
        $this->assertEquals(1, AssyNumber::count());
    }

    public function test_admin_can_manually_update_assy_number()
    {
        $carType = CarType::create(['name' => 'D26']);
        $carline = Carline::create(['name' => '664W-C5', 'car_type_id' => $carType->id]);

        $assy = AssyNumber::create([
            'assy_number' => '82111-VU020',
            'keterangan'  => 'Old Keterangan',
        ]);

        $response = $this->actingAsAdmin()->put(route('admin.master.assy_numbers.update', $assy->id), [
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
            'keterangan'  => 'Updated Keterangan',
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $this->assertDatabaseHas('assy_numbers', [
            'id'          => $assy->id,
            'carline_id'  => $carline->id,
            'keterangan'  => 'Updated Keterangan',
        ]);
    }

    public function test_admin_can_manually_delete_assy_number()
    {
        $assy = AssyNumber::create([
            'assy_number' => '82111-VU020',
        ]);

        $response = $this->actingAsAdmin()->delete(route('admin.master.assy_numbers.destroy', $assy->id));

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $this->assertDatabaseMissing('assy_numbers', [
            'id' => $assy->id,
        ]);
    }

    public function test_operator_and_rework_roles_are_forbidden_from_master_assy_routes()
    {
        // Web requests redirect to role-specific home according to EnsureIsAdmin
        $opResp = $this->actingAsOperator()->get(route('admin.master.assy_numbers.index'));
        $opResp->assertRedirect(route('operator.home'));

        $rwResp = $this->actingAsRework()->get(route('admin.master.assy_numbers.index'));
        $rwResp->assertRedirect(route('rework.index'));

        // DELETE requests return 403
        $delRespOp = $this->actingAsOperator()->delete(route('admin.master.assy_numbers.destroy', 1));
        $delRespOp->assertStatus(403);

        $delRespRw = $this->actingAsRework()->delete(route('admin.master.assy_numbers.destroy', 1));
        $delRespRw->assertStatus(403);

        // JSON requests return 403
        $jsonRespOp = $this->actingAsOperator()->getJson(route('admin.master.assy_numbers.index'));
        $jsonRespOp->assertStatus(403);

        $jsonRespRw = $this->actingAsRework()->getJson(route('admin.master.assy_numbers.index'));
        $jsonRespRw->assertStatus(403);
    }

    public function test_import_with_fixture_file_handles_upsert_duplicates_and_unknown_carline()
    {
        $carType = CarType::create(['name' => 'D26']);
        $carline664W = Carline::create(['name' => '664W-C5', 'car_type_id' => $carType->id]);
        $carlineAT11 = Carline::create(['name' => 'AT11', 'car_type_id' => $carType->id]);

        $fixturePath = base_path('tests/Fixtures/assy_numbers_test.csv');
        $this->assertFileExists($fixturePath);

        $uploadedFile = new UploadedFile(
            $fixturePath,
            'assy_numbers_test.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertEquals(4, $summary['success'], 'Should successfully process 4 rows (including upsert duplicate)');
        $this->assertEquals(2, $summary['skipped'], 'Should skip 2 rows (unknown carline and empty assy)');

        // Check error reasons
        $this->assertCount(2, $summary['errors']);
        $this->assertStringContainsString('tidak ditemukan di master carline', $summary['errors'][0]['reason']);
        $this->assertStringContainsString('Nomor assy kosong', $summary['errors'][1]['reason']);

        // Check database state
        // 1. 82111VU020 was upserted from 82111-VU020, final keterangan should be 'Updated Main Harness'
        $upsertedAssy = AssyNumber::where('assy_number', '82111VU020')
            ->orWhere('assy_number', '82111-VU020')
            ->first();
        $this->assertNotNull($upsertedAssy);
        $this->assertEquals($carline664W->id, $upsertedAssy->carline_id);
        $this->assertEquals('Updated Main Harness', $upsertedAssy->keterangan);

        // 2. 82115-VU010 belongs to AT11
        $assy2 = AssyNumber::where('assy_number', '82115-VU010')->first();
        $this->assertNotNull($assy2);
        $this->assertEquals($carlineAT11->id, $assy2->carline_id);
        $this->assertEquals('Engine Room Harness', $assy2->keterangan);

        // 3. 82113-VU040 has no carline
        $assy3 = AssyNumber::where('assy_number', '82113-VU040')->first();
        $this->assertNotNull($assy3);
        $this->assertNull($assy3->carline_id);
        $this->assertEquals('Harness tanpa carline', $assy3->keterangan);

        // 4. Unknown line assy (82112-VU030) was skipped and NOT in DB
        $this->assertDatabaseMissing('assy_numbers', [
            'assy_number' => '82112-VU030',
        ]);

        // Total unique assy in DB should be 3
        $this->assertEquals(3, AssyNumber::count());
    }

    public function test_import_with_corrupted_file_returns_error_message_instead_of_500()
    {
        // Upload a corrupted file with .xlsx extension containing non-zip content
        $corruptedFile = UploadedFile::fake()->createWithContent(
            'corrupted_data.xlsx',
            "NOT_A_VALID_ZIP_OR_EXCEL_HEADER\x00\x01\x02\x03\xFF\xFF"
        );

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $corruptedFile,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $response->assertSessionHasErrors(['file']);

        $errors = session('errors')->get('file');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Gagal memproses file impor', $errors[0]);
    }

    public function test_import_csv_with_trailing_empty_rows_ignores_empty_used_range()
    {
        $carType = CarType::create(['name' => 'D26']);
        $carline = Carline::create(['name' => '664W-C5', 'car_type_id' => $carType->id]);

        $csvWithTrailingEmpty = implode("\n", [
            'assy_number,carline,keterangan',
            '82111-VU020,664W-C5,Valid Main Harness',
            ',,',
            '   ,  ,  ',
            '',
            '',
        ]);

        $file = UploadedFile::fake()->createWithContent('assy_with_empty_used_range.csv', $csvWithTrailingEmpty);

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        // Only 1 real row should be processed, empty used range lines ignored
        $this->assertEquals(1, $summary['success']);
        $this->assertEquals(0, $summary['skipped']);
        $this->assertEquals(1, AssyNumber::count());
    }

    public function test_admin_can_save_and_update_family_and_conveyor_manually()
    {
        $carType = CarType::create(['name' => 'D26']);
        $carline = Carline::create(['name' => '664W-C5', 'car_type_id' => $carType->id]);

        // 1. Create with family and conveyor
        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.store'), [
            'assy_number' => '82111-FAM01',
            'carline_id'  => $carline->id,
            'family'      => 'D26D',
            'conveyor'    => 'CV-01',
            'keterangan'  => 'Test Family Harness',
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $this->assertDatabaseHas('assy_numbers', [
            'assy_number' => '82111-FAM01',
            'family'      => 'D26D',
            'conveyor'    => 'CV-01',
        ]);

        $assy = AssyNumber::where('assy_number', '82111-FAM01')->first();
        $this->assertNotNull($assy);

        // 2. Update family and conveyor
        $updateResp = $this->actingAsAdmin()->put(route('admin.master.assy_numbers.update', $assy->id), [
            'assy_number' => '82111-FAM01',
            'carline_id'  => $carline->id,
            'family'      => 'D26E',
            'conveyor'    => 'CV-02',
            'keterangan'  => 'Updated Family Harness',
        ]);

        $updateResp->assertRedirect(route('admin.master.assy_numbers.index'));
        $this->assertDatabaseHas('assy_numbers', [
            'id'       => $assy->id,
            'family'   => 'D26E',
            'conveyor' => 'CV-02',
        ]);
    }

    public function test_admin_can_search_by_family_and_conveyor()
    {
        AssyNumber::create([
            'assy_number' => '82111-SRCH1',
            'family'      => 'FAM-ALPHA',
            'conveyor'    => 'CV-NINE',
            'keterangan'  => 'Item A',
        ]);

        AssyNumber::create([
            'assy_number' => '82111-SRCH2',
            'family'      => 'FAM-BETA',
            'conveyor'    => 'CV-TEN',
            'keterangan'  => 'Item B',
        ]);

        // Search by family (case-insensitive)
        $respFamily = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index', ['search' => 'fam-alpha']));
        $respFamily->assertStatus(200);
        $respFamily->assertSee('82111-SRCH1');
        $respFamily->assertDontSee('82111-SRCH2');

        // Search by conveyor (case-insensitive)
        $respConveyor = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index', ['search' => 'cv-ten']));
        $respConveyor->assertStatus(200);
        $respConveyor->assertSee('82111-SRCH2');
        $respConveyor->assertDontSee('82111-SRCH1');
    }

    public function test_import_csv_with_family_and_conveyor_saves_successfully()
    {
        $csvContent = implode("\n", [
            'assy_number,family,conveyor,keterangan',
            '82111-FC01,D26D,CV-01,Harness Standard',
            '82111-FC02,D26E,CV-02,Harness Konveyor',
        ]);

        $file = UploadedFile::fake()->createWithContent('family_conveyor.csv', $csvContent);

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $summary = session('import_summary');
        $this->assertEquals(2, $summary['success']);

        $this->assertDatabaseHas('assy_numbers', [
            'assy_number' => '82111-FC01',
            'family'      => 'D26D',
            'conveyor'    => 'CV-01',
        ]);
        $this->assertDatabaseHas('assy_numbers', [
            'assy_number' => '82111-FC02',
            'family'      => 'D26E',
            'conveyor'    => 'CV-02',
        ]);
    }

    public function test_import_with_empty_family_conveyor_does_not_overwrite_existing_values()
    {
        // 1. Initial record with existing family & conveyor
        AssyNumber::create([
            'assy_number' => '82111-PRESERVE',
            'family'      => 'ORIGINAL-FAM',
            'conveyor'    => 'ORIGINAL-CV',
            'keterangan'  => 'Original Keterangan',
        ]);

        // 2. CSV import with same assy_number but empty family & conveyor
        $csvContent = implode("\n", [
            'assy_number,family,conveyor,keterangan',
            '82111-PRESERVE,,,Updated Only Keterangan',
        ]);

        $file = UploadedFile::fake()->createWithContent('preserve_test.csv', $csvContent);

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));

        // Values should NOT be overwritten with null/empty
        $updated = AssyNumber::where('assy_number', '82111-PRESERVE')->first();
        $this->assertNotNull($updated);
        $this->assertEquals('ORIGINAL-FAM', $updated->family, 'Family must not be overwritten by empty value in CSV');
        $this->assertEquals('ORIGINAL-CV', $updated->conveyor, 'Conveyor must not be overwritten by empty value in CSV');
        $this->assertEquals('Updated Only Keterangan', $updated->keterangan);
    }

    public function test_existing_legacy_assy_numbers_render_gracefully_without_family_or_conveyor()
    {
        AssyNumber::create([
            'assy_number' => '82111-LEGACY',
            'family'      => null,
            'conveyor'    => null,
            'keterangan'  => 'Data Lama Tanpa Family',
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index'));
        $response->assertStatus(200);
        $response->assertSee('82111-LEGACY');
        $response->assertSee('Data Lama Tanpa Family');
        // Dash indicates null family/conveyor rendered gracefully
        $response->assertSee('—');
    }

    public function test_master_page_displays_updated_column_labels(): void
    {
        $carType = CarType::create(['name' => 'D26D']);
        $carlineWithCarType = Carline::create(['name' => 'CARLINE-A', 'car_type_id' => $carType->id]);
        $carlineWithoutCarType = Carline::create(['name' => 'CARLINE-B', 'car_type_id' => null]);

        $response = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index'));
        $response->assertStatus(200);

        // Header tabel memuat "Assy", "Kode Conveyor", dan "Carline / Conveyor"
        $response->assertSee('<th class="py-3 px-4">Assy</th>', false);
        $response->assertSee('<th class="py-3 px-4">Kode Conveyor</th>', false);
        $response->assertSee('<th class="py-3 px-4">Carline / Conveyor</th>', false);
        $response->assertDontSee('<th class="py-3 px-4">Nomor Assy</th>', false);

        // Filter dropdown opsi memuat "Semua Carline / Conveyor"
        $response->assertSee('<option value="">Semua Carline / Conveyor</option>', false);

        // Form modal tambah & edit memuat label "Carline / Conveyor" dan "Kode Conveyor"
        $response->assertSee('Carline / Conveyor');
        $response->assertSee('Kode Conveyor');
        $response->assertSee('-- Pilih Carline / Conveyor (Opsional) --');

        // Modal tambah assy placeholder dan hint
        $response->assertSee('placeholder="Contoh: KCLL"', false);
        $response->assertSee('Isi kode assy saja (contoh: KCLL). Family diisi di kolom Family.');

        // Format dropdown options: CARLINE-A (D26D) dan CARLINE-B tanpa tanda kurung
        $response->assertSee('CARLINE-A (D26D)');
        $response->assertSee('CARLINE-B');
        $response->assertDontSee('CARLINE-B ()');
    }

    public function test_jenis_mobil_display_resolved_from_conveyor_token_only(): void
    {
        $carTypeMazda = CarType::create(['name' => 'MAZDA']);
        $carTypeToyota = CarType::create(['name' => 'TOYOTA']);

        // Carlines dengan token AB1 (keduanya MAZDA)
        Carline::create(['name' => 'J30A-AB1.INPANEL LHD', 'car_type_id' => $carTypeMazda->id]);
        Carline::create(['name' => 'J30A-AB1.INPANEL RHD', 'car_type_id' => $carTypeMazda->id]);

        // Carlines dengan token MIX1 (campuran TOYOTA dan MAZDA)
        Carline::create(['name' => 'LINE-MIX1', 'car_type_id' => $carTypeToyota->id]);
        Carline::create(['name' => 'LINE-MIX1.RHD', 'car_type_id' => $carTypeMazda->id]);

        // 1. Satu car_type: conveyor AB1 -> Jenis Mobil MAZDA (conveyor)
        $assySingle = AssyNumber::create([
            'assy_number' => 'KCLL-SINGLE',
            'family'      => '67030',
            'conveyor'    => 'AB1',
            'carline_id'  => null,
        ]);

        $resSingle = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index'));
        $resSingle->assertOk();
        $resSingle->assertSee('MAZDA');
        $resSingle->assertSee('(conveyor)');
        // carline_id tetap null di DB
        $this->assertNull($assySingle->fresh()->carline_id);

        // 2. Token AB10 tidak boleh cocok dengan AB1
        $assyTokenMismatch = AssyNumber::create([
            'assy_number' => 'KCLN-AB10',
            'conveyor'    => 'AB10',
            'carline_id'  => null,
        ]);
        $infoTokenMismatch = $assyTokenMismatch->resolveJenisMobil();
        $this->assertNull($infoTokenMismatch);

        // 3. Campuran car_type: conveyor MIX1 -> null
        $assyMixed = AssyNumber::create([
            'assy_number' => 'KMIX-MULTI',
            'conveyor'    => 'MIX1',
            'carline_id'  => null,
        ]);
        $infoMixed = $assyMixed->resolveJenisMobil();
        $this->assertNull($infoMixed);

        // 4. Tanpa kecocokan: conveyor NONE99 -> null
        $assyNoMatch = AssyNumber::create([
            'assy_number' => 'KNONE-001',
            'conveyor'    => 'NONE99',
            'carline_id'  => null,
        ]);
        $infoNoMatch = $assyNoMatch->resolveJenisMobil();
        $this->assertNull($infoNoMatch);
    }

    public function test_importer_with_capitalized_headers_does_not_misread_family_as_carline(): void
    {
        // CSV dengan header kapital: Assy, Family, Conveyor
        $csvContent = "Assy,Family,Conveyor\n"
            . "KCLL,67030,AB1\n"
            . "KCLT,8211,C7\n";

        $file = UploadedFile::fake()->createWithContent('assy_capital_headers.csv', $csvContent);

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertSame(2, $summary['success']);
        $this->assertSame(0, $summary['skipped']);

        // Pastikan KCLL tersimpan dengan family 67030 dan conveyor AB1
        $kcll = AssyNumber::where('assy_number', 'KCLL')->first();
        $this->assertNotNull($kcll);
        $this->assertSame('67030', $kcll->family);
        $this->assertSame('AB1', $kcll->conveyor);
        $this->assertNull($kcll->carline_id); // Family TIDAK dijadikan carline

        // Pastikan KCLT tersimpan dengan family 8211 dan conveyor C7
        $kclt = AssyNumber::where('assy_number', 'KCLT')->first();
        $this->assertNotNull($kclt);
        $this->assertSame('8211', $kclt->family);
        $this->assertSame('C7', $kclt->conveyor);
        $this->assertNull($kclt->carline_id);
    }

    public function test_import_four_columns_stores_car_type_id(): void
    {
        $toyota = CarType::create(['name' => 'TOYOTA']);
        $mazda  = CarType::create(['name' => 'MAZDA']);
        $honda  = CarType::create(['name' => 'HONDA']);

        $fixturePath = base_path('tests/Fixtures/assy_numbers_4_columns_test.csv');
        $this->assertFileExists($fixturePath);

        $uploadedFile = new UploadedFile(
            $fixturePath,
            'assy_numbers_4_columns_test.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertSame(3, $summary['success']);
        $this->assertSame(0, $summary['skipped']);

        $assy1 = AssyNumber::where('assy_number', '82111-4COL1')->first();
        $this->assertNotNull($assy1);
        $this->assertSame('FAM-A', $assy1->family);
        $this->assertSame('CV-A', $assy1->conveyor);
        $this->assertSame($toyota->id, $assy1->car_type_id);

        $assy2 = AssyNumber::where('assy_number', '82111-4COL2')->first();
        $this->assertNotNull($assy2);
        $this->assertSame('FAM-B', $assy2->family);
        $this->assertSame('CV-B', $assy2->conveyor);
        $this->assertSame($mazda->id, $assy2->car_type_id);

        $assy3 = AssyNumber::where('assy_number', '82111-4COL3')->first();
        $this->assertNotNull($assy3);
        $this->assertSame('FAM-C', $assy3->family);
        $this->assertSame('CV-C', $assy3->conveyor);
        $this->assertSame($honda->id, $assy3->car_type_id);
    }

    public function test_import_unknown_car_type_is_reported_and_does_not_create_new_record(): void
    {
        CarType::create(['name' => 'TOYOTA']);

        $csvContent = "assy,family,conveyor,jenis_mobil\n"
            . "82111-UNKNOWN-CT,FAM-X,CV-X,FERRARI_F40\n";

        $file = UploadedFile::fake()->createWithContent('unknown_car_type.csv', $csvContent);

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));
        $response->assertSessionHas('import_summary');

        $summary = session('import_summary');
        $this->assertSame(0, $summary['success']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertCount(1, $summary['errors']);
        $this->assertSame(2, $summary['errors'][0]['row']);
        $this->assertSame('82111-UNKNOWN-CT', $summary['errors'][0]['assy_number']);
        $this->assertStringContainsString('FERRARI_F40', $summary['errors'][0]['reason']);
        $this->assertStringContainsString('tidak ditemukan', $summary['errors'][0]['reason']);

        // Pastikan TIDAK membuat jenis mobil baru
        $this->assertFalse(CarType::where('name', 'FERRARI_F40')->exists());

        // Pastikan nomor assy TIDAK disimpan di database
        $this->assertDatabaseMissing('assy_numbers', [
            'assy_number' => '82111-UNKNOWN-CT',
        ]);
    }

    public function test_import_empty_car_type_cell_does_not_overwrite_existing_car_type_id(): void
    {
        $toyota = CarType::create(['name' => 'TOYOTA']);

        $existing = AssyNumber::create([
            'assy_number' => '82111-PRESERVE-CT',
            'family'      => 'ORIG-FAM',
            'conveyor'    => 'ORIG-CV',
            'car_type_id' => $toyota->id,
            'keterangan'  => 'Old note',
        ]);

        $csvContent = "assy,family,conveyor,jenis_mobil\n"
            . "82111-PRESERVE-CT,NEW-FAM,NEW-CV,\n";

        $file = UploadedFile::fake()->createWithContent('preserve_car_type.csv', $csvContent);

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));

        $fresh = $existing->fresh();
        $this->assertSame($toyota->id, $fresh->car_type_id, 'car_type_id tidak boleh ditimpa menjadi null jika sel kosong');
        $this->assertSame('NEW-FAM', $fresh->family);
        $this->assertSame('NEW-CV', $fresh->conveyor);
    }

    public function test_import_warning_when_excel_differs_from_conveyor_guess_but_keeps_excel_value(): void
    {
        $mazda  = CarType::create(['name' => 'MAZDA']);
        $toyota = CarType::create(['name' => 'TOYOTA']);

        // Carline dengan token CVCONV menghasilkan tebakan MAZDA
        Carline::create(['name' => 'LINE-CVCONV', 'car_type_id' => $mazda->id]);

        $csvContent = "assy,family,conveyor,jenis_mobil\n"
            . "82111-DIFF-GUESS,FAM1,CVCONV,TOYOTA\n";

        $file = UploadedFile::fake()->createWithContent('diff_guess.csv', $csvContent);

        $response = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.assy_numbers.index'));

        $summary = session('import_summary');
        $this->assertSame(1, $summary['success']);
        $this->assertSame(0, $summary['skipped']);
        $this->assertNotEmpty($summary['warnings']);
        $this->assertStringContainsString('TOYOTA', $summary['warnings'][0]['message']);
        $this->assertStringContainsString('MAZDA', $summary['warnings'][0]['message']);

        // Nilai Excel (TOYOTA) tetap disimpan
        $saved = AssyNumber::where('assy_number', '82111-DIFF-GUESS')->first();
        $this->assertNotNull($saved);
        $this->assertSame($toyota->id, $saved->car_type_id);
    }

    public function test_jenis_mobil_display_priority_order(): void
    {
        $typeDirect   = CarType::create(['name' => 'PRIO-DIRECT']);
        $typeCarline  = CarType::create(['name' => 'PRIO-CARLINE']);
        $typeConveyor = CarType::create(['name' => 'PRIO-CONVEYOR']);

        $carlineWithCarType = Carline::create([
            'name'        => 'LINE-PRIO',
            'car_type_id' => $typeCarline->id,
        ]);

        // Carline dengan token CONVTOKEN menuju typeConveyor
        Carline::create([
            'name'        => 'LINE-CONVTOKEN',
            'car_type_id' => $typeConveyor->id,
        ]);

        // 1. Prioritas 1: car_type_id langsung > carline.carType > conveyor
        $assyLevel1 = AssyNumber::create([
            'assy_number' => 'PRIO-LVL1',
            'car_type_id' => $typeDirect->id,
            'carline_id'  => $carlineWithCarType->id,
            'conveyor'    => 'CONVTOKEN',
        ]);
        $resolved1 = $assyLevel1->resolveJenisMobil();
        $this->assertSame('PRIO-DIRECT', $resolved1['name']);
        $this->assertFalse($resolved1['from_conveyor']);

        // 2. Prioritas 2: car_type_id null, carline.carType > conveyor
        $assyLevel2 = AssyNumber::create([
            'assy_number' => 'PRIO-LVL2',
            'car_type_id' => null,
            'carline_id'  => $carlineWithCarType->id,
            'conveyor'    => 'CONVTOKEN',
        ]);
        $resolved2 = $assyLevel2->resolveJenisMobil();
        $this->assertSame('PRIO-CARLINE', $resolved2['name']);
        $this->assertFalse($resolved2['from_conveyor']);

        // 3. Prioritas 3: car_type_id null, carline_id null, conveyor guess
        $assyLevel3 = AssyNumber::create([
            'assy_number' => 'PRIO-LVL3',
            'car_type_id' => null,
            'carline_id'  => null,
            'conveyor'    => 'CONVTOKEN',
        ]);
        $resolved3 = $assyLevel3->resolveJenisMobil();
        $this->assertSame('PRIO-CONVEYOR', $resolved3['name']);
        $this->assertTrue($resolved3['from_conveyor']);

        // 4. Fallback: semua null -> null
        $assyLevel4 = AssyNumber::create([
            'assy_number' => 'PRIO-LVL4',
            'car_type_id' => null,
            'carline_id'  => null,
            'conveyor'    => null,
        ]);
        $this->assertNull($assyLevel4->resolveJenisMobil());

        // Verifikasi tampilan tabel di web
        $res = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index'));
        $res->assertOk();
        $res->assertSee('PRIO-DIRECT');
        $res->assertSee('PRIO-CARLINE');
        $res->assertSee('PRIO-CONVEYOR');
        // Pastikan hanya conveyor yang memakai tanda (conveyor)
        $res->assertSee('(conveyor)');
    }

    public function test_importer_recognizes_capitalized_and_header_variants_for_car_type(): void
    {
        $honda = CarType::create(['name' => 'HONDA']);

        $headerVariants = [
            'JENIS MOBIL',
            'Jenis Mobil',
            'jenis_mobil',
            'CAR TYPE',
            'car type',
            'car_type',
        ];

        foreach ($headerVariants as $idx => $header) {
            $assyNum = "ASSY-HDR-VAR-{$idx}";
            $csv = "assy,family,conveyor,{$header}\n"
                . "{$assyNum},FAM,CV,HONDA\n";

            $file = UploadedFile::fake()->createWithContent("header_var_{$idx}.csv", $csv);

            $res = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.import'), [
                'file' => $file,
            ]);
            $res->assertRedirect(route('admin.master.assy_numbers.index'));

            $assy = AssyNumber::where('assy_number', $assyNum)->first();
            $this->assertNotNull($assy, "Assy {$assyNum} harus tersimpan dengan header variant '{$header}'");
            $this->assertSame($honda->id, $assy->car_type_id, "car_type_id harus cocok untuk header '{$header}'");
        }
    }

    public function test_manual_crud_for_car_type_in_assy_numbers(): void
    {
        $suzuki   = CarType::create(['name' => 'SUZUKI']);
        $daihatsu = CarType::create(['name' => 'DAIHATSU']);

        // 1. Tampilan view memuat dropdown Jenis Mobil berurutan berdasarkan nama
        $resView = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index'));
        $resView->assertOk();
        $resView->assertSee('Jenis Mobil');
        $resView->assertSee('DAIHATSU');
        $resView->assertSee('SUZUKI');

        // 2. Tambah assy number dengan car_type_id
        $storeRes = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.store'), [
            'assy_number' => '82111-MANUAL-01',
            'car_type_id' => $suzuki->id,
            'family'      => 'FAM-SZ',
            'conveyor'    => 'CV-SZ',
            'keterangan'  => 'Created via manual form',
        ]);
        $storeRes->assertRedirect(route('admin.master.assy_numbers.index'));
        $storeRes->assertSessionHas('success');

        $assy = AssyNumber::where('assy_number', '82111-MANUAL-01')->first();
        $this->assertNotNull($assy);
        $this->assertSame($suzuki->id, $assy->car_type_id);

        // 3. Validasi gagal jika car_type_id tidak valid
        $invalidRes = $this->actingAsAdmin()->post(route('admin.master.assy_numbers.store'), [
            'assy_number' => '82111-INVALID-01',
            'car_type_id' => 999999,
        ]);
        $invalidRes->assertSessionHasErrors('car_type_id');

        // 4. Update assy number mengganti car_type_id ke DAIHATSU
        $updateRes = $this->actingAsAdmin()->put(route('admin.master.assy_numbers.update', $assy->id), [
            'assy_number' => '82111-MANUAL-01',
            'car_type_id' => $daihatsu->id,
            'family'      => 'FAM-DH',
            'conveyor'    => 'CV-DH',
            'keterangan'  => 'Updated to Daihatsu',
        ]);
        $updateRes->assertRedirect(route('admin.master.assy_numbers.index'));
        $updateRes->assertSessionHas('success');

        $assy->refresh();
        $this->assertSame($daihatsu->id, $assy->car_type_id);
        $this->assertSame('Updated to Daihatsu', $assy->keterangan);

        // 5. Update assy number menghapus car_type_id (menjadi null)
        $clearRes = $this->actingAsAdmin()->put(route('admin.master.assy_numbers.update', $assy->id), [
            'assy_number' => '82111-MANUAL-01',
            'car_type_id' => '',
        ]);
        $clearRes->assertRedirect(route('admin.master.assy_numbers.index'));
        $this->assertNull($assy->fresh()->car_type_id);

        // 6. Delete assy number
        $delRes = $this->actingAsAdmin()->delete(route('admin.master.assy_numbers.destroy', $assy->id));
        $delRes->assertRedirect(route('admin.master.assy_numbers.index'));
        $this->assertDatabaseMissing('assy_numbers', ['id' => $assy->id]);
    }

    public function test_master_assy_index_eager_loads_car_type_without_n_plus_one(): void
    {
        $types = [];
        for ($i = 1; $i <= 5; $i++) {
            $types[] = CarType::create(['name' => "CAR-TYPE-{$i}"]);
        }

        for ($i = 1; $i <= 10; $i++) {
            AssyNumber::create([
                'assy_number' => "NPLUS-{$i}",
                'car_type_id' => $types[$i % 5]->id,
            ]);
        }

        \Illuminate\Support\Facades\DB::enableQueryLog();
        \Illuminate\Support\Facades\DB::flushQueryLog();

        $res = $this->actingAsAdmin()->get(route('admin.master.assy_numbers.index'));
        $res->assertOk();

        $queries = collect(\Illuminate\Support\Facades\DB::getQueryLog());
        // Verify car_types query is not called once per assy row (N+1)
        $carTypesPerRecordQueries = $queries->filter(function ($q) {
            return str_contains($q['query'], 'car_types') && (str_contains($q['query'], '`id` = ?') || str_contains($q['query'], '"id" = ?'));
        });
        $this->assertCount(0, $carTypesPerRecordQueries, 'carType must be eager loaded without N+1 single-id queries');
    }
}
