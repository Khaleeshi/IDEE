<?php

namespace App\Enums;

/**
 * Rôles cumulables d'un membre (cahier des charges, CP-03). Les rôles donnent
 * des droits ; les métiers, eux, viennent du référentiel des métiers (PM-01).
 */
enum UserRole: string
{
    case Porteur = 'porteur';
    case Contributeur = 'contributeur';
    case Investisseur = 'investisseur';
    case Expert = 'expert';
    case Coach = 'coach';
    case Fournisseur = 'fournisseur';
    case Acheteur = 'acheteur';

    public function label(): string
    {
        return match ($this) {
            self::Porteur => 'Porteur de projet',
            self::Contributeur => 'Contributeur de compétences',
            self::Investisseur => 'Investisseur',
            self::Expert => 'Expert',
            self::Coach => 'Coach ou mentor',
            self::Fournisseur => 'Fournisseur',
            self::Acheteur => 'Acheteur',
        };
    }

    /**
     * Rôles qui exigent une identité vérifiée pour être affichés (RG-11).
     */
    public function requiresIdentityVerification(): bool
    {
        return in_array($this, [self::Investisseur, self::Expert, self::Coach], true);
    }
}
