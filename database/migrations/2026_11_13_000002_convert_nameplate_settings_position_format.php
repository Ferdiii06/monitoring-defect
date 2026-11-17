<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengubah format aturan posisi pada nameplate_settings dari format legacy
     * "posisi,panjang" (mis. 1,8 -> 1-8) dan "1:8" menjadi format rentang "A-B" atau satu digit "N".
     * Migration ini bersifat idempotent.
     */
    public function up(): void
    {
        $columns = ['assy_no', 'suffix', 'level', 'date_field', 'sequence_label'];

        $records = DB::table('nameplate_settings')->get();

        foreach ($records as $record) {
            $updates = [];
            foreach ($columns as $col) {
                $val = $record->{$col};
                if ($val === null || trim((string) $val) === '') {
                    continue;
                }

                $converted = $this->convertLegacyFormat((string) $val);
                if ($converted !== $val) {
                    $updates[$col] = $converted;
                }
            }

            if (!empty($updates)) {
                DB::table('nameplate_settings')
                    ->where('id', $record->id)
                    ->update($updates);
            }
        }
    }

    /**
     * Konversi format legacy posisi dan panjang ke format digit rentang (A-B atau N).
     * Nilai yang sudah berformat A-B, N, atau tidak dapat dibaca dibiarkan apa adanya.
     */
    public function convertLegacyFormat(string $val): string
    {
        $trimmed = trim($val);

        // 1. Sudah format satu digit positif "N" (misal: "9")
        if (preg_match('/^([1-9]\d*)$/', $trimmed)) {
            return $trimmed;
        }

        // 2. Sudah format rentang "A-B" (misal: "1-8" atau "1 - 8") dengan A <= B
        if (preg_match('/^([1-9]\d*)\s*-\s*([1-9]\d*)$/', $trimmed, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($a <= $b) {
                return "{$a}-{$b}";
            }
            Log::warning("Migration nameplate_settings: nilai rentang terbalik '{$val}' pada ID record dibiarkan.");
            return $trimmed;
        }

        // 3. Format legacy "posisi,panjang" (misal: "1,8" -> 1 + 8 - 1 = 8 -> "1-8"; "9,2" -> "9-10"; "11,1" -> "11")
        if (preg_match('/^([1-9]\d*)\s*,\s*([1-9]\d*)$/', $trimmed, $m)) {
            $start = (int) $m[1];
            $len   = (int) $m[2];
            $end   = $start + $len - 1;
            return $start === $end ? (string) $start : "{$start}-{$end}";
        }

        // 4. Format legacy "posisi:panjang" (misal: "1:8" -> "1-8")
        if (preg_match('/^([1-9]\d*)\s*:\s*([1-9]\d*)$/', $trimmed, $m)) {
            $start = (int) $m[1];
            $len   = (int) $m[2];
            $end   = $start + $len - 1;
            return $start === $end ? (string) $start : "{$start}-{$end}";
        }

        // Nilai tidak bisa dibaca atau format lain: biarkan apa adanya dan laporkan
        Log::info("Migration nameplate_settings: nilai '{$val}' tidak dapat dikonversi secara otomatis, dibiarkan apa adanya.");
        return $val;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Format lama ("posisi,panjang" dan "1:8") sudah deprecated sehingga down() tidak membalikkan data.
    }
};
