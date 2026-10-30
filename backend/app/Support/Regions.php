<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Les quatorze régions administratives du Sénégal.
 */
final class Regions
{
    public const ALL = [
        'Dakar', 'Diourbel', 'Fatick', 'Kaffrine', 'Kaolack', 'Kédougou', 'Kolda',
        'Louga', 'Matam', 'Saint-Louis', 'Sédhiou', 'Tambacounda', 'Thiès', 'Ziguinchor',
    ];

    /**
     * Région correspondant à un identifiant d'adresse, par exemple « thies » pour Thiès.
     */
    public static function fromSlug(string $slug): ?string
    {
        foreach (self::ALL as $region) {
            if (Str::slug($region) === $slug) {
                return $region;
            }
        }

        return null;
    }
}
