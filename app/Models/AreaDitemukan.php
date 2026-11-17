<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaDitemukan extends Model
{
    use HasFactory;

    // Tipe Area Ditemukan (Revisi IDRECT Batch 1: hanya 2 nilai valid)
    public const TIPE_INTERNAL_DEFECT = 'internal_defect';
    public const TIPE_ASSEMBLY_DEFECT = 'assembly_defect';

    // Kategori Area Ditemukan (Revisi IDRECT Batch 1: hanya milik assy mana)
    public const KATEGORI_FINAL_ASSY = 'Final Assy';
    public const KATEGORI_PRE_ASSY = 'Pre Assy';

    // Konstanta legacy (HANYA untuk backward-compatibility replikasi migration lama yang sudah Ran dan legacy tests)
    public const KATEGORI_REWORK = 'Rework';
    public const TIPE_PRE_ASSY = 'Pre Assy';
    public const TIPE_INSPEKSI = 'inspeksi';
    public const TIPE_ASSEMBLY = 'assembly';
    public const TIPE_BELUM_DIKLASIFIKASI = 'belum_diklasifikasi';
    public const REPORT_OPERATOR = 'operator';
    public const REPORT_INSPEKTOR = 'inspektor';
    public const REPORT_INSPEKSI = 'inspeksi';
    public const REPORT_ASSEMBLY = 'assembly';
    public const REPORT_REWORK = 'rework';
    public const REPORT_BELUM_DIKLASIFIKASI = 'belum_diklasifikasi';

    protected $table = 'area_ditemukans';

    protected $fillable = [
        'name',
        'kategori',
        'tipe',
    ];

    protected static function booted()
    {
        static::saving(function ($area) {
            // 1. Normalisasi kategori: hanya Final Assy atau Pre Assy
            $normKat = strtolower(trim((string)$area->kategori));
            if (in_array($normKat, ['pre assy', 'pre_assy', 'pre-assy'], true)) {
                $area->kategori = self::KATEGORI_PRE_ASSY;
            } else {
                $area->kategori = self::KATEGORI_FINAL_ASSY;
            }

            // 2. Pre Assy selalu dipaksa internal_defect (pembagian operator/inspektor lewat ditemukan_oleh)
            if ($area->kategori === self::KATEGORI_PRE_ASSY) {
                $area->tipe = self::TIPE_INTERNAL_DEFECT;
            } else {
                // Final Assy: wajib salah satu dari assembly_defect atau internal_defect (default internal_defect)
                $normTipe = strtolower(trim((string)$area->tipe));
                if (in_array($normTipe, [self::TIPE_ASSEMBLY_DEFECT, 'assembly'], true)) {
                    $area->tipe = self::TIPE_ASSEMBLY_DEFECT;
                } else {
                    $area->tipe = self::TIPE_INTERNAL_DEFECT;
                }
            }
        });
    }

    /**
     * Accessor agar $area->kategori selalu mengembalikan Final Assy atau Pre Assy.
     */
    public function getKategoriAttribute($value): string
    {
        $norm = strtolower(trim((string)$value));
        if (in_array($norm, ['pre assy', 'pre_assy', 'pre-assy'], true)) {
            return self::KATEGORI_PRE_ASSY;
        }
        return self::KATEGORI_FINAL_ASSY;
    }

    /**
     * Helper: Daftar nama area bertipe assembly_defect.
     *
     * @return array<int, string>
     */
    public static function assemblyNames(): array
    {
        return static::where('tipe', self::TIPE_ASSEMBLY_DEFECT)
            ->pluck('name')
            ->toArray();
    }

    /**
     * Helper: Daftar nama area bertipe internal_defect.
     *
     * @return array<int, string>
     */
    public static function internalNames(): array
    {
        return static::where('tipe', self::TIPE_INTERNAL_DEFECT)
            ->pluck('name')
            ->toArray();
    }

    /**
     * Helper statis: Resolve tipe area berdasarkan nama area.
     * Mengembalikan internal_defect jika nama NULL atau tidak ditemukan di DB.
     */
    public static function resolveTipeByName(?string $name): string
    {
        if (empty($name)) {
            return self::TIPE_INTERNAL_DEFECT;
        }

        $area = static::where('name', trim($name))->first();
        if ($area && $area->tipe === self::TIPE_ASSEMBLY_DEFECT) {
            return self::TIPE_ASSEMBLY_DEFECT;
        }

        return self::TIPE_INTERNAL_DEFECT;
    }

    /**
     * Scope untuk Area bertipe Internal Defect.
     */
    public function scopeInternalDefect($query)
    {
        return $query->where('tipe', self::TIPE_INTERNAL_DEFECT);
    }

    /**
     * Scope untuk Area bertipe Assembly Defect.
     */
    public function scopeAssemblyDefect($query)
    {
        return $query->where('tipe', self::TIPE_ASSEMBLY_DEFECT);
    }

    /**
     * Scope untuk kategori Pre Assy.
     */
    public function scopePreAssy($query)
    {
        return $query->where('kategori', self::KATEGORI_PRE_ASSY);
    }

    /**
     * Scope untuk kategori Final Assy.
     */
    public function scopeFinalAssy($query)
    {
        return $query->where('kategori', self::KATEGORI_FINAL_ASSY);
    }

    /**
     * Relasi ke Defect berdasarkan nama area.
     */
    public function defects()
    {
        return $this->hasMany(Defect::class, 'area_ditemukan', 'name');
    }
}
