<?php

namespace App\Enums;

enum DocumentType: string
{
    case NationalId = 'CC';
    case ForeignerId = 'CE';
    case MinorId = 'TI';
    case Passport = 'PA';
    case Other = 'OTHER';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
