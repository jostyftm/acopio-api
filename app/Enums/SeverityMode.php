<?php

namespace App\Enums;

enum SeverityMode: string
{
    case Single = 'single';
    case Multiple = 'multiple';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
