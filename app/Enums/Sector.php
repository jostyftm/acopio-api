<?php

namespace App\Enums;

enum Sector: string
{
    case Urban = 'urban';
    case Rural = 'rural';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
