<?php

namespace App\Support;

class SubDefectHelper
{
    /**
     * Derive sub defect code from name if matching pattern '^(\d+)\.([A-Za-z])\b'.
     * Example: '1.A - CROSS CIRCUIT' -> '1A'.
     * Returns null if name does not match the pattern.
     */
    public static function deriveCode(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        if (preg_match('/^(\d+)\.([A-Za-z])\b/', trim($name), $matches)) {
            return $matches[1] . strtoupper($matches[2]);
        }

        return null;
    }
}
