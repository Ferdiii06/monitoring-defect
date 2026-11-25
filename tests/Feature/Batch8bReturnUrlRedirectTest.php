<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Batch8bReturnUrlRedirectTest extends TestCase
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

    private function validReportPayload(array $overrides = []): array
    {
        return array_merge([
            'type'             => 'Final Assy',
            'tanggal'          => now()->format('Y-m-d'),
            'jam'              => now()->format('H:i'),
            'shift'            => '1A',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '664W-C5',
            'area_ditemukan'   => 'CK',
            'ujung'            => '1',
            'end_number'       => '1',
            'jumlah'           => 1,
            'inspect_quantity' => 10,
            'jenis_defect'     => 'WIRING',
            'sub_defect'       => '1.A - CROSS CIRCUIT',
        ], $overrides);
    }

    public function test_admin_report_update_with_base_path_does_not_double_base_path(): void
    {
        $defect = $this->createFinalAssyDefect();
        $server = [
            'SCRIPT_NAME'     => '/monitoring-defect/public/index.php',
            'SCRIPT_FILENAME' => '/var/www/html/monitoring-defect/public/index.php',
            'PHP_SELF'        => '/monitoring-defect/public/index.php',
        ];

        $response = $this->actingAsAdmin()->withServerVariables($server)->put(
            'http://localhost/monitoring-defect/public/report/' . $defect->id,
            $this->validReportPayload([
                'return_url' => '/monitoring-defect/public/final-assy?page=2',
            ])
        );

        $response->assertRedirect('http://localhost/monitoring-defect/public/final-assy?page=2');
        $location = (string) $response->headers->get('Location');
        $this->assertStringNotContainsString('/monitoring-defect/public/monitoring-defect/public', $location);
    }

    public function test_admin_rework_update_with_base_path_does_not_double_base_path(): void
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

        $groupId = 'RWK-TEST-8B';
        Defect::create([
            'rework_group_id'    => $groupId,
            'waktu'              => now(),
            'waktu_in'           => now()->subMinutes(10),
            'waktu_out'          => now(),
            'status_rework'      => 'OUT',
            'user_name'          => 'rework_op',
            'shift'              => '1A',
            'jenis_assy'         => 'Final Assy',
            'jenis_mobil'        => 'CALYA',
            'conveyor'           => '664W-C5',
            'area_ditemukan'     => 'CK',
            'kanban_assy'        => 'KB-001',
            'nameplate_lama'     => 'NP-LAMA',
            'nameplate_baru'     => 'NP-BARU',
            'quantity'           => 1,
            'defect_type_id'     => $defectType->id,
            'sub_defect_type_id' => $sub->id,
            'jenis_defect'       => $defectType->name,
            'jenis_sub_defect'   => $sub->name,
        ]);

        $server = [
            'SCRIPT_NAME'     => '/monitoring-defect/public/index.php',
            'SCRIPT_FILENAME' => '/var/www/html/monitoring-defect/public/index.php',
            'PHP_SELF'        => '/monitoring-defect/public/index.php',
        ];

        $response = $this->actingAsAdmin()->withServerVariables($server)->put(
            'http://localhost/monitoring-defect/public/rework/update/' . $groupId,
            [
                'kanban_assy'    => 'KB-001',
                'nameplate'      => 'NP-BARU',
                'nameplate_lama' => 'NP-LAMA',
                'nameplate_baru' => 'NP-BARU',
                'conveyor'       => '664W-C5',
                'carline'        => 'CALYA',
                'area_ditemukan' => 'CK',
                'sub_defect_ids' => [$sub->id],
                'return_url'     => '/monitoring-defect/public/admin/rework-history?page=2',
            ]
        );

        $response->assertRedirect('http://localhost/monitoring-defect/public/admin/rework-history?page=2');
        $location = (string) $response->headers->get('Location');
        $this->assertStringNotContainsString('/monitoring-defect/public/monitoring-defect/public', $location);
    }

    public function test_admin_report_update_without_base_path_still_works(): void
    {
        $defect = $this->createFinalAssyDefect();

        $response = $this->actingAsAdmin()->put(
            route('admin.report.update', $defect->id),
            $this->validReportPayload([
                'return_url' => '/final-assy?page=2',
            ])
        );

        $response->assertRedirect('/final-assy?page=2');
    }

    public function test_admin_report_update_with_invalid_return_url_falls_back(): void
    {
        $defect = $this->createFinalAssyDefect();

        $response = $this->actingAsAdmin()->put(
            route('admin.report.update', $defect->id),
            $this->validReportPayload([
                'return_url' => 'https://evil.example/phish',
            ])
        );

        $response->assertRedirect(route('recent_defects.index'));
    }
}
