<?php

namespace Tests\Feature;

use App\Models\Nameplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminNameplateTest extends TestCase
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

    public function test_guest_cannot_access_nameplates(): void
    {
        $response = $this->get('/master/nameplates');
        $response->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_nameplates(): void
    {
        $response = $this->actingAsOperator()->get('/master/nameplates');
        $response->assertRedirect(route('operator.home'));
    }

    public function test_admin_can_access_nameplates_index(): void
    {
        Nameplate::create([
            'nameplate_code'  => '82111VU020A2607060008',
            'assy_number'     => '82111-VU020',
            'serial_number'   => '0008',
            'source'          => Nameplate::SOURCE_MANUAL,
            'is_manual_edit'  => true,
        ]);

        $response = $this->actingAsAdmin()->get('/master/nameplates');

        $response->assertStatus(200);
        $response->assertSee('Master Nameplate (HTScan JAI)');
        $response->assertSee('82111VU020A2607060008');
        $response->assertSee('82111-VU020');
    }

    public function test_admin_can_create_nameplate_manually_and_normalizes_code(): void
    {
        // Kode dengan spasi berlebih, huruf kecil, dan tanda bintang
        $rawInput = '  *82111vu020   a2607060008*  ';

        $response = $this->actingAsAdmin()->post('/master/nameplates', [
            'nameplate_code' => $rawInput,
            'keterangan'     => 'Pencatatan manual perdana',
        ]);

        $response->assertRedirect(route('admin.master.nameplates.index'));
        $response->assertSessionHas('success');

        // Harus ternormalisasi: huruf besar, bintang hilang, SEMUA spasi hilang
        $expectedCode = '82111VU020A2607060008';

        $this->assertDatabaseHas('nameplates', [
            'nameplate_code' => $expectedCode,
            'assy_number'    => '82111-VU020',
            'suffix'         => 'A',
            'date_code'      => '260706',
            'serial_number'  => '0008',
            'source'         => Nameplate::SOURCE_MANUAL,
            'is_manual_edit' => true,
            'keterangan'     => 'Pencatatan manual perdana',
        ]);
    }

    public function test_duplicate_nameplate_code_is_rejected(): void
    {
        Nameplate::create([
            'nameplate_code' => '82111VU020A2607060008',
            'source'         => Nameplate::SOURCE_MANUAL,
            'is_manual_edit' => true,
        ]);

        // Input kode yang sama (meski variasi spasi/case)
        $response = $this->actingAsAdmin()->post('/master/nameplates', [
            'nameplate_code' => '  82111vu020    a2607060008  ',
        ]);

        $response->assertSessionHasErrors(['nameplate_code']);
    }

    /**
     * Requirement 2:
     * Kunci unik nameplate_code harus dari kode TANPA spasi sama sekali.
     * Pastikan "82111VU020 A2607060008", "82111VU020A2607060008", dan versi spasi ganda menghasilkan satu baris.
     */
    public function test_nameplate_code_without_spaces_and_with_spaces_resolve_to_same_record(): void
    {
        // 1. Uji lewat manual store
        $res1 = $this->actingAsAdmin()->post('/master/nameplates', [
            'nameplate_code' => '82111VU020 A2607060008', // dengan 1 spasi
        ]);
        $res1->assertSessionHasNoErrors();
        $this->assertEquals(1, Nameplate::where('nameplate_code', '82111VU020A2607060008')->count());

        // Coba input tanpa spasi: harus ditolak karena duplikat
        $res2 = $this->actingAsAdmin()->post('/master/nameplates', [
            'nameplate_code' => '82111VU020A2607060008', // tanpa spasi
        ]);
        $res2->assertSessionHasErrors(['nameplate_code']);

        // Coba input dengan spasi ganda: harus ditolak karena duplikat
        $res3 = $this->actingAsAdmin()->post('/master/nameplates', [
            'nameplate_code' => '82111VU020   A2607060008', // spasi ganda
        ]);
        $res3->assertSessionHasErrors(['nameplate_code']);

        // Database tetap hanya memiliki 1 baris
        $this->assertEquals(1, Nameplate::count());

        // 2. Uji lewat CSV import
        Nameplate::query()->delete();

        $csvContent = implode("\n", [
            'nameplate_code,keterangan',
            '82111VU020 A2607060008,Versi Spasi Tunggal',
            '82111VU020A2607060008,Versi Tanpa Spasi',
            '82111VU020   A2607060008,Versi Spasi Ganda',
        ]);

        $file = UploadedFile::fake()->createWithContent('variasi_spasi.csv', $csvContent);
        $importResponse = $this->actingAsAdmin()->post('/master/nameplates/import', ['file' => $file]);
        $importResponse->assertRedirect(route('admin.master.nameplates.index'));

        // Hasil import: 1 record baru ditambahkan, 2 baris berikutnya mengupdate record yang sama
        $summary = session('nameplate_import_summary');
        $this->assertEquals(1, $summary['added']);
        $this->assertEquals(2, $summary['updated']);
        $this->assertEquals(0, $summary['failed']);

        // Hanya ada 1 baris unik di tabel nameplates
        $this->assertEquals(1, Nameplate::count());
        $saved = Nameplate::first();
        $this->assertEquals('82111VU020A2607060008', $saved->nameplate_code);
        $this->assertEquals('82111-VU020', $saved->assy_number);
    }

    /**
     * Requirement 3:
     * production_date dari date_code yang tidak valid (mis. 261340) harus null tanpa error.
     */
    public function test_invalid_date_code_results_in_null_production_date_without_error(): void
    {
        // Kode dengan bulan 13 dan tanggal 40 ('261340')
        $invalidDateCode = '82111VU020 A2613400008';

        // 1. Simpan lewat controller manual
        $response = $this->actingAsAdmin()->post('/master/nameplates', [
            'nameplate_code' => $invalidDateCode,
            'keterangan'     => 'Uji invalid date code 261340',
        ]);

        $response->assertRedirect(route('admin.master.nameplates.index'));
        $response->assertSessionHasNoErrors();

        $saved = Nameplate::where('nameplate_code', '82111VU020A2613400008')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('261340', $saved->date_code);
        $this->assertNull($saved->production_date, 'production_date harus null saat date_code tidak valid');
        $this->assertEquals('82111-VU020', $saved->assy_number);
        $this->assertEquals('A', $saved->suffix);
        $this->assertEquals('0008', $saved->serial_number);

        // 2. Simpan lewat CSV Import
        $csvContent = implode("\n", [
            'nameplate_code,keterangan',
            '821150EA11 B2699990099,Invalid Month and Day 99',
        ]);
        $file = UploadedFile::fake()->createWithContent('invalid_date.csv', $csvContent);
        $importResponse = $this->actingAsAdmin()->post('/master/nameplates/import', ['file' => $file]);
        $importResponse->assertRedirect(route('admin.master.nameplates.index'));

        $savedImport = Nameplate::where('nameplate_code', '821150EA11B2699990099')->first();
        $this->assertNotNull($savedImport);
        $this->assertEquals('269999', $savedImport->date_code);
        $this->assertNull($savedImport->production_date);
    }

    public function test_admin_can_update_nameplate_and_sets_manual_edit_true(): void
    {
        // Record awal hasil import otomatis
        $np = Nameplate::create([
            'nameplate_code' => '82111VU020A2607060008',
            'assy_number'    => '82111-VU020',
            'source'         => Nameplate::SOURCE_IMPORT,
            'is_manual_edit' => false,
        ]);

        $response = $this->actingAsAdmin()->put("/master/nameplates/{$np->id}", [
            'nameplate_code' => '82111VU020A2607060008',
            'assy_number'    => '82111-VU020-REVISED',
            'serial_number'  => '9999',
            'keterangan'     => 'Diedit oleh supervisor QA',
        ]);

        $response->assertRedirect(route('admin.master.nameplates.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nameplates', [
            'id'             => $np->id,
            'assy_number'    => '82111-VU020-REVISED',
            'serial_number'  => '9999',
            'is_manual_edit' => true,
            'keterangan'     => 'Diedit oleh supervisor QA',
        ]);
    }

    public function test_admin_can_delete_nameplate(): void
    {
        $np = Nameplate::create([
            'nameplate_code' => '82111VU020A2607060008',
            'source'         => Nameplate::SOURCE_MANUAL,
            'is_manual_edit' => true,
        ]);

        $response = $this->actingAsAdmin()->delete("/master/nameplates/{$np->id}");
        $response->assertRedirect(route('admin.master.nameplates.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('nameplates', ['id' => $np->id]);
    }

    public function test_import_valid_csv_file_with_raw_payload(): void
    {
        $csvContent = implode("\n", [
            'nameplate_code,keterangan,line_htscan,operator_scan',
            '82111VU020  A2607060008,Batch 1,LINE_ALPHA,OPR_01',
            '821150EA11  B2608100015,Batch 2,LINE_BETA,OPR_02',
        ]);

        $file = UploadedFile::fake()->createWithContent('nameplates.csv', $csvContent);

        $response = $this->actingAsAdmin()->post('/master/nameplates/import', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.nameplates.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('nameplate_import_summary');

        $summary = session('nameplate_import_summary');
        $this->assertEquals(2, $summary['added']);
        $this->assertEquals(0, $summary['updated']);
        $this->assertEquals(0, $summary['failed']);

        // Verifikasi row 1 (kunci unik tanpa spasi)
        $record1 = Nameplate::where('nameplate_code', '82111VU020A2607060008')->first();
        $this->assertNotNull($record1);
        $this->assertEquals('82111-VU020', $record1->assy_number);
        $this->assertEquals(Nameplate::SOURCE_IMPORT, $record1->source);
        $this->assertFalse($record1->is_manual_edit);

        // Kolom tak dikenal harus masuk raw_payload
        $this->assertIsArray($record1->raw_payload);
        $this->assertEquals('LINE_ALPHA', $record1->raw_payload['line_htscan']);
        $this->assertEquals('OPR_01', $record1->raw_payload['operator_scan']);
    }

    public function test_import_with_bad_rows_continues_and_reports_errors(): void
    {
        $csvContent = implode("\n", [
            'nameplate_code,keterangan',
            '82111VU020  A2607060008,Baris Valid 1',
            ',Baris Kosong', // Baris 3: Kode kosong
            '   ,Spasi Kosong', // Baris 4: Spasi kosong
            '821150EA11  B2608100015,Baris Valid 2',
        ]);

        $file = UploadedFile::fake()->createWithContent('nameplates_bad.csv', $csvContent);

        $response = $this->actingAsAdmin()->post('/master/nameplates/import', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.master.nameplates.index'));
        $summary = session('nameplate_import_summary');

        // 2 baris valid berhasil masuk, 2 baris gagal
        $this->assertEquals(2, $summary['added']);
        $this->assertEquals(2, $summary['failed']);
        $this->assertCount(2, $summary['errors']);
    }

    public function test_import_upsert_does_not_overwrite_manual_edit(): void
    {
        // 1. Buat record yang sudah diedit manual
        Nameplate::create([
            'nameplate_code' => '82111VU020A2607060008',
            'assy_number'    => '82111-CUSTOM-ASSY',
            'keterangan'     => 'Keterangan manual penting',
            'source'         => Nameplate::SOURCE_MANUAL,
            'is_manual_edit' => true,
        ]);

        // 2. Buat record biasa (belum diedit manual)
        Nameplate::create([
            'nameplate_code' => '821150EA11B2608100015',
            'assy_number'    => '82115-0EA11',
            'keterangan'     => 'Keterangan lama',
            'source'         => Nameplate::SOURCE_IMPORT,
            'is_manual_edit' => false,
        ]);

        // 3. File CSV mengimpor kedua kode di atas dengan nilai baru
        $csvContent = implode("\n", [
            'nameplate_code,keterangan',
            '82111VU020  A2607060008,Mencoba Menimpa Manual',
            '821150EA11  B2608100015,Keterangan Terupdate Otomatis',
        ]);

        $file = UploadedFile::fake()->createWithContent('nameplates_upsert.csv', $csvContent);

        $response = $this->actingAsAdmin()->post('/master/nameplates/import', [
            'file' => $file,
        ]);

        $summary = session('nameplate_import_summary');
        $this->assertEquals(1, $summary['updated'], 'Hanya 1 record non-manual yang boleh diperbarui');
        $this->assertEquals(1, $summary['skipped'], 'Record manual edit wajib dilewati');

        // Cek record manual: Nilai TIDAK BOLEH BERUBAH!
        $manualRecord = Nameplate::where('nameplate_code', '82111VU020A2607060008')->first();
        $this->assertNotNull($manualRecord);
        $this->assertEquals('82111-CUSTOM-ASSY', $manualRecord->assy_number);
        $this->assertEquals('Keterangan manual penting', $manualRecord->keterangan);
        $this->assertTrue($manualRecord->is_manual_edit);

        // Cek record non-manual: Nilai terupdate
        $importRecord = Nameplate::where('nameplate_code', '821150EA11B2608100015')->first();
        $this->assertNotNull($importRecord);
        $this->assertEquals('Keterangan Terupdate Otomatis', $importRecord->keterangan);
    }

    public function test_code_not_matching_pattern_is_saved_with_null_parsed_fields(): void
    {
        // Kode tidak cocok pola standar NAMEPLATE_PATTERN tapi tetap merupakan barcode unik
        $customCode = 'CUSTOM-HTSCAN-BATCH-999';

        $response = $this->actingAsAdmin()->post('/master/nameplates', [
            'nameplate_code' => $customCode,
            'keterangan'     => 'Barcode internal non standar',
        ]);

        $response->assertRedirect(route('admin.master.nameplates.index'));

        $this->assertDatabaseHas('nameplates', [
            'nameplate_code'  => $customCode,
            'assy_number'     => null,
            'suffix'          => null,
            'date_code'       => null,
            'production_date' => null,
            'serial_number'   => null,
            'keterangan'      => 'Barcode internal non standar',
        ]);
    }
}
