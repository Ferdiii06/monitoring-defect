<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReworkEditTest extends TestCase
{
    use RefreshDatabase;

    private function createFinalAssyDefect(string $typeName, string $subName, string $code): SubDefectType
    {
        $defectType = DefectType::firstOrCreate(
            ['name' => $typeName, 'type' => 'Final Assy']
        );

        return SubDefectType::firstOrCreate(
            ['defect_type_id' => $defectType->id, 'name' => $subName],
            ['code' => $code]
        );
    }

    public function test_pemilik_group_bisa_mengubah_kanban_dan_np_semua_baris_ikut_berubah_np_uppercase(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $sub2 = $this->createFinalAssyDefect('WIRING', '1.B - SHORT CIRCUIT', '1B');

        $groupId = 'uuid-edit-kanban-np';
        $waktuIn = now()->subMinutes(20);

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-OLD',
            'nameplate_lama'      => 'NP-OLD',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-OLD',
            'nameplate_lama'      => 'NP-OLD',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub2->defect_type_id,
            'sub_defect_type_id'  => $sub2->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub2->name,
        ]);

        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->put(route('rework.update', $groupId), [
            'kanban_assy'    => '  KB-NEW-123  ',
            'nameplate'      => '  np-new-abc  ',
            'sub_defect_ids' => [$sub1->id, $sub2->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');

        $rows = Defect::where('rework_group_id', $groupId)->get();
        $this->assertCount(2, $rows);

        foreach ($rows as $row) {
            $this->assertEquals('KB-NEW-123', $row->kanban_assy);
            $this->assertEquals('NP-NEW-ABC', $row->nameplate_lama);
            $this->assertEquals('reworkA', $row->user_name);
        }
    }

    public function test_tambah_defect_membuat_baris_baru_dengan_rework_group_id_sama_dan_waktu_tidak_berubah(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $sub2 = $this->createFinalAssyDefect('WIRING', '1.B - SHORT CIRCUIT', '1B');

        $groupId = 'uuid-add-defect';
        $waktuIn = now()->subMinutes(30)->startOfMinute();

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-ORIGINAL',
            'nameplate_lama'      => 'NP-ORIGINAL',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);

        $this->assertEquals(1, Defect::where('rework_group_id', $groupId)->count());

        // Tambahkan defect kedua ($sub2)
        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-ORIGINAL',
            'nameplate'      => 'NP-ORIGINAL',
            'sub_defect_ids' => [$sub1->id, $sub2->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');

        $rows = Defect::where('rework_group_id', $groupId)->orderBy('id')->get();
        $this->assertCount(2, $rows);

        $newRow = $rows[1];
        $this->assertEquals($sub2->id, $newRow->sub_defect_type_id);
        $this->assertEquals($groupId, $newRow->rework_group_id);
        $this->assertEquals($waktuIn->format('Y-m-d H:i:s'), $newRow->waktu_in->format('Y-m-d H:i:s'));
        $this->assertNull($newRow->waktu_out);
        $this->assertEquals('reworkA', $newRow->user_name);
    }

    public function test_hapus_defect_mengurangi_baris_tetapi_menghapus_semua_defect_ditolak(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $sub2 = $this->createFinalAssyDefect('WIRING', '1.B - SHORT CIRCUIT', '1B');

        $groupId = 'uuid-del-defect';
        $waktuIn = now()->subMinutes(10);

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-DEL',
            'nameplate_lama'      => 'NP-DEL',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);
        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-DEL',
            'nameplate_lama'      => 'NP-DEL',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub2->defect_type_id,
            'sub_defect_type_id'  => $sub2->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub2->name,
        ]);

        $session = [
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        // 1. Coba hapus semua defect (array kosong) -> Ditolak validasi
        $resEmpty = $this->withSession($session)->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-DEL',
            'nameplate'      => 'NP-DEL',
            'sub_defect_ids' => [],
        ]);
        $resEmpty->assertSessionHasErrors(['sub_defect_ids']);
        $this->assertEquals(2, Defect::where('rework_group_id', $groupId)->count());

        // 2. Hapus defect $sub2, sisakan $sub1 -> Berhasil
        $resKeepOne = $this->withSession($session)->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-DEL',
            'nameplate'      => 'NP-DEL',
            'sub_defect_ids' => [$sub1->id],
        ]);
        $resKeepOne->assertRedirect(route('rework.index'));
        $resKeepOne->assertSessionHas('success');

        $rows = Defect::where('rework_group_id', $groupId)->get();
        $this->assertCount(1, $rows);
        $this->assertEquals($sub1->id, $rows->first()->sub_defect_type_id);
    }

    public function test_sub_defect_duplikat_dalam_satu_group_ditolak(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');

        $groupId = 'uuid-dup-test';
        $waktuIn = now()->subMinutes(5);

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-DUP',
            'nameplate_lama'      => 'NP-DUP',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);

        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-DUP',
            'nameplate'      => 'NP-DUP',
            'sub_defect_ids' => [$sub1->id, $sub1->id],
        ]);

        $response->assertSessionHasErrors(['sub_defect_ids']);
    }

    public function test_akun_rework_lain_tidak_bisa_mengedit_group_milik_akun_lain(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $groupId = 'uuid-reworkA-exclusive';

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-A',
            'nameplate_lama'      => 'NP-A',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);

        $sessionReworkB = [
            'logged_in'     => true,
            'user_name'     => 'reworkB',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        // 1. GET edit form -> 403
        $resGet = $this->withSession($sessionReworkB)->get(route('rework.edit', $groupId));
        $resGet->assertStatus(403);

        // 2. PUT update -> 403
        $resPut = $this->withSession($sessionReworkB)->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-HACKED',
            'nameplate'      => 'NP-HACKED',
            'sub_defect_ids' => [$sub1->id],
        ]);
        $resPut->assertStatus(403);

        $row = Defect::where('rework_group_id', $groupId)->first();
        $this->assertEquals('KB-A', $row->kanban_assy);
    }

    public function test_user_tidak_bisa_mengakses_edit(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $groupId = 'uuid-no-user';

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-X',
            'nameplate_lama'      => 'NP-X',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);

        $sessionUser = [
            'logged_in'     => true,
            'user_name'     => 'Operator QA',
            'user_role'     => 'User',
            'current_shift' => '1A',
        ];

        // GET edit -> redirect operator home
        $resGet = $this->withSession($sessionUser)->get(route('rework.edit', $groupId));
        $resGet->assertRedirect(route('operator.home'));

        // PUT update -> redirect operator home
        $resPut = $this->withSession($sessionUser)->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-USER-EDIT',
            'nameplate'      => 'NP-USER-EDIT',
            'sub_defect_ids' => [$sub1->id],
        ]);
        $resPut->assertRedirect(route('operator.home'));
    }

    public function test_admin_bisa_mengedit_group_milik_akun_rework_mana_pun_akun_pembuat_tidak_berubah(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $sub2 = $this->createFinalAssyDefect('WIRING', '1.B - SHORT CIRCUIT', '1B');
        $groupId = 'uuid-admin-edit-any';

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-ORIGINAL',
            'nameplate_lama'      => 'NP-ORIGINAL',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);

        $adminSession = [
            'logged_in'     => true,
            'user_name'     => 'Admin QA',
            'user_role'     => 'Administrator',
            'current_shift' => '1A',
        ];

        // 1. Admin bisa buka form edit
        $resGet = $this->withSession($adminSession)->get(route('rework.edit', $groupId));
        $resGet->assertStatus(200);
        $resGet->assertSee('KB-ORIGINAL');

        // 2. Admin simpan perubahan (ubah Kanban, NP, dan ganti defect jadi $sub2)
        $resPut = $this->withSession($adminSession)->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-BY-ADMIN',
            'nameplate'      => 'NP-BY-ADMIN',
            'sub_defect_ids' => [$sub2->id],
        ]);
        $resPut->assertRedirect(route('rework.index'));
        $resPut->assertSessionHas('success');

        $updated = Defect::where('rework_group_id', $groupId)->first();
        $this->assertEquals('KB-BY-ADMIN', $updated->kanban_assy);
        $this->assertEquals('NP-BY-ADMIN', $updated->nameplate_lama);
        $this->assertEquals($sub2->id, $updated->sub_defect_type_id);

        // Akun pembuat tetap pemilik asli (reworkA), bukan Admin QA!
        $this->assertEquals('reworkA', $updated->user_name);
        $this->assertEquals('1A', $updated->shift);
    }

    public function test_admin_tidak_bisa_post_in_out_dan_tidak_melihat_form_in_di_rework(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');

        $adminSession = [
            'logged_in'     => true,
            'user_name'     => 'Admin QA',
            'user_role'     => 'Administrator',
            'current_shift' => '1A',
        ];

        // 1. GET /rework -> status 200, form In TIDAK tampil
        $resIndex = $this->withSession($adminSession)->get(route('rework.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertDontSee('Mulai Rework (In)');

        // 2. POST /rework/in ditolak -> redirect dashboard
        $resIn = $this->withSession($adminSession)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-ADMIN-IN',
            'nameplate'      => 'NP-ADMIN-IN',
            'sub_defect_ids' => [$sub1->id],
        ]);
        $resIn->assertRedirect(route('dashboard'));

        // 3. POST /rework/out ditolak -> redirect dashboard
        $resOut = $this->withSession($adminSession)->post(route('rework.store_out', 'some-group-id'));
        $resOut->assertRedirect(route('dashboard'));
    }

    public function test_edit_pada_group_selesai_tetap_mempertahankan_waktu_out_dan_durasi(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $sub2 = $this->createFinalAssyDefect('WIRING', '1.B - SHORT CIRCUIT', '1B');
        $groupId = 'uuid-completed-edit';

        $waktuIn = now()->subMinutes(45)->startOfMinute();
        $waktuOut = now()->subMinutes(15)->startOfMinute();
        $durasi = 30;

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'waktu_out'           => $waktuOut,
            'durasi_rework_menit' => $durasi,
            'status_rework'       => 'Selesai',
            'kanban_assy'         => 'KB-COMPLETED',
            'nameplate_lama'      => 'NP-COMPLETED',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);

        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-COMPLETED-EDIT',
            'nameplate'      => 'NP-COMPLETED-EDIT',
            'sub_defect_ids' => [$sub1->id, $sub2->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');

        $rows = Defect::where('rework_group_id', $groupId)->get();
        $this->assertCount(2, $rows);

        foreach ($rows as $row) {
            $this->assertEquals('Selesai', $row->status_rework);
            $this->assertEquals('KB-COMPLETED-EDIT', $row->kanban_assy);
            $this->assertEquals('NP-COMPLETED-EDIT', $row->nameplate_lama);
            $this->assertEquals($waktuIn->format('Y-m-d H:i:s'), $row->waktu_in->format('Y-m-d H:i:s'));
            $this->assertEquals($waktuOut->format('Y-m-d H:i:s'), $row->waktu_out->format('Y-m-d H:i:s'));
            $this->assertEquals($durasi, $row->durasi_rework_menit);
        }
    }

    public function test_kanban_dan_np_kosong_ditolak(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $groupId = 'uuid-empty-fields';

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => now(),
            'waktu_in'            => now(),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-OLD',
            'nameplate_lama'      => 'NP-OLD',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);

        $session = [
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        // 1. Kanban kosong
        $resNoKanban = $this->withSession($session)->put(route('rework.update', $groupId), [
            'kanban_assy'    => '   ',
            'nameplate'      => 'NP-VALID',
            'sub_defect_ids' => [$sub1->id],
        ]);
        $resNoKanban->assertSessionHasErrors(['kanban_assy']);

        // 2. Nameplate kosong
        $resNoNp = $this->withSession($session)->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-VALID',
            'nameplate'      => '   ',
            'sub_defect_ids' => [$sub1->id],
        ]);
        $resNoNp->assertSessionHasErrors(['nameplate']);
    }
}
