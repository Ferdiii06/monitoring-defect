<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\DpmTarget;
use App\Models\Carline;
use App\Models\CarType;

class AdminDpmTargetTest extends TestCase
{
    use RefreshDatabase;

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
            'user_name' => 'Operator 1',
            'user_role' => 'User',
        ]);
    }

    public function test_guest_cannot_access_dpm_targets(): void
    {
        $response = $this->get('/master/dpm-targets');
        $response->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_dpm_targets(): void
    {
        $response = $this->actingAsOperator()->get('/master/dpm-targets');
        $response->assertRedirect(route('operator.home'));
    }

    public function test_admin_can_access_dpm_targets_page(): void
    {
        $response = $this->actingAsAdmin()->get('/master/dpm-targets');

        $response->assertStatus(200);
        $response->assertSee('Master Target DPM');
        $response->assertSee('Final Assy');
        $response->assertSee('Pre Assy');
    }

    public function test_admin_can_create_valid_dpm_target(): void
    {
        $response = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-01-01',
            'period_end'   => '2026-01-31',
            'target_dpm'   => '15.50',
        ]);

        $response->assertRedirect(route('admin.master.dpm_targets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dpm_targets', [
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-01-01',
            'period_end'   => '2026-01-31',
            'target_dpm'   => 15.50,
        ]);
    }

    public function test_validation_fails_for_period_end_before_start_and_negative_value(): void
    {
        // 1. Tanggal akhir sebelum tanggal mulai
        $response1 = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-02-15',
            'period_end'   => '2026-02-01',
            'target_dpm'   => '10.00',
        ]);
        $response1->assertSessionHasErrors(['period_end']);

        // 2. Nilai DPM negatif
        $response2 = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-02-01',
            'period_end'   => '2026-02-28',
            'target_dpm'   => '-5.00',
        ]);
        $response2->assertSessionHasErrors(['target_dpm']);
    }

    public function test_overlap_rejected_for_same_assy_but_allowed_for_different_assy(): void
    {
        // Target dasar Final Assy: 1 Jan 2026 - 31 Jan 2026
        DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-01-01',
            'period_end'   => '2026-01-31',
            'target_dpm'   => 10.00,
        ]);

        // Percobaan 1: Tumpang tindih pada Final Assy (15 Jan - 15 Feb) -> Harus GAGAL
        $responseOverlap = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-01-15',
            'period_end'   => '2026-02-15',
            'target_dpm'   => 12.00,
        ]);
        $responseOverlap->assertSessionHasErrors(['period_start']);
        $this->assertDatabaseMissing('dpm_targets', [
            'jenis_assy' => 'Final Assy',
            'target_dpm' => 12.00,
        ]);

        // Percobaan 2: Periode yang sama persis tapi untuk Pre Assy -> Harus BERHASIL
        $responsePreAssy = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'   => 'Pre Assy',
            'period_start' => '2026-01-15',
            'period_end'   => '2026-02-15',
            'target_dpm'   => 25.00,
        ]);
        $responsePreAssy->assertRedirect(route('admin.master.dpm_targets.index'));
        $responsePreAssy->assertSessionHas('success');
        $this->assertDatabaseHas('dpm_targets', [
            'jenis_assy' => 'Pre Assy',
            'target_dpm' => 25.00,
        ]);
    }

    public function test_edit_does_not_conflict_with_itself(): void
    {
        $target = DpmTarget::create([
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-03-01',
            'period_end'   => '2026-03-31',
            'target_dpm'   => 10.00,
        ]);

        // Update target yang sama tanpa mengubah tanggal, hanya nilai DPM
        $response = $this->actingAsAdmin()->put("/master/dpm-targets/{$target->id}", [
            'jenis_assy'   => 'Final Assy',
            'period_start' => '2026-03-01',
            'period_end'   => '2026-03-31',
            'target_dpm'   => '8.50',
        ]);

        $response->assertRedirect(route('admin.master.dpm_targets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dpm_targets', [
            'id'         => $target->id,
            'target_dpm' => 8.50,
        ]);
    }

    public function test_admin_can_delete_dpm_target(): void
    {
        $target = DpmTarget::create([
            'jenis_assy'   => 'Pre Assy',
            'period_start' => '2026-04-01',
            'period_end'   => '2026-04-30',
            'target_dpm'   => 20.00,
        ]);

        $response = $this->actingAsAdmin()->delete("/master/dpm-targets/{$target->id}");

        $response->assertRedirect(route('admin.master.dpm_targets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('dpm_targets', [
            'id' => $target->id,
        ]);
    }

    public function test_cross_validation_rejects_carline_id_for_final_assy_and_conveyor_group_for_pre_assy(): void
    {
        $carType = CarType::create(['name' => 'TOYOTA']);
        $carline = Carline::create(['car_type_id' => null, 'name' => 'PRE-CARLINE-1']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '664W-C5']);

        // 1. Final Assy dengan carline_id harus DITOLAK
        $response1 = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'   => 'Final Assy',
            'carline_id'   => $carline->id,
            'period_start' => '2026-05-01',
            'period_end'   => '2026-05-31',
            'target_dpm'   => '10.00',
        ]);
        $response1->assertSessionHasErrors(['carline_id']);

        // 2. Pre Assy dengan conveyor_group harus DITOLAK
        $response2 = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'     => 'Pre Assy',
            'conveyor_group' => 'C5',
            'period_start'   => '2026-05-01',
            'period_end'     => '2026-05-31',
            'target_dpm'     => '15.00',
        ]);
        $response2->assertSessionHasErrors(['conveyor_group']);
    }

    public function test_specific_target_does_not_conflict_with_default_target_in_same_period(): void
    {
        $carType = CarType::create(['name' => 'TOYOTA']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '664W-C5']);

        // 1. Buat target default Final Assy (carline_id = null, conveyor_group = null)
        DpmTarget::create([
            'jenis_assy'     => 'Final Assy',
            'carline_id'     => null,
            'conveyor_group' => null,
            'period_start'   => '2026-06-01',
            'period_end'     => '2026-06-30',
            'target_dpm'     => 50.00,
        ]);

        // 2. Buat target spesifik Final Assy untuk konveyor C5 pada periode yang sama persis -> Harus BERHASIL
        $response = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'     => 'Final Assy',
            'conveyor_group' => 'C5',
            'period_start'   => '2026-06-01',
            'period_end'     => '2026-06-30',
            'target_dpm'     => 25.00,
        ]);

        $response->assertRedirect(route('admin.master.dpm_targets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dpm_targets', [
            'jenis_assy'     => 'Final Assy',
            'conveyor_group' => 'C5',
            'target_dpm'     => 25.00,
        ]);
    }

    public function test_specific_target_for_conveyor_a_conflicts_with_conveyor_a_but_not_conveyor_b(): void
    {
        $carType = CarType::create(['name' => 'TOYOTA']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '664W-C5']);
        Carline::create(['car_type_id' => $carType->id, 'name' => '664W-C9']);

        // 1. Target spesifik C5: 1 Juli s/d 31 Juli
        DpmTarget::create([
            'jenis_assy'     => 'Final Assy',
            'conveyor_group' => 'C5',
            'period_start'   => '2026-07-01',
            'period_end'     => '2026-07-31',
            'target_dpm'     => 20.00,
        ]);

        // 2. Buat target C5 overlap (15 Juli - 15 Agustus) -> Harus GAGAL
        $responseConflict = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'     => 'Final Assy',
            'conveyor_group' => 'C5',
            'period_start'   => '2026-07-15',
            'period_end'     => '2026-08-15',
            'target_dpm'     => 22.00,
        ]);
        $responseConflict->assertSessionHasErrors(['period_start']);

        // 3. Buat target C9 overlap (15 Juli - 15 Agustus) -> Harus BERHASIL (tidak saling memblokir)
        $responseAllowed = $this->actingAsAdmin()->post('/master/dpm-targets', [
            'jenis_assy'     => 'Final Assy',
            'conveyor_group' => 'C9',
            'period_start'   => '2026-07-15',
            'period_end'     => '2026-08-15',
            'target_dpm'     => 30.00,
        ]);
        $responseAllowed->assertRedirect(route('admin.master.dpm_targets.index'));
        $responseAllowed->assertSessionHas('success');

        $this->assertDatabaseHas('dpm_targets', [
            'jenis_assy'     => 'Final Assy',
            'conveyor_group' => 'C9',
            'target_dpm'     => 30.00,
        ]);
    }

    public function test_carline_destroy_blocked_when_used_in_dpm_targets(): void
    {
        $carline = Carline::create([
            'car_type_id' => null,
            'name'        => 'PRE-CARLINE-TARGETED',
        ]);

        DpmTarget::create([
            'jenis_assy'   => 'Pre Assy',
            'carline_id'   => $carline->id,
            'period_start' => '2026-08-01',
            'period_end'   => '2026-08-31',
            'target_dpm'   => 40.00,
        ]);

        // Hapus carline yang terikat di DpmTarget -> Harus DITOLAK
        $response = $this->actingAsAdmin()->delete("/master/carlines/{$carline->id}");
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('carlines', ['id' => $carline->id]);
    }
}
