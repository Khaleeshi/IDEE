<?php

namespace App\Enums;

/**
 * Événements affichés dans le fil (FC-01).
 */
enum ActivityType: string
{
    case ProjetPublie = 'projet_publie';
    case BesoinPublie = 'besoin_publie';
    case MiseAJour = 'mise_a_jour';
    case Reussite = 'reussite';

    public function label(): string
    {
        return match ($this) {
            self::ProjetPublie => 'Nouveau projet',
            self::BesoinPublie => 'Nouvel appel à action',
            self::MiseAJour => 'Mise à jour',
            self::Reussite => 'Réussite',
        };
    }
}
