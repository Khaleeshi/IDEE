<?php

namespace App\Enums;

/**
 * Une collaboration n'est publiée qu'une fois confirmée par les deux parties (CO-03, FC-06).
 */
enum CollaborationStatus: string
{
    case EnAttente = 'en_attente';
    case Confirmee = 'confirmee';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de confirmation',
            self::Confirmee => 'Confirmée',
            self::Refusee => 'Non confirmée',
        };
    }
}
