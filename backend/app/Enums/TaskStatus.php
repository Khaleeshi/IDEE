<?php

namespace App\Enums;

/**
 * Colonnes du tableau de tâches d'un projet (CO-02).
 */
enum TaskStatus: string
{
    case AFaire = 'a_faire';
    case EnCours = 'en_cours';
    case Fait = 'fait';

    public function label(): string
    {
        return match ($this) {
            self::AFaire => 'À faire',
            self::EnCours => 'En cours',
            self::Fait => 'Fait',
        };
    }
}
