<?php

namespace App\Enums;

/**
 * Forme d'un rendez-vous (MR-07).
 */
enum MeetingMode: string
{
    case Visio = 'visio';
    case Presentiel = 'presentiel';

    public function label(): string
    {
        return match ($this) {
            self::Visio => 'En visio',
            self::Presentiel => 'En présentiel',
        };
    }
}
