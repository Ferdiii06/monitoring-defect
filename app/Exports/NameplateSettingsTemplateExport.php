<?php

namespace App\Exports;

use App\Models\NameplateSetting;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NameplateSettingsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    public function title(): string
    {
        return 'Sheet1';
    }

    public function headings(): array
    {
        return [
            ['Nameplate Setting'],
            ['Carline', 'Name', 'Set', 'Date Format'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:D1');

        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            2 => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function array(): array
    {
        return [
            [
                'CONTOH-CARLINE-1',
                'Contoh Nameplate Line 1',
                NameplateSetting::SET_CARTON_BOX,
                'YYMMDD',
            ],
        ];
    }
}
