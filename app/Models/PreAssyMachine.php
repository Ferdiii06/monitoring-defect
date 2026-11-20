<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreAssyMachine extends Model
{
    use HasFactory;

    protected $fillable = [
        'carline_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Carline Pre Assy.
     */
    public function carline()
    {
        return $this->belongsTo(Carline::class);
    }

    /**
     * Scope untuk mesin yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
