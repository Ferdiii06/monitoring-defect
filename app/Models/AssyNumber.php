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
     * Cari master assy number dengan kode assy saja (kolom assy_number).
     */
    public static function findByAssyOnly(?string $assy): ?self
    {
        if ($assy === null || trim($assy) === '') {
            return null;
        }

        $trimmed = trim($assy);
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

    /**
     * Fallback pencocokan string penuh (seperti format lama/lengkap).
     */
    public static function findByFullString(?string $fullString): ?self
    {
        if ($fullString === null || trim($fullString) === '') {
            return null;
        }

        $trimmed = trim($fullString);
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

    /**
     * Cari master assy number dengan pencocokan fleksibel:
     * 1. Cari dengan assy saja (sebelum "-" pertama, kolom assy_number).
     * 2. Fallback ke string penuh (as-is / normalized) untuk assy lengkap.
     */
    public static function findByAssyNumber(?string $assyNumber): ?self
    {
        if ($assyNumber === null || trim($assyNumber) === '') {
            return null;
        }

        $trimmed = trim($assyNumber);
        $shortAssy = trim(explode('-', $trimmed, 2)[0]);

        // 1. Cari dengan assy saja (sebelum "-" pertama)
        if ($shortAssy !== '') {
            $found = static::findByAssyOnly($shortAssy);
            if ($found) {
                return $found;
            }
        }

        // 2. Fallback pencocokan string penuh (seperti format lama/lengkap)
        return static::findByFullString($trimmed);
    }

    /**
     * Resolusi nama jenis mobil untuk tampilan (hanya tampilan, tidak mengubah DB):
     * - Jika carline_id terisi, ambil dari relasi carline->carType->name.
     * - Jika carline_id NULL dan conveyor terisi: cari carline yang memuat conveyor sebagai token penuh (\b).
     *   Jika semua carline yang cocok memiliki carType yang sama, kembalikan nama tersebut dengan tanda from_conveyor.
     *   Jika campuran atau tidak ada yang cocok, kembalikan null.
     *
     * @param iterable|null $carlines Koleksi carline (opsional, untuk mencegah N+1)
     * @return array{name: string, from_conveyor: bool}|null
     */
    public function resolveJenisMobil(?iterable $carlines = null): ?array
    {
        if ($this->carline?->carType?->name) {
            return [
                'name'          => $this->carline->carType->name,
                'from_conveyor' => false,
            ];
        }

        if ($this->carline_id === null && filled($this->conveyor)) {
            $carType = class_exists(CarType::class) ? CarType::resolveFromConveyor($this->conveyor, $carlines) : null;
            if ($carType !== null && filled($carType->name)) {
                return [
                    'name'          => $carType->name,
                    'from_conveyor' => true,
                ];
            }
        }

        return null;
    }
}
