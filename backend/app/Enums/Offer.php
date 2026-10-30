<?php

namespace App\Enums;

/**
 * Offres payantes en packs prépayés (section 13, PA-01, PA-02).
 */
enum Offer: string
{
    case Pro = 'pro';
    case Investisseur = 'investisseur';
    case Sponsorise = 'sponsorise';

    public function label(): string
    {
        return match ($this) {
            self::Pro => 'Offre Pro entrepreneur',
            self::Investisseur => 'Offre investisseur',
            self::Sponsorise => 'Mise en avant « Sponsorisé »',
        };
    }

    /**
     * Ce que l'offre apporte, en une phrase.
     */
    public function benefits(): string
    {
        $config = config('idee.offers.'.$this->value);

        return match ($this) {
            self::Pro, self::Investisseur => $config['weekly_contacts'].' demandes de contact par semaine, '
                .$config['saved_searches'].' alertes enregistrées et les statistiques de visibilité de vos projets.',
            self::Sponsorise => 'Votre projet en tête de la recherche, avec la mention « Sponsorisé ». '
                .'Son score et les recommandations personnalisées ne changent pas.',
        };
    }

    public function price(): int
    {
        return (int) config('idee.offers.'.$this->value.'.price');
    }

    /**
     * Durée d'une période payée, en jours.
     */
    public function days(): int
    {
        return (int) config('idee.offers.'.$this->value.'.days');
    }

    /**
     * Une mise en avant porte sur un projet ; les autres offres sur le membre.
     */
    public function isForProject(): bool
    {
        return $this === self::Sponsorise;
    }

    /**
     * @return list<self>
     */
    public static function memberOffers(): array
    {
        return [self::Pro, self::Investisseur];
    }
}
