<?php

namespace App\Support;

class ConveyorGroup
{
    /**
     * Extract conveyor group from conveyor full name.
     *
     * Rules:
     * 1. Jika nama tidak mengandung "-", kembalikan apa adanya (trim).
     * 2. Ambil teks setelah "-" PERTAMA, potong sebelum "." pertama, trim.
     *    Contoh: 664W-C5A -> C5A, J30A-AB6.EXTEND LHD -> AB6, 953W ENG NO.3-C9 -> C9, 891W/892W-C1.GAS LHD -> C1.
     * 3. JANGAN membuang huruf terakhir (C5A tetap C5A).
     */
    public static function of(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $trimmed = trim($name);
        if ($trimmed === '') {
            return '';
        }

        if (!str_contains($trimmed, '-')) {
            return $trimmed;
        }

        $afterDash = explode('-', $trimmed, 2)[1];
        $afterDashBeforeDot = explode('.', $afterDash, 2)[0];

        return trim($afterDashBeforeDot);
    }
}
