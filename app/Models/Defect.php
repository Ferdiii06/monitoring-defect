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
        'konveyor',
        'jenis_defect',
        'jenis_sub_defect',
        'quantity',
    ];
}
