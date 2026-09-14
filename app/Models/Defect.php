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
        'line_conveyor',
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
        'defect_type_id',
        'sub_defect_type_id',
    ];

    protected $casts = [
        'waktu'                    => 'datetime',
        'quantity'                 => 'integer',
        'inspect_quantity'         => 'integer',
        'carline_id'               => 'integer',
        'inspect_process_type_id'  => 'integer',
        'defect_type_id'           => 'integer',
        'sub_defect_type_id'       => 'integer',
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
