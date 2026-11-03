<?php

namespace App\Enums;

/**
 * Moyens de paiement (PA-01, PA-04). La carte bancaire passe par Bictorys.
 */
enum PaymentMethod: string
{
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';
    case Carte = 'carte';

    public function label(): string
    {
        return match ($this) {
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
            self::Carte => 'Carte bancaire',
        };
    }
}
