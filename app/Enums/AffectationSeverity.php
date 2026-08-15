<?php

namespace App\Enums;

enum AffectationSeverity: string
{
    case Partial = 'partial';
    case Total = 'total';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
