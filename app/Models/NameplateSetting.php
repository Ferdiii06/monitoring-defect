<?php

namespace App\Models;

use App\Support\KanbanCodeParser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NameplateSetting extends Model
{
    use HasFactory;

    protected $table = 'nameplate_settings';

    public const SET_CARTON_BOX = 'CARTON BOX';
    public const SET_POLYTAINER = 'POLYTAINER';

    public const SET_OPTIONS = [
        self::SET_CARTON_BOX,
        self::SET_POLYTAINER,
    ];

    protected $fillable = [
        'carline',
        'assy_customer',
        'name',
        'set',
        'date_format',
        'assy_no',
        'suffix',
        'level',
        'date_field',
        'sequence_label',
    ];

    protected $casts = [
        'assy_customer' => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    /**
     * Mutator to ensure carline is always trimmed and uppercase.
     */
    public function setCarlineAttribute($value): void
    {
        $this->attributes['carline'] = strtoupper(trim((string) $value));
    }

    /**
     * Mutator to ensure date_format is always trimmed and uppercase.
     */
    public function setDateFormatAttribute($value): void
    {
        $this->attributes['date_format'] = strtoupper(trim((string) $value));
    }

    /**
     * Scope: search across carline, name, set, date_format.
     */
    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $like = '%' . $term . '%';
            $q->where('carline', 'like', $like)
              ->orWhere('name', 'like', $like)
              ->orWhere('set', 'like', $like)
              ->orWhere('date_format', 'like', $like);
        });
    }

    /**
     * Scope: sort by whitelist columns.
     */
    public function scopeSortBy($query, ?string $column, ?string $direction = 'asc')
    {
        $whitelist = ['carline', 'name', 'set', 'date_format', 'created_at'];
        $col = in_array($column, $whitelist, true) ? $column : 'carline';
        $dir = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($col, $dir);
    }

    /**
     * Ekstraksi barcode nameplate berdasarkan aturan segmentasi posisi dan panjang.
     * Aturan segmentasi: "start,length" (contoh: "1,8" -> mulai karakter 1, panjang 8)
     * atau "start-end" (contoh: "1-8" -> karakter 1 s/d 8).
     * Jika barcode diawali/diakhiri tanda *, tanda tersebut dibersihkan terlebih dahulu.
     */
    public static function extractBarcode(string $rawBarcode, array $rules = []): array
    {
        $normalized = trim($rawBarcode);
        // Hapus tanda bintang di awal dan akhir jika ada (Code 39 barcode)
        $normalized = preg_replace('/^\*+|\*+$/', '', $normalized);
        $normalized = str_replace(' ', '', $normalized);

        if ($normalized === '') {
            return [
                'success' => false,
                'message' => 'Barcode kosong atau tidak valid.',
            ];
        }

        $assyRule     = $rules['assy_no'] ?? null;
        $suffixRule   = $rules['suffix'] ?? null;
        $levelRule    = $rules['level'] ?? null;
        $dateRule     = $rules['date_field'] ?? ($rules['date'] ?? null);
        $dateFormat   = strtoupper(trim($rules['date_format'] ?? 'YYMMDD'));
        $seqRule      = $rules['sequence_label'] ?? null;

        $hasAnyCustomRule = filled($assyRule) || filled($suffixRule) || filled($levelRule) || filled($dateRule) || filled($seqRule);

        $assyVal   = self::extractSegment($normalized, $assyRule);
        $suffixVal = self::extractSegment($normalized, $suffixRule);
        $levelVal  = self::extractSegment($normalized, $levelRule);
        $dateVal   = self::extractSegment($normalized, $dateRule);
        $seqVal    = self::extractSegment($normalized, $seqRule);

        // Jika tidak ada aturan custom yang diisi, gunakan fallback parsing standar KanbanCodeParser
        if (!$hasAnyCustomRule) {
            $parsed = KanbanCodeParser::parseNameplate($normalized);
            if ($parsed !== null) {
                $assyVal   = $parsed['assy_number_raw'] ?? $parsed['assy_number'];
                $suffixVal = $parsed['suffix'];
                $dateVal   = $parsed['date_code'];
                $seqVal    = $parsed['serial'];
            }
        }

        // Format tanggal jika dateVal tersedia
        $formattedDate = self::formatDateValue($dateVal, $dateFormat);

        // Jika tidak ada satu pun field yang berhasil diekstrak
        if ($assyVal === null && $suffixVal === null && $levelVal === null && $dateVal === null && $seqVal === null) {
            return [
                'success' => false,
                'message' => 'Barcode tidak cocok dengan aturan segmentasi yang ditentukan.',
            ];
        }

        return [
            'success'       => true,
            'barcode_clean' => $normalized,
            'data'          => [
                'assy_no'        => $assyVal ?? '-',
                'suffix'         => $suffixVal ?? '-',
                'level'          => $levelVal ?? '-',
                'date'           => $formattedDate ?: ($dateVal ?? '-'),
                'date_raw'       => $dateVal ?? '-',
                'sequence_label' => $seqVal ?? '-',
            ],
        ];
    }

    /**
     * Ekstraksi segmen string berdasarkan aturan segmentasi:
     * - "start,length" (contoh: "1,8")
     * - "start-end" (contoh: "1-8")
     * - "start:length" (contoh: "1:8")
     * - regex "/.../"
     */
    public static function extractSegment(string $string, ?string $rule): ?string
    {
        if (!filled($rule)) {
            return null;
        }

        $rule = trim((string) $rule);

        // Format 1: "start,length" (contoh: "1,8" atau "1, 8") -> 1-based index
        if (preg_match('/^(\d+)\s*,\s*(\d+)$/', $rule, $m)) {
            $start = (int) $m[1] - 1;
            $len   = (int) $m[2];
            if ($start >= 0 && $start < strlen($string) && $len > 0) {
                return substr($string, $start, $len);
            }
            return null;
        }

        // Format 2: "start-end" atau "start..end" (contoh: "1-8" atau "1..8") -> 1-based index
        if (preg_match('/^(\d+)\s*(?:-|\.\.)\s*(\d+)$/', $rule, $m)) {
            $start = (int) $m[1] - 1;
            $end   = (int) $m[2];
            $len   = $end - $start;
            if ($start >= 0 && $start < strlen($string) && $len > 0) {
                return substr($string, $start, $len);
            }
            return null;
        }

        // Format 3: "start:length" (contoh: "1:8")
        if (preg_match('/^(\d+)\s*:\s*(\d+)$/', $rule, $m)) {
            $start = (int) $m[1] - 1;
            $len   = (int) $m[2];
            if ($start >= 0 && $start < strlen($string) && $len > 0) {
                return substr($string, $start, $len);
            }
            return null;
        }

        // Format 4: Regular expression delimiter /.../
        if (preg_match('/^\/.*\/[a-z]*$/i', $rule)) {
            if (@preg_match($rule, $string, $matches)) {
                return $matches[1] ?? ($matches[0] ?? null);
            }
        }

        return null;
    }

    /**
     * Format nilai tanggal ekstraksi berdasarkan Date Format yang ditentukan.
     */
    public static function formatDateValue(?string $rawDate, string $dateFormat): ?string
    {
        if (!filled($rawDate)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $rawDate);
        if ($digits === '') {
            return (string) $rawDate;
        }

        if ($dateFormat === 'YYMMDD' && strlen($digits) >= 6) {
            $yy = substr($digits, 0, 2);
            $mm = substr($digits, 2, 2);
            $dd = substr($digits, 4, 2);
            return "20{$yy}-{$mm}-{$dd} (Raw: {$rawDate})";
        }

        if ($dateFormat === 'YYYYMMDD' && strlen($digits) >= 8) {
            $yyyy = substr($digits, 0, 4);
            $mm   = substr($digits, 4, 2);
            $dd   = substr($digits, 6, 2);
            return "{$yyyy}-{$mm}-{$dd} (Raw: {$rawDate})";
        }

        if ($dateFormat === 'DDMMYY' && strlen($digits) >= 6) {
            $dd = substr($digits, 0, 2);
            $mm = substr($digits, 2, 2);
            $yy = substr($digits, 4, 2);
            return "20{$yy}-{$mm}-{$dd} (Raw: {$rawDate})";
        }

        if ($dateFormat === 'DDMMYYYY' && strlen($digits) >= 8) {
            $dd   = substr($digits, 0, 2);
            $mm   = substr($digits, 2, 2);
            $yyyy = substr($digits, 4, 4);
            return "{$yyyy}-{$mm}-{$dd} (Raw: {$rawDate})";
        }

        if ($dateFormat === 'YYMM' && strlen($digits) >= 4) {
            $yy = substr($digits, 0, 2);
            $mm = substr($digits, 2, 2);
            return "20{$yy}-{$mm} (Raw: {$rawDate})";
        }

        return (string) $rawDate;
    }
}
