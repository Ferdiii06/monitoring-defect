<?php

namespace App\Support;

use App\Models\KanbanComponent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class KanbanCodeParser
{
    /**
     * Regex acuan untuk e-kanban baru (QR Packing) dan kanban lama (Barcode Ikitsukan):
     * ASSY.CONVEYOR.SERIAL dengan pemisah titik atau spasi.
     */
    protected const KANBAN_PATTERN = '/^([A-Z0-9]+-[A-Z0-9]+)[.\s]+([A-Z0-9]+)[.\s]+(\d{3})$/';

    /**
     * Regex acuan untuk QR nameplate:
     * ASSY (10 alfanumerik tanpa '-'), spasi opsional, 1 huruf suffix opsional, 6 digit tanggal, 4 digit serial.
     */
    protected const NAMEPLATE_PATTERN = '/^([A-Z0-9]{10})\s*([A-Z]?)\s*(\d{6})(\d{4})$/';

    /**
     * Pola regex tetap untuk setiap komponen kanban di kode (konstanta).
     */
    public const COMPONENT_PATTERNS = [
        'assy_number' => '[A-Z0-9]+-[A-Z0-9]+',
        'conveyor'    => '[A-Z0-9]+',
        'serial'      => '\d{3}',
    ];

    public const CACHE_KEY = 'kanban_dynamic_pattern';
    public const CACHE_COMPONENTS_KEY = 'kanban_ordered_components';

    /**
     * Cache in-memory lokal untuk proses aktif
     */
    protected static ?string $cachedDynamicPattern = null;
    protected static bool $cacheInitialized = false;

    /**
     * Reset cache regex (dipanggil setelah reorder master format).
     * Menghapus cache in-memory lokal dan cache aplikasi lintas proses.
     */
    public static function clearCache(): void
    {
        self::$cachedDynamicPattern = null;
        self::$cacheInitialized = false;

        try {
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::CACHE_COMPONENTS_KEY);
        } catch (\Throwable) {
            // Abaikan kegagalan cache store
        }
    }

    /**
     * Dapatkan daftar komponen terurut dari database/cache dengan fallback bawaan.
     *
     * @return array<int, array{key: string, delimiter: string}>
     */
    public static function getOrderedComponents(): array
    {
        try {
            return Cache::remember(self::CACHE_COMPONENTS_KEY, 3600, function () {
                if (Schema::hasTable('kanban_components')) {
                    $components = KanbanComponent::orderBy('sequence', 'asc')->get();
                    if ($components->count() === count(self::COMPONENT_PATTERNS)) {
                        $keys = $components->pluck('component_key')->all();
                        if (count(array_diff(array_keys(self::COMPONENT_PATTERNS), $keys)) === 0) {
                            return $components->map(fn($c) => [
                                'key'       => $c->component_key,
                                'delimiter' => ($c->delimiter === 'spasi' || $c->delimiter === ' ') ? ' ' : '.',
                            ])->all();
                        }
                    }
                }

                return [
                    ['key' => 'assy_number', 'delimiter' => '.'],
                    ['key' => 'conveyor',    'delimiter' => '.'],
                    ['key' => 'serial',      'delimiter' => '.'],
                ];
            });
        } catch (\Throwable) {
            return [
                ['key' => 'assy_number', 'delimiter' => '.'],
                ['key' => 'conveyor',    'delimiter' => '.'],
                ['key' => 'serial',      'delimiter' => '.'],
            ];
        }
    }

    /**
     * Susun string kanban berdasarkan urutan komponen dan delimiter aktif di master kanban_components.
     * Memvalidasi setiap input komponen dan memastikan string hasilnya lolos parsing kembali.
     *
     * @throws \InvalidArgumentException Jika komponen tidak sesuai format regex
     * @throws \RuntimeException Jika string yang dihasilkan gagal di-parse kembali
     */
    public static function generateKanban(string $assyNumber, string $conveyor, string $serial): string
    {
        $cleanAssy = strtoupper(trim($assyNumber));
        $cleanConveyor = strtoupper(trim($conveyor));
        $cleanSerial = trim($serial);

        if (ctype_digit($cleanSerial) && strlen($cleanSerial) < 3) {
            $cleanSerial = str_pad($cleanSerial, 3, '0', STR_PAD_LEFT);
        }

        if (!preg_match('/^' . self::COMPONENT_PATTERNS['assy_number'] . '$/', $cleanAssy)) {
            throw new \InvalidArgumentException("Nomor Assy '{$assyNumber}' tidak valid. Format harus memuat tanda hubung (misal: 82111-VU020).");
        }

        if (!preg_match('/^' . self::COMPONENT_PATTERNS['conveyor'] . '$/', $cleanConveyor)) {
            throw new \InvalidArgumentException("Konveyor '{$conveyor}' tidak valid. Hanya karakter alfanumerik tanpa tanda hubung yang diizinkan.");
        }

        if (!preg_match('/^' . self::COMPONENT_PATTERNS['serial'] . '$/', $cleanSerial)) {
            throw new \InvalidArgumentException("Serial number '{$serial}' tidak valid. Harus berupa 3 digit angka (001-999).");
        }

        $components = self::getOrderedComponents();
        $map = [
            'assy_number' => $cleanAssy,
            'conveyor'    => $cleanConveyor,
            'serial'      => $cleanSerial,
        ];

        $parts = [];
        $total = count($components);
        foreach ($components as $index => $comp) {
            $parts[] = $map[$comp['key']] ?? '';
            if ($index < $total - 1) {
                $parts[] = $comp['delimiter'];
            }
        }
        $generated = implode('', $parts);

        // Verifikasi round-trip: string hasil generate wajib dapat di-parse kembali dengan sempurna
        $parsed = self::parseKanban($generated);
        if ($parsed === null ||
            $parsed['assy_number'] !== $cleanAssy ||
            $parsed['conveyor'] !== $cleanConveyor ||
            $parsed['serial'] !== $cleanSerial) {
            throw new \RuntimeException("String kanban yang dihasilkan ('{$generated}') gagal divalidasi oleh parser.");
        }

        return $generated;
    }

    /**
     * Dapatkan regex pola dinamis dari master kanban_components.
     * Menggunakan Laravel Cache agar aman dan tersinkronisasi lintas proses worker.
     */
    public static function getDynamicPattern(): ?string
    {
        if (self::$cacheInitialized) {
            return self::$cachedDynamicPattern;
        }

        try {
            $pattern = Cache::remember(self::CACHE_KEY, 3600, function () {
                return self::buildDynamicPattern() ?? '__NONE__';
            });

            self::$cachedDynamicPattern = ($pattern === '__NONE__') ? null : $pattern;
        } catch (\Throwable) {
            self::$cachedDynamicPattern = self::buildDynamicPattern();
        }

        self::$cacheInitialized = true;
        return self::$cachedDynamicPattern;
    }

    /**
     * Bangun regex dinamis dari tabel kanban_components.
     * Aman dari error jika tabel kanban_components belum dimigrasi/dibuat.
     */
    public static function buildDynamicPattern(): ?string
    {
        try {
            if (!Schema::hasTable('kanban_components')) {
                return null;
            }

            $components = KanbanComponent::orderBy('sequence', 'asc')->get();
            if ($components->count() !== count(self::COMPONENT_PATTERNS)) {
                return null;
            }

            // Pastikan semua komponen wajib ada
            $keys = $components->pluck('component_key')->all();
            if (count(array_diff(array_keys(self::COMPONENT_PATTERNS), $keys)) !== 0) {
                return null;
            }

            $regexParts = [];
            $total = $components->count();

            foreach ($components as $index => $comp) {
                $key = $comp->component_key;
                $pattern = self::COMPONENT_PATTERNS[$key];
                $regexParts[] = '(?P<' . $key . '>' . $pattern . ')';

                if ($index < $total - 1) {
                    $regexParts[] = self::regexDelimiter($comp->delimiter);
                }
            }

            return '/^' . implode('', $regexParts) . '$/';
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Escape delimiter menjadi pola regex.
     * Pilihan delimiter valid: '.' (titik) atau ' ' (spasi).
     */
    protected static function regexDelimiter(?string $delimiter): string
    {
        if ($delimiter === ' ' || $delimiter === 'spasi') {
            return '\s+';
        }
        // Default '.'
        return '\s*\.\s*';
    }

    /**
     * Normalisasi nomor assy: hapus tanda "-" dan spasi, ubah ke huruf besar.
     * Contoh: "82111-VU020" -> "82111VU020", " 82111 vu020 " -> "82111VU020".
     */
    public static function normalizeAssyNumber(?string $assy): ?string
    {
        if ($assy === null) {
            return null;
        }

        $clean = strtoupper(str_replace(['-', ' '], '', trim($assy)));
        return $clean === '' ? null : $clean;
    }

    /**
     * Normalisasi input:
     * Trim whitespace, buang karakter '*' di awal dan akhir, ubah ke huruf besar.
     * Mengembalikan null jika input null, kosong, atau hanya terdiri dari spasi/bintang.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $stripped = trim($trimmed, '*');
        $clean = trim($stripped);
        if ($clean === '') {
            return null;
        }

        return strtoupper($clean);
    }

    /**
     * Parse kode kanban (e-kanban baru / kanban lama).
     * Format acuan: ASSY.CONVEYOR.SERIAL atau sesuai urutan dinamis di master.
     *
     * @return array{assy_number: string, conveyor: string, serial: string}|null
     */
    public static function parseKanban(?string $raw): ?array
    {
        $normalized = self::normalize($raw);
        if ($normalized === null) {
            return null;
        }

        // 1. Coba regex dinamis dari master database (jika ada dan valid)
        $dynamicPattern = self::getDynamicPattern();
        if ($dynamicPattern !== null) {
            if (preg_match($dynamicPattern, $normalized, $matches)) {
                return [
                    'assy_number' => $matches['assy_number'] ?? null,
                    'conveyor'    => $matches['conveyor'] ?? null,
                    'serial'      => $matches['serial'] ?? null,
                ];
            }
        }

        // 2. Fallback ke KANBAN_PATTERN lama jika pola dinamis gagal atau master kosong
        if (!preg_match(self::KANBAN_PATTERN, $normalized, $matches)) {
            return null;
        }

        return [
            'assy_number' => $matches[1],
            'conveyor'    => $matches[2],
            'serial'      => $matches[3],
        ];
    }

    /**
     * Parse QR code nameplate.
     * Contoh: "82111VU020  A2607060008"
     *
     * @return array{assy_number: string, assy_number_raw: string, suffix: ?string, date_code: string, date: ?Carbon, serial: string}|null
     */
    public static function parseNameplate(?string $raw): ?array
    {
        $normalized = self::normalize($raw);
        if ($normalized === null) {
            return null;
        }

        if (!preg_match(self::NAMEPLATE_PATTERN, $normalized, $matches)) {
            return null;
        }

        $rawAssy = $matches[1];
        $cleanRaw = self::normalizeAssyNumber($rawAssy) ?? $rawAssy;
        $suffix = $matches[2] !== '' ? $matches[2] : null;
        $dateCode = $matches[3];
        $serial = $matches[4];

        $assyNumber = (strlen($cleanRaw) >= 5)
            ? substr($cleanRaw, 0, 5) . '-' . substr($cleanRaw, 5)
            : $cleanRaw;
        $date = self::parseDateCode($dateCode);

        return [
            'assy_number'     => $assyNumber,
            'assy_number_raw' => $rawAssy,
            'suffix'          => $suffix,
            'date_code'       => $dateCode,
            'date'            => $date,
            'serial'          => $serial,
        ];
    }

    /**
     * Deteksi dan parse kode: coba kanban lebih dulu, lalu nameplate.
     * Mengembalikan array hasil parsing ditambah key 'type' => 'kanban'|'nameplate'.
     * Jika tidak cocok keduanya, mengembalikan null.
     */
    public static function detect(?string $raw): ?array
    {
        $kanban = self::parseKanban($raw);
        if ($kanban !== null) {
            return array_merge(['type' => 'kanban'], $kanban);
        }

        $nameplate = self::parseNameplate($raw);
        if ($nameplate !== null) {
            return array_merge(['type' => 'nameplate'], $nameplate);
        }

        return null;
    }

    /**
     * Parse 6 digit date code (yymmdd) menjadi instance Carbon.
     * Mengembalikan null jika tanggal tidak valid atau terjadi error.
     */
    protected static function parseDateCode(string $dateCode): ?Carbon
    {
        if (strlen($dateCode) !== 6 || !ctype_digit($dateCode)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!ymd', $dateCode);
            $errors = Carbon::getLastErrors();
            if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                return null;
            }

            if ($date->format('ymd') !== $dateCode) {
                return null;
            }

            return $date->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
