<?php

namespace App\Enums;

/**
 * Rôles cumulables d'un membre (cahier des charges, CP-03).
 */
enum UserRole: string
{
    case Porteur = 'porteur';
    case Contributeur = 'contributeur';
    case Investisseur = 'investisseur';
    case Expert = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Porteur => 'Porteur de projet',
            self::Contributeur => 'Contributeur de compétences',
            self::Investisseur => 'Investisseur',
            self::Expert => 'Expert',
        };
    }

    /**
     * Rôles qui exigent une identité vérifiée pour être affichés (RG-11).
     */
    public function requiresIdentityVerification(): bool
    {
        return in_array($this, [self::Investisseur, self::Expert], true);
    }
}
