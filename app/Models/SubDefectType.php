<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubDefectType extends Model
{
    use HasFactory;

    protected $fillable = ['defect_type_id', 'name', 'code'];

    protected static function booted(): void
    {
        static::saving(function ($subDefect) {
            if ($subDefect->isDirty('name') || empty($subDefect->code)) {
                $subDefect->code = \App\Support\SubDefectHelper::deriveCode($subDefect->name);
            }
        });
    }

    public function defectType()
    {
        return $this->belongsTo(DefectType::class);
    }
}
