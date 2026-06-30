<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'waktu',
        'user_name',
        'jenis_aksi',
        'aktivitas',
        'jenis_defect',
        'ip_address',
    ];

    protected $casts = [
        'waktu' => 'datetime',
    ];
}
