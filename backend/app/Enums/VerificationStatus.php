<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case EnAttente = 'en_attente';
    case Approuvee = 'approuvee';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En cours d\'examen',
            self::Approuvee => 'Approuvée',
            self::Refusee => 'Refusée',
        };
    }
}
