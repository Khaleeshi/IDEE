<?php

namespace App\Enums;

/**
 * Forme d'un accompagnement sans paiement (AC-01). Une prestation payante passe
 * par un devis (SE-02).
 */
enum AccompanimentKind: string
{
    case Mentorat = 'mentorat';
    case Benevolat = 'benevolat';

    public function label(): string
    {
        return match ($this) {
            self::Mentorat => 'Mentorat',
            self::Benevolat => 'Aide bénévole',
        };
    }
}
