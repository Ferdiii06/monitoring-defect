<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carline extends Model
{
    use HasFactory;

    protected $fillable = ['car_type_id', 'name'];

    protected static function booted(): void
    {
        static::saved(function () {
            CarType::clearConveyorCache();
        });

        static::deleted(function () {
            CarType::clearConveyorCache();
        });
    }

    public function carType()
    {
        return $this->belongsTo(CarType::class);
    }

    public function assyNumbers()
    {
        return $this->hasMany(AssyNumber::class);
    }

    public function machines()
    {
        return $this->hasMany(PreAssyMachine::class);
    }
}
