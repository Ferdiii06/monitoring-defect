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
        'family',
        'conveyor',
        'keterangan',
    ];

    public function carline()
    {
        return $this->belongsTo(Carline::class);
    }

    /**
     * Cari master assy number dengan pencocokan fleksibel (as-is, normalized, stripped).
     */
    public static function findByAssyNumber(?string $assyNumber): ?self
    {
        if ($assyNumber === null || trim($assyNumber) === '') {
            return null;
        }

        $trimmed = trim($assyNumber);
        $normalized = class_exists(\App\Support\KanbanCodeParser::class)
            ? \App\Support\KanbanCodeParser::normalizeAssyNumber($trimmed)
            : strtoupper(str_replace(['-', ' '], '', $trimmed));

        return static::with(['carline.carType'])
            ->where(function ($q) use ($trimmed, $normalized) {
                $q->where('assy_number', $trimmed)
                  ->orWhere('assy_number', $normalized)
                  ->orWhereRaw("REPLACE(REPLACE(UPPER(assy_number), '-', ''), ' ', '') = ?", [$normalized]);
            })
            ->first();
    }
}
