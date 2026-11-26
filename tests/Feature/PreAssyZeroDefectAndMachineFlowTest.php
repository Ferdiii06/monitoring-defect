<?php

namespace Tests\Feature;

use App\Models\Carline;
use App\Models\Defect;
use App\Models\InspectProcessType;
use App\Models\PreAssyMachine;
use App\Models\AreaDitemukan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class PreAssyZeroDefectAndMachineFlowTest extends TestCase
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

    private function actingAsOperator()
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'Operator PA',
            'user_role'     => 'User',
            'current_shift' => '1A',
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

    public function test_pre_assy_with_defect_saves_area_ditemukan_from_inspect_process_and_no_mesin(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-01', 'car_type_id' => null]);
        PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MSN-PA-101', 'is_active' => true]);
        AreaDitemukan::firstOrCreate(
            ['name' => 'Cutting Process', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $payload = [
            'type'                    => 'Pre Assy',
            'carline_id'              => $carline->id,
            'tanggal'                 => Carbon::today()->toDateString(),
            'jam'                     => '08:30',
            'jenis_defect'            => 'CORE',
            'sub_defect'              => 'A.1 - FRAYING',
            'jumlah'                  => 3,
            'inspect_quantity'        => 50,
            'ditemukan_oleh'          => 'Operator',
            'area_ditemukan'          => 'Cutting Process',
            'no_mesin'                => 'MSN-PA-101',
            'no_terminal'             => 'TRM-01',
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('success');

        $saved = Defect::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Pre Assy', $saved->jenis_assy);
        $this->assertEquals($carline->id, $saved->carline_id);
        $this->assertNull($saved->inspect_process_type_id);
        $this->assertEquals('Cutting Process', $saved->area_ditemukan);
        $this->assertEquals('MSN-PA-101', $saved->no_mesin);
        $this->assertEquals('Operator', $saved->ditemukan_oleh);
        $this->assertEquals(3, $saved->quantity);
    }

    public function test_pre_assy_with_carline_without_active_machines_allows_null_no_mesin(): void
    {
        $carline = Carline::create(['name' => 'PA-EMPTY-LINE', 'car_type_id' => null]);
        // Tanpa mesin aktif
        $process = InspectProcessType::firstOrCreate(['name' => 'Crimping Process']);

        $payload = [
            'type'                    => 'Pre Assy',
            'carline_id'              => $carline->id,
            'tanggal'                 => Carbon::today()->toDateString(),
            'jam'                     => '09:00',
            'jenis_defect'            => 'TERMINAL',
            'sub_defect'              => 'B.1 - TERGORES',
            'jumlah'                  => 2,
            'ditemukan_oleh'          => 'Inspektor',
            'inspect_process_type_id' => $process->id,
            'no_mesin'                => '', // Kosong karena carline belum punya mesin
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('success');

        $saved = Defect::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertNull($saved->no_mesin);
        $this->assertEquals('Crimping Process', $saved->area_ditemukan);
    }

    public function test_pre_assy_with_carline_having_active_machines_requires_no_mesin(): void
    {
        $carline = Carline::create(['name' => 'PA-ACTIVE-LINE', 'car_type_id' => null]);
        PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MSN-ACT-1', 'is_active' => true]);
        $process = InspectProcessType::firstOrCreate(['name' => 'Cutting Process']);

        $payload = [
            'type'                    => 'Pre Assy',
            'carline_id'              => $carline->id,
            'tanggal'                 => Carbon::today()->toDateString(),
            'jam'                     => '09:00',
            'jenis_defect'            => 'CORE',
            'sub_defect'              => 'A.2 - CUT CORE',
            'jumlah'                  => 1,
            'ditemukan_oleh'          => 'Operator',
            'inspect_process_type_id' => $process->id,
            'no_mesin'                => '', // Kosong padahal carline punya mesin aktif
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertSessionHasErrors(['no_mesin' => 'Nomor mesin wajib dipilih.']);
        $this->assertEquals(0, Defect::count());
    }

    public function test_pre_assy_zero_defect_forces_four_fields_to_null(): void
    {
        $carline = Carline::create(['name' => 'PA-ZERO-LINE', 'car_type_id' => null]);
        PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MSN-Z1', 'is_active' => true]);
        $process = InspectProcessType::firstOrCreate(['name' => 'Process Ignored']);

        $payload = [
            'type'                    => 'Pre Assy',
            'carline_id'              => $carline->id,
            'tanggal'                 => Carbon::today()->toDateString(),
            'jam'                     => '10:00',
            'jenis_defect'            => 'Zero Defect',
            'sub_defect'              => '-',
            'jumlah'                  => 0,
            'inspect_quantity'        => 100,
            // Coba kirim field yang seharusnya tidak ada saat Zero Defect
            'inspect_process_type_id' => $process->id,
            'no_mesin'                => 'MSN-Z1',
            'ditemukan_oleh'          => 'Operator',
            'area_ditemukan'          => 'Cutting',
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));

        $saved = Defect::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Zero Defect', $saved->jenis_defect);
        $this->assertEquals(0, $saved->quantity);
        $this->assertNull($saved->inspect_process_type_id);
        $this->assertNull($saved->no_mesin);
        $this->assertNull($saved->ditemukan_oleh);
        $this->assertSame('Cutting', $saved->area_ditemukan);
        $this->assertEquals($carline->id, $saved->carline_id);
    }

    public function test_operator_and_admin_update_synchronizes_area_ditemukan_when_process_changes(): void
    {
        $carline = Carline::create(['name' => 'PA-SYNC-LINE', 'car_type_id' => null]);
        $m1 = PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MSN-S1', 'is_active' => true]);
        AreaDitemukan::firstOrCreate(['name' => 'Cutting Initial', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::firstOrCreate(['name' => 'Bonder Update', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);
        AreaDitemukan::firstOrCreate(['name' => 'Crimping Admin', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY], ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]);

        // 1. Buat record awal
        $defect = Defect::create([
            'waktu'                   => now(),
            'user_name'               => 'Operator PA',
            'shift'                   => '1A',
            'jenis_assy'              => 'Pre Assy',
            'carline_id'              => $carline->id,
            'inspect_process_type_id' => null,
            'area_ditemukan'          => 'Cutting Initial',
            'no_mesin'                => 'MSN-S1',
            'ditemukan_oleh'          => 'Operator',
            'jenis_defect'            => 'CORE',
            'jenis_sub_defect'        => 'A.1 - FRAYING',
            'quantity'                => 1,
        ]);

        // 2. Operator update area ke Bonder Update
        $opUpdate = [
            'type'                    => 'Pre Assy',
            'carline_id'              => $carline->id,
            'tanggal'                 => Carbon::today()->toDateString(),
            'jam'                     => '11:00',
            'jenis_defect'            => 'CORE',
            'sub_defect'              => 'A.1 - FRAYING',
            'jumlah'                  => 2,
            'ditemukan_oleh'          => 'Operator',
            'area_ditemukan'          => 'Bonder Update',
            'no_mesin'                => 'MSN-S1',
        ];

        $resOp = $this->actingAsOperator()->put(route('input_defect.update', $defect->id), $opUpdate);
        $resOp->assertRedirect(route('operator.home'));

        $fresh = $defect->fresh();
        $this->assertNull($fresh->inspect_process_type_id);
        $this->assertEquals('Bonder Update', $fresh->area_ditemukan);
        $this->assertEquals(2, $fresh->quantity);

        // 3. Admin update area ke Crimping Admin
        $adminUpdate = array_merge($opUpdate, [
            'area_ditemukan'          => 'Crimping Admin',
            'jumlah'                  => 5,
        ]);

        $resAdmin = $this->actingAsAdmin()->put(route('admin.report.update', $defect->id), $adminUpdate);
        $resAdmin->assertRedirect(route('recent_defects.index'));

        $freshAdmin = $defect->fresh();
        $this->assertNull($freshAdmin->inspect_process_type_id);
        $this->assertEquals('Crimping Admin', $freshAdmin->area_ditemukan);
        $this->assertEquals(5, $freshAdmin->quantity);
    }

    public function test_edit_old_record_with_empty_no_mesin_does_not_require_no_mesin(): void
    {
        $carline = Carline::create(['name' => 'PA-LEGACY-LINE', 'car_type_id' => null]);
        // Carline sekarang punya mesin aktif di master
        PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'NEW-MSN-1', 'is_active' => true]);
        $process = InspectProcessType::firstOrCreate(['name' => 'Process Legacy']);

        // Record lama di DB memiliki no_mesin = NULL
        $oldDefect = Defect::create([
            'waktu'                   => now(),
            'user_name'               => 'Operator PA',
            'shift'                   => '1A',
            'jenis_assy'              => 'Pre Assy',
            'carline_id'              => $carline->id,
            'inspect_process_type_id' => $process->id,
            'area_ditemukan'          => 'Process Legacy',
            'no_mesin'                => null, // Kosong dari data lama
            'ditemukan_oleh'          => 'Operator',
            'jenis_defect'            => 'CORE',
            'jenis_sub_defect'        => 'A.1 - FRAYING',
            'quantity'                => 1,
        ]);

        $updatePayload = [
            'type'                    => 'Pre Assy',
            'carline_id'              => $carline->id,
            'tanggal'                 => Carbon::today()->toDateString(),
            'jam'                     => '12:00',
            'jenis_defect'            => 'CORE',
            'sub_defect'              => 'A.1 - FRAYING',
            'jumlah'                  => 1,
            'ditemukan_oleh'          => 'Operator',
            'inspect_process_type_id' => $process->id,
            'no_mesin'                => '', // Tetap dibiarkan kosong
        ];

        // Operator edit record lama tetap lolos tanpa wajib isi no_mesin
        $res = $this->actingAsOperator()->put(route('input_defect.update', $oldDefect->id), $updatePayload);
        $res->assertSessionHasNoErrors();
        $res->assertRedirect(route('operator.home'));
        $this->assertNull($oldDefect->fresh()->no_mesin);
    }

    public function test_final_assy_isolation_is_completely_unaffected(): void
    {
        AreaDitemukan::create([
            'name'     => 'AREA-FA-TEST',
            'kategori' => AreaDitemukan::KATEGORI_FINAL_ASSY,
            'tipe'     => AreaDitemukan::TIPE_INTERNAL_DEFECT,
        ]);

        $payload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => Carbon::today()->toDateString(),
            'jam'              => '14:00',
            'end_number'       => 'END-99',
            'area_ditemukan'   => 'AREA-FA-TEST',
            'jenis_defect'     => 'MISSING PART',
            'sub_defect'       => '1.A - CLIP',
            'jumlah'           => 1,
            'inspect_quantity' => 20,
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));

        $saved = Defect::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Final Assy', $saved->jenis_assy);
        $this->assertEquals('CALYA', $saved->jenis_mobil);
        $this->assertEquals('1', $saved->conveyor);
        $this->assertEquals('AREA-FA-TEST', $saved->area_ditemukan);
        $this->assertNull($saved->no_mesin);
        $this->assertNull($saved->inspect_process_type_id);
        $this->assertNull($saved->carline_id);
    }
}
