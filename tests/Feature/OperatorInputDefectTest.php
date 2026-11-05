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

        return compact('carType', 'carline', 'defectType', 'subDefect');
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
}
