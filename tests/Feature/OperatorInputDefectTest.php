<?php

namespace Tests\Feature;

use App\Models\CarType;
use App\Models\Carline;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorInputDefectTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOperator(string $name = 'Operator 1')
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => $name,
            'user_role'     => 'User',
            'current_shift' => '1A',
        ]);
    }

    private function setupMasterData(): array
    {
        $carType = CarType::create(['name' => 'CALYA']);
        $carline = Carline::create([
            'name'        => 'CONV-1',
            'car_type_id' => $carType->id,
        ]);
        $defectType = DefectType::create([
            'name' => 'DIMENSI',
            'type' => 'Final Assy',
        ]);
        $subDefect = SubDefectType::create([
            'defect_type_id' => $defectType->id,
            'name'           => '1.A - DIMENSI PENDEK',
        ]);
        $area = \App\Models\AreaDitemukan::firstOrCreate(
            ['name' => 'CK'],
            ['tipe' => \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY, 'kategori' => \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY]
        );

        return compact('carType', 'carline', 'defectType', 'subDefect', 'area');
    }

    public function test_operator_can_save_internal_defect_with_nameplate_and_defective_tag(): void
    {
        $data = $this->setupMasterData();

        $payload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '10:00',
            'area_ditemukan'   => 'CK',
            'end_number'       => '1',
            'jenis_defect'     => $data['defectType']->name,
            'sub_defect'       => $data['subDefect']->name,
            'jumlah'           => 1,
            'inspect_quantity' => 10,
            'nameplate_lama'   => '  NP-INTERNAL-100  ',
            'no_defective_tag' => '  TAG-DEFECT-200  ',
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);

        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('defects', [
            'jenis_assy'       => 'Final Assy',
            'user_name'        => 'Operator 1',
            'rework_group_id'  => null,
            'nameplate_lama'   => 'NP-INTERNAL-100',
            'no_defective_tag' => 'TAG-DEFECT-200',
            'quantity'         => 1,
        ]);
    }

    public function test_operator_can_save_internal_defect_without_nameplate_and_defective_tag(): void
    {
        $data = $this->setupMasterData();

        $payload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '10:00',
            'area_ditemukan'   => 'CK',
            'end_number'       => '1',
            'jenis_defect'     => $data['defectType']->name,
            'sub_defect'       => $data['subDefect']->name,
            'jumlah'           => 1,
            'inspect_quantity' => 10,
            'nameplate_lama'   => '',
            'no_defective_tag' => null,
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);

        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('defects', [
            'jenis_assy'       => 'Final Assy',
            'user_name'        => 'Operator 1',
            'rework_group_id'  => null,
            'nameplate_lama'   => null,
            'no_defective_tag' => null,
            'quantity'         => 1,
        ]);
    }

    public function test_operator_input_defect_validates_max_length_for_np_and_tag(): void
    {
        $data = $this->setupMasterData();

        $payload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '10:00',
            'area_ditemukan'   => 'CK',
            'end_number'       => '1',
            'jenis_defect'     => $data['defectType']->name,
            'sub_defect'       => $data['subDefect']->name,
            'jumlah'           => 1,
            'nameplate_lama'   => str_repeat('A', 256),
            'no_defective_tag' => str_repeat('B', 256),
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);

        $response->assertSessionHasErrors(['nameplate_lama', 'no_defective_tag']);
        $this->assertDatabaseCount('defects', 0);
    }

    public function test_edit_form_displays_existing_np_and_tag_and_updates_successfully(): void
    {
        $data = $this->setupMasterData();

        $defect = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator 1',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => 'CK',
            'end_number'       => '1',
            'jenis_defect'     => $data['defectType']->name,
            'jenis_sub_defect' => $data['subDefect']->name,
            'quantity'         => 1,
            'nameplate_lama'   => 'NP-ORIGINAL-99',
            'no_defective_tag' => 'TAG-ORIGINAL-88',
        ]);

        $resEdit = $this->actingAsOperator()->get(route('input_defect.edit', $defect->id));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('NP-ORIGINAL-99');
        $resEdit->assertSee('TAG-ORIGINAL-88');

        $updatePayload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '11:00',
            'area_ditemukan'   => 'CK',
            'end_number'       => '1',
            'jenis_defect'     => $data['defectType']->name,
            'sub_defect'       => $data['subDefect']->name,
            'jumlah'           => 2,
            'nameplate_lama'   => '  NP-REVISED-101  ',
            'no_defective_tag' => '  TAG-REVISED-202  ',
        ];

        $resUpdate = $this->actingAsOperator()->put(route('input_defect.update', $defect->id), $updatePayload);
        $resUpdate->assertRedirect(route('operator.home'));

        $fresh = $defect->fresh();
        $this->assertEquals('NP-REVISED-101', $fresh->nameplate_lama);
        $this->assertEquals('TAG-REVISED-202', $fresh->no_defective_tag);
        $this->assertEquals(2, $fresh->quantity);
    }

    public function test_operator_home_displays_np_and_defective_tag_badges(): void
    {
        $data = $this->setupMasterData();

        Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator 1',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'jenis_defect'     => $data['defectType']->name,
            'jenis_sub_defect' => $data['subDefect']->name,
            'quantity'         => 1,
            'nameplate_lama'   => 'NP-BADGE-123',
            'no_defective_tag' => 'TAG-BADGE-456',
        ]);

        $response = $this->actingAsOperator()->get(route('operator.home'));
        $response->assertStatus(200);
        $response->assertSee('NP: NP-BADGE-123');
        $response->assertSee('Tag: TAG-BADGE-456');
    }

    public function test_non_zero_defect_requires_area_ditemukan_with_indonesian_message(): void
    {
        $data = $this->setupMasterData();

        $payload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '10:00',
            'jenis_defect'     => $data['defectType']->name,
            'sub_defect'       => $data['subDefect']->name,
            'jumlah'           => 1,
            'end_number'       => '1',
            // area_ditemukan omitted
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertSessionHasErrors([
            'area_ditemukan' => 'Area ditemukan wajib diisi.',
        ]);
        $this->assertDatabaseCount('defects', 0);
    }

    public function test_zero_defect_in_final_assy_saves_null_area_ditemukan(): void
    {
        $this->setupMasterData();

        $payload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '10:00',
            'jenis_defect'     => 'Zero Defect',
            'sub_defect'       => '-',
            'jumlah'           => 0,
            'area_ditemukan'   => 'CK', // even if provided, must be saved as null
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('defects', [
            'jenis_assy'     => 'Final Assy',
            'jenis_defect'   => 'Zero Defect',
            'quantity'       => 0,
            'area_ditemukan' => null,
        ]);
    }

    public function test_pre_assy_non_zero_defect_requires_area_ditemukan(): void
    {
        $carline = Carline::create(['name' => 'LINE-PRE-1']);
        \App\Models\AreaDitemukan::firstOrCreate(
            ['name' => 'Cutting', 'kategori' => \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $payloadWithoutArea = [
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'tanggal'        => '2026-11-05',
            'jam'            => '10:00',
            'jenis_defect'   => 'CRIMPING',
            'sub_defect'     => 'OVER CRIMP',
            'jumlah'         => 1,
            'ditemukan_oleh' => 'Operator',
        ];

        $res1 = $this->actingAsOperator()->post(route('input_defect.store'), $payloadWithoutArea);
        $res1->assertSessionHasErrors([
            'area_ditemukan' => 'Area ditemukan Pre Assy wajib dipilih.',
        ]);

        $payloadWithArea = array_merge($payloadWithoutArea, ['area_ditemukan' => 'Cutting']);
        $res2 = $this->actingAsOperator()->post(route('input_defect.store'), $payloadWithArea);
        $res2->assertRedirect(route('operator.home'));

        $this->assertDatabaseHas('defects', [
            'jenis_assy'              => 'Pre Assy',
            'inspect_process_type_id' => null,
            'area_ditemukan'          => 'Cutting',
            'quantity'                => 1,
        ]);
    }

    public function test_pre_assy_zero_defect_does_not_require_area_ditemukan(): void
    {
        $carline = Carline::create(['name' => 'LINE-PRE-2']);

        $payload = [
            'type'           => 'Pre Assy',
            'carline_id'     => $carline->id,
            'tanggal'        => '2026-11-05',
            'jam'            => '10:00',
            'jenis_defect'   => 'Zero Defect',
            'sub_defect'     => '-',
            'jumlah'         => 0,
            'ditemukan_oleh' => 'Inspektor',
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));

        $this->assertDatabaseHas('defects', [
            'jenis_assy'     => 'Pre Assy',
            'jenis_defect'   => 'Zero Defect',
            'quantity'       => 0,
        ]);
    }

    public function test_legacy_zero_defect_with_area_can_be_edited_without_error(): void
    {
        $this->setupMasterData();

        $defect = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator 1',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => 'CK',
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
        ]);

        $resEdit = $this->actingAsOperator()->get(route('input_defect.edit', $defect->id));
        $resEdit->assertStatus(200);

        $updatePayload = [
            'type'           => 'Final Assy',
            'jenis_mobil'    => 'CALYA',
            'conveyor'       => '1',
            'tanggal'        => '2026-11-05',
            'jam'            => '11:00',
            'jenis_defect'   => 'Zero Defect',
            'sub_defect'     => '-',
            'jumlah'         => 0,
        ];

        $resUpdate = $this->actingAsOperator()->put(route('input_defect.update', $defect->id), $updatePayload);
        $resUpdate->assertRedirect(route('operator.home'));

        $fresh = $defect->fresh();
        $this->assertNull($fresh->area_ditemukan);
        $this->assertEquals(0, $fresh->quantity);
    }

    /**
     * BATCH 4 TESTS
     */

    public function test_final_assy_non_zero_defect_requires_end_number_and_rejects_when_empty(): void
    {
        $data = $this->setupMasterData();

        // 1. Post tanpa end_number ditolak
        $payloadMissingEnd = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '10:00',
            'area_ditemukan'   => 'CK',
            'jenis_defect'     => $data['defectType']->name,
            'sub_defect'       => $data['subDefect']->name,
            'jumlah'           => 1,
            // end_number omitted
        ];

        $res1 = $this->actingAsOperator()->post(route('input_defect.store'), $payloadMissingEnd);
        $res1->assertSessionHasErrors(['end_number' => 'Nomor End (#) wajib diisi.']);
        $this->assertDatabaseCount('defects', 0);

        // 2. Post dengan end_number kosong string ditolak
        $payloadEmptyEnd = array_merge($payloadMissingEnd, ['end_number' => '']);
        $res2 = $this->actingAsOperator()->post(route('input_defect.store'), $payloadEmptyEnd);
        $res2->assertSessionHasErrors(['end_number' => 'Nomor End (#) wajib diisi.']);

        // 3. Post dengan end_number terisi sukses
        $payloadValid = array_merge($payloadMissingEnd, ['end_number' => 'END-01']);
        $res3 = $this->actingAsOperator()->post(route('input_defect.store'), $payloadValid);
        $res3->assertRedirect(route('operator.home'));
        $this->assertDatabaseHas('defects', [
            'jenis_assy' => 'Final Assy',
            'end_number' => 'END-01',
            'quantity'   => 1,
        ]);

        // 4. Update tanpa end_number ditolak
        $defect = Defect::latest('id')->first();
        $resUpdate = $this->actingAsOperator()->put(route('input_defect.update', $defect->id), array_merge($payloadValid, ['end_number' => '']));
        $resUpdate->assertSessionHasErrors(['end_number' => 'Nomor End (#) wajib diisi.']);
    }

    public function test_zero_defect_in_final_assy_forces_end_number_to_null_even_if_provided(): void
    {
        $this->setupMasterData();

        // 1. Store Zero Defect dengan end_number, specification, actual terisi di request
        $payload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '10:00',
            'jenis_defect'     => 'Zero Defect',
            'sub_defect'       => '-',
            'jumlah'           => 0,
            'end_number'       => 'END-99',
            'specification'    => 'SPEC-100',
            'actual'           => 'ACT-100',
            'area_ditemukan'   => 'CK',
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('success');

        $saved = Defect::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Zero Defect', $saved->jenis_defect);
        $this->assertEquals(0, $saved->quantity);
        $this->assertNull($saved->end_number);
        $this->assertNull($saved->specification);
        $this->assertNull($saved->actual);
        $this->assertNull($saved->area_ditemukan);

        // 2. Update menjadi Zero Defect dengan end_number tetap dipaksa null
        $defect = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator 1',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => 'CK',
            'end_number'       => 'END-ORIG',
            'specification'    => 'SPEC-ORIG',
            'actual'           => 'ACT-ORIG',
            'jenis_defect'     => 'DIMENSI',
            'jenis_sub_defect' => '1.A',
            'quantity'         => 1,
        ]);

        $updatePayload = [
            'type'          => 'Final Assy',
            'jenis_mobil'   => 'CALYA',
            'conveyor'      => '1',
            'tanggal'       => '2026-11-05',
            'jam'           => '11:00',
            'jenis_defect'  => 'Zero Defect',
            'sub_defect'    => '-',
            'jumlah'        => 0,
            'end_number'    => 'END-UPDATED',
            'specification' => 'SPEC-UPDATED',
            'actual'        => 'ACT-UPDATED',
        ];

        $resUpdate = $this->actingAsOperator()->put(route('input_defect.update', $defect->id), $updatePayload);
        $resUpdate->assertRedirect(route('operator.home'));

        $fresh = $defect->fresh();
        $this->assertEquals('Zero Defect', $fresh->jenis_defect);
        $this->assertEquals(0, $fresh->quantity);
        $this->assertNull($fresh->end_number);
        $this->assertNull($fresh->specification);
        $this->assertNull($fresh->actual);
    }

    public function test_actual_is_forced_to_null_when_specification_is_empty_and_saved_when_specification_is_filled(): void
    {
        $data = $this->setupMasterData();

        $basePayload = [
            'type'             => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'tanggal'          => '2026-11-05',
            'jam'              => '10:00',
            'area_ditemukan'   => 'CK',
            'end_number'       => 'END-01',
            'jenis_defect'     => $data['defectType']->name,
            'sub_defect'       => $data['subDefect']->name,
            'jumlah'           => 1,
        ];

        // 1. Specification null, actual terisi => actual dipaksa null
        $res1 = $this->actingAsOperator()->post(route('input_defect.store'), array_merge($basePayload, [
            'specification' => null,
            'actual'        => '5.5 mm',
        ]));
        $res1->assertRedirect(route('operator.home'));
        $d1 = Defect::latest('id')->first();
        $this->assertNull($d1->specification);
        $this->assertNull($d1->actual);

        // 2. Specification kosong string/whitespace, actual terisi => actual dipaksa null
        $res2 = $this->actingAsOperator()->post(route('input_defect.store'), array_merge($basePayload, [
            'specification' => '   ',
            'actual'        => '5.5 mm',
        ]));
        $res2->assertRedirect(route('operator.home'));
        $d2 = Defect::latest('id')->first();
        $this->assertNull($d2->specification);
        $this->assertNull($d2->actual);

        // 3. Specification terisi, actual kosong => specification tersimpan, actual null (karena actual opsional)
        $res3 = $this->actingAsOperator()->post(route('input_defect.store'), array_merge($basePayload, [
            'specification' => '5.0 mm',
            'actual'        => null,
        ]));
        $res3->assertRedirect(route('operator.home'));
        $d3 = Defect::latest('id')->first();
        $this->assertEquals('5.0 mm', $d3->specification);
        $this->assertNull($d3->actual);

        // 4. Specification terisi, actual terisi => keduanya tersimpan
        $res4 = $this->actingAsOperator()->post(route('input_defect.store'), array_merge($basePayload, [
            'specification' => '5.0 mm',
            'actual'        => '5.5 mm',
        ]));
        $res4->assertRedirect(route('operator.home'));
        $d4 = Defect::latest('id')->first();
        $this->assertEquals('5.0 mm', $d4->specification);
        $this->assertEquals('5.5 mm', $d4->actual);
    }

    public function test_input_defect_page_does_not_contain_laporan_terkunci_text(): void
    {
        $this->setupMasterData();

        // 1. Create page dengan type Final Assy: kotak "Area Assy" sudah dihapus
        $resFinal = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Final Assy']));
        $resFinal->assertStatus(200);
        $resFinal->assertDontSee('Laporan Terkunci', false);
        $resFinal->assertDontSee('Area Assy');
        $resFinal->assertSee('name="type"', false);

        // 2. Create page dengan type Pre Assy: kotak "Area Assy" sudah dihapus
        $resPre = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Pre Assy']));
        $resPre->assertStatus(200);
        $resPre->assertDontSee('Laporan Terkunci', false);
        $resPre->assertDontSee('Area Assy');
        $resPre->assertSee('name="type"', false);

        // 3. Edit page: kotak "Area Assy" sudah dihapus
        $defect = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator 1',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_mobil'      => 'CALYA',
            'conveyor'         => '1',
            'area_ditemukan'   => 'CK',
            'end_number'       => 'END-01',
            'jenis_defect'     => 'DIMENSI',
            'jenis_sub_defect' => '1.A',
            'quantity'         => 1,
        ]);
        $resEdit = $this->actingAsOperator()->get(route('input_defect.edit', $defect->id));
        $resEdit->assertStatus(200);
        $resEdit->assertDontSee('Laporan Terkunci', false);
        $resEdit->assertDontSee('Area Assy');
        $resEdit->assertSee('name="type"', false);
    }

    public function test_pre_assy_does_not_accept_end_number_and_forces_it_to_null(): void
    {
        $carline = Carline::create(['name' => 'LINE-PRE-END-TEST']);
        $process = \App\Models\InspectProcessType::firstOrCreate(['name' => 'Cutting']);

        $payload = [
            'type'                    => 'Pre Assy',
            'carline_id'              => $carline->id,
            'tanggal'                 => '2026-11-05',
            'jam'                     => '10:00',
            'jenis_defect'            => 'CRIMPING',
            'sub_defect'              => 'OVER CRIMP',
            'jumlah'                  => 1,
            'inspect_process_type_id' => $process->id,
            'ditemukan_oleh'          => 'Operator',
            'end_number'              => 'END-PRE-99', // Pre Assy tidak punya End, harus dipaksa null di DB
        ];

        // 1. Store Pre Assy
        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));

        $saved = Defect::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Pre Assy', $saved->jenis_assy);
        $this->assertNull($saved->end_number);

        // 2. Update Pre Assy
        $updatePayload = array_merge($payload, [
            'jumlah'     => 2,
            'end_number' => 'END-PRE-100',
        ]);
        $resUpdate = $this->actingAsOperator()->put(route('input_defect.update', $saved->id), $updatePayload);
        $resUpdate->assertRedirect(route('operator.home'));

        $fresh = $saved->fresh();
        $this->assertEquals(2, $fresh->quantity);
        $this->assertNull($fresh->end_number);
    }

    public function test_area_ditemukan_dropdown_displays_tipe_label_for_final_assy_and_plain_name_for_pre_assy(): void
    {
        \App\Models\AreaDitemukan::updateOrCreate(
            ['name' => 'FC'],
            ['kategori' => \App\Models\AreaDitemukan::KATEGORI_FINAL_ASSY, 'tipe' => \App\Models\AreaDitemukan::TIPE_ASSEMBLY_DEFECT]
        );
        \App\Models\AreaDitemukan::firstOrCreate(
            ['name' => 'Cutting', 'kategori' => \App\Models\AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => \App\Models\AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        // Final Assy page menampilkan tipe label
        $resFinal = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Final Assy']));
        $resFinal->assertStatus(200);
        $resFinal->assertSee('FC (Assembly Defect)');

        // Pre Assy page menampilkan Area Ditemukan Pre Assy
        $resPre = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Pre Assy']));
        $resPre->assertStatus(200);
        $resPre->assertSee('AREA DITEMUKAN PRE ASSY');
        $resPre->assertSee('>Cutting</option>', false);
        $resPre->assertDontSee('Cutting (Internal Defect)', false);
    }
}
