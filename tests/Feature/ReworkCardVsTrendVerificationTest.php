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
        AreaDitemukan::create(['name' => 'AREA-REWORK-TEST', 'tipe' => AreaDitemukan::TIPE_REWORK]);

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
        // a) Filter 1 Hari: Day 1 (2026-06-01)
        // ==========================================
        $resDay1 = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=rework&assy=final&start_date=$day1&end_date=$day1&group_by=day");
        $resDay1->assertStatus(200);
        $dpmFinal1 = $resDay1->json('dpm_final');
        $this->assertCount(1, $dpmFinal1);
        $b1 = $dpmFinal1[0];

        $cardDpmDay1 = ($b1['harness_rework'] > 0) ? round(($b1['def_rework'] / $b1['harness_rework']) * 1000000) : null;

        // Manual DB query Day 1
        $dbDay1Def = (int) Defect::byDashboardMode('rework', 'Final Assy')->whereBetween('waktu', [Carbon::parse($day1)->startOfDay(), Carbon::parse($day1)->endOfDay()])->sum('quantity');
        $dbDay1Harness = (int) Defect::byDashboardMode('rework', 'Final Assy')->whereBetween('waktu', [Carbon::parse($day1)->startOfDay(), Carbon::parse($day1)->endOfDay()])->whereNotNull('rework_group_id')->distinct('rework_group_id')->count('rework_group_id');
        $dbDay1Dpm = ($dbDay1Harness > 0) ? round(($dbDay1Def / $dbDay1Harness) * 1000000) : null;

        $this->assertEquals(7, $b1['def_rework']);
        $this->assertEquals(2, $b1['harness_rework']);
        $this->assertEquals(3500000, $b1['dpm']);
        $this->assertEquals($dbDay1Dpm, $cardDpmDay1);

        // ==========================================
        // b) Filter 3 Hari (Harian): 2026-06-01 s/d 2026-06-03
        // ==========================================
        $res3Days = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=rework&assy=final&start_date=$day1&end_date=$day3&group_by=day");
        $res3Days->assertStatus(200);
        $dpmFinal3 = $res3Days->json('dpm_final');
        $this->assertCount(3, $dpmFinal3);

        $totalDefFromBuckets = 0;
        $totalHarnessFromBuckets = 0;
        foreach ($dpmFinal3 as $b) {
            $totalDefFromBuckets += (int) $b['def_rework'];
            $totalHarnessFromBuckets += (int) $b['harness_rework'];
        }

        // Rumus kartu frontend Batch 4 awal: Math.round(totalDefectRework / totalHarnessRework * 1000000)
        $cardDpm3Days = ($totalHarnessFromBuckets > 0) ? round(($totalDefFromBuckets / $totalHarnessFromBuckets) * 1000000) : null;

        // ==========================================
        // c) Hitungan Manual Langsung dari Database Defects
        // ==========================================
        $db3DaysQuery = Defect::byDashboardMode('rework', 'Final Assy')->whereBetween('waktu', [Carbon::parse($day1)->startOfDay(), Carbon::parse($day3)->endOfDay()]);
        $db3DaysDef = (int) (clone $db3DaysQuery)->sum('quantity');
        $db3DaysHarness = (int) (clone $db3DaysQuery)->whereNotNull('rework_group_id')->distinct('rework_group_id')->count('rework_group_id');
        $db3DaysDpm = ($db3DaysHarness > 0) ? round(($db3DaysDef / $db3DaysHarness) * 1000000) : null;

        $this->assertEquals(25, $totalDefFromBuckets);
        $this->assertEquals(6, $totalHarnessFromBuckets);
        $this->assertEquals(25, $db3DaysDef);
        $this->assertEquals(6, $db3DaysHarness);
        $this->assertEquals(4166667, $cardDpm3Days);
        $this->assertEquals(4166667, $db3DaysDpm);

        // ==========================================
        // Kasus Tambahan: Group yang barisnya terpecah di 2 tanggal
        // ==========================================
        // GRP-SPLIT: 1 baris di Day 1 (qty 2), 1 baris di Day 2 (qty 3)
        Defect::create(['waktu' => "$day1 14:00:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-SPLIT', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-1', 'jenis_sub_defect' => 'SUB-1', 'quantity' => 2]);
        Defect::create(['waktu' => "$day2 14:00:00", 'user_name' => 'Operator Rework', 'rework_group_id' => 'GRP-SPLIT', 'area_ditemukan' => 'AREA-REWORK-TEST', 'jenis_assy' => 'Final Assy', 'jenis_defect' => 'DEFECT-2', 'jenis_sub_defect' => 'SUB-2', 'quantity' => 3]);

        $resSplit = $this->actingAsAdmin()->getJson("/dashboard/analytics?mode=rework&assy=final&start_date=$day1&end_date=$day3&group_by=day");
        $resSplit->assertStatus(200);
        $dpmFinalSplit = $resSplit->json('dpm_final');

        $splitBucketsDef = 0;
        $splitBucketsHarness = 0;
        foreach ($dpmFinalSplit as $b) {
            $splitBucketsDef += (int) $b['def_rework'];
            $splitBucketsHarness += (int) $b['harness_rework'];
        }
        $splitCardDpm = ($splitBucketsHarness > 0) ? round(($splitBucketsDef / $splitBucketsHarness) * 1000000) : null;

        $dbSplitQuery = Defect::byDashboardMode('rework', 'Final Assy')->whereBetween('waktu', [Carbon::parse($day1)->startOfDay(), Carbon::parse($day3)->endOfDay()]);
        $dbSplitDef = (int) (clone $dbSplitQuery)->sum('quantity');
        $dbSplitHarness = (int) (clone $dbSplitQuery)->whereNotNull('rework_group_id')->distinct('rework_group_id')->count('rework_group_id');
        $dbSplitDpm = ($dbSplitHarness > 0) ? round(($dbSplitDef / $dbSplitHarness) * 1000000) : null;

        // Output tabel verifikasi ke STDOUT agar tertangkap di log pengujian
        echo "\n\n=== HASIL VERIFIKASI OTOMATIS BATCH 4 ===\n";
        echo "1. FILTER 1 HARI ($day1):\n";
        echo sprintf("   - Bucket 1 Trend : Defect=%d, Harness=%d, DPM=%s\n", $b1['def_rework'], $b1['harness_rework'], number_format($b1['dpm'], 0, ',', '.'));
        echo sprintf("   - Kartu Frontend : Defect=%d, Harness=%d, DPM=%s\n", $b1['def_rework'], $b1['harness_rework'], number_format($cardDpmDay1, 0, ',', '.'));
        echo sprintf("   - Hitungan DB    : Defect=%d, Harness=%d, DPM=%s\n", $dbDay1Def, $dbDay1Harness, number_format($dbDay1Dpm, 0, ',', '.'));
        echo sprintf("   - Status         : %s\n", ($cardDpmDay1 === $dbDay1Dpm) ? 'COCOK' : 'TIDAK COCOK');

        echo "\n2. FILTER 3 HARI ($day1 s/d $day3 - Tanpa Split):\n";
        foreach ($dpmFinal3 as $idx => $b) {
            echo sprintf("   - Bucket %d (%s): Defect=%d, Harness=%d, DPM=%s\n", $idx + 1, $b['label'], $b['def_rework'], $b['harness_rework'], number_format($b['dpm'], 0, ',', '.'));
        }
        echo sprintf("   - Total Bucket   : Defect=%d, Harness=%d\n", $totalDefFromBuckets, $totalHarnessFromBuckets);
        echo sprintf("   - Kartu Frontend : DPM = (%d / %d) * 1.000.000 = %s\n", $totalDefFromBuckets, $totalHarnessFromBuckets, number_format($cardDpm3Days, 0, ',', '.'));
        echo sprintf("   - Hitungan DB    : Defect=%d, Harness=%d, DPM=%s\n", $db3DaysDef, $db3DaysHarness, number_format($db3DaysDpm, 0, ',', '.'));
        echo sprintf("   - Status         : %s\n", ($cardDpm3Days === $db3DaysDpm) ? 'COCOK' : 'TIDAK COCOK');

        echo "\n3. KASUS GROUP TERPECAH DUA TANGGAL (GRP-SPLIT di Day 1 & Day 2):\n";
        foreach ($dpmFinalSplit as $idx => $b) {
            echo sprintf("   - Bucket %d (%s): Defect=%d, Harness=%d, DPM=%s\n", $idx + 1, $b['label'], $b['def_rework'], $b['harness_rework'], number_format($b['dpm'], 0, ',', '.'));
        }
        echo sprintf("   - Total Bucket   : Defect=%d, Harness=%d\n", $splitBucketsDef, $splitBucketsHarness);
        echo sprintf("   - Kartu Frontend : DPM = (%d / %d) * 1.000.000 = %s\n", $splitBucketsDef, $splitBucketsHarness, number_format($splitCardDpm, 0, ',', '.'));
        echo sprintf("   - Hitungan DB    : Defect=%d, Harness=%d (DISTINCT), DPM=%s\n", $dbSplitDef, $dbSplitHarness, number_format($dbSplitDpm, 0, ',', '.'));
        echo sprintf("   - Penjelasan     : GRP-SPLIT dihitung di bucket Day 1 (1) dan Day 2 (1), sehingga total harness kartu = %d, sedangkan DB DISTINCT = %d. Status: %s\n",
            $splitBucketsHarness,
            $dbSplitHarness,
            ($splitCardDpm === $dbSplitDpm) ? 'COCOK' : 'BERBEDA (Informasi: harness group terpecah dihitung per bucket)'
        );
        echo "=========================================\n\n";
    }
}

