<?php

namespace App\Enums;

/**
 * Motifs de signalement (CF-04). Les soupçons d'arnaque passent en tête de file.
 */
enum ReportReason: string
{
    case Arnaque = 'arnaque';
    case FauxProfil = 'faux_profil';
    case Harcelement = 'harcelement';
    case ContenuInapproprie = 'contenu_inapproprie';
    case Spam = 'spam';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Arnaque => 'Arnaque ou demande d\'argent',
            self::FauxProfil => 'Faux profil ou usurpation',
            self::Harcelement => 'Harcèlement ou propos offensants',
            self::ContenuInapproprie => 'Contenu inapproprié',
            self::Spam => 'Publicité ou envois en masse',
            self::Autre => 'Autre motif',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::Arnaque => 3,
            self::FauxProfil, self::Harcelement => 2,
            default => 1,
        };
    }
}
