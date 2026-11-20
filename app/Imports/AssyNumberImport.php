<?php

namespace App\Imports;

use App\Models\AssyNumber;
use App\Models\Carline;
use App\Support\KanbanCodeParser;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\RemembersChunkOffset;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssyNumberImport implements ToCollection, WithHeadingRow, WithChunkReading, WithMultipleSheets
{
    use RemembersChunkOffset;

    protected int $successCount = 0;
    protected int $skippedCount = 0;
    protected array $skippedRows = [];
    protected ?EloquentCollection $allCarlines = null;
    protected bool $stopProcessing = false;

    public function chunkSize(): int
    {
        return 500;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function collection(Collection $rows)
    {
        if ($this->stopProcessing) {
            return;
        }

        // Cache all carlines for fast case-insensitive lookup
        if ($this->allCarlines === null) {
            $this->allCarlines = Carline::all();
        }

        $startOffset = $this->getChunkOffset() ?? 2;
        $chunkHasData = false;

        foreach ($rows as $index => $row) {
            $rowNum = $startOffset + $index;

            // Skip completely empty row (used range kosong / trailing empty rows)
            $nonEmptyValues = array_filter($row->toArray(), function ($val) {
                return $val !== null && trim((string) $val) !== '';
            });

            if (empty($nonEmptyValues)) {
                continue;
            }

            $chunkHasData = true;

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
                $matchedCarline = $this->allCarlines->first(function ($c) use ($rawCarline) {
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

            // Extract family (only 'family')
            $rawFamily = $row['family'] ?? null;
            $family = filled($rawFamily) ? trim((string) $rawFamily) : null;

            // Extract conveyor (only 'conveyor' or 'konveyor')
            $rawConveyor = $row['conveyor'] ?? $row['konveyor'] ?? null;
            $conveyor = filled($rawConveyor) ? trim((string) $rawConveyor) : null;

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
                $updateData = [
                    'assy_number' => $rawAssy,
                    'carline_id'  => $matchedCarline ? $matchedCarline->id : $existing->carline_id,
                    'keterangan'  => $keterangan ?? $existing->keterangan,
                ];

                if ($family !== null) {
                    $updateData['family'] = $family;
                }

                if ($conveyor !== null) {
                    $updateData['conveyor'] = $conveyor;
                }

                $existing->update($updateData);
            } else {
                AssyNumber::create([
                    'assy_number' => $rawAssy,
                    'carline_id'  => $matchedCarline?->id,
                    'family'      => $family,
                    'conveyor'    => $conveyor,
                    'keterangan'  => $keterangan,
                ]);
            }

            $this->successCount++;
        }

        // If an entire chunk of 500 rows is empty after having processed real data, stop further chunks
        if (!$chunkHasData && ($this->successCount > 0 || $this->skippedCount > 0)) {
            $this->stopProcessing = true;
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
