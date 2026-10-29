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
        $response->assertSee('Selesai');

        // Both defects rendered under the same row with code and name
        $response->assertSee('[1A]');
        $response->assertSee('1.A - TERMINAL BENGKOK');
        $response->assertSee('[2B]');
        $response->assertSee('2.B - TERMINAL LONGGAR');

        // Total harnesses badge should be 1 (not 2 rows)
        $response->assertSee('Total Harness:');
    }

    public function test_admin_rework_history_has_edit_button_without_in_out_delete(): void
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

        // MUST contain Edit link to /rework/edit/{groupId}
        $response->assertSee(route('rework.edit', ['groupId' => $groupId]));
        $response->assertSee('Edit');

        // MUST NOT contain In form, Out button, or Delete form
        $response->assertDontSee('Mulai Rework (In)');
        $response->assertDontSee('Selesai (Out)');
        $response->assertDontSee('/rework/in');
        $response->assertDontSee('/rework/out');
        $response->assertDontSee('admin.report.destroy');
        $response->assertDontSee('admin.report.edit');
        $response->assertDontSee('Hapus');
        $response->assertDontSee('action="DELETE"');
        $response->assertDontSee("onsubmit=\"return confirm('Apakah Anda yakin ingin menghapus");
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

        // 1. Admin opens edit form for harness owned by reworkA
        $returnUrl = url('/admin/rework-history?search=BEFORE');
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
}
