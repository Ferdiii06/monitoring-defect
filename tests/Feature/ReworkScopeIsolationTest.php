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
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
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
        // 1. Verifikasi Operator Input (Scope operatorInput)
        // ==========================================
        $operatorQuery = Defect::operatorInput()->where('jenis_assy', 'Final Assy')->get();
        $operatorIds = $operatorQuery->pluck('id')->all();

        // (a) Masuk operator input
        $this->assertContains($rowA->id, $operatorIds, 'Baris (a) operator + area internal harus masuk operatorInput');
        // (b) Masuk operator input
        $this->assertContains($rowB->id, $operatorIds, 'Baris (b) operator + area NULL harus masuk operatorInput');
        // (c) Masuk operator input (karena rework_group_id IS NULL)
        $this->assertContains($rowC->id, $operatorIds, 'Baris (c) operator harus masuk operatorInput');
        // (d) Rework TIDAK masuk operator input
        $this->assertNotContains($rowD->id, $operatorIds, 'Baris (d) rework TIDAK boleh masuk operatorInput');
        // (e) Rework TIDAK masuk operator input
        $this->assertNotContains($rowE->id, $operatorIds, 'Baris (e) rework TIDAK boleh masuk operatorInput');

        $this->assertEquals(6, $operatorQuery->sum('quantity'), 'Total quantity operator harus 1 + 2 + 3 = 6');

        // ==========================================
        // 2. Verifikasi Rework Record (Scope reworkRecord)
        // ==========================================
        $reworkQuery = Defect::reworkRecord()->where('jenis_assy', 'Final Assy')->get();
        $reworkIds = $reworkQuery->pluck('id')->all();

        // (d) Rework masuk reworkRecord
        $this->assertContains($rowD->id, $reworkIds, 'Baris (d) rework harus masuk reworkRecord');
        // (e) Rework masuk reworkRecord
        $this->assertContains($rowE->id, $reworkIds, 'Baris (e) rework harus masuk reworkRecord');
        // (a) Tidak masuk rework
        $this->assertNotContains($rowA->id, $reworkIds, 'Baris (a) operator TIDAK boleh masuk reworkRecord');
        // (b) Tidak masuk rework
        $this->assertNotContains($rowB->id, $reworkIds, 'Baris (b) operator TIDAK boleh masuk reworkRecord');
        // (c) Tidak masuk rework
        $this->assertNotContains($rowC->id, $reworkIds, 'Baris (c) operator TIDAK boleh masuk reworkRecord');

        $this->assertEquals(9, $reworkQuery->sum('quantity'), 'Total quantity rework harus 4 + 5 = 9');

        // ==========================================
        // 3. Verifikasi Endpoint /dashboard/analytics
        // ==========================================
        // Analytics hanya membaca operatorInput
        $resFinal = $this->actingAsAdmin()->getJson("/dashboard/analytics?start_date={$today}&end_date={$today}&mode=final&assy=final&group_by=day");
        $resFinal->assertStatus(200);
        $dpmFinal = $resFinal->json('dpm_final');
        $this->assertCount(1, $dpmFinal);
        $this->assertEquals(6, $dpmFinal[0]['total_defect']); // 1 + 2 + 3
        $this->assertEquals(0, $dpmFinal[0]['def_rework']); // rework di-nolkan di metrik dashboard

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

