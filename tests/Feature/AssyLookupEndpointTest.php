<?php

namespace Tests\Feature;

use App\Models\AssyNumber;
use App\Models\Carline;
use App\Models\CarType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssyLookupEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRework(string $name = 'rework_operator')
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => $name,
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ]);
    }

    private function actingAsAdmin(string $name = 'Admin')
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => $name,
            'user_role' => 'Administrator',
        ]);
    }

    private function actingAsOperator(string $name = 'Operator')
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => $name,
            'user_role' => 'User',
        ]);
    }

    public function test_lookup_endpoint_with_new_e_kanban_when_assy_exists_in_master(): void
    {
        $carType = CarType::create(['name' => 'AVANZA']);
        $carline = Carline::create([
            'name'        => '664W-C5',
            'car_type_id' => $carType->id,
        ]);
        AssyNumber::create([
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
            'keterangan'  => 'Assy Utama',
        ]);

        $response = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '*82111-VU020.C7.010*',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'assy_number'         => '82111-VU020',
            'conveyor'            => 'C7',
            'serial'              => '010',
            'carline_id'          => $carline->id,
            'carline_nama'        => '664W-C5',
            'ditemukan_di_master' => true,
        ]);
    }

    public function test_lookup_endpoint_with_old_kanban_and_qr_nameplate(): void
    {
        $carType = CarType::create(['name' => 'VELOZ']);
        $carline = Carline::create([
            'name'        => 'AT-LINE',
            'car_type_id' => $carType->id,
        ]);
        // Disimpan dengan tanda "-" di master
        AssyNumber::create([
            'assy_number' => '82111-VU020',
            'carline_id'  => $carline->id,
        ]);

        // Scan QR Nameplate tanpa tanda "-" ("82111VU020  A2607060008") harus cocok dengan master
        $resNameplate = $this->actingAsAdmin()->getJson(route('rework.lookup_assy', [
            'scan' => '82111VU020  A2607060008',
        ]));

        $resNameplate->assertStatus(200);
        $resNameplate->assertJson([
            'assy_number'         => '82111-VU020',
            'conveyor'            => null,
            'serial'              => '0008',
            'carline_id'          => $carline->id,
            'carline_nama'        => 'AT-LINE',
            'ditemukan_di_master' => true,
        ]);

        // Scan kanban lama (format titik: 82115-0EA11.AT11.057)
        $resOldKanban = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '82115-0EA11.AT11.057',
        ]));
        $resOldKanban->assertStatus(200);
        $resOldKanban->assertJson([
            'assy_number'         => '82115-0EA11',
            'conveyor'            => 'AT11',
            'serial'              => '057',
            'carline_id'          => null,
            'carline_nama'        => null,
            'ditemukan_di_master' => false,
        ]);
    }

    public function test_lookup_endpoint_when_assy_not_in_master(): void
    {
        $response = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '82119-99999.C1.001',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'assy_number'         => '82119-99999',
            'conveyor'            => 'C1',
            'serial'              => '001',
            'carline_id'          => null,
            'carline_nama'        => null,
            'ditemukan_di_master' => false,
        ]);
    }

    public function test_lookup_endpoint_with_invalid_code_returns_422(): void
    {
        // 1. Kode acak tidak valid
        $resInvalid = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => 'INVALID_CODE_123',
        ]));
        $resInvalid->assertStatus(422);
        $resInvalid->assertJsonStructure(['message']);

        // 2. Kode kosong
        $resEmpty = $this->actingAsRework()->getJson(route('rework.lookup_assy', [
            'code' => '',
        ]));
        $resEmpty->assertStatus(422);
        $resEmpty->assertJsonStructure(['message']);
    }

    public function test_lookup_endpoint_rejects_unauthorized_role(): void
    {
        // Role operator ditolak 403
        $resOperator = $this->actingAsOperator()->getJson(route('rework.lookup_assy', [
            'code' => '*82111-VU020.C7.010*',
        ]));
        $resOperator->assertStatus(403);
    }

    public function test_lookup_endpoint_rejects_guest(): void
    {
        // Guest tanpa login diarahkan ke login
        $resGuest = $this->get(route('rework.lookup_assy', [
            'code' => '*82111-VU020.C7.010*',
        ]));
        $resGuest->assertRedirect(route('login'));
    }
}
