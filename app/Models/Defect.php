<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Defect extends Model
{
    use HasFactory;

    protected $fillable = [
        'waktu',
        'user_name',
        'jenis_assy',
        'line_conveyor',
        'jenis_defect',
        'jenis_sub_defect',
        'quantity',
    ];
}
