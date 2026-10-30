<?php

namespace App\Http\Resources;

/**
 * Représente une valeur d'énumération avec son libellé en français.
 */
final class EnumResource
{
    /**
     * @return array{value: string|int, label: string}|null
     */
    public static function make(?\BackedEnum $enum): ?array
    {
        if ($enum === null) {
            return null;
        }

        return ['value' => $enum->value, 'label' => $enum->label()];
    }
}
