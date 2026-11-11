<?php

namespace App\Imports;

use App\Models\NameplateSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class NameplateSettingsImport implements ToCollection
{
    protected int $addedCount = 0;
    protected int $updatedCount = 0;
    protected int $failedCount = 0;
    protected array $failedRows = [];

    public function collection(Collection $rows): void
    {
        // 1. Deteksi baris header otomatis pada 5 baris pertama
        $headerRowIndex = null;
        $columnMap = [];

        $maxSearch = min(5, $rows->count());
        for ($i = 0; $i < $maxSearch; $i++) {
            $candidateRow = $rows[$i];
            $hasCarline = false;
            $hasName    = false;
            $tempMap    = [];

            foreach ($candidateRow as $colIdx => $cellVal) {
                $normalizedHeader = strtolower(trim((string) $cellVal));
                if ($normalizedHeader === '') {
                    continue;
                }

                // Match Carline
                if (in_array($normalizedHeader, ['carline', 'car_line', 'carline code', 'carline_code', 'kode carline'], true)) {
                    $hasCarline = true;
                    $tempMap['carline'] = $colIdx;
                } elseif (in_array($normalizedHeader, ['name', 'nama', 'carline name'], true)) {
                    $hasName = true;
                    $tempMap['name'] = $colIdx;
                } elseif ($normalizedHeader === 'set') {
                    $tempMap['set'] = $colIdx;
                } elseif (in_array($normalizedHeader, ['date format', 'date_format', 'format tanggal', 'dateformat'], true)) {
                    $tempMap['date_format'] = $colIdx;
                } elseif (in_array($normalizedHeader, ['assy customer', 'assy_customer', 'customer assy', 'customer_assy'], true)) {
                    $tempMap['assy_customer'] = $colIdx;
                } elseif (in_array($normalizedHeader, ['assy no.', 'assy no', 'assy_no', 'nomor assy', 'nomor_assy', 'assy'], true)) {
                    $tempMap['assy_no'] = $colIdx;
                } elseif ($normalizedHeader === 'suffix') {
                    $tempMap['suffix'] = $colIdx;
                } elseif ($normalizedHeader === 'level') {
                    $tempMap['level'] = $colIdx;
                } elseif (in_array($normalizedHeader, ['date', 'date_field', 'tanggal'], true)) {
                    $tempMap['date_field'] = $colIdx;
                } elseif (in_array($normalizedHeader, ['sequence label', 'sequence_label', 'sequence', 'serial'], true)) {
                    $tempMap['sequence_label'] = $colIdx;
                }
            }

            if ($hasCarline && $hasName) {
                $headerRowIndex = $i;
                $columnMap = $tempMap;
                break;
            }
        }

        if ($headerRowIndex === null) {
            $this->failedCount++;
            $this->failedRows[] = [
                'row'     => 1,
                'carline' => '-',
                'reason'  => 'Header kolom (Carline dan Name) tidak ditemukan pada 5 baris pertama file.',
            ];
            return;
        }

        // 2. Baca data mulai dari baris setelah header
        $totalRows = $rows->count();
        for ($rowIndex = $headerRowIndex + 1; $rowIndex < $totalRows; $rowIndex++) {
            $row = $rows[$rowIndex];
            $excelRowNum = $rowIndex + 1;

            // Lewati baris yang sepenuhnya kosong
            $isEmpty = true;
            foreach ($row as $val) {
                if (filled($val)) {
                    $isEmpty = false;
                    break;
                }
            }
            if ($isEmpty) {
                continue;
            }

            $rawCarline    = isset($columnMap['carline']) ? ($row[$columnMap['carline']] ?? null) : null;
            $rawName       = isset($columnMap['name']) ? ($row[$columnMap['name']] ?? null) : null;
            $rawSet        = isset($columnMap['set']) ? ($row[$columnMap['set']] ?? null) : null;
            $rawDateFormat = isset($columnMap['date_format']) ? ($row[$columnMap['date_format']] ?? null) : null;

            // Validasi 1: Carline
            $carlineVal = filled($rawCarline) ? strtoupper(trim((string) $rawCarline)) : null;
            if ($carlineVal === null || $carlineVal === '') {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'     => $excelRowNum,
                    'carline' => '-',
                    'reason'  => 'Kolom Carline Code wajib diisi.',
                ];
                continue;
            }

            if (mb_strlen($carlineVal) > 50) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'     => $excelRowNum,
                    'carline' => $carlineVal,
                    'reason'  => 'Kolom Carline Code maksimal 50 karakter.',
                ];
                continue;
            }

            // Validasi 2: Name
            $nameVal = filled($rawName) ? trim((string) $rawName) : null;
            if ($nameVal === null || $nameVal === '') {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'     => $excelRowNum,
                    'carline' => $carlineVal,
                    'reason'  => 'Kolom Name wajib diisi.',
                ];
                continue;
            }

            if (mb_strlen($nameVal) > 100) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'     => $excelRowNum,
                    'carline' => $carlineVal,
                    'reason'  => 'Kolom Name maksimal 100 karakter.',
                ];
                continue;
            }

            // Validasi 3: Set
            $setVal = filled($rawSet) ? strtoupper(trim((string) $rawSet)) : null;
            if (!in_array($setVal, NameplateSetting::SET_OPTIONS, true)) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'     => $excelRowNum,
                    'carline' => $carlineVal,
                    'reason'  => 'Set harus salah satu dari: ' . implode(', ', NameplateSetting::SET_OPTIONS) . '.',
                ];
                continue;
            }

            // Validasi 4: Date Format
            $dateVal = filled($rawDateFormat) ? strtoupper(trim((string) $rawDateFormat)) : null;
            if ($dateVal === null || $dateVal === '' || !preg_match('/^[YMD]+$/i', $dateVal) || mb_strlen($dateVal) > 20) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'     => $excelRowNum,
                    'carline' => $carlineVal,
                    'reason'  => 'Format Date Format hanya boleh berisi kombinasi karakter Y, M, dan D (contoh: YYMMDD) maksimal 20 karakter.',
                ];
                continue;
            }

            // Kolom opsional: hanya diproses jika kolom tersebut ada di file
            $hasAssyCustomerCol = array_key_exists('assy_customer', $columnMap);
            $hasAssyNoCol       = array_key_exists('assy_no', $columnMap);
            $hasSuffixCol       = array_key_exists('suffix', $columnMap);
            $hasLevelCol        = array_key_exists('level', $columnMap);
            $hasDateFieldCol    = array_key_exists('date_field', $columnMap);
            $hasSeqLabelCol     = array_key_exists('sequence_label', $columnMap);

            $rawAssyCustomer = $hasAssyCustomerCol ? ($row[$columnMap['assy_customer']] ?? null) : null;
            $rawAssyNo       = $hasAssyNoCol ? ($row[$columnMap['assy_no']] ?? null) : null;
            $rawSuffix       = $hasSuffixCol ? ($row[$columnMap['suffix']] ?? null) : null;
            $rawLevel        = $hasLevelCol ? ($row[$columnMap['level']] ?? null) : null;
            $rawDate         = $hasDateFieldCol ? ($row[$columnMap['date_field']] ?? null) : null;
            $rawSeq          = $hasSeqLabelCol ? ($row[$columnMap['sequence_label']] ?? null) : null;

            $assyCustomerBool = false;
            if (filled($rawAssyCustomer)) {
                $lower = strtolower(trim((string) $rawAssyCustomer));
                $assyCustomerBool = in_array($lower, ['yes', '1', 'true', 'ya', 'y'], true);
            }

            $assyNoVal  = filled($rawAssyNo) ? mb_substr(trim((string) $rawAssyNo), 0, 100) : null;
            $suffixVal  = filled($rawSuffix) ? mb_substr(trim((string) $rawSuffix), 0, 100) : null;
            $levelVal   = filled($rawLevel) ? mb_substr(trim((string) $rawLevel), 0, 100) : null;
            $dateFldVal = filled($rawDate) ? mb_substr(trim((string) $rawDate), 0, 100) : null;
            $seqVal     = filled($rawSeq) ? mb_substr(trim((string) $rawSeq), 0, 100) : null;

            // 3. Upsert dalam DB transaction
            try {
                DB::transaction(function () use (
                    $carlineVal,
                    $nameVal,
                    $setVal,
                    $dateVal,
                    $hasAssyCustomerCol,
                    $assyCustomerBool,
                    $hasAssyNoCol,
                    $assyNoVal,
                    $hasSuffixCol,
                    $suffixVal,
                    $hasLevelCol,
                    $levelVal,
                    $hasDateFieldCol,
                    $dateFldVal,
                    $hasSeqLabelCol,
                    $seqVal
                ) {
                    $setting = NameplateSetting::where('carline', $carlineVal)->first();

                    if ($setting) {
                        // Update data utama
                        $payload = [
                            'name'        => $nameVal,
                            'set'         => $setVal,
                            'date_format' => $dateVal,
                        ];

                        // Hanya update kolom opsional bila kolom tersebut ada di file
                        if ($hasAssyCustomerCol) {
                            $payload['assy_customer'] = $assyCustomerBool;
                        }
                        if ($hasAssyNoCol) {
                            $payload['assy_no'] = $assyNoVal;
                        }
                        if ($hasSuffixCol) {
                            $payload['suffix'] = $suffixVal;
                        }
                        if ($hasLevelCol) {
                            $payload['level'] = $levelVal;
                        }
                        if ($hasDateFieldCol) {
                            $payload['date_field'] = $dateFldVal;
                        }
                        if ($hasSeqLabelCol) {
                            $payload['sequence_label'] = $seqVal;
                        }

                        $setting->update($payload);
                        $this->updatedCount++;
                    } else {
                        // Insert data baru
                        $payload = [
                            'carline'        => $carlineVal,
                            'name'           => $nameVal,
                            'set'            => $setVal,
                            'date_format'    => $dateVal,
                            'assy_customer'  => $hasAssyCustomerCol ? $assyCustomerBool : false,
                            'assy_no'        => $hasAssyNoCol ? $assyNoVal : null,
                            'suffix'         => $hasSuffixCol ? $suffixVal : null,
                            'level'          => $hasLevelCol ? $levelVal : null,
                            'date_field'     => $hasDateFieldCol ? $dateFldVal : null,
                            'sequence_label' => $hasSeqLabelCol ? $seqVal : null,
                        ];

                        NameplateSetting::create($payload);
                        $this->addedCount++;
                    }
                });
            } catch (\Throwable $e) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row'     => $excelRowNum,
                    'carline' => $carlineVal,
                    'reason'  => 'Gagal menyimpan data: ' . $e->getMessage(),
                ];
            }
        }
    }

    public function getAddedCount(): int
    {
        return $this->addedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
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
