<?php

namespace App\Enums;

/**
 * Événements du journal d'accès aux documents (RG-05).
 */
enum DocumentLogAction: string
{
    case Demande = 'demande';
    case Accord = 'accord';
    case Refus = 'refus';
    case Revocation = 'revocation';
    case Consultation = 'consultation';

    public function label(): string
    {
        return match ($this) {
            self::Demande => 'a demandé l\'accès',
            self::Accord => 'a reçu l\'accès',
            self::Refus => 's\'est vu refuser l\'accès',
            self::Revocation => 'a perdu l\'accès',
            self::Consultation => 'a consulté',
        };
    }
}
