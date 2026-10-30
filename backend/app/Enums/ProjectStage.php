<?php

namespace App\Enums;

/**
 * Cycle de vie d'un projet (PR-03).
 */
enum ProjectStage: string
{
    case Idee = 'idee';
    case Validation = 'validation';
    case Equipe = 'equipe';
    case Prototype = 'prototype';
    case Lancement = 'lancement';
    case Croissance = 'croissance';
    case Export = 'export';

    public function label(): string
    {
        return match ($this) {
            self::Idee => 'Idée',
            self::Validation => 'Validation',
            self::Equipe => "Constitution de l'équipe",
            self::Prototype => 'Prototype',
            self::Lancement => 'Lancement',
            self::Croissance => 'Croissance',
            self::Export => 'Export',
        };
    }
}
