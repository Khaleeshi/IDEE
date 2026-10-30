<?php

namespace App\Enums;

/**
 * Inscription à une session de pitch (EV-01) : écouter, ou candidater pour présenter son projet.
 */
enum RegistrationStatus: string
{
    case Inscrit = 'inscrit';
    case Candidat = 'candidat';
    case Retenu = 'retenu';
    case NonRetenu = 'non_retenu';

    public function label(): string
    {
        return match ($this) {
            self::Inscrit => 'Inscrit',
            self::Candidat => 'Candidature envoyée',
            self::Retenu => 'Retenu pour présenter',
            self::NonRetenu => 'Non retenu',
        };
    }

    /**
     * La personne assiste à la session et reçoit le lien et le rappel.
     */
    public function attends(): bool
    {
        return in_array($this, [self::Inscrit, self::Retenu], true);
    }
}
