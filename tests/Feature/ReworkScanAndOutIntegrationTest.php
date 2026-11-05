<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\AssyNumber;
use App\Models\Carline;
use App\Models\CarType;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReworkScanAndOutIntegrationTest extends TestCase
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

    private function actingAsRework(string $name = 'reworkStaff')
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => $name,
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ]);
    }

    private function createSubDefect(string $code = '1.A', string $name = '1.A - CROSS CIRCUIT'): SubDefectType
    {
        $defectType = DefectType::firstOrCreate(
            ['name' => 'WIRING', 'type' => 'Final Assy']
        );

        return SubDefectType::firstOrCreate(
            ['defect_type_id' => $defectType->id, 'name' => $name],
            ['code' => $code]
        );
    }

    private function createDefect(array $attributes): Defect
    {
        $sub = $attributes['subDefect'] ?? $this->createSubDefect();
        unset($attributes['subDefect']);

        $default = [
            'waktu'              => now(),
            'waktu_in'           => now(),
            'status_rework'      => 'OPEN',
            'jenis_assy'         => 'Final Assy',
            'defect_type_id'     => $sub->defect_type_id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => 'WIRING',
            'jenis_sub_defect'   => $sub->name,
            'area_ditemukan'     => 'RWK',
            'quantity'           => 1,
            'shift'              => '1A',
        ];

        return Defect::create(array_merge($default, $attributes));
    }

    public function test_in_flow_with_scanned_kanban_when_assy_in_master(): void
    {
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);

        $carType = CarType::create(['name' => 'CALYA']);
        $carline = Carline::create([
            'car_type_id' => $carType->id,
            'name'        => '664W-C5',
        ]);

        AssyNumber::create([
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
            'keterangan'  => 'Main Assy Calya',
        ]);

        // 1. Endpoint lookup return JSON dengan car_type_id dan car_type_nama
        $lookupRes = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '82111-VU020.C5.001',
        ]));

        $lookupRes->assertStatus(200);
        $lookupRes->assertJson([
            'assy_number'         => '82111-VU020',
            'conveyor'            => 'C5',
            'carline_id'          => $carline->id,
            'carline_nama'        => '664W-C5',
            'car_type_id'         => $carType->id,
            'car_type_nama'       => 'CALYA',
            'ditemukan_di_master' => true,
        ]);

        // 2. Simpan form In dengan nilai hasil auto-fill dropdown
        $sub = $this->createSubDefect();
        $response = $this->actingAsRework()->post(route('rework.store_in'), [
            'kanban_assy'    => '82111-VU020.C5.001',
            'nameplate'      => '82111VU020A2607060001',
            'carline'        => 'CALYA',
            'conveyor'       => '664W-C5',
            'sub_defect_ids' => [$sub->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $this->assertDatabaseHas('defects', [
            'kanban_assy' => '82111-VU020.C5.001',
            'carline_id'  => $carline->id,
            'conveyor'    => '664W-C5',
            'jenis_mobil' => 'CALYA',
        ]);
    }

    public function test_in_flow_without_scan_manual_input_still_works(): void
    {
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);
        $carType = CarType::create(['name' => 'SIGRA']);
        $carline = Carline::create([
            'car_type_id' => $carType->id,
            'name'        => '664W-C5A',
        ]);

        $sub = $this->createSubDefect();

        $response = $this->actingAsRework()->post(route('rework.store_in'), [
            'kanban_assy'    => 'MANUAL-KB-001',
            'nameplate'      => 'MANUAL-NP-001',
            'carline'        => 'SIGRA',
            'conveyor'       => '664W-C5A',
            'sub_defect_ids' => [$sub->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $this->assertDatabaseHas('defects', [
            'kanban_assy'    => 'MANUAL-KB-001',
            'nameplate_lama' => 'MANUAL-NP-001',
            'carline_id'     => $carline->id,
            'conveyor'       => '664W-C5A',
            'jenis_mobil'    => 'SIGRA',
        ]);
    }

    public function test_in_flow_when_assy_not_in_master(): void
    {
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);

        // 1. Endpoint lookup return ditemukan_di_master = false dan conveyor scan
        $lookupRes = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '82199-UNREG.C7.009',
        ]));

        $lookupRes->assertStatus(200);
        $lookupRes->assertJson([
            'assy_number'         => '82199-UNREG',
            'conveyor'            => 'C7',
            'carline_id'          => null,
            'carline_nama'        => null,
            'car_type_id'         => null,
            'car_type_nama'       => null,
            'ditemukan_di_master' => false,
        ]);

        // 2. Form tetap bisa disimpan meskipun assy tidak di master
        $sub = $this->createSubDefect();
        $response = $this->actingAsRework()->post(route('rework.store_in'), [
            'kanban_assy'    => '82199-UNREG.C7.009',
            'nameplate'      => '82199UNREG001',
            'sub_defect_ids' => [$sub->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $this->assertDatabaseHas('defects', [
            'kanban_assy' => '82199-UNREG.C7.009',
            'carline_id'  => null,
        ]);
    }

    public function test_out_flow_with_new_nameplate_updates_all_defects_in_group(): void
    {
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);
        $sub1 = $this->createSubDefect('1.A', '1.A - CROSS CIRCUIT');
        $sub2 = $this->createSubDefect('2.A', '2.A - TERMINAL BENT');

        $groupId = (string) Str::uuid();
        $user = 'reworkStaff';

        // Buat 2 baris defect dalam 1 rework_group_id
        $this->createDefect([
            'rework_group_id' => $groupId,
            'user_name'       => $user,
            'subDefect'       => $sub1,
            'kanban_assy'     => 'KB-OUT-TEST',
            'nameplate_lama'  => 'NP-LAMA-01',
            'nameplate_baru'  => null,
            'waktu_in'        => now()->subMinutes(30),
        ]);

        $this->createDefect([
            'rework_group_id' => $groupId,
            'user_name'       => $user,
            'subDefect'       => $sub2,
            'kanban_assy'     => 'KB-OUT-TEST',
            'nameplate_lama'  => 'NP-LAMA-01',
            'nameplate_baru'  => null,
            'waktu_in'        => now()->subMinutes(30),
        ]);

        // Kirim Out dengan NP Baru
        $response = $this->actingAsRework($user)->post(route('rework.store_out', $groupId), [
            'nameplate_baru' => ' np-baru-9999 ',
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');

        // Pastikan SEMUA baris defect dalam group ter-update NP Baru kapital dan status CLOSE
        $defects = Defect::where('rework_group_id', $groupId)->get();
        $this->assertCount(2, $defects);
        foreach ($defects as $d) {
            $this->assertSame('CLOSE', $d->status_rework);
            $this->assertSame('NP-BARU-9999', $d->nameplate_baru);
            $this->assertNotNull($d->waktu_out);
        }
    }

    public function test_out_flow_without_new_nameplate_does_not_overwrite_existing_nameplate_baru(): void
    {
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);
        $sub = $this->createSubDefect();
        $groupId = (string) Str::uuid();
        $user = 'reworkStaff';

        // Defect sudah memiliki NP Baru sebelumnya
        $this->createDefect([
            'rework_group_id' => $groupId,
            'user_name'       => $user,
            'subDefect'       => $sub,
            'kanban_assy'     => 'KB-OUT-KEEP',
            'nameplate_lama'  => 'NP-LAMA-02',
            'nameplate_baru'  => 'EXISTING-NP-BARU',
            'waktu_in'        => now()->subMinutes(15),
        ]);

        // Out tanpa mengisi input nameplate_baru (string kosong)
        $response = $this->actingAsRework($user)->post(route('rework.store_out', $groupId), [
            'nameplate_baru' => '',
        ]);

        $response->assertRedirect(route('rework.index'));
        $defect = Defect::where('rework_group_id', $groupId)->first();
        $this->assertSame('CLOSE', $defect->status_rework);
        $this->assertSame('EXISTING-NP-BARU', $defect->nameplate_baru, 'Existing nameplate_baru must not be overwritten when Out input is empty');
    }

    public function test_rework_views_render_jenis_mobil_label_and_out_np_baru_field(): void
    {
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);
        $sub = $this->createSubDefect();
        $groupId = (string) Str::uuid();
        $user = 'reworkStaff';

        $this->createDefect([
            'rework_group_id' => $groupId,
            'user_name'       => $user,
            'subDefect'       => $sub,
            'kanban_assy'     => 'KB-VIEW-TEST',
            'nameplate_lama'  => 'NP-VIEW-TEST',
        ]);

        $response = $this->actingAsRework($user)->get(route('rework.index'));
        $response->assertStatus(200);
        $response->assertSee('Jenis Mobil');
        $response->assertSee('NP Baru');
        $response->assertSee('nameplate_baru');
    }
}
