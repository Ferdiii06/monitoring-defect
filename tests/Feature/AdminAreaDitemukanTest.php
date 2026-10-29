<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AreaDitemukan;
use App\Models\Defect;
use Carbon\Carbon;

class AdminAreaDitemukanTest extends TestCase
{
    use RefreshDatabase;

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
            'name' => 'AREA_BARU_INTERNAL',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response1->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response1->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'name' => 'AREA_BARU_INTERNAL',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        // 2. Tambah dengan tipe Rework eksplisit
        $response2 = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name' => 'AREA_BARU_REWORK',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $response2->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response2->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'name' => 'AREA_BARU_REWORK',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);
    }

    public function test_tambah_area_tanpa_tipe_otomatis_internal_defect(): void
    {
        $response = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name' => 'AREA_BARU_DEFAULT',
        ]);

        $response->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'name' => 'AREA_BARU_DEFAULT',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);
    }

    public function test_tambah_area_dengan_tipe_tidak_valid_ditolak(): void
    {
        $response = $this->actingAsAdmin()->post('/master/area-ditemukans', [
            'name' => 'AREA_INVALID',
            'tipe' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['tipe']);
        $this->assertDatabaseMissing('area_ditemukans', [
            'name' => 'AREA_INVALID',
        ]);
    }

    public function test_edit_tipe_area_berhasil(): void
    {
        $area = AreaDitemukan::create([
            'name' => 'AREA_EDIT_TEST',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name' => 'AREA_EDIT_TEST',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $response->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'id'   => $area->id,
            'name' => 'AREA_EDIT_TEST',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);
    }

    public function test_edit_tipe_tidak_valid_ditolak(): void
    {
        $area = AreaDitemukan::create([
            'name' => 'AREA_EDIT_INVALID',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name' => 'AREA_EDIT_INVALID',
            'tipe' => 'tipe_palsu',
        ]);

        $response->assertSessionHasErrors(['tipe']);
        $this->assertDatabaseHas('area_ditemukans', [
            'id'   => $area->id,
            'name' => 'AREA_EDIT_INVALID',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);
    }

    public function test_nama_area_yang_dipakai_defects_tidak_bisa_diubah_namanya_tetapi_tipe_bisa_diubah(): void
    {
        $area = AreaDitemukan::where('name', 'CK')->firstOrFail();

        Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'CK',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // 1. Coba ubah nama area yang sudah dipakai defect -> Harus DITOLAK di server
        $response1 = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name' => 'CK_RENAMED',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response1->assertSessionHasErrors(['name']);
        $this->assertDatabaseMissing('area_ditemukans', [
            'id'   => $area->id,
            'name' => 'CK_RENAMED',
        ]);
        $this->assertDatabaseHas('area_ditemukans', [
            'id'   => $area->id,
            'name' => 'CK',
        ]);

        // 2. Ubah tipe area saja (nama tetap 'CK') -> Harus BERHASIL
        $response2 = $this->actingAsAdmin()->put("/master/area-ditemukans/{$area->id}", [
            'name' => 'CK',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $response2->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response2->assertSessionHas('success');

        $this->assertDatabaseHas('area_ditemukans', [
            'id'   => $area->id,
            'name' => 'CK',
            'tipe' => AreaDitemukan::TIPE_REWORK,
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
            'name' => 'AREA_UNUSED_TO_DELETE',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $response = $this->actingAsAdmin()->delete("/master/area-ditemukans/{$area->id}");

        $response->assertRedirect(route('admin.master.area_ditemukans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('area_ditemukans', [
            'id' => $area->id,
        ]);
    }

    public function test_seeder_area_rework_idempotent_dan_get_rework_area_names_tidak_kosong(): void
    {
        // Pastikan awalnya belum ada area bertipe rework
        $this->assertEquals(0, AreaDitemukan::where('tipe', AreaDitemukan::TIPE_REWORK)->count());

        // Jalankan seeder pertama kali
        $this->seed(\Database\Seeders\ReworkAreaSeeder::class);

        // Verifikasi area 'REWORK' tersimpan dengan tipe rework
        $this->assertDatabaseHas('area_ditemukans', [
            'name' => 'REWORK',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);
        $this->assertEquals(1, AreaDitemukan::where('tipe', AreaDitemukan::TIPE_REWORK)->count());

        // Verifikasi getReworkAreaNames() tidak kosong dan berisi 'REWORK'
        $areaNames1 = Defect::getReworkAreaNames();
        $this->assertNotEmpty($areaNames1);
        $this->assertContains('REWORK', $areaNames1);

        // Jalankan seeder kedua kali untuk membuktikan idempotensi
        $this->seed(\Database\Seeders\ReworkAreaSeeder::class);

        // Jumlah area bertipe rework tetap 1 (tidak duplikat)
        $this->assertEquals(1, AreaDitemukan::where('tipe', AreaDitemukan::TIPE_REWORK)->count());

        // getReworkAreaNames() tetap konsisten
        $areaNames2 = Defect::getReworkAreaNames();
        $this->assertNotEmpty($areaNames2);
        $this->assertContains('REWORK', $areaNames2);
    }

    public function test_seeder_area_rework_tidak_membuat_duplikat_jika_sudah_ada_area_bertipe_rework(): void
    {
        // Buat area bertipe rework bernama 'Rework Station'
        AreaDitemukan::create([
            'name' => 'Rework Station',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $this->assertEquals(1, AreaDitemukan::where('tipe', AreaDitemukan::TIPE_REWORK)->count());

        // Jalankan ReworkAreaSeeder
        $this->seed(\Database\Seeders\ReworkAreaSeeder::class);

        // Pastikan tetap hanya 1 area bertipe rework dan tidak ada baris bernama 'REWORK'
        $this->assertEquals(1, AreaDitemukan::where('tipe', AreaDitemukan::TIPE_REWORK)->count());
        $this->assertDatabaseMissing('area_ditemukans', [
            'name' => 'REWORK',
        ]);
        $this->assertDatabaseHas('area_ditemukans', [
            'name' => 'Rework Station',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);
    }
}
