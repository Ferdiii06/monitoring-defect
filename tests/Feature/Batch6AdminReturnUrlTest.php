<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\CarType;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use App\Support\AdminReturnUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Batch6AdminReturnUrlTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(string $name = 'Admin')
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => $name,
            'user_role' => 'Administrator',
        ]);
    }

    private function createFinalAssyDefect(array $overrides = []): Defect
    {
        $defectType = DefectType::firstOrCreate(['name' => 'WIRING', 'type' => 'Final Assy']);
        $sub = SubDefectType::firstOrCreate(
            ['defect_type_id' => $defectType->id, 'name' => '1.A - CROSS CIRCUIT'],
            ['code' => '1A']
        );
        AreaDitemukan::firstOrCreate(
            ['name' => 'CK'],
            ['kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY, 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        return Defect::create(array_merge([
            'rework_group_id'    => null,
            'waktu'              => now(),
            'user_name'          => 'operator_1',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'jenis_mobil'        => 'CALYA',
            'conveyor'           => '664W-C5',
            'area_ditemukan'     => 'CK',
            'ujung'              => '1',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ], $overrides));
    }

    private function createPreAssyDefect(): Defect
    {
        $carline = Carline::create([
            'name'       => 'PA-LINE-1',
            'jenis_assy' => Carline::AREA_PRE_ASSY,
        ]);
        $defectType = DefectType::firstOrCreate(['name' => 'CRIMPING', 'type' => 'Pre Assy']);
        $sub = SubDefectType::firstOrCreate(
            ['defect_type_id' => $defectType->id, 'name' => 'BENT TERMINAL'],
            ['code' => 'BT']
        );
        AreaDitemukan::firstOrCreate(
            ['name' => 'CUTTING'],
            ['kategori' => AreaDitemukan::KATEGORI_PRE_ASSY, 'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        return Defect::create([
            'rework_group_id'    => null,
            'waktu'              => now(),
            'user_name'          => 'operator_pa',
            'shift'              => '1A',
            'jenis_assy'         => 'Pre Assy',
            'carline_id'         => $carline->id,
            'area_ditemukan'     => 'CUTTING',
            'ditemukan_oleh'     => 'Operator',
            'quantity'           => 1,
            'inspect_quantity'   => 10,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ]);
    }

    public function test_final_assy_and_pre_assy_edit_links_include_return_url_with_active_query_string(): void
    {
        $finalDefect = $this->createFinalAssyDefect();
        $preDefect = $this->createPreAssyDefect();

        $finalRes = $this->actingAsAdmin()->get('/final-assy?category=all&page=1');
        $finalRes->assertOk();
        $expectedFinalHref = route('admin.report.edit', [
            'id'         => $finalDefect->id,
            'return_url' => '/final-assy?category=all&page=1',
        ]);
        $finalRes->assertSee('href="' . e($expectedFinalHref) . '"', false);

        $preRes = $this->actingAsAdmin()->get('/pre-assy?carline=all&page=1');
        $preRes->assertOk();
        $expectedPreHref = route('admin.report.edit', [
            'id'         => $preDefect->id,
            'return_url' => '/pre-assy?carline=all&page=1',
        ]);
        $preRes->assertSee('href="' . e($expectedPreHref) . '"', false);
    }

    public function test_admin_edit_and_update_report_preserve_valid_final_assy_return_url(): void
    {
        $defect = $this->createFinalAssyDefect();
        $returnUrl = '/final-assy?category=all&page=1';

        $editRes = $this->actingAsAdmin()->get(route('admin.report.edit', [
            'id'         => $defect->id,
            'return_url' => $returnUrl,
        ]));
        $editRes->assertOk();
        $editRes->assertViewHas('backRoute', $returnUrl);
        $editRes->assertSee('<input type="hidden" name="return_url" value="' . e($returnUrl) . '">', false);

        $updateRes = $this->actingAsAdmin()->put(route('admin.report.update', $defect->id), [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '664W-C5',
            'tanggal'          => now()->format('Y-m-d'),
            'jam'              => '10:00',
            'area_ditemukan'   => 'CK',
            'end_number'       => '2',
            'jenis_defect'     => $defect->jenis_defect,
            'sub_defect'       => $defect->jenis_sub_defect,
            'jumlah'           => 2,
            'inspect_quantity' => 10,
            'return_url'       => $returnUrl,
        ]);

        $updateRes->assertRedirect($returnUrl);
    }

    public function test_without_return_url_falls_back_to_recent_defects_and_rework_history(): void
    {
        $nonRework = $this->createFinalAssyDefect();
        $rework = $this->createFinalAssyDefect([
            'rework_group_id' => 'group-fallback-batch6',
            'waktu_in'        => now(),
            'status_rework'   => 'OPEN',
            'kanban_assy'     => 'KB-B6-01',
            'nameplate_lama'  => 'NP-B6-01',
        ]);

        $editNonRework = $this->actingAsAdmin()->get(route('admin.report.edit', $nonRework->id));
        $editNonRework->assertOk();
        $editNonRework->assertViewHas('backRoute', route('recent_defects.index'));

        $editRework = $this->actingAsAdmin()->get(route('rework.edit', $rework->rework_group_id));
        $editRework->assertOk();
        $editRework->assertSee('href="' . route('admin.rework_history.index') . '"', false);
    }

    public function test_malicious_or_non_whitelisted_return_urls_are_rejected_and_use_fallback(): void
    {
        $nonRework = $this->createFinalAssyDefect();
        $rework = $this->createFinalAssyDefect([
            'rework_group_id' => 'group-evil-batch6',
            'waktu_in'        => now(),
            'status_rework'   => 'OPEN',
            'kanban_assy'     => 'KB-EVIL-01',
            'nameplate_lama'  => 'NP-EVIL-01',
        ]);

        $badUrls = [
            'https://evil.com/final-assy',
            '//evil.com/final-assy',
            '/dashboard',
            '/final-assy-evil',
            "/final-assy\nLocation: https://evil.com",
        ];

        foreach ($badUrls as $bad) {
            $this->assertNull(AdminReturnUrl::sanitize($bad), "Expected null for: {$bad}");

            $resNonRework = $this->actingAsAdmin()->get(route('admin.report.edit', [
                'id'         => $nonRework->id,
                'return_url' => $bad,
            ]));
            $resNonRework->assertOk();
            $resNonRework->assertViewHas('backRoute', route('recent_defects.index'));

            $resRework = $this->actingAsAdmin()->get(route('rework.edit', [
                'groupId'    => $rework->rework_group_id,
                'return_url' => $bad,
            ]));
            $resRework->assertOk();
            $resRework->assertSee('href="' . route('admin.rework_history.index') . '"', false);
        }
    }

    public function test_rework_edit_with_final_assy_and_pre_assy_return_url_uses_it_for_back_button(): void
    {
        $rework = $this->createFinalAssyDefect([
            'rework_group_id' => 'group-valid-rwk-b6',
            'waktu_in'        => now(),
            'status_rework'   => 'OPEN',
            'kanban_assy'     => 'KB-VALID-01',
            'nameplate_lama'  => 'NP-VALID-01',
        ]);

        foreach (['/final-assy?category=all&page=2', '/pre-assy?carline=all'] as $validUrl) {
            $res = $this->actingAsAdmin()->get(route('rework.edit', [
                'groupId'    => $rework->rework_group_id,
                'return_url' => $validUrl,
            ]));
            $res->assertOk();
            $res->assertSee('href="' . e($validUrl) . '"', false);
        }
    }

    public function test_admin_return_url_supports_base_path_and_rejects_invalid_paths(): void
    {
        $basePath = '/monitoring-defect/public';

        $validUrls = [
            '/monitoring-defect/public/final-assy?category=all&page=1',
            '/monitoring-defect/public/final-assy/internal?page=2',
            '/monitoring-defect/public/pre-assy?carline=all',
            '/monitoring-defect/public/recent-defects?shift=1A',
            '/monitoring-defect/public/admin/rework-history?status=OPEN',
        ];

        foreach ($validUrls as $url) {
            $this->assertSame($url, AdminReturnUrl::sanitize($url, $basePath));
        }

        $invalidUrls = [
            '/monitoring-defect/public/dashboard',
            '/monitoring-defect/public/final-assy-evil',
            '/monitoring-defect/public-evil/final-assy',
            '/final-assy?category=all',
            '//evil.com/monitoring-defect/public/final-assy',
            'https://evil.com/monitoring-defect/public/final-assy',
            '/final-assy-evil',
        ];

        foreach ($invalidUrls as $url) {
            $this->assertNull(AdminReturnUrl::sanitize($url, $basePath), "Expected null for: {$url}");
        }
    }

    public function test_http_request_with_base_path_preserves_return_url_in_admin_edit_and_rework_edit(): void
    {
        $nonRework = $this->createFinalAssyDefect();
        $rework = $this->createFinalAssyDefect([
            'rework_group_id' => 'group-basepath-b6b',
            'waktu_in'        => now(),
            'status_rework'   => 'OPEN',
            'kanban_assy'     => 'KB-BP-01',
            'nameplate_lama'  => 'NP-BP-01',
        ]);

        $server = [
            'SCRIPT_NAME'     => '/monitoring-defect/public/index.php',
            'SCRIPT_FILENAME' => base_path('public/index.php'),
            'PHP_SELF'        => '/monitoring-defect/public/index.php',
        ];

        $returnUrl = '/monitoring-defect/public/final-assy?category=all&page=1';

        $resNonRework = $this->actingAsAdmin()->withServerVariables($server)->get(
            'http://localhost/monitoring-defect/public/report/' . $nonRework->id . '/edit?return_url=' . urlencode($returnUrl)
        );
        $resNonRework->assertOk();
        $resNonRework->assertViewHas('backRoute', $returnUrl);
        $resNonRework->assertSee('<input type="hidden" name="return_url" value="' . e($returnUrl) . '">', false);

        $resRework = $this->actingAsAdmin()->withServerVariables($server)->get(
            'http://localhost/monitoring-defect/public/rework/edit/' . $rework->rework_group_id . '?return_url=' . urlencode($returnUrl)
        );
        $resRework->assertOk();
        $resRework->assertSee('href="' . e($returnUrl) . '"', false);

        // /monitoring-defect/public/dashboard ditolak dan jatuh ke fallback
        $resBad = $this->actingAsAdmin()->withServerVariables($server)->get(
            'http://localhost/monitoring-defect/public/report/' . $nonRework->id . '/edit?return_url=' . urlencode('/monitoring-defect/public/dashboard')
        );
        $resBad->assertOk();
        $resBad->assertViewHas('backRoute', route('recent_defects.index'));
    }
}
