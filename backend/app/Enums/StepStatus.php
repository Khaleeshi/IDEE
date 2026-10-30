<?php

namespace App\Enums;

/**
 * Avancement d'un membre sur une étape de parcours (PG-05).
 */
enum StepStatus: string
{
    case AFaire = 'a_faire';
    case EnCours = 'en_cours';
    case EnAttente = 'en_attente';
    case Terminee = 'terminee';

    public function label(): string
    {
        return match ($this) {
            self::AFaire => 'À faire',
            self::EnCours => 'En cours',
            self::EnAttente => 'En attente',
            self::Terminee => 'Terminée',
        };
    }

    public function isDone(): bool
    {
        return $this === self::Terminee;
    }
}
