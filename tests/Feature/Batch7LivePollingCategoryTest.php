<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Batch7LivePollingCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(string $name = 'Admin')
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => $name,
            'user_role' => 'Administrator',
        ]);
    }

    public function test_subpage_views_embed_selected_category_in_live_polling_js(): void
    {
        $finalAssembly = $this->actingAsAdmin()->get(route('final_assy.assembly'));
        $finalAssembly->assertOk();
        $finalAssembly->assertSee('params.set(\'category\', "assembly")', false);
        $finalAssembly->assertSee('/api/final-assy/live?', false);

        $finalInternal = $this->actingAsAdmin()->get(route('final_assy.internal'));
        $finalInternal->assertOk();
        $finalInternal->assertSee('params.set(\'category\', "internal")', false);

        $preAssembly = $this->actingAsAdmin()->get(route('pre_assy.assembly'));
        $preAssembly->assertOk();
        $preAssembly->assertSee('params.set(\'category\', "assembly")', false);
        $preAssembly->assertSee('/api/pre-assy/live?', false);

        $preInternal = $this->actingAsAdmin()->get(route('pre_assy.internal'));
        $preInternal->assertOk();
        $preInternal->assertSee('params.set(\'category\', "internal")', false);
    }

    public function test_final_assy_live_filters_by_category_and_normalizes_case_or_invalid_to_all(): void
    {
        $defectType = DefectType::firstOrCreate(['name' => 'WIRING', 'type' => 'Final Assy']);
        $sub = SubDefectType::firstOrCreate(
            ['defect_type_id' => $defectType->id, 'name' => '1.A - CROSS CIRCUIT'],
            ['code' => '1A']
        );

        $internalArea = AreaDitemukan::create([
            'name'     => 'AREA_INTERNAL_FA',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);
        $assemblyArea = AreaDitemukan::create([
            'name'     => 'AREA_ASSEMBLY_FA',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_ASSEMBLY_DEFECT,
        ]);

        $internalDefect = Defect::create([
            'rework_group_id'    => null,
            'waktu'              => now()->subMinutes(5),
            'user_name'          => 'op_internal',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'jenis_mobil'        => 'CALYA',
            'conveyor'           => '664W-C5',
            'area_ditemukan'     => $internalArea->name,
            'ujung'              => '1',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ]);

        $assemblyDefect1 = Defect::create([
            'rework_group_id'    => null,
            'waktu'              => now()->subMinutes(3),
            'user_name'          => 'op_assembly_1',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'jenis_mobil'        => 'CALYA',
            'conveyor'           => '664W-C5',
            'area_ditemukan'     => $assemblyArea->name,
            'ujung'              => '2',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ]);

        $assemblyDefect2 = Defect::create([
            'rework_group_id'    => null,
            'waktu'              => now()->subMinute(),
            'user_name'          => 'op_assembly_2',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'jenis_mobil'        => 'CALYA',
            'conveyor'           => '664W-C5',
            'area_ditemukan'     => $assemblyArea->name,
            'ujung'              => '3',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ]);

        // 1. category=assembly -> hanya Assembly Defect, total = 2
        $resAssembly = $this->getJson('/api/final-assy/live?category=assembly');
        $resAssembly->assertOk();
        $resAssembly->assertJsonPath('success', true);
        $resAssembly->assertJsonPath('total', 2);
        $assemblyIds = collect($resAssembly->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$assemblyDefect1->id, $assemblyDefect2->id], $assemblyIds);
        $this->assertNotContains($internalDefect->id, $assemblyIds);

        // 2. category=internal -> hanya Internal Defect, total = 1
        $resInternal = $this->getJson('/api/final-assy/live?category=internal');
        $resInternal->assertOk();
        $resInternal->assertJsonPath('total', 1);
        $internalIds = collect($resInternal->json('data'))->pluck('id')->all();
        $this->assertSame([$internalDefect->id], $internalIds);

        // 3. category=ALL (uppercase) -> perilaku all, total = 3
        $resUpperAll = $this->getJson('/api/final-assy/live?category=ALL');
        $resUpperAll->assertOk();
        $resUpperAll->assertJsonPath('total', 3);

        // 4. category=tidak_valid -> perilaku all, total = 3
        $resInvalid = $this->getJson('/api/final-assy/live?category=tidak_valid');
        $resInvalid->assertOk();
        $resInvalid->assertJsonPath('total', 3);
    }

    public function test_pre_assy_live_filters_by_category_and_matches_total_count(): void
    {
        $carline = Carline::create([
            'name'       => 'PA-LINE-POLL',
            'jenis_assy' => Carline::AREA_PRE_ASSY,
        ]);
        $defectType = DefectType::firstOrCreate(['name' => 'CRIMPING', 'type' => 'Pre Assy']);
        $sub = SubDefectType::firstOrCreate(
            ['defect_type_id' => $defectType->id, 'name' => 'BENT TERMINAL'],
            ['code' => 'BT']
        );
        AreaDitemukan::firstOrCreate(
            ['name' => 'CUTTING'],
            ['kategori' => AreaDitemukan::KATEGORI_PRE_ASSY, 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $opDefect1 = Defect::create([
            'rework_group_id'    => null,
            'waktu'              => now()->subMinutes(5),
            'user_name'          => 'pa_op_1',
            'shift'              => '1A',
            'jenis_assy'         => 'Pre Assy',
            'carline_id'         => $carline->id,
            'area_ditemukan'     => 'CUTTING',
            'ditemukan_oleh'     => 'Operator',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ]);

        $opDefect2 = Defect::create([
            'rework_group_id'    => null,
            'waktu'              => now()->subMinutes(3),
            'user_name'          => 'pa_op_2',
            'shift'              => '1A',
            'jenis_assy'         => 'Pre Assy',
            'carline_id'         => $carline->id,
            'area_ditemukan'     => 'CUTTING',
            'ditemukan_oleh'     => 'Operator',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ]);

        $inspDefect = Defect::create([
            'rework_group_id'    => null,
            'waktu'              => now()->subMinute(),
            'user_name'          => 'pa_insp_1',
            'shift'              => '1A',
            'jenis_assy'         => 'Pre Assy',
            'carline_id'         => $carline->id,
            'area_ditemukan'     => 'CUTTING',
            'ditemukan_oleh'     => 'Inspektor',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ]);

        // 1. category=assembly -> hanya ditemukan_oleh=Operator, total = 2
        $resOp = $this->getJson('/api/pre-assy/live?category=assembly');
        $resOp->assertOk();
        $resOp->assertJsonPath('total', 2);
        $opIds = collect($resOp->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$opDefect1->id, $opDefect2->id], $opIds);
        $this->assertNotContains($inspDefect->id, $opIds);
        foreach ($resOp->json('data') as $row) {
            $this->assertSame('Operator', $row['ditemukan_oleh']);
        }

        // 2. category=internal -> hanya ditemukan_oleh=Inspektor, total = 1
        $resInsp = $this->getJson('/api/pre-assy/live?category=internal');
        $resInsp->assertOk();
        $resInsp->assertJsonPath('total', 1);
        $inspIds = collect($resInsp->json('data'))->pluck('id')->all();
        $this->assertSame([$inspDefect->id], $inspIds);
        $this->assertSame('Inspektor', $resInsp->json('data.0.ditemukan_oleh'));

        // 3. category=ALL / invalid -> semua (total = 3)
        $this->getJson('/api/pre-assy/live?category=ALL')->assertOk()->assertJsonPath('total', 3);
        $this->getJson('/api/pre-assy/live?category=unknown_xyz')->assertOk()->assertJsonPath('total', 3);
    }
}
