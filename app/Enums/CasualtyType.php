<?php

namespace App\Enums;

enum CasualtyType: string
{
    case Deceased = 'deceased';
    case Injured = 'injured';

    public function label(): string
    {
        return match ($this) {
            self::Deceased => 'Fallecido',
            self::Injured => 'Lesionado',
        };
    }
}
