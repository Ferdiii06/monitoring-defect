<?php

namespace App\Imports;

use App\Models\AssyNumber;
use App\Models\Carline;
use App\Support\KanbanCodeParser;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssyNumberImport implements ToCollection, WithHeadingRow
{
    protected int $successCount = 0;
    protected int $skippedCount = 0;
    protected array $skippedRows = [];

    public function collection(Collection $rows)
    {
        // Cache all carlines for fast case-insensitive lookup
        $allCarlines = Carline::all();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +1 for 0-index, +1 for heading row

            // Extract assy_number
            $rawAssy = $row['assy_number'] ?? $row['assy'] ?? $row['nomor_assy'] ?? null;
            if ($rawAssy === null && isset($row[0])) {
                $rawAssy = $row[0];
            }
            $rawAssy = filled($rawAssy) ? trim((string) $rawAssy) : null;

            if ($rawAssy === null) {
                $this->skippedCount++;
                $this->skippedRows[] = [
                    'row'         => $rowNum,
                    'assy_number' => '-',
                    'reason'      => 'Nomor assy kosong',
                ];
                continue;
            }

            $normalizedAssy = KanbanCodeParser::normalizeAssyNumber($rawAssy);
            if ($normalizedAssy === null) {
                $this->skippedCount++;
                $this->skippedRows[] = [
                    'row'         => $rowNum,
                    'assy_number' => $rawAssy,
                    'reason'      => 'Format nomor assy tidak valid',
                ];
                continue;
            }

            // Extract carline
            $rawCarline = $row['carline'] ?? $row['nama_carline'] ?? $row['carline_name'] ?? null;
            if ($rawCarline === null && isset($row[1])) {
                $rawCarline = $row[1];
            }
            $rawCarline = filled($rawCarline) ? trim((string) $rawCarline) : null;

            $matchedCarline = null;
            if ($rawCarline !== null && $rawCarline !== '') {
                $matchedCarline = $allCarlines->first(function ($c) use ($rawCarline) {
                    return strcasecmp(trim($c->name), $rawCarline) === 0;
                });

                if (!$matchedCarline) {
                    $this->skippedCount++;
                    $this->skippedRows[] = [
                        'row'         => $rowNum,
                        'assy_number' => $rawAssy,
                        'reason'      => "Carline '{$rawCarline}' tidak ditemukan di master carline",
                    ];
                    continue;
                }
            }

            // Optional keterangan
            $keterangan = $row['keterangan'] ?? $row['notes'] ?? $row['description'] ?? null;
            if ($keterangan === null && isset($row[2])) {
                $keterangan = $row[2];
            }
            $keterangan = filled($keterangan) ? trim((string) $keterangan) : null;

            // Idempotent upsert based on normalized assy number
            $existing = AssyNumber::where('assy_number', $rawAssy)
                ->orWhere('assy_number', $normalizedAssy)
                ->orWhereRaw("REPLACE(REPLACE(UPPER(assy_number), '-', ''), ' ', '') = ?", [$normalizedAssy])
                ->first();

            if ($existing) {
                $existing->update([
                    'assy_number' => $rawAssy,
                    'carline_id'  => $matchedCarline ? $matchedCarline->id : $existing->carline_id,
                    'keterangan'  => $keterangan ?? $existing->keterangan,
                ]);
            } else {
                AssyNumber::create([
                    'assy_number' => $rawAssy,
                    'carline_id'  => $matchedCarline?->id,
                    'keterangan'  => $keterangan,
                ]);
            }

            $this->successCount++;
        }
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
