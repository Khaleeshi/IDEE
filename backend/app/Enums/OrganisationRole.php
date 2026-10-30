<?php

namespace App\Enums;

/**
 * Rôle d'un membre dans une organisation (CP-05).
 */
enum OrganisationRole: string
{
    case Administrateur = 'administrateur';
    case Membre = 'membre';

    public function label(): string
    {
        return match ($this) {
            self::Administrateur => 'Gère la page',
            self::Membre => 'Membre',
        };
    }
}
