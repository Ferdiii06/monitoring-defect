<?php

namespace Tests\Feature;

use App\Models\Carline;
use App\Models\CarType;
use App\Models\Defect;
use App\Models\DefectType;
use App\Models\SubDefectType;
use App\Models\AreaDitemukan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class PreAssySequenceTest extends TestCase
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

    private function actingAsOperator(string $name = 'Operator Pre')
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => $name,
            'user_role'     => 'User',
            'current_shift' => '1A',
        ]);
    }

    public function test_pre_assy_with_defect_saves_no_sequence_and_ignores_nameplate(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-SEQ', 'car_type_id' => null]);
        AreaDitemukan::firstOrCreate(
            ['name' => 'Cutting Process', 'kategori' => AreaDitemukan::KATEGORI_PRE_ASSY],
            ['tipe' => AreaDitemukan::TIPE_INTERNAL_DEFECT]
        );

        $payload = [
            'type'             => 'Pre Assy',
            'carline_id'       => $carline->id,
            'tanggal'          => Carbon::today()->toDateString(),
            'jam'              => '09:00',
            'jenis_defect'     => 'CORE',
            'sub_defect'       => 'A.1 - FRAYING',
            'jumlah'           => 2,
            'ditemukan_oleh'   => 'Operator',
            'area_ditemukan'   => 'Cutting Process',
            'no_sequence'      => '  SEQ-12345  ',
            'no_defective_tag' => '  TAG-999  ',
            'nameplate_lama'   => 'NP-IGNORED-FOR-PRE',
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('success');

        $saved = Defect::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertSame('Pre Assy', $saved->jenis_assy);
        $this->assertSame('SEQ-12345', $saved->no_sequence);
        $this->assertSame('TAG-999', $saved->no_defective_tag);
        // Pre Assy must ignore nameplate_lama and save null
        $this->assertNull($saved->nameplate_lama);
    }

    public function test_pre_assy_zero_defect_forces_no_sequence_and_no_defective_tag_to_null(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-ZERO', 'car_type_id' => null]);

        $payload = [
            'type'             => 'Pre Assy',
            'carline_id'       => $carline->id,
            'tanggal'          => Carbon::today()->toDateString(),
            'jam'              => '09:00',
            'jenis_defect'     => 'Zero Defect',
            'sub_defect'       => '-',
            'jumlah'           => 0,
            'no_sequence'      => 'SEQ-SHOULD-BE-NULL',
            'no_defective_tag' => 'TAG-SHOULD-BE-NULL',
            'nameplate_lama'   => 'NP-SHOULD-BE-NULL',
        ];

        $response = $this->actingAsOperator()->post(route('input_defect.store'), $payload);
        $response->assertRedirect(route('operator.home'));

        $saved = Defect::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertSame('Pre Assy', $saved->jenis_assy);
        $this->assertNull($saved->no_sequence);
        $this->assertNull($saved->no_defective_tag);
        $this->assertNull($saved->nameplate_lama);
    }

    public function test_form_pre_assy_contains_no_sequence_and_does_not_contain_nameplate(): void
    {
        $response = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Pre Assy']));
        $response->assertStatus(200);

        $response->assertSee('NO SEQUENCE');
        $response->assertDontSee('NAMEPLATE (NP)');
    }

    public function test_form_final_assy_contains_nameplate_and_does_not_contain_no_sequence(): void
    {
        $response = $this->actingAsOperator()->get(route('input_defect.create', ['type' => 'Final Assy']));
        $response->assertStatus(200);

        $response->assertSee('NAMEPLATE (NP)');
        $response->assertDontSee('NO SEQUENCE');
    }

    public function test_operator_home_displays_seq_badge_for_pre_assy_and_np_badge_for_final_assy(): void
    {
        $preDefect = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator Pre',
            'shift'            => '1A',
            'jenis_assy'       => 'Pre Assy',
            'jenis_defect'     => 'CORE',
            'jenis_sub_defect' => 'A.1',
            'quantity'         => 1,
            'no_sequence'      => 'SEQ-777',
        ]);

        $finalDefect = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator Pre',
            'shift'            => '1A',
            'jenis_assy'       => 'Final Assy',
            'jenis_defect'     => 'DIMENSI',
            'jenis_sub_defect' => '1.A',
            'quantity'         => 1,
            'nameplate_lama'   => 'NP-888',
        ]);

        $response = $this->actingAsOperator()->get(route('operator.home'));
        $response->assertStatus(200);

        $response->assertSee('SEQ: SEQ-777');
        $response->assertSee('NP: NP-888');
    }
}
