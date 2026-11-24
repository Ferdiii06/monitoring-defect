<?php

namespace Tests\Feature;

use App\Imports\AssyNumberImport;
use App\Models\AssyNumber;
use App\Models\Carline;
use App\Models\CarType;
use App\Models\Defect;
use App\Models\DpmTarget;
use App\Models\PreAssyMachine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Tests\TestCase;

class Batch4MasterCarlineTest extends TestCase
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
            'logged_in'     => true,
            'user_name'     => 'Operator QA',
            'user_role'     => 'User',
            'current_shift' => '1A',
        ]);
    }

    /**
     * 1. Migration backfill benar: car_type_id null -> 'Pre Assy', not null -> 'Final Assy'.
     */
    public function test_migration_backfill_correctly_assigns_jenis_assy(): void
    {
        $carType = CarType::create(['name' => 'TOYOTA']);

        $paCarline = Carline::create([
            'name'        => 'PA-MIGRATE-TEST',
            'car_type_id' => null,
        ]);

        $faCarline = Carline::create([
            'name'        => 'FA-MIGRATE-TEST',
            'car_type_id' => $carType->id,
        ]);

        $this->assertEquals(Carline::AREA_PRE_ASSY, $paCarline->jenis_assy);
        $this->assertEquals(Carline::AREA_FINAL_ASSY, $faCarline->jenis_assy);

        $this->assertTrue(Carline::preAssy()->where('id', $paCarline->id)->exists());
        $this->assertFalse(Carline::finalAssy()->where('id', $paCarline->id)->exists());

        $this->assertTrue(Carline::finalAssy()->where('id', $faCarline->id)->exists());
        $this->assertFalse(Carline::preAssy()->where('id', $faCarline->id)->exists());
    }

    /**
     * 2. Store dan Update validasi dua area:
     *    - Final Assy mewajibkan car_type_id.
     *    - Pre Assy melarang car_type_id (disimpan null).
     */
    public function test_store_and_update_validation_for_two_areas(): void
    {
        $carType = CarType::create(['name' => 'DAIHATSU']);

        // A. Store Final Assy tanpa car_type_id -> Ditolak
        $resStoreFaFail = $this->actingAsAdmin()->post(route('admin.master.carlines.store'), [
            'name'       => 'CONV-FA-INVALID',
            'jenis_assy' => 'Final Assy',
        ]);
        $resStoreFaFail->assertSessionHasErrors('car_type_id');

        // B. Store Final Assy dengan car_type_id -> Berhasil
        $resStoreFaOk = $this->actingAsAdmin()->post(route('admin.master.carlines.store'), [
            'name'        => 'CONV-FA-VALID',
            'jenis_assy'  => 'Final Assy',
            'car_type_id' => $carType->id,
        ]);
        $resStoreFaOk->assertRedirect();
        $savedFa = Carline::where('name', 'CONV-FA-VALID')->first();
        $this->assertNotNull($savedFa);
        $this->assertEquals(Carline::AREA_FINAL_ASSY, $savedFa->jenis_assy);
        $this->assertEquals($carType->id, $savedFa->car_type_id);

        // C. Store Pre Assy tanpa car_type_id -> Berhasil & car_type_id null
        $resStorePaOk = $this->actingAsAdmin()->post(route('admin.master.carlines.store'), [
            'name'       => 'LINE-PA-VALID',
            'jenis_assy' => 'Pre Assy',
        ]);
        $resStorePaOk->assertRedirect();
        $savedPa = Carline::where('name', 'LINE-PA-VALID')->first();
        $this->assertNotNull($savedPa);
        $this->assertEquals(Carline::AREA_PRE_ASSY, $savedPa->jenis_assy);
        $this->assertNull($savedPa->car_type_id);

        // D. Store Pre Assy jika user mengirim car_type_id -> tetap disimpan null
        $this->actingAsAdmin()->post(route('admin.master.carlines.store'), [
            'name'        => 'LINE-PA-STRIP-CT',
            'jenis_assy'  => 'Pre Assy',
            'car_type_id' => $carType->id,
        ]);
        $savedPaStrip = Carline::where('name', 'LINE-PA-STRIP-CT')->first();
        $this->assertNotNull($savedPaStrip);
        $this->assertNull($savedPaStrip->car_type_id);
    }

    /**
     * 3. Nama unik per area (case-insensitive). Boleh nama sama antar area berbeda.
     */
    public function test_name_uniqueness_per_area(): void
    {
        $carType = CarType::create(['name' => 'MITSUBISHI']);

        // 1. Buat Pre Assy 'NSN'
        Carline::create([
            'name'        => 'NSN',
            'jenis_assy'  => 'Pre Assy',
            'car_type_id' => null,
        ]);

        // 2. Buat Pre Assy dengan nama 'nsn' (duplikat case-insensitive di area sama) -> Ditolak
        $resDupPa = $this->actingAsAdmin()->post(route('admin.master.carlines.store'), [
            'name'       => 'nsn',
            'jenis_assy' => 'Pre Assy',
        ]);
        $resDupPa->assertSessionHasErrors('name');

        // 3. Buat Final Assy dengan nama 'NSN' -> Diterima (beda area)
        $resFaSameName = $this->actingAsAdmin()->post(route('admin.master.carlines.store'), [
            'name'        => 'NSN',
            'jenis_assy'  => 'Final Assy',
            'car_type_id' => $carType->id,
        ]);
        $resFaSameName->assertRedirect();
        $this->assertEquals(2, Carline::where('name', 'NSN')->count());
    }

    /**
     * 4. Ubah area ditolak bila carline sudah dipakai (defects, pre_assy_machines, dpm_targets, assy_numbers).
     */
    public function test_change_area_rejected_when_carline_is_used(): void
    {
        $carType = CarType::create(['name' => 'SUZUKI']);

        $carline = Carline::create([
            'name'        => 'PA-USED-CARLINE',
            'jenis_assy'  => 'Pre Assy',
            'car_type_id' => null,
        ]);

        // Relasikan dengan mesin Pre Assy
        PreAssyMachine::create([
            'carline_id' => $carline->id,
            'name'       => 'KOMAX-01',
            'is_active'  => true,
        ]);

        // Coba ganti jenis_assy ke Final Assy -> Ditolak
        $resUpdate = $this->actingAsAdmin()->put(route('admin.master.carlines.update', $carline->id), [
            'name'        => 'PA-USED-CARLINE',
            'jenis_assy'  => 'Final Assy',
            'car_type_id' => $carType->id,
        ]);

        $resUpdate->assertSessionHasErrors('jenis_assy');
        $this->assertEquals(Carline::AREA_PRE_ASSY, $carline->fresh()->jenis_assy);

        // Jika belum dipakai di mana-mana -> Boleh diubah
        $carlineUnused = Carline::create([
            'name'        => 'UNUSED-CHANGE',
            'jenis_assy'  => 'Pre Assy',
            'car_type_id' => null,
        ]);

        $resUpdateOk = $this->actingAsAdmin()->put(route('admin.master.carlines.update', $carlineUnused->id), [
            'name'        => 'UNUSED-CHANGE',
            'jenis_assy'  => 'Final Assy',
            'car_type_id' => $carType->id,
        ]);
        $resUpdateOk->assertRedirect();
        $this->assertEquals(Carline::AREA_FINAL_ASSY, $carlineUnused->fresh()->jenis_assy);
    }

    /**
     * 5. Destroy ditolak bila carline punya No Mesin (PreAssyMachine) dan tidak menghapus mesin cascade.
     */
    public function test_destroy_rejected_when_carline_has_machines(): void
    {
        $carline = Carline::create([
            'name'        => 'PA-DEL-BLOCKED',
            'jenis_assy'  => 'Pre Assy',
            'car_type_id' => null,
        ]);

        $machine = PreAssyMachine::create([
            'carline_id' => $carline->id,
            'name'       => 'MACHINE-BLOCK-1',
            'is_active'  => true,
        ]);

        $resDel = $this->actingAsAdmin()->delete(route('admin.master.carlines.destroy', $carline->id));
        $resDel->assertSessionHasErrors('error');

        // Carline dan Mesin tidak terhapus
        $this->assertDatabaseHas('carlines', ['id' => $carline->id]);
        $this->assertDatabaseHas('pre_assy_machines', ['id' => $machine->id]);
    }

    /**
     * 6. Tab dan Filter pada Halaman Master Carline:
     *    - Tab Pre Assy: kolom Jenis Mobil tidak tampil.
     *    - Tab Semua: baris Pre Assy menampilkan "Pre Assy (tanpa jenis mobil)" dan link No Mesin.
     */
    public function test_tab_and_filter_in_master_carlines_view(): void
    {
        $carType = CarType::create(['name' => 'HYUNDAI']);

        $pa = Carline::create([
            'name'        => 'PA-VIEW-LINE',
            'jenis_assy'  => 'Pre Assy',
            'car_type_id' => null,
        ]);

        $fa = Carline::create([
            'name'        => 'FA-VIEW-CONV',
            'jenis_assy'  => 'Final Assy',
            'car_type_id' => $carType->id,
        ]);

        // 1. Tab Pre Assy: kolom Jenis Mobil tidak tampil di header tabel & Final Assy conveyor tidak muncul di tabel
        $resPa = $this->actingAsAdmin()->get(route('admin.master.carlines.index', ['area' => 'Pre Assy']));
        $resPa->assertOk();
        $resPa->assertSee('PA-VIEW-LINE');
        $resPa->assertDontSee('FA-VIEW-CONV');
        $resPa->assertDontSee('Jenis Mobil:'); // Filter Jenis Mobil disembunyikan
        $resPa->assertDontSee('>Jenis Mobil</th>', false); // Kolom Jenis Mobil di tabel tidak tampil

        // 2. Tab Final Assy: hanya tampil Final Assy
        $resFa = $this->actingAsAdmin()->get(route('admin.master.carlines.index', ['area' => 'Final Assy']));
        $resFa->assertOk();
        $resFa->assertSee('FA-VIEW-CONV');
        $resFa->assertSee('HYUNDAI');
        $resFa->assertDontSee('PA-VIEW-LINE');

        // 3. Tab Semua: baris Pre Assy menampilkan badge/teks "Pre Assy (tanpa jenis mobil)" & link Mesin
        $resAll = $this->actingAsAdmin()->get(route('admin.master.carlines.index', ['area' => 'all']));
        $resAll->assertOk();
        $resAll->assertSee('PA-VIEW-LINE');
        $resAll->assertSee('FA-VIEW-CONV');
        $resAll->assertSee('Pre Assy (tanpa jenis mobil)');
        $resAll->assertSee(route('admin.master.pre_assy_machines.index', ['carline_id' => $pa->id]));
    }

    /**
     * 7. Dropdown form operator: Pre Assy hanya carline Pre Assy, Final Assy dan rework hanya Final Assy.
     */
    public function test_operator_form_dropdowns_filter_carlines_strictly_by_area(): void
    {
        $carType = CarType::create(['name' => 'WULING']);

        $pa = Carline::create([
            'name'        => 'LINE-PA-DROPDOWN',
            'jenis_assy'  => 'Pre Assy',
            'car_type_id' => null,
        ]);

        $fa = Carline::create([
            'name'        => 'CONV-FA-DROPDOWN',
            'jenis_assy'  => 'Final Assy',
            'car_type_id' => $carType->id,
        ]);

        // Form Operator Pre Assy
        $resInputPa = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Pre Assy']));
        $resInputPa->assertOk();
        $paOptions = $resInputPa->viewData('preAssyCarlines');
        $this->assertTrue($paOptions->contains('id', $pa->id));
        $this->assertFalse($paOptions->contains('id', $fa->id));

        // Form Operator Final Assy
        $resInputFa = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Final Assy']));
        $resInputFa->assertOk();
        $carTypes = $resInputFa->viewData('carTypes');
        $faCarlineIds = $carTypes->flatMap->carlines->pluck('id')->all();
        $this->assertContains($fa->id, $faCarlineIds);
        $this->assertNotContains($pa->id, $faCarlineIds);
    }

    /**
     * 8. AssyNumberImport: jika nama carline cocok dengan lebih dari satu carline, baris dilewati dan masuk ringkasan "ambigu".
     */
    public function test_assy_number_import_skips_ambiguous_carline_name(): void
    {
        $carType = CarType::create(['name' => 'TOYOTA']);

        // Buat 2 carline bernama sama: 1 di Pre Assy, 1 di Final Assy
        Carline::create(['name' => 'LINE-DUAL', 'jenis_assy' => 'Pre Assy', 'car_type_id' => null]);
        Carline::create(['name' => 'LINE-DUAL', 'jenis_assy' => 'Final Assy', 'car_type_id' => $carType->id]);

        $import = new AssyNumberImport();
        $rows = collect([
            [
                'assy_number' => '8211-AMBIGU-01',
                'carline'     => 'LINE-DUAL',
                'family'      => 'FAM-1',
                'conveyor'    => 'C1',
                'jenis_mobil' => 'TOYOTA',
            ],
            [
                'assy_number' => '8211-VALID-01',
                'carline'     => null,
                'family'      => 'FAM-2',
                'conveyor'    => 'C2',
                'jenis_mobil' => 'TOYOTA',
            ]
        ]);

        $import->collection($rows);

        $this->assertEquals(1, $import->getSuccessCount());
        $this->assertEquals(1, $import->getSkippedCount());

        $skipped = $import->getSkippedRows();
        $this->assertCount(1, $skipped);
        $this->assertStringContainsString('ambigu', strtolower($skipped[0]['reason']));
    }
}
