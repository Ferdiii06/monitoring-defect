<?php

namespace App\Exports;

use App\Models\Defect;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class PreAssyExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Defect::where('jenis_assy', 'Pre Assy');

        $dateRange = $this->request->input('date_range');
        $selectedDefect = $this->request->input('defect');
        $selectedLine = $this->request->input('line');
        $selectedConveyor = $this->request->input('conveyor');

        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                $query->whereBetween('waktu', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay()
                ]);
            } else if (count($dates) === 1) {
                $query->whereBetween('waktu', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[0])->endOfDay()
                ]);
            }
        }

        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }

        if ($selectedLine && $selectedLine !== 'all') {
            $query->where('jenis_mobil', $selectedLine);
        }

        if ($selectedConveyor && $selectedConveyor !== 'all') {
            $query->where('conveyor', $selectedConveyor);
        }

        return $query->orderBy('waktu', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Waktu',
            'User',
            'Shift',
            'Jenis Assy',
            'Data Mobil',
            'Konveyor',
            'Jenis Defect',
            'Jenis Sub Defect',
            'No Terminal',
            'No Mesin',
            'Quantity',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            Carbon::parse($row->waktu)->format('Y-m-d H:i:s'),
            $row->user_name,
            $row->shift ?? '-',
            $row->jenis_assy,
            $row->line_conveyor,
            $row->conveyor,
            $row->jenis_defect,
            $row->jenis_sub_defect,
            $row->no_terminal,
            $row->no_mesin,
            $row->quantity,
        ];
    }
}
