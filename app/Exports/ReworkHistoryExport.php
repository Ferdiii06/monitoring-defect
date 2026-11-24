<?php

namespace App\Exports;

use App\Http\Controllers\Admin\ReworkHistoryController;
use App\Models\AreaDitemukan;
use App\Models\Defect;
use App\Support\KanbanCodeParser;
use App\Support\SubDefectHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReworkHistoryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected $request;

    protected int $rowNumber = 0;
    protected int $reworkNumber = 0;
    protected ?string $lastGroupId = null;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));

        return [
            1 => ['font' => ['bold' => true]],
            "A1:{$lastColumn}1" => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function query()
    {
        return ReworkHistoryController::baseQuery($this->request)
            ->with(['defectType', 'subDefectType', 'carline.carType'])
            ->orderBy(DB::raw('COALESCE(waktu_in, waktu)'), 'asc')
            ->orderBy('rework_group_id', 'asc')
            ->orderBy('id', 'asc');
    }

    public function headings(): array
    {
        return [
            'No',
            'No Rework',
            'Status Rework',
            'Input Susulan',
            'Waktu In',
            'Waktu Out',
            'Waktu Rework (Menit)',
            'Shift',
            'Operator Rework',
            'Scan Kanban Assy',
            'No Urut Kanban',
            'Status Kanban',
            'No Nameplate Lama',
            'No Nameplate Baru',
            'Jenis Mobil',
            'Konveyor',
            'Kode Defect',
            'Jenis Defect',
            'Nama Sub Defect',
            'Quantity',
            'Area Ditemukan',
            'Tipe Area',
            'No Defective Tag',
            '4M Change Status',
            'Ujung (#End)',
            'Carton Box / No Box',
            'Posisi & Detail Rework',
            'F C/H',
            'F C/W',
            'R C/H',
            'R C/W',
            'Group ID',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        if ($this->lastGroupId === null || $row->rework_group_id !== $this->lastGroupId) {
            $this->lastGroupId = $row->rework_group_id;
            $this->reworkNumber++;
        }

        $no = $this->rowNumber;
        $noRework = $this->reworkNumber;

        $code = $row->subDefectType?->code ?? SubDefectHelper::deriveCode($row->jenis_sub_defect) ?? '-';
        $waktuIn = $row->waktu_in ?? $row->waktu;
        $status = in_array($row->status_rework, ['Selesai', 'CLOSE'], true) ? 'CLOSE' : 'OPEN';

        $waktuInFormatted = $waktuIn ? Carbon::parse($waktuIn)->format('d/m/Y H:i') : '-';
        if ($status === 'OPEN') {
            $waktuOutFormatted = '-';
        } else {
            $waktuOutFormatted = $row->waktu_out ? Carbon::parse($row->waktu_out)->format('d/m/Y H:i') : '-';
        }

        $carline = $row->jenis_mobil ?: ($row->carline?->carType?->name ?: $row->carline?->name) ?: '-';
        $conveyor = $row->conveyor ?: ($row->carline?->name) ?: '-';

        $areaModel = $row->area_ditemukan ? AreaDitemukan::where('name', $row->area_ditemukan)->first() : null;
        if ($areaModel && $areaModel->kategori === AreaDitemukan::KATEGORI_FINAL_ASSY) {
            $tipeAreaLabel = ($areaModel->tipe === AreaDitemukan::TIPE_ASSEMBLY_DEFECT) ? 'Assembly Defect' : 'Internal Defect';
        } else {
            $tipeAreaLabel = '-';
        }

        $effectiveKanbanStatus = $row->kanban_status;
        if ($effectiveKanbanStatus === null && filled($row->kanban_assy)) {
            $effectiveKanbanStatus = KanbanCodeParser::evaluateKanban($row->kanban_assy)['kanban_status'] ?? null;
        }

        $statusKanbanLabel = match ($effectiveKanbanStatus) {
            'terdaftar'            => 'Terdaftar',
            'tidak_terdaftar'      => 'Tidak Terdaftar',
            'family_beda'          => 'Family Beda',
            'conveyor_beda'        => 'Conveyor Beda',
            'format_tidak_dikenal' => 'Format Tidak Dikenal',
            default                => '-',
        };

        return [
            $no,
            $noRework,
            $status,
            $row->is_susulan ? 'Ya' : 'Tidak',
            $waktuInFormatted,
            $waktuOutFormatted,
            $row->durasi_rework_menit !== null ? $row->durasi_rework_menit : '-',
            $row->shift ?? '-',
            $row->user_name ?? '-',
            $row->kanban_assy ?? '-',
            $row->kanban_serial !== null ? $row->kanban_serial : '-',
            $statusKanbanLabel,
            $row->nameplate_lama ?? '-',
            $row->nameplate_baru ?? '-',
            $carline,
            $conveyor,
            $code,
            $row->jenis_defect ?? '-',
            $row->jenis_sub_defect ?? '-',
            $row->quantity ?? 1,
            $row->area_ditemukan ?? '-',
            $tipeAreaLabel,
            $row->no_defective_tag ?? '-',
            $row->status_4m ?? 'NO',
            $row->ujung ?? '-',
            $row->no_box ?? '-',
            $row->posisi_detail_rework ?? '-',
            $row->fc_h ?? '-',
            $row->fc_w ?? '-',
            $row->rc_h ?? '-',
            $row->rc_w ?? '-',
            $row->rework_group_id ?? '-',
        ];
    }
}
