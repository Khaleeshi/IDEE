<?php

namespace App\Enums;

/**
 * Accord de prochaine étape (CO-01) : proposé par l'un, accepté par l'autre.
 */
enum AgreementStatus: string
{
    case Proposee = 'proposee';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';
    case Realisee = 'realisee';

    public function label(): string
    {
        return match ($this) {
            self::Proposee => 'Proposée',
            self::Acceptee => 'Acceptée',
            self::Refusee => 'Refusée',
            self::Realisee => 'Réalisée',
        };
    }
}
