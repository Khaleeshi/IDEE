<?php

namespace App\Enums;

/**
 * Étapes d'une demande de devis et de la prestation qui suit (SE-02).
 */
enum QuoteStatus: string
{
    case Demandee = 'demandee';
    case DevisEnvoye = 'devis_envoye';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';
    case Livree = 'livree';
    case Terminee = 'terminee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Demandee => 'Devis demandé',
            self::DevisEnvoye => 'Devis reçu',
            self::Acceptee => 'Prestation en cours',
            self::Refusee => 'Devis refusé',
            self::Livree => 'Prestation livrée',
            self::Terminee => 'Terminée',
            self::Annulee => 'Annulée',
        };
    }
}
