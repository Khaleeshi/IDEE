<?php

namespace App\Enums;

/**
 * Moyens de paiement des offres (PA-01). La carte bancaire viendra avec un agrégateur.
 */
enum PaymentMethod: string
{
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';

    public function label(): string
    {
        return match ($this) {
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
        };
    }
}
