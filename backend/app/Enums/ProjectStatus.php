<?php

namespace App\Enums;

/**
 * Statut de visibilité d'un projet (RG-07).
 */
enum ProjectStatus: string
{
    case Actif = 'actif';
    case EnSommeil = 'en_sommeil';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Actif => 'Actif',
            self::EnSommeil => 'En sommeil',
            self::Archive => 'Archivé',
        };
    }
}
