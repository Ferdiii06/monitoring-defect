<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carline extends Model
{
    use HasFactory;

    public const AREA_FINAL_ASSY = 'Final Assy';
    public const AREA_PRE_ASSY = 'Pre Assy';

    protected $fillable = ['car_type_id', 'name', 'jenis_assy'];

    protected static function booted(): void
    {
        static::creating(function ($carline) {
            if (empty($carline->jenis_assy)) {
                $carline->jenis_assy = empty($carline->car_type_id) ? self::AREA_PRE_ASSY : self::AREA_FINAL_ASSY;
            }
        });

        static::saved(function () {
            CarType::clearConveyorCache();
        });

        static::deleted(function () {
            CarType::clearConveyorCache();
        });
    }

    public function scopePreAssy($query)
    {
        return $query->where('jenis_assy', self::AREA_PRE_ASSY);
    }

    public function scopeFinalAssy($query)
    {
        return $query->where('jenis_assy', self::AREA_FINAL_ASSY);
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

    public function preAssyMachines()
    {
        return $this->hasMany(PreAssyMachine::class);
    }

    public function defects()
    {
        return $this->hasMany(Defect::class);
    }

    public function dpmTargets()
    {
        return $this->hasMany(DpmTarget::class);
    }
}
