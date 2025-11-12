<?php

namespace App\Support;

final class Num
{
    /**
     * Normalize a human-entered number to a dot-decimal string with up to $scale dp.
     * Accepts: "1,234.50", "1.234,50", "1 234,5", "1234", "IDR 1,234.5000", etc.
     * Returns: "1234.5", "1234.50", "1234.0000" (string) or null if empty.
     */
    public static function normalize(?string $s, int $scale = 4): ?string
    {
        if ($s === null) return null;
        $s = trim($s);
        if ($s === '') return null;

        // Keep digits, comma, dot, minus
        $s = preg_replace('/[^0-9,\.\-]+/', '', $s) ?? '';
        if ($s === '' || $s === '-' ) return null;

        // If both separators appear, assume the LAST one is the decimal.
        $lastDot   = strrpos($s, '.');
        $lastComma = strrpos($s, ',');

        if ($lastDot !== false && $lastComma !== false) {
            $decPos = max($lastDot, $lastComma);
            $int = preg_replace('/[^\d\-]/', '', substr($s, 0, $decPos)) ?? '0';
            $dec = preg_replace('/\D/', '', substr($s, $decPos + 1)) ?? '';
            $s = ($int === '' ? '0' : $int) . '.' . $dec;
        } elseif ($lastComma !== false) {
            // Only comma present → treat comma as decimal
            $s = str_replace('.', '', $s);   // any dot before was thousand
            $s = str_replace(',', '.', $s);
        } else {
            // Only dots or pure digits → dots are decimal, remove grouping
            // Remove all dots except the last one (if many)
            if (substr_count($s, '.') > 1) {
                $parts = explode('.', $s);
                $dec   = array_pop($parts);
                $int   = implode('', $parts);
                $s = $int . '.' . $dec;
            }
        }

        // Now $s is "[-]\d+(.\d+)?"
        // Limit/normalize scale
        if (!str_contains($s, '.')) {
            return $s . ($scale > 0 ? '.' . str_repeat('0', $scale) : '');
        }
        [$i, $d] = explode('.', $s, 2);
        $d = substr($d, 0, $scale);
        return $i . '.' . str_pad($d, $scale, '0');
    }

    /** Cast a normalized (or raw) numeric to float safely for quick math/display. */
    public static function toFloat(int|float|string|null $n): float
    {
        if (is_null($n)) return 0.0;
        if (is_string($n)) {
            // If it isn't normalized, try to normalize with scale 4; else cast
            $n2 = self::normalize($n, 4);
            return (float)($n2 ?? $n);
        }
        return (float)$n;
    }

    /** BCMath multiply with graceful fallback; returns string with $scale dp. */
    public static function mul(int|float|string|null $a, int|float|string|null $b, int $scale = 4): string
    {
        if (function_exists('bcmul')) {
            return bcmul((string)self::toFloat($a), (string)self::toFloat($b), $scale);
        }
        return number_format(self::toFloat($a) * self::toFloat($b), $scale, '.', '');
    }

    /** BCMath add with graceful fallback; returns string with $scale dp. */
    public static function add(int|float|string|null $a, int|float|string|null $b, int $scale = 4): string
    {
        if (function_exists('bcadd')) {
            return bcadd((string)self::toFloat($a), (string)self::toFloat($b), $scale);
        }
        return number_format(self::toFloat($a) + self::toFloat($b), $scale, '.', '');
    }

    /**
     * Format a number using EN-style: thousands = ',', decimal = '.'
     * Hide trailing zeros if $stripZeros = true and hide decimals completely if .0000.
     */
    public static function fmt(int|float|string|null $n, int $maxDp = 4, bool $stripZeros = true): string
    {
        $f = self::toFloat($n);
        // Decide dp: if fractional part is non-zero, show up to $maxDp (trim), else 0
        $dp = (fmod($f, 1.0) == 0.0) ? 0 : $maxDp;
        $out = number_format($f, $dp, '.', ',');
        if ($stripZeros && $dp > 0) {
            // Trim trailing zeros and maybe the dot
            $out = rtrim(rtrim($out, '0'), '.');
        }
        return $out;
    }

    /** Money formatter with currency prefix (IDR by default). */
    public static function fmtMoney(int|float|string|null $n, string $prefix = 'IDR ', int $maxDp = 4, bool $stripZeros = true): string
    {
        return $prefix . self::fmt($n, $maxDp, $stripZeros);
    }
}
