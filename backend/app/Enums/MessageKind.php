<?php

namespace App\Enums;

/**
 * Nature d'un message de la messagerie (MR-04).
 */
enum MessageKind: string
{
    case Texte = 'texte';
    case Vocal = 'vocal';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Texte => 'Texte',
            self::Vocal => 'Message vocal',
            self::Document => 'Document',
        };
    }
}
