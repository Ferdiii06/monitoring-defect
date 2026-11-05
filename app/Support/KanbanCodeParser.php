<?php

namespace App\Support;

use Carbon\Carbon;

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
     * Format: ASSY.CONVEYOR.SERIAL
     *
     * @return array{assy_number: string, conveyor: string, serial: string}|null
     */
    public static function parseKanban(?string $raw): ?array
    {
        $normalized = self::normalize($raw);
        if ($normalized === null) {
            return null;
        }

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

            return $date;
        } catch (\Throwable) {
            return null;
        }
    }
}
