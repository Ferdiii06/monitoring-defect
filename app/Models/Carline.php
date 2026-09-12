<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carline extends Model
{
    use HasFactory;

    protected $fillable = ['car_type_id', 'name'];

    public function carType()
    {
        return $this->belongsTo(CarType::class);
    }
}
