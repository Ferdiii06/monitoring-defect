<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class ReworkCardVsTrendVerificationTest extends TestCase
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

    public function test_automated_verification_card_vs_trend_with_fictitious_data(): void
    {
        AreaDitemukan::create(['name' => 'AREA-REWORK-TEST', 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        $day1 = '2026-06-01';
        $day2 = '2026-06-02';
        $day3 = '2026-06-03';

        // 6 Groups Rework (2 group per hari, 1-3 defect per group)
        // Day 1
        // GRP-01: 2 baris (qty 2, qty 1) = 3 defect
        Defect::create(['waktu' => "$day1 08:00:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-01', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-1', 'jenis_sub_defect' => 'SUB-1', 'quantity' => 2]);
        Defect::create(['waktu' => "$day1 08:30:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-01', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-2', 'jenis_sub_defect' => 'SUB-2', 'quantity' => 1]);
        // GRP-02: 1 baris (qty 4) = 4 defect
        Defect::create(['waktu' => "$day1 09:00:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-02', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-3', 'jenis_sub_defect' => 'SUB-3', 'quantity' => 4]);

        // Day 2
        // GRP-03: 3 baris (qty 1, qty 2, qty 3) = 6 defect
        Defect::create(['waktu' => "$day2 08:00:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-03', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-1', 'jenis_sub_defect' => 'SUB-1', 'quantity' => 1]);
        Defect::create(['waktu' => "$day2 08:15:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-03', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-2', 'jenis_sub_defect' => 'SUB-2', 'quantity' => 2]);
        Defect::create(['waktu' => "$day2 08:30:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-03', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-3', 'jenis_sub_defect' => 'SUB-3', 'quantity' => 3]);
        // GRP-04: 2 baris (qty 1, qty 1) = 2 defect
        Defect::create(['waktu' => "$day2 09:00:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-04', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-1', 'jenis_sub_defect' => 'SUB-1', 'quantity' => 1]);
        Defect::create(['waktu' => "$day2 09:30:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-04', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-2', 'jenis_sub_defect' => 'SUB-2', 'quantity' => 1]);

        // Day 3
        // GRP-05: 1 baris (qty 5) = 5 defect
        Defect::create(['waktu' => "$day3 08:00:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-05', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-1', 'jenis_sub_defect' => 'SUB-1', 'quantity' => 5]);
        // GRP-06: 2 baris (qty 2, qty 3) = 5 defect
        Defect::create(['waktu' => "$day3 09:00:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-06', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-2', 'jenis_sub_defect' => 'SUB-2', 'quantity' => 2]);
        Defect::create(['waktu' => "$day3 09:30:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-06', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-3', 'jenis_sub_defect' => 'SUB-3', 'quantity' => 3]);

        // ==========================================
        // Verifikasi Batch 2: Rework adalah pure record
        // Data rework TIDAK masuk dpm_final pada dashboard
        // ==========================================
        $resDay1 = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=final&assy=final&start_date=$day1&end_date=$day1&group_by=day");
        $resDay1->assertStatus(200);
        $dpmFinal1 = $resDay1->json('dpm_final');
        $this->assertCount(1, $dpmFinal1);
        $b1 = $dpmFinal1[0];

        $this->assertEquals(0, $b1['total_defect']);
        $this->assertNull($b1['dpm']);
        $this->assertEquals(0, $b1['def_rework']);
        $this->assertEquals(0, $b1['harness_rework']);

        // Filter 3 Hari: semua bucket total_defect = 0
        $res3Days = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=final&assy=final&start_date=$day1&end_date=$day3&group_by=day");
        $res3Days->assertStatus(200);
        $dpmFinal3 = $res3Days->json('dpm_final');
        $this->assertCount(3, $dpmFinal3);
        foreach ($dpmFinal3 as $b) {
            $this->assertEquals(0, $b['total_defect']);
            $this->assertNull($b['dpm']);
            $this->assertEquals(0, $b['def_rework']);
            $this->assertEquals(0, $b['harness_rework']);
        }

        // Verifikasi DB: semua 25 defect rework terisolasi di scope reworkRecord
        $this->assertEquals(25, Defect::reworkRecord()->where('jenis_assy', 'Final Assy')->sum('quantity'));
        $this->assertEquals(6, Defect::reworkRecord()->where('jenis_assy', 'Final Assy')->distinct('rework_group_id')->count('rework_group_id'));
        $this->assertEquals(0, Defect::operatorInput()->where('jenis_assy', 'Final Assy')->sum('quantity'));

        // Tambah 1 data operator aktual di Day 1 (qty 5, inspect 100)
        Defect::create([
            'waktu'            => "$day1 10:00:00",
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'AREA-REWORK-TEST',
            'rework_group_id'  => null,
            'jenis_defect'     => 'MISSING PART',
            'jenis_sub_defect' => 'CLIP',
            'quantity'         => 5,
            'inspect_quantity' => 100,
        ]);

        // Verifikasi bahwa dashboard HANYA menghitung data operator (5), bukan 5 + 7 rework
        $resDay1After = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=final&assy=final&start_date=$day1&end_date=$day1&group_by=day");
        $b1After = $resDay1After->json('dpm_final')[0];
        $this->assertEquals(5, $b1After['total_defect']);
        $this->assertEquals(100, $b1After['total_inspect']);
        $this->assertEquals(50000.0, $b1After['dpm']);
        $this->assertEquals(0, $b1After['def_rework']);
    }
}

