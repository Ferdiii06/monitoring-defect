<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CarType extends Model
{
    use HasFactory;

    public const SCAN_FORMAT_ASSY_FAMILY = 'assy_family';
    public const SCAN_FORMAT_FAMILY_ASSY = 'family_assy';

    public const SCAN_FORMATS = [
        self::SCAN_FORMAT_ASSY_FAMILY,
        self::SCAN_FORMAT_FAMILY_ASSY,
    ];

    public const SCAN_FORMAT_LABELS = [
        self::SCAN_FORMAT_ASSY_FAMILY => 'Assy-Family (contoh: KCLL-8211)',
        self::SCAN_FORMAT_FAMILY_ASSY => 'Family-Assy (contoh: 82111-1LB01)',
    ];

    /**
     * Cache in-memory per request untuk resolusi conveyor -> carType
     */
    protected static array $conveyorCache = [];

    public static function clearConveyorCache(): void
    {
        self::$conveyorCache = [];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearConveyorCache();
        });

        static::deleted(function () {
            static::clearConveyorCache();
        });
    }

    protected $fillable = [
        'name',
        'scan_format',
    ];

    protected $casts = [
        'scan_format' => 'string',
    ];

    public function carlines()
    {
        return $this->hasMany(Carline::class);
    }

    public function assyNumbers()
    {
        return $this->hasMany(AssyNumber::class);
    }

    public function getScanFormatLabelAttribute(): string
    {
        return self::SCAN_FORMAT_LABELS[$this->scan_format] ?? 'Otomatis (coba kedua urutan)';
    }

    public function getScanFormatShortLabelAttribute(): string
    {
        return match ($this->scan_format) {
            self::SCAN_FORMAT_ASSY_FAMILY => 'Assy-Family',
            self::SCAN_FORMAT_FAMILY_ASSY => 'Family-Assy',
            default => 'Otomatis',
        };
    }

    /**
     * Resolusi jenis mobil tunggal dari kode conveyor berdasarkan kecocokan token penuh (\b).
     * Jika semua carline pemilik token berjenis mobil sama, kembalikan instance CarType tersebut.
     * Jika ambigu (campuran) atau tidak ada yang cocok, kembalikan null.
     *
     * @param string|null $conveyor Kode conveyor (misal: "AB1", "C5")
     * @param iterable|null $carlines Koleksi carline (opsional, jika sudah di-eager-load di luar)
     * @return self|null
     */
    public static function resolveFromConveyor(?string $conveyor, ?iterable $carlines = null): ?self
    {
        if ($conveyor === null || trim($conveyor) === '') {
            return null;
        }

        $conveyorCode = trim($conveyor);
        $cacheKey = strtoupper($conveyorCode);

        // Jika $carlines tidak disediakan, manfaatkan cache in-memory per request
        if ($carlines === null && array_key_exists($cacheKey, self::$conveyorCache)) {
            return self::$conveyorCache[$cacheKey];
        }

        $pattern = '/\b' . preg_quote($conveyorCode, '/') . '\b/i';

        try {
            if (!function_exists('app') || !app()->bound('db')) {
                return null;
            }

            if ($carlines !== null) {
                $candidates = collect($carlines);
            } else {
                // Saring di query dengan LIKE agar tidak memuat seluruh data carline dari database (case-insensitive & escape %, _, \)
                $escapedCode = addcslashes(strtolower($conveyorCode), '%_\\');
                $candidates = Carline::with('carType')
                    ->whereNotNull('car_type_id')
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . $escapedCode . '%'])
                    ->get();
            }

            // Verifikasi token penuh di PHP (\b) agar C5 tidak cocok dengan C5A
            $matched = $candidates->filter(function ($c) use ($pattern) {
                return isset($c->name) && preg_match($pattern, (string) $c->name);
            });

            if ($matched->isEmpty()) {
                if ($carlines === null) {
                    self::$conveyorCache[$cacheKey] = null;
                }
                return null;
            }

            $types = $matched->map(fn($c) => $c->carType)->filter()->unique('id');

            $result = ($types->count() === 1) ? $types->first() : null;

            if ($carlines === null) {
                self::$conveyorCache[$cacheKey] = $result;
            }

            return $result;
        } catch (\Throwable $e) {
            if (function_exists('app') && app()->bound('log')) {
                Log::warning("Gagal resolusi jenis mobil dari conveyor '{$conveyor}': " . $e->getMessage(), [
                    'conveyor'  => $conveyor,
                    'exception' => $e,
                ]);
            }
            return null;
        }
    }
}
