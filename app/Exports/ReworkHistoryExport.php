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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReworkHistoryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
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

    public function columnFormats(): array
    {
        return [
            'E' => 'dd/mm/yyyy hh:mm:ss',
            'F' => 'dd/mm/yyyy hh:mm:ss',
            'G' => '0.00',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
        $highestRow = max(1, $sheet->getHighestRow());

        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->freezePane('A2');

        return [
            "A1:{$lastColumn}1" => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
            ],
            "A1:{$lastColumn}{$highestRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
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

        $waktuInVal = $waktuIn ? Date::dateTimeToExcel(Carbon::parse($waktuIn)) : '-';

        if ($status === 'OPEN' || !$row->waktu_out) {
            $waktuOutVal = '-';
            $durasiReworkMenit = '-';
        } else {
            $waktuOutVal = Date::dateTimeToExcel(Carbon::parse($row->waktu_out));
            if ($waktuIn) {
                $cIn = Carbon::parse($waktuIn);
                $cOut = Carbon::parse($row->waktu_out);
                $diffSeconds = max(0, $cIn->diffInSeconds($cOut));
                $durasiReworkMenit = round($diffSeconds / 60, 2);
            } else {
                $durasiReworkMenit = '-';
            }
        }

        $carline = filled($row->jenis_mobil)
            ? $row->jenis_mobil
            : ($row->carline?->carType?->name ?: ($row->carline?->name ?: '-'));
        $conveyor = filled($row->conveyor)
            ? $row->conveyor
            : ($row->carline?->name ?: '-');

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
            $waktuInVal,
            $waktuOutVal,
            $durasiReworkMenit,
            $row->shift ?? '-',
            $row->user_name ?? '-',
            filled($row->kanban_assy) ? $row->kanban_assy : '-',
            $row->kanban_serial !== null ? $row->kanban_serial : '-',
            $statusKanbanLabel,
            filled($row->nameplate_lama) ? $row->nameplate_lama : '-',
            filled($row->nameplate_baru) ? $row->nameplate_baru : '-',
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
