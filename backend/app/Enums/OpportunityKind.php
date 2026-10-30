<?php

namespace App\Enums;

/**
 * Nature d'une opportunité du répertoire (OP-01).
 */
enum OpportunityKind: string
{
    case Pret = 'pret';
    case Garantie = 'garantie';
    case Investissement = 'investissement';
    case Accompagnement = 'accompagnement';
    case Concours = 'concours';

    public function label(): string
    {
        return match ($this) {
            self::Pret => 'Prêt',
            self::Garantie => 'Garantie de crédit',
            self::Investissement => 'Investissement',
            self::Accompagnement => 'Accompagnement',
            self::Concours => 'Concours ou appel à projets',
        };
    }
}
