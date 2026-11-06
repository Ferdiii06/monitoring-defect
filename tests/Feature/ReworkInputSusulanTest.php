<?php

namespace Tests\Feature;

use App\Exports\ReworkHistoryExport;
use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReworkInputSusulanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AreaDitemukan::firstOrCreate([
            'name' => 'RWK',
            'tipe' => 'rework',
        ]);
    }

    private function createFinalAssyDefect(string $typeName = 'WIRING', string $subName = '1.A - CROSS CIRCUIT', string $code = '1A'): SubDefectType
    {
        $defectType = DefectType::firstOrCreate(
            ['name' => $typeName, 'type' => 'Final Assy']
        );

        return SubDefectType::firstOrCreate(
            ['defect_type_id' => $defectType->id, 'name' => $subName],
            ['code' => $code]
        );
    }

    private function reworkSession(array $overrides = []): array
    {
        return array_merge([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ], $overrides);
    }

    private function adminSession(): array
    {
        return [
            'logged_in'     => true,
            'user_name'     => 'admin_user',
            'user_role'     => 'Administrator',
            'current_shift' => '1A',
        ];
    }

    public function test_simpan_susulan_sukses_dengan_status_close_dan_durasi_terhitung(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        $response = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-SUSULAN-001',
            'nameplate'      => 'NP-OLD-001',
            'nameplate_baru' => 'np-new-999',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-05',
            'jam_in'         => '08:30',
            'jam_out'        => '09:15',
            'shift'          => '1B',
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success');
        $successMsg = session('success');
        $this->assertStringContainsString('Rework susulan berhasil disimpan (CLOSE)', $successMsg);
        $this->assertStringContainsString('05/11/2026 08:30', $successMsg);
        $this->assertStringContainsString('05/11/2026 09:15', $successMsg);
        $this->assertStringContainsString('45 menit', $successMsg);

        $defect = Defect::where('kanban_assy', 'KB-SUSULAN-001')->first();
        $this->assertNotNull($defect);
        $this->assertTrue($defect->is_susulan);
        $this->assertEquals('CLOSE', $defect->status_rework);
        $this->assertEquals('2026-11-05 08:30:00', $defect->waktu->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-05 08:30:00', $defect->waktu_in->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-05 09:15:00', $defect->waktu_out->format('Y-m-d H:i:s'));
        $this->assertEquals(45, $defect->durasi_rework_menit);
        $this->assertEquals('1B', $defect->shift);
        $this->assertEquals('NP-NEW-999', $defect->nameplate_baru);
        $this->assertNotEmpty($defect->rework_group_id);

        Carbon::setTestNow();
    }

    public function test_susulan_lintas_tengah_malam_tambah_satu_hari_dan_durasi_benar(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        // In 23:45 tanggal 4 Nov, Out 00:30 (hari berikutnya 5 Nov) -> durasi 45 menit
        $response = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-MIDNIGHT-001',
            'nameplate'      => 'NP-MIDNIGHT',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-04',
            'jam_in'         => '23:45',
            'jam_out'        => '00:30',
            'shift'          => '2A',
        ]);

        $response->assertRedirect(route('rework.index'));
        $defect = Defect::where('kanban_assy', 'KB-MIDNIGHT-001')->first();
        $this->assertNotNull($defect);
        $this->assertTrue($defect->is_susulan);
        $this->assertEquals('2026-11-04 23:45:00', $defect->waktu_in->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-05 00:30:00', $defect->waktu_out->format('Y-m-d H:i:s'));
        $this->assertEquals(45, $defect->durasi_rework_menit);
        $this->assertEquals('CLOSE', $defect->status_rework);

        Carbon::setTestNow();
    }

    public function test_susulan_menolak_waktu_in_di_masa_depan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        $response = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-FUTURE-001',
            'nameplate'      => 'NP-FUTURE',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-06',
            'jam_in'         => '15:30',
            'jam_out'        => '16:00',
            'shift'          => '1A',
        ]);

        $response->assertSessionHasErrors(['jam_in']);
        $this->assertEquals(0, Defect::count());

        Carbon::setTestNow();
    }

    public function test_susulan_menolak_waktu_out_di_masa_depan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        $response = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-FUTURE-OUT',
            'nameplate'      => 'NP-FUTURE',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-06',
            'jam_in'         => '13:00',
            'jam_out'        => '15:00',
            'shift'          => '1A',
        ]);

        $response->assertSessionHasErrors(['jam_out']);
        $this->assertEquals(0, Defect::count());

        Carbon::setTestNow();
    }

    public function test_susulan_menolak_waktu_out_sama_dengan_in(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        $response = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-EQUAL-001',
            'nameplate'      => 'NP-EQUAL',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-05',
            'jam_in'         => '10:00',
            'jam_out'        => '10:00',
            'shift'          => '1A',
        ]);

        $response->assertSessionHasErrors(['jam_out']);
        $this->assertEquals(0, Defect::count());

        Carbon::setTestNow();
    }

    public function test_susulan_menolak_waktu_in_lebih_dari_7_hari_ke_belakang(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        // 8 hari ke belakang dari 6 Nov adalah 29 Okt
        $response = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-OLD-001',
            'nameplate'      => 'NP-OLD',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-10-28',
            'jam_in'         => '08:00',
            'jam_out'        => '09:00',
            'shift'          => '1A',
        ]);

        $response->assertSessionHasErrors(['tanggal']);
        $this->assertEquals(0, Defect::count());

        Carbon::setTestNow();
    }

    public function test_susulan_menolak_durasi_lebih_dari_24_jam(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        $response = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-LONG-001',
            'nameplate'      => 'NP-LONG',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-03',
            'tanggal_out'    => '2026-11-05',
            'jam_in'         => '08:00',
            'jam_out'        => '09:00',
            'shift'          => '1A',
        ]);

        $response->assertSessionHasErrors(['jam_out']);
        $this->assertEquals(0, Defect::count());

        Carbon::setTestNow();
    }

    public function test_susulan_wajib_memilih_shift_valid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        // Shift kosong
        $responseNoShift = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-NO-SHIFT',
            'nameplate'      => 'NP-001',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-05',
            'jam_in'         => '08:00',
            'jam_out'        => '09:00',
            'shift'          => '',
        ]);

        $responseNoShift->assertSessionHasErrors(['shift']);

        // Shift invalid
        $responseInvalidShift = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-INVALID-SHIFT',
            'nameplate'      => 'NP-001',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-05',
            'jam_in'         => '08:00',
            'jam_out'        => '09:00',
            'shift'          => '3A',
        ]);

        $responseInvalidShift->assertSessionHasErrors(['shift']);
        $this->assertEquals(0, Defect::count());

        Carbon::setTestNow();
    }

    public function test_mode_live_tidak_berubah_tetap_open_dan_is_susulan_false(): void
    {
        $sub = $this->createFinalAssyDefect();

        $response = $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'live',
            'kanban_assy'    => 'KB-LIVE-001',
            'nameplate'      => 'NP-LIVE-001',
            'sub_defect_ids' => [$sub->id],
        ]);

        $response->assertRedirect(route('rework.index'));
        $response->assertSessionHas('success', 'Rework harness berhasil dimulai (In).');

        $defect = Defect::where('kanban_assy', 'KB-LIVE-001')->first();
        $this->assertNotNull($defect);
        $this->assertFalse($defect->is_susulan);
        $this->assertEquals('OPEN', $defect->status_rework);
        $this->assertNull($defect->waktu_out);
        $this->assertNull($defect->durasi_rework_menit);
        $this->assertNull($defect->nameplate_baru);
        $this->assertEquals('1A', $defect->shift); // dari session current_shift
    }

    public function test_badge_susulan_tampil_di_riwayat_operator_dan_admin(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        // 1. Simpan susulan
        $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-VIEW-SUSULAN',
            'nameplate'      => 'NP-VIEW-SUSULAN',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-05',
            'jam_in'         => '09:00',
            'jam_out'        => '09:30',
            'shift'          => '1A',
        ]);

        // Cek halaman riwayat operator
        $operatorView = $this->withSession($this->reworkSession())->get(route('rework.index'));
        $operatorView->assertStatus(200);
        $operatorView->assertSee('KB-VIEW-SUSULAN');
        $operatorView->assertSee('Susulan');

        // Cek halaman riwayat admin
        $adminView = $this->withSession($this->adminSession())->get(route('admin.rework_history.index'));
        $adminView->assertStatus(200);
        $adminView->assertSee('KB-VIEW-SUSULAN');
        $adminView->assertSee('Susulan');

        Carbon::setTestNow();
    }

    public function test_rework_history_export_memuat_kolom_input_susulan_ya_dan_tidak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        // Susulan
        $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-EXP-SUSULAN',
            'nameplate'      => 'NP-EXP-SUSULAN',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-05',
            'jam_in'         => '09:00',
            'jam_out'        => '09:30',
            'shift'          => '1A',
        ]);

        // Live
        $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'live',
            'kanban_assy'    => 'KB-EXP-LIVE',
            'nameplate'      => 'NP-EXP-LIVE',
            'sub_defect_ids' => [$sub->id],
        ]);

        $export = new ReworkHistoryExport(new Request());
        $headings = $export->headings();
        $this->assertContains('Input Susulan', $headings);

        $rows = $export->query()->get();
        $this->assertCount(2, $rows);

        $susulanRow = $rows->firstWhere('kanban_assy', 'KB-EXP-SUSULAN');
        $this->assertNotNull($susulanRow);
        $mappedSusulan = $export->map($susulanRow);
        $this->assertContains('Ya', $mappedSusulan);

        $liveRow = $rows->firstWhere('kanban_assy', 'KB-EXP-LIVE');
        $this->assertNotNull($liveRow);
        $mappedLive = $export->map($liveRow);
        $this->assertContains('Tidak', $mappedLive);

        Carbon::setTestNow();
    }

    public function test_baris_susulan_masuk_hari_kejadian_di_dashboard_dan_kpi(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        // Input susulan untuk tanggal kemarin: 2026-11-05
        $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-DASHBOARD-001',
            'nameplate'      => 'NP-DASHBOARD',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-05',
            'jam_in'         => '10:00',
            'jam_out'        => '10:45',
            'shift'          => '1A',
        ]);

        $defect = Defect::where('kanban_assy', 'KB-DASHBOARD-001')->first();
        $this->assertNotNull($defect);
        // Pastikan DATE(waktu) adalah 2026-11-05 (hari kejadian), BUKAN 2026-11-06 (hari input)
        $this->assertEquals('2026-11-05', $defect->waktu->format('Y-m-d'));

        // Akses dashboard mode rework
        $dashboardResponse = $this->withSession($this->adminSession())->get(route('dashboard', [
            'mode'       => 'rework',
            'date_range' => '2026-11-05 to 2026-11-05',
        ]));
        $dashboardResponse->assertStatus(200);

        // Akses dashboard tanggal hari ini (2026-11-06) - defect kemarin tidak boleh masuk hitungan hari ini
        $dashboardToday = $this->withSession($this->adminSession())->get(route('dashboard', [
            'mode'       => 'rework',
            'date_range' => '2026-11-06 to 2026-11-06',
        ]));
        $dashboardToday->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_admin_dapat_menghapus_group_susulan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 14:00:00'));

        $sub = $this->createFinalAssyDefect();

        $this->withSession($this->reworkSession())->post(route('rework.store_in'), [
            'mode_input'     => 'susulan',
            'kanban_assy'    => 'KB-DEL-SUSULAN',
            'nameplate'      => 'NP-DEL-SUSULAN',
            'sub_defect_ids' => [$sub->id],
            'tanggal'        => '2026-11-05',
            'jam_in'         => '08:00',
            'jam_out'        => '08:30',
            'shift'          => '1A',
        ]);

        $defect = Defect::where('kanban_assy', 'KB-DEL-SUSULAN')->first();
        $this->assertNotNull($defect);
        $groupId = $defect->rework_group_id;

        $response = $this->withSession($this->adminSession())->delete(route('admin.rework_history.destroy', $groupId));
        $response->assertRedirect();

        $this->assertEquals(0, Defect::where('rework_group_id', $groupId)->count());

        Carbon::setTestNow();
    }
}
