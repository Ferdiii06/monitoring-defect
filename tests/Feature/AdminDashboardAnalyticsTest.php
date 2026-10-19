<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Defect;
use App\Models\Carline;
use App\Models\DpmTarget;
use Carbon\Carbon;

class AdminDashboardAnalyticsTest extends TestCase
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
            'user_role' => 'Operator',
        ]);
    }

    public function test_guest_cannot_access_analytics(): void
    {
        $response = $this->get('/dashboard/analytics');
        $response->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_analytics(): void
    {
        $response = $this->actingAsOperator()->get('/dashboard/analytics');
        $response->assertRedirect(route('operator.home'));
    }

    public function test_admin_can_access_and_json_structure(): void
    {
        $response = $this->actingAsAdmin()->getJson('/dashboard/analytics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'dpm_final',
            'pareto_final',
            'dpm_pre',
            'pareto_pre',
        ]);
    }

    public function test_dpm_calculation_correctness_and_null_inspect(): void
    {
        // Hari 1: 2 defect, 1000 inspect -> DPM = (2 / 1000) * 1.000.000 = 2000.00
        Defect::create([
            'waktu'            => Carbon::today()->setTime(9, 0),
            'user_name'        => 'Operator A',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'BROKEN PIN',
            'quantity'         => 2,
            'inspect_quantity' => 1000,
        ]);

        // Hari 2: 1 defect, 0 inspect -> DPM harus null (bukan 0)
        Defect::create([
            'waktu'            => Carbon::today()->subDays(1)->setTime(10, 0),
            'user_name'        => 'Operator A',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'BROKEN PIN',
            'quantity'         => 1,
            'inspect_quantity' => 0,
        ]);

        $response = $this->actingAsAdmin()->getJson('/dashboard/analytics?group_by=day');

        $response->assertStatus(200);
        $dpmFinal = $response->json('dpm_final');

        $this->assertCount(2, $dpmFinal);

        // Hari dengan inspect = 0 menghasilkan DPM = null
        $this->assertEquals(Carbon::today()->subDays(1)->format('Y-m-d'), $dpmFinal[0]['label']);
        $this->assertEquals(1, $dpmFinal[0]['total_defect']);
        $this->assertEquals(0, $dpmFinal[0]['total_inspect']);
        $this->assertNull($dpmFinal[0]['dpm']);

        // Hari dengan inspect = 1000 menghasilkan DPM = 2000
        $this->assertEquals(Carbon::today()->format('Y-m-d'), $dpmFinal[1]['label']);
        $this->assertEquals(2, $dpmFinal[1]['total_defect']);
        $this->assertEquals(1000, $dpmFinal[1]['total_inspect']);
        $this->assertEquals(2000.0, $dpmFinal[1]['dpm']);
    }

    public function test_group_by_week_and_month_aggregates_before_calculating_ratio(): void
    {
        // Gunakan 2 hari di minggu lalu (Senin & Selasa) yang pasti berada di dalam default 30 hari
        $monday = Carbon::today()->subDays(14)->startOfWeek(Carbon::MONDAY)->setTime(8, 0);
        $tuesday = $monday->copy()->addDay()->setTime(9, 0);

        Defect::create([
            'waktu'            => $monday,
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 1,
            'inspect_quantity' => 1000,
        ]);

        Defect::create([
            'waktu'            => $tuesday,
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 1,
            'inspect_quantity' => 3000,
        ]);

        $response = $this->actingAsAdmin()->getJson('/dashboard/analytics?group_by=week');

        $response->assertStatus(200);
        $dpmFinal = $response->json('dpm_final');

        $this->assertCount(1, $dpmFinal);
        $this->assertEquals($monday->format('Y-m-d'), $dpmFinal[0]['label']);
        $this->assertEquals(2, $dpmFinal[0]['total_defect']);
        $this->assertEquals(4000, $dpmFinal[0]['total_inspect']);
        $this->assertEquals(500.0, $dpmFinal[0]['dpm']);
        $this->assertNotEquals(666.67, $dpmFinal[0]['dpm']);

        // Uji group_by=month juga
        $responseMonth = $this->actingAsAdmin()->getJson('/dashboard/analytics?group_by=month');
        $responseMonth->assertStatus(200);
        $dpmMonth = $responseMonth->json('dpm_final');
        $this->assertCount(1, $dpmMonth);
        $this->assertEquals($monday->format('Y-m'), $dpmMonth[0]['label']);
        $this->assertEquals(2, $dpmMonth[0]['total_defect']);
        $this->assertEquals(4000, $dpmMonth[0]['total_inspect']);
        $this->assertEquals(500.0, $dpmMonth[0]['dpm']);
    }

    public function test_zero_defect_excluded_from_pareto_and_normalization(): void
    {
        // Zero Defect: quantity 0, jenis_defect 'Zero Defect' -> harus diabaikan di Pareto
        Defect::create([
            'waktu'            => Carbon::today()->setTime(8, 0),
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
            'inspect_quantity' => 100,
        ]);

        // Defect dengan variasi penulisan (lowercase + trailing space)
        Defect::create([
            'waktu'            => Carbon::today()->setTime(9, 0),
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'INSERT CIRCUIT',
            'jenis_sub_defect' => 'cross circuit  ',
            'quantity'         => 3,
            'inspect_quantity' => 100,
        ]);

        // Defect sama dengan uppercase
        Defect::create([
            'waktu'            => Carbon::today()->setTime(10, 0),
            'user_name'        => 'Operator B',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'INSERT CIRCUIT',
            'jenis_sub_defect' => 'CROSS CIRCUIT',
            'quantity'         => 2,
            'inspect_quantity' => 100,
        ]);

        // Defect lain
        Defect::create([
            'waktu'            => Carbon::today()->setTime(11, 0),
            'user_name'        => 'Operator C',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'BROKEN CLIP',
            'quantity'         => 5,
            'inspect_quantity' => 100,
        ]);

        $response = $this->actingAsAdmin()->getJson('/dashboard/analytics');

        $response->assertStatus(200);
        $pareto = $response->json('pareto_final');

        // Pareto hanya berisi 2 kategori normal: 'BROKEN CLIP' (5) dan 'CROSS CIRCUIT' (3+2=5)
        $this->assertCount(2, $pareto);

        $names = array_column($pareto, 'name');
        $this->assertNotContains('ZERO DEFECT', $names);
        $this->assertNotContains('-', $names);
        $this->assertContains('CROSS CIRCUIT', $names);
        $this->assertContains('BROKEN CLIP', $names);

        // Kedua item total 5, grand total = 10 -> kumulatif: item 1 = 50%, item 2 = 100%
        $this->assertEquals(50.0, $pareto[0]['cumulative_percent']);
        $this->assertEquals(100.0, $pareto[1]['cumulative_percent']);
    }

    public function test_filters_conveyor_and_carline_work_independently(): void
    {
        $carline1 = Carline::create(['name' => 'PRE-CARLINE-A']);
        $carline2 = Carline::create(['name' => 'PRE-CARLINE-B']);

        // Final Assy records
        Defect::create([
            'waktu'            => Carbon::today()->setTime(8, 0),
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-ALPHA',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'SCRATCH',
            'quantity'         => 10,
            'inspect_quantity' => 1000,
        ]);

        Defect::create([
            'waktu'            => Carbon::today()->setTime(9, 0),
            'user_name'        => 'Operator B',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-BETA',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 20,
            'inspect_quantity' => 2000,
        ]);

        // Pre Assy records
        Defect::create([
            'waktu'            => Carbon::today()->setTime(8, 30),
            'user_name'        => 'Operator C',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline1->id,
            'jenis_defect'     => 'CORE',
            'jenis_sub_defect' => 'FRAYING',
            'quantity'         => 5,
            'inspect_quantity' => 500,
        ]);

        Defect::create([
            'waktu'            => Carbon::today()->setTime(9, 30),
            'user_name'        => 'Operator D',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline2->id,
            'jenis_defect'     => 'CORE',
            'jenis_sub_defect' => 'CUT CORE',
            'quantity'         => 15,
            'inspect_quantity' => 1500,
        ]);

        // 1. Filter conveyor = CONV-ALPHA: Final Assy harus hanya 10, Pre Assy tetap lengkap (5 + 15 = 20)
        $response1 = $this->actingAsAdmin()->getJson('/dashboard/analytics?conveyor=CONV-ALPHA');
        $response1->assertStatus(200);

        $dpmFinal1 = $response1->json('dpm_final');
        $dpmPre1 = $response1->json('dpm_pre');

        $this->assertEquals(10, $dpmFinal1[0]['total_defect']);
        $this->assertEquals(1000, $dpmFinal1[0]['total_inspect']);

        $this->assertEquals(20, $dpmPre1[0]['total_defect']);
        $this->assertEquals(2000, $dpmPre1[0]['total_inspect']);

        // 2. Filter carline_id = $carline1->id: Pre Assy harus hanya 5, Final Assy tetap lengkap (10 + 20 = 30)
        $response2 = $this->actingAsAdmin()->getJson('/dashboard/analytics?carline_id=' . $carline1->id);
        $response2->assertStatus(200);

        $dpmFinal2 = $response2->json('dpm_final');
        $dpmPre2 = $response2->json('dpm_pre');

        $this->assertEquals(30, $dpmFinal2[0]['total_defect']);
        $this->assertEquals(3000, $dpmFinal2[0]['total_inspect']);

        $this->assertEquals(5, $dpmPre2[0]['total_defect']);
        $this->assertEquals(500, $dpmPre2[0]['total_inspect']);
    }

    public function test_dpm_targets_matching_isolation_and_transitions(): void
    {
        // 1. Buat 2 target berurutan untuk Final Assy:
        // Target A: 1 Jan 2026 s/d 15 Jan 2026 -> target = 50.00
        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-01-01',
            'period_end'   => '2026-01-15',
            'target_dpm'   => 50.00,
        ]);

        // Target B: 16 Jan 2026 s/d 31 Jan 2026 -> target = 30.00
        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-01-16',
            'period_end'   => '2026-01-31',
            'target_dpm'   => 30.00,
        ]);

        // 2. Buat target untuk Pre Assy (hanya 1 Jan s/d 15 Jan) -> target = 80.00
        DpmTarget::create([
            'jenis_assy'   => 'Pre Assy',
            'period_start' => '2026-01-01',
            'period_end'   => '2026-01-15',
            'target_dpm'   => 80.00,
        ]);

        // 3. Buat defect data untuk Final Assy:
        // Titik 1: 10 Jan 2026 (dalam rentang Target A)
        Defect::create([
            'waktu'            => Carbon::parse('2026-01-10 09:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 1,
            'inspect_quantity' => 100,
        ]);

        // Titik 2: 20 Jan 2026 (dalam rentang Target B - perpindahan target)
        Defect::create([
            'waktu'            => Carbon::parse('2026-01-20 09:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 2,
            'inspect_quantity' => 100,
        ]);

        // Titik 3: 05 Feb 2026 (di luar semua rentang target)
        Defect::create([
            'waktu'            => Carbon::parse('2026-02-05 09:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 1,
            'inspect_quantity' => 100,
        ]);

        // 4. Buat defect data untuk Pre Assy:
        // Titik Pre 1: 10 Jan 2026 (dalam rentang target Pre Assy 80.00, BUKAN 50.00)
        Defect::create([
            'waktu'            => Carbon::parse('2026-01-10 09:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 2,
            'inspect_quantity' => 100,
        ]);

        // Titik Pre 2: 20 Jan 2026 (di luar rentang target Pre Assy -> harus NULL, tidak boleh bocor dari Final Assy yang 30.00)
        Defect::create([
            'waktu'            => Carbon::parse('2026-01-20 09:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 1,
            'inspect_quantity' => 100,
        ]);

        // Request analytics rentang 2026-01-01 s/d 2026-02-10
        $response = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-01-01&end_date=2026-02-10&group_by=day');

        $response->assertStatus(200);
        $dpmFinal = $response->json('dpm_final');
        $dpmPre = $response->json('dpm_pre');

        $this->assertCount(3, $dpmFinal);
        // Titik 1 Final Assy (10 Jan 2026): target = 50.0
        $this->assertEquals('2026-01-10', $dpmFinal[0]['label']);
        $this->assertEquals(50.0, $dpmFinal[0]['target']);

        // Titik 2 Final Assy (20 Jan 2026): target = 30.0 (perpindahan target menghasilkan nilai berbeda)
        $this->assertEquals('2026-01-20', $dpmFinal[1]['label']);
        $this->assertEquals(30.0, $dpmFinal[1]['target']);

        // Titik 3 Final Assy (05 Feb 2026): di luar target = null
        $this->assertEquals('2026-02-05', $dpmFinal[2]['label']);
        $this->assertNull($dpmFinal[2]['target']);

        // Pre Assy:
        $this->assertCount(2, $dpmPre);
        // Titik 1 Pre Assy (10 Jan 2026): target = 80.0 (isolasi target jenis_assy)
        $this->assertEquals('2026-01-10', $dpmPre[0]['label']);
        $this->assertEquals(80.0, $dpmPre[0]['target']);

        // Titik 2 Pre Assy (20 Jan 2026): target = null (tidak bocor dari Final Assy 30.0)
        $this->assertEquals('2026-01-20', $dpmPre[1]['label']);
        $this->assertNull($dpmPre[1]['target']);
    }

    public function test_analytics_with_assy_parameter(): void
    {
        $carline1 = Carline::create(['name' => 'CARLINE-1']);
        $carline2 = Carline::create(['name' => 'CARLINE-2']);

        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-03-01',
            'period_end'   => '2026-03-31',
            'target_dpm'   => 45.00,
        ]);

        DpmTarget::create([
            'jenis_assy'   => 'Pre Assy',
            'period_start' => '2026-03-01',
            'period_end'   => '2026-03-31',
            'target_dpm'   => 65.00,
        ]);

        // Final Assy defect
        Defect::create([
            'waktu'            => Carbon::parse('2026-03-10 10:00:00'),
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-A',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 3,
            'inspect_quantity' => 100,
        ]);

        // Pre Assy defect
        Defect::create([
            'waktu'            => Carbon::parse('2026-03-10 10:00:00'),
            'user_name'        => 'Operator B',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline1->id,
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'WIRE',
            'quantity'         => 5,
            'inspect_quantity' => 100,
        ]);

        // 1. Uji assy=final:
        // Kirim carline_id=$carline2->id (filter silang yang harus diabaikan oleh Final Assy)
        $resFinal = $this->actingAsAdmin()->getJson('/dashboard/analytics?assy=final&start_date=2026-03-01&end_date=2026-03-31&carline_id=' . $carline2->id . '&conveyor=CONV-A');
        $resFinal->assertStatus(200);

        // Hanya mengembalikan dpm_final dan pareto_final
        $jsonFinal = $resFinal->json();
        $this->assertArrayHasKey('dpm_final', $jsonFinal);
        $this->assertArrayHasKey('pareto_final', $jsonFinal);
        $this->assertArrayNotHasKey('dpm_pre', $jsonFinal);
        $this->assertArrayNotHasKey('pareto_pre', $jsonFinal);

        // Key target tetap ada dan benar
        $this->assertCount(1, $jsonFinal['dpm_final']);
        $this->assertEquals(45.0, $jsonFinal['dpm_final'][0]['target']);
        $this->assertEquals(3, $jsonFinal['dpm_final'][0]['total_defect']);

        // 2. Uji assy=pre:
        // Kirim conveyor=CONV-NONEXISTENT (filter silang yang harus diabaikan oleh Pre Assy)
        $resPre = $this->actingAsAdmin()->getJson('/dashboard/analytics?assy=pre&start_date=2026-03-01&end_date=2026-03-31&conveyor=CONV-NONEXISTENT&carline_id=' . $carline1->id);
        $resPre->assertStatus(200);

        // Hanya mengembalikan dpm_pre dan pareto_pre
        $jsonPre = $resPre->json();
        $this->assertArrayHasKey('dpm_pre', $jsonPre);
        $this->assertArrayHasKey('pareto_pre', $jsonPre);
        $this->assertArrayNotHasKey('dpm_final', $jsonPre);
        $this->assertArrayNotHasKey('pareto_final', $jsonPre);

        // Key target tetap ada dan benar
        $this->assertCount(1, $jsonPre['dpm_pre']);
        $this->assertEquals(65.0, $jsonPre['dpm_pre'][0]['target']);
        $this->assertEquals(5, $jsonPre['dpm_pre'][0]['total_defect']);
    }
}
