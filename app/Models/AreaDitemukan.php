<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaDitemukan extends Model
{
    use HasFactory;

    protected $table = 'area_ditemukans';

    protected $fillable = ['name'];
}
