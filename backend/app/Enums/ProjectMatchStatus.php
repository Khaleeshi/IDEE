<?php

namespace App\Enums;

/**
 * Proposition de rapprochement entre deux projets proches (PR-08, RG-12).
 */
enum ProjectMatchStatus: string
{
    case Proposee = 'proposee';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::Proposee => 'Proposé',
            self::Acceptee => 'Accepté',
            self::Refusee => 'Décliné',
        };
    }
}
