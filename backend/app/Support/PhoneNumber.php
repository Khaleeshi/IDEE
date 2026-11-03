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

    /**
     * @param  string|null  $country  pays choisi à l'inscription (code ISO) : son
     *                                indicatif s'ajoute à un numéro saisi sans « + »
     */
    public static function normalize(string $input, ?string $country = null): ?string
    {
        $number = preg_replace('/[\s.\-()]/', '', trim($input)) ?? '';

        if (str_starts_with($number, '00')) {
            $number = '+'.substr($number, 2);
        }

        $dialCode = $country ? Countries::dialCode($country) : null;
        if ($dialCode !== null && $dialCode !== self::SENEGAL_PREFIX && ! str_starts_with($number, '+') && $number !== '') {
            // 06 12 34 56 78 depuis la France devient +33612345678 : le 0 national tombe.
            $number = $dialCode.ltrim($number, '0');
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
