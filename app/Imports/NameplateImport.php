<?php

namespace App\Imports;

use App\Models\Nameplate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NameplateImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $addedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    protected int $unmatchedPatternCount = 0;
    protected int $failedCount = 0;
    protected array $failedRows = [];

    /**
     * Known column names to be excluded from raw_payload
     */
    protected const KNOWN_COLUMNS = [
        'nameplate_code', 'code', 'nameplate', 'qr_code', 'kode', 'kode_nameplate',
        'assy_number', 'nomor_assy', 'suffix', 'date_code', 'production_date',
        'serial_number', 'serial', 'keterangan', 'notes', 'description',
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +1 for 0-based index, +1 for heading row

            // 1. Ekstrak raw code
            $rawCode = $row['nameplate_code']
                ?? $row['code']
                ?? $row['nameplate']
                ?? $row['qr_code']
                ?? $row['kode']
                ?? $row['kode_nameplate']
                ?? ($row[0] ?? null);

            $rawCode = filled($rawCode) ? trim((string) $rawCode) : null;

            if ($rawCode === null) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'    => $rowNum,
                    'code'   => '-',
                    'reason' => 'Kode nameplate kosong',
                ];
                continue;
            }

            // 2. Normalisasi kode
            $normCode = Nameplate::normalizeCode($rawCode);
            if ($normCode === null) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'    => $rowNum,
                    'code'   => $rawCode,
                    'reason' => 'Format kode nameplate tidak valid setelah normalisasi',
                ];
                continue;
            }

            // 3. Ekstrak field menggunakan regex standar
            $extracted = Nameplate::extractFromCode($normCode);
            if (!$extracted['is_pattern_matched']) {
                $this->unmatchedPatternCount++;
            }

            // 4. Fallback jika ada kolom eksplisit di CSV
            $assy = filled($row['assy_number'] ?? $row['nomor_assy'] ?? null)
                ? trim((string)($row['assy_number'] ?? $row['nomor_assy']))
                : $extracted['assy_number'];

            $keterangan = filled($row['keterangan'] ?? $row['notes'] ?? null)
                ? trim((string)($row['keterangan'] ?? $row['notes']))
                : null;

            // 5. Ekstrak kolom tak dikenal ke raw_payload
            $rawPayload = [];
            foreach ($row as $colKey => $colVal) {
                if (is_numeric($colKey)) {
                    continue;
                }
                if (!in_array(strtolower($colKey), self::KNOWN_COLUMNS, true) && filled($colVal)) {
                    $rawPayload[$colKey] = is_string($colVal) ? trim($colVal) : $colVal;
                }
            }
            $payloadToStore = count($rawPayload) > 0 ? $rawPayload : null;

            // 6. Idempotent Upsert
            try {
                $existing = Nameplate::where('nameplate_code', $normCode)->first();

                if ($existing) {
                    // JANGAN menimpa baris yang pernah diedit manual!
                    if ($existing->is_manual_edit) {
                        $this->skippedCount++;
                        continue;
                    }

                    $existing->update([
                        'assy_number'     => $assy ?? $existing->assy_number,
                        'suffix'          => $extracted['suffix'] ?? $existing->suffix,
                        'date_code'       => $extracted['date_code'] ?? $existing->date_code,
                        'production_date' => $extracted['production_date'] ?? $existing->production_date,
                        'serial_number'   => $extracted['serial_number'] ?? $existing->serial_number,
                        'keterangan'      => $keterangan ?? $existing->keterangan,
                        'source'          => Nameplate::SOURCE_IMPORT,
                        'is_manual_edit'  => false,
                        'raw_payload'     => $payloadToStore ?? $existing->raw_payload,
                    ]);

                    $this->updatedCount++;
                } else {
                    Nameplate::create([
                        'nameplate_code'  => $normCode,
                        'assy_number'     => $assy,
                        'suffix'          => $extracted['suffix'],
                        'date_code'       => $extracted['date_code'],
                        'production_date' => $extracted['production_date'],
                        'serial_number'   => $extracted['serial_number'],
                        'keterangan'      => $keterangan,
                        'source'          => Nameplate::SOURCE_IMPORT,
                        'is_manual_edit'  => false,
                        'raw_payload'     => $payloadToStore,
                    ]);

                    $this->addedCount++;
                }
            } catch (\Throwable $e) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'    => $rowNum,
                    'code'   => $normCode,
                    'reason' => 'Database error: ' . $e->getMessage(),
                ];
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getAddedCount(): int
    {
        return $this->addedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getUnmatchedPatternCount(): int
    {
        return $this->unmatchedPatternCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getFailedRows(): array
    {
        return $this->failedRows;
    }
}

