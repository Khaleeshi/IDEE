<?php

namespace App\Enums;

/**
 * Autorisation de consulter les documents privés d'un projet (RG-05).
 */
enum DocumentAccessStatus: string
{
    case Demandee = 'demandee';
    case Accordee = 'accordee';
    case Refusee = 'refusee';
    case Revoquee = 'revoquee';

    public function label(): string
    {
        return match ($this) {
            self::Demandee => 'Demandée',
            self::Accordee => 'Accordée',
            self::Refusee => 'Refusée',
            self::Revoquee => 'Révoquée',
        };
    }
}
