<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Ouvert = 'ouvert';
    case Traite = 'traite';
    case EnRecours = 'en_recours';
    case Clos = 'clos';

    public function label(): string
    {
        return match ($this) {
            self::Ouvert => 'À traiter',
            self::Traite => 'Traité',
            self::EnRecours => 'Recours en cours',
            self::Clos => 'Clos',
        };
    }
}
