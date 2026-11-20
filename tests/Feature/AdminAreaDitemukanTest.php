<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAreaDitemukanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Http::fake([
            '*/users*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            '*' => \Illuminate\Support\Facades\Http::response([], 200),
        ]);

        // Default seeding area bawaan
        $defaultAreas = ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'];
        foreach ($defaultAreas as $name) {
            AreaDitemukan::updateOrCreate(
                ['name' => $name],
                [
                    'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
                    'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
                ]
            );
        }
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
            'user_name' => 'Operator 1',
            'user_role' => 'User',
        ]);
    }

    public function test_guest_cannot_access_area_ditemukans(): void
    {
        $response = $this->get('/master/area-ditemukans');
        $response->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_area_ditemukans(): void
    {
        $response = $this->actingAsOperator()->get('/master/area-ditemukans');
        $response->assertRedirect(route('operator.home'));
    }

    public function test_admin_can_access_area_ditemukans_page(): void
    {
        $response = $this->actingAsAdmin()->get('/master/area-ditemukans');

        $response->assertStatus(200);
        $response->assertSee('Master Area Ditemukan');
        $response->assertSee('CK');
        $response->assertSee('Internal Defect');
    }

    public function test_tambah_area_dengan_tipe_berhasil(): void
    {
        // 1. Tambah dengan tipe Internal Defect eksplisit
        $response1 = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name'     => 'AREA_BARU_INTERNAL',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response1->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response1->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'name'     => 'AREA_BARU_INTERNAL',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        // 2. Tambah dengan tipe Assembly Defect eksplisit
        $response2 = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name'     => 'AREA_BARU_ASSEMBLY',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);

        $response2->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response2->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'name'     => 'AREA_BARU_ASSEMBLY',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);
    }

    public function test_tambah_area_pre_assy_tanpa_tipe_berhasil(): void
    {
        $response = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name'     => 'AREA_PRE_DEFAULT',
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
        ]);

        $response->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'name'     => 'AREA_PRE_DEFAULT',
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
        ]);
    }

    public function test_tambah_area_final_assy_tanpa_tipe_ditolak(): void
    {
        $response = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name'     => 'AREA_FINAL_NO_TIPE',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
        ]);

        $response->assertSessionHasErrors(['tipe']);
        $this->assertDatabaseMissing('area_ditemukans', [
            'name' => 'AREA_FINAL_NO_TIPE',
        ]);
    }

    public function test_tambah_area_dengan_tipe_tidak_valid_ditolak(): void
    {
        $response = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name'     => 'AREA_INVALID',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['tipe']);
        $this->assertDatabaseMissing('area_ditemukans', [
            'name' => 'AREA_INVALID',
        ]);
    }

    public function test_kategori_rework_ditolak_hanya_final_assy_dan_pre_assy(): void
    {
        $response = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name'     => 'AREA_REWORK_REJECT',
            'kategori' => 'Rework',
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response->assertSessionHasErrors(['kategori']);
        $this->assertDatabaseMissing('area_ditemukans', [
            'name' => 'AREA_REWORK_REJECT',
        ]);
    }

    public function test_pre_assy_selalu_dipaksa_internal_defect(): void
    {
        // Admin mencoba memilih assembly_defect untuk Pre Assy -> dipaksa internal_defect
        $response = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name'     => 'PRE_FORCED_AREA',
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);

        $response->assertRedirect(route('admin.master.area_ditemukans.index'));
        $this->assertDatabaseHas('area_ditemukans', [
            'name'     => 'PRE_FORCED_AREA',
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);
    }

    public function test_edit_tipe_area_berhasil(): void
    {
        $area = AreaDitemukan::create([
            'name'     => 'AREA_EDIT_TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name'     => 'AREA_EDIT_TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);

        $response->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'id'       => $area->id,
            'name'     => 'AREA_EDIT_TEST',
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
        ]);
    }

    public function test_edit_tipe_tidak_valid_ditolak(): void
    {
        $area = AreaDitemukan::create([
            'name'     => 'AREA_EDIT_INVALID',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name'     => 'AREA_EDIT_INVALID',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => 'tipe_palsu',
        ]);

        $response->assertSessionHasErrors(['tipe']);
        $this->assertDatabaseHas('area_ditemukans', [
            'id'   => $area->id,
            'name' => 'AREA_EDIT_INVALID',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);
    }

    public function test_nama_area_yang_dipakai_defects_bisa_diubah_dan_update_defects_kategori_sama(): void
    {
        $area = AreaDitemukan::where('name', 'CK')->firstOrFail();

        $defect = Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'CK',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // 1. Ubah nama area yang sudah dipakai defect -> Harus BERHASIL dan mengupdate defect
        $response1 = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name'     => 'CK_RENAMED',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response1->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response1->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'id'   => $area->id,
            'name' => 'CK_RENAMED',
        ]);
        $this->assertDatabaseHas('defects', [
            'id'             => $defect->id,
            'area_ditemukan' => 'CK_RENAMED',
        ]);

        // 2. Coba ubah nama ke nama yang sudah ada di kategori yang sama ('V1') -> Harus DITOLAK
        $response2 = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name'     => 'V1',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response2->assertSessionHasErrors(['name']);

        // 3. Ubah tipe area (nama tetap 'CK_RENAMED') -> Harus BERHASIL
        $response3 = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name'     => 'CK_RENAMED',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);

        $response3->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response3->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'id'   => $area->id,
            'name' => 'CK_RENAMED',
            'tipe' => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);
    }

    public function test_nama_area_yang_dipakai_defects_tidak_bisa_dihapus(): void
    {
        $area = AreaDitemukan::where('name', 'V1')->firstOrFail();

        Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'V1',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 2,
            'inspect_quantity' => 20,
        ]);

        $response = $this->actingAsAdmin()->delete("/master/area-ditemukans/{$area->id}");

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('area_ditemukans', [
            'id' => $area->id,
        ]);
    }

    public function test_area_yang_belum_dipakai_bisa_dihapus(): void
    {
        $area = AreaDitemukan::create([
            'name'     => 'AREA_UNUSED_TO_DELETE',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response = $this->actingAsAdmin()->delete("/master/area-ditemukans/{$area->id}");

        $response->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('area_ditemukans', [
            'id' => $area->id,
        ]);
    }

    public function test_seeder_area_classification_menghasilkan_6_final_dan_16_pre_assy(): void
    {
        $seeder = new \Database\Seeders\AreaDitemukanClassificationSeeder();
        $seeder->run();

        // 6 Final Assy (CK, V1, V2, FC, WP, RI) bertipe internal_defect
        $this->assertEquals(6, AreaDitemukan::where('kategori', AreaDitemukan::KATEGORI_FINAL_ASSY)->where('tipe', AreaDitemukan::TIPE_INTERNAL_DEFECT)->count());

        // 16 Pre Assy (16 item dari inspect_process_types) bertipe internal_defect
        $this->assertEquals(16, AreaDitemukan::where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)->where('tipe', AreaDitemukan::TIPE_INTERNAL_DEFECT)->count());

        // Area REWORK tidak boleh ada
        $this->assertDatabaseMissing('area_ditemukans', ['name' => 'REWORK']);
    }

    public function test_helper_assembly_names_dan_internal_names_dan_resolve_tipe_by_name(): void
    {
        AreaDitemukan::create([
            'name'     => 'AREA_ASSY_TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);

        $assemblyNames = AreaDitemukan::assemblyNames();
        $this->assertContains('AREA_ASSY_TEST', $assemblyNames);

        $internalNames = AreaDitemukan::internalNames();
        $this->assertContains('CK', $internalNames);
        $this->assertNotContains('AREA_ASSY_TEST', $internalNames);

        // resolveTipeByName
        $this->assertEquals(AreaDitemukan::TIPE_ASSEMBLY_DEFECT, AreaDitemukan::resolveTipeByName('AREA_ASSY_TEST'));
        $this->assertEquals(AreaDitemukan::TIPE_INTERNAL_DEFECT, AreaDitemukan::resolveTipeByName('CK'));
        // NULL atau tidak ditemukan default ke internal_defect
        $this->assertEquals(AreaDitemukan::TIPE_INTERNAL_DEFECT, AreaDitemukan::resolveTipeByName(null));
        $this->assertEquals(AreaDitemukan::TIPE_INTERNAL_DEFECT, AreaDitemukan::resolveTipeByName('AREA_GHAIB_NOT_FOUND'));
    }

    public function test_dropdown_dan_validasi_input_defect_memuat_area_dan_lolos_validasi(): void
    {
        $area = AreaDitemukan::create([
            'name'     => 'AREA_INPUT_TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        // 1. Dropdown input defect memuat area ini
        $response = $this->withSession([
            'logged_in' => true,
            'user_name' => 'Operator QA',
            'user_role' => 'User',
        ])->get(route('input_defect.create', ['type' => 'Final Assy']));

        $response->assertOk();
        $response->assertSee('AREA_INPUT_TEST');

        // 2. Simpan input defect dengan area ini berhasil lolos validasi
        $storeRes = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'Operator QA',
            'user_role'     => 'User',
            'current_shift' => '1A',
        ])->post(route('input_defect.store'), [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => Carbon::today()->toDateString(),
            'jam'              => '10:00',
            'area_ditemukan'   => 'AREA_INPUT_TEST',
            'end_number'       => '1',
            'jenis_defect'     => 'MISSING PART',
            'sub_defect'       => 'LOCK',
            'jumlah'           => 1,
            'inspect_quantity' => 10,
        ]);

        $storeRes->assertRedirect(route('operator.home'));
        $storeRes->assertSessionHas('success');
        $this->assertDatabaseHas('defects', [
            'area_ditemukan' => 'AREA_INPUT_TEST',
            'jenis_defect'   => 'MISSING PART',
        ]);
    }

    public function test_master_area_view_menampilkan_badge_dan_opsi_kategori_dan_tipe(): void
    {
        AreaDitemukan::create([
            'name'     => 'PRE_VIEW_TEST',
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        AreaDitemukan::create([
            'name'     => 'FA_ASSY_VIEW_TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);

        $res = $this->actingAsAdmin()->get('/master/area-ditemukans');
        $res->assertOk();
        $res->assertSee('Pre Assy');
        $res->assertSee('Final Assy');
        $res->assertSee('Internal Defect');
        $res->assertSee('Assembly Defect');
        $res->assertDontSee('Kategori Report');
    }

    public function test_area_pre_assy_menampilkan_strip_dan_bukan_badge_internal_atau_assembly(): void
    {
        AreaDitemukan::create([
            'name'     => 'PRE_STRIP_TEST',
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $res = $this->actingAsAdmin()->get('/master/area-ditemukans');
        $res->assertOk();
        $content = $res->getContent();

        // Pastikan baris PRE_STRIP_TEST memiliki kolom "-" untuk tipe
        $this->assertMatchesRegularExpression('/PRE_STRIP_TEST.*?>\s*-\s*<\/span>/s', $content);
    }
}
