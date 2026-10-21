<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DpmTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis_assy',
        'period_start',
        'period_end',
        'target_dpm',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'target_dpm'   => 'decimal:2',
    ];

    public function setPeriodStartAttribute($value)
    {
        $this->attributes['period_start'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setPeriodEndAttribute($value)
    {
        $this->attributes['period_end'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }
}

