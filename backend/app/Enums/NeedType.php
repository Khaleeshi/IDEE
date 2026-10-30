<?php

namespace App\Enums;

/**
 * Types de besoins qu'un projet peut publier (appels à action, AA-01).
 */
enum NeedType: string
{
    case Financement = 'financement';
    case Associe = 'associe';
    case Competence = 'competence';
    case Clients = 'clients';
    case Fournisseur = 'fournisseur';
    case Local = 'local';
    case Materiel = 'materiel';
    case Mentor = 'mentor';

    public function label(): string
    {
        return match ($this) {
            self::Financement => 'Financement',
            self::Associe => 'Associé',
            self::Competence => 'Compétence',
            self::Clients => 'Clients',
            self::Fournisseur => 'Fournisseur',
            self::Local => 'Local',
            self::Materiel => 'Matériel',
            self::Mentor => 'Mentor',
        };
    }
}
