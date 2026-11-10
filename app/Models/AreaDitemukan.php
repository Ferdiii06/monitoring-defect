<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaDitemukan extends Model
{
    use HasFactory;

    // Tipe Sistem Operasional
    public const TIPE_INTERNAL_DEFECT = 'internal_defect';
    public const TIPE_REWORK = 'rework';

    // Kategori Pelaporan Final Assy
    public const REPORT_BELUM_DIKLASIFIKASI = 'belum_diklasifikasi';
    public const REPORT_INSPEKSI = 'inspeksi';
    public const REPORT_ASSEMBLY = 'assembly';
    public const REPORT_REWORK = 'rework';

    // Alias konstanta lama untuk backward-compatibility
    public const TIPE_INSPEKSI = 'inspeksi';
    public const TIPE_ASSEMBLY = 'assembly';
    public const TIPE_BELUM_DIKLASIFIKASI = 'belum_diklasifikasi';

    protected $table = 'area_ditemukans';

    protected $fillable = [
        'name',
        'tipe',
        'kategori_report',
    ];

    protected static function booted()
    {
        static::saving(function ($area) {
            if (empty($area->kategori_report)) {
                if (in_array($area->tipe, [self::REPORT_INSPEKSI, self::REPORT_ASSEMBLY, self::REPORT_BELUM_DIKLASIFIKASI], true)) {
                    $area->kategori_report = $area->tipe;
                    $area->tipe = self::TIPE_INTERNAL_DEFECT;
                } elseif ($area->tipe === self::TIPE_REWORK) {
                    $area->kategori_report = self::REPORT_ASSEMBLY;
                } else {
                    $area->kategori_report = self::REPORT_BELUM_DIKLASIFIKASI;
                }
            }
        });
    }

    /**
     * Relasi ke Defect berdasarkan nama area.
     */
    public function defects()
    {
        return $this->hasMany(Defect::class, 'area_ditemukan', 'name');
    }

    /**
     * Ambil nama area bertipe rework secara deterministik (urutan ID terkecil).
     */
    public static function getDefaultReworkAreaName(): ?string
    {
        $tipeRework = defined(static::class . '::TIPE_REWORK')
            ? static::TIPE_REWORK
            : 'rework';

        return static::where('tipe', $tipeRework)
            ->orderBy('id', 'asc')
            ->value('name');
    }
}
