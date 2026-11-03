<?php

namespace App\Enums;

/**
 * Poste d'un membre dans une organisation (CP-05, IN-01) : l'administrateur gère
 * tout, le gestionnaire les programmes et les cohortes, le coach suit les projets
 * qui lui sont confiés.
 */
enum OrganisationRole: string
{
    case Administrateur = 'administrateur';
    case Gestionnaire = 'gestionnaire';
    case Coach = 'coach';
    case Membre = 'membre';

    public function label(): string
    {
        return match ($this) {
            self::Administrateur => 'Gère la page',
            self::Gestionnaire => 'Gère les programmes et les cohortes',
            self::Coach => 'Coach ou mentor de la structure',
            self::Membre => 'Membre',
        };
    }
}
