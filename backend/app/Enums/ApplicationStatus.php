<?php

namespace App\Enums;

/**
 * Où en est une candidature déposée auprès d'un guichet extérieur, selon le membre.
 */
enum ApplicationStatus: string
{
    case EnPreparation = 'en_preparation';
    case Deposee = 'deposee';
    case EnExamen = 'en_examen';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::EnPreparation => 'En préparation',
            self::Deposee => 'Déposée',
            self::EnExamen => 'En examen',
            self::Acceptee => 'Acceptée',
            self::Refusee => 'Refusée',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Acceptee, self::Refusee], true);
    }
}
