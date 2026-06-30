<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class LogSystemExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = ActivityLog::query();

        $dateRange = $this->request->input('date_range');
        $selectedAction = $this->request->input('action');
        $selectedDefect = $this->request->input('defect');

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

        if ($selectedAction && $selectedAction !== 'all') {
            $query->where('jenis_aksi', $selectedAction);
        }

        if ($selectedDefect && $selectedDefect !== 'all') {
            $query->where('jenis_defect', $selectedDefect);
        }

        return $query->orderBy('waktu', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Waktu',
            'User',
            'Jenis Aksi',
            'Aktivitas',
            'Jenis Defect',
            'IP Address',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            Carbon::parse($row->waktu)->format('Y-m-d H:i:s'),
            $row->user_name,
            $row->jenis_aksi,
            $row->aktivitas,
            $row->jenis_defect,
            $row->ip_address,
        ];
    }
}
