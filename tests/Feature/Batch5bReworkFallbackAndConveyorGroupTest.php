<?php

namespace Tests\Feature;

use App\Exports\ReworkHistoryExport;
use App\Models\AreaDitemukan;
use App\Models\CarType;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use App\Support\ConveyorGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class Batch5bReworkFallbackAndConveyorGroupTest extends TestCase
{
    use RefreshDatabase;

    private function createSubDefect(): SubDefectType
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

    public function test_conveyor_group_ignores_rework_rows_and_excludes_unique_rework_conveyor_from_dashboard_filter(): void
    {
        $sub = $this->createSubDefect();

        // Baris operator (rework_group_id = null) -> harus muncul
        Defect::create([
            'rework_group_id'    => null,
            'waktu'              => now(),
            'user_name'          => 'operator_1',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'conveyor'           => '909W-OP11',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $sub->defect_type_id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => 'WIRING',
            'jenis_sub_defect'   => $sub->name,
        ]);

        // Baris rework (rework_group_id NOT NULL) dengan conveyor unik -> TIDAK boleh muncul di dropdown filter
        Defect::create([
            'rework_group_id'    => 'rwk-group-unique-conv-1',
            'waktu'              => now(),
            'waktu_in'           => now(),
            'status_rework'      => 'OPEN',
            'user_name'          => 'rework_1',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'conveyor'           => 'RWKONLY99',
            'quantity'           => 1,
            'defect_type_id'     => $sub->defect_type_id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => 'WIRING',
            'jenis_sub_defect'   => $sub->name,
        ]);

        $groups = ConveyorGroup::availableGroups();
        $this->assertContains('OP11', $groups);
        $this->assertNotContains('RWKONLY99', $groups);

        $resolvedRework = ConveyorGroup::resolveNamesForGroup('RWKONLY99');
        $this->assertSame([], $resolvedRework);

        $resolvedOp = ConveyorGroup::resolveNamesForGroup('OP11');
        $this->assertContains('909W-OP11', $resolvedOp);

        // Pastikan halaman dashboard admin juga tidak memuat RWKONLY99 di dropdown filter
        $dashRes = $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin',
            'user_role' => 'Administrator',
        ])->get(route('dashboard'));
        $dashRes->assertOk();
        $dashRes->assertSee('OP11');
        $dashRes->assertDontSee('RWKONLY99');
    }

    public function test_null_rework_columns_render_dash_in_web_admin_and_export_without_error(): void
    {
        $sub = $this->createSubDefect();
        AreaDitemukan::firstOrCreate(['name' => 'RWK'], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        $groupId = 'group-null-fallback-1';
        $defect = Defect::create([
            'rework_group_id'    => $groupId,
            'waktu'              => now()->subMinutes(15),
            'waktu_in'           => now()->subMinutes(15),
            'status_rework'      => 'OPEN',
            'kanban_assy'        => null,
            'kanban_serial'      => null,
            'kanban_status'      => null,
            'nameplate_lama'     => null,
            'nameplate_baru'     => null,
            'conveyor'           => null,
            'carline_id'         => null,
            'jenis_mobil'        => null,
            'user_name'          => 'rework_null_tester',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'quantity'           => 1,
            'area_ditemukan'     => 'RWK',
            'defect_type_id'     => $sub->defect_type_id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => 'WIRING',
            'jenis_sub_defect'   => $sub->name,
        ]);

        // 1. Kartu Riwayat Rework (web operator rework)
        $webRes = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'rework_null_tester',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->get(route('rework.index'));

        $webRes->assertOk();
        $webRes->assertSee('NP: -');
        $webRes->assertSee('NP Baru: -');
        $webRes->assertSee('Jenis Mobil: -');
        $webRes->assertSee('Conv: -');
        $webRes->assertSee('Status Kanban: -');

        // 2. Tabel Riwayat Rework (admin)
        $adminRes = $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin',
            'user_role' => 'Administrator',
        ])->get(route('admin.rework_history.index'));

        $adminRes->assertOk();
        $adminRes->assertSee('rework_null_tester');

        // 3. Export Rework (32 kolom)
        $export = new ReworkHistoryExport(new Request());
        $headings = $export->headings();
        $this->assertCount(32, $headings);

        $mapped = $export->map($defect->fresh(['carline.carType', 'subDefectType']));
        $this->assertCount(32, $mapped);

        $idxStatusKanban = array_search('Status Kanban', $headings, true);
        $idxNpLama = array_search('No Nameplate Lama', $headings, true);
        $idxNpBaru = array_search('No Nameplate Baru', $headings, true);
        $idxJenisMobil = array_search('Jenis Mobil', $headings, true);
        $idxConveyor = array_search('Konveyor', $headings, true);

        $this->assertSame('-', $mapped[$idxStatusKanban]);
        $this->assertSame('-', $mapped[$idxNpLama]);
        $this->assertSame('-', $mapped[$idxNpBaru]);
        $this->assertSame('-', $mapped[$idxJenisMobil]);
        $this->assertSame('-', $mapped[$idxConveyor]);
    }

    public function test_complete_rework_row_keeps_original_values_in_web_admin_and_export(): void
    {
        $sub = $this->createSubDefect();
        AreaDitemukan::firstOrCreate(['name' => 'RWK'], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        $carType = CarType::create(['name' => 'VELOZ']);
        $carline = Carline::create([
            'car_type_id' => $carType->id,
            'name'        => '664W-C5',
            'jenis_assy'  => Carline::AREA_FINAL_ASSY,
        ]);

        $groupId = 'group-complete-1';
        $defect = Defect::create([
            'rework_group_id'    => $groupId,
            'waktu'              => now()->subMinutes(20),
            'waktu_in'           => now()->subMinutes(20),
            'waktu_out'          => now()->subMinutes(5),
            'durasi_rework_menit' => 15,
            'status_rework'      => 'CLOSE',
            'kanban_assy'        => '82111-VU020.C5.010',
            'kanban_serial'      => 10,
            'kanban_status'      => 'terdaftar',
            'nameplate_lama'     => 'NP-LAMA-FULL',
            'nameplate_baru'     => 'NP-BARU-FULL',
            'conveyor'           => '664W-C5',
            'carline_id'         => $carline->id,
            'jenis_mobil'        => 'VELOZ',
            'user_name'          => 'rework_full_tester',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'quantity'           => 1,
            'area_ditemukan'     => 'RWK',
            'defect_type_id'     => $sub->defect_type_id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => 'WIRING',
            'jenis_sub_defect'   => $sub->name,
        ]);

        $webRes = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'rework_full_tester',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->get(route('rework.index'));

        $webRes->assertOk();
        $webRes->assertSee('NP: NP-LAMA-FULL');
        $webRes->assertSee('NP Baru: NP-BARU-FULL');
        $webRes->assertSee('Jenis Mobil: VELOZ');
        $webRes->assertSee('Conv: 664W-C5');
        $webRes->assertSee('Status Kanban: Terdaftar');

        $adminRes = $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin',
            'user_role' => 'Administrator',
        ])->get(route('admin.rework_history.index'));

        $adminRes->assertOk();
        $adminRes->assertSee('NP-LAMA-FULL');
        $adminRes->assertSee('NP-BARU-FULL');
        $adminRes->assertSee('VELOZ');
        $adminRes->assertSee('664W-C5');
        $adminRes->assertSee('Terdaftar');

        $export = new ReworkHistoryExport(new Request());
        $headings = $export->headings();
        $mapped = $export->map($defect->fresh(['carline.carType', 'subDefectType']));

        $this->assertSame('Terdaftar', $mapped[array_search('Status Kanban', $headings, true)]);
        $this->assertSame('NP-LAMA-FULL', $mapped[array_search('No Nameplate Lama', $headings, true)]);
        $this->assertSame('NP-BARU-FULL', $mapped[array_search('No Nameplate Baru', $headings, true)]);
        $this->assertSame('VELOZ', $mapped[array_search('Jenis Mobil', $headings, true)]);
        $this->assertSame('664W-C5', $mapped[array_search('Konveyor', $headings, true)]);
    }
}
