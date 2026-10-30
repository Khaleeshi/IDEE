<?php

namespace App\Enums;

/**
 * Niveaux de vérification cumulables (cahier des charges, section 8).
 */
enum VerificationLevel: int
{
    case None = 0;
    case Phone = 1;
    case Identity = 2;
    case Organisation = 3;
    case QualifiedInvestor = 4;

    public function label(): string
    {
        return match ($this) {
            self::None => 'Non vérifié',
            self::Phone => 'Téléphone vérifié',
            self::Identity => 'Identité vérifiée',
            self::Organisation => 'Organisation vérifiée',
            self::QualifiedInvestor => 'Investisseur qualifié',
        };
    }

    public function isAtLeast(self $level): bool
    {
        return $this->value >= $level->value;
    }
}
