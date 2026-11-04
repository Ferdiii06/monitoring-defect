<?php

namespace Tests\Unit;

use App\Support\KanbanCodeParser;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class KanbanCodeParserTest extends TestCase
{
    /**
     * Test validasi kanban contoh 1 (*82111-VU020.C7.010*).
     */
    public function test_parse_kanban_example_1_with_asterisks(): void
    {
        $result = KanbanCodeParser::parseKanban('*82111-VU020.C7.010*');

        $this->assertNotNull($result);
        $this->assertSame('82111-VU020', $result['assy_number']);
        $this->assertSame('C7', $result['conveyor']);
        $this->assertSame('010', $result['serial']);
    }

    /**
     * Test validasi kanban contoh 2 (82115-0EA11.AT11.057).
     */
    public function test_parse_kanban_example_2(): void
    {
        $result = KanbanCodeParser::parseKanban('82115-0EA11.AT11.057');

        $this->assertNotNull($result);
        $this->assertSame('82115-0EA11', $result['assy_number']);
        $this->assertSame('AT11', $result['conveyor']);
        $this->assertSame('057', $result['serial']);
    }

    /**
     * Test validasi kanban contoh 3 dengan pemisah spasi (82111-VU020.C7 010).
     */
    public function test_parse_kanban_example_3_with_space_separator(): void
    {
        $result = KanbanCodeParser::parseKanban('82111-VU020.C7 010');

        $this->assertNotNull($result);
        $this->assertSame('82111-VU020', $result['assy_number']);
        $this->assertSame('C7', $result['conveyor']);
        $this->assertSame('010', $result['serial']);
    }

    /**
     * Test validasi nameplate contoh acuan ("82111VU020  A2607060008").
     */
    public function test_parse_nameplate_standard_example(): void
    {
        $result = KanbanCodeParser::parseNameplate('82111VU020  A2607060008');

        $this->assertNotNull($result);
        $this->assertSame('82111-VU020', $result['assy_number']);
        $this->assertSame('82111VU020', $result['assy_number_raw']);
        $this->assertSame('A', $result['suffix']);
        $this->assertSame('260706', $result['date_code']);
        $this->assertInstanceOf(Carbon::class, $result['date']);
        $this->assertSame('2026-07-06', $result['date']->format('Y-m-d'));
        $this->assertSame('0008', $result['serial']);
    }

    /**
     * Test variasi input: tanda * ikut terbaca, huruf kecil, spasi berlebih di awal/akhir.
     */
    public function test_normalization_variations(): void
    {
        // Kanban dengan huruf kecil, spasi di awal/akhir, dan asterisk
        $kanban = KanbanCodeParser::parseKanban('   *82111-vu020.c7.010*   ');
        $this->assertNotNull($kanban);
        $this->assertSame('82111-VU020', $kanban['assy_number']);
        $this->assertSame('C7', $kanban['conveyor']);
        $this->assertSame('010', $kanban['serial']);

        // Nameplate dengan tanda asterisk, huruf kecil, dan spasi di awal/akhir
        $nameplate = KanbanCodeParser::parseNameplate('   *82111vu020  a2607060008*   ');
        $this->assertNotNull($nameplate);
        $this->assertSame('82111-VU020', $nameplate['assy_number']);
        $this->assertSame('82111VU020', $nameplate['assy_number_raw']);
        $this->assertSame('A', $nameplate['suffix']);
        $this->assertSame('260706', $nameplate['date_code']);
        $this->assertInstanceOf(Carbon::class, $nameplate['date']);
        $this->assertSame('2026-07-06', $nameplate['date']->format('Y-m-d'));
        $this->assertSame('0008', $nameplate['serial']);
    }

    /**
     * Test variasi pemisah kanban (titik vs spasi, kombinasi).
     */
    public function test_kanban_separator_variations(): void
    {
        // Titik semua
        $r1 = KanbanCodeParser::parseKanban('82111-VU020.C7.010');
        $this->assertNotNull($r1);
        $this->assertSame('82111-VU020', $r1['assy_number']);
        $this->assertSame('C7', $r1['conveyor']);
        $this->assertSame('010', $r1['serial']);

        // Spasi semua
        $r2 = KanbanCodeParser::parseKanban('82111-VU020 C7 010');
        $this->assertNotNull($r2);
        $this->assertSame('82111-VU020', $r2['assy_number']);
        $this->assertSame('C7', $r2['conveyor']);
        $this->assertSame('010', $r2['serial']);

        // Spasi lalu titik
        $r3 = KanbanCodeParser::parseKanban('82111-VU020 C7.010');
        $this->assertNotNull($r3);
        $this->assertSame('82111-VU020', $r3['assy_number']);
        $this->assertSame('C7', $r3['conveyor']);
        $this->assertSame('010', $r3['serial']);

        // Titik lalu spasi
        $r4 = KanbanCodeParser::parseKanban('82111-VU020.C7 010');
        $this->assertNotNull($r4);
        $this->assertSame('82111-VU020', $r4['assy_number']);
        $this->assertSame('C7', $r4['conveyor']);
        $this->assertSame('010', $r4['serial']);
    }

    /**
     * Test nameplate tanpa suffix (suffix bernilai null).
     */
    public function test_nameplate_without_suffix(): void
    {
        $result = KanbanCodeParser::parseNameplate('82111VU020  2607060008');

        $this->assertNotNull($result);
        $this->assertSame('82111-VU020', $result['assy_number']);
        $this->assertSame('82111VU020', $result['assy_number_raw']);
        $this->assertNull($result['suffix']);
        $this->assertSame('260706', $result['date_code']);
        $this->assertInstanceOf(Carbon::class, $result['date']);
        $this->assertSame('2026-07-06', $result['date']->format('Y-m-d'));
        $this->assertSame('0008', $result['serial']);
    }

    /**
     * Test nameplate dengan kode tanggal tidak valid (date null, tidak melempar exception).
     */
    public function test_nameplate_with_invalid_date_code_returns_null_date_without_exception(): void
    {
        // Bulan 13 tidak valid
        $resultMonth13 = KanbanCodeParser::parseNameplate('82111VU020  A2613060008');
        $this->assertNotNull($resultMonth13);
        $this->assertSame('82111-VU020', $resultMonth13['assy_number']);
        $this->assertSame('82111VU020', $resultMonth13['assy_number_raw']);
        $this->assertSame('A', $resultMonth13['suffix']);
        $this->assertSame('261306', $resultMonth13['date_code']);
        $this->assertNull($resultMonth13['date']);
        $this->assertSame('0008', $resultMonth13['serial']);

        // Tanggal 32 tidak valid
        $resultDay32 = KanbanCodeParser::parseNameplate('82111VU020  A2607320008');
        $this->assertNotNull($resultDay32);
        $this->assertSame('260732', $resultDay32['date_code']);
        $this->assertNull($resultDay32['date']);

        // 29 Feb di tahun bukan kabisat (2026)
        $resultNonLeap = KanbanCodeParser::parseNameplate('82111VU020  A2602290008');
        $this->assertNotNull($resultNonLeap);
        $this->assertSame('260229', $resultNonLeap['date_code']);
        $this->assertNull($resultNonLeap['date']);

        // Digit 999999
        $result999 = KanbanCodeParser::parseNameplate('82111VU020  A9999990008');
        $this->assertNotNull($result999);
        $this->assertSame('999999', $result999['date_code']);
        $this->assertNull($result999['date']);
    }

    /**
     * Test input tidak valid mengembalikan null: string kosong, null, teks acak, kanban kurang segmen, serial bukan 3 digit.
     */
    public function test_invalid_inputs_return_null(): void
    {
        // Null dan string kosong
        $this->assertNull(KanbanCodeParser::parseKanban(null));
        $this->assertNull(KanbanCodeParser::parseKanban(''));
        $this->assertNull(KanbanCodeParser::parseKanban('   '));
        $this->assertNull(KanbanCodeParser::parseKanban('*'));
        $this->assertNull(KanbanCodeParser::parseKanban('***'));

        $this->assertNull(KanbanCodeParser::parseNameplate(null));
        $this->assertNull(KanbanCodeParser::parseNameplate(''));
        $this->assertNull(KanbanCodeParser::parseNameplate('   '));
        $this->assertNull(KanbanCodeParser::parseNameplate('*'));

        // Teks acak
        $this->assertNull(KanbanCodeParser::parseKanban('RANDOM TEXT 12345'));
        $this->assertNull(KanbanCodeParser::parseNameplate('RANDOM TEXT 12345'));

        // Kanban kurang segmen
        $this->assertNull(KanbanCodeParser::parseKanban('82111-VU020.C7'));
        $this->assertNull(KanbanCodeParser::parseKanban('82111-VU020'));

        // Kanban serial bukan 3 digit (2 digit atau 4 digit atau non-angka)
        $this->assertNull(KanbanCodeParser::parseKanban('82111-VU020.C7.01'));
        $this->assertNull(KanbanCodeParser::parseKanban('82111-VU020.C7.0100'));
        $this->assertNull(KanbanCodeParser::parseKanban('82111-VU020.C7.ABC'));

        // Kanban assy tanpa tanda minus
        $this->assertNull(KanbanCodeParser::parseKanban('82111VU020.C7.010'));

        // Nameplate format tidak valid (assy kurang digit, serial bukan 4 digit, date bukan 6 digit)
        $this->assertNull(KanbanCodeParser::parseNameplate('82111VU02  A2607060008')); // assy 9 digit
        $this->assertNull(KanbanCodeParser::parseNameplate('82111VU020  A260706008'));  // serial 3 digit
        $this->assertNull(KanbanCodeParser::parseNameplate('82111VU020  A26070600008')); // serial 5 digit
        $this->assertNull(KanbanCodeParser::parseNameplate('82111VU020  A260700008'));   // date 5 digit
    }

    /**
     * Test method detect() membedakan tipe kanban vs nameplate dengan benar.
     */
    public function test_detect_distinguishes_kanban_and_nameplate(): void
    {
        // Deteksi kanban
        $detectedKanban = KanbanCodeParser::detect('*82111-VU020.C7.010*');
        $this->assertNotNull($detectedKanban);
        $this->assertSame('kanban', $detectedKanban['type']);
        $this->assertSame('82111-VU020', $detectedKanban['assy_number']);
        $this->assertSame('C7', $detectedKanban['conveyor']);
        $this->assertSame('010', $detectedKanban['serial']);

        // Deteksi nameplate
        $detectedNameplate = KanbanCodeParser::detect('82111VU020  A2607060008');
        $this->assertNotNull($detectedNameplate);
        $this->assertSame('nameplate', $detectedNameplate['type']);
        $this->assertSame('82111-VU020', $detectedNameplate['assy_number']);
        $this->assertSame('82111VU020', $detectedNameplate['assy_number_raw']);
        $this->assertSame('A', $detectedNameplate['suffix']);
        $this->assertSame('260706', $detectedNameplate['date_code']);
        $this->assertInstanceOf(Carbon::class, $detectedNameplate['date']);
        $this->assertSame('2026-07-06', $detectedNameplate['date']->format('Y-m-d'));
        $this->assertSame('0008', $detectedNameplate['serial']);

        // Deteksi input tidak valid
        $this->assertNull(KanbanCodeParser::detect(null));
        $this->assertNull(KanbanCodeParser::detect(''));
        $this->assertNull(KanbanCodeParser::detect('INVALID_CODE'));
    }
}
