<?php

namespace App\Imports;

use App\Models\AssyNumber;
use App\Models\Carline;
use App\Models\CarType;
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
    protected array $warnings = [];
    protected ?EloquentCollection $allCarlines = null;
    protected ?EloquentCollection $allCarTypes = null;
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

        // Cache all carlines and carTypes for fast case-insensitive lookup
        if ($this->allCarlines === null) {
            $this->allCarlines = Carline::with('carType')->get();
        }
        if ($this->allCarTypes === null) {
            $this->allCarTypes = CarType::all();
        }

        $startOffset = $this->getChunkOffset() ?? 2;
        $chunkHasData = false;

        foreach ($rows as $index => $row) {
            $rowNum = $startOffset + $index;

            // Skip completely empty row (used range kosong / trailing empty rows)
            $rowArray = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
            $nonEmptyValues = array_filter($rowArray, function ($val) {
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

            // Extract family
            $rawFamily = $row['family'] ?? null;
            $family = filled($rawFamily) ? trim((string) $rawFamily) : null;

            // Extract conveyor
            $rawConveyor = $row['conveyor'] ?? $row['konveyor'] ?? null;
            $conveyor = filled($rawConveyor) ? trim((string) $rawConveyor) : null;

            // Extract carline (hanya fallback ke index [1] jika tidak ada kolom family atau conveyor)
            $rawCarline = $row['carline'] ?? $row['nama_carline'] ?? $row['carline_name'] ?? null;
            if ($rawCarline === null && isset($row[1]) && !isset($row['family']) && !isset($row['conveyor'])) {
                $rawCarline = $row[1];
            }
            $rawCarline = filled($rawCarline) ? trim((string) $rawCarline) : null;

            $matchedCarline = null;
            if ($rawCarline !== null && $rawCarline !== '') {
                $matchingCarlines = $this->allCarlines->filter(function ($c) use ($rawCarline) {
                    return strcasecmp(trim($c->name), $rawCarline) === 0;
                });

                if ($matchingCarlines->count() > 1) {
                    $this->skippedCount++;
                    $this->skippedRows[] = [
                        'row'         => $rowNum,
                        'assy_number' => $rawAssy,
                        'reason'      => "Nama carline '{$rawCarline}' ambigu karena cocok dengan {$matchingCarlines->count()} carline berbeda di master",
                    ];
                    continue;
                }

                $matchedCarline = $matchingCarlines->first();

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

            // Extract jenis_mobil
            $rawCarType = $row['jenis_mobil'] ?? $row['jenis mobil'] ?? $row['car_type'] ?? $row['car type'] ?? null;
            if ($rawCarType === null) {
                foreach ($row as $k => $v) {
                    if (is_string($k)) {
                        $normalizedKey = strtolower(trim(preg_replace('/[\s_-]+/', ' ', $k)));
                        if (in_array($normalizedKey, ['jenis mobil', 'car type', 'car types', 'cartype', 'jenismobil', 'tipe mobil', 'type mobil'], true)) {
                            $rawCarType = $v;
                            break;
                        }
                    }
                }
            }
            $rawCarType = filled($rawCarType) ? trim((string) $rawCarType) : null;

            $matchedCarType = null;
            if ($rawCarType !== null && $rawCarType !== '') {
                $matchedCarType = $this->allCarTypes->first(function ($ct) use ($rawCarType) {
                    return strcasecmp(trim($ct->name), $rawCarType) === 0;
                });

                if (!$matchedCarType) {
                    $this->skippedCount++;
                    $this->skippedRows[] = [
                        'row'         => $rowNum,
                        'assy_number' => $rawAssy,
                        'reason'      => "Jenis Mobil '{$rawCarType}' tidak ditemukan di master jenis mobil",
                    ];
                    continue;
                }
            }

            // Optional keterangan (hanya fallback ke index [2] jika tidak ada kolom conveyor)
            $keterangan = $row['keterangan'] ?? $row['notes'] ?? $row['description'] ?? null;
            if ($keterangan === null && isset($row[2]) && !isset($row['conveyor'])) {
                $keterangan = $row[2];
            }
            $keterangan = filled($keterangan) ? trim((string) $keterangan) : null;

            // Idempotent upsert based on normalized assy number
            $existing = AssyNumber::where('assy_number', $rawAssy)
                ->orWhere('assy_number', $normalizedAssy)
                ->orWhereRaw("REPLACE(REPLACE(UPPER(assy_number), '-', ''), ' ', '') = ?", [$normalizedAssy])
                ->first();

            // Peringatan jika jenis mobil dari Excel berbeda dari hasil tebakan kode conveyor
            $conveyorToCheck = $conveyor ?? $existing?->conveyor;
            if ($matchedCarType !== null && filled($conveyorToCheck)) {
                $guessedCarType = CarType::resolveFromConveyor($conveyorToCheck, $this->allCarlines);
                if ($guessedCarType !== null && $guessedCarType->id !== $matchedCarType->id) {
                    $this->warnings[] = [
                        'row'         => $rowNum,
                        'assy_number' => $rawAssy,
                        'reason'      => "Jenis Mobil '{$matchedCarType->name}' berbeda dari hasil tebakan conveyor '{$conveyorToCheck}' ('{$guessedCarType->name}')",
                        'message'     => "Jenis Mobil '{$matchedCarType->name}' pada baris {$rowNum} (Assy {$rawAssy}) berbeda dari hasil tebakan conveyor '{$conveyorToCheck}' ('{$guessedCarType->name}'). Nilai Excel tetap disimpan.",
                    ];
                }
            }

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

                if ($matchedCarType !== null) {
                    $updateData['car_type_id'] = $matchedCarType->id;
                }

                $existing->update($updateData);
            } else {
                AssyNumber::create([
                    'assy_number' => $rawAssy,
                    'carline_id'  => $matchedCarline?->id,
                    'car_type_id' => $matchedCarType?->id,
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

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
