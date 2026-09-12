<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefectType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type'];

    public function subDefectTypes()
    {
        return $this->hasMany(SubDefectType::class);
    }
}
