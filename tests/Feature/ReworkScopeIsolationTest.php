<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ReworkHistoryController;
use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReworkScopeIsolationTest extends TestCase
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
     * Test aturan 2 syarat (sumber input + tipe area) untuk mode internal dan rework:
     * (a) operator + area internal masuk internal
     * (b) operator + area NULL masuk internal
     * (c) operator + area rework tidak masuk internal maupun rework
     * (d) rework + area rework masuk rework
     * (e) rework + area internal tidak masuk keduanya
     * (f) riwayat rework menampilkan baris (e) yang merupakan data rework walaupun mismatch area
     */
    public function test_scope_isolation_two_conditions_and_rework_history(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $areaInternal = AreaDitemukan::create([
            'name' => 'AREA-INT',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $areaRework = AreaDitemukan::create([
            'name' => 'AREA-RWK',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        // (a) Operator + area internal
        $rowA = Defect::create([
            'user_name'        => 'Operator 1',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'TERMINAL',
            'jenis_sub_defect' => 'BENT',
            'quantity'         => 1,
            'inspect_quantity' => 100,
            'area_ditemukan'   => 'AREA-INT',
            'rework_group_id'  => null,
        ]);

        // (b) Operator + area NULL
        $rowB = Defect::create([
            'user_name'        => 'Operator 1',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'WIRE',
            'jenis_sub_defect' => 'CUT',
            'quantity'         => 2,
            'inspect_quantity' => 100,
            'area_ditemukan'   => null,
            'rework_group_id'  => null,
        ]);

        // (c) Operator + area rework (mismatch: operator tapi area rework)
        $rowC = Defect::create([
            'user_name'        => 'Operator 1',
            'waktu'            => Carbon::now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'CONNECTOR',
            'jenis_sub_defect' => 'BROKEN',
            'quantity'         => 3,
            'inspect_quantity' => 100,
            'area_ditemukan'   => 'AREA-RWK',
            'rework_group_id'  => null,
        ]);

        // (d) Rework + area rework
        $groupIdD = (string) Str::uuid();
        $rowD = Defect::create([
            'user_name'           => 'reworkA',
            'waktu'               => Carbon::now(),
            'waktu_in'            => Carbon::now(),
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => 'CLIP',
            'jenis_sub_defect'    => 'DAMAGE CLIP',
            'quantity'            => 4,
            'inspect_quantity'    => null,
            'area_ditemukan'      => 'AREA-RWK',
            'kanban_assy'         => 'KB-001',
            'nameplate_lama'      => 'NP-001',
            'rework_group_id'     => $groupIdD,
            'status_rework'       => 'OPEN',
        ]);

        // (e) Rework + area internal (mismatch: rework tapi area internal)
        $groupIdE = (string) Str::uuid();
        $rowE = Defect::create([
            'user_name'           => 'reworkA',
            'waktu'               => Carbon::now(),
            'waktu_in'            => Carbon::now(),
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => 'TAPE',
            'jenis_sub_defect'    => 'WRONG TAPE',
            'quantity'            => 5,
            'inspect_quantity'    => null,
            'area_ditemukan'      => 'AREA-INT',
            'kanban_assy'         => 'KB-002',
            'nameplate_lama'      => 'NP-002',
            'rework_group_id'     => $groupIdE,
            'status_rework'       => 'OPEN',
        ]);

        // ==========================================
        // 1. Verifikasi Mode Internal (Scope Eloquent)
        // ==========================================
        $internalQuery = Defect::byDashboardMode('internal', 'Final Assy')->get();
        $internalIds = $internalQuery->pluck('id')->all();

        // (a) Masuk internal
        $this->assertContains($rowA->id, $internalIds, 'Baris (a) operator + area internal harus masuk mode internal');
        // (b) Masuk internal
        $this->assertContains($rowB->id, $internalIds, 'Baris (b) operator + area NULL harus masuk mode internal');
        // (c) Operator + area rework TIDAK masuk internal
        $this->assertNotContains($rowC->id, $internalIds, 'Baris (c) operator + area rework TIDAK boleh masuk mode internal');
        // (d) Rework + area rework TIDAK masuk internal
        $this->assertNotContains($rowD->id, $internalIds, 'Baris (d) rework + area rework TIDAK boleh masuk mode internal');
        // (e) Rework + area internal TIDAK masuk internal
        $this->assertNotContains($rowE->id, $internalIds, 'Baris (e) rework + area internal TIDAK boleh masuk mode internal');

        $this->assertEquals(3, $internalQuery->sum('quantity'), 'Total quantity internal harus 1 + 2 = 3');

        // ==========================================
        // 2. Verifikasi Mode Rework (Scope Eloquent)
        // ==========================================
        $reworkQuery = Defect::byDashboardMode('rework', 'Final Assy')->get();
        $reworkIds = $reworkQuery->pluck('id')->all();

        // (d) Rework + area rework masuk rework
        $this->assertContains($rowD->id, $reworkIds, 'Baris (d) rework + area rework harus masuk mode rework');
        // (a) Tidak masuk rework
        $this->assertNotContains($rowA->id, $reworkIds, 'Baris (a) operator TIDAK boleh masuk mode rework');
        // (b) Tidak masuk rework
        $this->assertNotContains($rowB->id, $reworkIds, 'Baris (b) operator TIDAK boleh masuk mode rework');
        // (c) Operator + area rework TIDAK masuk rework (karena bukan rework input)
        $this->assertNotContains($rowC->id, $reworkIds, 'Baris (c) operator + area rework TIDAK boleh masuk mode rework');
        // (e) Rework + area internal TIDAK masuk rework (karena area bukan tipe rework)
        $this->assertNotContains($rowE->id, $reworkIds, 'Baris (e) rework + area internal TIDAK boleh masuk mode rework');

        $this->assertEquals(4, $reworkQuery->sum('quantity'), 'Total quantity rework harus 4');

        // ==========================================
        // 3. Verifikasi Endpoint /dashboard/analytics
        // ==========================================
        // Mode internal analytics
        $resInternal = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&mode=internal&assy=final&group_by=day");
        $resInternal->assertStatus(200);
        $dpmFinalInternal = $resInternal->json('dpm_final');
        $this->assertCount(1, $dpmFinalInternal);
        $this->assertEquals(3, $dpmFinalInternal[0]['total_defect']); // 1 + 2

        // Mode rework analytics
        $resRework = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&mode=rework&assy=final&group_by=day");
        $resRework->assertStatus(200);
        $dpmFinalRework = $resRework->json('dpm_final');
        $this->assertCount(1, $dpmFinalRework);
        $this->assertEquals(4, $dpmFinalRework[0]['total_defect']); // hanya baris D

        // ==========================================
        // 4. Verifikasi Riwayat Rework (ReworkHistoryController::baseQuery & /admin/rework-history)
        // (f) Riwayat rework menampilkan baris (e) versi rework yang mismatch
        // ==========================================
        $historyQuery = ReworkHistoryController::baseQuery(new Request())->get();
        $historyGroupIds = $historyQuery->pluck('rework_group_id')->unique()->all();

        // Baris D (rework + area rework) muncul di riwayat
        $this->assertContains($groupIdD, $historyGroupIds, 'Group D harus tampil di riwayat rework');
        // Baris E (rework + area internal) HARUS TETAP TERCATAT dan tampil di riwayat rework
        $this->assertContains($groupIdE, $historyGroupIds, 'Group E (mismatch) HARUS tetap tampil di riwayat rework');
        // Baris C (operator + area rework) TIDAK tampil di riwayat rework karena bukan rework group
        $this->assertFalse(in_array(null, $historyGroupIds, true));

        // Akses halaman riwayat rework oleh Admin via HTTP
        $resHistory = $this->actingAsAdmin()->get('/admin/rework-history');
        $resHistory->assertStatus(200);
        $resHistory->assertSee('KB-001');
        $resHistory->assertSee('KB-002'); // Kanban dari baris (e) yang mismatch tetap tampil
    }
}

