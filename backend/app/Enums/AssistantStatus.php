<?php

namespace App\Enums;

/**
 * État d'une demande faite à l'assistant IA (section 7).
 */
enum AssistantStatus: string
{
    case EnCours = 'en_cours';
    case Pret = 'pret';
    case Echec = 'echec';

    public function label(): string
    {
        return match ($this) {
            self::EnCours => 'En préparation',
            self::Pret => 'Prêt',
            self::Echec => 'Échec',
        };
    }
}
