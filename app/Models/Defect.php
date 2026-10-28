<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Defect extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'waktu',
        'user_name',
        'shift',
        'jenis_assy',
        'jenis_mobil',
        'conveyor',
        'jenis_defect',
        'jenis_sub_defect',
        'quantity',
        'end_number',
        'specification',
        'actual',
        'area_ditemukan',
        'job_station',
        'keterangan',
        'no_terminal',
        'no_mesin',
        // Kolom baru (Tahap C)
        'inspect_quantity',
        'ditemukan_oleh',
        'pattern',
        'carline_id',
        'inspect_process_type_id',
        'final_inspect_type_id',
        'defect_type_id',
        'sub_defect_type_id',
        // Kolom Rework (Batch 1)
        'waktu_in',
        'waktu_out',
        'durasi_rework_menit',
        'status_rework',
        'kanban_assy',
        'nameplate_lama',
        'nameplate_baru',
        'no_defective_tag',
        'status_4m',
        'posisi_detail_rework',
        'fc_h',
        'fc_w',
        'rc_h',
        'rc_w',
        'no_box',
        'ujung',
    ];

    protected $casts = [
        'waktu'                    => 'datetime',
        'quantity'                 => 'integer',
        'inspect_quantity'         => 'integer',
        'carline_id'               => 'integer',
        'inspect_process_type_id'  => 'integer',
        'final_inspect_type_id'    => 'integer',
        'defect_type_id'           => 'integer',
        'sub_defect_type_id'       => 'integer',
        'waktu_in'                 => 'datetime',
        'waktu_out'                => 'datetime',
        'durasi_rework_menit'      => 'integer',
    ];

    // --- Relasi ke master data baru ---

    public function carline()
    {
        return $this->belongsTo(Carline::class);
    }

    public function inspectProcessType()
    {
        return $this->belongsTo(InspectProcessType::class);
    }

    public function finalInspectType()
    {
        return $this->belongsTo(FinalAssyInspectType::class, 'final_inspect_type_id');
    }

    public function defectType()
    {
        return $this->belongsTo(DefectType::class);
    }

    public function subDefectType()
    {
        return $this->belongsTo(SubDefectType::class);
    }

    /**
     * Akses jenis mobil via relasi carline->carType
     * (tidak disimpan langsung, ambil dari relasi)
     */
    public function getCarTypeNameAttribute(): ?string
    {
        return $this->carline?->carType?->name;
    }
}
