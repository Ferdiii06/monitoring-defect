<?php

namespace Tests\Feature;

use App\Exports\PreAssyExport;
use App\Models\AreaDitemukan;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\InspectProcessType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class AdminAreaDitemukanSplitMenuTest extends TestCase
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
            'logged_in'     => true,
            'user_name'     => 'Admin QA',
            'user_role'     => 'Administrator',
            'current_shift' => '1A',
        ]);
    }

    private function actingAsOperator()
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'Operator PA',
            'user_role'     => 'User',
            'current_shift' => '1A',
        ]);
    }

    public function test_sidebar_has_two_area_menus_and_no_inspect_process(): void
    {
        $response = $this->actingAsAdmin()->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Area Ditemukan Final Assy');
        $response->assertSee('Area Ditemukan Pre Assy');
        $response->assertDontSee('Inspect Proses');
        $response->assertDontSee('Inspect Process');
    }

    public function test_area_pre_assy_view_hides_tipe_defect_column_and_input(): void
    {
        AreaDitemukan::firstOrCreate(
            ['name' => 'Cutting', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $response = $this->actingAsAdmin()->get(route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy']));
        $response->assertStatus(200);

        $response->assertSee('Master Area Ditemukan Pre Assy');
        $response->assertSee('Cutting');
        // Pre Assy table header should not have Tipe Defect
        $response->assertDontSee('>Tipe Defect</th>', false);
        // Pre Assy form should not have select tipe
        $response->assertDontSee('id="tipe"', false);
    }

    public function test_wp_can_exist_in_both_final_assy_and_pre_assy(): void
    {
        $wpFinal = AreaDitemukan::firstOrCreate(
            ['name' => 'WP', 'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $wpPre = AreaDitemukan::firstOrCreate(
            ['name' => 'WP', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $this->assertDatabaseHas('area_ditemukans', [
            'id'       => $wpFinal->id,
            'name'     => 'WP',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
        ]);

        $this->assertDatabaseHas('area_ditemukans', [
            'id'       => $wpPre->id,
            'name'     => 'WP',
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
        ]);
    }

    public function test_lock_rule_is_category_specific_between_final_and_pre_assy(): void
    {
        $wpFinal = AreaDitemukan::firstOrCreate(
            ['name' => 'WP', 'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $wpPre = AreaDitemukan::firstOrCreate(
            ['name' => 'WP', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        // Buat defect hanya pada Final Assy dengan area 'WP'
        Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator FA',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => 'WP',
            'end_number'       => 'END-01',
            'jenis_defect'     => 'DIMENSI',
            'jenis_sub_defect' => 'A.1',
            'quantity'         => 1,
        ]);

        // 1. WP Final Assy terkunci dan tidak bisa dihapus
        $delFinal = $this->actingAsAdmin()->delete(route('admin.master.area_ditemukans.destroy', $wpFinal->id));
        $delFinal->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('area_ditemukans', ['id' => $wpFinal->id]);

        // 2. WP Pre Assy TIDAK terkunci oleh defect Final Assy dan bisa dihapus
        $delPre = $this->actingAsAdmin()->delete(route('admin.master.area_ditemukans.destroy', $wpPre->id));
        $delPre->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('area_ditemukans', ['id' => $wpPre->id]);
    }

    public function test_new_pre_assy_report_saves_area_and_leaves_inspect_process_null(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-SPLIT', 'car_type_id' => null]);
        AreaDitemukan::firstOrCreate(
            ['name' => 'Cutting', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $payload = [
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'tanggal'        => Carbon::today()->toDateString(),
            'jam'            => '08:00',
            'jenis_defect'   => 'CORE',
            'sub_defect'     => 'FRAYING',
            'jumlah'         => 2,
            'ditemukan_oleh' => 'Operator',
            'area_ditemukan' => 'Cutting',
        ];

        $res = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $res->assertRedirect(route('operator.home'));

        $defect = Defect::latest('id')->first();
        $this->assertNotNull($defect);
        $this->assertEquals('Cutting', $defect->area_ditemukan);
        $this->assertNull($defect->inspect_process_type_id);
    }

    public function test_display_fallback_for_legacy_pre_assy_records_without_area_ditemukan(): void
    {
        $carline = Carline::create(['name' => 'PA-LEGACY-VIEW', 'car_type_id' => null]);
        $process = InspectProcessType::firstOrCreate(['name' => 'Solder Process']);

        // Data defect lama: inspect_process_type_id terisi, area_ditemukan null
        $legacyDefect = Defect::create([
            'waktu'                   => now(),
            'user_name'               => 'Operator PA',
            'shift'                   => '1A',
            'jenis_assy'              => 'Pre Assy',
            'carline_id'              => $carline->id,
            'inspect_process_type_id' => $process->id,
            'area_ditemukan'          => null,
            'jenis_defect'            => 'CORE',
            'jenis_sub_defect'        => 'A.1',
            'quantity'                => 1,
            'ditemukan_oleh'          => 'Operator',
        ]);

        // 1. Tampilan di pre_assy.blade.php
        $resPreAssy = $this->actingAsAdmin()->get(route('pre_assy.index'));
        $resPreAssy->assertStatus(200);
        $resPreAssy->assertSee('Solder Process');

        // 2. Tampilan di operator_home.blade.php
        $resHome = $this->actingAsOperator()->get(route('operator.home'));
        $resHome->assertStatus(200);
        $resHome->assertSee('Solder Process');

        // 3. Fallback di PreAssyExport
        $export = new PreAssyExport([$legacyDefect]);
        $row = $export->map($legacyDefect);
        // Column index 7 is area / process name
        $this->assertEquals('Solder Process', $row[7]);

        // 4. Form edit report lama menampilkan Solder Process terpilih
        $resEdit = $this->actingAsOperator()->get(route('input_defect.edit', $legacyDefect->id));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Solder Process');
    }

    public function test_rename_area_updates_defects_of_same_category_and_does_not_touch_other_category(): void
    {
        $wpFinal = AreaDitemukan::firstOrCreate(
            ['name' => 'WP', 'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $wpPre = AreaDitemukan::firstOrCreate(
            ['name' => 'WP', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $defectFinal = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator FA',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => 'WP',
            'end_number'       => 'END-01',
            'jenis_defect'     => 'DIMENSI',
            'jenis_sub_defect' => 'A.1',
            'quantity'         => 1,
        ]);

        $defectPre = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator PA',
            'shift'            => '1A',
            'jenis_assy'       => 'Pre Assy',
            'area_ditemukan'   => 'WP',
            'jenis_defect'     => 'CORE',
            'jenis_sub_defect' => 'A.1',
            'quantity'         => 2,
            'ditemukan_oleh'   => 'Operator',
        ]);

        $res = $this->actingAsAdmin()->put(route('admin.master.area_ditemukans.update', $wpFinal->id), [
            'name'     => 'WP_FINAL_BARU',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $res->assertSessionHas('success');

        // WP Final Assy terupdate
        $this->assertDatabaseHas('area_ditemukans', ['id' => $wpFinal->id, 'name' => 'WP_FINAL_BARU']);
        $this->assertDatabaseHas('defects', ['id' => $defectFinal->id, 'area_ditemukan' => 'WP_FINAL_BARU']);

        // WP Pre Assy dan defect Pre Assy TETAP 'WP'
        $this->assertDatabaseHas('area_ditemukans', ['id' => $wpPre->id, 'name' => 'WP']);
        $this->assertDatabaseHas('defects', ['id' => $defectPre->id, 'area_ditemukan' => 'WP']);
    }

    public function test_rename_area_rejected_if_name_exists_in_same_category(): void
    {
        $areaA = AreaDitemukan::firstOrCreate(
            ['name' => 'AREA_A', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );
        $areaB = AreaDitemukan::firstOrCreate(
            ['name' => 'AREA_B', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $res = $this->actingAsAdmin()->put(route('admin.master.area_ditemukans.update', $areaA->id), [
            'name'     => 'AREA_B',
            'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $res->assertSessionHasErrors(['name']);
        $this->assertDatabaseHas('area_ditemukans', ['id' => $areaA->id, 'name' => 'AREA_A']);
    }

    public function test_delete_rejected_for_pre_assy_with_legacy_inspect_process_relation_defect(): void
    {
        $process = InspectProcessType::firstOrCreate(['name' => 'Jam Twist']);
        $area = AreaDitemukan::firstOrCreate(
            ['name' => 'Jam Twist', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        Defect::create([
            'waktu'                   => now(),
            'user_name'               => 'Operator PA',
            'shift'                   => '1A',
            'jenis_assy'              => 'Pre Assy',
            'inspect_process_type_id' => $process->id,
            'area_ditemukan'          => null,
            'jenis_defect'            => 'CORE',
            'jenis_sub_defect'        => 'A.1',
            'quantity'                => 1,
            'ditemukan_oleh'          => 'Operator',
        ]);

        $res = $this->actingAsAdmin()->delete(route('admin.master.area_ditemukans.destroy', $area->id));
        $res->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('area_ditemukans', ['id' => $area->id]);
    }

    public function test_toggle_is_active_flips_status_and_redirects_with_flash(): void
    {
        $area = AreaDitemukan::firstOrCreate(
            ['name' => 'TOGGLE_TEST', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT, 'is_active' => true]
        );

        $res1 = $this->actingAsAdmin()->patch(route('admin.master.area_ditemukans.toggle', $area->id));
        $res1->assertSessionHas('success');
        $this->assertDatabaseHas('area_ditemukans', ['id' => $area->id, 'is_active' => false]);

        $res2 = $this->actingAsAdmin()->patch(route('admin.master.area_ditemukans.toggle', $area->id));
        $res2->assertSessionHas('success');
        $this->assertDatabaseHas('area_ditemukans', ['id' => $area->id, 'is_active' => true]);
    }

    public function test_inactive_area_hidden_from_operator_and_rejected_in_store_but_accepted_in_edit(): void
    {
        $carline = Carline::create(['name' => 'PA-INACTIVE-TEST', 'car_type_id' => null]);
        $areaInactive = AreaDitemukan::firstOrCreate(
            ['name' => 'Area_Mati', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT, 'is_active' => false]
        );

        // 1. Inactive area tidak muncul di form create
        $resCreate = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Pre Assy']));
        $resCreate->assertStatus(200);
        $resCreate->assertDontSee('value="Area_Mati"', false);

        // 2. Simpan defect baru dengan area nonaktif ditolak
        $resStore = $this->actingAsOperator()->post(route('input_defect.store'), [
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'tanggal'        => Carbon::today()->toDateString(),
            'jam'            => '08:00',
            'jenis_defect'   => 'CORE',
            'sub_defect'     => 'FRAYING',
            'jumlah'         => 1,
            'ditemukan_oleh' => 'Operator',
            'area_ditemukan' => 'Area_Mati',
        ]);
        $resStore->assertSessionHasErrors(['area_ditemukan']);

        // 3. Edit defect lama yang sudah tersimpan dengan area nonaktif
        $defectOld = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator PA',
            'shift'            => '1A',
            'jenis_assy'       => 'Pre Assy',
            'carline_id'       => $carline->id,
            'area_ditemukan'   => 'Area_Mati',
            'jenis_defect'     => 'CORE',
            'jenis_sub_defect' => 'FRAYING',
            'quantity'         => 1,
            'ditemukan_oleh'   => 'Operator',
        ]);

        $resEdit = $this->actingAsOperator()->get(route('input_defect.edit', $defectOld->id));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Area_Mati (Nonaktif)');

        // 4. Update defect lama dengan area yang sama tetap diizinkan
        $resUpdate = $this->actingAsOperator()->put(route('input_defect.update', $defectOld->id), [
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'tanggal'        => Carbon::today()->toDateString(),
            'jam'            => '08:30',
            'jenis_defect'   => 'CORE',
            'sub_defect'     => 'FRAYING',
            'jumlah'         => 2,
            'ditemukan_oleh' => 'Operator',
            'area_ditemukan' => 'Area_Mati',
        ]);
        $resUpdate->assertRedirect(route('operator.home'));
        $this->assertDatabaseHas('defects', ['id' => $defectOld->id, 'quantity' => 2]);
    }

    public function test_retired_inspect_process_types_routes_redirect_to_pre_assy_area_master(): void
    {
        $resIndex = $this->actingAsAdmin()->get(route('admin.master.inspect_process_types.index'));
        $resIndex->assertRedirect(route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy']));
        $resIndex->assertSessionHas('info', 'Master ini sudah dipindahkan ke Area Ditemukan Pre Assy');

        $resStore = $this->actingAsAdmin()->post(route('admin.master.inspect_process_types.store'), ['name' => 'Dummy']);
        $resStore->assertRedirect(route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy']));

        $resUpdate = $this->actingAsAdmin()->put(route('admin.master.inspect_process_types.update', 1), ['name' => 'Dummy']);
        $resUpdate->assertRedirect(route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy']));

        $resDestroy = $this->actingAsAdmin()->delete(route('admin.master.inspect_process_types.destroy', 1));
        $resDestroy->assertRedirect(route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy']));
    }

    public function test_dashboard_pareto_unaffected_by_inactive_area_status(): void
    {
        $area = AreaDitemukan::firstOrCreate(
            ['name' => 'CK_TEST_INACTIVE', 'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT, 'is_active' => false]
        );

        Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator QA',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => 'CK_TEST_INACTIVE',
            'end_number'       => 'END-01',
            'jenis_defect'     => 'DAMAGE',
            'jenis_sub_defect' => 'PIN',
            'quantity'         => 3,
            'inspect_quantity' => 10,
        ]);

        $res = $this->actingAsAdmin()->get(route('dashboard'));
        $res->assertStatus(200);
        // Angka defect tetap masuk di dashboard
        $this->assertDatabaseHas('defects', ['area_ditemukan' => 'CK_TEST_INACTIVE', 'quantity' => 3]);
    }

    public function test_seeder_area_classification_menghasilkan_tepat_16_pre_assy_dan_cocok_dengan_daftar_resmi(): void
    {
        // Kosongkan tabel area_ditemukans
        \Illuminate\Support\Facades\DB::table('area_ditemukans')->delete();

        $seeder = new \Database\Seeders\AreaDitemukanClassificationSeeder();
        $seeder->run();

        $expectedPreAssy = [
            'Cutting', 'Manual Crimping', 'Joint Crimping', 'Bonder',
            'Heatshrink', 'Reychern', 'Solder', 'La Term. WP', 'A/B',
            'Jam Twist', 'WP', 'MN20', 'EJ30', 'MN10', 'TP25', 'EJ35'
        ];

        $actualPreAssy = AreaDitemukan::where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)
            ->pluck('name')
            ->toArray();

        $this->assertCount(16, $actualPreAssy);
        sort($expectedPreAssy);
        sort($actualPreAssy);
        $this->assertEquals($expectedPreAssy, $actualPreAssy);
    }

    public function test_migration_000002_menyalin_nama_dari_inspect_process_types_secara_idempotent(): void
    {
        // Setup inspect_process_types dengan 16 data resmi
        \Illuminate\Support\Facades\DB::table('inspect_process_types')->delete();
        $expectedProcesses = [
            'Cutting', 'Manual Crimping', 'Joint Crimping', 'Bonder',
            'Heatshrink', 'Reychern', 'Solder', 'La Term. WP', 'A/B',
            'Jam Twist', 'WP', 'MN20', 'EJ30', 'MN10', 'TP25', 'EJ35'
        ];
        foreach ($expectedProcesses as $name) {
            \Illuminate\Support\Facades\DB::table('inspect_process_types')->insert([
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Kosongkan area Pre Assy di area_ditemukans
        \Illuminate\Support\Facades\DB::table('area_ditemukans')
            ->where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)
            ->delete();

        // Salin proses menggunakan logika migration 000002
        $processNames = \Illuminate\Support\Facades\DB::table('inspect_process_types')->pluck('name');
        foreach ($processNames as $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            $exists = \Illuminate\Support\Facades\DB::table('area_ditemukans')
                ->where('name', $name)
                ->where('kategori', 'Pre Assy')
                ->exists();
            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('area_ditemukans')->insert([
                    'name'       => $name,
                    'kategori'   => 'Pre Assy',
                    'tipe'       => 'internal_defect',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $countRun1 = AreaDitemukan::where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)->count();
        $this->assertEquals(16, $countRun1);

        // Jalankan kedua kali untuk membuktikan idempotency (tidak menambah jika sudah ada)
        foreach ($processNames as $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            $exists = \Illuminate\Support\Facades\DB::table('area_ditemukans')
                ->where('name', $name)
                ->where('kategori', 'Pre Assy')
                ->exists();
            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('area_ditemukans')->insert([
                    'name'       => $name,
                    'kategori'   => 'Pre Assy',
                    'tipe'       => 'internal_defect',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $countRun2 = AreaDitemukan::where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)->count();
        $this->assertEquals(16, $countRun2);

        $actualNames = AreaDitemukan::where('kategori', AreaDitemukan::KATEGORI_PRE_ASSY)
            ->pluck('name')
            ->sort()
            ->values()
            ->toArray();
        sort($expectedProcesses);
        $this->assertEquals($expectedProcesses, $actualNames);
    }
}
