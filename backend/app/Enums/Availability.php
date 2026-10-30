<?php

namespace App\Enums;

/**
 * Disponibilité affichée sur le profil (CP-02).
 */
enum Availability: string
{
    case Disponible = 'disponible';
    case Partielle = 'partielle';
    case Indisponible = 'indisponible';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible pour de nouveaux projets',
            self::Partielle => 'Quelques heures par semaine',
            self::Indisponible => 'Pas disponible en ce moment',
        };
    }
}
