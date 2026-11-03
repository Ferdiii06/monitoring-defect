<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Support\SubDefectHelper;

class SubDefectHelperTest extends TestCase
{
    /**
     * Test normalisasi sub defect menangani null, empty string, strip, dan kata LAIN-LAIN.
     */
    public function test_normalize_handles_null_empty_and_lain_lain(): void
    {
        $this->assertEquals('LAIN-LAIN', SubDefectHelper::normalize(null));
        $this->assertEquals('LAIN-LAIN', SubDefectHelper::normalize(''));
        $this->assertEquals('LAIN-LAIN', SubDefectHelper::normalize('   '));
        $this->assertEquals('LAIN-LAIN', SubDefectHelper::normalize('-'));
        $this->assertEquals('LAIN-LAIN', SubDefectHelper::normalize(' - '));
        $this->assertEquals('LAIN-LAIN', SubDefectHelper::normalize('LAIN-LAIN'));
        $this->assertEquals('LAIN-LAIN', SubDefectHelper::normalize('lain-lain'));
        $this->assertEquals('LAIN-LAIN', SubDefectHelper::normalize('  Lain-Lain  '));
    }

    /**
     * Test penyatuan sub defect lintas lini menggunakan peta alias (saat ini: FOREIGN MATERIAL).
     * 11.A (Final Assy) dan G.1 (Pre Assy) keduanya disatukan menjadi 'FOREIGN MATERIAL'.
     */
    public function test_normalize_aliases_foreign_material_across_lines(): void
    {
        $this->assertEquals('FOREIGN MATERIAL', SubDefectHelper::normalize('11.A - FOREIGN MATERIAL'));
        $this->assertEquals('FOREIGN MATERIAL', SubDefectHelper::normalize('G.1 - FOREIGN MATERIAL'));
        $this->assertEquals('FOREIGN MATERIAL', SubDefectHelper::normalize('FOREIGN MATERIAL'));
        $this->assertEquals('FOREIGN MATERIAL', SubDefectHelper::normalize('foreign material'));
        $this->assertEquals('FOREIGN MATERIAL', SubDefectHelper::normalize('  11.A - foreign material  '));
        $this->assertEquals('FOREIGN MATERIAL', SubDefectHelper::normalize('  g.1 - foreign material  '));
    }

    /**
     * Test sub defect lain tetap mempertahankan kode dan nama mentah (tidak menyatu).
     * Contoh: C.2 dan D.2 tetap terpisah; 5.B dan 6.B tetap terpisah.
     */
    public function test_normalize_preserves_raw_name_and_code_for_other_defects(): void
    {
        // Pre Assy: C/H TERLALU RENDAH pada FRONT CRIMPING (C.2) vs REAR CRIMPING (D.2)
        $this->assertEquals('C.2 - C/H TERLALU RENDAH', SubDefectHelper::normalize('C.2 - C/H TERLALU RENDAH'));
        $this->assertEquals('D.2 - C/H TERLALU RENDAH', SubDefectHelper::normalize('D.2 - C/H TERLALU RENDAH'));
        $this->assertNotEquals(
            SubDefectHelper::normalize('C.2 - C/H TERLALU RENDAH'),
            SubDefectHelper::normalize('D.2 - C/H TERLALU RENDAH')
        );

        // Final Assy: MISALIGN pada HALF LOCK (5.B) vs WRONG PART (6.B)
        $this->assertEquals('5.B - MISALIGN', SubDefectHelper::normalize('5.B - MISALIGN'));
        $this->assertEquals('6.B - MISALIGN', SubDefectHelper::normalize('6.B - MISALIGN'));
        $this->assertNotEquals(
            SubDefectHelper::normalize('5.B - MISALIGN'),
            SubDefectHelper::normalize('6.B - MISALIGN')
        );

        // Lainnya
        $this->assertEquals('1.A - CROSS CIRCUIT', SubDefectHelper::normalize('1.A - CROSS CIRCUIT'));
        $this->assertEquals('NON-CRIMPING', SubDefectHelper::normalize('NON-CRIMPING'));
    }
}
