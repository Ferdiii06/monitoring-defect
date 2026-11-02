<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\DpmTarget;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReworkDpmCalculationTest extends TestCase
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
     * Skenario a: 10 group rework, total 14 quantity defect -> DPM 1.400.000.
     */
    public function test_scenario_a_ten_groups_with_fourteen_qty_yields_dpm_one_million_four_hundred_thousand(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        AreaDitemukan::create(['name' => 'AREA-RWK', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        // 8 groups dengan quantity 1 (total 8)
        for ($i = 1; $i <= 8; $i++) {
            Defect::create([
                'user_name'        => 'reworkA',
                'waktu'            => Carbon::now(),
                'jenis_assy'       => 'Final Assy',
                'area_ditemukan'   => 'AREA-RWK',
                'rework_group_id'  => (string) Str::uuid(),
                'kanban_assy'      => 'KB-0' . $i,
                'jenis_defect'     => 'TERMINAL',
                'jenis_sub_defect' => 'BENT',
                'quantity'         => 1,
            ]);
        }

        // 2 groups dengan quantity 3 (total 6) -> total 8 + 6 = 14
        for ($i = 9; $i <= 10; $i++) {
            Defect::create([
                'user_name'        => 'reworkA',
                'waktu'            => Carbon::now(),
                'jenis_assy'       => 'Final Assy',
                'area_ditemukan'   => 'AREA-RWK',
                'rework_group_id'  => (string) Str::uuid(),
                'kanban_assy'      => 'KB-0' . $i,
                'jenis_defect'     => 'WIRE',
                'jenis_sub_defect' => 'CUT',
                'quantity'         => 3,
            ]);
        }

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=rework&assy=final&start_date={$today}&end_date={$today}&group_by=day");
        $res->assertStatus(200);

        $points = $res->json('dpm_final');
        $this->assertCount(1, $points);
        $this->assertEquals(14, $points[0]['total_defect']);
        $this->assertEquals(10, $points[0]['total_harness_rework']);
        $this->assertEquals(1400000.0, $points[0]['dpm']);
        $this->assertNull($points[0]['target']);
    }

    /**
     * Skenario b: 1 group dengan 3 baris defect -> penyebut tetap 1.
     */
    public function test_scenario_b_one_group_with_three_defects_has_denominator_one(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        AreaDitemukan::create(['name' => 'AREA-RWK', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        $groupId = (string) Str::uuid();

        Defect::create([
            'user_name'        => 'reworkA',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-RWK',
            'rework_group_id'  => $groupId,
            'kanban_assy'      => 'KB-MULTI',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 1,
        ]);

        Defect::create([
            'user_name'        => 'reworkA',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-RWK',
            'rework_group_id'  => $groupId,
            'kanban_assy'      => 'KB-MULTI',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'quantity'         => 1,
        ]);

        Defect::create([
            'user_name'        => 'reworkA',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-RWK',
            'rework_group_id'  => $groupId,
            'kanban_assy'      => 'KB-MULTI',
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'BROKEN',
            'quantity'         => 1,
        ]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=rework&assy=final&start_date={$today}&end_date={$today}&group_by=day");
        $res->assertStatus(200);

        $points = $res->json('dpm_final');
        $this->assertCount(1, $points);
        $this->assertEquals(3, $points[0]['total_defect']);
        $this->assertEquals(1, $points[0]['total_harness_rework']);
        $this->assertEquals(3000000.0, $points[0]['dpm']);
    }

    /**
     * Skenario c: Baris rework dengan area internal (mismatch) tidak masuk pembilang maupun penyebut.
     */
    public function test_scenario_c_mismatch_rework_with_internal_area_excluded_from_numerator_and_denominator(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        AreaDitemukan::create(['name' => 'AREA-RWK', 'tipe' => AreaDitemukan::TIPE_REWORK]);
        AreaDitemukan::create(['name' => 'AREA-INT', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        // Group 1: Valid rework
        Defect::create([
            'user_name'        => 'reworkA',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-RWK',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-VALID',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 2,
        ]);

        // Group 2: Mismatch rework (area bertipe internal_defect)
        Defect::create([
            'user_name'        => 'reworkA',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-INT',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-MISMATCH',
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'BROKEN',
            'quantity'         => 5,
        ]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=rework&assy=final&start_date={$today}&end_date={$today}&group_by=day");
        $res->assertStatus(200);

        $points = $res->json('dpm_final');
        $this->assertCount(1, $points);
        $this->assertEquals(2, $points[0]['total_defect']); // Group 2 (5 qty) diabaikan
        $this->assertEquals(1, $points[0]['total_harness_rework']); // Group 2 diabaikan
        $this->assertEquals(2000000.0, $points[0]['dpm']); // (2 / 1) * 1.000.000
    }

    /**
     * Skenario d: Tidak ada data rework -> dpm null, tidak error.
     */
    public function test_scenario_d_empty_rework_returns_null_dpm_without_error(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        AreaDitemukan::create(['name' => 'AREA-RWK', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=rework&assy=final&start_date={$today}&end_date={$today}&group_by=day");
        $res->assertStatus(200);

        $points = $res->json('dpm_final');
        $this->assertCount(1, $points);
        $this->assertEquals(0, $points[0]['total_defect']);
        $this->assertEquals(0, $points[0]['total_harness_rework']);
        $this->assertNull($points[0]['dpm']);
        $this->assertNull($points[0]['target']);
    }

    /**
     * Skenario e: Tren per bucket: dua hari berbeda dihitung terpisah.
     */
    public function test_scenario_e_trend_buckets_calculate_dpm_independently_per_day(): void
    {
        AreaDitemukan::create(['name' => 'AREA-RWK', 'tipe' => AreaDitemukan::TIPE_REWORK]);

        $yesterday = Carbon::yesterday()->startOfDay()->addHours(10);
        $today = Carbon::today()->startOfDay()->addHours(10);

        // Hari 1 (Kemarin): 1 group, qty 2 -> DPM = (2 / 1) * 1.000.000 = 2.000.000
        Defect::create([
            'user_name'        => 'reworkA',
            'waktu'            => $yesterday,
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-RWK',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-YESTERDAY',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 2,
        ]);

        // Hari 2 (Hari ini): 2 group, masing-masing qty 1 (total qty 2) -> DPM = (2 / 2) * 1.000.000 = 1.000.000
        Defect::create([
            'user_name'        => 'reworkA',
            'waktu'            => $today,
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-RWK',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-TODAY-1',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'quantity'         => 1,
        ]);

        Defect::create([
            'user_name'        => 'reworkA',
            'waktu'            => $today->copy()->addMinutes(30),
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-RWK',
            'rework_group_id'  => (string) Str::uuid(),
            'kanban_assy'      => 'KB-TODAY-2',
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'BROKEN',
            'quantity'         => 1,
        ]);

        $startDate = Carbon::yesterday()->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=rework&assy=final&start_date={$startDate}&end_date={$endDate}&group_by=day");
        $res->assertStatus(200);

        $points = $res->json('dpm_final');
        $this->assertCount(2, $points);

        // Bucket Kemarin
        $this->assertEquals(2, $points[0]['total_defect']);
        $this->assertEquals(1, $points[0]['total_harness_rework']);
        $this->assertEquals(2000000.0, $points[0]['dpm']);

        // Bucket Hari Ini
        $this->assertEquals(2, $points[1]['total_defect']);
        $this->assertEquals(2, $points[1]['total_harness_rework']);
        $this->assertEquals(1000000.0, $points[1]['dpm']);
    }

    /**
     * Skenario f: DPM internal tidak berubah dibanding sebelum batch ini.
     */
    public function test_scenario_f_internal_dpm_calculation_remains_unchanged(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        AreaDitemukan::create(['name' => 'AREA-INT', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'period_end'   => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'target_dpm'   => 50.00,
        ]);

        Defect::create([
            'user_name'        => 'Operator QA',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-INT',
            'rework_group_id'  => null,
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 5,
            'inspect_quantity' => 1000,
        ]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=internal&assy=final&start_date={$today}&end_date={$today}&group_by=day");
        $res->assertStatus(200);

        $points = $res->json('dpm_final');
        $this->assertCount(1, $points);
        $this->assertEquals(5, $points[0]['total_defect']);
        $this->assertEquals(1000, $points[0]['total_inspect']);
        $this->assertEquals(5000.0, $points[0]['dpm']); // (5 / 1000) * 1.000.000
        $this->assertEquals(50.0, $points[0]['target']);
    }
}

