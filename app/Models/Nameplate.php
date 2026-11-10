<?php

namespace App\Models;

use App\Support\KanbanCodeParser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nameplate extends Model
{
    use HasFactory;

    protected $table = 'nameplates';

    protected $fillable = [
        'nameplate_code',
        'assy_number',
        'suffix',
        'date_code',
        'production_date',
        'serial_number',
        'keterangan',
        'source',
        'is_manual_edit',
        'raw_payload',
    ];

    protected $casts = [
        'is_manual_edit'  => 'boolean',
        'raw_payload'     => 'array',
        'production_date' => 'date',
    ];

    public const SOURCE_IMPORT = 'import';
    public const SOURCE_MANUAL = 'manual';

    /**
     * Mutator agar penyimpanan nameplate_code selalu bersih tanpa spasi dan huruf besar.
     */
    public function setNameplateCodeAttribute($value): void
    {
        $this->attributes['nameplate_code'] = self::normalizeCode($value) ?? $value;
    }

    /**
     * Normalisasi kode nameplate:
     * Trim whitespace dan asterisk (*), hapus SEMUA whitespace (spasi) agar menjadi kunci unik tanpa spasi,
     * serta ubah ke huruf besar.
     */
    public static function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $trimmed = trim($code);
        if ($trimmed === '') {
            return null;
        }

        // Hapus tanda bintang pembungkus barcode bila ada
        $stripped = trim($trimmed, '*');

        // Hapus semua whitespace agar kunci unik seragam tanpa spasi
        $noSpace = preg_replace('/\s+/', '', $stripped);
        $clean = trim($noSpace);
        if ($clean === '') {
            return null;
        }

        return strtoupper($clean);
    }

    /**
     * Tampilan kode nameplate dengan spasi standar bila cocok pola.
     */
    public function getFormattedCodeAttribute(): string
    {
        if ($this->assy_number && $this->date_code && $this->serial_number) {
            $cleanAssy = str_replace(['-', ' '], '', $this->assy_number);
            $suffix = $this->suffix ?? '';
            return "{$cleanAssy} {$suffix}{$this->date_code}{$this->serial_number}";
        }

        return $this->nameplate_code;
    }

    /**
     * Ekstrak field nameplate menggunakan regex NAMEPLATE_PATTERN standar.
     * Mengembalikan array field beserta flag apakah kode cocok dengan pola.
     */
    public static function extractFromCode(string $normalizedCode): array
    {
        $parsed = KanbanCodeParser::parseNameplate($normalizedCode);

        if ($parsed !== null) {
            return [
                'is_pattern_matched' => true,
                'assy_number'        => $parsed['assy_number'] ?? null,
                'suffix'             => $parsed['suffix'] ?? null,
                'date_code'          => $parsed['date_code'] ?? null,
                'production_date'    => $parsed['date'] ? $parsed['date']->format('Y-m-d') : null,
                'serial_number'      => $parsed['serial'] ?? null,
            ];
        }

        return [
            'is_pattern_matched' => false,
            'assy_number'        => null,
            'suffix'             => null,
            'date_code'          => null,
            'production_date'    => null,
            'serial_number'      => null,
        ];
    }
}

