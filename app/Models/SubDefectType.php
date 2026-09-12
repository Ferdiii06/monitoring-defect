<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubDefectType extends Model
{
    use HasFactory;

    protected $fillable = ['defect_type_id', 'name'];

    public function defectType()
    {
        return $this->belongsTo(DefectType::class);
    }
}
