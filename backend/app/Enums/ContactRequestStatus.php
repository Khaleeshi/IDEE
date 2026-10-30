<?php

namespace App\Enums;

/**
 * Statuts d'une demande de contact : la règle « aucune demande sans
 * réponse » (MR-02, RG-03).
 */
enum ContactRequestStatus: string
{
    case Envoyee = 'envoyee';
    case Lue = 'lue';
    case Interesse = 'interesse';
    case PasMaintenant = 'pas_maintenant';
    case Refusee = 'refusee';
    case Expiree = 'expiree';

    public function label(): string
    {
        return match ($this) {
            self::Envoyee => 'Envoyée',
            self::Lue => 'Lue',
            self::Interesse => 'Intéressé',
            self::PasMaintenant => 'Pas maintenant',
            self::Refusee => 'Refusée',
            self::Expiree => 'Sans réponse',
        };
    }

    /**
     * La demande attend encore une réponse du destinataire.
     */
    public function isPending(): bool
    {
        return in_array($this, [self::Envoyee, self::Lue], true);
    }

    /**
     * Le destinataire a donné une réponse explicite.
     */
    public function isAnswered(): bool
    {
        return in_array($this, [self::Interesse, self::PasMaintenant, self::Refusee], true);
    }

    /**
     * Réponses que le destinataire peut donner.
     *
     * @return list<self>
     */
    public static function responses(): array
    {
        return [self::Interesse, self::PasMaintenant, self::Refusee];
    }
}
