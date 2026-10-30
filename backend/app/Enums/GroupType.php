<?php

namespace App\Enums;

/**
 * Groupes du réseau (FC-05).
 */
enum GroupType: string
{
    case Secteur = 'secteur';
    case Region = 'region';

    public function label(): string
    {
        return match ($this) {
            self::Secteur => 'Secteur',
            self::Region => 'Région',
        };
    }
}
