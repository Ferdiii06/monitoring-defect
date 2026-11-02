<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\DpmTarget;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Tests\TestCase;

class CombinedModeBackendTest extends TestCase
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

    /**
     * Skenario a: Baris mismatch tidak ikut di total mode 'all'.
     */
    public function test_scenario_a_mismatch_rows_not_included_in_mode_all_total(): void
    {
        AreaDitemukan::create(['name' => 'Area Internal', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Rework', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        // 1. Sah Internal: operator (rework_group_id null) + area internal -> 4 pcs
        Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'operator1',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Internal',
            'rework_group_id'  => null,
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 4,
            'inspect_quantity' => 100,
        ]);

        // 2. Sah Rework: rework_group_id not null + area rework -> 6 pcs
        Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'rework1',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-01',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'quantity'         => 6,
        ]);

        // 3. Mismatch 1: Operator (rework_group_id null) + area rework -> 10 pcs (hanya tercatat)
        Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'operator1',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => null,
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'BROKEN',
            'quantity'         => 10,
        ]);

        // 4. Mismatch 2: Rework (rework_group_id not null) + area internal -> 20 pcs (hanya tercatat)
        Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'rework1',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Internal',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-02',
            'jenis_defect'     => 'HOUSING',
            'jenis_sub_defect' => 'CRACK',
            'quantity'         => 20,
        ]);

        // Total di DB: 4 + 6 + 10 + 20 = 40.
        // Tetapi mode 'all' HANYA union dari sah internal (4) + sah rework (6) = 10.
        $totalAllGlobal = (int) Defect::byDashboardMode('all')->sum('quantity');
        $this->assertEquals(10, $totalAllGlobal);

        $totalAllFinal = (int) Defect::byDashboardMode('all', 'Final Assy')->sum('quantity');
        $this->assertEquals(10, $totalAllFinal);

        // Cek via API Stats
        $res = $this->actingAsAdmin()->getJson('/api/dashboard/stats?mode=all');
        $res->assertStatus(200);
        $res->assertJson([
            'totalDefect'     => 10,
            'internal_defect' => 4,
            'rework_defect'   => 6,
            'internal_percent'=> 40.0,
            'rework_percent'  => 60.0,
        ]);

        // Cek via apiCharts (/api/dashboard/charts)
        $chartsRes = $this->actingAsAdmin()->getJson('/api/dashboard/charts?mode=all');
        $chartsRes->assertStatus(200);
        $chartsRes->assertJson([
            'success'          => true,
            'internal_defect'  => 4,
            'rework_defect'    => 6,
            'internal_percent' => 40.0,
            'rework_percent'   => 60.0,
        ]);
        $this->assertEquals(4, $chartsRes->json('finalAssy.internal_defect'));
        $this->assertEquals(6, $chartsRes->json('finalAssy.rework_defect'));
        $this->assertEquals(40.0, $chartsRes->json('finalAssy.internal_percent'));
        $this->assertEquals(60.0, $chartsRes->json('finalAssy.rework_percent'));
    }

    /**
     * Skenario b: def_internal + def_rework = total_defect per bucket.
     */
    public function test_scenario_b_def_internal_plus_def_rework_equals_total_defect_per_bucket(): void
    {
        AreaDitemukan::create(['name' => 'FA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Rework', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        $day1 = Carbon::parse('2026-05-10 10:00:00');
        $day2 = Carbon::parse('2026-05-11 10:00:00');

        // Day 1: Internal 5 pcs, Rework 7 pcs (2 harness) -> total 12
        Defect::create([
            'waktu'            => $day1,
            'user_name'        => 'op QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'FA Inspection',
            'rework_group_id'  => null,
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 5,
            'inspect_quantity' => 500,
        ]);

        $group1 = (string) Str::uuid();
        Defect::create([
            'waktu'            => $day1,
            'user_name'        => 'rw QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => $group1,
            'kanban_assy'      => 'KB-1',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'quantity'         => 3,
        ]);

        $group2 = (string) Str::uuid();
        Defect::create([
            'waktu'            => $day1,
            'user_name'        => 'rw QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => $group2,
            'kanban_assy'      => 'KB-2',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'quantity'         => 4,
        ]);

        // Day 1 Mismatch (harus diabaikan dari bucket):
        Defect::create([
            'waktu'            => $day1,
            'user_name'        => 'op QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => null,
            'jenis_defect'     => 'OTHER',
            'jenis_sub_defect' => 'OTHER',
            'quantity'         => 99,
        ]);

        // Day 2: Internal 2 pcs, Rework 3 pcs (1 harness) -> total 5
        Defect::create([
            'waktu'            => $day2,
            'user_name'        => 'op QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'FA Inspection',
            'rework_group_id'  => null,
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 2,
            'inspect_quantity' => 300,
        ]);

        $group3 = (string) Str::uuid();
        Defect::create([
            'waktu'            => $day2,
            'user_name'        => 'rw QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => $group3,
            'kanban_assy'      => 'KB-3',
            'jenis_defect'     => 'HOUSING',
            'jenis_sub_defect' => 'CRACK',
            'quantity'         => 3,
        ]);

        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?mode=all&assy=final&start_date=2026-05-10&end_date=2026-05-11&group_by=day');
        $res->assertStatus(200);

        $points = $res->json('dpm_final');
        $this->assertCount(2, $points);

        // Bucket Day 1
        $b1 = $points[0];
        $this->assertEquals('2026-05-10', $b1['label']);
        $this->assertEquals(5, $b1['def_internal']);
        $this->assertEquals(7, $b1['def_rework']);
        $this->assertEquals(12, $b1['total_defect']);
        $this->assertEquals($b1['def_internal'] + $b1['def_rework'], $b1['total_defect']);
        $this->assertEquals(2, $b1['harness_rework']);

        // Bucket Day 2
        $b2 = $points[1];
        $this->assertEquals('2026-05-11', $b2['label']);
        $this->assertEquals(2, $b2['def_internal']);
        $this->assertEquals(3, $b2['def_rework']);
        $this->assertEquals(5, $b2['total_defect']);
        $this->assertEquals($b2['def_internal'] + $b2['def_rework'], $b2['total_defect']);
        $this->assertEquals(1, $b2['harness_rework']);
    }

    /**
     * Skenario c: Mode 'all' tidak mengirim DPM gabungan Final Assy (dpm null).
     */
    public function test_scenario_c_mode_all_does_not_send_combined_dpm_for_final_assy_dpm_null(): void
    {
        AreaDitemukan::create(['name' => 'FA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Rework', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        Defect::create([
            'waktu'            => '2026-05-10 10:00:00',
            'user_name'        => 'op QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'FA Inspection',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 10,
            'inspect_quantity' => 1000,
        ]);

        Defect::create([
            'waktu'            => '2026-05-10 11:00:00',
            'user_name'        => 'rw QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-RWK',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'quantity'         => 5,
        ]);

        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?mode=all&assy=final&start_date=2026-05-10&end_date=2026-05-10&group_by=day');
        $res->assertStatus(200);

        $points = $res->json('dpm_final');
        $this->assertNotEmpty($points);
        foreach ($points as $p) {
            $this->assertNull($p['dpm']);
            $this->assertNull($p['target']);
        }

        $combined = $res->json('dpm_combined');
        $this->assertNotEmpty($combined);
        foreach ($combined as $p) {
            $this->assertNull($p['dpm']);
            $this->assertNull($p['target']);
        }
    }

    /**
     * Skenario d: Pre Assy mode 'all' tetap menghitung DPM internal seperti sebelumnya.
     */
    public function test_scenario_d_pre_assy_mode_all_calculates_internal_dpm_normally(): void
    {
        AreaDitemukan::create(['name' => 'PA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        $carline = Carline::create(['name' => 'Line 1']);

        DpmTarget::create([
            'jenis_assy'   => 'Pre Assy',
            'carline_id'   => $carline->id,
            'period_start' => '2026-05-01',
            'period_end'   => '2026-05-31',
            'target_dpm'   => 45.0,
        ]);

        Defect::create([
            'waktu'            => '2026-05-10 09:00:00',
            'user_name'        => 'operator PA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'area_ditemukan'   => 'PA Inspection',
            'rework_group_id'  => null,
            'jenis_defect'     => 'CRIMP',
            'jenis_sub_defect' => 'CRIMP DEFECT',
            'quantity'         => 5,
            'inspect_quantity' => 1000,
        ]);

        // Request Pre Assy under mode 'all'
        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?mode=all&assy=pre&start_date=2026-05-10&end_date=2026-05-10&group_by=day&carline_id=' . $carline->id);
        $res->assertStatus(200);

        $points = $res->json('dpm_pre');
        $this->assertNotNull($points);
        $this->assertCount(1, $points);

        $p = $points[0];
        // DPM Pre Assy: (5 / 1000) * 1.000.000 = 5000.0
        $this->assertEquals(5000.0, $p['dpm']);
        $this->assertEquals(45.0, $p['target']);
        $this->assertEquals(5, $p['total_defect']);
        $this->assertEquals(1000, $p['total_inspect']);
    }

    /**
     * Skenario e: Pareto gabungan konsisten dengan total di poin b.
     */
    public function test_scenario_e_combined_pareto_consistent_with_total_defect_in_b(): void
    {
        AreaDitemukan::create(['name' => 'FA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Rework', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        $day = Carbon::parse('2026-05-10 10:00:00');

        // Sub Defect 1: TERMINAL BENT -> Internal 5 pcs, Rework 3 pcs -> total 8
        Defect::create([
            'waktu'            => $day,
            'user_name'        => 'op QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'FA Inspection',
            'rework_group_id'  => null,
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'TERMINAL BENT',
            'quantity'         => 5,
            'inspect_quantity' => 500,
        ]);

        Defect::create([
            'waktu'            => $day,
            'user_name'        => 'rw QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-A',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'TERMINAL BENT',
            'quantity'         => 3,
        ]);

        // Sub Defect 2: WIRE CUT -> Internal 2 pcs, Rework 4 pcs -> total 6
        Defect::create([
            'waktu'            => $day,
            'user_name'        => 'op QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'FA Inspection',
            'rework_group_id'  => null,
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'WIRE CUT',
            'quantity'         => 2,
            'inspect_quantity' => 200,
        ]);

        Defect::create([
            'waktu'            => $day,
            'user_name'        => 'rw QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-B',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'WIRE CUT',
            'quantity'         => 4,
        ]);

        // Baris mismatch: 99 pcs (harus tidak masuk pareto)
        Defect::create([
            'waktu'            => $day,
            'user_name'        => 'op QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => null,
            'jenis_defect'     => 'OTHER',
            'jenis_sub_defect' => 'OTHER MISMATCH',
            'quantity'         => 99,
        ]);

        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?mode=all&assy=final&start_date=2026-05-10&end_date=2026-05-10&group_by=day');
        $res->assertStatus(200);

        // Cek total bucket
        $bucket = $res->json('dpm_final')[0];
        $this->assertEquals(7, $bucket['def_internal']); // 5 + 2
        $this->assertEquals(7, $bucket['def_rework']);   // 3 + 4
        $this->assertEquals(14, $bucket['total_defect']);

        // Cek pareto
        $pareto = $res->json('pareto_final');
        $this->assertCount(2, $pareto);

        $paretoTotalSum = array_sum(array_column($pareto, 'total'));
        $this->assertEquals($bucket['total_defect'], $paretoTotalSum);
        $this->assertEquals(14, $paretoTotalSum);

        // Rank 1: TERMINAL BENT (total 8: 5 internal, 3 rework) -> 8/14 = 57.14%
        $this->assertEquals('TERMINAL BENT', $pareto[0]['name']);
        $this->assertEquals(8, $pareto[0]['total']);
        $this->assertEquals(5, $pareto[0]['internal_qty']);
        $this->assertEquals(3, $pareto[0]['rework_qty']);
        $this->assertEquals(57.14, $pareto[0]['cumulative_percent']);

        // Rank 2: WIRE CUT (total 6: 2 internal, 4 rework) -> cumulative 100.0%
        $this->assertEquals('WIRE CUT', $pareto[1]['name']);
        $this->assertEquals(6, $pareto[1]['total']);
        $this->assertEquals(2, $pareto[1]['internal_qty']);
        $this->assertEquals(4, $pareto[1]['rework_qty']);
        $this->assertEquals(100.0, $pareto[1]['cumulative_percent']);

        // Drilldown mode 'all' untuk sub defect TERMINAL BENT
        $ddRes = $this->actingAsAdmin()->getJson('/dashboard/pareto-drilldown?mode=all&jenis_assy=Final+Assy&sub_defect=TERMINAL+BENT&start_date=2026-05-10&end_date=2026-05-10');
        $ddRes->assertStatus(200);
        $drilldownItems = $ddRes->json('items');
        $drilldownSum = array_sum(array_column($drilldownItems, 'total'));
        $this->assertEquals(8, $drilldownSum);
    }
}
