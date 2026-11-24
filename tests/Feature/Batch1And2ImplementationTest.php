<?php

namespace Tests\Feature;

use App\Exports\FinalAssyExport;
use App\Exports\RecentDefectsExport;
use App\Models\AreaDitemukan;
use App\Models\Carline;
use App\Models\Defect;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Tests\TestCase;

class Batch1And2ImplementationTest extends TestCase
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
     * (a) Recent Defects web, live polling, dan Excel export tidak memuat rework.
     */
    public function test_recent_defects_web_live_and_export_strictly_exclude_rework(): void
    {
        $now = Carbon::now();

        // 1. Defect operator normal (tanpa rework_group_id)
        $opDefect = Defect::create([
            'waktu'            => $now,
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'TERMINAL BENT',
            'jenis_sub_defect' => 'BENT PIN',
            'quantity'         => 2,
            'rework_group_id'  => null,
        ]);

        // 2. Defect rework (ber-rework_group_id)
        $reworkDefect = Defect::create([
            'waktu'            => $now,
            'user_name'        => 'Rework Op',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'REWORK_ISOLATED_DEFECT_XYZ',
            'jenis_sub_defect' => 'LOOSE TAPE',
            'quantity'         => 3,
            'rework_group_id'  => 'uuid-rework-batch-001',
            'status_rework'    => 'OPEN',
        ]);

        // --- A. Web: GET /recent-defects ---
        $resWeb = $this->actingAsAdmin()->get(route('recent_defects.index'));
        $resWeb->assertOk();
        $resWeb->assertSee('TERMINAL BENT');
        $resWeb->assertDontSee('REWORK_ISOLATED_DEFECT_XYZ');

        $viewDefects = $resWeb->viewData('records');
        $this->assertTrue($viewDefects->contains('id', $opDefect->id));
        $this->assertFalse($viewDefects->contains('id', $reworkDefect->id));

        // --- B. Live API: GET /api/recent-defects/live ---
        $resLive = $this->actingAsAdmin()->getJson('/api/recent-defects/live');
        $resLive->assertOk();
        $liveData = collect($resLive->json('data'));
        $this->assertTrue($liveData->contains('id', $opDefect->id));
        $this->assertFalse($liveData->contains('id', $reworkDefect->id));

        // --- C. Export: RecentDefectsExport ---
        $export = new RecentDefectsExport(new Request());
        $exportRecords = $export->query()->get();
        $this->assertTrue($exportRecords->contains('id', $opDefect->id));
        $this->assertFalse($exportRecords->contains('id', $reworkDefect->id));

        $resExportHttp = $this->actingAsAdmin()->get(route('recent_defects.export'));
        $resExportHttp->assertOk();
    }

    /**
     * (b) Dashboard recent defects live polling (/api/dashboard/recent-defects) tidak memuat rework.
     */
    public function test_dashboard_recent_defects_live_polling_excludes_rework(): void
    {
        $now = Carbon::now();

        $opDefect = Defect::create([
            'waktu'            => $now,
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Pre Assy',
            'jenis_defect'     => 'CORE WIRE DEFECT',
            'jenis_sub_defect' => 'CUT',
            'quantity'         => 1,
            'rework_group_id'  => null,
        ]);

        $reworkDefect = Defect::create([
            'waktu'            => $now,
            'user_name'        => 'Rework Op',
            'jenis_assy'       => 'Pre Assy',
            'jenis_defect'     => 'REWORK CRIMPING',
            'jenis_sub_defect' => 'LOOSE',
            'quantity'         => 5,
            'rework_group_id'  => 'uuid-rework-batch-002',
            'status_rework'    => 'OPEN',
        ]);

        $res = $this->actingAsAdmin()->getJson('/api/dashboard/recent-defects');
        $res->assertOk();

        $items = collect($res->json('data'));
        $this->assertTrue($items->contains('id', $opDefect->id));
        $this->assertFalse($items->contains('id', $reworkDefect->id));
    }

    /**
     * (c) adminDestroyReport menolak baris rework dan tidak menghapus record.
     */
    public function test_admin_destroy_report_rejects_rework_rows_and_does_not_delete(): void
    {
        $reworkDefect = Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Rework Op',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'REWORK NOT DELETABLE',
            'jenis_sub_defect' => 'DETAIL',
            'quantity'         => 1,
            'rework_group_id'  => 'uuid-rework-batch-protect-del',
            'status_rework'    => 'OPEN',
        ]);

        // 1. Request via Web (redirect back dengan flash error)
        $resWeb = $this->actingAsAdmin()
            ->from(route('recent_defects.index'))
            ->delete(route('admin.report.destroy', $reworkDefect->id));

        $resWeb->assertRedirect(route('recent_defects.index'));
        $resWeb->assertSessionHas('error', 'Data defect rework tidak dapat dihapus dari menu ini. Silakan gunakan menu Riwayat Rework.');

        // Record tetap ada di database
        $this->assertNotNull(Defect::find($reworkDefect->id));

        // 2. Request via JSON (HTTP 422 Unprocessable Entity)
        $resJson = $this->actingAsAdmin()
            ->deleteJson(route('admin.report.destroy', $reworkDefect->id));

        $resJson->assertStatus(422);
        $resJson->assertJson([
            'message' => 'Data defect rework tidak dapat dihapus dari menu ini. Silakan gunakan menu Riwayat Rework.',
        ]);

        // Record tetap ada di database
        $this->assertNotNull(Defect::find($reworkDefect->id));

        // 3. Bandingkan dengan defect biasa (bukan rework) yang sah dihapus
        $normalDefect = Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Operator QA',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'NORMAL DEFECT',
            'jenis_sub_defect' => 'DETAIL',
            'quantity'         => 1,
            'rework_group_id'  => null,
        ]);

        $resDelNormal = $this->actingAsAdmin()
            ->from(route('recent_defects.index'))
            ->delete(route('admin.report.destroy', $normalDefect->id));

        $resDelNormal->assertRedirect(route('recent_defects.index'));
        $this->assertNull(Defect::find($normalDefect->id));
    }

    /**
     * (d) Final Assy report dan export tidak memuat rework serta tidak ada lagi kategori rework.
     */
    public function test_final_assy_report_and_export_strictly_exclude_rework_and_rework_category(): void
    {
        $areaInspeksi = AreaDitemukan::create([
            'name' => 'AREA-INSPEKSI-FA',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $normalFinalDefect = Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Inspektor QA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => $areaInspeksi->name,
            'jenis_defect'     => 'INS-DEFECT-VALID',
            'jenis_sub_defect' => 'DETAIL',
            'quantity'         => 2,
            'rework_group_id'  => null,
        ]);

        $reworkDefect = Defect::create([
            'waktu'            => Carbon::now(),
            'user_name'        => 'Rework FA',
            'jenis_assy'       => 'Final Assy',
            'area_ditemukan'   => 'REWORK AREA',
            'jenis_defect'     => 'REWORK-DEFECT-ISOLATED',
            'jenis_sub_defect' => 'DETAIL',
            'quantity'         => 4,
            'rework_group_id'  => 'uuid-rework-batch-fa-001',
            'status_rework'    => 'OPEN',
        ]);

        // 1. Web Final Assy: tidak menampilkan rework defect maupun tab/kategori rework
        $resFa = $this->actingAsAdmin()->get(route('final_assy.index'));
        $resFa->assertOk();
        $resFa->assertSee('INS-DEFECT-VALID');
        $resFa->assertDontSee('REWORK-DEFECT-ISOLATED');
        $resFa->assertDontSee('Riwayat Defect Rework');

        $faDefects = $resFa->viewData('records');
        $this->assertTrue($faDefects->contains('id', $normalFinalDefect->id));
        $this->assertFalse($faDefects->contains('id', $reworkDefect->id));

        // 2. Akses dengan ?category=rework: harus di-normalize/fallback ke non-rework
        $resFaReworkCategory = $this->actingAsAdmin()->get(route('final_assy.index', ['category' => 'rework']));
        $resFaReworkCategory->assertOk();
        $resFaReworkCategory->assertDontSee('REWORK-DEFECT-ISOLATED');

        // 3. Export Final Assy: query strictly exclude rework
        $exportAll = new FinalAssyExport(new Request(['category' => 'all']));
        $exportAllRecords = $exportAll->query()->get();
        $this->assertTrue($exportAllRecords->contains('id', $normalFinalDefect->id));
        $this->assertFalse($exportAllRecords->contains('id', $reworkDefect->id));

        $exportRework = new FinalAssyExport(new Request(['category' => 'rework']));
        $exportReworkRecords = $exportRework->query()->get();
        $this->assertFalse($exportReworkRecords->contains('id', $reworkDefect->id));

        // 4. Submenu /final-assy/rework harus redirect ke admin.rework_history.index
        $resSubmenuRework = $this->actingAsAdmin()->get(route('final_assy.rework'));
        $resSubmenuRework->assertRedirect(route('admin.rework_history.index'));
    }

    /**
     * (e) Migration rename carline DFM ke NSN bersifat idempotent tanpa duplikasi.
     */
    public function test_rename_dfm_migration_is_idempotent_and_prevents_duplicate(): void
    {
        $migration = require database_path('migrations/2026_11_24_000001_rename_dfm_carline_to_nsn.php');

        // Skenario 1: Jika sudah ada carline bernama 'NSN', up() tidak mengubah apa-apa lagi dan tidak melempar error
        DB::table('carlines')->updateOrInsert(
            ['name' => 'NSN'],
            ['car_type_id' => null, 'created_at' => now(), 'updated_at' => now()]
        );

        $countBefore = DB::table('carlines')->where('name', 'NSN')->count();
        $this->assertGreaterThanOrEqual(1, $countBefore);

        // Jalankan up() saat 'NSN' sudah ada
        $migration->up();

        $countAfter = DB::table('carlines')->where('name', 'NSN')->count();
        $this->assertEquals($countBefore, $countAfter, 'Jumlah carline NSN tidak bertambah saat up() dijalankan ulang.');

        // Skenario 2: Simulasi DFM ada, up() mengubah DFM menjadi NSN
        DB::table('carlines')->whereRaw('LOWER(name) = ?', ['nsn'])->delete();

        $dfmId = DB::table('carlines')->insertGetId([
            'name'        => 'DFM',
            'car_type_id' => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $migration->up();

        $renamed = DB::table('carlines')->find($dfmId);
        $this->assertNotNull($renamed);
        $this->assertEquals('NSN', $renamed->name);

        // Bersihkan
        DB::table('carlines')->where('id', $dfmId)->delete();
    }
}
