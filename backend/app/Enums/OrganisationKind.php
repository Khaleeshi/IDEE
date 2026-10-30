<?php

namespace App\Enums;

/**
 * Types d'organisations présentes sur IDEE (section 3 du cahier des charges).
 */
enum OrganisationKind: string
{
    case Entreprise = 'entreprise';
    case Accompagnement = 'accompagnement';
    case Institution = 'institution';
    case Financeur = 'financeur';
    case Association = 'association';

    public function label(): string
    {
        return match ($this) {
            self::Entreprise => 'Entreprise',
            self::Accompagnement => 'Incubateur ou structure d\'accompagnement',
            self::Institution => 'Institution publique',
            self::Financeur => 'Banque, IMF ou fonds',
            self::Association => 'Association, ONG ou GIE',
        };
    }

    /**
     * Organisations qui peuvent publier des programmes dans le répertoire (OR-01).
     */
    public function publishesProgrammes(): bool
    {
        return $this !== self::Entreprise;
    }
}
