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
}
