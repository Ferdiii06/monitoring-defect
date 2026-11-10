<?php

namespace Tests\Feature;

use App\Models\KanbanComponent;
use App\Support\KanbanCodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminKanbanFormatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        KanbanCodeParser::clearCache();
    }

    protected function tearDown(): void
    {
        KanbanCodeParser::clearCache();
        parent::tearDown();
    }

    private function actingAsAdmin()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator',
        ]);
    }

    private function actingAsOperator()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Operator 1',
            'user_role' => 'User',
        ]);
    }

    public function test_guest_cannot_access_kanban_format(): void
    {
        $response = $this->get('/master/kanban-format');
        $response->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_kanban_format(): void
    {
        $response = $this->actingAsOperator()->get('/master/kanban-format');
        $response->assertRedirect(route('operator.home'));
    }

    public function test_admin_can_access_kanban_format_and_sees_default_components(): void
    {
        $response = $this->actingAsAdmin()->get('/master/kanban-format');

        $response->assertStatus(200);
        $response->assertSee('Master Format Kanban');
        $response->assertSee('Nomor Assy');
        $response->assertSee('Konveyor');
        $response->assertSee('Serial Number');
        $response->assertSee('82111-VU020.C7.010');

        $this->assertEquals(3, KanbanComponent::count());
    }

    public function test_valid_reorder_updates_sequence_and_delimiter_in_transaction(): void
    {
        // Setup initial default
        $this->actingAsAdmin()->get('/master/kanban-format');

        $payload = [
            'components' => [
                [
                    'component_key' => 'conveyor',
                    'sequence'      => 1,
                    'delimiter'     => ' ',
                ],
                [
                    'component_key' => 'assy_number',
                    'sequence'      => 2,
                    'delimiter'     => '.',
                ],
                [
                    'component_key' => 'serial',
                    'sequence'      => 3,
                    'delimiter'     => '.',
                ],
            ],
        ];

        $response = $this->actingAsAdmin()->put('/master/kanban-format', $payload);

        $response->assertRedirect(route('admin.master.kanban_format.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kanban_components', [
            'component_key' => 'conveyor',
            'sequence'      => 1,
            'delimiter'     => ' ',
        ]);

        $this->assertDatabaseHas('kanban_components', [
            'component_key' => 'assy_number',
            'sequence'      => 2,
            'delimiter'     => '.',
        ]);

        $this->assertDatabaseHas('kanban_components', [
            'component_key' => 'serial',
            'sequence'      => 3,
            'delimiter'     => '.',
        ]);
    }

    /**
     * Requirement 1: Delimiter '-' harus dihapus dan ditolak saat validasi.
     */
    public function test_delimiter_dash_is_rejected_by_validation(): void
    {
        $this->actingAsAdmin()->get('/master/kanban-format');

        $payload = [
            'components' => [
                ['component_key' => 'assy_number', 'sequence' => 1, 'delimiter' => '-'],
                ['component_key' => 'conveyor',    'sequence' => 2, 'delimiter' => '.'],
                ['component_key' => 'serial',      'sequence' => 3, 'delimiter' => '.'],
            ],
        ];

        $response = $this->actingAsAdmin()->put('/master/kanban-format', $payload);
        $response->assertSessionHasErrors(['components.0.delimiter']);
    }

    public function test_duplicate_sequence_is_rejected(): void
    {
        $this->actingAsAdmin()->get('/master/kanban-format');

        $payload = [
            'components' => [
                ['component_key' => 'assy_number', 'sequence' => 1, 'delimiter' => '.'],
                ['component_key' => 'conveyor',    'sequence' => 1, 'delimiter' => '.'], // DUPLICATE sequence
                ['component_key' => 'serial',      'sequence' => 2, 'delimiter' => '.'],
            ],
        ];

        $response = $this->actingAsAdmin()->put('/master/kanban-format', $payload);

        $response->assertSessionHasErrors(['components']);

        // Data tidak berubah
        $this->assertDatabaseHas('kanban_components', [
            'component_key' => 'conveyor',
            'sequence'      => 2,
        ]);
    }

    public function test_missing_component_is_rejected(): void
    {
        $this->actingAsAdmin()->get('/master/kanban-format');

        $payload = [
            'components' => [
                ['component_key' => 'assy_number', 'sequence' => 1, 'delimiter' => '.'],
                ['component_key' => 'conveyor',    'sequence' => 2, 'delimiter' => '.'],
            ],
        ];

        $response = $this->actingAsAdmin()->put('/master/kanban-format', $payload);

        $response->assertSessionHasErrors(['components']);
    }

    public function test_admin_can_reset_format_to_default(): void
    {
        // Ubah dulu ke custom
        KanbanComponent::updateOrCreate(['component_key' => 'conveyor'], ['label' => 'Konveyor', 'sequence' => 1, 'delimiter' => ' ']);
        KanbanComponent::updateOrCreate(['component_key' => 'assy_number'], ['label' => 'Nomor Assy', 'sequence' => 2, 'delimiter' => '.']);
        KanbanComponent::updateOrCreate(['component_key' => 'serial'], ['label' => 'Serial Number', 'sequence' => 3, 'delimiter' => '.']);

        $response = $this->actingAsAdmin()->post('/master/kanban-format/reset');
        $response->assertRedirect(route('admin.master.kanban_format.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kanban_components', [
            'component_key' => 'assy_number',
            'sequence'      => 1,
            'delimiter'     => '.',
        ]);
        $this->assertDatabaseHas('kanban_components', [
            'component_key' => 'conveyor',
            'sequence'      => 2,
            'delimiter'     => '.',
        ]);
    }

    public function test_parser_with_default_order(): void
    {
        // Seed default: 1=assy_number, 2=conveyor, 3=serial, delimiter='.'
        (new \Database\Seeders\KanbanComponentSeeder())->run();
        KanbanCodeParser::clearCache();

        $result = KanbanCodeParser::parseKanban('82111-VU020.C7.010');

        $this->assertNotNull($result);
        $this->assertEquals('82111-VU020', $result['assy_number']);
        $this->assertEquals('C7', $result['conveyor']);
        $this->assertEquals('010', $result['serial']);
    }

    public function test_parser_with_new_order(): void
    {
        // Urutan baru: 1=conveyor, 2=assy_number, 3=serial, delimiter=' ' (spasi) dan '.' (titik)
        KanbanComponent::updateOrCreate(
            ['component_key' => 'conveyor'],
            ['label' => 'Konveyor', 'sequence' => 1, 'delimiter' => ' ']
        );
        KanbanComponent::updateOrCreate(
            ['component_key' => 'assy_number'],
            ['label' => 'Nomor Assy', 'sequence' => 2, 'delimiter' => '.']
        );
        KanbanComponent::updateOrCreate(
            ['component_key' => 'serial'],
            ['label' => 'Serial Number', 'sequence' => 3, 'delimiter' => '.']
        );
        KanbanCodeParser::clearCache();

        $input = 'C7 82111-VU020.010';
        $result = KanbanCodeParser::parseKanban($input);

        $this->assertNotNull($result);
        $this->assertEquals('82111-VU020', $result['assy_number']);
        $this->assertEquals('C7', $result['conveyor']);
        $this->assertEquals('010', $result['serial']);
    }

    public function test_parser_fallback_for_old_string_when_order_changed(): void
    {
        // Urutan baru: 1=serial, 2=conveyor, 3=assy_number
        KanbanComponent::updateOrCreate(
            ['component_key' => 'serial'],
            ['label' => 'Serial Number', 'sequence' => 1, 'delimiter' => '.']
        );
        KanbanComponent::updateOrCreate(
            ['component_key' => 'conveyor'],
            ['label' => 'Konveyor', 'sequence' => 2, 'delimiter' => '.']
        );
        KanbanComponent::updateOrCreate(
            ['component_key' => 'assy_number'],
            ['label' => 'Nomor Assy', 'sequence' => 3, 'delimiter' => '.']
        );
        KanbanCodeParser::clearCache();

        // String berformat LAMA (assy.conveyor.serial):
        $oldString = '82111-VU020.C7.010';

        // Pola dinamis akan gagal karena menunggu serial di depan,
        // namun FALLBACK KANBAN_PATTERN lama harus berhasil mem-parse-nya!
        $result = KanbanCodeParser::parseKanban($oldString);

        $this->assertNotNull($result);
        $this->assertEquals('82111-VU020', $result['assy_number']);
        $this->assertEquals('C7', $result['conveyor']);
        $this->assertEquals('010', $result['serial']);
    }

    /**
     * Requirement 4:
     * String lama tidak salah ter-parse oleh urutan baru (tidak terjadi pertukaran komponen).
     */
    public function test_old_string_is_not_misparsed_by_new_order(): void
    {
        // Urutan baru: 1=conveyor, 2=assy_number, 3=serial, delimiter='.'
        KanbanComponent::updateOrCreate(
            ['component_key' => 'conveyor'],
            ['label' => 'Konveyor', 'sequence' => 1, 'delimiter' => '.']
        );
        KanbanComponent::updateOrCreate(
            ['component_key' => 'assy_number'],
            ['label' => 'Nomor Assy', 'sequence' => 2, 'delimiter' => '.']
        );
        KanbanComponent::updateOrCreate(
            ['component_key' => 'serial'],
            ['label' => 'Serial Number', 'sequence' => 3, 'delimiter' => '.']
        );
        KanbanCodeParser::clearCache();

        // String lama: "82111-VU020.C7.010"
        // Jika format baru memaksakan slot pertama (conveyor) mencocokkan "82111-VU020",
        // itu akan salah karena nomor assy mengandung hyphen '-'.
        // Regex conveyor ([A-Z0-9]+) menolaknya sehingga pola baru gagal,
        // lalu fallback ke KANBAN_PATTERN bawaan.
        $oldString = '82111-VU020.C7.010';
        $result = KanbanCodeParser::parseKanban($oldString);

        $this->assertNotNull($result);
        $this->assertEquals('82111-VU020', $result['assy_number'], 'Nomor assy tidak boleh tertukar dengan conveyor');
        $this->assertEquals('C7', $result['conveyor'], 'Conveyor tidak boleh tertukar dengan assy');
        $this->assertEquals('010', $result['serial']);
    }

    public function test_parser_when_master_is_empty(): void
    {
        // Master kosong sama sekali
        KanbanComponent::query()->delete();
        KanbanCodeParser::clearCache();

        $input = '82111-VU020.C7.010';
        $result = KanbanCodeParser::parseKanban($input);

        $this->assertNotNull($result);
        $this->assertEquals('82111-VU020', $result['assy_number']);
        $this->assertEquals('C7', $result['conveyor']);
        $this->assertEquals('010', $result['serial']);
    }

    /**
     * Requirement 4 (a):
     * Parser tidak error bila tabel kanban_components belum ada.
     */
    public function test_parser_does_not_error_when_kanban_components_table_is_missing(): void
    {
        Schema::dropIfExists('kanban_components');
        KanbanCodeParser::clearCache();

        $input = '82111-VU020.C7.010';
        $result = KanbanCodeParser::parseKanban($input);

        $this->assertNotNull($result);
        $this->assertEquals('82111-VU020', $result['assy_number']);
        $this->assertEquals('C7', $result['conveyor']);
        $this->assertEquals('010', $result['serial']);
    }

    /**
     * Requirement 4 (b):
     * Cache aman lintas proses (menggunakan Cache::remember dan Cache::forget).
     */
    public function test_parser_cache_persists_in_laravel_cache_and_cleared_safely(): void
    {
        (new \Database\Seeders\KanbanComponentSeeder())->run();
        KanbanCodeParser::clearCache();

        $this->assertFalse(Cache::has(KanbanCodeParser::CACHE_KEY));

        $pattern = KanbanCodeParser::getDynamicPattern();
        $this->assertNotNull($pattern);
        $this->assertTrue(Cache::has(KanbanCodeParser::CACHE_KEY));

        KanbanCodeParser::clearCache();
        $this->assertFalse(Cache::has(KanbanCodeParser::CACHE_KEY));
    }
}
