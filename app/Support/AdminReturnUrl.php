<?php

namespace App\Support;

class AdminReturnUrl
{
    /**
     * Whitelist prefix URI untuk redirect/tombol Back admin.
     * Diambil dari route:list (final_assy.*, pre_assy.*, recent_defects.*, admin.rework_history.*).
     */
    public const ALLOWED_PREFIXES = [
        '/final-assy',
        '/pre-assy',
        '/recent-defects',
        '/admin/rework-history',
    ];

    /**
     * Validasi dan sanitasi parameter return_url untuk administrator.
     * Hanya menerima path relatif yang cocok per-segmen dengan salah satu whitelist
     * (sama persis atau diikuti '/' atau '?'), tanpa skema, host, '//host', atau karakter kontrol.
     * Mendukung deployment di bawah base path (misal '/monitoring-defect/public').
     */
    public static function sanitize(?string $returnUrl, ?string $basePath = null): ?string
    {
        if ($returnUrl === null || trim($returnUrl) === '') {
            return null;
        }

        $trimmed = trim($returnUrl);

        // Tolak karakter kontrol / newline (CR, LF, TAB, NUL, dll.)
        if (preg_match('/[\x00-\x1F\x7F]/', $trimmed)) {
            return null;
        }

        // Tolak URL absolut dengan skema (http:, https:, javascript:, data:, dll.)
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $trimmed)) {
            return null;
        }

        // Tolak protocol-relative '//', backslash Windows '\\', atau kombinasi '/\' / '\/'
        if (
            str_starts_with($trimmed, '//')
            || str_contains($trimmed, '\\')
        ) {
            return null;
        }

        $parsed = parse_url($trimmed);
        if (
            $parsed === false
            || isset($parsed['scheme'])
            || isset($parsed['host'])
            || isset($parsed['user'])
            || isset($parsed['pass'])
            || isset($parsed['port'])
        ) {
            return null;
        }

        $path = $parsed['path'] ?? '';
        if ($path === '' || !str_starts_with($path, '/')) {
            return null;
        }

        $resolvedBasePath = $basePath ?? (function_exists('request') ? (request()?->getBasePath() ?? '') : '');
        $normalizedBasePath = rtrim(trim((string) $resolvedBasePath), '/');
        if ($normalizedBasePath !== '' && !str_starts_with($normalizedBasePath, '/')) {
            $normalizedBasePath = '/' . $normalizedBasePath;
        }
        if ($normalizedBasePath === '/') {
            $normalizedBasePath = '';
        }

        if ($normalizedBasePath !== '') {
            if (!str_starts_with($path, $normalizedBasePath . '/')) {
                return null;
            }
            $relativePath = substr($path, strlen($normalizedBasePath));
        } else {
            $relativePath = $path;
        }

        $matchedPrefix = null;
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($relativePath === $prefix || str_starts_with($relativePath, $prefix . '/')) {
                $matchedPrefix = $prefix;
                break;
            }
        }

        if ($matchedPrefix === null) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Ubah hasil sanitize() menjadi target relatif aplikasi untuk redirect()->to().
     * Membuang basePath di awal tepat satu kali jika diikuti '/' atau akhir string
     * (atau '?' / '#'), sambil mempertahankan query string dan fragment.
     */
    public static function toRedirectTarget(?string $sanitized, string $basePath): ?string
    {
        if ($sanitized === null) {
            return null;
        }

        $normalizedBasePath = rtrim(trim($basePath), '/');
        if ($normalizedBasePath !== '' && !str_starts_with($normalizedBasePath, '/')) {
            $normalizedBasePath = '/' . $normalizedBasePath;
        }
        if ($normalizedBasePath === '' || $normalizedBasePath === '/') {
            return $sanitized;
        }

        if (str_starts_with($sanitized, $normalizedBasePath)) {
            $rest = substr($sanitized, strlen($normalizedBasePath));
            if ($rest === '') {
                return '/';
            }
            if (str_starts_with($rest, '/') || str_starts_with($rest, '?') || str_starts_with($rest, '#')) {
                return $rest;
            }
        }

        return $sanitized;
    }
}
