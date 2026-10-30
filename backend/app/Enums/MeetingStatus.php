<?php

namespace App\Enums;

/**
 * Rendez-vous proposé par l'un, confirmé par l'autre (MR-07).
 */
enum MeetingStatus: string
{
    case Proposee = 'proposee';
    case Confirmee = 'confirmee';
    case Refusee = 'refusee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Proposee => 'Proposé',
            self::Confirmee => 'Confirmé',
            self::Refusee => 'Refusé',
            self::Annulee => 'Annulé',
        };
    }
}
