<?php

namespace Tests\Feature;

use App\Exports\NameplateSettingsExport;
use App\Exports\NameplateSettingsTemplateExport;
use App\Models\NameplateSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminNameplateSettingTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_guest_cannot_access_nameplate_settings(): void
    {
        $response = $this->get('/master/nameplate-settings');
        $response->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_nameplate_settings(): void
    {
        $response = $this->actingAsOperator()->get('/master/nameplate-settings');
        $response->assertRedirect(route('operator.home'));
    }

    public function test_admin_can_access_nameplate_settings_index_and_see_empty_state(): void
    {
        $response = $this->actingAsAdmin()->get('/master/nameplate-settings');

        $response->assertStatus(200);
        $response->assertSee('Nameplate Setting (Carline)');
        $response->assertSee('Tidak ada data nameplate yang ditemukan.');
        $response->assertSee('New');
        $response->assertSee('Download Excel');
        $response->assertSee('Upload by Excel');
    }

    public function test_admin_can_access_nameplate_settings_index_with_data(): void
    {
        $setting = NameplateSetting::factory()->create([
            'carline'       => 'LINE-01',
            'assy_customer' => true,
            'name'          => 'Front Harness Line 1',
            'set'           => 'CARTON BOX',
            'date_format'   => 'YYMMDD',
        ]);

        $response = $this->actingAsAdmin()->get('/master/nameplate-settings');

        $response->assertStatus(200);
        $response->assertSee('LINE-01');
        $response->assertSee('Assy Cust');
        $response->assertSee('Front Harness Line 1');
        $response->assertSee('CARTON BOX');
        $response->assertSee('YYMMDD');
    }

    public function test_admin_can_create_nameplate_setting_with_all_fields_and_auto_uppercase(): void
    {
        $response = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'        => '  line-alpha  ',
            'assy_customer'  => '1',
            'name'           => 'Line Alpha Setting',
            'set'            => 'CARTON BOX',
            'date_format'    => 'yymmdd',
            'assy_no'        => '1-8',
            'suffix'         => '9-10',
            'level'          => '11',
            'date_field'     => '12-17',
            'sequence_label' => '18-21',
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nameplate_settings', [
            'carline'        => 'LINE-ALPHA',
            'assy_customer'  => 1,
            'name'           => 'Line Alpha Setting',
            'set'            => 'CARTON BOX',
            'date_format'    => 'YYMMDD',
            'assy_no'        => '1-8',
            'suffix'         => '9-10',
            'level'          => '11',
            'date_field'     => '12-17',
            'sequence_label' => '18-21',
        ]);
    }

    public function test_create_validation_rejects_missing_and_invalid_date_format(): void
    {
        $response = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'     => '',
            'name'        => '',
            'set'         => 'INVALID_SET',
            'date_format' => 'INVALID_XYZ',
        ]);

        $response->assertSessionHasErrors([
            'carline'     => 'Kolom Carline Code wajib diisi.',
            'name'        => 'Kolom Name wajib diisi.',
            'set'         => 'Pilihan Set tidak valid.',
            'date_format' => 'Format Date Format hanya boleh berisi kombinasi karakter Y, M, dan D (contoh: YYMMDD).',
        ]);
    }

    public function test_date_format_accepts_valid_ymd_text(): void
    {
        $response = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'     => 'LINE-VALID-DATE',
            'name'        => 'Line Custom Date Format',
            'set'         => 'POLYTAINER',
            'date_format' => 'yyyyMMdd',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('nameplate_settings', [
            'carline'     => 'LINE-VALID-DATE',
            'date_format' => 'YYYYMMDD',
        ]);
    }

    public function test_duplicate_carline_is_rejected_on_create(): void
    {
        NameplateSetting::factory()->create(['carline' => 'LINE-01']);

        $response = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'     => 'line-01',
            'name'        => 'Another Line 01',
            'set'         => 'POLYTAINER',
            'date_format' => 'YYYYMMDD',
        ]);

        $response->assertSessionHasErrors(['carline']);
        $this->assertEquals(1, NameplateSetting::where('carline', 'LINE-01')->count());
    }

    public function test_admin_can_update_nameplate_setting_with_new_fields(): void
    {
        $setting = NameplateSetting::factory()->create([
            'carline'       => 'LINE-02',
            'assy_customer' => false,
            'name'          => 'Old Line 2',
            'set'           => 'CARTON BOX',
            'date_format'   => 'YYMMDD',
        ]);

        $response = $this->actingAsAdmin()->put('/master/nameplate-settings/' . $setting->id, [
            'carline'        => 'LINE-02',
            'assy_customer'  => '1',
            'name'           => 'Updated Line 2',
            'set'            => 'POLYTAINER',
            'date_format'    => 'YYYYMMDD',
            'assy_no'        => '1-10',
            'suffix'         => '11',
            'level'          => '12-13',
            'date_field'     => '14-21',
            'sequence_label' => '22-25',
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('success');

        $setting->refresh();
        $this->assertTrue($setting->assy_customer);
        $this->assertEquals('Updated Line 2', $setting->name);
        $this->assertEquals('POLYTAINER', $setting->set);
        $this->assertEquals('YYYYMMDD', $setting->date_format);
        $this->assertEquals('1-10', $setting->assy_no);
        $this->assertEquals('11', $setting->suffix);
        $this->assertEquals('12-13', $setting->level);
        $this->assertEquals('14-21', $setting->date_field);
        $this->assertEquals('22-25', $setting->sequence_label);
    }

    public function test_update_duplicate_carline_rejected_for_different_record(): void
    {
        NameplateSetting::factory()->create(['carline' => 'LINE-01']);
        $setting2 = NameplateSetting::factory()->create(['carline' => 'LINE-02']);

        $response = $this->actingAsAdmin()->put('/master/nameplate-settings/' . $setting2->id, [
            'carline'     => 'LINE-01',
            'name'        => 'Line 02 Renamed to 01',
            'set'         => 'CARTON BOX',
            'date_format' => 'YYMMDD',
        ]);

        $response->assertSessionHasErrors(['carline']);
    }

    public function test_search_filter_works_across_fields(): void
    {
        NameplateSetting::factory()->create(['carline' => 'ALFA-01', 'name' => 'Line Main']);
        NameplateSetting::factory()->create(['carline' => 'BETA-02', 'name' => 'Secondary Special']);

        $res1 = $this->actingAsAdmin()->get('/master/nameplate-settings?search=Special');
        $res1->assertStatus(200);
        $res1->assertSee('BETA-02');
        $res1->assertDontSee('ALFA-01');

        $res2 = $this->actingAsAdmin()->get('/master/nameplate-settings?search=ALFA');
        $res2->assertStatus(200);
        $res2->assertSee('ALFA-01');
        $res2->assertDontSee('BETA-02');
    }

    public function test_sort_whitelist_and_order(): void
    {
        NameplateSetting::factory()->create(['carline' => 'LINE-A']);
        NameplateSetting::factory()->create(['carline' => 'LINE-Z']);

        $responseAsc = $this->actingAsAdmin()->get('/master/nameplate-settings?sort_by=carline&order=asc');
        $responseAsc->assertStatus(200);
        $contentAsc = $responseAsc->getContent();
        $this->assertTrue(strpos($contentAsc, 'LINE-A') < strpos($contentAsc, 'LINE-Z'));

        $responseDesc = $this->actingAsAdmin()->get('/master/nameplate-settings?sort_by=carline&order=desc');
        $responseDesc->assertStatus(200);
        $contentDesc = $responseDesc->getContent();
        $this->assertTrue(strpos($contentDesc, 'LINE-Z') < strpos($contentDesc, 'LINE-A'));
    }

    public function test_export_excel_structure_and_headers(): void
    {
        NameplateSetting::factory()->create([
            'carline'        => 'EXP-01',
            'assy_customer'  => true,
            'name'           => 'Export Line 1',
            'set'            => 'CARTON BOX',
            'date_format'    => 'YYMMDD',
            'assy_no'        => '1-8',
            'suffix'         => '9-10',
            'level'          => '11',
            'date_field'     => '12-17',
            'sequence_label' => '18-21',
        ]);

        $export = new NameplateSettingsExport();
        $raw = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        $temp = tempnam(sys_get_temp_dir(), 'exp_test_') . '.xlsx';
        file_put_contents($temp, $raw);

        $spreadsheet = IOFactory::load($temp);
        $sheet = $spreadsheet->getSheetByName('Sheet1');
        $this->assertNotNull($sheet, 'Sheet1 harus ada di file export.');

        // Baris 1: Judul "Nameplate Setting" & merge A1:D1
        $this->assertEquals('Nameplate Setting', $sheet->getCell('A1')->getValue());
        $this->assertArrayHasKey('A1:D1', $sheet->getMergeCells());

        // Baris 2: Header 4 kolom persis
        $this->assertEquals('Carline', $sheet->getCell('A2')->getValue());
        $this->assertEquals('Name', $sheet->getCell('B2')->getValue());
        $this->assertEquals('Set', $sheet->getCell('C2')->getValue());
        $this->assertEquals('Date Format', $sheet->getCell('D2')->getValue());
        $this->assertNull($sheet->getCell('E2')->getValue(), 'Hanya boleh 4 kolom pada baris header.');

        // Baris 3: Data
        $this->assertEquals('EXP-01', $sheet->getCell('A3')->getValue());
        $this->assertEquals('Export Line 1', $sheet->getCell('B3')->getValue());
        $this->assertEquals('CARTON BOX', $sheet->getCell('C3')->getValue());
        $this->assertEquals('YYMMDD', $sheet->getCell('D3')->getValue());
        $this->assertNull($sheet->getCell('E3')->getValue(), 'Hanya boleh 4 kolom pada baris data.');

        @unlink($temp);
    }

    public function test_export_excel_when_empty_still_has_title_and_header_rows(): void
    {
        NameplateSetting::query()->delete();

        $export = new NameplateSettingsExport();
        $raw = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        $temp = tempnam(sys_get_temp_dir(), 'exp_empty_') . '.xlsx';
        file_put_contents($temp, $raw);

        $spreadsheet = IOFactory::load($temp);
        $sheet = $spreadsheet->getSheetByName('Sheet1');
        $this->assertNotNull($sheet);

        $this->assertEquals('Nameplate Setting', $sheet->getCell('A1')->getValue());
        $this->assertArrayHasKey('A1:D1', $sheet->getMergeCells());
        $this->assertEquals('Carline', $sheet->getCell('A2')->getValue());
        $this->assertEquals('Name', $sheet->getCell('B2')->getValue());
        $this->assertEquals('Set', $sheet->getCell('C2')->getValue());
        $this->assertEquals('Date Format', $sheet->getCell('D2')->getValue());
        $this->assertNull($sheet->getCell('A3')->getValue(), 'Baris ke-3 harus kosong bila tidak ada data.');

        @unlink($temp);
    }

    public function test_export_excel_follows_search_and_sort(): void
    {
        NameplateSetting::query()->delete();
        NameplateSetting::factory()->create(['carline' => 'LINE-A', 'name' => 'Alpha Line']);
        NameplateSetting::factory()->create(['carline' => 'LINE-Z', 'name' => 'Zeta Line']);

        // Search filter test
        $exportSearch = new NameplateSettingsExport('Zeta');
        $rawSearch = Excel::raw($exportSearch, \Maatwebsite\Excel\Excel::XLSX);
        $temp1 = tempnam(sys_get_temp_dir(), 'exp_s_') . '.xlsx';
        file_put_contents($temp1, $rawSearch);
        $sheet1 = IOFactory::load($temp1)->getSheetByName('Sheet1');

        $this->assertEquals('LINE-Z', $sheet1->getCell('A3')->getValue());
        $this->assertNull($sheet1->getCell('A4')->getValue());
        @unlink($temp1);

        // Sort desc test
        $exportSort = new NameplateSettingsExport(null, 'carline', 'desc');
        $rawSort = Excel::raw($exportSort, \Maatwebsite\Excel\Excel::XLSX);
        $temp2 = tempnam(sys_get_temp_dir(), 'exp_sort_') . '.xlsx';
        file_put_contents($temp2, $rawSort);
        $sheet2 = IOFactory::load($temp2)->getSheetByName('Sheet1');

        $this->assertEquals('LINE-Z', $sheet2->getCell('A3')->getValue());
        $this->assertEquals('LINE-A', $sheet2->getCell('A4')->getValue());
        @unlink($temp2);
    }

    public function test_export_endpoint_returns_attachment_filename(): void
    {
        $response = $this->actingAsAdmin()->get('/master/nameplate-settings/export');
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $expectedName = 'nameplate_setting_' . date('Ymd') . '.xlsx';
        $this->assertStringContainsString($expectedName, $response->headers->get('content-disposition'));
    }

    public function test_template_download_structure(): void
    {
        $template = new NameplateSettingsTemplateExport();
        $raw = Excel::raw($template, \Maatwebsite\Excel\Excel::XLSX);

        $temp = tempnam(sys_get_temp_dir(), 'tmpl_') . '.xlsx';
        file_put_contents($temp, $raw);

        $spreadsheet = IOFactory::load($temp);
        $sheet = $spreadsheet->getSheetByName('Sheet1');
        $this->assertNotNull($sheet);

        $this->assertEquals('Nameplate Setting', $sheet->getCell('A1')->getValue());
        $this->assertArrayHasKey('A1:D1', $sheet->getMergeCells());
        $this->assertEquals('Carline', $sheet->getCell('A2')->getValue());
        $this->assertEquals('Name', $sheet->getCell('B2')->getValue());
        $this->assertEquals('Set', $sheet->getCell('C2')->getValue());
        $this->assertEquals('Date Format', $sheet->getCell('D2')->getValue());
        $this->assertNull($sheet->getCell('E2')->getValue());

        // Baris 3: Contoh data
        $this->assertEquals('CONTOH-CARLINE-1', $sheet->getCell('A3')->getValue());
        $this->assertEquals('Contoh Nameplate Line 1', $sheet->getCell('B3')->getValue());
        $this->assertEquals('CARTON BOX', $sheet->getCell('C3')->getValue());
        $this->assertEquals('YYMMDD', $sheet->getCell('D3')->getValue());
        $this->assertNull($sheet->getCell('E3')->getValue());

        @unlink($temp);
    }

    public function test_import_new_format_with_title_row1_and_header_row2(): void
    {
        $csv = "Nameplate Setting,,,\n"
             . "Carline,Name,Set,Date Format\n"
             . "LINE-NEW-FMT,Line New Format,CARTON BOX,YYMMDD\n";

        $file = UploadedFile::fake()->createWithContent('new_format.csv', $csv);

        $response = $this->actingAsAdmin()->post('/master/nameplate-settings/import', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nameplate_settings', [
            'carline'     => 'LINE-NEW-FMT',
            'name'        => 'Line New Format',
            'set'         => 'CARTON BOX',
            'date_format' => 'YYMMDD',
        ]);
    }

    public function test_import_old_format_with_header_row1(): void
    {
        $csv = "Carline,Name,Set,Date Format\n"
             . "LINE-OLD-FMT,Line Old Format,POLYTAINER,YYYYMMDD\n";

        $file = UploadedFile::fake()->createWithContent('old_format.csv', $csv);

        $response = $this->actingAsAdmin()->post('/master/nameplate-settings/import', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nameplate_settings', [
            'carline'     => 'LINE-OLD-FMT',
            'name'        => 'Line Old Format',
            'set'         => 'POLYTAINER',
            'date_format' => 'YYYYMMDD',
        ]);
    }

    public function test_import_round_trip_does_not_clear_optional_columns(): void
    {
        // 1. Buat record dengan seluruh kolom opsional terisi format baru
        NameplateSetting::factory()->create([
            'carline'        => 'LINE-ROUNDTRIP',
            'assy_customer'  => true,
            'name'           => 'Initial Name',
            'set'            => 'CARTON BOX',
            'date_format'    => 'YYMMDD',
            'assy_no'        => '1-8',
            'suffix'         => '9-10',
            'level'          => '11',
            'date_field'     => '12-17',
            'sequence_label' => '18-21',
        ]);

        // 2. Ekspor ke XLSX (format baru 4 kolom: Carline, Name, Set, Date Format)
        $rawXlsx = Excel::raw(new NameplateSettingsExport('LINE-ROUNDTRIP'), \Maatwebsite\Excel\Excel::XLSX);

        // 3. Impor kembali berkas XLSX tersebut
        $file = UploadedFile::fake()->createWithContent('roundtrip.xlsx', $rawXlsx);

        $response = $this->actingAsAdmin()->post('/master/nameplate-settings/import', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('success');

        // 4. Verifikasi bahwa kolom opsional TIDAK dikosongkan (tetap utuh)
        $setting = NameplateSetting::where('carline', 'LINE-ROUNDTRIP')->firstOrFail();
        $this->assertEquals('Initial Name', $setting->name);
        $this->assertEquals('CARTON BOX', $setting->set);
        $this->assertEquals('YYMMDD', $setting->date_format);
        $this->assertTrue($setting->assy_customer, 'assy_customer tidak boleh dikosongkan.');
        $this->assertEquals('1-8', $setting->assy_no, 'assy_no tidak boleh dikosongkan.');
        $this->assertEquals('9-10', $setting->suffix, 'suffix tidak boleh dikosongkan.');
        $this->assertEquals('11', $setting->level, 'level tidak boleh dikosongkan.');
        $this->assertEquals('12-17', $setting->date_field, 'date_field tidak boleh dikosongkan.');
        $this->assertEquals('18-21', $setting->sequence_label, 'sequence_label tidak boleh dikosongkan.');
    }

    public function test_import_reports_exact_excel_row_numbers_in_error_summary(): void
    {
        // Format baru:
        // Baris 1: Judul
        // Baris 2: Header
        // Baris 3: Error (Carline kosong)
        // Baris 4: Valid
        // Baris 5: Error (Set tidak valid)
        $csv = "Nameplate Setting,,,\n"
             . "Carline,Name,Set,Date Format\n"
             . ",Empty Carline,CARTON BOX,YYMMDD\n"
             . "LINE-VALID-ROW,Good Row,POLYTAINER,YYMMDD\n"
             . "LINE-BAD-ROW,Bad Set,INVALID_SET,YYMMDD\n";

        $file = UploadedFile::fake()->createWithContent('row_test.csv', $csv);

        $response = $this->actingAsAdmin()->post('/master/nameplate-settings/import', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('nameplate_setting_import_summary');

        $summary = session('nameplate_setting_import_summary');
        $this->assertEquals(1, $summary['added']);
        $this->assertEquals(2, $summary['failed']);

        $errorRows = array_column($summary['errors'], 'row');
        $this->assertContains(3, $errorRows, 'Nomor baris error harus mengacu ke baris 3 Excel asli.');
        $this->assertContains(5, $errorRows, 'Nomor baris error harus mengacu ke baris 5 Excel asli.');

        $this->assertDatabaseHas('nameplate_settings', [
            'carline' => 'LINE-VALID-ROW',
        ]);
    }

    public function test_import_with_optional_columns_updates_them_when_present(): void
    {
        NameplateSetting::factory()->create([
            'carline'       => 'LINE-OPT',
            'assy_customer' => false,
            'name'          => 'Old Name',
            'set'           => 'CARTON BOX',
            'date_format'   => 'YYMMDD',
        ]);

        $csv = "Carline,Name,Set,Date Format,Assy Customer,Assy No.,Suffix,Level,Date,Sequence Label\n"
             . "LINE-OPT,Updated Name,POLYTAINER,YYYYMMDD,yes,\"1-10\",\"11\",\"12-13\",\"14-21\",\"22-25\"\n";

        $file = UploadedFile::fake()->createWithContent('optional.csv', $csv);

        $response = $this->actingAsAdmin()->post('/master/nameplate-settings/import', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));

        $this->assertDatabaseHas('nameplate_settings', [
            'carline'        => 'LINE-OPT',
            'assy_customer'  => 1,
            'name'           => 'Updated Name',
            'set'            => 'POLYTAINER',
            'date_format'    => 'YYYYMMDD',
            'assy_no'        => '1-10',
            'suffix'         => '11',
            'level'          => '12-13',
            'date_field'     => '14-21',
            'sequence_label' => '22-25',
        ]);
    }

    public function test_test_scan_rejects_guest_and_operator(): void
    {
        $payload = ['barcode' => '*50060938012603090001*'];

        $guestRes = $this->post('/master/nameplate-settings/test-scan', $payload);
        $guestRes->assertRedirect(route('login'));

        $opRes = $this->actingAsOperator()->post('/master/nameplate-settings/test-scan', $payload);
        $opRes->assertRedirect(route('operator.home'));
    }

    public function test_test_scan_extracts_barcode_segments_and_does_not_persist_to_db(): void
    {
        $initialCount = NameplateSetting::count();

        $barcode = '*50060938012603090001*';
        $payload = [
            'barcode'        => $barcode,
            'carline'        => 'LINE-TEST',
            'date_format'    => 'YYMMDD',
            'assy_no'        => '1-8',
            'suffix'         => '9-10',
            'level'          => '',
            'date_field'     => '11-16',
            'sequence_label' => '17-20',
        ];

        $response = $this->actingAsAdmin()->postJson('/master/nameplate-settings/test-scan', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success'       => true,
            'barcode_clean' => '50060938012603090001',
            'data'          => [
                'assy_no'        => '50060938',
                'suffix'         => '01',
                'level'          => '-',
                'date'           => '2026-03-09 (Raw: 260309)',
                'sequence_label' => '0001',
            ],
        ]);

        $this->assertEquals($initialCount, NameplateSetting::count());
    }

    public function test_test_scan_returns_error_for_empty_barcode_and_does_not_persist(): void
    {
        $initialCount = NameplateSetting::count();

        $response = $this->actingAsAdmin()->postJson('/master/nameplate-settings/test-scan', [
            'barcode' => '   ',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Barcode tidak boleh kosong.',
        ]);

        $this->assertEquals($initialCount, NameplateSetting::count());
    }

    /**
     * Test validasi: nilai invalid ditolak (huruf, A>B, 0, format lama "1,8", "1:8").
     * Nilai valid (A-B dan N) diterima.
     */
    public function test_validation_rejects_invalid_digit_range_formats_on_create_and_update(): void
    {
        $expectedError = "Format harus 'Digit ke' awal-akhir, mis. 1-8, atau satu digit, mis. 9.";

        // 1. Format lama "1,8" ditolak
        $res1 = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'     => 'LINE-INV-1',
            'name'        => 'Line Invalid 1',
            'set'         => 'CARTON BOX',
            'date_format' => 'YYMMDD',
            'assy_no'     => '1,8',
        ]);
        $res1->assertSessionHasErrors(['assy_no' => $expectedError]);

        // 2. Format lama "1:8" ditolak
        $res2 = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'     => 'LINE-INV-2',
            'name'        => 'Line Invalid 2',
            'set'         => 'CARTON BOX',
            'date_format' => 'YYMMDD',
            'suffix'      => '1:8',
        ]);
        $res2->assertSessionHasErrors(['suffix' => $expectedError]);

        // 3. Huruf / teks non-digit ditolak
        $res3 = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'     => 'LINE-INV-3',
            'name'        => 'Line Invalid 3',
            'set'         => 'CARTON BOX',
            'date_format' => 'YYMMDD',
            'level'       => 'abc',
        ]);
        $res3->assertSessionHasErrors(['level' => $expectedError]);

        // 4. Rentang terbalik A > B (mis. 8-1) ditolak
        $res4 = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'     => 'LINE-INV-4',
            'name'        => 'Line Invalid 4',
            'set'         => 'CARTON BOX',
            'date_format' => 'YYMMDD',
            'assy_no'     => '8-1',
        ]);
        $res4->assertSessionHasErrors(['assy_no' => $expectedError]);

        // 5. Digit 0 ditolak (hanya angka bulat positif >= 1)
        $res5 = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'     => 'LINE-INV-5',
            'name'        => 'Line Invalid 5',
            'set'         => 'CARTON BOX',
            'date_format' => 'YYMMDD',
            'assy_no'     => '0',
            'suffix'      => '0-5',
        ]);
        $res5->assertSessionHasErrors(['assy_no' => $expectedError, 'suffix' => $expectedError]);

        // 6. Nilai valid format satu digit N dan rentang A-B diterima
        $resValid = $this->actingAsAdmin()->post('/master/nameplate-settings', [
            'carline'        => 'LINE-VAL-1',
            'name'           => 'Line Valid 1',
            'set'            => 'CARTON BOX',
            'date_format'    => 'YYMMDD',
            'assy_no'        => '1-8',
            'suffix'         => '9',
            'level'          => '10-10',
            'date_field'     => '11 - 16', // spasi dinormalisasi
            'sequence_label' => '17-20',
        ]);
        $resValid->assertSessionHasNoErrors();
        $this->assertDatabaseHas('nameplate_settings', [
            'carline'        => 'LINE-VAL-1',
            'assy_no'        => '1-8',
            'suffix'         => '9',
            'level'          => '10-10',
            'date_field'     => '11-16',
            'sequence_label' => '17-20',
        ]);

        // 7. Update dengan format invalid juga ditolak
        $setting = NameplateSetting::where('carline', 'LINE-VAL-1')->firstOrFail();
        $resUpdate = $this->actingAsAdmin()->put('/master/nameplate-settings/' . $setting->id, [
            'carline'     => 'LINE-VAL-1',
            'name'        => 'Line Valid 1 Updated',
            'set'         => 'CARTON BOX',
            'date_format' => 'YYMMDD',
            'assy_no'     => '1,8', // format lama pada update
        ]);
        $resUpdate->assertSessionHasErrors(['assy_no' => $expectedError]);
    }

    /**
     * Test Scan dengan format baru:
     * - Hasil cocok (ekstraksi segmen sukses)
     * - Barcode terlalu pendek (pesan error jelas, bukan 500)
     * - Nilai aturan tidak valid (pesan error jelas, bukan 500)
     */
    public function test_test_scan_scenarios_with_new_format(): void
    {
        // Skenario A: Hasil cocok (rentang A-B dan satu digit N)
        $resMatch = $this->actingAsAdmin()->postJson('/master/nameplate-settings/test-scan', [
            'barcode'        => '50060938012603090001',
            'assy_no'        => '1-8',
            'suffix'         => '9-10',
            'level'          => '',
            'date_field'     => '11-16',
            'date_format'    => 'YYMMDD',
            'sequence_label' => '17-20',
        ]);
        $resMatch->assertStatus(200);
        $resMatch->assertJson([
            'success' => true,
            'data'    => [
                'assy_no'        => '50060938',
                'suffix'         => '01',
                'level'          => '-',
                'date'           => '2026-03-09 (Raw: 260309)',
                'sequence_label' => '0001',
            ],
        ]);

        // Skenario B: Barcode terlalu pendek (panjang 8 digit, aturan membutuhkan hingga digit ke-20)
        $resShort = $this->actingAsAdmin()->postJson('/master/nameplate-settings/test-scan', [
            'barcode'        => '50060938',
            'assy_no'        => '1-8',
            'suffix'         => '9-10',
            'sequence_label' => '17-20',
        ]);
        $resShort->assertStatus(422);
        $resShort->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Barcode terlalu pendek', $resShort->json('message'));

        // Skenario C: Nilai aturan tidak valid format lama "1,8" (kembalikan pesan error yang jelas, bukan 500)
        $resInvalidOld = $this->actingAsAdmin()->postJson('/master/nameplate-settings/test-scan', [
            'barcode' => '50060938012603090001',
            'assy_no' => '1,8',
        ]);
        $resInvalidOld->assertStatus(422);
        $resInvalidOld->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Format aturan Assy No.', $resInvalidOld->json('message'));
        $this->assertStringContainsString('tidak valid', $resInvalidOld->json('message'));

        // Skenario D: Nilai aturan tidak valid rentang terbalik "8-1"
        $resInvalidRange = $this->actingAsAdmin()->postJson('/master/nameplate-settings/test-scan', [
            'barcode' => '50060938012603090001',
            'assy_no' => '8-1',
        ]);
        $resInvalidRange->assertStatus(422);
        $resInvalidRange->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('tidak valid', $resInvalidRange->json('message'));
    }

    /**
     * Test migrasi data lama:
     * - "posisi,panjang" (1,8 -> 1-8; 9,2 -> 9-10; 11,2 -> 11-12)
     * - "1:8" -> 1-8
     * - "11,1" -> 11
     * - data yang sudah format A-B, N, kosong, atau tidak terbaca dibiarkan apa adanya
     * - bersifat idempotent jika dijalankan berulang
     */
    public function test_migration_converts_legacy_data_to_new_format_idempotently(): void
    {
        // 1. Seed data dengan format legacy
        $row1 = NameplateSetting::factory()->create([
            'carline'        => 'MIG-01',
            'assy_no'        => '1,8',
            'suffix'         => '9,2',
            'level'          => '11,2',
            'date_field'     => '13,6',
            'sequence_label' => '19:4',
        ]);

        $row2 = NameplateSetting::factory()->create([
            'carline'        => 'MIG-02',
            'assy_no'        => '1-8',     // sudah format baru
            'suffix'         => '9',       // satu digit
            'level'          => '11,1',    // length 1 -> 11
            'date_field'     => null,      // kosong
            'sequence_label' => 'INV_TXT', // tidak bisa dibaca
        ]);

        // 2. Jalankan migration class secara terisolasi
        $migration = require database_path('migrations/2026_11_13_000002_convert_nameplate_settings_position_format.php');
        $migration->up();

        // 3. Verifikasi konversi
        $fresh1 = $row1->fresh();
        $this->assertEquals('1-8', $fresh1->assy_no);
        $this->assertEquals('9-10', $fresh1->suffix);
        $this->assertEquals('11-12', $fresh1->level);
        $this->assertEquals('13-18', $fresh1->date_field);
        $this->assertEquals('19-22', $fresh1->sequence_label);

        $fresh2 = $row2->fresh();
        $this->assertEquals('1-8', $fresh2->assy_no, 'Format A-B yang sudah benar tetap dipertahankan.');
        $this->assertEquals('9', $fresh2->suffix, 'Format N satu digit tetap dipertahankan.');
        $this->assertEquals('11', $fresh2->level, 'Length 1 dikonversi menjadi satu digit.');
        $this->assertNull($fresh2->date_field, 'Nilai null tetap null.');
        $this->assertEquals('INV_TXT', $fresh2->sequence_label, 'Nilai tidak terbaca dibiarkan apa adanya.');

        // 4. Verifikasi Idempotensi (jalankan up() kedua kali)
        $migration->up();
        $fresh1AfterSecond = $row1->fresh();
        $fresh2AfterSecond = $row2->fresh();

        $this->assertEquals('1-8', $fresh1AfterSecond->assy_no);
        $this->assertEquals('9-10', $fresh1AfterSecond->suffix);
        $this->assertEquals('11-12', $fresh1AfterSecond->level);
        $this->assertEquals('13-18', $fresh1AfterSecond->date_field);
        $this->assertEquals('19-22', $fresh1AfterSecond->sequence_label);

        $this->assertEquals('1-8', $fresh2AfterSecond->assy_no);
        $this->assertEquals('9', $fresh2AfterSecond->suffix);
        $this->assertEquals('11', $fresh2AfterSecond->level);
    }

    /**
     * Test tampilan view:
     * - Placeholder baru "Contoh: Digit ke X-Y"
     * - Tooltip baru dengan penjelasan "Isi digit ke berapa pada barcode..."
     * - Tidak ada teks format lama "posisi,panjang" atau "1:8" di view
     */
    public function test_view_renders_new_placeholders_and_tooltips(): void
    {
        $response = $this->actingAsAdmin()->get('/master/nameplate-settings');
        $response->assertStatus(200);

        // Placeholders baru
        $response->assertSee('Contoh: Digit ke 1-8');
        $response->assertSee('Contoh: Digit ke 9-10');
        $response->assertSee('Contoh: Digit ke 11-12');
        $response->assertSee('Contoh: Digit ke 11-16');
        $response->assertSee('Contoh: Digit ke 17-20');

        // Tooltip baru
        $response->assertSee('Isi digit ke berapa pada barcode. Contoh: 1-8 artinya digit ke-1 sampai ke-8; 9 artinya hanya digit ke-9.');

        // Format lama tidak lagi muncul di view
        $response->assertDontSee('Contoh: 1,8 atau 1-8');
        $response->assertDontSee('Contoh: 9,2 atau 9-10');
        $response->assertDontSee('Contoh: 11,2');
        $response->assertDontSee('Contoh: 11,6 atau 11-16');
        $response->assertDontSee('Contoh: 17,4 atau 17-20');
        $response->assertDontSee('Aturan posisi dan panjang');
    }

    /**
     * Test import Excel:
     * - Format invalid ditolak dengan error yang tepat
     * - Format valid dengan spasi ("1 - 8") dinormalisasi menjadi "1-8"
     */
    public function test_import_rejects_invalid_digit_range_and_normalizes_valid(): void
    {
        // CSV dengan 1 baris invalid format lama ("1,8") dan 1 baris valid dengan spasi ("1 - 8")
        $csv = "Carline,Name,Set,Date Format,Assy No.,Suffix\n"
             . "LINE-BAD-POS,Line Bad,CARTON BOX,YYMMDD,\"1,8\",\"9-10\"\n"
             . "LINE-GOOD-POS,Line Good,CARTON BOX,YYMMDD,\"1 - 8\",\"9\"\n";

        $file = UploadedFile::fake()->createWithContent('import_pos_test.csv', $csv);

        $response = $this->actingAsAdmin()->post('/master/nameplate-settings/import', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('nameplate_setting_import_summary');

        $summary = session('nameplate_setting_import_summary');
        $this->assertEquals(1, $summary['added']);
        $this->assertEquals(1, $summary['failed']);

        // Baris yang gagal memuat pesan validasi format
        $failReason = $summary['errors'][0]['reason'] ?? '';
        $this->assertStringContainsString("Format Assy No. harus 'Digit ke' awal-akhir, mis. 1-8, atau satu digit, mis. 9.", $failReason);

        // Baris yang berhasil dinormalisasi menjadi "1-8"
        $this->assertDatabaseHas('nameplate_settings', [
            'carline' => 'LINE-GOOD-POS',
            'assy_no' => '1-8',
            'suffix'  => '9',
        ]);
    }
}
