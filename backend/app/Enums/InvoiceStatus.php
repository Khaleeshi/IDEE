<?php

namespace App\Enums;

/**
 * Facture d'un contrat avec une organisation (PA-03).
 */
enum InvoiceStatus: string
{
    case Emise = 'emise';
    case Payee = 'payee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Emise => 'À payer',
            self::Payee => 'Payée',
            self::Annulee => 'Annulée',
        };
    }
}
