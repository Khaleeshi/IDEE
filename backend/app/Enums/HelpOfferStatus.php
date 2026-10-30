<?php

namespace App\Enums;

/**
 * Statut d'une proposition « Je peux aider » (AA-03).
 */
enum HelpOfferStatus: string
{
    case Proposee = 'proposee';
    case Acceptee = 'acceptee';
    case Declinee = 'declinee';

    public function label(): string
    {
        return match ($this) {
            self::Proposee => 'Proposée',
            self::Acceptee => 'Acceptée',
            self::Declinee => 'Déclinée',
        };
    }
}
