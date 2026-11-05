<?php

namespace App\Support;

/**
 * Utilidades para el RUT chileno.
 *
 * Acepta "12.345.678-5", "12345678-5" o "123456785" y lo normaliza a
 * "12345678-5". El dígito verificador se calcula con módulo 11.
 */
final class Rut
{
    public static function normalize(string $rut): string
    {
        $clean = strtoupper(preg_replace('/[^0-9kK]/', '', $rut) ?? '');

        if (strlen($clean) < 2) {
            return $clean;
        }

        return substr($clean, 0, -1).'-'.substr($clean, -1);
    }

    public static function isValid(string $rut): bool
    {
        // Antes de normalizar, solo se permiten dígitos, puntos, guion y K.
        if (! preg_match('/^[0-9.]+-?[0-9kK]$/', trim($rut))) {
            return false;
        }

        $normalized = self::normalize($rut);

        if (! preg_match('/^(\d{7,8})-([\dK])$/', $normalized, $m)) {
            return false;
        }

        return self::checkDigit($m[1]) === $m[2];
    }

    public static function checkDigit(string $body): string
    {
        $sum = 0;
        $factor = 2;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += (int) $body[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $dv = 11 - ($sum % 11);

        return match ($dv) {
            11 => '0',
            10 => 'K',
            default => (string) $dv,
        };
    }

    /** Versión enmascarada para logs: 12345678-5 → ****5678-5 */
    public static function mask(string $rut): string
    {
        $normalized = self::normalize($rut);

        return str_repeat('*', max(0, strlen($normalized) - 6)).substr($normalized, -6);
    }
}
