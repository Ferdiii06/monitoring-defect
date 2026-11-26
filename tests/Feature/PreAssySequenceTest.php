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
            'area_ditemukan'   => 'Cutting',
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

        // Template No Sequence ada di form Pre Assy, Nameplate tidak ada
        $response->assertSee('NO SEQUENCE');
        $response->assertDontSee('NAMEPLATE (NP)');

        // Kondisi Alpine: No Sequence hanya aktif jika ada defect dan bukan Zero Defect
        $response->assertSee("form.type === 'Pre Assy' && form.jenis_defect && form.jenis_defect !== 'Zero Defect'", false);

        // State awal saat baru buka: jenis_defect kosong sehingga field tersembunyi
        $response->assertSee("jenis_defect: ''", false);
    }

    public function test_form_pre_assy_edit_displays_saved_sequence_when_defect_exists(): void
    {
        $defect = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator Pre',
            'shift'            => '1A',
            'jenis_assy'       => 'Pre Assy',
            'jenis_defect'     => 'CORE',
            'jenis_sub_defect' => 'A.1',
            'quantity'         => 1,
            'no_sequence'      => 'SEQ-EXISTING-123',
            'no_defective_tag' => 'TAG-EXISTING-456',
        ]);

        $response = $this->actingAsOperator()->get(route('input_defect.edit', $defect->id));
        $response->assertStatus(200);

        // State terisi dengan defect dan sequence tersimpan
        $response->assertSee("jenis_defect: 'CORE'", false);
        $response->assertSee("no_sequence: 'SEQ-EXISTING-123'", false);
        $response->assertSee("no_defective_tag: 'TAG-EXISTING-456'", false);
    }

    public function test_form_pre_assy_edit_zero_defect_keeps_sequence_empty(): void
    {
        $zeroDefect = Defect::create([
            'waktu'            => now(),
            'user_name'        => 'Operator Pre',
            'shift'            => '1A',
            'jenis_assy'       => 'Pre Assy',
            'jenis_defect'     => 'Zero Defect',
            'jenis_sub_defect' => '-',
            'quantity'         => 0,
            'no_sequence'      => null,
            'no_defective_tag' => null,
        ]);

        $response = $this->actingAsOperator()->get(route('input_defect.edit', $zeroDefect->id));
        $response->assertStatus(200);

        // State jenis_defect bernilai 'Zero Defect' (sehingga kondisi x-if Alpine false)
        $response->assertSee("jenis_defect: 'Zero Defect'", false);
        $response->assertSee("no_sequence: ''", false);
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
