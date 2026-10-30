<?php

namespace App\Enums;

/**
 * Type d'apport d'une réponse à un appel à action (FC-03).
 */
enum HelpOfferKind: string
{
    case Aide = 'aide';
    case MiseEnRelation = 'mise_en_relation';

    public function label(): string
    {
        return match ($this) {
            self::Aide => 'Je peux aider',
            self::MiseEnRelation => "Je connais quelqu'un",
        };
    }
}
