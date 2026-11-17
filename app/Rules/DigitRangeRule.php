<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DigitRangeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Format yang diterima:
     * - Satu digit: "N" (contoh: "9")
     * - Rentang: "A-B" (contoh: "1-8"), dengan A <= B
     * Hanya angka bulat positif (A >= 1, B >= 1).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $str = trim((string) $value);

        // 1. Satu digit: "N" (angka bulat positif >= 1)
        if (preg_match('/^([1-9]\d*)$/', $str)) {
            return;
        }

        // 2. Rentang: "A-B" (A >= 1, B >= 1, dan A <= B)
        if (preg_match('/^([1-9]\d*)\s*-\s*([1-9]\d*)$/', $str, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($a <= $b) {
                return;
            }
        }

        $fail("Format harus 'Digit ke' awal-akhir, mis. 1-8, atau satu digit, mis. 9.");
    }
}
