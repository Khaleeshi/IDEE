<?php

namespace App\Enums;

/**
 * Point de suivi d'un projet dans une cohorte (OR-03).
 */
enum CheckinStatus: string
{
    case BonneVoie = 'bonne_voie';
    case ARisque = 'a_risque';
    case Difficulte = 'difficulte';

    public function label(): string
    {
        return match ($this) {
            self::BonneVoie => 'En bonne voie',
            self::ARisque => 'À surveiller',
            self::Difficulte => 'En difficulté',
        };
    }
}
