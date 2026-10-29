<?php

namespace Tests\Feature;

use App\Models\AreaDitemukan;
use App\Models\CarType;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReworkFormTest extends TestCase
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

    public function test_akun_rework_bisa_membuat_in_dengan_dua_defect_dan_rework_group_id_sama(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $sub2 = $this->createFinalAssyDefect('WIRING', '1.B - SHORT CIRCUIT', '1B');

        AreaDitemukan::create([
            'name' => 'RWK',
            'tipe' => 'rework',
        ]);

        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->post(route('rework.store_in'), [
            'kanban_assy'    => '  KB-12345  ',
            'nameplate'      => '  np-abcdef  ',
            'sub_defect_ids' => [$sub1->id, $sub2->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(2, Defect::count());

        $defects = Defect::orderBy('id')->get();
        $first = $defects[0];
        $second = $defects[1];

        // Validasi rework_group_id UUID (panjang 36 karakter) dan sama persis
        $this->assertNotEmpty($first->rework_group_id);
        $this->assertEquals(36, strlen($first->rework_group_id));
        $this->assertEquals($first->rework_group_id, $second->rework_group_id);

        // Validasi field-field kedua baris
        foreach ($defects as $defect) {
            $this->assertEquals('KB-12345', $defect->kanban_assy);
            $this->assertEquals('NP-ABCDEF', $defect->nameplate_lama);
            $this->assertNull($defect->nameplate_baru);
            $this->assertEquals('OPEN', $defect->status_rework);
            $this->assertNotNull($defect->waktu_in);
            $this->assertNull($defect->waktu_out);
            $this->assertNull($defect->durasi_rework_menit);
            $this->assertEquals('reworkA', $defect->user_name);
            $this->assertEquals('1A', $defect->shift);
            $this->assertEquals('Final Assy', $defect->jenis_assy);
            $this->assertEquals(1, $defect->quantity);
            $this->assertEquals('RWK', $defect->area_ditemukan);
            $this->assertNull($defect->conveyor);
            $this->assertNull($defect->carline_id);
        }

        $this->assertEquals($sub1->id, $first->sub_defect_type_id);
        $this->assertEquals('1.A - CROSS CIRCUIT', $first->jenis_sub_defect);
        $this->assertEquals('WIRING', $first->jenis_defect);

        $this->assertEquals($sub2->id, $second->sub_defect_type_id);
        $this->assertEquals('1.B - SHORT CIRCUIT', $second->jenis_sub_defect);
        $this->assertEquals('WIRING', $second->jenis_defect);
    }

    public function test_in_tanpa_defect_tanpa_kanban_atau_tanpa_np_ditolak(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');

        $session = [
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        // 1. Tanpa Kanban
        $resNoKanban = $this->withSession($session)->post(route('rework.store_in'), [
            'kanban_assy'    => '   ',
            'nameplate'      => 'NP-123',
            'sub_defect_ids' => [$sub1->id],
        ]);
        $resNoKanban->assertSessionHasErrors(['kanban_assy']);

        // 2. Tanpa Nameplate
        $resNoNp = $this->withSession($session)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-123',
            'nameplate'      => '   ',
            'sub_defect_ids' => [$sub1->id],
        ]);
        $resNoNp->assertSessionHasErrors(['nameplate']);

        // 3. Tanpa Defect (array kosong)
        $resNoDefect = $this->withSession($session)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-123',
            'nameplate'      => 'NP-123',
            'sub_defect_ids' => [],
        ]);
        $resNoDefect->assertSessionHasErrors(['sub_defect_ids']);

        // 4. Defect bukan dari Final Assy (misal Pre Assy)
        $preAssyType = DefectType::create(['name' => 'CRIMPING', 'type' => 'Pre Assy']);
        $preSub = SubDefectType::create(['defect_type_id' => $preAssyType->id, 'name' => 'BENT TERMINAL']);

        $resPreAssy = $this->withSession($session)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-123',
            'nameplate'      => 'NP-123',
            'sub_defect_ids' => [$preSub->id],
        ]);
        $resPreAssy->assertSessionHasErrors(['sub_defect_ids']);

        $this->assertEquals(0, Defect::count());
    }

    public function test_out_hanya_bisa_oleh_pemilik_group_dan_tidak_bisa_dua_kali(): void
    {
        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $groupId = 'group-uuid-test-123';
        $waktuIn = now()->subMinutes(18);

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-TEST',
            'nameplate_lama'      => 'NP-TEST',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub->defect_type_id,
            'sub_defect_type_id'  => $sub->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub->name,
        ]);

        // 1. Akun rework lain (reworkB) mencoba melakukan Out -> DITOLAK 403
        $resOtherUser = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkB',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->post(route('rework.store_out', $groupId));

        $resOtherUser->assertStatus(403);
        $this->assertEquals('Berjalan', Defect::where('rework_group_id', $groupId)->value('status_rework'));
        $this->assertNull(Defect::where('rework_group_id', $groupId)->value('waktu_out'));

        // 2. Pemilik asli (reworkA) melakukan Out -> BERHASIL
        $resOwner = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->post(route('rework.store_out', $groupId));

        $resOwner->assertRedirect(route('rework.index'));
        $resOwner->assertSessionHas('success');

        $updatedDefect = Defect::where('rework_group_id', $groupId)->first();
        $this->assertEquals('CLOSE', $updatedDefect->status_rework);
        $this->assertNotNull($updatedDefect->waktu_out);
        $this->assertEquals(18, $updatedDefect->durasi_rework_menit);

        $firstWaktuOut = $updatedDefect->waktu_out;

        // 3. Pemilik mencoba melakukan Out KEDUA KALI -> DITOLAK / TIDAK BISA DI-OUT ULANG
        $resSecondOut = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->post(route('rework.store_out', $groupId));

        $resSecondOut->assertRedirect(route('rework.index'));
        $resSecondOut->assertSessionHas('error');

        // Pastikan waktu_out tidak berubah
        $this->assertEquals($firstWaktuOut, Defect::where('rework_group_id', $groupId)->value('waktu_out'));
    }

    public function test_riwayat_hanya_menampilkan_group_milik_akun_sendiri_dan_terkelompok_benar(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $sub2 = $this->createFinalAssyDefect('WIRING', '1.B - SHORT CIRCUIT', '1B');

        // Group A milik reworkA: 2 defect
        $groupA = 'uuid-group-a-reworkA';
        Defect::create([
            'rework_group_id'     => $groupA,
            'waktu'               => now()->subMinutes(10),
            'waktu_in'            => now()->subMinutes(10),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-OWN-1',
            'nameplate_lama'      => 'NP-OWN-1',
            'user_name'           => 'reworkA',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
        ]);
        Defect::create([
            'rework_group_id'     => $groupA,
            'waktu'               => now()->subMinutes(10),
            'waktu_in'            => now()->subMinutes(10),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-OWN-1',
            'nameplate_lama'      => 'NP-OWN-1',
            'user_name'           => 'reworkA',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub2->defect_type_id,
            'sub_defect_type_id'  => $sub2->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub2->name,
        ]);

        // Group B milik reworkB (akun lain)
        $groupB = 'uuid-group-b-reworkB';
        Defect::create([
            'rework_group_id'     => $groupB,
            'waktu'               => now()->subMinutes(5),
            'waktu_in'            => now()->subMinutes(5),
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-OTHER-USER',
            'nameplate_lama'      => 'NP-OTHER-USER',
            'user_name'           => 'reworkB',
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
        ])->get(route('rework.index'));

        $response->assertStatus(200);

        // Group milik reworkA tampil
        $response->assertSee('KB-OWN-1');
        $response->assertSee('NP-OWN-1');
        $response->assertSee('uuid-group-a-reworkA');
        $response->assertSee('1.A - CROSS CIRCUIT');
        $response->assertSee('1.B - SHORT CIRCUIT');

        // Group milik reworkB TIDAK tampil
        $response->assertDontSee('KB-OTHER-USER');
        $response->assertDontSee('NP-OTHER-USER');
        $response->assertDontSee('uuid-group-b-reworkB');
    }

    public function test_role_user_dan_administrator_tidak_bisa_post_in_dan_out(): void
    {
        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');

        // 1. Guest (belum login)
        $resGuestIn = $this->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-GUEST',
            'nameplate'      => 'NP-GUEST',
            'sub_defect_ids' => [$sub->id],
        ]);
        $resGuestIn->assertRedirect(route('login'));

        $resGuestOut = $this->post(route('rework.store_out', 'some-uuid'));
        $resGuestOut->assertRedirect(route('login'));

        // 2. Role User (Operator)
        $userSession = [
            'logged_in'     => true,
            'user_name'     => 'Operator QA',
            'user_role'     => 'User',
            'current_shift' => '1A',
        ];

        $resUserIn = $this->withSession($userSession)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-USER',
            'nameplate'      => 'NP-USER',
            'sub_defect_ids' => [$sub->id],
        ]);
        $resUserIn->assertRedirect(route('operator.home'));

        $resUserOut = $this->withSession($userSession)->post(route('rework.store_out', 'some-uuid'));
        $resUserOut->assertRedirect(route('operator.home'));

        // 3. Role Administrator
        $adminSession = [
            'logged_in'     => true,
            'user_name'     => 'Admin QA',
            'user_role'     => 'Administrator',
            'current_shift' => '1A',
        ];

        $resAdminIn = $this->withSession($adminSession)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-ADMIN',
            'nameplate'      => 'NP-ADMIN',
            'sub_defect_ids' => [$sub->id],
        ]);
        $resAdminIn->assertRedirect(route('dashboard'));

        $resAdminOut = $this->withSession($adminSession)->post(route('rework.store_out', 'some-uuid'));
        $resAdminOut->assertRedirect(route('dashboard'));

        $this->assertEquals(0, Defect::count());
    }

    public function test_gagal_bila_tidak_ada_area_rework_dan_tidak_ada_baris_tersimpan(): void
    {
        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');

        // Pastikan tidak ada master area bertipe rework
        AreaDitemukan::where('tipe', AreaDitemukan::TIPE_REWORK)->delete();

        $session = [
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        $response = $this->withSession($session)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-NO-AREA',
            'nameplate'      => 'NP-NO-AREA',
            'sub_defect_ids' => [$sub->id],
        ]);

        // Harus redirect back dan memiliki session error pada area_ditemukan
        $response->assertRedirect();
        $response->assertSessionHasErrors(['area_ditemukan']);

        $expectedMessage = 'Area bertipe Rework belum diatur. Hubungi admin untuk menambahkannya di Master Data > Area Ditemukan.';
        $this->assertEquals($expectedMessage, session('errors')->first('area_ditemukan'));

        // Input lama harus dipertahankan (withInput)
        $this->assertEquals('KB-NO-AREA', session('_old_input.kanban_assy'));
        $this->assertEquals('NP-NO-AREA', session('_old_input.nameplate'));
        $this->assertEquals([$sub->id], session('_old_input.sub_defect_ids'));

        // Tidak boleh ada data defect yang tersimpan
        $this->assertEquals(0, Defect::count());

        // Pastikan pesan error tampil di blade /rework
        $indexResponse = $this->withSession($session)->get(route('rework.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($expectedMessage);
    }

    public function test_sukses_dan_area_ditemukan_terisi_nama_area_rework_bila_ada(): void
    {
        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');

        AreaDitemukan::create([
            'name' => 'AREA_REWORK_MAIN',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $session = [
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        $response = $this->withSession($session)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-SUCCESS',
            'nameplate'      => 'NP-SUCCESS',
            'sub_defect_ids' => [$sub->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(1, Defect::count());
        $defect = Defect::first();
        $this->assertNotNull($defect->area_ditemukan);
        $this->assertEquals('AREA_REWORK_MAIN', $defect->area_ditemukan);
    }

    public function test_bila_ada_dua_area_rework_yang_terpilih_konsisten_id_terkecil(): void
    {
        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');

        $area1 = AreaDitemukan::create([
            'name' => 'AREA_REWORK_ID_KECIL',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $area2 = AreaDitemukan::create([
            'name' => 'AREA_REWORK_ID_BESAR',
            'tipe' => AreaDitemukan::TIPE_REWORK,
        ]);

        $this->assertTrue($area1->id < $area2->id);

        $session = [
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        $response = $this->withSession($session)->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-CONSISTENT',
            'nameplate'      => 'NP-CONSISTENT',
            'sub_defect_ids' => [$sub->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $this->assertEquals(1, Defect::count());

        $defect = Defect::first();
        // Harus memilih $area1 karena memiliki id terkecil
        $this->assertEquals('AREA_REWORK_ID_KECIL', $defect->area_ditemukan);
    }

    public function test_edit_tidak_membuat_area_ditemukan_null(): void
    {
        $sub1 = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        $sub2 = $this->createFinalAssyDefect('WIRING', '1.B - SHORT CIRCUIT', '1B');

        $groupId = 'uuid-test-edit-area-preserve';
        $waktuIn = now()->subMinutes(15);

        Defect::create([
            'rework_group_id'     => $groupId,
            'waktu'               => $waktuIn,
            'waktu_in'            => $waktuIn,
            'status_rework'       => 'Berjalan',
            'kanban_assy'         => 'KB-PRE-EDIT',
            'nameplate_lama'      => 'NP-PRE-EDIT',
            'user_name'           => 'reworkA',
            'shift'               => '1A',
            'jenis_assy'          => 'Final Assy',
            'quantity'            => 1,
            'defect_type_id'      => $sub1->defect_type_id,
            'sub_defect_type_id'  => $sub1->id,
            'jenis_defect'        => 'WIRING',
            'jenis_sub_defect'    => $sub1->name,
            'area_ditemukan'      => 'AREA_REWORK_ORIGINAL',
        ]);

        $session = [
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        // Edit: ubah kanban, nameplate, dan tambahkan defect kedua ($sub2)
        $response = $this->withSession($session)->put(route('rework.update', $groupId), [
            'kanban_assy'    => 'KB-POST-EDIT',
            'nameplate'      => 'NP-POST-EDIT',
            'sub_defect_ids' => [$sub1->id, $sub2->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');

        $defects = Defect::where('rework_group_id', $groupId)->orderBy('id')->get();
        $this->assertCount(2, $defects);

        foreach ($defects as $d) {
            $this->assertNotNull($d->area_ditemukan);
            $this->assertEquals('AREA_REWORK_ORIGINAL', $d->area_ditemukan);
            $this->assertEquals('KB-POST-EDIT', $d->kanban_assy);
            $this->assertEquals('NP-POST-EDIT', $d->nameplate_lama);
        }
    }

    public function test_rework_in_dan_update_dengan_seluruh_field_baru_tersimpan_di_database(): void
    {
        $area = AreaDitemukan::create([
            'name' => 'OFFLINE_AREA',
            'tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');

        $session = [
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ];

        // 1. Submit store_in with all legacy fields
        $responseIn = $this->withSession($session)->post(route('rework.store_in'), [
            'kanban_assy'          => 'KB-FULL-001',
            'nameplate'            => 'NP-OLD-001',
            'sub_defect_ids'       => [$sub->id],
            'no_defective_tag'     => 'TAG-9999',
            'ujung'                => 'END-1',
            'status_4m'            => 'YES',
            'area_ditemukan'       => 'OFFLINE_AREA',
            'posisi_detail_rework' => 'Konektor A pin 3',
            'fc_h'                 => '1.25',
            'fc_w'                 => '2.10',
            'rc_h'                 => '1.30',
            'rc_w'                 => '2.20',
            'no_box'               => 'BOX-517',
            'shift'                => '2B',
        ]);

        $responseIn->assertRedirect(route('rework.index'));
        $defect = Defect::where('kanban_assy', 'KB-FULL-001')->first();
        $this->assertNotNull($defect);
        $this->assertEquals('TAG-9999', $defect->no_defective_tag);
        $this->assertEquals('END-1', $defect->ujung);
        $this->assertEquals('YES', $defect->status_4m);
        $this->assertEquals('OFFLINE_AREA', $defect->area_ditemukan);
        $this->assertEquals('Konektor A pin 3', $defect->posisi_detail_rework);
        $this->assertEquals('1.25', $defect->fc_h);
        $this->assertEquals('2.10', $defect->fc_w);
        $this->assertEquals('1.30', $defect->rc_h);
        $this->assertEquals('2.20', $defect->rc_w);
        $this->assertEquals('BOX-517', $defect->no_box);
        $this->assertEquals('2B', $defect->shift);

        // 2. Submit update with NP Baru
        $groupId = $defect->rework_group_id;
        $responsePut = $this->withSession($session)->put(route('rework.update', $groupId), [
            'kanban_assy'          => 'KB-FULL-001-UPDATED',
            'nameplate'            => 'NP-OLD-001',
            'nameplate_baru'       => 'NP-NEW-002',
            'sub_defect_ids'       => [$sub->id],
            'no_defective_tag'     => 'TAG-8888',
            'ujung'                => 'END-2',
            'status_4m'            => 'NO',
            'area_ditemukan'       => 'OFFLINE_AREA',
            'posisi_detail_rework' => 'Konektor B pin 4',
            'no_box'               => 'BOX-3638',
            'shift'                => '3A',
        ]);

        $responsePut->assertRedirect(route('rework.index'));
        $updated = Defect::where('rework_group_id', $groupId)->first();
        $this->assertEquals('KB-FULL-001-UPDATED', $updated->kanban_assy);
        $this->assertEquals('NP-NEW-002', $updated->nameplate_baru);
        $this->assertEquals('TAG-8888', $updated->no_defective_tag);
        $this->assertEquals('END-2', $updated->ujung);
        $this->assertEquals('NO', $updated->status_4m);
        $this->assertEquals('Konektor B pin 4', $updated->posisi_detail_rework);
        $this->assertEquals('BOX-3638', $updated->no_box);
        $this->assertEquals('3A', $updated->shift);
    }

    public function test_simpan_in_dengan_conveyor_dan_carline_tersimpan_di_database(): void
    {
        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);

        $carType = CarType::create(['name' => 'TOYOTA']);
        $carline = Carline::create([
            'car_type_id' => $carType->id,
            'name'        => '664W-C5',
        ]);

        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-CONV-01',
            'nameplate'      => 'NP-CONV-01',
            'sub_defect_ids' => [$sub->id],
            'carline'        => 'TOYOTA',
            'conveyor'       => '664W-C5',
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');

        $defect = Defect::where('kanban_assy', 'KB-CONV-01')->first();
        $this->assertNotNull($defect);
        $this->assertEquals('664W-C5', $defect->conveyor);
        $this->assertEquals($carline->id, $defect->carline_id);
        $this->assertEquals('TOYOTA', $defect->jenis_mobil);
        $this->assertEquals('OPEN', $defect->status_rework);
    }

    public function test_validasi_kombinasi_conveyor_dan_carline_tidak_valid(): void
    {
        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);

        $toyota = CarType::create(['name' => 'TOYOTA']);
        $nissan = CarType::create(['name' => 'NISSAN']);
        Carline::create([
            'car_type_id' => $toyota->id,
            'name'        => '664W-C5',
        ]);

        // Kirim kombinasi tidak valid: conveyor 664W-C5 (milik TOYOTA) tapi carline NISSAN
        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->from(route('rework.index'))->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-INVALID-01',
            'nameplate'      => 'NP-INVALID-01',
            'sub_defect_ids' => [$sub->id],
            'carline'        => 'NISSAN',
            'conveyor'       => '664W-C5',
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHasErrors(['conveyor']);
        $this->assertEquals(0, Defect::where('kanban_assy', 'KB-INVALID-01')->count());
    }

    public function test_simpan_in_dengan_conveyor_saja_mengisi_carline_id_dan_jenis_mobil_secara_otomatis(): void
    {
        $sub = $this->createFinalAssyDefect('WIRING', '1.A - CROSS CIRCUIT', '1A');
        AreaDitemukan::create(['name' => 'RWK', 'tipe' => 'rework']);

        $mazda = CarType::create(['name' => 'MAZDA']);
        $carline = Carline::create([
            'car_type_id' => $mazda->id,
            'name'        => 'J72A-12B.LHD',
        ]);

        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->post(route('rework.store_in'), [
            'kanban_assy'    => 'KB-AUTO-01',
            'nameplate'      => 'NP-AUTO-01',
            'sub_defect_ids' => [$sub->id],
            'conveyor'       => 'J72A-12B.LHD',
        ]);

        $response->assertRedirect(route('rework.index'));
        $defect = Defect::where('kanban_assy', 'KB-AUTO-01')->first();
        $this->assertNotNull($defect);
        $this->assertEquals('J72A-12B.LHD', $defect->conveyor);
        $this->assertEquals($carline->id, $defect->carline_id);
        $this->assertEquals('MAZDA', $defect->jenis_mobil);
    }
}
