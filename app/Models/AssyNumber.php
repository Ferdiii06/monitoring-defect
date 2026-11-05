<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssyNumber extends Model
{
    use HasFactory;

    protected $table = 'assy_numbers';

    protected $fillable = [
        'assy_number',
        'carline_id',
        'keterangan',
    ];

    public function carline()
    {
        return $this->belongsTo(Carline::class);
    }
}
