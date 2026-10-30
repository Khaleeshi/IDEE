<?php

namespace App\Enums;

/**
 * Décisions possibles d'un modérateur (cahier des charges, section 8).
 */
enum ModerationDecision: string
{
    case AucuneAction = 'aucune_action';
    case Avertissement = 'avertissement';
    case Retrait = 'retrait';
    case Suspension = 'suspension';
    case Exclusion = 'exclusion';

    public function label(): string
    {
        return match ($this) {
            self::AucuneAction => 'Aucune action',
            self::Avertissement => 'Avertissement',
            self::Retrait => 'Retrait du contenu',
            self::Suspension => 'Suspension du compte',
            self::Exclusion => 'Exclusion',
        };
    }

    /**
     * La personne visée est informée de toute décision autre que « aucune action ».
     */
    public function informsTarget(): bool
    {
        return $this !== self::AucuneAction;
    }
}
