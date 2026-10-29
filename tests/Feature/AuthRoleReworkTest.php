<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthRoleReworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_operator_berhasil_dengan_akun_role_user(): void
    {
        $user = User::firstOrCreate(
            ['name' => 'Operator QA'],
            ['role' => 'User', 'pin' => '123456']
        );

        $response = $this->post('/login', [
            'login_type' => 'user',
            'name'       => 'Operator QA',
            'shift'      => '1A',
            'pin'        => '123456',
        ]);

        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('logged_in', true);
        $response->assertSessionHas('user_role', 'User');
        $response->assertSessionHas('user_name', 'Operator QA');
        $response->assertSessionHas('current_shift', '1A');
    }

    public function test_login_operator_ditolak_dengan_akun_rework_dan_administrator(): void
    {
        User::firstOrCreate(
            ['name' => 'reworkA'],
            ['role' => 'Rework', 'pin' => '123456']
        );

        User::firstOrCreate(
            ['name' => 'Admin QA'],
            ['role' => 'Administrator', 'pin' => 'admin1']
        );

        // 1. Akun Rework mencoba login via tab Operator -> Harus DITOLAK
        $response1 = $this->post('/login', [
            'login_type' => 'user',
            'name'       => 'reworkA',
            'shift'      => '1A',
            'pin'        => '123456',
        ]);

        $response1->assertSessionHasErrors(['name']);
        $this->assertNull(session('logged_in'));

        // 2. Akun Administrator mencoba login via tab Operator -> Harus DITOLAK
        $response2 = $this->post('/login', [
            'login_type' => 'user',
            'name'       => 'Admin QA',
            'shift'      => '1A',
            'pin'        => 'admin1',
        ]);

        $response2->assertSessionHasErrors(['name']);
        $this->assertNull(session('logged_in'));
    }

    public function test_login_rework_berhasil_dengan_reworkA(): void
    {
        User::firstOrCreate(
            ['name' => 'reworkA'],
            ['role' => 'Rework', 'pin' => '123456']
        );

        $response = $this->post('/login', [
            'login_type' => 'rework',
            'name'       => 'reworkA',
            'shift'      => '2A',
            'pin'        => '123456',
        ]);

        $response->assertRedirect(route('operator.home'));
        $response->assertSessionHas('logged_in', true);
        $response->assertSessionHas('user_role', 'Rework');
        $response->assertSessionHas('user_name', 'reworkA');
        $response->assertSessionHas('current_shift', '2A');
    }

    public function test_login_rework_ditolak_dengan_akun_operator_dan_administrator(): void
    {
        User::firstOrCreate(
            ['name' => 'Operator 1'],
            ['role' => 'User', 'pin' => '123456']
        );

        User::firstOrCreate(
            ['name' => 'Admin QA'],
            ['role' => 'Administrator', 'pin' => 'admin1']
        );

        // 1. Akun Operator mencoba login via tab Rework -> Harus DITOLAK
        $response1 = $this->post('/login', [
            'login_type' => 'rework',
            'name'       => 'Operator 1',
            'shift'      => '1A',
            'pin'        => '123456',
        ]);

        $response1->assertSessionHasErrors(['name']);
        $this->assertNull(session('logged_in'));

        // 2. Akun Admin mencoba login via tab Rework -> Harus DITOLAK
        $response2 = $this->post('/login', [
            'login_type' => 'rework',
            'name'       => 'Admin QA',
            'shift'      => '1A',
            'pin'        => 'admin1',
        ]);

        $response2->assertSessionHasErrors(['name']);
        $this->assertNull(session('logged_in'));
    }

    public function test_login_admin_hanya_untuk_administrator(): void
    {
        User::firstOrCreate(
            ['name' => 'Admin QA'],
            ['role' => 'Administrator', 'pin' => 'admin123']
        );

        User::firstOrCreate(
            ['name' => 'reworkA'],
            ['role' => 'Rework', 'pin' => '123456']
        );

        User::firstOrCreate(
            ['name' => 'Operator QA'],
            ['role' => 'User', 'pin' => '123456']
        );

        // 1. Akun Administrator login via tab Admin -> Harus BERHASIL
        $response1 = $this->post('/login', [
            'login_type' => 'admin',
            'name'       => 'Admin QA',
            'pin'        => 'admin123',
        ]);

        $response1->assertRedirect(route('dashboard'));
        $response1->assertSessionHas('logged_in', true);
        $response1->assertSessionHas('user_role', 'Administrator');

        // 2. Akun Rework login via tab Admin -> Harus DITOLAK
        $response2 = $this->post('/login', [
            'login_type' => 'admin',
            'name'       => 'reworkA',
            'pin'        => '123456',
        ]);

        $response2->assertSessionHasErrors(['name']);

        // 3. Akun Operator login via tab Admin -> Harus DITOLAK
        $response3 = $this->post('/login', [
            'login_type' => 'admin',
            'name'       => 'Operator QA',
            'pin'        => '123456',
        ]);

        $response3->assertSessionHasErrors(['name']);
    }

    public function test_akun_rework_tidak_bisa_mengakses_route_admin(): void
    {
        $adminRoutes = [
            '/dashboard',
            '/master/area-ditemukans',
            '/add-account',
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->withSession([
                'logged_in' => true,
                'user_name' => 'reworkA',
                'user_role' => 'Rework',
            ])->get($route);

            $response->assertRedirect(route('operator.home'));
        }
    }

    public function test_akun_rework_bisa_mengakses_operator_home(): void
    {
        $response = $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'reworkA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ])->get('/home');

        $response->assertStatus(200);
        $response->assertSee('Operator Workspace');
        $response->assertSee('reworkA');
    }

    public function test_root_and_login_routes_redirect_rework_to_operator_home(): void
    {
        $responseRoot = $this->withSession([
            'logged_in' => true,
            'user_name' => 'reworkA',
            'user_role' => 'Rework',
        ])->get('/');

        $responseRoot->assertRedirect(route('operator.home'));

        $responseLogin = $this->withSession([
            'logged_in' => true,
            'user_name' => 'reworkA',
            'user_role' => 'Rework',
        ])->get('/login');

        $responseLogin->assertRedirect(route('operator.home'));
    }

    public function test_admin_can_create_and_update_rework_account_with_6_digit_pin(): void
    {
        // 1. Buat akun Rework valid (PIN 6 digit) -> Harus BERHASIL
        $response1 = $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator',
        ])->post('/add-account', [
            'name' => 'reworkC',
            'role' => 'Rework',
            'pin'  => '654321',
        ]);

        $response1->assertRedirect(route('account.create'));
        $response1->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'reworkC',
            'role' => 'Rework',
            'pin'  => '654321',
        ]);

        // 2. Buat akun Rework dengan PIN kurang dari 6 digit -> Harus DITOLAK
        $response2 = $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator',
        ])->post('/add-account', [
            'name' => 'reworkD',
            'role' => 'Rework',
            'pin'  => '1234',
        ]);

        $response2->assertSessionHasErrors(['pin']);
        $this->assertDatabaseMissing('users', [
            'name' => 'reworkD',
        ]);

        // 3. Update akun Rework
        $user = User::where('name', 'reworkC')->firstOrFail();
        $response3 = $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator',
        ])->put("/add-account/{$user->id}", [
            'name' => 'reworkC_updated',
            'role' => 'Rework',
            'pin'  => '999888',
        ]);

        $response3->assertRedirect(route('account.create'));
        $response3->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => 'reworkC_updated',
            'role' => 'Rework',
            'pin'  => '999888',
        ]);
    }

    public function test_pesan_error_login_identik_untuk_nama_tidak_ada_pin_salah_dan_role_salah_di_tiap_tab(): void
    {
        $expectedError = 'Nama atau PIN salah.';

        User::firstOrCreate(['name' => 'OpAkun'], ['role' => 'User', 'pin' => '111222']);
        User::firstOrCreate(['name' => 'RewAkun'], ['role' => 'Rework', 'pin' => '333444']);
        User::firstOrCreate(['name' => 'AdmAkun'], ['role' => 'Administrator', 'pin' => 'adminpass']);

        // Tab User
        // 1. Nama tidak ada
        $res = $this->post('/login', ['login_type' => 'user', 'name' => 'NonExistent', 'shift' => '1A', 'pin' => '111222']);
        $res->assertSessionHasErrors(['name' => $expectedError]);
        // 2. PIN salah
        $res = $this->post('/login', ['login_type' => 'user', 'name' => 'OpAkun', 'shift' => '1A', 'pin' => '999999']);
        $res->assertSessionHasErrors(['name' => $expectedError]);
        // 3. Role salah (akun Rework coba di tab User)
        $res = $this->post('/login', ['login_type' => 'user', 'name' => 'RewAkun', 'shift' => '1A', 'pin' => '333444']);
        $res->assertSessionHasErrors(['name' => $expectedError]);

        // Tab Rework
        // 1. Nama tidak ada
        $res = $this->post('/login', ['login_type' => 'rework', 'name' => 'NonExistent', 'shift' => '1A', 'pin' => '333444']);
        $res->assertSessionHasErrors(['name' => $expectedError]);
        // 2. PIN salah
        $res = $this->post('/login', ['login_type' => 'rework', 'name' => 'RewAkun', 'shift' => '1A', 'pin' => '999999']);
        $res->assertSessionHasErrors(['name' => $expectedError]);
        // 3. Role salah (akun User coba di tab Rework)
        $res = $this->post('/login', ['login_type' => 'rework', 'name' => 'OpAkun', 'shift' => '1A', 'pin' => '111222']);
        $res->assertSessionHasErrors(['name' => $expectedError]);

        // Tab Admin
        // 1. Nama tidak ada
        $res = $this->post('/login', ['login_type' => 'admin', 'name' => 'NonExistent', 'pin' => 'adminpass']);
        $res->assertSessionHasErrors(['name' => $expectedError]);
        // 2. PIN salah
        $res = $this->post('/login', ['login_type' => 'admin', 'name' => 'AdmAkun', 'pin' => 'wrongpass']);
        $res->assertSessionHasErrors(['name' => $expectedError]);
        // 3. Role salah (akun User coba di tab Admin)
        $res = $this->post('/login', ['login_type' => 'admin', 'name' => 'OpAkun', 'pin' => '111222']);
        $res->assertSessionHasErrors(['name' => $expectedError]);
    }

    public function test_validasi_pin_dan_perubahan_role_di_account_controller(): void
    {
        $adminSession = [
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator',
        ];

        // 1. Buat akun User/Rework dengan PIN non-angka 'abcdef' -> Ditolak
        $res = $this->withSession($adminSession)->post('/add-account', [
            'name' => 'UserAlphaPin',
            'role' => 'User',
            'pin'  => 'abcdef',
        ]);
        $res->assertSessionHasErrors(['pin']);

        $res = $this->withSession($adminSession)->post('/add-account', [
            'name' => 'ReworkAlphaPin',
            'role' => 'Rework',
            'pin'  => 'abcdef',
        ]);
        $res->assertSessionHasErrors(['pin']);

        // 2. Buat akun User/Rework dengan PIN 8 digit '12345678' -> Ditolak
        $res = $this->withSession($adminSession)->post('/add-account', [
            'name' => 'UserEightPin',
            'role' => 'User',
            'pin'  => '12345678',
        ]);
        $res->assertSessionHasErrors(['pin']);

        $res = $this->withSession($adminSession)->post('/add-account', [
            'name' => 'ReworkEightPin',
            'role' => 'Rework',
            'pin'  => '12345678',
        ]);
        $res->assertSessionHasErrors(['pin']);

        // 3. Buat akun Administrator dengan PIN 4 karakter
        $adminUser = User::forceCreate([
            'name' => 'TargetAdminToConvert',
            'role' => 'Administrator',
            'pin'  => 'adm4',
        ]);

        // 4. Ubah role ke User tanpa PIN -> Ditolak karena role baru User dan PIN lama bukan 6 digit angka
        $res = $this->withSession($adminSession)->put("/add-account/{$adminUser->id}", [
            'name' => 'TargetAdminToConvert',
            'role' => 'User',
            'pin'  => '',
        ]);
        $res->assertSessionHasErrors(['pin']);

        // 5. Ubah role ke Rework tanpa PIN -> Ditolak
        $res = $this->withSession($adminSession)->put("/add-account/{$adminUser->id}", [
            'name' => 'TargetAdminToConvert',
            'role' => 'Rework',
            'pin'  => '',
        ]);
        $res->assertSessionHasErrors(['pin']);

        // 6. Ubah role ke Rework dengan PIN 'abcdef' -> Ditolak
        $res = $this->withSession($adminSession)->put("/add-account/{$adminUser->id}", [
            'name' => 'TargetAdminToConvert',
            'role' => 'Rework',
            'pin'  => 'abcdef',
        ]);
        $res->assertSessionHasErrors(['pin']);

        // 7. Ubah role ke Rework dengan PIN '12345678' -> Ditolak
        $res = $this->withSession($adminSession)->put("/add-account/{$adminUser->id}", [
            'name' => 'TargetAdminToConvert',
            'role' => 'Rework',
            'pin'  => '12345678',
        ]);
        $res->assertSessionHasErrors(['pin']);

        // 8. Ubah role ke Rework dengan PIN 6 digit angka '654321' -> Berhasil
        $res = $this->withSession($adminSession)->put("/add-account/{$adminUser->id}", [
            'name' => 'ConvertedToRework',
            'role' => 'Rework',
            'pin'  => '654321',
        ]);
        $res->assertRedirect(route('account.create'));
        $res->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'   => $adminUser->id,
            'name' => 'ConvertedToRework',
            'role' => 'Rework',
            'pin'  => '654321',
        ]);

        // 9. Update akun Rework (nama saja, tanpa ganti PIN) -> Berhasil karena PIN lama sudah 6 digit angka
        $res = $this->withSession($adminSession)->put("/add-account/{$adminUser->id}", [
            'name' => 'ConvertedToReworkRenamed',
            'role' => 'Rework',
            'pin'  => '',
        ]);
        $res->assertRedirect(route('account.create'));
        $res->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id'   => $adminUser->id,
            'name' => 'ConvertedToReworkRenamed',
            'role' => 'Rework',
            'pin'  => '654321',
        ]);
    }
}
