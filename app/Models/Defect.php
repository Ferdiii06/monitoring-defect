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

    /**
     * Ambil daftar nama area bertipe rework (dimemoize per request via helper once()).
     *
     * @return array<int, string>
     */
    public static function getReworkAreaNames(): array
    {
        return once(function () {
            try {
                if (Schema::hasTable('area_ditemukans')) {
                    $tipeRework = defined(AreaDitemukan::class . '::TIPE_REWORK')
                        ? AreaDitemukan::TIPE_REWORK
                        : 'rework';

                    return AreaDitemukan::where('tipe', $tipeRework)
                        ->pluck('name')
                        ->filter(fn($n) => filled($n))
                        ->values()
                        ->toArray();
                }
            } catch (\Throwable $e) {
                report($e);
                return [];
            }

            return [];
        });
    }

    /**
     * Scope query berdasarkan mode dashboard (internal, rework, all).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $mode 'internal'|'rework'|'all'
     * @param string|null $jenisAssy 'Final Assy'|'Pre Assy'|null
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDashboardMode($query, ?string $mode = 'internal', ?string $jenisAssy = null)
    {
        $mode = in_array($mode, ['all', 'internal', 'rework'], true) ? $mode : 'internal';

        // Pre Assy: Rework tidak berlaku di Pre Assy.
        // Bila jenisAssy eksplisit Pre Assy:
        // - Mode 'rework' mengecualikan Pre Assy seluruhnya (0 baris).
        // - Mode 'internal' dan 'all': operator (rework_group_id IS NULL) DAN area non-rework (termasuk NULL).
        if ($jenisAssy === 'Pre Assy') {
            if ($mode === 'rework') {
                return $query->whereRaw('1 = 0');
            }

            $query->whereNull('rework_group_id');
            $reworkAreaNames = static::getReworkAreaNames();
            if (!empty($reworkAreaNames)) {
                $query->where(function ($q) use ($reworkAreaNames) {
                    $q->whereNull('area_ditemukan')
                      ->orWhereNotIn('area_ditemukan', $reworkAreaNames);
                });
            }
            return $query;
        }

        // Ambil daftar nama area bertipe rework dari master area_ditemukans (memoized)
        $reworkAreaNames = static::getReworkAreaNames();

        // Bila query khusus untuk Final Assy
        if ($jenisAssy === 'Final Assy') {
            if ($mode === 'rework') {
                if (empty($reworkAreaNames)) {
                    return $query->whereRaw('1 = 0');
                }
                return $query->whereNotNull('rework_group_id')
                    ->whereIn('area_ditemukan', $reworkAreaNames);
            }

            if ($mode === 'internal') {
                $query->whereNull('rework_group_id');
                if (empty($reworkAreaNames)) {
                    return $query;
                }
                return $query->where(function ($q) use ($reworkAreaNames) {
                    $q->whereNull('area_ditemukan')
                      ->orWhereNotIn('area_ditemukan', $reworkAreaNames);
                });
            }

            // Mode 'all' Final Assy: union data SAH saja
            // (internal: operator DAN non-rework area) + (rework: rework_group_id IS NOT NULL DAN area rework)
            return $query->where(function ($q) use ($reworkAreaNames) {
                $q->where(function ($sub) use ($reworkAreaNames) {
                    $sub->whereNull('rework_group_id');
                    if (!empty($reworkAreaNames)) {
                        $sub->where(function ($areaSub) use ($reworkAreaNames) {
                            $areaSub->whereNull('area_ditemukan')
                              ->orWhereNotIn('area_ditemukan', $reworkAreaNames);
                        });
                    }
                });

                if (!empty($reworkAreaNames)) {
                    $q->orWhere(function ($sub) use ($reworkAreaNames) {
                        $sub->whereNotNull('rework_group_id')
                            ->whereIn('area_ditemukan', $reworkAreaNames);
                    });
                }
            });
        }

        // Bila query global (campuran Pre Assy dan Final Assy, jenisAssy === null)
        if ($mode === 'rework') {
            // Rework hanya Final Assy, ber-group rework, dan area bertipe rework (Pre Assy dikecualikan seluruhnya)
            if (empty($reworkAreaNames)) {
                return $query->whereRaw('1 = 0');
            }
            return $query->where('jenis_assy', 'Final Assy')
                ->whereNotNull('rework_group_id')
                ->whereIn('area_ditemukan', $reworkAreaNames);
        }

        if ($mode === 'internal') {
            // Mode 'internal' untuk query global:
            // Wajib diinput operator (rework_group_id IS NULL) DAN area_ditemukan NULL atau BUKAN tipe rework
            $query->whereNull('rework_group_id');
            if (empty($reworkAreaNames)) {
                return $query;
            }
            return $query->where(function ($q) use ($reworkAreaNames) {
                $q->whereNull('area_ditemukan')
                  ->orWhereNotIn('area_ditemukan', $reworkAreaNames);
            });
        }

        // Mode 'all' global: union data SAH saja
        // internal sah (Pre Assy & Final Assy) + rework sah (Final Assy only)
        return $query->where(function ($q) use ($reworkAreaNames) {
            $q->where(function ($sub) use ($reworkAreaNames) {
                $sub->whereNull('rework_group_id');
                if (!empty($reworkAreaNames)) {
                    $sub->where(function ($areaSub) use ($reworkAreaNames) {
                        $areaSub->whereNull('area_ditemukan')
                          ->orWhereNotIn('area_ditemukan', $reworkAreaNames);
                    });
                }
            });

            if (!empty($reworkAreaNames)) {
                $q->orWhere(function ($sub) use ($reworkAreaNames) {
                    $sub->where('jenis_assy', 'Final Assy')
                        ->whereNotNull('rework_group_id')
                        ->whereIn('area_ditemukan', $reworkAreaNames);
                });
            }
        });
    }

    /**
     * Scope for Final Assy report queries (operator only, strictly excludes rework).
     */
    public function scopeFinalAssyReport($query)
    {
        return $query->where('jenis_assy', 'Final Assy')->whereNull('rework_group_id');
    }
}
