<?php

namespace App\Enums;

/**
 * Vérification d'une organisation par son RCCM et son NINEA (CF-03).
 */
enum OrganisationVerification: string
{
    case NonVerifiee = 'non_verifiee';
    case EnAttente = 'en_attente';
    case Verifiee = 'verifiee';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::NonVerifiee => 'Non vérifiée',
            self::EnAttente => 'Vérification en cours',
            self::Verifiee => 'Organisation vérifiée',
            self::Refusee => 'Vérification refusée',
        };
    }
}
