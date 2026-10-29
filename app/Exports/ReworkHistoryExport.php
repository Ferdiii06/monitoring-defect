<?php

namespace App\Exports;

use App\Http\Controllers\Admin\ReworkHistoryController;
use App\Models\Defect;
use App\Support\SubDefectHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReworkHistoryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function query()
    {
        return ReworkHistoryController::baseQuery($this->request)
            ->with(['defectType', 'subDefectType'])
            ->orderByDesc(DB::raw('COALESCE(waktu_in, waktu)'))
            ->orderBy('rework_group_id')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Group ID',
            'Waktu In',
            'Waktu Out',
            'Status Rework',
            'Durasi (Menit)',
            'Kanban Assy',
            'No Nameplate Lama',
            'No Nameplate Baru',
            'Operator',
            'Shift',
            'Kode Defect',
            'Jenis Defect',
            'Sub Defect',
            'Quantity',
            'Area Ditemukan',
        ];
    }

    public function map($row): array
    {
        $code = $row->subDefectType?->code ?? SubDefectHelper::deriveCode($row->jenis_sub_defect) ?? '-';
        $waktuIn = $row->waktu_in ?? $row->waktu;

        return [
            $row->id,
            $row->rework_group_id ?? '-',
            $waktuIn ? Carbon::parse($waktuIn)->format('Y-m-d H:i:s') : '-',
            $row->waktu_out ? Carbon::parse($row->waktu_out)->format('Y-m-d H:i:s') : '-',
            $row->status_rework ?? 'Berjalan',
            $row->durasi_rework_menit !== null ? $row->durasi_rework_menit : '-',
            $row->kanban_assy ?? '-',
            $row->nameplate_lama ?? '-',
            $row->nameplate_baru ?? '-',
            $row->user_name ?? '-',
            $row->shift ?? '-',
            $code,
            $row->jenis_defect ?? '-',
            $row->jenis_sub_defect ?? '-',
            $row->quantity ?? 1,
            $row->area_ditemukan ?? '-',
        ];
    }
}
