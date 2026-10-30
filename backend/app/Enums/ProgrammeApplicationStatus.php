<?php

namespace App\Enums;

/**
 * Candidature à un programme envoyée depuis IDEE (OR-02).
 */
enum ProgrammeApplicationStatus: string
{
    case Envoyee = 'envoyee';
    case Preselectionnee = 'preselectionnee';
    case Retenue = 'retenue';
    case NonRetenue = 'non_retenue';

    public function label(): string
    {
        return match ($this) {
            self::Envoyee => 'Envoyée',
            self::Preselectionnee => 'Présélectionnée',
            self::Retenue => 'Retenue',
            self::NonRetenue => 'Non retenue',
        };
    }

    /**
     * Décisions que l'organisation peut prendre.
     *
     * @return list<self>
     */
    public static function decisions(): array
    {
        return [self::Preselectionnee, self::Retenue, self::NonRetenue];
    }
}
