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
            'assy_no'        => '1,8',
            'suffix'         => '9,2',
            'level'          => '11,2',
            'date_field'     => '13,6',
            'sequence_label' => '19,4',
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nameplate_settings', [
            'carline'        => 'LINE-ALPHA',
            'assy_customer'  => 1,
            'name'           => 'Line Alpha Setting',
            'set'            => 'CARTON BOX',
            'date_format'    => 'YYMMDD',
            'assy_no'        => '1,8',
            'suffix'         => '9,2',
            'level'          => '11,2',
            'date_field'     => '13,6',
            'sequence_label' => '19,4',
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
            'assy_no'        => '1,10',
            'suffix'         => '11,1',
            'level'          => '12,2',
            'date_field'     => '14,8',
            'sequence_label' => '22,4',
        ]);

        $response->assertRedirect(route('admin.master.nameplate_settings.index'));
        $response->assertSessionHas('success');

        $setting->refresh();
        $this->assertTrue($setting->assy_customer);
        $this->assertEquals('Updated Line 2', $setting->name);
        $this->assertEquals('POLYTAINER', $setting->set);
        $this->assertEquals('YYYYMMDD', $setting->date_format);
        $this->assertEquals('1,10', $setting->assy_no);
        $this->assertEquals('11,1', $setting->suffix);
        $this->assertEquals('12,2', $setting->level);
        $this->assertEquals('14,8', $setting->date_field);
        $this->assertEquals('22,4', $setting->sequence_label);
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
            'assy_no'        => '1,8',
            'suffix'         => '9,2',
            'level'          => '11,2',
            'date_field'     => '13,6',
            'sequence_label' => '19,4',
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
        // 1. Buat record dengan seluruh kolom opsional terisi
        NameplateSetting::factory()->create([
            'carline'        => 'LINE-ROUNDTRIP',
            'assy_customer'  => true,
            'name'           => 'Initial Name',
            'set'            => 'CARTON BOX',
            'date_format'    => 'YYMMDD',
            'assy_no'        => '1,8',
            'suffix'         => '9,2',
            'level'          => '11,2',
            'date_field'     => '13,6',
            'sequence_label' => '19,4',
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
        $this->assertEquals('1,8', $setting->assy_no, 'assy_no tidak boleh dikosongkan.');
        $this->assertEquals('9,2', $setting->suffix, 'suffix tidak boleh dikosongkan.');
        $this->assertEquals('11,2', $setting->level, 'level tidak boleh dikosongkan.');
        $this->assertEquals('13,6', $setting->date_field, 'date_field tidak boleh dikosongkan.');
        $this->assertEquals('19,4', $setting->sequence_label, 'sequence_label tidak boleh dikosongkan.');
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
             . "LINE-OPT,Updated Name,POLYTAINER,YYYYMMDD,yes,\"1,10\",\"11,1\",\"12,2\",\"14,8\",\"22,4\"\n";

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
            'assy_no'        => '1,10',
            'suffix'         => '11,1',
            'level'          => '12,2',
            'date_field'     => '14,8',
            'sequence_label' => '22,4',
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
            'assy_no'        => '1,8',
            'suffix'         => '9,2',
            'level'          => '',
            'date_field'     => '11,6',
            'sequence_label' => '17,4',
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
}
