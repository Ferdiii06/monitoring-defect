<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        'rework_group_id',
        'is_susulan',
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
        'is_susulan'               => 'boolean',
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

    /**
     * Scope data input operator (rework_group_id IS NULL).
     */
    public function scopeOperatorInput($query)
    {
        return $query->whereNull('rework_group_id');
    }

    /**
     * Scope data record rework (rework_group_id IS NOT NULL).
     */
    public function scopeReworkRecord($query)
    {
        return $query->whereNotNull('rework_group_id');
    }

    /**
     * Relasi ke AreaDitemukan berdasarkan string name (tanpa bentrok dengan atribut area_ditemukan).
     */
    public function areaDitemukanRef()
    {
        return $this->belongsTo(AreaDitemukan::class, 'area_ditemukan', 'name');
    }

    /**
     * Scope untuk menerapkan seluruh parameter filter Final Assy secara terpusat.
     * Digunakan bersama oleh halaman report, kartu KPI, dan endpoint data chart.
     */
    public function scopeFilterFinalAssy($query, $request = null)
    {
        $query->where('jenis_assy', 'Final Assy');

        if ($request === null) {
            return $query;
        }

        $getParam = function ($key) use ($request) {
            if ($request instanceof \Illuminate\Http\Request) {
                return $request->input($key);
            }
            if (is_array($request)) {
                return $request[$key] ?? null;
            }
            return null;
        };

        $dateRange = $getParam('date_range');
        if ($dateRange) {
            try {
                $dates = explode(' to ', $dateRange);
                if (count($dates) === 2) {
                    $startDate = \Carbon\Carbon::parse($dates[0])->startOfDay();
                    $endDate = \Carbon\Carbon::parse($dates[1])->endOfDay();
                    $query->whereBetween('waktu', [$startDate, $endDate]);
                } else if (count($dates) === 1) {
                    $startDate = \Carbon\Carbon::parse($dates[0])->startOfDay();
                    $endDate = \Carbon\Carbon::parse($dates[0])->endOfDay();
                    $query->whereBetween('waktu', [$startDate, $endDate]);
                }
            } catch (\Throwable $e) {
                // Abaikan bila format tanggal tidak valid
            }
        }

        $defect = $getParam('defect');
        if ($defect && $defect !== 'all') {
            $query->where('jenis_defect', $defect);
        }

        $line = $getParam('line');
        if ($line && $line !== 'all') {
            $query->where('jenis_mobil', $line);
        }

        $conveyor = $getParam('conveyor');
        if ($conveyor && $conveyor !== 'all') {
            $query->where('conveyor', $conveyor);
        }

        $pattern = $getParam('pattern');
        if ($pattern && $pattern !== 'all') {
            $query->where('pattern', $pattern);
        }

        return $query;
    }

    /**
     * Scope Defect Riil (mengecualikan Zero Defect / kuantitas 0).
     */
    public function scopeActualDefect($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('jenis_defect')
              ->orWhere('jenis_defect', '!=', 'Zero Defect');
        })->where('quantity', '>', 0);
    }

    /**
     * Scope for Final Assy report queries (operator only, strictly excludes rework).
     */
    public function scopeFinalAssyReport($query)
    {
        return $query->where('jenis_assy', 'Final Assy')->operatorInput();
    }

    /**
     * Scope Final Assy: Internal Defect
     * operatorInput + Final Assy + actualDefect + area internal (termasuk NULL/kosong/tidak dikenal).
     */
    public function scopeFinalAssyInternalDefect($query)
    {
        return $query->operatorInput()
            ->where('jenis_assy', 'Final Assy')
            ->actualDefect()
            ->where(function ($q) {
                $q->whereNull('area_ditemukan')
                  ->orWhere('area_ditemukan', '')
                  ->orWhereDoesntHave('areaDitemukanRef')
                  ->orWhereHas('areaDitemukanRef', function ($sub) {
                      $sub->where('tipe', '!=', AreaDitemukan::TIPE_ASSEMBLY_DEFECT);
                  });
            });
    }

    /**
     * Scope Final Assy: Assembly Defect
     * operatorInput + Final Assy + actualDefect + area bertipe assembly_defect.
     */
    public function scopeFinalAssyAssemblyDefect($query)
    {
        return $query->operatorInput()
            ->where('jenis_assy', 'Final Assy')
            ->actualDefect()
            ->whereHas('areaDitemukanRef', function ($sub) {
                $sub->where('tipe', AreaDitemukan::TIPE_ASSEMBLY_DEFECT);
            });
    }

    /**
     * Scope Pre Assy: Internal Defect oleh Inspektor.
     * operatorInput + Pre Assy + actualDefect + ditemukan_oleh = 'Inspektor'.
     */
    public function scopePreAssyInspektor($query)
    {
        return $query->operatorInput()
            ->where('jenis_assy', 'Pre Assy')
            ->actualDefect()
            ->where('ditemukan_oleh', 'Inspektor');
    }

    /**
     * Alias backward-compatibility untuk Pre Assy Inspektor.
     */
    public function scopePreAssyByInspektor($query)
    {
        return $this->scopePreAssyInspektor($query);
    }

    public function scopePreAssyInternal($query)
    {
        return $this->scopePreAssyInspektor($query);
    }

    /**
     * Scope Pre Assy: Internal Defect oleh Operator.
     * operatorInput + Pre Assy + actualDefect + ditemukan_oleh = 'Operator'.
     */
    public function scopePreAssyOperator($query)
    {
        return $query->operatorInput()
            ->where('jenis_assy', 'Pre Assy')
            ->actualDefect()
            ->where('ditemukan_oleh', 'Operator');
    }

    /**
     * Alias backward-compatibility untuk Pre Assy Operator.
     */
    public function scopePreAssyByOperator($query)
    {
        return $this->scopePreAssyOperator($query);
    }

    public function scopePreAssyAssembly($query)
    {
        return $this->scopePreAssyOperator($query);
    }

    public function scopePreAssyProcess($query)
    {
        return $this->scopePreAssyOperator($query);
    }

    /**
     * Scope Pre Assy: Tidak Diketahui (ditemukan_oleh NULL atau selain Inspektor/Operator).
     */
    public function scopePreAssyUnknown($query)
    {
        return $query->operatorInput()
            ->where('jenis_assy', 'Pre Assy')
            ->actualDefect()
            ->where(function ($q) {
                $q->whereNull('ditemukan_oleh')
                  ->orWhereNotIn('ditemukan_oleh', ['Inspektor', 'Operator']);
            });
    }
}
