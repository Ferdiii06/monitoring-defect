<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\DpmTarget;

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
            'user_role' => 'Operator',
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
}
