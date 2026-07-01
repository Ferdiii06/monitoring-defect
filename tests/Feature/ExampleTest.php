<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_log_system_page(): void
    {
        $response = $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator'
        ])->get('/log-system');

        $response->assertStatus(200);
        $response->assertSee('Log System');
    }

    public function test_delete_external_defect(): void
    {
        $defect = \App\Models\Defect::create([
            'external_id' => 99,
            'waktu' => now(),
            'user_name' => 'Operator Uji',
            'jenis_assy' => 'Final Assy',
            'line_conveyor' => 'Toyota',
            'konveyor' => '664W-C5',
            'jenis_defect' => 'INSERT CIRCUIT',
            'jenis_sub_defect' => 'CROSS CIRCUIT',
            'quantity' => 1,
        ]);

        $response = $this->postJson('/api/defects/delete-external', [
            'id' => 99
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $this->assertDatabaseMissing('defects', [
            'external_id' => 99
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'jenis_aksi' => 'Delete Report',
            'user_name' => 'Operator Uji'
        ]);
    }

    public function test_get_dashboard_stats(): void
    {
        \App\Models\Defect::create([
            'external_id' => 101,
            'waktu' => now(), // today
            'user_name' => 'Operator A',
            'jenis_assy' => 'Final Assy',
            'line_conveyor' => 'Toyota',
            'konveyor' => '664W-C5',
            'jenis_defect' => 'TERMINAL',
            'jenis_sub_defect' => 'TERGORES',
            'quantity' => 5,
        ]);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'totalDefect',
            'defectToday',
            'activeUsers',
            'totalUsers'
        ]);
    }
}
