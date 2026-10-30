<?php

namespace App\Enums;

/**
 * Statut d'un besoin (AA-04).
 */
enum NeedStatus: string
{
    case Ouvert = 'ouvert';
    case Comble = 'comble';
    case Abandonne = 'abandonne';

    public function label(): string
    {
        return match ($this) {
            self::Ouvert => 'Ouvert',
            self::Comble => 'Comblé',
            self::Abandonne => 'Abandonné',
        };
    }

    public function isClosed(): bool
    {
        return $this !== self::Ouvert;
    }
}
