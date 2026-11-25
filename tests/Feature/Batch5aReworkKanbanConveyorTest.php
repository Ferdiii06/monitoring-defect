<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\AssyNumber;
use App\Models\CarType;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Batch5aReworkKanbanConveyorTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRework(string $name = 'rework_operator')
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => $name,
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ]);
    }

    private function actingAsOperator(string $name = 'operator_1')
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => $name,
            'user_role'     => 'User',
            'current_shift' => '1A',
        ]);
    }

    private function setupSubDefect(): SubDefectType
    {
        $defectType = DefectType::firstOrCreate([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);

        return SubDefectType::firstOrCreate(
            ['defect_type_id' => $defectType->id, 'name' => '1.A - CROSS CIRCUIT'],
            ['code' => '1A']
        );
    }

    public function test_assy_ada_di_master_returns_uppercase_conveyor_and_jenis_mobil(): void
    {
        $carType = CarType::create(['name' => 'INNOVA ZENIX']);
        $carline = Carline::create([
            'car_type_id' => $carType->id,
            'name'        => '909W-AT11.HEV LHD',
            'jenis_assy'  => Carline::AREA_FINAL_ASSY,
        ]);

        AssyNumber::create([
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
            'car_type_id' => $carType->id,
            'conveyor'    => 'AT11',
        ]);

        $response = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '*82111-VU020.at11.005*',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'assy_number'         => '82111-VU020',
            'conveyor'            => 'AT11',
            'conveyor_master'     => 'AT11',
            'conveyor_beda'       => false,
            'serial'              => '005',
            'car_type_nama'       => 'INNOVA ZENIX',
            'jenis_mobil'         => 'INNOVA ZENIX',
            'ditemukan_di_master' => true,
        ]);
    }

    public function test_assy_tidak_ada_di_master_returns_jenis_mobil_dash_and_saves_scanned_conveyor(): void
    {
        $sub = $this->setupSubDefect();
        AreaDitemukan::firstOrCreate(['name' => 'RWK'], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        $lookupRes = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '82199-UNREG.c7.009',
        ]));

        $lookupRes->assertStatus(200);
        $lookupRes->assertJson([
            'assy_number'         => '82199-UNREG',
            'conveyor'            => 'C7',
            'jenis_mobil'         => '-',
            'car_type_nama'       => null,
            'ditemukan_di_master' => false,
        ]);

        $storeRes = $this->actingAsRework()->post(route('rework.store_in'), [
            'kanban_assy'    => '82199-UNREG.c7.009',
            'nameplate'      => '82199UNREG009',
            'area_ditemukan' => 'RWK',
            'sub_defect_ids' => [$sub->id],
        ]);

        $storeRes->assertRedirect(route('rework.index'));
        $this->assertDatabaseHas('defects', [
            'kanban_assy'   => '82199-UNREG.c7.009',
            'conveyor'      => 'C7',
            'jenis_mobil'   => '-',
            'kanban_status' => 'tidak_terdaftar',
            'carline_id'    => null,
        ]);
    }

    public function test_conveyor_beda_saves_scanned_conveyor_uppercase_in_in_and_edit(): void
    {
        $sub = $this->setupSubDefect();
        AreaDitemukan::firstOrCreate(['name' => 'RWK'], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        $carType = CarType::create(['name' => 'CALYA']);
        $carline = Carline::create([
            'car_type_id' => $carType->id,
            'name'        => '664W-C5',
            'jenis_assy'  => Carline::AREA_FINAL_ASSY,
        ]);

        AssyNumber::create([
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
            'car_type_id' => $carType->id,
            'conveyor'    => 'C5',
        ]);

        // Lookup menandai conveyor_beda = true
        $lookupRes = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '*82111-VU020.c9.012*',
        ]));
        $lookupRes->assertStatus(200);
        $lookupRes->assertJson([
            'conveyor'            => 'C9',
            'conveyor_master'     => 'C5',
            'conveyor_beda'       => true,
            'jenis_mobil'         => 'CALYA',
            'ditemukan_di_master' => true,
        ]);

        // Form In mengirim dropdown master (664W-C5), tetapi yang disimpan harus konveyor dari scan (C9 uppercase)
        $storeRes = $this->actingAsRework()->post(route('rework.store_in'), [
            'kanban_assy'    => '*82111-VU020.c9.012*',
            'nameplate'      => 'NP-BEDA-01',
            'carline'        => 'CALYA',
            'conveyor'       => '664W-C5',
            'area_ditemukan' => 'RWK',
            'sub_defect_ids' => [$sub->id],
        ]);
        $storeRes->assertRedirect(route('rework.index'));

        $defect = Defect::where('nameplate_lama', 'NP-BEDA-01')->first();
        $this->assertNotNull($defect);
        $this->assertSame('C9', $defect->conveyor);
        $this->assertSame('CALYA', $defect->jenis_mobil);
        $this->assertSame('conveyor_beda', $defect->kanban_status);

        // Form Edit juga menyimpan konveyor scan uppercase (C8) saat conveyor_beda
        $updateRes = $this->actingAsRework()->put(route('rework.update', $defect->rework_group_id), [
            'kanban_assy'    => '*82111-VU020.c8.015*',
            'nameplate'      => 'NP-BEDA-01',
            'carline'        => 'CALYA',
            'conveyor'       => '664W-C5',
            'area_ditemukan' => 'RWK',
            'sub_defect_ids' => [$sub->id],
        ]);
        $updateRes->assertRedirect(route('rework.index'));

        $defect->refresh();
        $this->assertSame('C8', $defect->conveyor);
        $this->assertSame('conveyor_beda', $defect->kanban_status);
    }

    public function test_validasi_nama_carline_panjang_lolos_di_rework_in_dan_edit(): void
    {
        $sub = $this->setupSubDefect();
        AreaDitemukan::firstOrCreate(['name' => 'RWK'], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        $longConveyor = '909W-AT11.HEV LHD';

        // Simpan In dengan nama carline/conveyor panjang yang belum ada di master carlines
        $storeRes = $this->actingAsRework()->post(route('rework.store_in'), [
            'kanban_assy'    => 'MANUAL-LONG-001',
            'nameplate'      => 'NP-LONG-001',
            'carline'        => 'INNOVA ZENIX',
            'conveyor'       => $longConveyor,
            'area_ditemukan' => 'RWK',
            'sub_defect_ids' => [$sub->id],
        ]);

        $storeRes->assertRedirect(route('rework.index'));
        $storeRes->assertSessionHasNoErrors();

        $defect = Defect::where('kanban_assy', 'MANUAL-LONG-001')->first();
        $this->assertNotNull($defect);
        $this->assertSame($longConveyor, $defect->conveyor);
        $this->assertSame('INNOVA ZENIX', $defect->jenis_mobil);

        // Edit dengan nama carline/conveyor panjang juga lolos
        $longConveyorEdited = '909W-AT12.HEV RHD';
        $editRes = $this->actingAsRework()->put(route('rework.update', $defect->rework_group_id), [
            'kanban_assy'    => 'MANUAL-LONG-001',
            'nameplate'      => 'NP-LONG-001',
            'carline'        => 'INNOVA ZENIX',
            'conveyor'       => $longConveyorEdited,
            'area_ditemukan' => 'RWK',
            'sub_defect_ids' => [$sub->id],
        ]);

        $editRes->assertRedirect(route('rework.index'));
        $editRes->assertSessionHasNoErrors();
        $this->assertSame($longConveyorEdited, $defect->fresh()->conveyor);
    }

    public function test_form_operator_internal_defect_tidak_berubah(): void
    {
        $carType = CarType::create(['name' => 'CALYA']);
        Carline::create([
            'name'        => 'CONV-1',
            'car_type_id' => $carType->id,
            'jenis_assy'  => Carline::AREA_FINAL_ASSY,
        ]);
        $defectType = DefectType::create(['name' => 'DIMENSI', 'type' => 'Final Assy']);
        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - DIMENSI PENDEK',
        ]);
        AreaDitemukan::firstOrCreate(
            ['name' => 'CK'],
            ['tipe' => AreaDitemukan::KATEGORI_FINAL_ASSY, 'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY]
        );

        // Halaman input defect operator tetap memiliki dropdown conveyor manual tanpa lookup kanban
        $getRes = $this->actingAsOperator()->get(route('input_defect.create'));
        $getRes->assertOk();
        $getRes->assertSee('x-model="form.conveyor"', false);
        $getRes->assertDontSee('lookup-assy', false);

        // Simpan defect operator tetap berjalan normal dengan rework_group_id = null
        $postRes = $this->actingAsOperator()->post(route('input_defect.store'), [
            'type'               => 'Final Assy',
            'jenis_mobil'        => 'CALYA',
            'conveyor'           => 'CONV-1',
            'tanggal'            => '2026-11-05',
            'jam'                => '10:00',
            'area_ditemukan'     => 'CK',
            'end_number'         => '1',
            'jenis_defect'       => $defectType->name,
            'sub_defect_type_id' => $subDefect->id,
            'jumlah'             => 1,
            'inspect_quantity'   => 10,
            'shift'              => '1A',
        ]);

        $postRes->assertRedirect();
        $postRes->assertSessionHasNoErrors();
        $this->assertDatabaseHas('defects', [
            'rework_group_id' => null,
            'jenis_assy'      => 'Final Assy',
            'conveyor'        => 'CONV-1',
        ]);
    }
}
