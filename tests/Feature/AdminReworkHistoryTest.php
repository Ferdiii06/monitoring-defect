<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReworkHistoryTest extends TestCase
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

    private function actingAsOperator()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Operator QA',
            'user_role' => 'User',
            'current_shift' => '1A',
        ]);
    }

    private function actingAsRework()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'reworkA',
            'user_role' => 'Rework',
            'current_shift' => '1A',
        ]);
    }

    public function test_guest_cannot_access_admin_rework_history(): void
    {
        $response = $this->get('/admin/rework-history');
        $response->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_admin_rework_history(): void
    {
        $response = $this->actingAsOperator()->get('/admin/rework-history');
        $response->assertRedirect(route('operator.home'));
    }

    public function test_rework_user_cannot_access_admin_rework_history(): void
    {
        $response = $this->actingAsRework()->get('/admin/rework-history');
        $response->assertRedirect(route('rework.index'));
    }

    public function test_admin_can_access_admin_rework_history_page(): void
    {
        $response = $this->actingAsAdmin()->get('/admin/rework-history');
        $response->assertStatus(200);
        $response->assertSee('Riwayat Rework');
        $response->assertSee('Daftar riwayat harness rework Final Assy');
    }

    public function test_rework_harness_grouped_correctly_by_group_id(): void
    {
        // 1. Setup master data
        $reworkArea = AreaDitemukan::create([
            'name' => 'REWORK AREA 1',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $defectType = DefectType::create([
            'name' => 'TERMINAL DEFECT',
            'type' => 'Final Assy',
        ]);

        $subDefect1 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - TERMINAL BENGKOK',
        ]);

        $subDefect2 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '2.B - TERMINAL LONGGAR',
        ]);

        $groupId = (string) Str::uuid();
        $inTime = Carbon::parse('2026-10-25 10:00:00');
        $outTime = Carbon::parse('2026-10-25 10:30:00');

        // 2 Harness defects under the same rework_group_id
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $inTime,
            'waktu_in'            => $inTime,
            'waktu_out'           => $outTime,
            'durasi_rework_menit' => 30,
            'status_rework'       => 'Selesai',
            'kanban_assy'         => 'KB-TEST-999',
            'nameplate_lama'      => 'NP-TEST-888',
            'user_name'           => 'rework_operator_1',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'area_ditemukan'      => $reworkArea->name,
            'defect_type_id'      => $defectType->id,
            'sub_defect_type_id'  => $subDefect1->id,
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect1->name,
            'quantity'            => 1,
        ]);

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $inTime,
            'waktu_in'            => $inTime,
            'waktu_out'           => $outTime,
            'durasi_rework_menit' => 30,
            'status_rework'       => 'Selesai',
            'kanban_assy'         => 'KB-TEST-999',
            'nameplate_lama'      => 'NP-TEST-888',
            'user_name'           => 'rework_operator_1',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'area_ditemukan'      => $reworkArea->name,
            'defect_type_id'      => $defectType->id,
            'sub_defect_type_id'  => $subDefect2->id,
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect2->name,
            'quantity'            => 1,
        ]);

        $response = $this->actingAsAdmin()->get('/admin/rework-history');
        $response->assertStatus(200);

        // Harness metadata visible
        $response->assertSee('KB-TEST-999');
        $response->assertSee('NP-TEST-888');
        $response->assertSee('rework_operator_1');
        $response->assertSee('30 menit');
        $response->assertSee('CLOSE');

        // Both defects rendered under the same row with code and name
        $response->assertSee('[1A]');
        $response->assertSee('1.A - TERMINAL BENGKOK');
        $response->assertSee('[2B]');
        $response->assertSee('2.B - TERMINAL LONGGAR');

        // Total harnesses badge should be 1 (not 2 rows)
        $response->assertSee('Total Harness:');
    }

    public function test_admin_rework_history_has_edit_and_delete_button_without_in_out(): void
    {
        $reworkArea = AreaDitemukan::create([
            'name' => 'REWORK AREA 1',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $groupId = (string) Str::uuid();
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-READONLY-1',
            'nameplate_lama'      => 'NP-READONLY-1',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => 'TERMINAL DEFECT',
            'jenis_sub_defect'    => '1.A - DEFECT READONLY',
            'area_ditemukan'      => $reworkArea->name,
            'quantity'            => 1,
        ]);

        $response = $this->actingAsAdmin()->get('/admin/rework-history');
        $response->assertStatus(200);

        // MUST contain Edit link to /rework/edit/{groupId} and Delete button
        $response->assertSee(route('rework.edit', ['groupId' => $groupId]));
        $response->assertSee('Edit');
        $response->assertSee(route('admin.rework_history.destroy', $groupId));
        $response->assertSee('Hapus');

        // MUST NOT contain In form, Out button
        $response->assertDontSee('Mulai Rework (In)');
        $response->assertDontSee('Selesai (Out)');
        $response->assertDontSee('/rework/in');
        $response->assertDontSee('/rework/out');
        $response->assertDontSee('admin.report.destroy');
        $response->assertDontSee('admin.report.edit');
    }

    public function test_admin_can_open_and_save_edit_for_other_operator_harness_and_redirects_to_admin_history(): void
    {
        $reworkArea = AreaDitemukan::create([
            'name' => 'REWORK AREA 1',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);

        $subDefect1 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        $subDefect2 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.B - SHORT CIRCUIT',
        ]);

        $groupId = (string) Str::uuid();
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-BEFORE',
            'nameplate_lama'      => 'NP-BEFORE',
            'user_name'           => 'reworkA', // created by reworkA
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect1->name,
            'sub_defect_type_id'  => $subDefect1->id,
            'area_ditemukan'      => $reworkArea->name,
            'quantity'            => 1,
        ]);

        // 1. Admin opens edit form for harness owned by reworkA with relative return_url
        $returnUrl = '/admin/rework-history?search=BEFORE';
        $responseGet = $this->actingAsAdmin()->get(route('rework.edit', ['groupId' => $groupId, 'return_url' => $returnUrl]));
        $responseGet->assertStatus(200);
        $responseGet->assertSee('KB-BEFORE');
        $responseGet->assertSee(e($returnUrl));

        // 2. Admin submits update
        $responsePut = $this->actingAsAdmin()->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-AFTER-ADMIN',
            'nameplate'      => 'NP-AFTER-ADMIN',
            'sub_defect_ids' => [$subDefect2->id],
            'return_url'     => $returnUrl,
        ]);

        // 3. Admin redirected back to return_url
        $responsePut->assertRedirect($returnUrl);

        // 4. Verify DB: creator user_name (reworkA) and area_ditemukan preserved
        $updated = Defect::where('rework_group_id', $groupId)->first();
        $this->assertEquals('KB-AFTER-ADMIN', $updated->kanban_assy);
        $this->assertEquals('NP-AFTER-ADMIN', $updated->nameplate_lama);
        $this->assertEquals('reworkA', $updated->user_name);
        $this->assertEquals($reworkArea->name, $updated->area_ditemukan);
        $this->assertNotNull($updated->area_ditemukan);
    }

    public function test_invalid_return_url_ignored_and_falls_back_to_admin_history(): void
    {
        $reworkArea = AreaDitemukan::create([
            'name' => 'REWORK AREA 1',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);

        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        $groupId = (string) Str::uuid();
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-TEST-URL',
            'nameplate_lama'      => 'NP-TEST-URL',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect->name,
            'sub_defect_type_id'  => $subDefect->id,
            'area_ditemukan'      => $reworkArea->name,
            'quantity'            => 1,
        ]);

        $invalidUrls = [
            'https://contoh.com',
            '//contoh.com',
            'http://contoh.com/phishing',
            'javascript:alert(1)',
            '\\\\contoh.com',
            '/admin/rework-history-fake',
        ];

        foreach ($invalidUrls as $invalidUrl) {
            // 1. GET edit with invalid return_url -> view does not render invalidUrl as back link or hidden input
            $resGet = $this->actingAsAdmin()->get(route('rework.edit', ['groupId' => $groupId, 'return_url' => $invalidUrl]));
            $resGet->assertStatus(200);
            $resGet->assertDontSee($invalidUrl, false);
            $resGet->assertSee(route('admin.rework_history.index'));

            // 2. PUT update with invalid return_url -> redirect falls back to route admin.rework_history.index
            $resPut = $this->actingAsAdmin()->put(route('rework.update', $groupId), [
                'kanban_assy'    => 'KB-TEST-URL',
                'nameplate'      => 'NP-TEST-URL',
                'sub_defect_ids' => [$subDefect->id],
                'return_url'     => $invalidUrl,
            ]);
            $resPut->assertRedirect(route('admin.rework_history.index'));
        }
    }

    public function test_update_adds_defect_with_deterministic_rework_area_id_terkecil(): void
    {
        // Two rework areas with different IDs
        $areaBesar = AreaDitemukan::create([
            'name' => 'AREA_REWORK_BESAR',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $areaKecil = AreaDitemukan::create([
            'name' => 'AREA_REWORK_KECIL',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        // Ensure $areaKecil has smaller id
        if ($areaKecil->id > $areaBesar->id) {
            $temp = $areaKecil;
            $areaKecil = $areaBesar;
            $areaBesar = $temp;
        }

        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);

        $subDefect1 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        $subDefect2 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.B - SHORT CIRCUIT',
        ]);

        // Legacy harness without area_ditemukan (null)
        $groupId = (string) Str::uuid();
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-DETERM',
            'nameplate_lama'      => 'NP-DETERM',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect1->name,
            'sub_defect_type_id'  => $subDefect1->id,
            'area_ditemukan'      => null,
            'quantity'            => 1,
        ]);

        // Update adding a second defect
        $resPut = $this->actingAsAdmin()->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-DETERM',
            'nameplate'      => 'NP-DETERM',
            'sub_defect_ids' => [$subDefect1->id, $subDefect2->id],
        ]);
        $resPut->assertRedirect(route('admin.rework_history.index'));

        $defects = Defect::where('rework_group_id', $groupId)->orderBy('id')->get();
        $this->assertCount(2, $defects);
        foreach ($defects as $d) {
            $this->assertNotNull($d->area_ditemukan);
            $this->assertEquals($areaKecil->name, $d->area_ditemukan);
        }
    }

    public function test_update_fails_when_template_has_no_area_and_master_rework_area_empty(): void
    {
        // Delete all rework areas
        AreaDitemukan::where('tipe', AreaDitemukan::TIPE_REWORK)->delete();

        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);

        $subDefect1 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        $subDefect2 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.B - SHORT CIRCUIT',
        ]);

        $groupId = (string) Str::uuid();
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-NO-AREA',
            'nameplate_lama'      => 'NP-NO-AREA',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect1->name,
            'sub_defect_type_id'  => $subDefect1->id,
            'area_ditemukan'      => null,
            'quantity'            => 1,
        ]);

        $initialCount = Defect::where('rework_group_id', $groupId)->count();

        // Update with new defect must fail and store NO new row
        $resPut = $this->actingAsAdmin()->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-NO-AREA',
            'nameplate'      => 'NP-NO-AREA',
            'sub_defect_ids' => [$subDefect1->id, $subDefect2->id],
        ]);

        $resPut->assertSessionHasErrors([
            'area_ditemukan' => 'Area bertipe Rework belum diatur. Hubungi admin untuk menambahkannya di Master Data > Area Ditemukan.',
        ]);

        // Verifikasi tidak ada baris baru tersimpan
        $this->assertEquals($initialCount, Defect::where('rework_group_id', $groupId)->count());
    }

    public function test_rework_operator_cannot_edit_harness_owned_by_another_operator(): void
    {
        $reworkArea = AreaDitemukan::create([
            'name' => 'REWORK AREA 1',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);

        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        $groupId = (string) Str::uuid();
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-OWNER-A',
            'nameplate_lama'      => 'NP-OWNER-A',
            'user_name'           => 'reworkA', // owned by reworkA
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect->name,
            'sub_defect_type_id'  => $subDefect->id,
            'area_ditemukan'      => $reworkArea->name,
            'quantity'            => 1,
        ]);

        // reworkB attempts to open edit form -> 403 Forbidden
        $responseGet = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkB',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->get(route('rework.edit', $groupId));
        $responseGet->assertStatus(403);

        // reworkB attempts to update -> 403 Forbidden
        $responsePut = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkB',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-HACK',
            'nameplate'      => 'NP-HACK',
            'sub_defect_ids' => [$subDefect->id],
        ]);
        $responsePut->assertStatus(403);
    }

    public function test_operator_and_guest_are_rejected_from_rework_edit_routes(): void
    {
        $groupId = (string) Str::uuid();

        // Guest to rework edit -> redirected to login
        $resGuest = $this->get(route('rework.edit', $groupId));
        $resGuest->assertRedirect(route('login'));

        // Operator (User) to rework edit -> redirected to operator.home
        $resOperator = $this->actingAsOperator()->get(route('rework.edit', $groupId));
        $resOperator->assertRedirect(route('operator.home'));

        // Operator (User) to rework update -> redirected to operator.home
        $resOperatorPut = $this->actingAsOperator()->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-FAIL',
            'nameplate'      => 'NP-FAIL',
            'sub_defect_ids' => [1],
        ]);
        $resOperatorPut->assertRedirect(route('operator.home'));
    }

    public function test_filters_by_search_np_and_date_range_and_status(): void
    {
        $reworkArea = AreaDitemukan::create([
            'name' => 'REWORK AREA 1',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $group1 = (string) Str::uuid();
        Defect::create([
            'rework_group_id' => $group1,
            'waktu'          => Carbon::parse('2026-10-10 09:00:00'),
            'waktu_in'       => Carbon::parse('2026-10-10 09:00:00'),
            'waktu_out'      => Carbon::parse('2026-10-10 09:15:00'),
            'status_rework'  => 'Selesai',
            'kanban_assy'    => 'KB-ALPHA',
            'nameplate_lama' => 'NP-ALPHA-123',
            'user_name'      => 'reworkA',
            'shift'          => '1A',
            'jenis_assy'     => 'Final Assy',
            'jenis_defect'   => 'TERMINAL DEFECT',
            'jenis_sub_defect' => '1.A - DEFECT ALPHA',
            'area_ditemukan' => $reworkArea->name,
            'quantity'       => 1,
        ]);

        $group2 = (string) Str::uuid();
        Defect::create([
            'rework_group_id' => $group2,
            'waktu'          => Carbon::parse('2026-10-20 14:00:00'),
            'waktu_in'       => Carbon::parse('2026-10-20 14:00:00'),
            'status_rework'  => 'Berjalan',
            'kanban_assy'    => 'KB-BETA',
            'nameplate_lama' => 'NP-BETA-456',
            'user_name'      => 'reworkB',
            'shift'          => '2B',
            'jenis_assy'     => 'Final Assy',
            'jenis_defect'   => 'TERMINAL DEFECT',
            'jenis_sub_defect' => '2.B - DEFECT BETA',
            'area_ditemukan' => $reworkArea->name,
            'quantity'       => 1,
        ]);

        // 1. Filter by Search NP
        $resSearch = $this->actingAsAdmin()->get('/admin/rework-history?search=ALPHA');
        $resSearch->assertStatus(200);
        $resSearch->assertSee('NP-ALPHA-123');
        $resSearch->assertDontSee('NP-BETA-456');

        // 2. Filter by Status Berjalan
        $resStatus = $this->actingAsAdmin()->get('/admin/rework-history?status=Berjalan');
        $resStatus->assertStatus(200);
        $resStatus->assertSee('NP-BETA-456');
        $resStatus->assertDontSee('NP-ALPHA-123');

        // 3. Filter by Date Range (only covering Oct 10)
        $resDate = $this->actingAsAdmin()->get('/admin/rework-history?date_range=2026-10-09+to+2026-10-11');
        $resDate->assertStatus(200);
        $resDate->assertSee('NP-ALPHA-123');
        $resDate->assertDontSee('NP-BETA-456');
    }

    public function test_sidebar_contains_riwayat_rework_link_with_active_state(): void
    {
        $response = $this->actingAsAdmin()->get('/admin/rework-history');
        $response->assertStatus(200);

        // Sidebar link exists
        $response->assertSee(route('admin.rework_history.index'));
        $response->assertSee('Riwayat Rework');
    }

    public function test_admin_can_download_rework_history_excel_export(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $response = $this->actingAsAdmin()->get(route('admin.rework_history.export'));
        $response->assertStatus(200);

        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('riwayat_rework_' . now()->format('Y-m-d') . '.xlsx', function (\App\Exports\ReworkHistoryExport $export) {
            return true;
        });
    }

    public function test_non_admin_cannot_access_rework_history_export(): void
    {
        // Guest
        $resGuest = $this->get(route('admin.rework_history.export'));
        $resGuest->assertRedirect(route('login'));

        // Operator
        $resOperator = $this->actingAsOperator()->get(route('admin.rework_history.export'));
        $resOperator->assertRedirect(route('operator.home'));

        // Rework Operator
        $resRework = $this->actingAsRework()->get(route('admin.rework_history.export'));
        $resRework->assertRedirect(route('rework.index'));
    }

    public function test_rework_history_view_has_export_excel_button_and_script(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.rework_history.index'));
        $response->assertStatus(200);
        $response->assertSee('Export');
        $response->assertSee('exportExcel()', false);
        $response->assertSee(route('admin.rework_history.export'), false);
    }

    public function test_rework_history_export_generates_valid_rows_and_headings(): void
    {
        $reworkArea = AreaDitemukan::create([
            'name' => 'REWORK AREA 1',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $defectType = DefectType::create([
            'name' => 'TERMINAL DEFECT',
            'type' => 'Final Assy',
        ]);

        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - TERMINAL BENGKOK',
        ]);

        $groupId = (string) Str::uuid();
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-EXPORT-TEST',
            'nameplate_lama'      => 'NP-EXPORT-TEST',
            'user_name'           => 'rework_exp_user',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'defect_type_id'      => $defectType->id,
            'sub_defect_type_id'  => $subDefect->id,
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect->name,
            'area_ditemukan'      => $reworkArea->name,
            'quantity'            => 1,
        ]);

        $request = new \Illuminate\Http\Request(['search' => 'EXPORT-TEST']);
        $export = new \App\Exports\ReworkHistoryExport($request);

        $headings = $export->headings();
        $this->assertContains('Group ID', $headings);
        $this->assertContains('Kanban Assy', $headings);
        $this->assertContains('Status Rework', $headings);

        $rows = $export->query()->get();
        $this->assertCount(1, $rows);

        $mapped = $export->map($rows->first());
        $this->assertEquals($groupId, $mapped[1]);
        $this->assertEquals('OPEN', $mapped[4]);
        $this->assertEquals('KB-EXPORT-TEST', $mapped[6]);
        $this->assertEquals('NP-EXPORT-TEST', $mapped[7]);
        $this->assertEquals($reworkArea->name, $mapped[14]);
        $this->assertEquals('rework_exp_user', $mapped[21]);
        $this->assertEquals('1A', $mapped[22]);
        $this->assertEquals('1A', $mapped[23]); // code derived
        $this->assertEquals('TERMINAL DEFECT', $mapped[24]);
        $this->assertEquals('1.A - TERMINAL BENGKOK', $mapped[25]);
        $this->assertEquals(1, $mapped[26]);
    }

    public function test_recent_defects_displays_rework_edit_and_active_delete_for_all_rows(): void
    {
        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);
        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        $normalDefect = Defect::create([
            'rework_group_id'  => null,
            'waktu'            => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'user_name'        => 'Operator QA',
            'quantity'         => 1,
            'inspect_quantity' => 10,
        ]);

        $reworkGroupId = (string) Str::uuid();
        $reworkDefect = Defect::create([
            'rework_group_id'  => $reworkGroupId,
            'waktu'            => now(),
            'waktu_in'         => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'user_name'        => 'reworkA',
            'kanban_assy'      => 'KB-TEST-001',
            'nameplate_lama'   => 'NP-TEST-001',
            'status_rework'    => 'OPEN',
            'quantity'         => 1,
        ]);

        $resRecent = $this->actingAsAdmin()->get(route('recent_defects.index'));
        $resRecent->assertStatus(200);

        // Baris rework: link edit ke rework.edit, tombol hapus form aktif
        $resRecent->assertSee(route('rework.edit', ['groupId' => $reworkGroupId]));
        $resRecent->assertSee(route('admin.report.destroy', $reworkDefect->id));

        // Baris non-rework: link edit ke admin.report.edit, tombol hapus form aktif
        $resRecent->assertSee(route('admin.report.edit', $normalDefect->id));
        $resRecent->assertSee(route('admin.report.destroy', $normalDefect->id));
    }

    public function test_report_final_assy_strictly_excludes_rework_and_includes_operator_rows(): void
    {
        $reworkArea = AreaDitemukan::create([
            'name' => 'AREA KHUSUS REWORK',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $internalArea = AreaDitemukan::create([
            'name' => 'CK AREA',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);
        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        // 1. Operator defect dengan area internal (HARUS MUNCUL)
        $opDefectNormal = Defect::create([
            'rework_group_id'  => null,
            'waktu'            => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'area_ditemukan'   => $internalArea->name,
            'user_name'        => 'Operator Normal',
            'quantity'         => 1,
        ]);

        // 2. Operator defect dengan area bertipe rework (HARUS TETAP MUNCUL sesuai keputusan bisnis)
        $opDefectReworkArea = Defect::create([
            'rework_group_id'  => null,
            'waktu'            => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'area_ditemukan'   => $reworkArea->name,
            'user_name'        => 'Operator With Rework Area',
            'quantity'         => 2,
        ]);

        // 3. Rework defect dengan area bertipe rework (TIDAK BOLEH MUNCUL)
        $reworkDefect1 = Defect::create([
            'rework_group_id'  => (string) Str::uuid(),
            'waktu'            => now(),
            'waktu_in'         => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'area_ditemukan'   => $reworkArea->name,
            'user_name'        => 'reworkA',
            'kanban_assy'      => 'KB-EXCLUDE-RW1',
            'nameplate_lama'   => 'NP-EXCLUDE-RW1',
            'status_rework'    => 'OPEN',
            'quantity'         => 1,
        ]);

        // 4. Rework defect dengan area bertipe internal (TIDAK BOLEH MUNCUL)
        $reworkDefect2 = Defect::create([
            'rework_group_id'  => (string) Str::uuid(),
            'waktu'            => now(),
            'waktu_in'         => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'area_ditemukan'   => $internalArea->name,
            'user_name'        => 'reworkA',
            'kanban_assy'      => 'KB-EXCLUDE-RW2',
            'nameplate_lama'   => 'NP-EXCLUDE-RW2',
            'status_rework'    => 'OPEN',
            'quantity'         => 1,
        ]);

        $resFinal = $this->actingAsAdmin()->get(route('final_assy.index'));
        $resFinal->assertStatus(200);

        // Operator rows muncul
        $resFinal->assertSee('Operator Normal');
        $resFinal->assertSee('Operator With Rework Area');

        // Rework rows tidak muncul sama sekali
        $resFinal->assertDontSee('KB-EXCLUDE-RW1');
        $resFinal->assertDontSee('KB-EXCLUDE-RW2');
        $resFinal->assertDontSee(route('rework.edit', ['groupId' => $reworkDefect1->rework_group_id]));
        $resFinal->assertDontSee(route('rework.edit', ['groupId' => $reworkDefect2->rework_group_id]));

        // Edit link di Final Assy hanya admin.report.edit
        $resFinal->assertSee(route('admin.report.edit', $opDefectNormal->id));
        $resFinal->assertSee(route('admin.report.edit', $opDefectReworkArea->id));
    }

    public function test_final_assy_export_strictly_excludes_rework_rows_and_matches_table(): void
    {
        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);
        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        // 1 Operator row
        Defect::create([
            'rework_group_id'  => null,
            'waktu'            => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'user_name'        => 'Operator Export Test',
            'quantity'         => 1,
        ]);

        // 1 Rework row
        Defect::create([
            'rework_group_id'  => (string) Str::uuid(),
            'waktu'            => now(),
            'waktu_in'         => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'user_name'        => 'reworkA',
            'kanban_assy'      => 'KB-EXPORT-REWORK-EXCLUDE',
            'nameplate_lama'   => 'NP-EXPORT-REWORK-EXCLUDE',
            'status_rework'    => 'OPEN',
            'quantity'         => 1,
        ]);

        $request = new \Illuminate\Http\Request();
        $export = new \App\Exports\FinalAssyExport($request);
        $exportRows = $export->query()->get();

        $this->assertCount(1, $exportRows);
        $this->assertEquals('Operator Export Test', $exportRows->first()->user_name);
        $this->assertNull($exportRows->first()->rework_group_id);
    }

    public function test_admin_edit_report_on_rework_row_redirects_to_rework_edit(): void
    {
        $reworkGroupId = (string) Str::uuid();
        $reworkDefect = Defect::create([
            'rework_group_id'  => $reworkGroupId,
            'waktu'            => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'WIRING',
            'jenis_sub_defect' => 'CROSS CIRCUIT',
            'user_name'        => 'reworkA',
            'quantity'         => 1,
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.report.edit', [
            'id' => $reworkDefect->id,
            'return_url' => '/recent-defects',
        ]));

        $response->assertRedirect(route('rework.edit', [
            'groupId'    => $reworkGroupId,
            'return_url' => '/recent-defects',
        ]));
    }

    public function test_admin_update_report_on_rework_row_is_rejected_without_modifying_data(): void
    {
        $area = AreaDitemukan::create([
            'name' => 'REWORK AREA ORIG',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $reworkGroupId = (string) Str::uuid();
        $reworkDefect = Defect::create([
            'rework_group_id'  => $reworkGroupId,
            'waktu'            => now(),
            'waktu_in'         => now(),
            'area_ditemukan'   => $area->name,
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'WIRING',
            'jenis_sub_defect' => 'CROSS CIRCUIT',
            'quantity'         => 1,
            'inspect_quantity' => null,
            'user_name'        => 'reworkA',
            'kanban_assy'      => 'KB-SAFEGUARD',
            'nameplate_lama'   => 'NP-SAFEGUARD',
        ]);

        $response = $this->actingAsAdmin()->from('/final-assy')->put(route('admin.report.update', $reworkDefect->id), [
            'type'             => 'Pre Assy',
            'area_ditemukan'   => 'CK',
            'jumlah'           => 99,
            'inspect_quantity' => 500,
            'tanggal'          => now()->format('Y-m-d'),
            'jam'              => '10:00',
        ]);

        $response->assertRedirect('/final-assy');
        $response->assertSessionHas('error');

        $fresh = $reworkDefect->fresh();
        $this->assertEquals($area->name, $fresh->area_ditemukan);
        $this->assertEquals('Final Assy', $fresh->jenis_assy);
        $this->assertEquals(1, $fresh->quantity);
        $this->assertNull($fresh->inspect_quantity);
    }

    public function test_admin_destroy_report_allows_deleting_rework_row_and_non_rework_row(): void
    {
        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);
        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        $reworkDefect = Defect::create([
            'rework_group_id'  => (string) Str::uuid(),
            'waktu'            => now(),
            'waktu_in'         => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'user_name'        => 'reworkA',
            'quantity'         => 1,
        ]);

        $normalDefect = Defect::create([
            'rework_group_id'  => null,
            'waktu'            => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect->name,
            'user_name'        => 'Operator Normal',
            'quantity'         => 1,
        ]);

        // 1. Operator ditolak 403
        $resOp = $this->actingAsOperator()->delete(route('admin.report.destroy', $reworkDefect->id));
        $resOp->assertStatus(403);
        $this->assertDatabaseHas('defects', ['id' => $reworkDefect->id]);

        // 2. Akun Rework ditolak 403
        $resRw = $this->actingAsRework()->delete(route('admin.report.destroy', $reworkDefect->id));
        $resRw->assertStatus(403);
        $this->assertDatabaseHas('defects', ['id' => $reworkDefect->id]);

        // 3. Admin berhasil hapus baris rework
        $resRework = $this->actingAsAdmin()->from('/recent-defects')->delete(route('admin.report.destroy', $reworkDefect->id));
        $resRework->assertRedirect('/recent-defects');
        $resRework->assertSessionHas('success');
        $this->assertDatabaseMissing('defects', ['id' => $reworkDefect->id]);

        // 4. Admin berhasil hapus baris normal
        $resNormal = $this->actingAsAdmin()->from('/recent-defects')->delete(route('admin.report.destroy', $normalDefect->id));
        $resNormal->assertRedirect('/recent-defects');
        $resNormal->assertSessionHas('success');
        $this->assertDatabaseMissing('defects', ['id' => $normalDefect->id]);
    }

    public function test_admin_can_delete_rework_group_and_non_admin_is_rejected_403(): void
    {
        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);
        $subDefect1 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);
        $subDefect2 = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.B - SHORT CIRCUIT',
        ]);

        $groupIdTarget = (string) Str::uuid();
        $defect1 = Defect::create([
            'rework_group_id'  => $groupIdTarget,
            'waktu'            => now(),
            'waktu_in'         => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect1->name,
            'user_name'        => 'reworkA',
            'quantity'         => 1,
        ]);
        $defect2 = Defect::create([
            'rework_group_id'  => $groupIdTarget,
            'waktu'            => now(),
            'waktu_in'         => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect2->name,
            'user_name'        => 'reworkA',
            'quantity'         => 1,
        ]);

        $groupIdOther = (string) Str::uuid();
        $defectOther = Defect::create([
            'rework_group_id'  => $groupIdOther,
            'waktu'            => now(),
            'waktu_in'         => now(),
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => $defectType->name,
            'jenis_sub_defect' => $subDefect1->name,
            'user_name'        => 'reworkB',
            'quantity'         => 1,
        ]);

        // Verifikasi tombol hapus dan konfirmasi dialog di halaman admin
        $resPage = $this->actingAsAdmin()->get('/admin/rework-history');
        $resPage->assertStatus(200);
        $resPage->assertSee(route('admin.rework_history.destroy', $groupIdTarget));
        $resPage->assertSee('Sebanyak 2 defect yang ada di dalam harness ini akan ikut terhapus', false);

        // Operator ditolak 403
        $resOp = $this->actingAsOperator()->delete(route('admin.rework_history.destroy', $groupIdTarget));
        $resOp->assertStatus(403);
        $this->assertDatabaseHas('defects', ['id' => $defect1->id]);
        $this->assertDatabaseHas('defects', ['id' => $defect2->id]);

        // Rework role ditolak 403
        $resRw = $this->actingAsRework()->delete(route('admin.rework_history.destroy', $groupIdTarget));
        $resRw->assertStatus(403);
        $this->assertDatabaseHas('defects', ['id' => $defect1->id]);
        $this->assertDatabaseHas('defects', ['id' => $defect2->id]);

        // Admin berhasil hapus
        $resAdmin = $this->actingAsAdmin()->from('/admin/rework-history')->delete(route('admin.rework_history.destroy', $groupIdTarget));
        $resAdmin->assertRedirect('/admin/rework-history');
        $resAdmin->assertSessionHas('success');

        // Kedua baris defect di groupIdTarget terhapus
        $this->assertDatabaseMissing('defects', ['id' => $defect1->id]);
        $this->assertDatabaseMissing('defects', ['id' => $defect2->id]);

        // Defect group lain tetap utuh
        $this->assertDatabaseHas('defects', ['id' => $defectOther->id]);
    }

    public function test_sanitize_admin_return_url_allows_recent_defects_and_rework_history(): void
    {
        $controller = app(\App\Http\Controllers\ReworkController::class);
        $method = new \ReflectionMethod($controller, 'sanitizeAdminReturnUrl');
        $method->setAccessible(true);

        // Allowed relative URLs
        $this->assertEquals('/recent-defects?x=1', $method->invoke($controller, '/recent-defects?x=1'));
        $this->assertEquals('/recent-defects', $method->invoke($controller, '/recent-defects'));
        $this->assertEquals('/admin/rework-history?x=1', $method->invoke($controller, '/admin/rework-history?x=1'));
        $this->assertEquals('/admin/rework-history', $method->invoke($controller, '/admin/rework-history'));

        // Disallowed / Malicious URLs
        $this->assertNull($method->invoke($controller, 'https://evil.test'));
        $this->assertNull($method->invoke($controller, 'http://evil.test'));
        $this->assertNull($method->invoke($controller, '//evil.test'));
        $this->assertNull($method->invoke($controller, '/\\evil.test'));
        $this->assertNull($method->invoke($controller, '\\\\evil.test'));
        $this->assertNull($method->invoke($controller, 'javascript:alert(1)'));
        $this->assertNull($method->invoke($controller, '/lain'));
        $this->assertNull($method->invoke($controller, '/recent-defects-hack'));
    }

    public function test_rework_edit_for_admin_uses_admin_layout_and_for_rework_role_uses_operator_layout(): void
    {
        $area = AreaDitemukan::create([
            'name' => 'REWORK AREA 1',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);
        $defectType = DefectType::create([
            'name' => 'WIRING',
            'type' => 'Final Assy',
        ]);
        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - CROSS CIRCUIT',
        ]);

        $reworkGroupId = (string) Str::uuid();
        Defect::create([
            'rework_group_id'     => $reworkGroupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'OPEN',
            'kanban_assy'         => 'KB-LAYOUT-TEST',
            'nameplate_lama'      => 'NP-LAYOUT-TEST',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'jenis_defect'        => $defectType->name,
            'jenis_sub_defect'    => $subDefect->name,
            'sub_defect_type_id'  => $subDefect->id,
            'area_ditemukan'      => $area->name,
            'quantity'            => 1,
        ]);

        // 1. Admin GET edit -> uses layouts.app (has adminSidebar)
        $resAdmin = $this->actingAsAdmin()->get(route('rework.edit', $reworkGroupId));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('id="adminSidebar"', false);
        $resAdmin->assertSee('Admin • Riwayat Rework');

        // 2. Rework operator GET edit -> uses layouts.operator (does NOT have adminSidebar)
        $resRework = $this->actingAsRework()->get(route('rework.edit', $reworkGroupId));
        $resRework->assertStatus(200);
        $resRework->assertDontSee('id="adminSidebar"', false);
        $resRework->assertSee('Rework QA • Edit');
    }
}
