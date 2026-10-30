<?php

namespace App\Enums;

/**
 * État d'un achat (PA-01).
 */
enum PaymentStatus: string
{
    case EnAttente = 'en_attente';
    case Payee = 'payee';
    case Echouee = 'echouee';
    case Expiree = 'expiree';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Payee => 'Payé',
            self::Echouee => 'Échoué',
            self::Expiree => 'Expiré',
            self::Refusee => 'Refusé',
        };
    }
}
