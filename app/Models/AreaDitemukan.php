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
}
