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
}
