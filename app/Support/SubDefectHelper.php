<?php

namespace App\Support;

class SubDefectHelper
{
    /**
     * Peta alias sub defect lintas lini untuk agregasi Mode Gabungan.
     * Hanya sub defect yang benar-benar setara secara konsep antar-lini
     * (saat ini: FOREIGN MATERIAL yang ada di Final Assy 11.A dan Pre Assy G.1)
     * yang disatukan menjadi satu alias bersama ('FOREIGN MATERIAL').
     */
    public const COMBINED_ALIAS_MAP = [
        '11.A - FOREIGN MATERIAL' => 'FOREIGN MATERIAL',
        'G.1 - FOREIGN MATERIAL'  => 'FOREIGN MATERIAL',
        'FOREIGN MATERIAL'        => 'FOREIGN MATERIAL',
    ];

    /**
     * Derive sub defect code from name if matching pattern '^(\d+)\.([A-Za-z])\b'.
     * Example: '1.A - CROSS CIRCUIT' -> '1A'.
     * Returns null if name does not match the pattern.
     */
    public static function deriveCode(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        if (preg_match('/^(\d+)\.([A-Za-z])\b/', trim($name), $matches)) {
            return $matches[1] . strtoupper($matches[2]);
        }

        return null;
    }

    /**
     * Normalisasi nama sub defect untuk mode Gabungan (Mode All).
     *
     * Aturan:
     * 1. null, string kosong, atau '-' -> 'LAIN-LAIN'
     * 2. 'LAIN-LAIN' -> 'LAIN-LAIN'
     * 3. Jika nama (trimmed, uppercase) cocok dengan entri di COMBINED_ALIAS_MAP,
     *    kembalikan canonical alias (saat ini: 'FOREIGN MATERIAL').
     * 4. Jika tidak ada di peta alias, kembalikan nama mentah yang di-trim dan di-uppercase
     *    beserta kode aslinya (contoh: '5.B - MISALIGN', '6.B - MISALIGN',
     *    'C.2 - C/H TERLALU RENDAH', 'D.2 - C/H TERLALU RENDAH').
     */
    public static function normalize(?string $name): string
    {
        if ($name === null) {
            return 'LAIN-LAIN';
        }

        $trimmed = trim($name);
        if ($trimmed === '' || $trimmed === '-') {
            return 'LAIN-LAIN';
        }

        $upper = strtoupper(preg_replace('/\s+/', ' ', $trimmed));
        if ($upper === 'LAIN-LAIN') {
            return 'LAIN-LAIN';
        }

        if (isset(self::COMBINED_ALIAS_MAP[$upper])) {
            return self::COMBINED_ALIAS_MAP[$upper];
        }

        return $upper;
    }
}
