<?php

namespace App\Support;

/**
 * Pays proposés à l'inscription (CP-07) : le Sénégal d'abord, puis la sous-région
 * et les pays de la diaspora. Un numéro d'un autre pays se saisit avec son indicatif.
 */
class Countries
{
    /**
     * @var array<string, array{0: string, 1: string}> code ISO => [nom, indicatif]
     */
    private const LIST = [
        'SN' => ['Sénégal', '+221'],
        'ML' => ['Mali', '+223'],
        'GM' => ['Gambie', '+220'],
        'GN' => ['Guinée', '+224'],
        'GW' => ['Guinée-Bissau', '+245'],
        'MR' => ['Mauritanie', '+222'],
        'CI' => ['Côte d\'Ivoire', '+225'],
        'BF' => ['Burkina Faso', '+226'],
        'NE' => ['Niger', '+227'],
        'TG' => ['Togo', '+228'],
        'BJ' => ['Bénin', '+229'],
        'CM' => ['Cameroun', '+237'],
        'MA' => ['Maroc', '+212'],
        'FR' => ['France', '+33'],
        'BE' => ['Belgique', '+32'],
        'CH' => ['Suisse', '+41'],
        'IT' => ['Italie', '+39'],
        'ES' => ['Espagne', '+34'],
        'DE' => ['Allemagne', '+49'],
        'GB' => ['Royaume-Uni', '+44'],
        'US' => ['États-Unis', '+1'],
        'CA' => ['Canada', '+1'],
    ];

    /**
     * @return array<string, string> code => « Sénégal (+221) »
     */
    public static function options(): array
    {
        return array_map(fn (array $country) => "{$country[0]} ({$country[1]})", self::LIST);
    }

    public static function dialCode(string $code): ?string
    {
        return self::LIST[strtoupper($code)][1] ?? null;
    }

    /**
     * @return list<array{code: string, name: string, dial_code: string}>
     */
    public static function all(): array
    {
        return array_map(fn (string $code, array $country) => ['code' => $code, 'name' => $country[0], 'dial_code' => $country[1]], array_keys(self::LIST), self::LIST);
    }
}
