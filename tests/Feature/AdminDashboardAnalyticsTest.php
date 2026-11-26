<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Defect;
use App\Models\Carline;
use App\Models\CarType;
use App\Models\DpmTarget;
use App\Models\AreaDitemukan;
use App\Support\ConveyorGroup;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AdminDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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
            'logged_in' => true,
            'user_name' => 'Operator 1',
            'user_role' => 'User',
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

        $startDate = Carbon::today()->subDays(1)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');
        $response = $this->actingAsAdmin()->getJson("/dashboard/analytics?group_by=day&start_date={$startDate}&end_date={$endDate}");

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

        $startDateWeek = $monday->format('Y-m-d');
        $endDateWeek = $monday->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
        $response = $this->actingAsAdmin()->getJson("/dashboard/analytics?group_by=week&start_date={$startDateWeek}&end_date={$endDateWeek}");

        $response->assertStatus(200);
        $dpmFinal = $response->json('dpm_final');

        $this->assertCount(1, $dpmFinal);
        $this->assertEquals($monday->format('Y-m-d'), $dpmFinal[0]['label']);
        $this->assertEquals(2, $dpmFinal[0]['total_defect']);
        $this->assertEquals(4000, $dpmFinal[0]['total_inspect']);
        $this->assertEquals(500.0, $dpmFinal[0]['dpm']);
        $this->assertNotEquals(666.67, $dpmFinal[0]['dpm']);

        // Uji group_by=month juga
        $startDateMonth = $monday->copy()->startOfMonth()->format('Y-m-d');
        $endDateMonth = $monday->copy()->endOfMonth()->format('Y-m-d');
        $responseMonth = $this->actingAsAdmin()->getJson("/dashboard/analytics?group_by=month&start_date={$startDateMonth}&end_date={$endDateMonth}");
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
        $todayStr = Carbon::today()->format('Y-m-d');
        $response1 = $this->actingAsAdmin()->getJson("/dashboard/analytics?conveyor=CONV-ALPHA&start_date={$todayStr}&end_date={$todayStr}");
        $response1->assertStatus(200);

        $dpmFinal1 = $response1->json('dpm_final');
        $dpmPre1 = $response1->json('dpm_pre');

        $this->assertEquals(10, $dpmFinal1[0]['total_defect']);
        $this->assertEquals(1000, $dpmFinal1[0]['total_inspect']);

        $this->assertEquals(20, $dpmPre1[0]['total_defect']);
        $this->assertEquals(2000, $dpmPre1[0]['total_inspect']);

        // 2. Filter carline_id = $carline1->id: Pre Assy harus hanya 5, Final Assy tetap lengkap (10 + 20 = 30)
        $response2 = $this->actingAsAdmin()->getJson("/dashboard/analytics?carline_id={$carline1->id}&start_date={$todayStr}&end_date={$todayStr}");
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

        $this->assertCount(41, $dpmFinal);
        // Titik 1 Final Assy (10 Jan 2026): target = 50.0
        $point1 = collect($dpmFinal)->firstWhere('label', '2026-01-10');
        $this->assertNotNull($point1);
        $this->assertEquals(50.0, $point1['target']);

        // Titik 2 Final Assy (20 Jan 2026): target = 30.0 (perpindahan target menghasilkan nilai berbeda)
        $point2 = collect($dpmFinal)->firstWhere('label', '2026-01-20');
        $this->assertNotNull($point2);
        $this->assertEquals(30.0, $point2['target']);

        // Titik 3 Final Assy (05 Feb 2026): di luar target = null
        $point3 = collect($dpmFinal)->firstWhere('label', '2026-02-05');
        $this->assertNotNull($point3);
        $this->assertNull($point3['target']);

        // Pre Assy:
        $this->assertCount(41, $dpmPre);
        // Titik 1 Pre Assy (10 Jan 2026): target = 80.0 (isolasi target jenis_assy)
        $pointPre1 = collect($dpmPre)->firstWhere('label', '2026-01-10');
        $this->assertNotNull($pointPre1);
        $this->assertEquals(80.0, $pointPre1['target']);

        // Titik 2 Pre Assy (20 Jan 2026): target = null (tidak bocor dari Final Assy 30.0)
        $pointPre2 = collect($dpmPre)->firstWhere('label', '2026-01-20');
        $this->assertNotNull($pointPre2);
        $this->assertNull($pointPre2['target']);
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
        $this->assertCount(31, $jsonFinal['dpm_final']);
        $pointFinal = collect($jsonFinal['dpm_final'])->firstWhere('label', '2026-03-10');
        $this->assertNotNull($pointFinal);
        $this->assertEquals(45.0, $pointFinal['target']);
        $this->assertEquals(3, $pointFinal['total_defect']);

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
        $this->assertCount(31, $jsonPre['dpm_pre']);
        $pointPre = collect($jsonPre['dpm_pre'])->firstWhere('label', '2026-03-10');
        $this->assertNotNull($pointPre);
        $this->assertEquals(65.0, $pointPre['target']);
        $this->assertEquals(5, $pointPre['total_defect']);
    }

    public function test_conveyor_group_c5_combines_multiple_carline_c5_but_c5a_is_separate(): void
    {
        $carType = CarType::create(['name' => 'TOYOTA']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '664W-C5']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '023J-C5']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '664W-C5A']);

        // 1. Cek helper ConveyorGroup
        $this->assertEquals('C5', ConveyorGroup::of('664W-C5'));
        $this->assertEquals('C5', ConveyorGroup::of('023J-C5'));
        $this->assertEquals('C5A', ConveyorGroup::of('664W-C5A'));

        // 2. Buat defect untuk 664W-C5, 023J-C5, dan 664W-C5A
        Defect::create([
            'waktu'            => Carbon::today()->setTime(8, 0),
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => '664W-C5',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 3,
            'inspect_quantity' => 100,
        ]);

        Defect::create([
            'waktu'            => Carbon::today()->setTime(9, 0),
            'user_name'        => 'Operator B',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => '023J-C5',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 2,
            'inspect_quantity' => 100,
        ]);

        Defect::create([
            'waktu'            => Carbon::today()->setTime(10, 0),
            'user_name'        => 'Operator C',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => '664W-C5A',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 7,
            'inspect_quantity' => 100,
        ]);

        // Filter group C5: harus menghasilkan 3 + 2 = 5 defect, 200 inspect (tanpa 664W-C5A)
        $todayStr = Carbon::today()->format('Y-m-d');
        $resC5 = $this->actingAsAdmin()->getJson("/dashboard/analytics?conveyor=C5&start_date={$todayStr}&end_date={$todayStr}");
        $resC5->assertStatus(200);
        $dpmC5 = $resC5->json('dpm_final');
        $this->assertEquals(5, $dpmC5[0]['total_defect']);
        $this->assertEquals(200, $dpmC5[0]['total_inspect']);

        // Filter group C5A: harus hanya 7 defect, 100 inspect
        $resC5A = $this->actingAsAdmin()->getJson("/dashboard/analytics?conveyor=C5A&start_date={$todayStr}&end_date={$todayStr}");
        $resC5A->assertStatus(200);
        $dpmC5A = $resC5A->json('dpm_final');
        $this->assertEquals(7, $dpmC5A[0]['total_defect']);
        $this->assertEquals(100, $dpmC5A[0]['total_inspect']);
    }

    public function test_deleted_or_historical_conveyor_appears_in_dropdown_and_is_filterable(): void
    {
        // Konveyor DELETED-C99 tidak ada di master Carline
        Defect::create([
            'waktu'            => Carbon::today()->setTime(8, 0),
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'DELETED-C99.SPECIAL',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 4,
            'inspect_quantity' => 200,
        ]);

        // 1. Cek pada dashboard index: grup 'C99' harus ada di dropdown $finalAssyConveyors
        $resIndex = $this->actingAsAdmin()->get('/dashboard');
        $resIndex->assertStatus(200);
        $resIndex->assertViewHas('finalAssyConveyors', function ($conveyors) {
            return in_array('C99', (array) $conveyors);
        });

        // 2. Cek filter analytics dengan conveyor=C99
        $todayStr = Carbon::today()->format('Y-m-d');
        $resAnalytics = $this->actingAsAdmin()->getJson("/dashboard/analytics?conveyor=C99&start_date={$todayStr}&end_date={$todayStr}");
        $resAnalytics->assertStatus(200);
        $dpm = $resAnalytics->json('dpm_final');
        $this->assertEquals(4, $dpm[0]['total_defect']);
        $this->assertEquals(200, $dpm[0]['total_inspect']);
    }

    public function test_carline_destroy_blocked_when_used_succeeds_when_unused(): void
    {
        $carType = CarType::create(['name' => 'TOYOTA']);
        $usedByConveyor = Carline::create(['car_type_id' => $carType->id, 'name' => 'LINE-USED-CONV']);
        $usedByCarlineId = Carline::create(['car_type_id' => null, 'name' => 'CARLINE-USED-FK']);
        $unusedCarline = Carline::create(['car_type_id' => $carType->id, 'name' => 'LINE-UNUSED']);

        // Defect yang menggunakan nama conveyor
        Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'LINE-USED-CONV',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // Defect yang menggunakan carline_id
        Defect::create([
            'waktu'            => Carbon::today(),
            'user_name'        => 'Operator B',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $usedByCarlineId->id,
            'jenis_defect'     => 'CORE',
            'jenis_sub_defect' => 'FRAYING',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        // 1. Hapus yang dipakai via conveyor -> harus diblokir
        $res1 = $this->actingAsAdmin()->delete("/master/carlines/{$usedByConveyor->id}");
        $res1->assertSessionHasErrors('error');
        $this->assertDatabaseHas('carlines', ['id' => $usedByConveyor->id]);

        // 2. Hapus yang dipakai via carline_id -> harus diblokir
        $res2 = $this->actingAsAdmin()->delete("/master/carlines/{$usedByCarlineId->id}");
        $res2->assertSessionHasErrors('error');
        $this->assertDatabaseHas('carlines', ['id' => $usedByCarlineId->id]);

        // 3. Hapus yang tidak dipakai -> harus berhasil
        $res3 = $this->actingAsAdmin()->delete("/master/carlines/{$unusedCarline->id}");
        $res3->assertRedirect(route('admin.master.carlines.index'));
        $res3->assertSessionHas('success');
        $this->assertDatabaseMissing('carlines', ['id' => $unusedCarline->id]);
    }

    public function test_exact_conveyor_match_fallback_still_works(): void
    {
        Defect::create([
            'waktu'            => Carbon::today()->setTime(8, 0),
            'user_name'        => 'Operator A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CUSTOM-LEGACY-CONV',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 5,
            'inspect_quantity' => 150,
        ]);

        // Request persis dengan nilai nama conveyor lama yang bukan kunci grup
        $todayStr = Carbon::today()->format('Y-m-d');
        $response = $this->actingAsAdmin()->getJson("/dashboard/analytics?conveyor=CUSTOM-LEGACY-CONV&start_date={$todayStr}&end_date={$todayStr}");
        $response->assertStatus(200);

        $dpm = $response->json('dpm_final');
        $this->assertEquals(5, $dpm[0]['total_defect']);
        $this->assertEquals(150, $dpm[0]['total_inspect']);
    }

    public function test_analytics_dpm_target_resolution_specific_fallback_and_default_only_when_all(): void
    {
        $carType = CarType::create(['name' => 'TOYOTA']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '664W-C5']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '664W-C9']);

        $preCarline1 = Carline::create(['car_type_id' => null, 'name' => 'PRE-CARLINE-1']);
        $preCarline2 = Carline::create(['car_type_id' => null, 'name' => 'PRE-CARLINE-2']);

        // Targets:
        // Final Assy: default = 50.0, spesifik C5 = 25.0 (C9 tidak punya target spesifik)
        DpmTarget::create([
            'jenis_assy'     => 'Final Assy',
            'carline_id'     => null,
            'conveyor_group' => null,
            'period_start'   => '2026-05-01',
            'period_end'     => '2026-05-31',
            'target_dpm'     => 50.00,
        ]);
        DpmTarget::create([
            'jenis_assy'     => 'Final Assy',
            'carline_id'     => null,
            'conveyor_group' => 'C5',
            'period_start'   => '2026-05-01',
            'period_end'     => '2026-05-31',
            'target_dpm'     => 25.00,
        ]);

        // Pre Assy: default = 100.0, spesifik PRE-CARLINE-1 = 75.0 (PRE-CARLINE-2 tidak punya target spesifik)
        DpmTarget::create([
            'jenis_assy'     => 'Pre Assy',
            'carline_id'     => null,
            'conveyor_group' => null,
            'period_start'   => '2026-05-01',
            'period_end'     => '2026-05-31',
            'target_dpm'     => 100.00,
        ]);
        DpmTarget::create([
            'jenis_assy'     => 'Pre Assy',
            'carline_id'     => $preCarline1->id,
            'conveyor_group' => null,
            'period_start'   => '2026-05-01',
            'period_end'     => '2026-05-31',
            'target_dpm'     => 75.00,
        ]);

        // Defect data:
        Defect::create([
            'waktu'            => Carbon::parse('2026-05-10 08:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => '664W-C5',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 2,
            'inspect_quantity' => 1000,
        ]);
        Defect::create([
            'waktu'            => Carbon::parse('2026-05-10 09:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => '664W-C9',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 3,
            'inspect_quantity' => 1000,
        ]);
        Defect::create([
            'waktu'            => Carbon::parse('2026-05-10 08:30:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $preCarline1->id,
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'WIRE',
            'quantity'         => 1,
            'inspect_quantity' => 500,
        ]);
        Defect::create([
            'waktu'            => Carbon::parse('2026-05-10 09:30:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $preCarline2->id,
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'WIRE',
            'quantity'         => 2,
            'inspect_quantity' => 500,
        ]);

        // 1. Final Assy saat filter conveyor=C5 aktif -> pakai target spesifik C5 (25.0)
        $resC5 = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-05-01&end_date=2026-05-31&group_by=day&conveyor=C5');
        $resC5->assertStatus(200);
        $dpmC5 = $resC5->json('dpm_final');
        $this->assertCount(31, $dpmC5);
        $this->assertEquals(25.0, collect($dpmC5)->firstWhere('label', '2026-05-10')['target']);

        // 2. Final Assy saat filter conveyor=C9 aktif -> fallback ke target default (50.0)
        $resC9 = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-05-01&end_date=2026-05-31&group_by=day&conveyor=C9');
        $resC9->assertStatus(200);
        $dpmC9 = $resC9->json('dpm_final');
        $this->assertCount(31, $dpmC9);
        $this->assertEquals(50.0, collect($dpmC9)->firstWhere('label', '2026-05-10')['target']);

        // 3. Final Assy saat filter conveyor=all (semua konveyor) -> HANYA pakai target default (50.0)
        $resAllFinal = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-05-01&end_date=2026-05-31&group_by=day&conveyor=all');
        $resAllFinal->assertStatus(200);
        $dpmAllFinal = $resAllFinal->json('dpm_final');
        $this->assertCount(31, $dpmAllFinal);
        $this->assertEquals(50.0, collect($dpmAllFinal)->firstWhere('label', '2026-05-10')['target']);

        // 4. Pre Assy saat filter carline_id spesifik aktif -> pakai target spesifik PRE-CARLINE-1 (75.0)
        $resPre1 = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-05-01&end_date=2026-05-31&group_by=day&carline_id=' . $preCarline1->id);
        $resPre1->assertStatus(200);
        $dpmPre1 = $resPre1->json('dpm_pre');
        $this->assertCount(31, $dpmPre1);
        $this->assertEquals(75.0, collect($dpmPre1)->firstWhere('label', '2026-05-10')['target']);

        // 5. Pre Assy saat filter carline_id=PRE-CARLINE-2 aktif -> fallback ke default (100.0)
        $resPre2 = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-05-01&end_date=2026-05-31&group_by=day&carline_id=' . $preCarline2->id);
        $resPre2->assertStatus(200);
        $dpmPre2 = $resPre2->json('dpm_pre');
        $this->assertCount(31, $dpmPre2);
        $this->assertEquals(100.0, collect($dpmPre2)->firstWhere('label', '2026-05-10')['target']);

        // 6. Pre Assy saat filter carline_id=all -> HANYA pakai target default (100.0)
        $resAllPre = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-05-01&end_date=2026-05-31&group_by=day&carline_id=all');
        $resAllPre->assertStatus(200);
        $dpmAllPre = $resAllPre->json('dpm_pre');
        $this->assertCount(31, $dpmAllPre);
        $this->assertEquals(100.0, collect($dpmAllPre)->firstWhere('label', '2026-05-10')['target']);
    }

    public function test_month_mode_with_future_empty_months_retains_buckets_with_null_dpm_and_matched_target(): void
    {
        // Target Oktober (50.0) dan target November-Desember (35.0)
        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-10-01',
            'period_end'   => '2026-10-31',
            'target_dpm'   => 50.00,
        ]);
        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-11-01',
            'period_end'   => '2026-12-31',
            'target_dpm'   => 35.00,
        ]);

        // Defect data HANYA di bulan Oktober 2026
        Defect::create([
            'waktu'            => Carbon::parse('2026-10-15 10:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 5,
            'inspect_quantity' => 1000,
        ]);

        // Request mode month untuk rentang Okt - Des (3 bulan)
        $response = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-10-01&end_date=2026-12-31&group_by=month&assy=final');
        $response->assertStatus(200);

        $dpmFinal = $response->json('dpm_final');
        $this->assertCount(3, $dpmFinal);

        // Bulan Oktober: memiliki data defect dan inspect
        $this->assertEquals('2026-10', $dpmFinal[0]['label']);
        $this->assertEquals(5, $dpmFinal[0]['total_defect']);
        $this->assertEquals(1000, $dpmFinal[0]['total_inspect']);
        $this->assertEquals(5000.0, $dpmFinal[0]['dpm']);
        $this->assertEquals(50.0, $dpmFinal[0]['target']);

        // Bulan November (kosong): total_defect = 0, total_inspect = 0, dpm = null, target = 35.0
        $this->assertEquals('2026-11', $dpmFinal[1]['label']);
        $this->assertEquals(0, $dpmFinal[1]['total_defect']);
        $this->assertEquals(0, $dpmFinal[1]['total_inspect']);
        $this->assertNull($dpmFinal[1]['dpm']);
        $this->assertEquals(35.0, $dpmFinal[1]['target']);

        // Bulan Desember (kosong): total_defect = 0, total_inspect = 0, dpm = null, target = 35.0
        $this->assertEquals('2026-12', $dpmFinal[2]['label']);
        $this->assertEquals(0, $dpmFinal[2]['total_defect']);
        $this->assertEquals(0, $dpmFinal[2]['total_inspect']);
        $this->assertNull($dpmFinal[2]['dpm']);
        $this->assertEquals(35.0, $dpmFinal[2]['target']);
    }

    public function test_day_and_week_modes_include_empty_buckets_in_chronological_order(): void
    {
        // Defect hanya di hari ke-2 (2026-06-02)
        Defect::create([
            'waktu'            => Carbon::parse('2026-06-02 09:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 2,
            'inspect_quantity' => 500,
        ]);

        // 1. Mode Day (5 hari: 2026-06-01 s/d 2026-06-05)
        $resDay = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-05&group_by=day&assy=final');
        $resDay->assertStatus(200);
        $dpmDay = $resDay->json('dpm_final');

        $this->assertCount(5, $dpmDay);
        $labelsDay = array_column($dpmDay, 'label');
        $this->assertEquals(['2026-06-01', '2026-06-02', '2026-06-03', '2026-06-04', '2026-06-05'], $labelsDay);

        $this->assertNull($dpmDay[0]['dpm']);
        $this->assertEquals(4000.0, $dpmDay[1]['dpm']);
        $this->assertNull($dpmDay[2]['dpm']);
        $this->assertNull($dpmDay[3]['dpm']);
        $this->assertNull($dpmDay[4]['dpm']);

        // 2. Mode Week (3 minggu berurutan: Senin 2026-06-01, 2026-06-08, 2026-06-15)
        $resWeek = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-21&group_by=week&assy=final');
        $resWeek->assertStatus(200);
        $dpmWeek = $resWeek->json('dpm_final');

        $this->assertCount(3, $dpmWeek);
        $labelsWeek = array_column($dpmWeek, 'label');
        $this->assertEquals(['2026-06-01', '2026-06-08', '2026-06-15'], $labelsWeek);

        // Minggu 1 ada data, minggu 2 dan 3 kosong
        $this->assertEquals(4000.0, $dpmWeek[0]['dpm']);
        $this->assertNull($dpmWeek[1]['dpm']);
        $this->assertNull($dpmWeek[2]['dpm']);
    }

    public function test_mid_period_target_matches_in_month_and_week_modes(): void
    {
        // Target mulai di tengah bulan (15-30 November 2026)
        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-11-15',
            'period_end'   => '2026-11-30',
            'target_dpm'   => 42.00,
        ]);

        // Target mulai di tengah minggu (Rabu 2026-11-18 s/d Minggu 2026-11-22)
        DpmTarget::create([
            'jenis_assy'   => 'Pre Assy',
            'period_start' => '2026-11-18',
            'period_end'   => '2026-11-22',
            'target_dpm'   => 77.00,
        ]);

        // Uji mode month untuk Final Assy
        $resMonth = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-11-01&end_date=2026-11-30&group_by=month&assy=final');
        $resMonth->assertStatus(200);
        $dpmMonth = $resMonth->json('dpm_final');
        $this->assertCount(1, $dpmMonth);
        $this->assertEquals('2026-11', $dpmMonth[0]['label']);
        $this->assertEquals(42.0, $dpmMonth[0]['target']);

        // Uji mode week untuk Pre Assy (minggu mulai Senin 2026-11-16)
        $resWeek = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-11-16&end_date=2026-11-22&group_by=week&assy=pre');
        $resWeek->assertStatus(200);
        $dpmWeek = $resWeek->json('dpm_pre');
        $this->assertCount(1, $dpmWeek);
        $this->assertEquals('2026-11-16', $dpmWeek[0]['label']);
        $this->assertEquals(77.0, $dpmWeek[0]['target']);
    }

    public function test_bucket_with_inspect_greater_than_zero_and_zero_defect_yields_zero_dpm(): void
    {
        Defect::create([
            'waktu'            => Carbon::parse('2026-08-10 10:00:00'),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
            'inspect_quantity' => 500,
        ]);

        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-08-10&end_date=2026-08-10&group_by=day&assy=final');
        $res->assertStatus(200);

        $dpmFinal = $res->json('dpm_final');
        $this->assertCount(1, $dpmFinal);
        $this->assertEquals(0, $dpmFinal[0]['total_defect']);
        $this->assertEquals(500, $dpmFinal[0]['total_inspect']);
        // dpm harus numeric 0, BUKAN null
        $this->assertNotNull($dpmFinal[0]['dpm']);
        $this->assertEquals(0.0, $dpmFinal[0]['dpm']);
    }

    public function test_very_long_date_range_does_not_exceed_400_buckets_cap(): void
    {
        // Rentang 500 hari: 2024-01-01 s/d 2025-05-14
        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2024-01-01&end_date=2025-05-14&group_by=day&assy=final');
        $res->assertStatus(200);

        $dpmFinal = $res->json('dpm_final');
        $this->assertCount(400, $dpmFinal);

        // Memastikan bucket terakhir adalah batas akhir rentang
        $this->assertEquals('2025-05-14', end($dpmFinal)['label']);

        // Memastikan kronologis
        $labels = array_column($dpmFinal, 'label');
        $sortedLabels = $labels;
        sort($sortedLabels);
        $this->assertEquals($sortedLabels, $labels);
    }

    public function test_mode_internal_default_dan_mengecualikan_baris_rework(): void
    {
        AreaDitemukan::create(['name' => 'FA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Rework', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        // Defect A (Final Assy internal)
        Defect::create([
            'waktu'            => '2026-06-01 10:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'TERMINAL BENT',
            'area_ditemukan'   => 'FA Inspection',
            'quantity'         => 2,
            'inspect_quantity' => 1000,
        ]);

        // Defect B (Final Assy area null -> internal)
        Defect::create([
            'waktu'            => '2026-06-01 11:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'WIRE SCRATCH',
            'area_ditemukan'   => null,
            'quantity'         => 1,
            'inspect_quantity' => 0,
        ]);

        // Defect C (Final Assy rework)
        Defect::create([
            'waktu'            => '2026-06-01 12:00:00',
            'user_name'        => 'Reworker A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => null,
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'CONNECTOR BROKEN',
            'area_ditemukan'   => 'Area Rework',
            'rework_group_id'  => (string) Str::uuid(),
            'quantity'         => 5,
            'inspect_quantity' => null,
        ]);

        // Defect D (Pre Assy internal)
        Defect::create([
            'waktu'            => '2026-06-01 09:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => null,
            'jenis_defect'     => 'CRIMP',
            'jenis_sub_defect' => 'CRIMP BAD',
            'area_ditemukan'   => 'FA Inspection',
            'quantity'         => 3,
            'inspect_quantity' => 500,
        ]);

        // 1. Tanpa parameter mode (default harus internal)
        $resDefault = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-01&group_by=day');
        $resDefault->assertStatus(200);

        $dpmFinalDef = $resDefault->json('dpm_final');
        $this->assertCount(1, $dpmFinalDef);
        $this->assertEquals(3, $dpmFinalDef[0]['total_defect']); // Defect A (2) + Defect B (1), tanpa Defect C
        $this->assertEquals(3, $dpmFinalDef[0]['total_defect_internal']);
        $this->assertEquals(0, $dpmFinalDef[0]['total_defect_rework']);
        $this->assertEquals(1000, $dpmFinalDef[0]['total_inspect']);
        $this->assertEquals(3000.0, $dpmFinalDef[0]['dpm']); // (3 / 1000) * 1.000.000

        $paretoFinalDef = $resDefault->json('pareto_final');
        $paretoNamesDef = array_column($paretoFinalDef, 'name');
        $this->assertContains('TERMINAL BENT', $paretoNamesDef);
        $this->assertContains('WIRE SCRATCH', $paretoNamesDef);
        $this->assertNotContains('CONNECTOR BROKEN', $paretoNamesDef);

        $dpmPreDef = $resDefault->json('dpm_pre');
        $this->assertCount(1, $dpmPreDef);
        $this->assertEquals(3, $dpmPreDef[0]['total_defect']);
        $this->assertEquals(6000.0, $dpmPreDef[0]['dpm']);

        $paretoPreDef = $resDefault->json('pareto_pre');
        $this->assertCount(1, $paretoPreDef);
        $this->assertEquals('CRIMP BAD', $paretoPreDef[0]['name']);

        // 2. Eksplisit mode=internal (harus identik)
        $resInternal = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-01&group_by=day&mode=internal');
        $resInternal->assertStatus(200);
        $this->assertEquals($resDefault->json(), $resInternal->json());
    }

    public function test_baris_rework_tidak_pernah_mempengaruhi_metrik_dashboard_analytics(): void
    {
        AreaDitemukan::create(['name' => 'FA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        Defect::create([
            'waktu'            => '2026-06-01 10:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'TERMINAL BENT',
            'area_ditemukan'   => 'FA Inspection',
            'quantity'         => 2,
            'inspect_quantity' => 1000,
        ]);

        Defect::create([
            'waktu'            => '2026-06-01 12:00:00',
            'user_name'        => 'Reworker A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => null,
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'CONNECTOR BROKEN',
            'area_ditemukan'   => 'FA Inspection',
            'rework_group_id'  => (string) Str::uuid(),
            'quantity'         => 5,
            'inspect_quantity' => null,
        ]);

        Defect::create([
            'waktu'            => '2026-06-01 09:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => null,
            'jenis_defect'     => 'CRIMP',
            'jenis_sub_defect' => 'CRIMP BAD',
            'area_ditemukan'   => 'FA Inspection',
            'quantity'         => 3,
            'inspect_quantity' => 500,
        ]);

        // Dashboard hanya membaca data operator. Rework dikecualikan dari metrik.
        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-01&group_by=day&mode=total');
        $res->assertStatus(200);

        // Final Assy: hanya Defect A (operator input, rework diabaikan)
        $dpmFinal = $res->json('dpm_final');
        $this->assertCount(1, $dpmFinal);
        $this->assertEquals(2, $dpmFinal[0]['total_defect']);
        $this->assertEquals(2, $dpmFinal[0]['total_defect_internal']);
        $this->assertEquals(0, $dpmFinal[0]['total_defect_rework']);
        $this->assertEquals(2000.0, $dpmFinal[0]['dpm']);

        $paretoFinal = $res->json('pareto_final');
        $this->assertCount(1, $paretoFinal);
        $this->assertEquals('TERMINAL BENT', $paretoFinal[0]['name']);
        $this->assertEquals(2, $paretoFinal[0]['total']);

        // Pre Assy: hanya operator input (Defect Pre Assy = 3)
        $dpmPre = $res->json('dpm_pre');
        $this->assertCount(1, $dpmPre);
        $this->assertEquals(3, $dpmPre[0]['total_defect']);
        $this->assertEquals(6000.0, $dpmPre[0]['dpm']);
    }

    public function test_mode_all_menggabungkan_internal_dan_rework_dengan_dpm_null_dan_breakdown(): void
    {
        AreaDitemukan::create(['name' => 'FA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        Defect::create([
            'waktu'            => '2026-06-01 10:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'TERMINAL BENT',
            'area_ditemukan'   => 'FA Inspection',
            'quantity'         => 2,
            'inspect_quantity' => 1000,
        ]);

        Defect::create([
            'waktu'            => '2026-06-01 12:00:00',
            'user_name'        => 'Reworker A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => null,
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'CONNECTOR BROKEN',
            'area_ditemukan'   => 'FA Inspection',
            'rework_group_id'  => (string) Str::uuid(),
            'quantity'         => 5,
            'inspect_quantity' => null,
        ]);

        Defect::create([
            'waktu'            => '2026-06-01 09:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => null,
            'jenis_defect'     => 'CRIMP',
            'jenis_sub_defect' => 'CRIMP BAD',
            'area_ditemukan'   => 'FA Inspection',
            'quantity'         => 3,
            'inspect_quantity' => 500,
        ]);

        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-01&group_by=day&mode=all');
        $res->assertStatus(200);

        // Di Batch 2: mode all menggabungkan operator Pre (3) + operator Final (2) = 5
        // Rework (5) diabaikan dari dashboard
        $paretoFinal = $res->json('pareto_final');
        $this->assertCount(2, $paretoFinal);

        // Rank 1: CRIMP BAD (3 pcs Pre Assy)
        $this->assertEquals('CRIMP BAD', $paretoFinal[0]['name']);
        $this->assertEquals(3, $paretoFinal[0]['total']);

        // Rank 2: TERMINAL BENT (2 pcs Final Assy)
        $this->assertEquals('TERMINAL BENT', $paretoFinal[1]['name']);
        $this->assertEquals(2, $paretoFinal[1]['total']);
    }

    public function test_master_area_rework_kosong_query_rework_aman_tanpa_error(): void
    {
        Defect::create([
            'waktu'            => '2026-06-01 10:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'TERMINAL BENT',
            'area_ditemukan'   => 'Inspection Line',
            'quantity'         => 2,
            'inspect_quantity' => 1000,
        ]);

        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-01&group_by=day&mode=rework');
        $res->assertStatus(200);

        $dpmFinal = $res->json('dpm_final');
        $this->assertCount(1, $dpmFinal);
        $this->assertEquals(2, $dpmFinal[0]['total_defect']);

        $paretoFinal = $res->json('pareto_final');
        $this->assertCount(1, $paretoFinal);
    }

    public function test_baris_area_ditemukan_null_dan_bukan_rework_masuk_ke_mode_internal(): void
    {
        AreaDitemukan::create(['name' => 'Area Internal A', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        // Baris dengan area null
        Defect::create([
            'waktu'            => '2026-06-01 10:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'DEFECT NULL AREA',
            'area_ditemukan'   => null,
            'quantity'         => 3,
            'inspect_quantity' => 1000,
        ]);

        // Baris dengan area internal
        Defect::create([
            'waktu'            => '2026-06-01 11:00:00',
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'DEFECT INTERNAL AREA',
            'area_ditemukan'   => 'Area Internal A',
            'quantity'         => 4,
            'inspect_quantity' => 0,
        ]);

        // Baris rework
        Defect::create([
            'waktu'            => '2026-06-01 11:30:00',
            'user_name'        => 'Reworker A',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'REWORK DEFECT',
            'area_ditemukan'   => 'Area Internal A',
            'rework_group_id'  => (string) Str::uuid(),
            'quantity'         => 10,
        ]);

        // Mode internal: keduanya masuk (3 + 4 = 7), rework (10) diabaikan
        $resInternal = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-01&group_by=day&mode=internal');
        $resInternal->assertStatus(200);
        $this->assertEquals(7, $resInternal->json('dpm_final.0.total_defect'));
    }

    public function test_pada_data_tanpa_rework_mode_internal_identik_dengan_tanpa_parameter_mode(): void
    {
        // Setup target DPM
        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-06-01',
            'period_end'   => '2026-06-30',
            'target_dpm'   => 150.00,
        ]);
        DpmTarget::create([
            'jenis_assy'   => 'Pre Assy',
            'period_start' => '2026-06-01',
            'period_end'   => '2026-06-30',
            'target_dpm'   => 80.00,
        ]);

        // 12 sub defect untuk Final Assy (menguji Top 10 + kumulatif)
        for ($i = 1; $i <= 12; $i++) {
            Defect::create([
                'waktu'            => '2026-06-01 10:00:00',
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Final Assy',
                'conveyor'         => 'CONV-1',
                'jenis_defect'     => 'DEFECT TYPE',
                'jenis_sub_defect' => "SUB DEFECT {$i}",
                'quantity'         => $i * 2,
                'inspect_quantity' => ($i === 1) ? 5000 : 0,
            ]);
        }

        // 5 sub defect untuk Pre Assy
        for ($j = 1; $j <= 5; $j++) {
            Defect::create([
                'waktu'            => '2026-06-01 09:00:00',
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Pre Assy',
                'carline_id'       => null,
                'jenis_defect'     => 'PRE DEFECT',
                'jenis_sub_defect' => "PRE SUB {$j}",
                'quantity'         => $j * 3,
                'inspect_quantity' => ($j === 1) ? 3000 : 0,
            ]);
        }

        $resWithoutMode = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-01&group_by=day');
        $resWithoutMode->assertStatus(200);

        $resInternal = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-01&end_date=2026-06-01&group_by=day&mode=internal');
        $resInternal->assertStatus(200);

        // Seluruh payload JSON harus identik
        $this->assertEquals($resWithoutMode->json('dpm_final'), $resInternal->json('dpm_final'));
        $this->assertEquals($resWithoutMode->json('dpm_pre'), $resInternal->json('dpm_pre'));
        $this->assertEquals($resWithoutMode->json('pareto_final'), $resInternal->json('pareto_final'));
        $this->assertEquals($resWithoutMode->json('pareto_pre'), $resInternal->json('pareto_pre'));
        $this->assertEquals($resWithoutMode->json(), $resInternal->json());

        // Pastikan Pareto Top 10 & cumulative_percent terhitung dengan benar
        $paretoFinal = $resInternal->json('pareto_final');
        $this->assertCount(10, $paretoFinal);
        $this->assertEquals('SUB DEFECT 12', $paretoFinal[0]['name']);
        $this->assertEquals(24, $paretoFinal[0]['total']);

        // Pastikan terurut menurun dan kumulatif naik hingga mendekati atau sama dengan batas top 10
        for ($k = 0; $k < count($paretoFinal) - 1; $k++) {
            $this->assertGreaterThanOrEqual($paretoFinal[$k + 1]['total'], $paretoFinal[$k]['total']);
            $this->assertLessThanOrEqual($paretoFinal[$k + 1]['cumulative_percent'], $paretoFinal[$k]['cumulative_percent']);
        }
    }

    public function test_kpi_dan_tabel_terbaru_dan_donut_menghormati_mode_dashboard(): void
    {
        AreaDitemukan::create(['name' => 'Area FA 1', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Rework 1', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        // Defect 1: Final Assy internal (today)
        $d1 = Defect::create([
            'waktu'            => Carbon::today()->setTime(10, 0),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'area_ditemukan'   => 'Area FA 1',
            'quantity'         => 10,
            'inspect_quantity' => 1000,
        ]);

        // Defect 2: Final Assy rework (today)
        $d2 = Defect::create([
            'waktu'            => Carbon::today()->setTime(11, 0),
            'user_name'        => 'Reworker A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => null,
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'BROKEN',
            'area_ditemukan'   => 'Area Rework 1',
            'rework_group_id'  => (string) Str::uuid(),
            'quantity'         => 5,
            'inspect_quantity' => null,
        ]);

        // Defect 3: Pre Assy internal (today)
        $d3 = Defect::create([
            'waktu'            => Carbon::today()->setTime(9, 0),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => null,
            'jenis_defect'     => 'CRIMP',
            'jenis_sub_defect' => 'BAD',
            'area_ditemukan'   => 'Area FA 1',
            'quantity'         => 7,
            'inspect_quantity' => 500,
        ]);

        // Defect 4: Final Assy internal (yesterday)
        $d4 = Defect::create([
            'waktu'            => Carbon::yesterday()->setTime(14, 0),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'area_ditemukan'   => null,
            'quantity'         => 3,
            'inspect_quantity' => 0,
        ]);

        // 1. Mode Internal (Final Assy Operator Only di Batch 2)
        $resInternal = $this->actingAsAdmin()->get('/dashboard?mode=internal');
        $resInternal->assertStatus(200);
        $resInternal->assertViewHas('defectBulanIni', 13); // 10 + 3 (Final Assy operator only, tanpa rework)
        $resInternal->assertViewHas('defectToday', 10); // 10 (Final Assy operator today)

        $recentInternal = $resInternal->viewData('recentDefects');
        $recentInternalIds = $recentInternal->pluck('id')->all();
        $this->assertContains($d1->id, $recentInternalIds);
        $this->assertContains($d4->id, $recentInternalIds);
        $this->assertNotContains($d2->id, $recentInternalIds); // rework dikecualikan

        // 2. Mode All (Total Operator di Batch 2)
        $resAll = $this->actingAsAdmin()->get('/dashboard?mode=all');
        $resAll->assertStatus(200);
        $resAll->assertViewHas('defectBulanIni', 20); // 10 + 7 + 3 (Pre + Final operator, tanpa 5 rework)
        $resAll->assertViewHas('defectToday', 17); // 10 + 7

        $recentAll = $resAll->viewData('recentDefects');
        $recentAllIds = $recentAll->pluck('id')->all();
        $this->assertContains($d1->id, $recentAllIds);
        $this->assertContains($d3->id, $recentAllIds);
        $this->assertContains($d4->id, $recentAllIds);
        $this->assertNotContains($d2->id, $recentAllIds); // rework tetap dikecualikan
    }

    public function test_get_stats_api_menghormati_mode(): void
    {
        AreaDitemukan::create(['name' => 'Area FA 1', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        Defect::create([
            'waktu'            => Carbon::today()->setTime(10, 0),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'area_ditemukan'   => 'Area FA 1',
            'quantity'         => 10,
        ]);

        Defect::create([
            'waktu'            => Carbon::today()->setTime(11, 0),
            'user_name'        => 'Reworker A',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => null,
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'BROKEN',
            'area_ditemukan'   => 'Area FA 1',
            'rework_group_id'  => (string) Str::uuid(),
            'quantity'         => 5,
        ]);

        Defect::create([
            'waktu'            => Carbon::today()->setTime(9, 0),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => null,
            'jenis_defect'     => 'CRIMP',
            'jenis_sub_defect' => 'BAD',
            'area_ditemukan'   => 'Area FA 1',
            'quantity'         => 7,
        ]);

        Defect::create([
            'waktu'            => Carbon::yesterday()->setTime(14, 0),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'area_ditemukan'   => null,
            'quantity'         => 3,
        ]);

        // Default mode -> total operator (20 total, 17 today)
        $resDef = $this->getJson('/api/dashboard/stats');
        $resDef->assertStatus(200);
        $this->assertEquals(20, $resDef->json('defectBulanIni'));
        $this->assertEquals(17, $resDef->json('defectToday'));

        // Mode total -> total operator (20 total, 17 today)
        $resTotal = $this->getJson('/api/dashboard/stats?mode=total');
        $resTotal->assertStatus(200);
        $this->assertEquals(20, $resTotal->json('defectBulanIni'));
        $this->assertEquals(17, $resTotal->json('defectToday'));

        // Mode final -> Final Assy operator (13 total, 10 today)
        $resFinal = $this->getJson('/api/dashboard/stats?mode=final');
        $resFinal->assertStatus(200);
        $this->assertEquals(13, $resFinal->json('defectBulanIni'));
        $this->assertEquals(10, $resFinal->json('defectToday'));

        // Mode pre -> Pre Assy operator (7 total, 7 today)
        $resPre = $this->getJson('/api/dashboard/stats?mode=pre');
        $resPre->assertStatus(200);
        $this->assertEquals(7, $resPre->json('defectBulanIni'));
        $this->assertEquals(7, $resPre->json('defectToday'));
    }

    public function test_tanpa_rework_hasil_index_dan_get_stats_identik_tanpa_mode_dan_mode_internal(): void
    {
        Defect::create([
            'waktu'            => Carbon::today()->setTime(10, 0),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-1',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'area_ditemukan'   => 'Inspection Line',
            'quantity'         => 12,
        ]);

        Defect::create([
            'waktu'            => Carbon::today()->setTime(11, 0),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => null,
            'jenis_defect'     => 'CRIMP',
            'jenis_sub_defect' => 'BAD',
            'area_ditemukan'   => null,
            'quantity'         => 8,
        ]);

        $resIndexNoMode = $this->actingAsAdmin()->get('/dashboard');
        $resIndexTotal = $this->actingAsAdmin()->get('/dashboard?mode=total');

        $this->assertEquals($resIndexNoMode->viewData('defectBulanIni'), $resIndexTotal->viewData('defectBulanIni'));
        $this->assertEquals($resIndexNoMode->viewData('defectToday'), $resIndexTotal->viewData('defectToday'));
        $this->assertEquals(
            $resIndexNoMode->viewData('recentDefects')->pluck('id')->all(),
            $resIndexTotal->viewData('recentDefects')->pluck('id')->all()
        );
        $this->assertEquals($resIndexNoMode->viewData('finalAssyChart'), $resIndexTotal->viewData('finalAssyChart'));
        $this->assertEquals($resIndexNoMode->viewData('preAssyChart'), $resIndexTotal->viewData('preAssyChart'));

        $resStatsNoMode = $this->getJson('/api/dashboard/stats');
        $resStatsTotal = $this->getJson('/api/dashboard/stats?mode=total');

        $this->assertEquals($resStatsNoMode->json('defectBulanIni'), $resStatsTotal->json('defectBulanIni'));
        $this->assertEquals($resStatsNoMode->json('defectToday'), $resStatsTotal->json('defectToday'));
    }

    public function test_dashboard_renders_toggle_mode_ui_with_active_states(): void
    {
        // 1. Default (Total)
        $resDef = $this->actingAsAdmin()->get('/dashboard');
        $resDef->assertStatus(200);
        $resDef->assertSee('id="btnModeTotal"', false);
        $resDef->assertSee('id="btnModeFinal"', false);
        $resDef->assertSee('id="btnModePre"', false);
        $resDef->assertSee('Total');
        $resDef->assertSee('Final Assy');
        $resDef->assertSee('Pre Assy');

        $htmlDef = $resDef->getContent();
        $this->assertMatchesRegularExpression('/id="btnModeTotal"[^>]*aria-pressed="true"/', $htmlDef);
        $this->assertMatchesRegularExpression('/id="btnModeFinal"[^>]*aria-pressed="false"/', $htmlDef);
        $this->assertMatchesRegularExpression('/id="btnModePre"[^>]*aria-pressed="false"/', $htmlDef);

        // 2. Mode Final Assy
        $resFinal = $this->actingAsAdmin()->get('/dashboard?mode=final');
        $resFinal->assertStatus(200);
        $htmlFinal = $resFinal->getContent();
        $this->assertMatchesRegularExpression('/id="btnModeFinal"[^>]*aria-pressed="true"/', $htmlFinal);
        $this->assertMatchesRegularExpression('/id="btnModeTotal"[^>]*aria-pressed="false"/', $htmlFinal);
        $this->assertMatchesRegularExpression('/id="btnModePre"[^>]*aria-pressed="false"/', $htmlFinal);

        // 3. Mode Pre Assy
        $resPre = $this->actingAsAdmin()->get('/dashboard?mode=pre');
        $resPre->assertStatus(200);
        $htmlPre = $resPre->getContent();
        $this->assertMatchesRegularExpression('/id="btnModePre"[^>]*aria-pressed="true"/', $htmlPre);
        $this->assertMatchesRegularExpression('/id="btnModeTotal"[^>]*aria-pressed="false"/', $htmlPre);
        $this->assertMatchesRegularExpression('/id="btnModeFinal"[^>]*aria-pressed="false"/', $htmlPre);
    }

    public function test_dashboard_renders_batch_4_elements_and_per_mode_visual_markup(): void
    {
        $response = $this->actingAsAdmin()->get('/dashboard');
        $response->assertStatus(200);

        // Card Header & Filter IDs
        $response->assertSee('id="cardTitleDpmFinal"', false);
        $response->assertSee('id="cardSubDpmFinal"', false);
        $response->assertSee('id="wrapFilterConveyorDpmFinal"', false);
        $response->assertSee('id="cardTitleParetoFinal"', false);
        $response->assertSee('id="cardSubParetoFinal"', false);
        $response->assertSee('id="wrapFilterConveyorParetoFinal"', false);

        // Pre Assy IDs
        $response->assertSee('id="cardTitleDpmPre"', false);
        $response->assertSee('id="cardSubDpmPre"', false);
        $response->assertSee('id="cardTitleParetoPre"', false);
        $response->assertSee('id="cardSubParetoPre"', false);

        // Script Constants & Guard Checks
        $html = $response->getContent();
        $this->assertStringContainsString('THEME_COLORS', $html);
        $this->assertStringContainsString('updateConveyorFilterUI', $html);
        $this->assertStringContainsString('updateModeVisuals', $html);
        $this->assertStringContainsString('reqMode !== currentMode', $html);
        $this->assertStringContainsString('currentMode === \'final\' && filtersDpmFinal.conveyor', $html);
        $this->assertStringContainsString('currentMode === \'final\' && filtersParetoFinal.conveyor', $html);
        $this->assertStringContainsString('Chart.getChart(canvas)', $html);
    }

    public function test_batch_6_mode_all_menggabungkan_pre_dan_final_ke_satu_tren_dan_pareto_top10(): void
    {
        AreaDitemukan::create(['name' => 'FA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Rework', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        // Buat 12 jenis sub defect berbeda di Pre Assy dan Final Assy
        // Final Assy Internal (4 sub defects)
        for ($i = 1; $i <= 4; $i++) {
            Defect::create([
                'waktu'            => '2026-06-02 10:00:00',
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Final Assy',
                'conveyor'         => 'CONV-1',
                'jenis_defect'     => 'DEFECT-FA-' . $i,
                'jenis_sub_defect' => 'SUB-FA-INT-' . $i,
                'area_ditemukan'   => 'FA Inspection',
                'quantity'         => $i * 10, // 10, 20, 30, 40
                'inspect_quantity' => 1000,
            ]);
        }

        // Final Assy Rework (4 sub defects)
        for ($i = 1; $i <= 4; $i++) {
            Defect::create([
                'waktu'            => '2026-06-02 11:00:00',
                'user_name'        => 'Reworker A',
                'jenis_assy'       => 'Final Assy',
                'conveyor'         => null,
                'jenis_defect'     => 'DEFECT-REW-' . $i,
                'jenis_sub_defect' => 'SUB-FA-REW-' . $i,
                'area_ditemukan'   => 'Area Rework',
                'rework_group_id'  => (string) Str::uuid(),
                'quantity'         => $i * 15, // 15, 30, 45, 60
                'inspect_quantity' => null,
            ]);
        }

        // Pre Assy Internal (4 sub defects)
        for ($i = 1; $i <= 4; $i++) {
            Defect::create([
                'waktu'            => '2026-06-02 09:00:00',
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Pre Assy',
                'carline_id'       => null,
                'jenis_defect'     => 'DEFECT-PRE-' . $i,
                'jenis_sub_defect' => 'SUB-PRE-' . $i,
                'area_ditemukan'   => 'FA Inspection',
                'quantity'         => $i * 25, // 25, 50, 75, 100
                'inspect_quantity' => 500,
            ]);
        }

        // Total Defect per sub defect:
        // SUB-PRE-4: 100 (int 100, rew 0)
        // SUB-PRE-3: 75 (int 75, rew 0)
        // SUB-FA-REW-4: 60 (int 0, rew 60)
        // SUB-PRE-2: 50 (int 50, rew 0)
        // SUB-FA-REW-3: 45 (int 0, rew 45)
        // SUB-FA-INT-4: 40 (int 40, rew 0)
        // SUB-FA-REW-2: 30 (int 0, rew 30)
        // SUB-FA-INT-3: 30 (int 30, rew 0)
        // SUB-PRE-1: 25 (int 25, rew 0)
        // SUB-FA-INT-2: 20 (int 20, rew 0)
        // --- Batas Top 10 (Total 10 teratas: 100+75+60+50+45+40+30+30+25+20 = 475) ---
        // Diluar Top 10:
        // SUB-FA-REW-1: 15 (int 0, rew 15)
        // SUB-FA-INT-1: 10 (int 10, rew 0)
        // Grand Total Semua = 475 + 15 + 10 = 500
        // Total Defect Internal = (10+20+30+40) + (25+50+75+100) = 350
        // Total Defect Rework = 15+30+45+60 = 150
        // Grand Total = 350 + 150 = 500

        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-02&end_date=2026-06-02&group_by=day&mode=all');
        $res->assertStatus(200);

        // Tren Gabungan: hanya operator defect (Pre 250 + Final 100 = 350)
        // Rework (150) dikecualikan dari tren & pareto
        $pareto = $res->json('pareto_final');
        $this->assertCount(8, $pareto);

        // Peringkat 1: SUB-PRE-4 (100) -> 100/350 = 28.57%
        $this->assertEquals('SUB-PRE-4', $pareto[0]['name']);
        $this->assertEquals('SUB-PRE-4', $pareto[0]['key']);
        $this->assertEquals('[Pre] SUB-PRE-4', $pareto[0]['label']);
        $this->assertEquals('Pre', $pareto[0]['area_tag']);
        $this->assertEquals(100, $pareto[0]['total']);
        $this->assertEquals(100, $pareto[0]['pre_qty']);
        $this->assertEquals(0, $pareto[0]['final_qty']);
        $this->assertEquals(28.57, $pareto[0]['cumulative_percent']);

        // Peringkat 2: SUB-PRE-3 (75) -> (100+75)/350 = 50.0%
        $this->assertEquals('SUB-PRE-3', $pareto[1]['name']);
        $this->assertEquals('SUB-PRE-3', $pareto[1]['key']);
        $this->assertEquals('[Pre] SUB-PRE-3', $pareto[1]['label']);
        $this->assertEquals('Pre', $pareto[1]['area_tag']);
        $this->assertEquals(75, $pareto[1]['total']);
        $this->assertEquals(75, $pareto[1]['pre_qty']);
        $this->assertEquals(0, $pareto[1]['final_qty']);
        $this->assertEquals(50.0, $pareto[1]['cumulative_percent']);

        // Peringkat 3: SUB-PRE-2 (50) -> (175+50)/350 = 64.29%
        $this->assertEquals('SUB-PRE-2', $pareto[2]['name']);
        $this->assertEquals(50, $pareto[2]['total']);

        // Peringkat 4: SUB-FA-INT-4 (40) -> Final Assy operator
        $this->assertEquals('SUB-FA-INT-4', $pareto[3]['name']);
        $this->assertEquals(40, $pareto[3]['total']);
        $this->assertEquals(40, $pareto[3]['final_qty']);
        $this->assertEquals('Final', $pareto[3]['area_tag']);
    }

    public function test_batch_6_mode_all_filter_conveyor_diabaikan_pada_tren_dan_pareto_gabungan(): void
    {
        AreaDitemukan::create(['name' => 'FA Inspection', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Rework', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        Defect::create([
            'waktu'            => '2026-06-03 10:00:00',
            'user_name'        => 'QA 1',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-A',
            'jenis_defect'     => 'DEFECT 1',
            'jenis_sub_defect' => 'SUB 1',
            'area_ditemukan'   => 'FA Inspection',
            'quantity'         => 10,
            'inspect_quantity' => 1000,
        ]);

        Defect::create([
            'waktu'            => '2026-06-03 10:00:00',
            'user_name'        => 'QA 2',
            'jenis_assy'       => 'Final Assy',
            'conveyor'         => 'CONV-B',
            'jenis_defect'     => 'DEFECT 2',
            'jenis_sub_defect' => 'SUB 2',
            'area_ditemukan'   => 'FA Inspection',
            'quantity'         => 20,
            'inspect_quantity' => 1000,
        ]);

        // Request dengan mode all dan conveyor='CONV-A' tetap menyertakan semua konveyor
        $res = $this->actingAsAdmin()->getJson('/dashboard/analytics?start_date=2026-06-03&end_date=2026-06-03&group_by=day&mode=all&conveyor=CONV-A');
        $res->assertStatus(200);

        $trends = $res->json('dpm_final');
        $this->assertCount(1, $trends);
        $this->assertEquals(30, $trends[0]['total_defect']); // 10 + 20
    }

    public function test_batch_6_dashboard_view_renders_gabungan_markup_and_active_state(): void
    {
        // 1. Mode Total (dua kolom berdampingan, keduanya tampil)
        $resTotal = $this->actingAsAdmin()->get('/dashboard?mode=total');
        $resTotal->assertStatus(200);

        $htmlTotal = $resTotal->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="columnPreAssy"[^>]*class="[^"]*hidden[^"]*"/', $htmlTotal);
        $this->assertDoesNotMatchRegularExpression('/id="columnFinalAssy"[^>]*class="[^"]*xl:col-span-2[^"]*"/', $htmlTotal);

        $resTotal->assertSee('Tren Defect Final Assy');
        $resTotal->assertSee('Tren Defect Pre Assy');

        // 2. Mode Final Assy (satu kolom penuh, Pre Assy hidden)
        $resFinal = $this->actingAsAdmin()->get('/dashboard?mode=final');
        $resFinal->assertStatus(200);

        $htmlFinal = $resFinal->getContent();
        $this->assertMatchesRegularExpression('/id="columnPreAssy"[^>]*class="[^"]*hidden[^"]*"/', $htmlFinal);
        $this->assertMatchesRegularExpression('/id="columnFinalAssy"[^>]*class="[^"]*xl:col-span-2[^"]*"/', $htmlFinal);

        $resFinal->assertSee('DPM Final Assy');
        $resFinal->assertSee('Pareto Sub Defect Final Assy');

        // 3. Mode Pre Assy (satu kolom penuh, Final Assy hidden)
        $resPre = $this->actingAsAdmin()->get('/dashboard?mode=pre');
        $resPre->assertStatus(200);

        $htmlPre = $resPre->getContent();
        $this->assertMatchesRegularExpression('/id="columnFinalAssy"[^>]*class="[^"]*hidden[^"]*"/', $htmlPre);
        $this->assertMatchesRegularExpression('/id="columnPreAssy"[^>]*class="[^"]*xl:col-span-2[^"]*"/', $htmlPre);

        $resPre->assertSee('PPM Pre Assy');
        $resPre->assertSee('Pareto Sub Defect Pre Assy');
    }

    /**
     * Test HTML dashboard memuat URL route('admin.dashboard.pareto_drilldown')
     * dan tidak memuat pemanggilan fetch yang diawali hardcoded path '/dashboard'.
     */
    public function test_dashboard_uses_route_for_pareto_drilldown_and_no_raw_slash_dashboard_fetch(): void
    {
        $response = $this->actingAsAdmin()->get('/dashboard');
        $response->assertStatus(200);

        $html = $response->getContent();
        $expectedRoute = route('admin.dashboard.pareto_drilldown');

        // Pastikan HTML dashboard memuat hasil route('admin.dashboard.pareto_drilldown')
        $this->assertStringContainsString($expectedRoute, $html);

        // Pastikan tidak ada fetch yang diawali hardcoded '/dashboard'
        $this->assertDoesNotMatchRegularExpression('/fetch\(\s*[\'`"]\/dashboard/', $html);
    }

    public function test_admin_can_drilldown_pareto_final_assy_conveyor(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        Defect::create([
            'user_name' => 'Operator 1',
            'waktu' => now(),
            'jenis_defect' => 'WIRING',
            'jenis_assy' => 'Final Assy',
            'jenis_sub_defect' => 'CROSS CIRCUIT',
            'conveyor' => 'CV-01',
            'quantity' => 10,
        ]);
        Defect::create([
            'user_name' => 'Operator 1',
            'waktu' => now(),
            'jenis_defect' => 'WIRING',
            'jenis_assy' => 'Final Assy',
            'jenis_sub_defect' => 'CROSS CIRCUIT',
            'conveyor' => 'CV-02',
            'quantity' => 5,
        ]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/pareto-drilldown?sub_defect=CROSS CIRCUIT&start_date={$today}&end_date={$today}&jenis_assy=Final Assy");
        $res->assertStatus(200);
        $res->assertJsonStructure(['sub_defect', 'metric', 'items']);
        $this->assertEquals('conveyor', $res->json('metric'));
        $this->assertCount(2, $res->json('items'));
        $this->assertEquals('CV-01', $res->json('items.0.name'));
        $this->assertEquals(10, $res->json('items.0.total'));
    }

    public function test_admin_can_drilldown_pareto_pre_assy_carline(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $carlineA = Carline::create(['name' => 'CARLINE-ALPHA']);
        $carlineB = Carline::create(['name' => 'CARLINE-BETA']);

        Defect::create([
            'user_name' => 'Operator 1',
            'waktu' => now(),
            'jenis_defect' => 'WIRING',
            'jenis_assy' => 'Pre Assy',
            'jenis_sub_defect' => 'TERMINAL BENGKOK',
            'carline_id' => $carlineA->id,
            'quantity' => 8,
        ]);
        Defect::create([
            'user_name' => 'Operator 1',
            'waktu' => now(),
            'jenis_defect' => 'WIRING',
            'jenis_assy' => 'Pre Assy',
            'jenis_sub_defect' => 'TERMINAL BENGKOK',
            'carline_id' => $carlineB->id,
            'quantity' => 4,
        ]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/pareto-drilldown?sub_defect=TERMINAL BENGKOK&start_date={$today}&end_date={$today}&jenis_assy=Pre Assy");
        $res->assertStatus(200);
        $res->assertJsonStructure(['sub_defect', 'metric', 'items']);
        $this->assertEquals('carline', $res->json('metric'));
        $this->assertCount(2, $res->json('items'));
        $this->assertEquals('CARLINE-ALPHA', $res->json('items.0.name'));
        $this->assertEquals(8, $res->json('items.0.total'));
    }

    public function test_total_pareto_gabungan_equals_total_kpi_mode_gabungan(): void
    {
        $today = Carbon::today()->toDateString();

        $areaInt = AreaDitemukan::create(['name' => 'AREA-INT-FA', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $areaRew = AreaDitemukan::create(['name' => 'AREA-REW-FA', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $carline = Carline::create(['name' => 'CARLINE-OMEGA']);

        // 1. Pre Assy Internal: qty 10 (G.1 - FOREIGN MATERIAL)
        Defect::create([
            'waktu' => "{$today} 08:00:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT-1',
            'jenis_sub_defect' => 'G.1 - FOREIGN MATERIAL',
            'carline_id' => $carline->id,
            'quantity' => 10,
        ]);

        // 2. Final Assy Internal: qty 5 (11.A - FOREIGN MATERIAL)
        Defect::create([
            'waktu' => "{$today} 09:00:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'DEFECT-1',
            'jenis_sub_defect' => '11.A - FOREIGN MATERIAL',
            'conveyor' => 'CONV-1',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 5,
        ]);

        // 3. Final Assy Rework: qty 8 (11.A - FOREIGN MATERIAL, rework group)
        Defect::create([
            'waktu' => "{$today} 10:00:00",
            'user_name' => 'Operator Rework',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'DEFECT-1',
            'jenis_sub_defect' => '11.A - FOREIGN MATERIAL',
            'rework_group_id' => 'GRP-REW-101',
            'area_ditemukan' => $areaRew->name,
            'quantity' => 8,
        ]);

        // 4. Final Assy Internal: qty 7 (1.A - CROSS CIRCUIT)
        Defect::create([
            'waktu' => "{$today} 11:00:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'DEFECT-2',
            'jenis_sub_defect' => '1.A - CROSS CIRCUIT',
            'conveyor' => 'CONV-2',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 7,
        ]);

        // 5. Defect Anomali (rework tapi area internal) -> tidak masuk DPM/pareto
        Defect::create([
            'waktu' => "{$today} 12:00:00",
            'user_name' => 'Operator Rework',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'DEFECT-MISMATCH',
            'jenis_sub_defect' => '2.B - DAMAGE CONNECTOR',
            'rework_group_id' => 'GRP-REW-999',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 99,
        ]);

        // Total operator: 10 (Pre) + 5 (Final) + 7 (Final) = 22. Rework (8, 99) diabaikan.
        $resAnalytics = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=all&start_date={$today}&end_date={$today}");
        $resAnalytics->assertStatus(200);

        $pareto = $resAnalytics->json('pareto_final');
        $sumParetoTotal = array_sum(array_column($pareto, 'total'));

        // Cek bahwa FOREIGN MATERIAL menyatu dari Final Assy + Pre Assy (hanya data operator)
        $fmItem = collect($pareto)->firstWhere('name', 'FOREIGN MATERIAL');
        $this->assertNotNull($fmItem);
        $this->assertEquals(15, $fmItem['total']); // 10 (Pre) + 5 (Final Internal)
        $this->assertEquals(15, $fmItem['internal_qty']);
        $this->assertEquals(0, $fmItem['rework_qty']);

        // Cek total pareto sama dengan KPI stats mode all
        $resStats = $this->actingAsAdmin()->getJson('/api/dashboard/stats?mode=all');
        $resStats->assertStatus(200);
        $kpiTotalDefect = $resStats->json('defectBulanIni');

        $this->assertEquals(22, $sumParetoTotal);
        $this->assertEquals(22, $kpiTotalDefect);
        $this->assertEquals($kpiTotalDefect, $sumParetoTotal);
    }

    public function test_drilldown_groups_sum_equals_pareto_bar_value(): void
    {
        $today = Carbon::today()->toDateString();

        $areaInt = AreaDitemukan::create(['name' => 'AREA-INT-FA', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $areaRew = AreaDitemukan::create(['name' => 'AREA-REW-FA', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $carlineA = Carline::create(['name' => 'CARLINE-ALPHA']);
        $carlineB = Carline::create(['name' => 'CARLINE-BETA']);

        // 1. Pre Assy Carline A: 6 pcs (G.1 - FOREIGN MATERIAL)
        Defect::create([
            'waktu' => "{$today} 08:00:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT-1',
            'jenis_sub_defect' => 'G.1 - FOREIGN MATERIAL',
            'carline_id' => $carlineA->id,
            'quantity' => 6,
        ]);

        // 2. Pre Assy Carline B: 4 pcs (G.1 - FOREIGN MATERIAL)
        Defect::create([
            'waktu' => "{$today} 08:30:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT-1',
            'jenis_sub_defect' => 'G.1 - FOREIGN MATERIAL',
            'carline_id' => $carlineB->id,
            'quantity' => 4,
        ]);

        // 3. Final Assy Internal Conv 1: 5 pcs (11.A - FOREIGN MATERIAL)
        Defect::create([
            'waktu' => "{$today} 09:00:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'DEFECT-1',
            'jenis_sub_defect' => '11.A - FOREIGN MATERIAL',
            'conveyor' => 'CONV-1',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 5,
        ]);

        // 4. Final Assy Rework: 3 pcs (diabaikan dari dashboard di Batch 2)
        Defect::create([
            'waktu' => "{$today} 09:30:00",
            'user_name' => 'Operator Rework',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'DEFECT-1',
            'jenis_sub_defect' => '11.A - FOREIGN MATERIAL',
            'conveyor' => null,
            'rework_group_id' => 'GRP-REW-202',
            'area_ditemukan' => $areaRew->name,
            'quantity' => 3,
        ]);

        // Total operator FOREIGN MATERIAL = 6 + 4 + 5 = 15
        $res = $this->actingAsAdmin()->getJson("/dashboard/pareto-drilldown?sub_defect=FOREIGN MATERIAL&mode=all&start_date={$today}&end_date={$today}");
        $res->assertStatus(200);
        $res->assertJsonStructure(['sub_defect', 'metric', 'items']);
        $this->assertEquals('conveyor_carline', $res->json('metric'));

        $items = $res->json('items');
        $sumDrilldown = array_sum(array_column($items, 'total'));
        $this->assertEquals(15, $sumDrilldown);

        $itemNames = array_column($items, 'name');
        $this->assertContains('Pre: CARLINE-ALPHA', $itemNames);
        $this->assertContains('Pre: CARLINE-BETA', $itemNames);
        $this->assertContains('Final: CONV-1', $itemNames);

        // Uji juga kategori LAIN-LAIN (termasuk strip '-' dan string kosong '')
        Defect::create([
            'waktu' => "{$today} 10:00:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT-DASH',
            'jenis_sub_defect' => '-',
            'carline_id' => $carlineA->id,
            'quantity' => 7,
        ]);
        Defect::create([
            'waktu' => "{$today} 10:30:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'DEFECT-EMPTY',
            'jenis_sub_defect' => '',
            'conveyor' => 'CONV-1',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 3,
        ]);

        $resLain = $this->actingAsAdmin()->getJson("/dashboard/pareto-drilldown?sub_defect=LAIN-LAIN&mode=all&start_date={$today}&end_date={$today}");
        $resLain->assertStatus(200);
        $itemsLain = $resLain->json('items');
        $sumLain = array_sum(array_column($itemsLain, 'total'));
        $this->assertEquals(10, $sumLain); // 7 + 3
    }

    /**
     * Test penyatuan sub defect mode Gabungan hanya untuk yang ada di alias map (FOREIGN MATERIAL),
     * sedangkan sub defect lain (C.2 vs D.2, 5.B vs 6.B) tetap menjadi batang terpisah.
     */
    public function test_pareto_gabungan_alias_map_and_distinct_bars(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $areaInt = AreaDitemukan::create(['name' => 'Area Assembly QA Test', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        // Pre Assy: C.2 - C/H TERLALU RENDAH vs D.2 - C/H TERLALU RENDAH
        Defect::create([
            'waktu' => "{$today} 08:00:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'FRONT CRIMPING',
            'jenis_sub_defect' => 'C.2 - C/H TERLALU RENDAH',
            'quantity' => 2,
        ]);
        Defect::create([
            'waktu' => "{$today} 08:15:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'REAR CRIMPING',
            'jenis_sub_defect' => 'D.2 - C/H TERLALU RENDAH',
            'quantity' => 3,
        ]);

        // Final Assy: 5.B - MISALIGN vs 6.B - MISALIGN
        Defect::create([
            'waktu' => "{$today} 08:30:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'HALF LOCK',
            'jenis_sub_defect' => '5.B - MISALIGN',
            'conveyor' => 'CONV-1',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 4,
        ]);
        Defect::create([
            'waktu' => "{$today} 08:45:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'WRONG PART',
            'jenis_sub_defect' => '6.B - MISALIGN',
            'conveyor' => 'CONV-2',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 5,
        ]);

        // Lintas lini: 11.A - FOREIGN MATERIAL (Final) vs G.1 - FOREIGN MATERIAL (Pre)
        Defect::create([
            'waktu' => "{$today} 09:00:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'LAIN-LAIN',
            'jenis_sub_defect' => '11.A - FOREIGN MATERIAL',
            'conveyor' => 'CONV-1',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 6,
        ]);
        Defect::create([
            'waktu' => "{$today} 09:15:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'CRIMPING',
            'jenis_sub_defect' => 'G.1 - FOREIGN MATERIAL',
            'quantity' => 7,
        ]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?assy=final&mode=all&start_date={$today}&end_date={$today}");
        $res->assertStatus(200);

        $pareto = $res->json('pareto_final');
        $paretoNames = array_column($pareto, 'name');
        $paretoTotals = array_combine($paretoNames, array_column($pareto, 'total'));

        // FOREIGN MATERIAL disatukan menjadi 1 batang dengan total 6 + 7 = 13
        $this->assertContains('FOREIGN MATERIAL', $paretoNames);
        $this->assertEquals(13, $paretoTotals['FOREIGN MATERIAL']);

        // C.2 dan D.2 tetap menjadi 2 batang terpisah
        $this->assertContains('C.2 - C/H TERLALU RENDAH', $paretoNames);
        $this->assertEquals(2, $paretoTotals['C.2 - C/H TERLALU RENDAH']);
        $this->assertContains('D.2 - C/H TERLALU RENDAH', $paretoNames);
        $this->assertEquals(3, $paretoTotals['D.2 - C/H TERLALU RENDAH']);

        // 5.B dan 6.B tetap menjadi 2 batang terpisah
        $this->assertContains('5.B - MISALIGN', $paretoNames);
        $this->assertEquals(4, $paretoTotals['5.B - MISALIGN']);
        $this->assertContains('6.B - MISALIGN', $paretoNames);
        $this->assertEquals(5, $paretoTotals['6.B - MISALIGN']);
    }

    public function test_mode_all_pareto_includes_area_tag_key_label_and_qty_breakdown_and_drilldown(): void
    {
        $areaInt = AreaDitemukan::create(['name' => 'Area Assembly', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $areaRew = AreaDitemukan::create(['name' => 'Area Rework Khusus', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $today = '2026-06-15';

        // 1. Shared defect: FOREIGN MATERIAL in Final Assy Internal (4 pcs), Final Assy Rework (2 pcs), Pre Assy (5 pcs)
        Defect::create([
            'waktu' => "{$today} 08:00:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'LAIN-LAIN',
            'jenis_sub_defect' => '11.A - FOREIGN MATERIAL',
            'conveyor' => 'CONV-1',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 4,
            'inspect_quantity' => 100,
        ]);
        Defect::create([
            'waktu' => "{$today} 08:30:00",
            'user_name' => 'Reworker 1',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'LAIN-LAIN',
            'jenis_sub_defect' => '11.A - FOREIGN MATERIAL',
            'area_ditemukan' => $areaRew->name,
            'rework_group_id' => (string) Str::uuid(),
            'quantity' => 2,
        ]);
        Defect::create([
            'waktu' => "{$today} 09:00:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'CRIMPING',
            'jenis_sub_defect' => 'G.1 - FOREIGN MATERIAL',
            'quantity' => 5,
            'inspect_quantity' => 200,
        ]);

        // 2. Final-only defect (Internal 3 pcs, Rework 3 pcs)
        Defect::create([
            'waktu' => "{$today} 09:30:00",
            'user_name' => 'Operator FA',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'INSER CIRCUIT',
            'jenis_sub_defect' => '1.A - CROSS CIRCUIT',
            'conveyor' => 'CONV-2',
            'area_ditemukan' => $areaInt->name,
            'quantity' => 3,
        ]);
        Defect::create([
            'waktu' => "{$today} 09:45:00",
            'user_name' => 'Reworker 1',
            'jenis_assy' => 'Final Assy',
            'jenis_defect' => 'INSER CIRCUIT',
            'jenis_sub_defect' => '1.A - CROSS CIRCUIT',
            'area_ditemukan' => $areaRew->name,
            'rework_group_id' => (string) Str::uuid(),
            'quantity' => 3,
        ]);

        // 3. Pre-only defect (7 pcs)
        Defect::create([
            'waktu' => "{$today} 10:00:00",
            'user_name' => 'Operator Pre',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'CORE',
            'jenis_sub_defect' => 'A.1 - FRAYING',
            'quantity' => 7,
        ]);

        // Fetch pareto mode all
        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=all&start_date={$today}&end_date={$today}");
        $res->assertStatus(200);

        $pareto = $res->json('pareto_final');
        $byKey = collect($pareto)->keyBy('key');

        // Check Shared Defect: FOREIGN MATERIAL
        $this->assertTrue($byKey->has('FOREIGN MATERIAL'));
        $fm = $byKey->get('FOREIGN MATERIAL');
        $this->assertEquals('FOREIGN MATERIAL', $fm['key']);
        $this->assertEquals('FOREIGN MATERIAL', $fm['name']);
        $this->assertEquals('Final+Pre', $fm['area_tag']);
        $this->assertEquals('[Final+Pre] FOREIGN MATERIAL', $fm['label']);
        $this->assertEquals(9, $fm['total']); // 4 (FA operator) + 5 (Pre operator); rework (2) diabaikan
        $this->assertEquals(4, $fm['final_qty']);
        $this->assertEquals(5, $fm['pre_qty']);
        $this->assertEquals(4, $fm['final_internal_qty']);
        $this->assertEquals(9, $fm['internal_qty']);
        $this->assertEquals(0, $fm['rework_qty']);

        // Check Final-only: 1.A - CROSS CIRCUIT
        $this->assertTrue($byKey->has('1.A - CROSS CIRCUIT'));
        $cc = $byKey->get('1.A - CROSS CIRCUIT');
        $this->assertEquals('1.A - CROSS CIRCUIT', $cc['key']);
        $this->assertEquals('Final', $cc['area_tag']);
        $this->assertEquals('[Final] 1.A - CROSS CIRCUIT', $cc['label']);
        $this->assertEquals(3, $cc['total']); // 3 operator; rework (3) diabaikan
        $this->assertEquals(3, $cc['final_qty']);
        $this->assertEquals(0, $cc['pre_qty']);
        $this->assertEquals(3, $cc['final_internal_qty']);

        // Check Pre-only: A.1 - FRAYING
        $this->assertTrue($byKey->has('A.1 - FRAYING'));
        $fr = $byKey->get('A.1 - FRAYING');
        $this->assertEquals('A.1 - FRAYING', $fr['key']);
        $this->assertEquals('Pre', $fr['area_tag']);
        $this->assertEquals('[Pre] A.1 - FRAYING', $fr['label']);
        $this->assertEquals(7, $fr['total']);
        $this->assertEquals(0, $fr['final_qty']);
        $this->assertEquals(7, $fr['pre_qty']);

        // Test Drilldown using pure key
        $resDrillKey = $this->actingAsAdmin()->getJson("/dashboard/pareto-drilldown?sub_defect=FOREIGN MATERIAL&mode=all&start_date={$today}&end_date={$today}");
        $resDrillKey->assertStatus(200);
        $this->assertEquals('FOREIGN MATERIAL', $resDrillKey->json('sub_defect'));
        $this->assertNotEmpty($resDrillKey->json('items'));

        // Test Drilldown with area tag in sub_defect (defensive handling)
        $resDrillTagged = $this->actingAsAdmin()->getJson("/dashboard/pareto-drilldown?sub_defect=[Final+Pre] FOREIGN MATERIAL&mode=all&start_date={$today}&end_date={$today}");
        $resDrillTagged->assertStatus(200);
        $this->assertEquals('FOREIGN MATERIAL', $resDrillTagged->json('sub_defect'));
        $this->assertEquals($resDrillKey->json('items'), $resDrillTagged->json('items'));

        // Mode Internal and Mode Rework verification: area_tag is not present in single mode
        $resInternal = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=internal&assy=final&start_date={$today}&end_date={$today}");
        $resInternal->assertStatus(200);
        $paretoInternal = $resInternal->json('pareto_final');
        $this->assertNotEmpty($paretoInternal);
        $this->assertArrayNotHasKey('area_tag', $paretoInternal[0]);
    }

    public function test_no_konveyor_text_remains_in_views(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        $found = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $content = file_get_contents($file->getPathname());
                if (preg_match('/\bkonveyor\b/i', $content)) {
                    $found[] = $file->getFilename();
                }
            }
        }
        $this->assertEmpty($found, 'Found konveyor in views: ' . implode(', ', $found));
    }

    /**
     * Test Point 8: Pareto Utama = Defect & Zero Defect Dikecualikan
     */
    public function test_pareto_by_defect_and_zero_defect_exclusion(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $carline = Carline::create(['name' => 'CARLINE-ALPHA']);

        Defect::create([
            'waktu'            => "{$today} 08:00:00",
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'jenis_defect'     => 'CRIMPING BEND',
            'jenis_sub_defect' => 'LOCK',
            'quantity'         => 5,
            'inspect_quantity' => 10,
        ]);

        Defect::create([
            'waktu'            => "{$today} 08:30:00",
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'jenis_defect'     => 'STRIP DEFECT',
            'jenis_sub_defect' => 'CORE',
            'quantity'         => 3,
            'inspect_quantity' => 10,
        ]);

        Defect::create([
            'waktu'            => "{$today} 09:00:00",
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
            'inspect_quantity' => 100,
        ]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?pareto_by=defect&start_date={$today}&end_date={$today}&assy=pre");
        $res->assertStatus(200);

        $paretoPre = $res->json('pareto_pre');
        $this->assertNotEmpty($paretoPre);
        $names = array_column($paretoPre, 'name');
        $this->assertContains('CRIMPING BEND', $names);
        $this->assertContains('STRIP DEFECT', $names);
        $this->assertNotContains('Zero Defect', $names);

        $first = $paretoPre[0];
        $this->assertEquals('CRIMPING BEND', $first['name']);
        $this->assertEquals(5, $first['total']);
    }

    /**
     * Test Point 8: Drilldown 3-Level: Defect -> Carline -> Area Ditemukan
     */
    public function test_pareto_drilldown_3_levels_defect_to_carline_to_area(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $carlineA = Carline::create(['name' => 'CARLINE-ALPHA']);
        $carlineB = Carline::create(['name' => 'CARLINE-BETA']);

        Defect::create([
            'waktu'            => "{$today} 08:00:00",
            'user_name'        => 'Operator 1',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carlineA->id,
            'area_ditemukan'   => 'Manual Crimping',
            'jenis_defect'     => 'CRIMPING BEND',
            'jenis_sub_defect' => 'LOCK',
            'quantity'         => 7,
            'inspect_quantity' => 10,
        ]);

        Defect::create([
            'waktu'            => "{$today} 08:15:00",
            'user_name'        => 'Operator 1',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carlineA->id,
            'area_ditemukan'   => 'Join Crimping',
            'jenis_defect'     => 'CRIMPING BEND',
            'jenis_sub_defect' => 'CORE',
            'quantity'         => 3,
            'inspect_quantity' => 10,
        ]);

        Defect::create([
            'waktu'            => "{$today} 08:30:00",
            'user_name'        => 'Operator 2',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carlineB->id,
            'area_ditemukan'   => 'Wonder',
            'jenis_defect'     => 'CRIMPING BEND',
            'jenis_sub_defect' => 'LOCK',
            'quantity'         => 4,
            'inspect_quantity' => 10,
        ]);

        // Level 2: Defect -> Carline
        $resL2 = $this->actingAsAdmin()->getJson("/dashboard/pareto-drilldown?defect=CRIMPING BEND&level=2&start_date={$today}&end_date={$today}&jenis_assy=Pre Assy");
        $resL2->assertStatus(200);
        $resL2->assertJsonStructure(['defect', 'metric', 'level', 'items']);
        $this->assertEquals('carline', $resL2->json('metric'));
        $this->assertEquals(2, $resL2->json('level'));
        $this->assertEquals('CARLINE-ALPHA', $resL2->json('items.0.name'));
        $this->assertEquals(10, $resL2->json('items.0.total')); // 7 + 3
        $this->assertEquals('CARLINE-BETA', $resL2->json('items.1.name'));
        $this->assertEquals(4, $resL2->json('items.1.total'));

        // Level 3: Defect + Carline -> Area Ditemukan
        $resL3 = $this->actingAsAdmin()->getJson("/dashboard/pareto-drilldown?defect=CRIMPING BEND&carline=CARLINE-ALPHA&level=3&start_date={$today}&end_date={$today}&jenis_assy=Pre Assy");
        $resL3->assertStatus(200);
        $resL3->assertJsonStructure(['defect', 'carline', 'metric', 'level', 'items']);
        $this->assertEquals('area_ditemukan', $resL3->json('metric'));
        $this->assertEquals(3, $resL3->json('level'));
        $this->assertEquals('Manual Crimping', $resL3->json('items.0.name'));
        $this->assertEquals(7, $resL3->json('items.0.total'));
        $this->assertEquals('Join Crimping', $resL3->json('items.1.name'));
        $this->assertEquals(3, $resL3->json('items.1.total'));
    }

    /**
     * Test Point 8: Top 5 Area Ditemukan (Final Assy)
     */
    public function test_top_5_area_ditemukan_final_assy(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        // 6 areas with different PCS
        $areas = [
            ['name' => 'CK', 'qty' => 50],
            ['name' => 'V1', 'qty' => 40],
            ['name' => 'V2', 'qty' => 30],
            ['name' => 'FC', 'qty' => 20],
            ['name' => 'WP', 'qty' => 10],
            ['name' => 'RI', 'qty' => 5],
        ];

        foreach ($areas as $a) {
            Defect::create([
                'waktu'            => "{$today} 08:00:00",
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Final Assy',
                'area_ditemukan'   => $a['name'],
                'jenis_defect'     => 'MISSING PART',
                'jenis_sub_defect' => 'LOCK',
                'quantity'         => $a['qty'],
                'inspect_quantity' => 100,
            ]);
        }

        // Zero Defect (harus diabaikan)
        Defect::create([
            'waktu'            => "{$today} 08:30:00",
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'CK',
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
            'inspect_quantity' => 100,
        ]);

        // 1. Endpoint dedicated /dashboard/top5-area-final
        $resEndpoint = $this->actingAsAdmin()->getJson("/dashboard/top5-area-final?start_date={$today}&end_date={$today}");
        $resEndpoint->assertStatus(200);
        $resEndpoint->assertJsonStructure(['items' => [['name', 'total']]]);
        $items = $resEndpoint->json('items');
        $this->assertCount(5, $items);
        $this->assertEquals('CK', $items[0]['name']);
        $this->assertEquals(50, $items[0]['total']);
        $this->assertEquals('WP', $items[4]['name']);
        $this->assertEquals(10, $items[4]['total']);
        $this->assertNotContains('RI', array_column($items, 'name'));

        // 2. Included in /dashboard/analytics
        $resAnalytics = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&assy=final");
        $resAnalytics->assertStatus(200);
        $this->assertArrayHasKey('top5_area_final', $resAnalytics->json());
        $this->assertCount(5, $resAnalytics->json('top5_area_final'));
    }

    /**
     * Test HTML dashboard mengandung script yang valid secara sintaksis (lolos node --check)
     * untuk seluruh mode (total, final, pre).
     */
    public function test_dashboard_rendered_javascript_has_no_syntax_errors(): void
    {
        exec('node -v 2>&1', $nodeVersion, $versionCode);
        if ($versionCode !== 0) {
            $this->markTestSkipped('Node.js is not available in the current environment.');
        }

        $modes = ['total', 'final', 'pre'];
        $tempDir = sys_get_temp_dir();

        foreach ($modes as $mode) {
            $response = $this->actingAsAdmin()->get("/dashboard?mode={$mode}");
            $response->assertStatus(200);

            $html = $response->getContent();
            preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches);

            $this->assertNotEmpty($matches[1], "No <script> tags found for mode {$mode}");

            foreach ($matches[1] as $idx => $scriptContent) {
                $trimmed = trim($scriptContent);
                if (empty($trimmed)) {
                    continue;
                }

                $tmpFile = $tempDir . DIRECTORY_SEPARATOR . "test_dashboard_{$mode}_script_{$idx}.js";
                file_put_contents($tmpFile, $trimmed);

                $output = [];
                $exitCode = 0;
                exec("node --check " . escapeshellarg($tmpFile) . " 2>&1", $output, $exitCode);

                @unlink($tmpFile);

                $this->assertSame(
                    0,
                    $exitCode,
                    "SyntaxError found in mode {$mode}, script #{$idx}:\n" . implode("\n", $output)
                );
            }
        }
    }

    /**
     * Part C: Verifikasi kumulatif Pareto tiap kategori mencapai 100% dari totalnya sendiri
     */
    public function test_part_c_analytics_category_pareto_reaches_100_percent_cumulative(): void
    {
        AreaDitemukan::create(['name' => 'Internal Area A', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Assembly Area B', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY_DEFECT]);

        $today = now()->format('Y-m-d');

        // Final Assy - Internal Defect
        Defect::create([
            'waktu' => "{$today} 08:00:00",
            'user_name' => 'Operator 1',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'DEFECT_INT_1',
            'jenis_sub_defect' => 'SUB_INT_1',
            'area_ditemukan' => 'Internal Area A',
            'quantity' => 15,
            'inspect_quantity' => 1000,
        ]);
        Defect::create([
            'waktu' => "{$today} 08:30:00",
            'user_name' => 'Operator 1',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'DEFECT_INT_2',
            'jenis_sub_defect' => 'SUB_INT_2',
            'area_ditemukan' => 'Internal Area A',
            'quantity' => 5,
            'inspect_quantity' => 0,
        ]);

        // Final Assy - Assembly Defect
        Defect::create([
            'waktu' => "{$today} 09:00:00",
            'user_name' => 'Operator 1',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'DEFECT_ASSY_1',
            'jenis_sub_defect' => 'SUB_ASSY_1',
            'area_ditemukan' => 'Assembly Area B',
            'quantity' => 20,
            'inspect_quantity' => 0,
        ]);
        Defect::create([
            'waktu' => "{$today} 09:30:00",
            'user_name' => 'Operator 1',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'DEFECT_ASSY_2',
            'jenis_sub_defect' => 'SUB_ASSY_2',
            'area_ditemukan' => 'Assembly Area B',
            'quantity' => 10,
            'inspect_quantity' => 0,
        ]);

        $resFinal = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&assy=final");
        $resFinal->assertStatus(200);

        $paretoFinalInt = $resFinal->json('pareto_final_internal');
        $this->assertNotEmpty($paretoFinalInt);
        $lastInt = end($paretoFinalInt);
        $this->assertEquals(100.0, (float) $lastInt['cumulative_percent']);
        $this->assertEquals(20, array_sum(array_column($paretoFinalInt, 'total')));

        $paretoFinalAssy = $resFinal->json('pareto_final_assembly');
        $this->assertNotEmpty($paretoFinalAssy);
        $lastAssy = end($paretoFinalAssy);
        $this->assertEquals(100.0, (float) $lastAssy['cumulative_percent']);
        $this->assertEquals(30, array_sum(array_column($paretoFinalAssy, 'total')));

        // Pre Assy - Operator vs Inspektor
        Defect::create([
            'waktu' => "{$today} 10:00:00",
            'user_name' => 'Operator QA',
            'ditemukan_oleh' => 'Operator',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT_OP_1',
            'jenis_sub_defect' => 'SUB_OP_1',
            'quantity' => 12,
            'inspect_quantity' => 500,
        ]);
        Defect::create([
            'waktu' => "{$today} 10:30:00",
            'user_name' => 'Operator QA',
            'ditemukan_oleh' => 'Operator',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT_OP_2',
            'jenis_sub_defect' => 'SUB_OP_2',
            'quantity' => 8,
            'inspect_quantity' => 0,
        ]);

        Defect::create([
            'waktu' => "{$today} 11:00:00",
            'user_name' => 'Inspektor QA',
            'ditemukan_oleh' => 'Inspektor',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT_INSP_1',
            'jenis_sub_defect' => 'SUB_INSP_1',
            'quantity' => 14,
            'inspect_quantity' => 0,
        ]);

        $resPre = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&assy=pre");
        $resPre->assertStatus(200);

        $paretoPreOp = $resPre->json('pareto_pre_operator');
        $this->assertNotEmpty($paretoPreOp);
        $lastOp = end($paretoPreOp);
        $this->assertEquals(100.0, (float) $lastOp['cumulative_percent']);
        $this->assertEquals(20, array_sum(array_column($paretoPreOp, 'total')));

        $paretoPreInsp = $resPre->json('pareto_pre_inspektor');
        $this->assertNotEmpty($paretoPreInsp);
        $lastInsp = end($paretoPreInsp);
        $this->assertEquals(100.0, (float) $lastInsp['cumulative_percent']);
        $this->assertEquals(14, array_sum(array_column($paretoPreInsp, 'total')));
    }

    /**
     * Part C: Invariansi pembagian kategori DPM:
     * Final Assy: dpm_internal + dpm_assembly = dpm
     * Pre Assy: dpm_operator + dpm_inspektor = dpm
     */
    public function test_part_c_dpm_category_invariance_sum_equals_total_dpm(): void
    {
        AreaDitemukan::create(['name' => 'Internal Area A', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Assembly Area B', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY_DEFECT]);

        $today = now()->format('Y-m-d');

        // Final Assy: Total inspect = 1000, Internal qty = 3, Assembly qty = 7 => Total qty = 10
        // Expected total DPM = 10,000, Internal DPM = 3,000, Assembly DPM = 7,000
        Defect::create([
            'waktu' => "{$today} 08:00:00",
            'user_name' => 'Operator 1',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'DEFECT_INT',
            'jenis_sub_defect' => 'SUB_INT',
            'area_ditemukan' => 'Internal Area A',
            'quantity' => 3,
            'inspect_quantity' => 1000,
        ]);
        Defect::create([
            'waktu' => "{$today} 08:30:00",
            'user_name' => 'Operator 1',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'DEFECT_ASSY',
            'jenis_sub_defect' => 'SUB_ASSY',
            'area_ditemukan' => 'Assembly Area B',
            'quantity' => 7,
            'inspect_quantity' => 0,
        ]);

        $resFinal = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&assy=final");
        $resFinal->assertStatus(200);

        $trendsFinal = $resFinal->json('dpm_final');
        $this->assertNotEmpty($trendsFinal);
        $bucketFinal = $trendsFinal[0];
        $this->assertEquals(10000.0, (float) $bucketFinal['dpm']);
        $this->assertEquals(3000.0, (float) $bucketFinal['dpm_internal']);
        $this->assertEquals(7000.0, (float) $bucketFinal['dpm_assembly']);
        $this->assertEquals(
            (float) $bucketFinal['dpm'],
            (float) $bucketFinal['dpm_internal'] + (float) $bucketFinal['dpm_assembly']
        );

        // Pre Assy: Total inspect = 2000, Operator qty = 4, Inspektor qty = 6 => Total qty = 10
        // Expected total PPM = 5,000, Operator PPM = 2,000, Inspektor PPM = 3,000
        Defect::create([
            'waktu' => "{$today} 09:00:00",
            'user_name' => 'Operator QA',
            'ditemukan_oleh' => 'Operator',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT_OP',
            'jenis_sub_defect' => 'SUB_OP',
            'quantity' => 4,
            'inspect_quantity' => 2000,
        ]);
        Defect::create([
            'waktu' => "{$today} 09:30:00",
            'user_name' => 'Inspektor QA',
            'ditemukan_oleh' => 'Inspektor',
            'jenis_assy' => 'Pre Assy',
            'jenis_defect' => 'DEFECT_INSP',
            'jenis_sub_defect' => 'SUB_INSP',
            'quantity' => 6,
            'inspect_quantity' => 0,
        ]);

        $resPre = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&assy=pre");
        $resPre->assertStatus(200);

        $trendsPre = $resPre->json('dpm_pre');
        $this->assertNotEmpty($trendsPre);
        $bucketPre = $trendsPre[0];
        $this->assertEquals(5000.0, (float) $bucketPre['dpm']);
        $this->assertEquals(2000.0, (float) $bucketPre['dpm_operator']);
        $this->assertEquals(3000.0, (float) $bucketPre['dpm_inspektor']);
        $this->assertEquals(
            (float) $bucketPre['dpm'],
            (float) $bucketPre['dpm_operator'] + (float) $bucketPre['dpm_inspektor']
        );
    }

    /**
     * Part C: Rework dikecualikan dari seluruh metrik kategori
     */
    public function test_part_c_rework_excluded_from_all_category_metrics(): void
    {
        AreaDitemukan::create(['name' => 'Internal Area A', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        $today = now()->format('Y-m-d');

        Defect::create([
            'waktu' => "{$today} 08:00:00",
            'user_name' => 'Operator QA',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'NORMAL_DEFECT',
            'jenis_sub_defect' => 'NORMAL_SUB',
            'area_ditemukan' => 'Internal Area A',
            'quantity' => 5,
            'inspect_quantity' => 1000,
        ]);

        Defect::create([
            'waktu' => "{$today} 08:30:00",
            'user_name' => 'Reworker QA',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'REWORK_DEFECT',
            'jenis_sub_defect' => 'REWORK_SUB',
            'area_ditemukan' => 'Internal Area A',
            'quantity' => 50,
            'inspect_quantity' => 0,
            'rework_group_id' => (string) Str::uuid(),
        ]);

        $res = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&assy=final");
        $res->assertStatus(200);

        $pareto = $res->json('pareto_final_internal');
        $this->assertCount(1, $pareto);
        $this->assertEquals('NORMAL_SUB', $pareto[0]['name']);
        $this->assertEquals(5, $pareto[0]['total']);

        $trends = $res->json('dpm_final');
        $this->assertEquals(5000.0, (float) $trends[0]['dpm_internal']);
    }

    /**
     * Part C: Pareto drilldown memfilter berdasarkan category
     */
    public function test_part_c_pareto_drilldown_category_filtering(): void
    {
        AreaDitemukan::create(['name' => 'Area Int 1', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::create(['name' => 'Area Assy 1', 'tipe' => AreaDitemukan::TIPE_ASSEMBLY_DEFECT]);

        $today = now()->format('Y-m-d');

        Defect::create([
            'waktu' => "{$today} 08:00:00",
            'user_name' => 'Operator 1',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'DEFECT_DRILL',
            'jenis_sub_defect' => 'DEFECT_DRILL',
            'area_ditemukan' => 'Area Int 1',
            'quantity' => 10,
        ]);
        Defect::create([
            'waktu' => "{$today} 08:30:00",
            'user_name' => 'Operator 1',
            'jenis_assy' => 'Final Assy',
            'conveyor' => 'C1',
            'jenis_defect' => 'DEFECT_DRILL',
            'jenis_sub_defect' => 'DEFECT_DRILL',
            'area_ditemukan' => 'Area Assy 1',
            'quantity' => 20,
        ]);

        // Drilldown level 3 with category=internal
        $resInt = $this->actingAsAdmin()->getJson(
            "/dashboard/pareto-drilldown?defect=DEFECT_DRILL&level=3&carline=C1&jenis_assy=Final+Assy&category=internal&start_date={$today}&end_date={$today}"
        );
        $resInt->assertStatus(200);
        $itemsInt = $resInt->json('items');
        $this->assertCount(1, $itemsInt);
        $this->assertEquals('Area Int 1', $itemsInt[0]['name']);
        $this->assertEquals(10, $itemsInt[0]['total']);

        // Drilldown level 3 with category=assembly
        $resAssy = $this->actingAsAdmin()->getJson(
            "/dashboard/pareto-drilldown?defect=DEFECT_DRILL&level=3&carline=C1&jenis_assy=Final+Assy&category=assembly&start_date={$today}&end_date={$today}"
        );
        $resAssy->assertStatus(200);
        $itemsAssy = $resAssy->json('items');
        $this->assertCount(1, $itemsAssy);
        $this->assertEquals('Area Assy 1', $itemsAssy[0]['name']);
        $this->assertEquals(20, $itemsAssy[0]['total']);
    }

    /**
     * Part C: Dashboard view merender layout 2 kolom per kategori pada mode final dan pre
     * dengan wrapper grid dan breakpoint yang persis sama dengan mode Total (grid grid-cols-1 xl:grid-cols-2 gap-6)
     */
    public function test_part_c_dashboard_view_renders_two_column_category_layout_for_final_and_pre(): void
    {
        // 1. Mode Final: kedua kategori (Internal & Assembly) dalam grid dua kolom xl:grid-cols-2 yang sama dengan mode Total
        $resFinal = $this->actingAsAdmin()->get('/dashboard?mode=final');
        $resFinal->assertStatus(200);
        $resFinal->assertSee('id="finalAssyModeFinalWrapper"', false);
        $resFinal->assertSee('id="categoryGridFinalAssy"', false);
        $resFinal->assertSee('class="grid grid-cols-1 xl:grid-cols-2 gap-6"', false);
        $resFinal->assertSee('id="colFinalInternal"', false);
        $resFinal->assertSee('id="colFinalAssembly"', false);
        $resFinal->assertSee('id="chartDpmFinalInternal"', false);
        $resFinal->assertSee('id="chartDpmFinalAssembly"', false);
        $resFinal->assertSee('id="chartParetoFinalInternal"', false);
        $resFinal->assertSee('id="chartParetoFinalAssembly"', false);
        $resFinal->assertSee('id="filterConveyorFinalAssy"', false);

        // 2. Mode Pre: kedua kategori (Operator & Inspektor) dalam grid dua kolom xl:grid-cols-2 yang sama dengan mode Total
        $resPre = $this->actingAsAdmin()->get('/dashboard?mode=pre');
        $resPre->assertStatus(200);
        $resPre->assertSee('id="preAssyModePreWrapper"', false);
        $resPre->assertSee('id="categoryGridPreAssy"', false);
        $resPre->assertSee('id="colPreOperator"', false);
        $resPre->assertSee('id="colPreInspektor"', false);
        $resPre->assertSee('id="chartDpmPreOperator"', false);
        $resPre->assertSee('id="chartDpmPreInspektor"', false);
        $resPre->assertSee('id="chartParetoPreOperator"', false);
        $resPre->assertSee('id="chartParetoPreInspektor"', false);
        $resPre->assertSee('id="filterCarlinePreAssy"', false);

        // 3. Mode Total: container dua kolom persis sama (grid-cols-1 xl:grid-cols-2 gap-6)
        $resTotal = $this->actingAsAdmin()->get('/dashboard?mode=total');
        $resTotal->assertStatus(200);
        $resTotal->assertSee('id="analyticsGridContainer"', false);
        $resTotal->assertSee('id="finalAssyModeTotalWrapper"', false);
        $resTotal->assertSee('id="preAssyModeTotalWrapper"', false);
        $resTotal->assertSee('id="chartDpmFinal"', false);
        $resTotal->assertSee('id="chartParetoFinal"', false);
        $resTotal->assertSee('id="chartDpmPre"', false);
        $resTotal->assertSee('id="chartParetoPre"', false);
    }

    /**
     * Test Dashboard Pre Assy menggunakan label "Ditemukan oleh Operator" dan "Ditemukan oleh Inspektor"
     * serta tidak memuat label internal/assembly defect untuk Pre Assy.
     */
    public function test_dashboard_pre_assy_uses_ditemukan_oleh_operator_dan_inspektor_labels(): void
    {
        $resPre = $this->actingAsAdmin()->get('/dashboard?mode=pre');
        $resPre->assertStatus(200);

        // Harus memuat label Ditemukan oleh Operator dan Inspektor
        $resPre->assertSee('Ditemukan oleh Operator');
        $resPre->assertSee('Ditemukan oleh Inspektor');
        $resPre->assertSee('Tren PPM - Ditemukan oleh Operator');
        $resPre->assertSee('Tren PPM - Ditemukan oleh Inspektor');
        $resPre->assertSee('Pareto - Ditemukan oleh Operator');
        $resPre->assertSee('Pareto - Ditemukan oleh Inspektor');

        // Tidak boleh memuat teks lama yang mengasosiasikan Pre Assy dengan Internal Defect
        $resPre->assertDontSee('Internal Defect Operator');
        $resPre->assertDontSee('Internal Defect Inspektor');
        $resPre->assertDontSee('Pareto Defect Operator');
        $resPre->assertDontSee('Pareto Defect Inspektor');
    }

    /**
     * Test verifikasi lengkap kartu "Defect Bulan Ini":
     * (1) Boundary tanggal:
     *     - Tanggal terakhir bulan lalu (23:59:59) TIDAK dihitung.
     *     - Tanggal 1 bulan ini (00:00:00) DIHITUNG.
     *     - Tanggal terakhir bulan ini (23:59:59) DIHITUNG.
     *     - Tanggal 1 bulan depan (00:00:00) TIDAK dihitung.
     * (2) Rework TIDAK dihitung (rework_group_id != null diabaikan).
     * (3) Zero Defect TIDAK mengubah angka (quantity 0).
     * (4) Mode final/pre/all menyaring benar.
     * (5) index dan getStats menghasilkan angka identik di seluruh mode.
     * (6) Defect Hari Ini tidak berubah perilakunya dan selalu <= Defect Bulan Ini.
     */
    public function test_kpi_defect_bulan_ini_boundary_rework_zero_defect_mode_dan_konsistensi(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 15, 14, 30, 0, 'Asia/Jakarta'));

        try {
            // (1) Boundary Data:
            // a. Tanggal terakhir bulan lalu: 2026-10-31 23:59:59 (TIDAK dihitung)
            Defect::create([
                'waktu'            => '2026-10-31 23:59:59',
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Final Assy',
                'conveyor'         => 'CONV-1',
                'jenis_defect'     => 'WIRE',
                'jenis_sub_defect' => 'CUT',
                'quantity'         => 100,
            ]);

            // b. Tanggal 1 bulan ini tepat 00:00:00 (DIHITUNG, Final Assy: 10 pcs)
            Defect::create([
                'waktu'            => '2026-11-01 00:00:00',
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Final Assy',
                'conveyor'         => 'CONV-1',
                'jenis_defect'     => 'TERMINAL',
                'jenis_sub_defect' => 'BENT',
                'quantity'         => 10,
            ]);

            // c. Hari ini: 2026-11-15 10:00:00 (DIHITUNG, Final Assy: 5 pcs)
            Defect::create([
                'waktu'            => '2026-11-15 10:00:00',
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Final Assy',
                'conveyor'         => 'CONV-2',
                'jenis_defect'     => 'CONNECTOR',
                'jenis_sub_defect' => 'BROKEN',
                'quantity'         => 5,
            ]);

            // d. Tanggal terakhir bulan ini tepat 23:59:59 (DIHITUNG, Pre Assy: 20 pcs)
            Defect::create([
                'waktu'            => '2026-11-30 23:59:59',
                'user_name'        => 'Operator Pre',
                'jenis_assy'       => 'Pre Assy',
                'carline_id'       => null,
                'jenis_defect'     => 'CRIMP',
                'jenis_sub_defect' => 'BAD',
                'quantity'         => 20,
            ]);

            // e. Tanggal 1 bulan depan tepat 00:00:00 (TIDAK dihitung)
            Defect::create([
                'waktu'            => '2026-12-01 00:00:00',
                'user_name'        => 'Operator Pre',
                'jenis_assy'       => 'Pre Assy',
                'carline_id'       => null,
                'jenis_defect'     => 'CRIMP',
                'jenis_sub_defect' => 'BAD',
                'quantity'         => 200,
            ]);

            // (2) Rework di bulan ini (TIDAK dihitung karena rework_group_id terisi)
            Defect::create([
                'waktu'            => '2026-11-15 11:00:00',
                'user_name'        => 'Reworker A',
                'jenis_assy'       => 'Final Assy',
                'conveyor'         => 'CONV-1',
                'jenis_defect'     => 'HOUSING',
                'jenis_sub_defect' => 'CRACK',
                'rework_group_id'  => (string) Str::uuid(),
                'quantity'         => 50,
            ]);

            // (3) Zero Defect di bulan ini (TIDAK mengubah angka karena quantity = 0)
            Defect::create([
                'waktu'            => '2026-11-15 12:00:00',
                'user_name'        => 'Operator QA',
                'jenis_assy'       => 'Final Assy',
                'conveyor'         => 'CONV-1',
                'jenis_defect'     => 'Zero Defect',
                'jenis_sub_defect' => '-',
                'quantity'         => 0,
            ]);

            // (4) & (5) Mode check dan verifikasi index vs getStats identik:
            $modes = [
                'total' => ['expectedBulanIni' => 35, 'expectedToday' => 5],
                'final' => ['expectedBulanIni' => 15, 'expectedToday' => 5],
                'pre'   => ['expectedBulanIni' => 20, 'expectedToday' => 0],
            ];

            foreach ($modes as $mode => $exp) {
                // Initial SSR via DashboardController::index
                $resIndex = $this->actingAsAdmin()->get("/dashboard?mode={$mode}");
                $resIndex->assertStatus(200);
                $indexBulanIni = $resIndex->viewData('defectBulanIni');
                $indexToday = $resIndex->viewData('defectToday');

                // Polling API via DefectApiController::getStats
                $resApi = $this->actingAsAdmin()->getJson("/api/dashboard/stats?mode={$mode}");
                $resApi->assertStatus(200);
                $apiBulanIni = $resApi->json('defectBulanIni');
                $apiToday = $resApi->json('defectToday');

                // Pastikan nilai index dan getStats IDENTIK
                $this->assertSame($indexBulanIni, $apiBulanIni, "Mode {$mode}: defectBulanIni pada index dan getStats harus identik.");
                $this->assertSame($indexToday, $apiToday, "Mode {$mode}: defectToday pada index dan getStats harus identik.");

                // Pastikan angka tepat sesuai ekspektasi filter mode
                $this->assertEquals($exp['expectedBulanIni'], $apiBulanIni, "Mode {$mode}: ekspektasi defectBulanIni salah.");
                $this->assertEquals($exp['expectedToday'], $apiToday, "Mode {$mode}: ekspektasi defectToday salah.");

                // (6) Defect Hari Ini selalu <= Defect Bulan Ini
                $this->assertLessThanOrEqual($apiBulanIni, $apiToday, "Mode {$mode}: defectToday ({$apiToday}) harus <= defectBulanIni ({$apiBulanIni}).");
            }

            // Mode 'all' (alias total)
            $resAllApi = $this->actingAsAdmin()->getJson('/api/dashboard/stats?mode=all');
            $resAllApi->assertStatus(200);
            $this->assertEquals(35, $resAllApi->json('defectBulanIni'));
            $this->assertEquals(5, $resAllApi->json('defectToday'));

            // View check: kartu menampilkan teks label "Defect Bulan Ini" dan "November 2026"
            $resDashboardView = $this->actingAsAdmin()->get('/dashboard');
            $resDashboardView->assertSee('Defect Bulan Ini');
            $resDashboardView->assertSee('November 2026');
            $resDashboardView->assertSee('stat-defect-bulan-ini');
            $resDashboardView->assertDontSee('stat-total-defect');

        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_pareto_defaults_to_sub_defect_and_accepts_pareto_by_sub_defect(): void
    {
        $carline = Carline::create(['name' => 'CL-SUB-1', 'car_type_id' => null]);

        Defect::create([
            'user_name'        => 'Operator QA',
            'waktu'            => now(),
            'shift'            => '1A',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'conveyor'         => 'CL-SUB-1',
            'jenis_defect'     => 'DEFECT INDUK',
            'jenis_sub_defect' => 'SUB DEFECT KHUSUS',
            'quantity'         => 4,
            'quantity_inspect' => 100,
        ]);

        // Tanpa parameter pareto_by -> default sub_defect
        $resDefault = $this->actingAsAdmin()->getJson(route('admin.dashboard.analytics', [
            'type'       => 'pre',
            'start_date' => now()->toDateString(),
            'end_date'   => now()->toDateString(),
        ]));
        $resDefault->assertOk();
        $jsonDefault = json_encode($resDefault->json('pareto_pre'));
        $this->assertStringContainsString('SUB DEFECT KHUSUS', $jsonDefault);
        $this->assertStringNotContainsString('DEFECT INDUK', $jsonDefault);

        // Dengan pareto_by=sub_defect
        $resExplicit = $this->actingAsAdmin()->getJson(route('admin.dashboard.analytics', [
            'type'       => 'pre',
            'pareto_by'  => 'sub_defect',
            'start_date' => now()->toDateString(),
            'end_date'   => now()->toDateString(),
        ]));
        $resExplicit->assertOk();
        $jsonExplicit = json_encode($resExplicit->json('pareto_pre'));
        $this->assertStringContainsString('SUB DEFECT KHUSUS', $jsonExplicit);
        $this->assertStringNotContainsString('DEFECT INDUK', $jsonExplicit);
    }

    public function test_pre_assy_zero_defect_with_area_is_excluded_from_pareto_and_defect_quantity(): void
    {
        $carline = Carline::create(['name' => 'CL-ZD-AREA', 'car_type_id' => null]);

        Defect::create([
            'user_name'        => 'Operator QA',
            'waktu'            => now(),
            'shift'            => '1A',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'conveyor'         => 'CL-ZD-AREA',
            'area_ditemukan'   => 'Cutting',
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
            'quantity_inspect' => 250,
        ]);

        $res = $this->actingAsAdmin()->getJson(route('admin.dashboard.analytics', [
            'type'       => 'pre',
            'start_date' => now()->toDateString(),
            'end_date'   => now()->toDateString(),
        ]));

        $res->assertOk();
        $this->assertEmpty($res->json('pareto_pre'));
        $this->assertSame(0, (int) Defect::operatorInput()->actualDefect()->where('jenis_assy', 'Pre Assy')->sum('quantity'));
    }
}
