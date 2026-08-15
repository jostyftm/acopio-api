<?php

namespace App\Enums;

enum SpecialNeed: string
{
    case Children = 'children';
    case Senior = 'senior';
    case Disability = 'disability';
    case Pregnant = 'pregnant';
    case ChronicIllness = 'chronic_illness';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
