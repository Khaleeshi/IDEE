<?php

namespace App\Enums;

/**
 * Documents rédigés par l'assistant IA en plus du business plan.
 */
enum AssistantDocumentKind: string
{
    case PreparationRendezVous = 'preparation_rdv';
    case Presentation = 'presentation';

    public function label(): string
    {
        return match ($this) {
            self::PreparationRendezVous => 'Préparation du rendez-vous',
            self::Presentation => 'Présentation du projet',
        };
    }
}
