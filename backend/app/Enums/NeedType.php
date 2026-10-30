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

    /**
     * Mention portée par chaque recherche de financement (section 9, deuxième ligne rouge).
     */
    public const FUNDRAISING_NOTICE = 'Ceci n\'est pas une offre au public. Le montant est indicatif : aucun argent ne transite par IDEE, qui ne propose ni parts ni actions.';
}
