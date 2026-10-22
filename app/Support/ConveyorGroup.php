<?php

namespace App\Support;

use App\Models\Carline;
use App\Models\Defect;

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

    /**
     * Dapatkan semua kode grup konveyor unik dari gabungan master carline Final Assy dan data defects.
     *
     * @return array<string>
     */
    public static function availableGroups(): array
    {
        $masterFinalConveyors = Carline::whereNotNull('car_type_id')->pluck('name');
        $defectFinalConveyors = Defect::where('jenis_assy', 'Final Assy')
            ->whereNotNull('conveyor')
            ->where('conveyor', '!=', '')
            ->distinct()
            ->pluck('conveyor');

        $groups = $masterFinalConveyors->merge($defectFinalConveyors)
            ->map(fn($name) => static::of($name))
            ->filter(fn($g) => $g !== '')
            ->unique()
            ->values()
            ->all();

        usort($groups, 'strnatcasecmp');

        return $groups;
    }

    /**
     * Cari semua nama konveyor dari gabungan master dan data defects yang memiliki grup yang sama.
     *
     * @return array<string>
     */
    public static function resolveNamesForGroup(string $groupKey): array
    {
        $masterFinalConveyors = Carline::whereNotNull('car_type_id')->pluck('name');
        $defectFinalConveyors = Defect::where('jenis_assy', 'Final Assy')
            ->whereNotNull('conveyor')
            ->where('conveyor', '!=', '')
            ->distinct()
            ->pluck('conveyor');

        $allConveyors = $masterFinalConveyors->merge($defectFinalConveyors)->unique();

        return $allConveyors->filter(function ($name) use ($groupKey) {
            return static::of($name) === $groupKey;
        })->values()->all();
    }
}
