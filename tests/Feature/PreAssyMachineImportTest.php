<?php

namespace Tests\Feature;

use App\Models\Carline;
use App\Models\CarType;
use App\Models\PreAssyMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Once;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PreAssyMachineImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Once::class)) {
            Once::flush();
        }

        Http::fake([
            '*/users*' => Http::response(['data' => []], 200),
            '*'        => Http::response([], 200),
        ]);
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
            'logged_in'     => true,
            'user_name'     => 'Operator QA',
            'user_role'     => 'User',
            'current_shift' => '1A',
        ]);
    }

    private function makeExcelUpload(array $rows, string $filename = 'machines.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIdx => $row) {
            foreach ($row as $colIdx => $cell) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $coord = $colLetter . ($rowIdx + 1);
                if (is_array($cell) && isset($cell['type'])) {
                    $sheet->setCellValueExplicit($coord, $cell['value'], $cell['type']);
                } else {
                    $sheet->setCellValue($coord, $cell);
                }
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpPath);

        return new UploadedFile(
            $tmpPath,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_admin_can_import_pre_assy_machines_successfully(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);

        $file = $this->makeExcelUpload([
            ['  no   mesin  ', 'Keterangan'],
            ['MC-01', 'ignored'],
            ['  MC-02  ', 'ignored'],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $file,
        ]);

        $response->assertRedirect(route('admin.master.pre_assy_machines.index', ['carline_id' => $carline->id]));
        $response->assertSessionHas('import_summary', [
            'success' => 2,
            'skipped' => 0,
            'errors'  => [],
        ]);

        $this->assertDatabaseHas('pre_assy_machines', [
            'carline_id' => $carline->id,
            'name'       => 'MC-01',
            'is_active'  => true,
        ]);
        $this->assertDatabaseHas('pre_assy_machines', [
            'carline_id' => $carline->id,
            'name'       => 'MC-02',
            'is_active'  => true,
        ]);
    }

    public function test_import_rejects_invalid_header(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);

        $file = $this->makeExcelUpload([
            ['Kode Mesin'],
            ['MC-01'],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('pre_assy_machines', 0);
    }

    public function test_import_ignores_empty_rows(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);

        $file = $this->makeExcelUpload([
            ['No Mesin'],
            [''],
            ['   '],
            [null],
            ['MC-10'],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $file,
        ]);

        $response->assertSessionHas('import_summary', [
            'success' => 1,
            'skipped' => 0,
            'errors'  => [],
        ]);
        $this->assertDatabaseCount('pre_assy_machines', 1);
    }

    public function test_import_skips_duplicates_in_db_case_insensitively(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);
        PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MC-01', 'is_active' => true]);

        $file = $this->makeExcelUpload([
            ['No Mesin'],
            ['mc-01'],
            ['MC-02'],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $file,
        ]);

        $summary = session('import_summary');
        $this->assertSame(1, $summary['success']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertStringContainsString('sudah ada di carline ini', $summary['errors'][0]);
        $this->assertDatabaseCount('pre_assy_machines', 2);
    }

    public function test_import_skips_duplicates_within_same_file(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);

        $file = $this->makeExcelUpload([
            ['No Mesin'],
            ['MC-01'],
            ['mc-01'],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $file,
        ]);

        $summary = session('import_summary');
        $this->assertSame(1, $summary['success']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertStringContainsString('kembar di dalam file', $summary['errors'][0]);
        $this->assertDatabaseCount('pre_assy_machines', 1);
    }

    public function test_import_preserves_leading_zero_for_text_cells_and_normalizes_whole_floats(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);

        $file = $this->makeExcelUpload([
            ['No Mesin'],
            [['value' => '001', 'type' => DataType::TYPE_STRING]],
            [12.0],
        ]);

        $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $file,
        ]);

        $this->assertDatabaseHas('pre_assy_machines', ['carline_id' => $carline->id, 'name' => '001']);
        $this->assertDatabaseHas('pre_assy_machines', ['carline_id' => $carline->id, 'name' => '12']);
    }

    public function test_import_rejects_csv_file(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);
        $csv = UploadedFile::fake()->createWithContent('machines.csv', "No Mesin\nMC-01\n");

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $csv,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('pre_assy_machines', 0);
    }

    public function test_import_rejects_more_than_1000_rows(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);

        $rows = [['No Mesin']];
        for ($i = 1; $i <= 1001; $i++) {
            $rows[] = ['MC-' . $i];
        }
        $file = $this->makeExcelUpload($rows);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('pre_assy_machines', 0);
    }

    public function test_import_rejects_invalid_carline(): void
    {
        $file = $this->makeExcelUpload([
            ['No Mesin'],
            ['MC-01'],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => 99999,
            'file'       => $file,
        ]);

        $response->assertSessionHasErrors('carline_id');
        $this->assertDatabaseCount('pre_assy_machines', 0);
    }

    public function test_non_admin_cannot_access_import_or_template(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);
        $file = $this->makeExcelUpload([['No Mesin'], ['MC-01']]);

        $this->actingAsOperator()
            ->post(route('admin.master.pre_assy_machines.import'), [
                'carline_id' => $carline->id,
                'file'       => $file,
            ])
            ->assertRedirect(route('operator.home'));

        $this->assertDatabaseCount('pre_assy_machines', 0);

        $this->actingAsOperator()
            ->get(route('admin.master.pre_assy_machines.template'))
            ->assertRedirect(route('operator.home'));
    }

    public function test_admin_can_download_template(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.master.pre_assy_machines.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=template_no_mesin_pre_assy.xlsx');

        $tmpFile = tempnam(sys_get_temp_dir(), 'tpl_');
        file_put_contents($tmpFile, $response->streamedContent());
        $spreadsheet = IOFactory::load($tmpFile);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('No Mesin', $sheet->getCell('A1')->getValue());
        $this->assertNull($sheet->getCell('A2')->getValue());
        $this->assertSame('@', $sheet->getStyle('A:A')->getNumberFormat()->getFormatCode());
    }

    public function test_import_rejects_final_assy_carline(): void
    {
        $carType = CarType::create(['name' => 'TYPE-A']);
        $finalAssyCarline = Carline::create(['name' => 'CL-FINAL-1', 'car_type_id' => $carType->id]);

        $file = $this->makeExcelUpload([
            ['No Mesin'],
            ['MC-01'],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $finalAssyCarline->id,
            'file'       => $file,
        ]);

        $response->assertSessionHasErrors('carline_id');
        $this->assertDatabaseCount('pre_assy_machines', 0);
    }

    public function test_import_allows_file_with_5_data_rows_and_1500_empty_rows(): void
    {
        $carline = Carline::create(['name' => 'CL-PRE-1', 'type' => 'pre_assy']);

        $rows = [['No Mesin']];
        for ($i = 1; $i <= 5; $i++) {
            $rows[] = ['MC-' . $i];
        }
        for ($i = 1; $i <= 1500; $i++) {
            $rows[] = [''];
        }
        $file = $this->makeExcelUpload($rows);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.import'), [
            'carline_id' => $carline->id,
            'file'       => $file,
        ]);

        $response->assertSessionHas('import_summary', [
            'success' => 5,
            'skipped' => 0,
            'errors'  => [],
        ]);
        $this->assertDatabaseCount('pre_assy_machines', 5);
    }
}
