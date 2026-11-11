<?php

namespace App\Exports;

use App\Models\NameplateSetting;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NameplateSettingsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    protected ?string $searchTerm;
    protected string $sortBy;
    protected string $order;

    public function __construct(?string $searchTerm = null, string $sortBy = 'carline', string $order = 'asc')
    {
        $this->searchTerm = $searchTerm;
        $this->sortBy     = $sortBy ?: 'carline';
        $this->order      = strtolower($order) === 'desc' ? 'desc' : 'asc';
    }

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

    public function query()
    {
        return NameplateSetting::query()
            ->search($this->searchTerm)
            ->sortBy($this->sortBy, $this->order);
    }

    public function map($setting): array
    {
        return [
            $setting->carline,
            $setting->name,
            $setting->set,
            $setting->date_format,
        ];
    }
}
