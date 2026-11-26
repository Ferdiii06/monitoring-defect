<?php

namespace App\Imports;

use App\Models\PreAssyMachine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PreAssyMachineImport implements ToCollection, WithMultipleSheets
{
    public const MAX_ROWS = 1000;

    protected int $carlineId;
    protected int $successCount = 0;
    protected int $skippedCount = 0;
    protected array $skippedRows = [];

    public function __construct(int $carlineId)
    {
        $this->carlineId = $carlineId;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw new \InvalidArgumentException('File Excel kosong. Header "No Mesin" tidak ditemukan.');
        }

        $headerRow = $rows->first();
        $headerColIndex = $this->findHeaderColumnIndex($headerRow);

        if ($headerColIndex === null) {
            throw new \InvalidArgumentException('Header "No Mesin" tidak ditemukan pada baris pertama sheet.');
        }

        $dataRows = $rows->slice(1)->values();

        $entries = [];
        foreach ($dataRows as $index => $row) {
            $rowNum = $index + 2; // Baris 1 adalah header
            $raw = $row[$headerColIndex] ?? null;
            $name = $this->normalizeCellValue($raw);
            if ($name === '') {
                continue;
            }
            $entries[] = ['rowNum' => $rowNum, 'name' => $name];
        }

        if (count($entries) > self::MAX_ROWS) {
            throw new \InvalidArgumentException(
                'Jumlah baris data melebihi batas maksimal (' . self::MAX_ROWS . ' baris per file).'
            );
        }

        DB::transaction(function () use ($entries) {
            $existingLower = PreAssyMachine::where('carline_id', $this->carlineId)
                ->pluck('name')
                ->map(fn ($n) => mb_strtolower(trim((string) $n)))
                ->flip()
                ->all();

            $seenInFile = [];

            foreach ($entries as $entry) {
                $rowNum = $entry['rowNum'];
                $name = $entry['name'];
                $lower = mb_strtolower($name);

                if (isset($existingLower[$lower])) {
                    $this->skippedCount++;
                    $this->skippedRows[] = "Baris {$rowNum}: \"{$name}\" — sudah ada di carline ini";
                    continue;
                }

                if (isset($seenInFile[$lower])) {
                    $this->skippedCount++;
                    $this->skippedRows[] = "Baris {$rowNum}: \"{$name}\" — kembar di dalam file";
                    continue;
                }

                PreAssyMachine::create([
                    'carline_id' => $this->carlineId,
                    'name'       => $name,
                    'is_active'  => true,
                ]);

                $seenInFile[$lower] = true;
                $this->successCount++;
            }
        });
    }

    protected function findHeaderColumnIndex($headerRow): ?int
    {
        if (!is_iterable($headerRow)) {
            return null;
        }

        foreach ($headerRow as $colIndex => $cell) {
            $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $cell)));
            if ($normalized === 'no mesin') {
                return (int) $colIndex;
            }
        }

        return null;
    }

    protected function normalizeCellValue($raw): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        if ((is_int($raw) || is_float($raw)) && floor((float) $raw) == (float) $raw) {
            return (string) (int) $raw;
        }

        return trim((string) $raw);
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }
}
