<?php

namespace App\Support;

/**
 * Normalise les numéros de téléphone au format international (E.164).
 * Un numéro sénégalais saisi sans indicatif reçoit le préfixe +221 ;
 * les numéros de la diaspora doivent être saisis avec leur indicatif.
 */
class PhoneNumber
{
    public const SENEGAL_PREFIX = '+221';

    public static function normalize(string $input): ?string
    {
        $number = preg_replace('/[\s.\-()]/', '', trim($input)) ?? '';

        if (str_starts_with($number, '00')) {
            $number = '+'.substr($number, 2);
        }

        if (preg_match('/^[37]\d{8}$/', $number) === 1) {
            $number = self::SENEGAL_PREFIX.$number;
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $number) === 1 ? $number : null;
    }

    /**
     * Présentation lisible : +221 77 123 45 67 pour un numéro sénégalais.
     */
    public static function format(string $number): string
    {
        if (preg_match('/^\+221(\d{2})(\d{3})(\d{2})(\d{2})$/', $number, $parts) === 1) {
            return sprintf('+221 %s %s %s %s', $parts[1], $parts[2], $parts[3], $parts[4]);
        }

        return $number;
    }
}
