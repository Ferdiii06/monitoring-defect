<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\CarType;
use App\Models\Defect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Batch8AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(string $name = 'Admin QA')
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => $name,
            'user_role' => 'Administrator',
        ]);
    }

    public function test_a02_error_debug_removed_and_ignored(): void
    {
        $this->assertFileDoesNotExist(public_path('error_debug.txt'));
        $gitignore = file_get_contents(base_path('.gitignore'));
        $this->assertStringContainsString('/public/error_debug.txt', $gitignore);

        $controllerSource = file_get_contents(app_path('Http/Controllers/Api/DefectApiController.php'));
        $this->assertStringNotContainsString('error_debug.txt', $controllerSource);
    }

    public function test_a03_d01_d02_d03_polling_views_escape_html_and_render_edit_delete_safely(): void
    {
        $views = [
            route('final_assy.index'),
            route('pre_assy.index'),
            route('recent_defects.index'),
            route('log_system.index'),
        ];

        foreach ($views as $url) {
            $res = $this->actingAsAdmin()->get($url);
            $res->assertOk();
            $res->assertSee('function escapeHtml(', false);
            $res->assertSee("document.addEventListener('visibilitychange'", false);
            $res->assertSee('if (document.hidden) return;', false);
            $res->assertSee("r.headers.get('content-type')", false);
            $res->assertSee('!r.ok || r.redirected', false);
            $res->assertDontSee('[Polling] Gagal fetch', false);
        }

        $reportViews = [
            route('final_assy.index'),
            route('pre_assy.index'),
            route('recent_defects.index'),
        ];

        foreach ($reportViews as $url) {
            $res = $this->actingAsAdmin()->get($url);
            $res->assertOk();
            $res->assertDontSee('<a href="/report/${item.id}/edit"', false);
            $res->assertSee('const editUrlTemplate =', false);
            $res->assertSee('const destroyUrlTemplate =', false);
            $res->assertSee('return_url=', false);
            $res->assertSee('<input type="hidden" name="_method" value="DELETE">', false);
            $res->assertSee('>Hapus</button>', false);
        }

        // D-03: Pre Assy live polling badge text matches SSR
        $preRes = $this->actingAsAdmin()->get(route('pre_assy.index'));
        $preRes->assertOk();
        $preRes->assertDontSee('border-blue-200 bg-blue-50 text-blue-700 tracking-wider">Ditemukan oleh Inspektor</span>', false);
        $preRes->assertDontSee('border-amber-200 bg-amber-50 text-amber-700 tracking-wider">Ditemukan oleh Operator</span>', false);
        $preRes->assertSee('border-blue-200 bg-blue-50 text-blue-700 tracking-wider">Inspektor</span>', false);
        $preRes->assertSee('border-amber-200 bg-amber-50 text-amber-700 tracking-wider">Operator</span>', false);
        $preRes->assertSee('escapeHtml(item.user_name', false);
    }

    public function test_d01_polling_edit_and_destroy_templates_respect_base_path(): void
    {
        $server = [
            'SCRIPT_NAME'     => '/monitoring-defect/public/index.php',
            'SCRIPT_FILENAME' => base_path('public/index.php'),
            'PHP_SELF'        => '/monitoring-defect/public/index.php',
        ];

        $res = $this->actingAsAdmin()->withServerVariables($server)
            ->get('http://localhost/monitoring-defect/public/final-assy');

        $res->assertOk();
        $content = $res->getContent();
        $this->assertStringContainsString('\/monitoring-defect\/public\/report\/__ID__\/edit', $content);
        $this->assertStringContainsString('\/monitoring-defect\/public\/report\/__ID__', $content);
        $this->assertStringContainsString('/monitoring-defect/public/api/final-assy/live', $content);
    }

    public function test_b01_get_stats_excludes_rework_from_recent_operators(): void
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200),
        ]);

        // 1 operator defect + 2 distinct rework users within 15 minutes
        Defect::create([
            'waktu'            => now()->subMinutes(2),
            'user_name'        => 'OP_REAL_1',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'MAZDA',
            'conveyor'         => 'J72A-12B.LHD',
            'jenis_defect'     => 'WIRING',
            'jenis_sub_defect' => '1A',
            'quantity'         => 1,
            'inspect_quantity' => 10,
            'rework_group_id'  => null,
        ]);

        Defect::create([
            'waktu'            => now()->subMinutes(1),
            'user_name'        => 'reworkA',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'MAZDA',
            'conveyor'         => 'J72A-12B.LHD',
            'jenis_defect'     => 'WIRING',
            'jenis_sub_defect' => '1A',
            'quantity'         => 1,
            'rework_group_id'  => '11111111-1111-1111-1111-111111111111',
        ]);

        Defect::create([
            'waktu'            => now()->subMinutes(1),
            'user_name'        => 'reworkB',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'MAZDA',
            'conveyor'         => 'J72A-12B.LHD',
            'jenis_defect'     => 'WIRING',
            'jenis_sub_defect' => '1A',
            'quantity'         => 1,
            'rework_group_id'  => '22222222-2222-2222-2222-222222222222',
        ]);

        $res = $this->getJson('/api/dashboard/stats');
        $res->assertOk();
        $res->assertJsonPath('activeUsers', 1);
    }

    public function test_b04_area_ditemukan_and_car_type_reject_case_insensitive_duplicates(): void
    {
        AreaDitemukan::create([
            'name'     => 'CK_TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $resAreaStore = $this->actingAsAdmin()->post(route('admin.master.area_ditemukans.store'), [
            'name'     => 'ck_test',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);
        $resAreaStore->assertSessionHasErrors('name');

        $area2 = AreaDitemukan::create([
            'name'     => 'V1_TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);
        $resAreaUpdate = $this->actingAsAdmin()->put(route('admin.master.area_ditemukans.update', $area2->id), [
            'name'     => 'ck_test',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);
        $resAreaUpdate->assertSessionHasErrors('name');

        $car1 = CarType::create(['name' => 'MAZDA_TEST']);
        $resCarStore = $this->actingAsAdmin()->post(route('admin.master.car_types.store'), [
            'name' => 'mazda_test',
        ]);
        $resCarStore->assertSessionHasErrors('name');

        $car2 = CarType::create(['name' => 'NISSAN_TEST']);
        $resCarUpdate = $this->actingAsAdmin()->put(route('admin.master.car_types.update', $car2->id), [
            'name' => 'mazda_test',
        ]);
        $resCarUpdate->assertSessionHasErrors('name');
    }
}
