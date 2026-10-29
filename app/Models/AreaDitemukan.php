<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaDitemukan extends Model
{
    use HasFactory;

    public const TIPE_INTERNAL_DEFECT = 'internal_defect';
    public const TIPE_REWORK = 'rework';

    protected $table = 'area_ditemukans';

    protected $fillable = [
        'name',
        'tipe',
    ];

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
