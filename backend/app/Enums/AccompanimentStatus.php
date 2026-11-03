<?php

namespace App\Enums;

/**
 * Étapes d'un accompagnement (AC-01).
 */
enum AccompanimentStatus: string
{
    case Demandee = 'demandee';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case Refusee = 'refusee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Demandee => 'Demande envoyée',
            self::EnCours => 'En cours',
            self::Terminee => 'Terminé',
            self::Refusee => 'Refusé',
            self::Annulee => 'Annulé',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Demandee, self::EnCours], true);
    }
}
